<?php

namespace Thiagoprz\CrudTools\Interfaces;

interface ModelCrudInterface
{
    public static function validateOn(string $scenario = 'create', int $id = null): array;
    public static function validations(int $id = null): array;
    public static function fileUploads(self $model): array;
    public static function search(array $data);
}