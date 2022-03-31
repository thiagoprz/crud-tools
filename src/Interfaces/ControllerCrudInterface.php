<?php

namespace Thiagoprz\CrudTools\Interfaces;

use Illuminate\Http\Request;

interface ControllerCrudInterface
{
    /**
     * @param $forRedirect
     * @return string
     */
    public function getViewPath(bool $forRedirect = false): string;

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request);

    /**
     * @param Request $request
     * @param int|string $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function destroy(Request $request, $id);

    /**
     * @param Request $request
     * @param int|string $id
     * @return mixed
     */
    public function edit(Request $request, $id);

    /**
     * @param Request $request
     * @return mixed
     */
    public function show(Request $request, $id);

    /**
     * @param Request $request
     * @return mixed
     */
    public function create(Request $request);

    /**
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function store(Request $request);


    /**
     * @param Request $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector|\Illuminate\Http\Resources\Json\JsonResource
     */
    public function update(Request $request, $id);

    /**
     * @param Request $request
     * @param ModelCrudInterface $model
     * @return void
     */
    public function handleFileUploads(Request $request, ModelCrudInterface $model): void;
}