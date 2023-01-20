<?php declare(strict_types = 1);

namespace Thiagoprz\CrudTools\Interfaces;

interface CrudRequestInterface
{
    public function rules(): array;
    public function data(): array;
}
