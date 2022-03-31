<?php

namespace Thiagoprz\CrudTools\Interfaces;

interface ModelCrudInterface
{
    public static function validateOn($scenario = 'create', $id = null): array;
    public static function validate($scenario = 'create', $id = null): array;
    public static function fileUpload(self $model): void;
    public static function search(array $data);
}