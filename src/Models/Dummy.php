<?php

namespace Thiagoprz\CrudTools\Models;

use Database\Factories\DummyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CrudTools\Interfaces\ModelCrudInterface;

class Dummy extends Model implements ModelCrudInterface
{
    use ModelCrud, HasFactory;

    /**
     * Table name
     * @var string
     */
    protected $table = 'dummy';

    /**
     * @var string[]
     */
    public static $searchable = [
        'id' => 'int',
        'name' => 'string',
        'email' => 'string',
    ];

    /**
     * @return DummyFactory
     */
    protected static function newFactory()
    {
        return DummyFactory::new();
    }
}