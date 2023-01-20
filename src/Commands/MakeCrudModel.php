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
    const PLACEHOLDER_PRIMARYKEY = '{{primaryKey}}';
    const PLACEHOLDER_FILLABLE = '{{fillable}}';
    const PLACEHOLDER_SEARCHABLE = '{{searchable}}';
    const PLACEHOLDER_TABLE = '{{table}}';
    const PLACEHOLDER_USE_LOGABLE = '{{useLogable}}';
    const PLACEHOLDER_LOGABLE = '{{logable}}';
    const PLACEHOLDER_PROPERTIES = '{{properties}}';
    const PLACEHOLDER_SOFTDELETES = '{{softDeletes}}';
    const PLACEHOLDER_USE_SOFTDELETES = '{{useSoftDeletes}}';
    const PLACEHOLDER_UPLOADS = '{{uploadInterface}}';
    const PLACEHOLDER_USE_UPLOADS = '{{useUploads}}';

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
                            {--logable : Implements logging on table using Spatie/Activitylog (specify if yes, no value needs to be passed). [optional]}
                            {--m|migration : Generates migration }';

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
            $table = $this->getTableName();
        }
        $stub = str_replace(self::PLACEHOLDER_TABLE, $table, $stub);

        $properties = [];

        $this->buildFillable($stub, $properties);

        $this->buildSearchable($stub, $properties);

        $propertiesReplace = '';

        if ($this->option('primaryKey')) {
            $primaryKey = $this->option('primaryKey');
            if (strstr($primaryKey, ',')) {
                $primaryKey_fields = explode(',', $primaryKey);
                $primaryKey_fields = array_map(function ($key) use (&$propertiesReplace) {
                    $propertiesReplace .= "* @property int $$key" . PHP_EOL;
                    return "'$key'";
                }, $primaryKey_fields);
                $stub = Str::replace(self::PLACEHOLDER_PRIMARYKEY, '[' . implode(', ', $primaryKey_fields) . ']', $stub);
            } else {
                $propertiesReplace .= "* @property int $$primaryKey" . PHP_EOL;
                $stub = Str::replace(self::PLACEHOLDER_PRIMARYKEY, "'$primaryKey'", $stub);
            }
        } else {
            $propertiesReplace .= '* @property int $id' . PHP_EOL;
            $stub = Str::replace(self::PLACEHOLDER_PRIMARYKEY, '\'id\'', $stub);
        }

        $propertiesReplace .= ' * @property mixed $' . implode(PHP_EOL . ' * @property mixed $', $properties);

        $softDeletes = $this->option('softDeletes');
        $stub = Str::replace(self::PLACEHOLDER_SOFTDELETES, $softDeletes ? 'use SoftDeletes;' : '', $stub);
        $stub = Str::replace(self::PLACEHOLDER_USE_SOFTDELETES . PHP_EOL, $softDeletes ? 'use Illuminate\Database\Eloquent\SoftDeletes;' : '', $stub);

        // Timestamps
        $propertiesReplace .= PHP_EOL . ' * @property \Carbon\Carbon $created_at';
        $propertiesReplace .= PHP_EOL . ' * @property \Carbon\Carbon $updated_at';
        if ($softDeletes) {
            $propertiesReplace .= PHP_EOL . ' * @property \Carbon\Carbon $deleted_at';
        }

        $stub = Str::replace(self::PLACEHOLDER_PROPERTIES, $propertiesReplace, $stub);

        $this->buildLogable($stub);

        $this->buildUpload($stub);

        $ret = $this->replaceNamespace($stub, $name);

        if ($this->option('migration')) {
            $this->createMigration();
        }

        return $ret->replaceClass($stub, $name);
    }

    /**
     * @param string $stub
     * @param array $properties
     * @return void
     */
    private function buildFillable(string &$stub, array &$properties): void
    {
        if ($this->option('fillable')) {
            $properties = $fillable_fields = explode(',', $this->option('fillable'));
            $fillable_fields = array_map(function ($field) {
                return "'$field'";
            }, $fillable_fields);
            if (count($fillable_fields) > 0) {
                $stub = Str::replace(self::PLACEHOLDER_FILLABLE, implode(', ', $fillable_fields), $stub);
            }
        } else {
            $stub = Str::replace(self::PLACEHOLDER_FILLABLE, '', $stub);
        }
    }

    /**
     * @param string $stub
     * @param array $properties
     * @return void
     */
    private function buildSearchable(string &$stub, array &$properties): void
    {
        if ($this->option('searchable')) {
            $searchable_fields = explode(',', $this->option('searchable'));
            $properties = array_unique(array_merge($searchable_fields, $properties), SORT_REGULAR);
            if (count($searchable_fields) > 0) {
                $searchable_fields = array_map(function ($field) {
                    return PHP_EOL . "\t\t'$field' => 'string'";
                }, $searchable_fields);
                $stub = Str::replace(self::PLACEHOLDER_SEARCHABLE, implode(', ', $searchable_fields) . ',' . PHP_EOL . "\t", $stub);
            }
        } else {
            $stub = Str::replace(self::PLACEHOLDER_SEARCHABLE . PHP_EOL, '', $stub);
        }
    }

    /**
     * @param string $stub
     * @return void
     */
    private function buildLogable(string &$stub): void
    {
        if ($this->option('logable')) {
            $stub = Str::replace(self::PLACEHOLDER_USE_LOGABLE, 'use Thiagoprz\CrudTools\Models\Logable;', $stub);
            $stub = Str::replace(self::PLACEHOLDER_LOGABLE, 'use Logable;', $stub);
        } else {
            $stub = Str::replace(self::PLACEHOLDER_USE_LOGABLE . PHP_EOL, '', $stub);
            $stub = Str::replace(self::PLACEHOLDER_LOGABLE . PHP_EOL, '', $stub);
        }
    }

    /**
     * Builds upload part on stub
     * @param string $stub
     * @return void
     */
    private function buildUpload(string &$stub): void
    {
        if ($this->option('uploads')) {
            $stub = Str::replace(self::PLACEHOLDER_USE_UPLOADS, 'use Thiagoprz\CrudTools\Interfaces\UploadsInterface;', $stub);
            $stub = Str::replace(self::PLACEHOLDER_UPLOADS, ', UploadsInterface', $stub);
            $upload  = <<<EOD

    /**
     * Upload files available on fillable fields, defined by attribute and customizable path
     *
     * @return array
     */
    public function fileUploads(): array
    {
        return [
            // TODO: adjust according to your model file fields 
            'photo' => [
                'path' => "photos/\$this->id",
                'name' => Str::slug(\$this->name) . '.jpg',
            ],
        ];
    }
}

EOD;
            $lastSemicolon = strrpos($stub, ';');
            $stub = Str::substrReplace($stub, PHP_EOL . $upload, $lastSemicolon + 1);
        } else {
            $stub = Str::replace(self::PLACEHOLDER_USE_UPLOADS . PHP_EOL, '', $stub);
            $stub = Str::replace(self::PLACEHOLDER_UPLOADS, '', $stub);
        }
    }

    /**
     * @return string
     */
    private function getTableName(): string
    {
        $modelName = explode('/', $this->argument('name'));
        $table = Str::snake($modelName);
        return Str::lower(Str::plural($table));
    }

    /**
     * Create a migration file for the model.
     *
     * @return void
     */
    private function createMigration()
    {
        $table = Str::snake(Str::pluralStudly(class_basename($this->argument('name'))));
        $this->call('make:migration', [
            'name' => "create_{$table}_table",
            '--create' => $table,
        ]);
    }
}
