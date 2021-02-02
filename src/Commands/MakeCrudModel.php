<?php
namespace Thiagoprz\CrudTools\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

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
                            {--uploads : Model has upload fields? (specify if yes, no value needs to be passed). [optional]}
                            {--logable : Implements logging on table using Spatie/Activitylog (specify if yes, no value needs to be passed). [optional]}';

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
            $table = Str::lower(Str::plural(last($modelClass)));
        }
        $stub = str_replace('{{table}}', $table, $stub);

        $properties = [];

        if ($this->option('fillable')) {
            $properties = $fillable_fields = explode(',', $this->option('fillable'));
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
            $properties = array_unique(array_merge($searchable_fields, $properties), SORT_REGULAR);
            if (count($searchable_fields) > 0) {
                $searchable_fields = array_map(function($field) {
                    return PHP_EOL . "\t\t'$field' => 'string'";
                }, $searchable_fields);
                $stub = str_replace('{{searchable}}', implode(', ', $searchable_fields) . ',' . PHP_EOL . "\t", $stub);
            }
        } else {
            $stub = str_replace('{{searchable}}', '', $stub);
        }

        $propertiesReplace = '';

        if ($this->option('primaryKey')) {
            $primaryKey = $this->option('primaryKey');
            if (strstr($primaryKey, ',')) {
                $primaryKey_fields = explode(',', $primaryKey);
                $primaryKey_fields = array_map(function($key) use(&$propertiesReplace) {
                    $propertiesReplace .= "* @property int $$key". PHP_EOL;
                    return "'$key'";
                }, $primaryKey_fields);
                $stub = str_replace('{{primaryKey}}', '[' . implode(', ', $primaryKey_fields) . ']', $stub);
            } else {
                $propertiesReplace .= "* @property int $$primaryKey" . PHP_EOL;
                $stub = str_replace('{{primaryKey}}', "'$primaryKey'", $stub);
            }
        } else {
            $propertiesReplace .= '* @property int $id'. PHP_EOL;
            $stub = str_replace('{{primaryKey}}', '\'id\'', $stub);
        }

        $propertiesReplace .= ' * @property mixed $' . implode(PHP_EOL . ' * @property mixed $', $properties);

        $softDeletes = $this->option('softDeletes');
        $stub = str_replace('{{softDeletes}}', $softDeletes ? 'use SoftDeletes;' : '', $stub);
        $stub = str_replace('{{useSoftDeletes}}', $softDeletes ? 'use Illuminate\Database\Eloquent\SoftDeletes;' : '', $stub);

        // Timestamps
        $propertiesReplace .= PHP_EOL . ' * @property \Carbon\Carbon $created_at';
        $propertiesReplace .= PHP_EOL . ' * @property \Carbon\Carbon $updated_at';
        if ($softDeletes) {
            $propertiesReplace .= PHP_EOL . ' * @property \Carbon\Carbon $deleted_at';
        }

        $stub = str_replace('{{properties}}', $propertiesReplace, $stub);

        $logable = $this->option('logable');
        if ($logable) {
            $stub = str_replace('{{useLogable}}', 'use Thiagoprz\CrudTools\Models\Logable;', $stub);
            $stub = str_replace('{{logable}}', 'use Logable;', $stub);
        } else {
            $stub = str_replace('{{useLogable}}', '', $stub);
            $stub = str_replace('{{logable}}', '', $stub);
        }

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
            //'photo' => [
            //    'path' => 'photos/' . str_slug(\$model->name) . '.jpg',
            //],
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
