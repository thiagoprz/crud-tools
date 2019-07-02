<?php

namespace Thiagoprz\CrudTools\Http\Controllers;

use \Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

/**
 * Trait ControllerCrud
 * @package Thiagoprz\EasyCrud\Http\Controllers
 * @property string modelClass
 */
trait ControllerCrud
{

    public function getViewPath($forRedirect = false)
    {
        $ns_prefix = '';
        $ns_prefix_arr = explode('\\', (new \ReflectionObject($this))->getNamespaceName());
        if (end($ns_prefix_arr) != 'Controllers') {
            $ns_prefix = strtolower(end($ns_prefix_arr)) . ($forRedirect ? '/' : '.');
        }
        $model_name_arr = explode('\\', $this->modelClass);
        return $ns_prefix . strtolower(end($model_name_arr));
    }

    /**
     * List index
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $items = $this->modelClass::search($request->all());
        if ($request->ajax())
        {
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
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function store(Request $request)
    {
        $this->validate($request, $this->modelClass::validateOn('create'));
        $requestData = $request->all();
        $model = $this->modelClass::create($requestData);
        $url = !$request->input('url_return') ? $this->getViewPath(true) . '/' . $model->id : $request->input('url_return');
        return redirect($url)->with('flash_message', trans('crud.added'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\View\View
     */
    public function show($id)
    {
        ${strtolower(class_basename($this->modelClass))} = $this->modelClass::findOrFail($id);
        $logs = Activity::whereSubjectType($this->modelClass)
            ->whereSubjectId($id)
            ->orderBy('created_at', 'DESC')
            ->paginate(10);
        return view($this->getViewPath() . '.show', compact(strtolower(class_basename($this->modelClass)), 'logs'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     *
     * @return \Illuminate\View\View
     */
    public function edit($id)
    {
        $model = $this->modelClass::findOrFail($id);
        return view($this->getViewPath() . '.edit', compact('model'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param  int $id
     *
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, $this->modelClass::validateOn('update'));
        $requestData = $request->all();
        $model = $this->modelClass::findOrFail($id);
        $model->update($requestData);
        $url = !$request->input('url_return') ? $this->getViewPath(true) . '/' . $model->id : $request->input('url_return');
        return redirect($url)->with('flash_message', trans('crud.updated'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Request $request, $id)
    {
        $this->modelClass::destroy($id);
        $url = !$request->input('url_return') ? $this->getViewPath(true) : $request->input('url_return');
        return redirect($url)->with('flash_message', trans('crud.deleted'));
    }


}