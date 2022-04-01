<?php

namespace Thiagoprz\CrudTools\Models;

use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CrudTools\Interfaces\ModelCrudInterface;

class CrudModel extends Model implements ModelCrudInterface
{

    public static function validateOn($scenario = 'create', $id = null): array
    {
        // TODO: Implement validateOn() method.
    }

    public static function validate($scenario = 'create', $id = null): array
    {
        // TODO: Implement validate() method.
    }

    public static function search(array $data)
    {
        // TODO: Implement search() method.
    }

    public static function fileUpload(ModelCrudInterface $model): void
    {
        // TODO: Implement fileUpload() method.
    }
}