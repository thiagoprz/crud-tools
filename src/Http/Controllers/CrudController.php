<?php

namespace Thiagoprz\CrudTools\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Spatie\Activitylog\Models\Activity;
use Thiagoprz\CrudTools\Interfaces\ControllerCrudInterface;
use Thiagoprz\CrudTools\Interfaces\ModelCrudInterface;

class CrudController extends Controller implements ControllerCrudInterface
{

    /**
     * The Model to be worked with the CRUD implementation
     * @var ModelCrudInterface
     */
    public ModelCrudInterface $modelClass;

    /**
     * Disabling logs if not needed
     * @var bool
     */
    public bool $disableLogs = false;

    /**
     * @param bool $forRedirect
     * @return string
     */
    public function getViewPath(bool $forRedirect = false): string
    {
        $ns_prefix = '';
        $ns_prefix_arr = explode('\\', (new \ReflectionObject($this))->getNamespaceName());
        if (end($ns_prefix_arr) != 'Controllers') {
            $ns_prefix = strtolower(end($ns_prefix_arr)) . ($forRedirect ? '/' : '.');
        }
        $model_name_arr = explode('\\', $this->modelClass);
        return $ns_prefix . strtolower(end($model_name_arr));
    }

    public function index(Request $request)
    {
        $items = $this->modelClass::search($request->all());
        if ($request->ajax() || $request->wantsJson())
        {
            if (property_exists($this->modelClass, 'resourceForSearch')) {
                return $items;
            }
            return response()->json($items);
        }
        return view($this->getViewPath() . '.index', compact('items'));
    }

    public function destroy(Request $request, $id)
    {
        if ($request->input('with_trashed') && property_exists($this->modelClass, 'withTrashedForbidden')) {
            $model = $this->modelClass::withTrashed()->findOrFail($id);
            if ($model->deleted_at) {
                $count = $model->forceDelete();
            } else {
                $count = $this->modelClass::destroy($id);
            }
        } else {
            $count = $this->modelClass::destroy($id);
        }
        $url = $request->input('url_return') ? $request->input('url_return') : $this->getViewPath(true);
        $success = $count > 0;
        $error = !$success;
        $message = !$success ? __('No records were deleted') : __('crud.deleted');
        return $this->isAjax() ? response()->json(compact('success', 'error', 'message')) : redirect($url)->with('flash_message', $message);
    }

    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function edit(Request $request, $id)
    {
        $model = $this->modelClass::findOrFail($id);
        return view($this->getViewPath() . '.edit', compact('model'));
    }

    public function show(Request $request, $id)
    {
        if (isset($request->with_trashed) && !isset($this->modelClass::$withTrashedForbidden)) {
            $model = $this->modelClass::withTrashed()->findOrFail($id);
        } else {
            $model = $this->modelClass::findOrFail($id);
        }
        if (!$this->disableLogs) {
            $logs = Activity::whereSubjectType($this->modelClass)
                ->whereSubjectId($id)
                ->orderBy('created_at', 'DESC')
                ->paginate(10);
        }
        if ($request->ajax() || $request->wantsJson())
        {
            return $this->jsonModel($model);
        }
        return view($this->getViewPath() . '.show', !$this->disableLogs ? compact('model', 'logs') : compact('model'));
    }

    public function create(Request $request)
    {
        return view($this->getViewPath() . '.create');
    }

    public function store(Request $request)
    {
        if ($request->ajax() || $request->wantsJson()) {
            $validation = Validator::make($request->all(), $this->modelClass::validateOn('create'));
            if ($validation->fails()) {
                return response()->json([
                    'error' => true,'errors' => $validation->errors()->messages()
                ], 419);
            }
        } else {
            $this->validate($request, $this->modelClass::validateOn('create'));
        }
        $requestData = $request->all();
        $model = $this->modelClass::create($requestData);
        $this->handleFileUploads($request, $model);
        if ($request->ajax() || $request->wantsJson())
        {
            return $this->jsonModel($model);
        }
        $url = !$request->input('url_return') ? $this->getViewPath(true) . '/' . $model->id : $request->input('url_return');
        return redirect($url)->with('flash_message', trans('crud.added'));
    }

    public function update(Request $request, $id)
    {
        if ($this->isAjax()) {
            $validation = Validator::make($request->all(), $this->modelClass::validateOn('update', $id));
            if ($validation->fails()) {
                return response()->json([
                    'error' => true,'errors' => $validation->errors()->messages()
                ], 419);
            }
        } else {
            $this->validate($request, $this->modelClass::validateOn('update', $id));
        }
        $requestData = $request->all();
        $model = $this->modelClass::findOrFail($id);
        $this->handleFileUploads($request, $model);
        $model->update($requestData);
        $url = $request->input('url_return') ? $request->input('url_return') : $this->getViewPath(true) . '/' . $model->id;
        return $this->isAjax() ? $this->jsonModel($model) : redirect($url)->with('flash_message', trans('crud.updated'));
    }

    /**
     * @param ModelCrudInterface $model
     * @return void
     */
    public function handleFileUploads(Request $request, ModelCrudInterface $model): void
    {
        if (!method_exists($this->modelClass, 'fileUploads')) {
            return;
        }
        $file_uploads = $this->modelClass::fileUploads($model);
        foreach ($file_uploads as $file_upload => $file_data) {
            if ($request->hasFile($file_upload)) {
                $file = $request->file($file_upload);
                $upload = Storage::putFileAs($file_data['path'], $file, !isset($file_data['name']) ? $file->getClientOriginalName() : $file_data['name']);
                $requestData[$file_upload] = $upload;
            }
        }
    }
}