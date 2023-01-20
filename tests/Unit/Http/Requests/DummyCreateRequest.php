<?php

namespace Unit\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Thiagoprz\CrudTools\Interfaces\CrudRequestInterface;
use Unit\Models\Dummy;

class DummyCreateRequest extends FormRequest implements CrudRequestInterface
{

    public function rules(): array
    {
        return Dummy::validateOn('create');
    }

    public function data(): array
    {
        return $this->all();
    }
}
