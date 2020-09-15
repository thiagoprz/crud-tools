<?php
namespace Thiagoprz\CrudTools\Commands;

use Illuminate\Console\GeneratorCommand;

/**
 * Class MakeCrudModel
 * @package Thiagoprz\CrudTools\Commands
 */
class MakeCrudModel extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud-model
                            {name : The name of the model (with namespace).}
                            {--table= : The name of the table which the model is based on. [optional]}
                            {--primaryKey= : The name of the primary key. [optional - default id]}
                            {--fillable= : List of fillable columns (separated by commas). [optional]}
                            {--hidden= : List of hidden columns (separated by commas). [optional]}
                            {--searchable= : List of fields that will be present on search() method (separated by commas). [optional]}
                            {--softDeletes : Using soft deletes? (specify if yes, no value needs to be passed). [optional]}
                            {--uploads : Model has upload fields? (specify if yes, no value needs to be passed). [optional]}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a CRUD controller with all it\'s helpers';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Model';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return config('crud-tools.custom_template') ? config('crud-tools.path') . '/Model.stub' : __DIR__ . '/../stubs/Model.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace;
    }

    /**
     * Build the model class with the given name.
     *
     * @param  string  $name
     *
     * @return string
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());

        $table = $this->option('table');
        if (!$table) {
            $modelClass = explode('/', $this->argument('name'));
            $table = last($modelClass);
        }
        $stub = str_replace('{{table}}', $table, $stub);

        if ($this->option('fillable')) {
            $fillable_fields = explode(',', $this->option('fillable'));
            $fillable_fields = array_map(function($field) {
                return "'$field'";
            }, $fillable_fields);
            if (count($fillable_fields) > 0) {
                $stub = str_replace('{{fillable}}', implode(', ', $fillable_fields), $stub);
            }
        } else {
            $stub = str_replace('{{fillable}}', '', $stub);
        }

        if ($this->option('searchable')) {
            $searchable_fields = explode(',', $this->option('searchable'));
            if (count($searchable_fields) > 0) {
                $searchable_fields = array_map(function($field) {
                    return "\t\t'$field' => 'string'" . PHP_EOL;
                }, $searchable_fields);
                $stub = str_replace('{{searchable}}', PHP_EOL . implode(', ', $searchable_fields) . "\t", $stub);
            }
        } else {
            $stub = str_replace('{{searchable}}', '', $stub);
        }


        if ($this->option('primaryKey')) {
            $primaryKey = $this->option('primaryKey');
            if (strstr($primaryKey, ',')) {
                $primaryKey_fields = explode(',', $primaryKey);
                $primaryKey_fields = array_map(function($key) {
                    return "'$key'";
                }, $primaryKey_fields);
                $stub = str_replace('{{primaryKey}}', '[' . implode(', ', $primaryKey_fields) . ']', $stub);
            } else {
                $stub = str_replace('{{primaryKey}}', "'$primaryKey'", $stub);
            }
        } else {
            $stub = str_replace('{{primaryKey}}', '\'id\'', $stub);
        }

        $softDeletes = $this->option('softDeletes');
        $stub = str_replace('{{softDeletes}}', $softDeletes ? 'use SoftDeletes;' : '', $stub);
        $stub = str_replace('{{useSoftDeletes}}', $softDeletes ? 'use Illuminate\Database\Eloquent\SoftDeletes;' : '', $stub);

        if ($this->option('uploads')) {
            $upload  = <<<EOD

    /**
     * Upload files available on fillable fields, defined by attribute and customizable path
     *
     * @param \$model DummyClass
     * @return array
     */
    public static function fileUploads(DummyClass \$model)
    {
        return [
            /*'photo' => [
                'path' => 'photos/' . str_slug(\$model->name) . '.jpg',
            ],*/
        ];
    }
}

EOD;
            $lastSemicolon = strrpos($stub, ';');
            $stub = substr_replace($stub, PHP_EOL . $upload, $lastSemicolon + 1);
        }


        $ret = $this->replaceNamespace($stub, $name);

        return $ret->replaceClass($stub, $name);
    }
}
