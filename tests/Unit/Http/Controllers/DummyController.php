<?php declare(strict_types = 1);

namespace Unit\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Thiagoprz\CrudTools\Http\Controllers\CrudController;
use Thiagoprz\CrudTools\Interfaces\CrudControllerInterface;
use Unit\Http\Requests\DummyCreateRequest;
use Unit\Http\Requests\DummyUpdateRequest;
use Unit\Models\Dummy;

class DummyController extends Controller implements CrudControllerInterface
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use CrudController;
    public $modelClass = Dummy::class;

    public function store(DummyCreateRequest $request)
    {
        $model = new $this->modelClass();
        $model->fill($request->only($model->getFillable()));
        $model->save();
        $this->handleFileUploads($request, $model);
        if ($this->isAjax($request)) {
            return $this->jsonModel($model);
        }
        $url = !$request->input('url_return') ? $this->getViewPath(true) . '/' . $model->id : $request->input('url_return');
        return redirect($url)->with('flash_message', trans('crud.added'));
    }

    public function update(DummyUpdateRequest $request, int $id)
    {
        $model = $this->modelClass::findOrFail($id);
        $this->handleFileUploads($request, $model);
        $model->update($request->only($model->getFillable()));
        if ($this->isAjax($request)) {
            return $this->jsonModel($model);
        }
        $url = !$request->input('url_return') ? $this->getViewPath(true) . '/' . $model->id : $request->input('url_return');
        return redirect($url)->with('flash_message', trans('crud.updated'));
    }
}
