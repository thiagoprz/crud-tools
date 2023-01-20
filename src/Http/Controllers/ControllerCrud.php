<?php declare(strict_types = 1);

namespace Thiagoprz\CrudTools\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Illuminate\Support\Facades\Storage;
use Thiagoprz\CrudTools\Interfaces\SearchInterface;
use Thiagoprz\CrudTools\Interfaces\ValidatesInterface;

/**
 * Trait ControllerCrud
 * @package Thiagoprz\EasyCrud\Http\Controllers
 * @property ValidatesInterface|SearchInterface $modelClass
 */
trait ControllerCrud
{
    /**
     * Disabling logs if not needed
     *
     * @var bool
     */
    public $disableLogs = false;

    /**
     * @param $forRedirect
     * @return string
     */
    public function getViewPath($forRedirect = false): string
    {
        $nsPrefix = '';
        $nsPrefixArr = explode('\\', (new \ReflectionObject($this))->getNamespaceName());
        if (end($nsPrefixArr) != 'Controllers') {
            $nsPrefix = strtolower(end($nsPrefixArr)) . ($forRedirect ? '/' : '.');
        }
        $modelNameArr = explode('\\', $this->modelClass);
        return $nsPrefix . strtolower(end($modelNameArr));
    }

    /**
     * List index
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $items = $this->model->search($request->all());
        if ($request->ajax() || $request->wantsJson()) {
            if (property_exists($this->modelClass, 'resourceForSearch')) {
                return $items;
            }
            return response()->json($items);
        }
        return view($this->getViewPath() . '.index', compact('items'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\View\View
     */
    public function create()
    {
        return view($this->getViewPath() . '.create');
    }

    /**
     * Display the specified resource.
     *
     *
     * @param  mixed $id
     * @return \Illuminate\View\View
     */
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
        if ($request->ajax() || $request->wantsJson()) {
            return $this->jsonModel($model);
        }
        return view($this->getViewPath() . '.show', !$this->disableLogs ? compact('model', 'logs') : compact('model'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  mixed $id
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $model = $this->modelClass::findOrFail($id);
        return view($this->getViewPath() . '.edit', compact('model'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param mixed $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $id)
    {
        if (isset($request->with_trashed) && property_exists($this->modelClass, 'withTrashedForbidden')) {
            $model = $this->modelClass::withTrashed()->findOrFail($id);
            if ($model->deleted_at) {
                $count = $model->forceDelete();
            } else {
                $count = $this->modelClass::destroy($id);
            }
        } else {
            $count = $this->modelClass::destroy($id);
        }
        $url = !$request->input('url_return') ? $this->getViewPath(true) : $request->input('url_return');
        $success = $count > 0;
        $error = !$success;
        $message = !$success ? __('No records were deleted') : __('crud.deleted');
        return $this->isAjax($request)
            ? response()->json(compact('success', 'error', 'message'))
            : redirect($url)->with('flash_message', $message);
    }

    /**
     * @param Request $request
     * @return bool
     */
    private function isAjax(Request $request): bool
    {
        return $request->ajax() || $request->wantsJson();
    }

    /**
     * Returns JSON representation of object
     *
     * @param $model
     * @return JsonResponse
     */
    private function jsonModel($model): JsonResponse
    {
        /** @var string $resourceForSearch */
        $output = isset($this->modelClass::$resourceForSearch)
            ? new $this->modelClass::$resourceForSearch($model)
            : $model;
        return response()->json($output);
    }

    /**
     * @param Request $request
     * @param ValidatesInterface|null $model
     * @return void
     */
    public function handleFileUploads(Request $request, $model = null): void
    {
        $uploads = [];
        $fileUploads = $model->fileUploads();
        foreach ($fileUploads as $file_upload => $file_data) {
            if ($request->hasFile($file_upload)) {
                $file = $request->file($file_upload);
                $upload = Storage::putFileAs(
                    $file_data['path'],
                    $file,
                    !isset($file_data['name']) ? $file->getClientOriginalName() : $file_data['name']
                );
                $uploads[$file_upload] = $upload;
            }
        }
        if (!empty($uploads)) {
            $model->update($uploads);
        }
    }
}
