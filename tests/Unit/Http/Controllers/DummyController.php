<?php declare(strict_types = 1);

namespace Unit\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;
use Thiagoprz\CrudTools\Http\Controllers\ControllerCrud;
use Thiagoprz\CrudTools\Interfaces\ControllerCrudInterface;
use Unit\Models\Dummy;

class DummyController extends Controller implements ControllerCrudInterface
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
    use ControllerCrud;
    public $modelClass = Dummy::class;
}