<?php declare(strict_types = 1);

namespace Unit\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Thiagoprz\CrudTools\database\factories\DummyFactory;
use Thiagoprz\CrudTools\Interfaces\UploadsInterface;
use Thiagoprz\CrudTools\Interfaces\ValidatesInterface;
use Thiagoprz\CrudTools\Models\CrudModel;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $phone
 * @property string $city
 */
class Dummy extends Model implements ValidatesInterface, UploadsInterface
{
    use CrudModel, HasFactory;

    /**
     * Table name
     * @var string
     */
    protected $table = 'dummy';

    protected $fillable = ['name', 'email', 'phone', 'city'];

    /**
     * @var string[]
     */
    public static $searchable = [
        'id' => 'int',
        'name' => 'string',
        'email' => 'string',
        'phone' => 'string',
        'city' => 'string',
    ];

    /**
     * @var string[]
     */
    public static $validations = [
        'create' => [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:dummy|max:255',
            'phone' => 'required|string|max:255',
            'city' => 'required|string|max:255',
        ],
        'update' => [
            'name' => 'required|string|max:255',
        ],
    ];

    /**
     * @return DummyFactory
     */
    protected static function newFactory()
    {
        return DummyFactory::new();
    }

    /**
     * @return array[]
     */
    public function fileUploads(): array
    {
        return [
            'photo' => [
                'name' => "$this->name.jpg",
                'path' => "photos/dummy",
            ],
        ];
    }
}
