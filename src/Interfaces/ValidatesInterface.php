<?php declare(strict_types = 1);

namespace Thiagoprz\CrudTools\Interfaces;

interface ValidatesInterface
{
    public static function validateOn(string $scenario = 'create', int $id = null): array;
    public static function validations(int $id = null): array;
}
