# Laravel Crud Tools
Easy to use Laravel CRUD package with Controller, Model and Log system built in.

## Table of contents
* [Installation](#installation)
* [Setup](#setup)
* [CRUD Controller](#crud-controller)
* [CRUD Model](#crud-model)


## Installation
Install through composer using: ``composer install thiagoprz\crud-tools``

Run after install scripts for Spatie Activity Logger:

``php artisan vendor:publish --provider="Spatie\Activitylog\ActivitylogServiceProvider" --tag="migrations"``

Run migrations:

``php artisan migrate``

You can read Spatie Activity Log [Documentations](https://github.com/spatie/laravel-activitylog)


## Setup

### CRUD Controller:
A CRUD Controller can be achieve by just creating a standard controller class using ControllerCrud trait.

The next step is to create a folder inside ``resources/views`` with the desired namespace or on root folder if the controller won't be using a specific namespace (admin on the example).
```
<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Thiagoprz\CrudTools\Http\Controllers\ControllerCrud;

class UserController extends Controller
{
    use ControllerCrud;
    public $modelClass = User::class;
}
```

Views directory structure used by Controller CRUD based on the above example:

Folder: 
> views/admin/user

Files:
> create.blade.php

> edit.blade.php 

Available vars: $model (the model being updated) 

> form.blade.php

Available vars: $model (the model being updated - only on edit action)

> index.blade.php

Available vars: $items (the pagination object containing a filtered collection of the model)

> show.blade.php

Available vars: $model (the model being displayed)

### CRUD Model:

For models you just need to add the trait ModelCrud and after that create a static property declaring model's validations (based on the create, update and/or delete scenarios), default order, filtering rules, upload file rules, define resources, and with / countable relationships.


- Validations:
```
<?php
...
use Thiagoprz\CrudTools\Models\ModelCrud;
class User extends Authenticatable
{
    use ModelCrud;
    
    /**
     * Model validations
     *
     * @var array
     */
    static $validations = [
        'create' => [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ],
        'update' => [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ],
    ];
    ...
}
```
- Searchable fields:

You can create a $searchable property that will hold fields allowed to be searched on the static method **search()** - very useful with the ControllerCrud. 

```
<?php
...
use Thiagoprz\CrudTools\Models\ModelCrud;
class User extends Authenticatable
{
    use ModelCrud;
    /**
     * Fields that can be searched by (static)method search()
     *
     * @var array
     */
    static $searchable = [
        'id' => 'int',
        'name' => 'string',
    ];
    ...
}
```

- Sortable fields:

You can defined the fields that will be used as default sorting of your model on the index action. Also, you can pass an "order" input used by the search method allowing the override the default order defined by this variable. 

```
<?php
...
use Thiagoprz\CrudTools\Models\ModelCrud;
class Books extends Model
{
    use ModelCrud;
    /**
     * Default order
     *
     * @var array
     */
    static $search_order = [
        'title' => 'ASC',
        'updated_at' => 'DESC',
        'created_at' => 'DESC',
    ];
    ...
}
```


- Upload fields:

You can create a fileUploads method to define which and where your uploadable fields will store the files: 

```
<?php
...
use Thiagoprz\CrudTools\Models\ModelCrud;
class User extends Authenticatable
{
    use ModelCrud;
    ...
    /**
     * @param Campaign $model
     * @return array
     */
    public static function fileUploads(Campaign $model)
    {
        return [
            'FIELD_NAME' => [
                'path' => 'FOLDER', // Mandatory
                'name' => 'FILE_NAME', // (OPTIONAL)if not provided will be the file original name 
            ],
        ];
    }
    ...
}
```

### CRUD Generators

- Controllers:

You can create a standard Controller to work with a model by using the following command:

``` php artisan make:crud-controller NAMESPACE1/NAMEController NAMESPACE2/Model ```

> NAMESPACE1: is the name of the Controller's namespace
>
> NAMEController: is the name of the controller
>
> NAMESPACE2: is the name of the Model's namespace
>
> Model: Name of the model

## Supported By Jetbrains
This project is being developed with the help of Jetbrains through its project to support Open Source software.

![Test Image 1](support/jetbrains.svg)
