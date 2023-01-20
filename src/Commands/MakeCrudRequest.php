<?php

namespace Thiagoprz\CrudTools\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class MakeCrudRequest extends GeneratorCommand
{
    const PLACEHOLDER_MODEL_NAME = '{{modelName}}';
    const PLACEHOLDER_MODEL_NAMESPACE = '{{modelNameSpace}}';
    const PLACEHOLDER_REQUEST_NAME = '{{name}}';
    const PLACEHOLDER_SCENARIO = '{{scenario}}';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud-request
                            {name : The request name.}
                            {scenario : The scenario for this request.}
                            {model : The name of the Model (eg: User or Models/User depending on your directory structure).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a CRUD request';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'Request';

    protected function getNameInput()
    {
        $scenarioUpper = Str::ucfirst($this->argument('scenario'));
        $modelName = class_basename($this->argument('model'));
        return $modelName . $scenarioUpper . 'Request';
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
        $model = $this->argument('model');
        $stub = Str::replace([
            self::PLACEHOLDER_MODEL_NAME,
            self::PLACEHOLDER_MODEL_NAMESPACE,
            self::PLACEHOLDER_REQUEST_NAME,
            self::PLACEHOLDER_SCENARIO,
        ], [
            class_basename($model),
            $model,
            class_basename($name),
            $this->argument('scenario'),
        ], $stub);
        $ret = $this->replaceNamespace($stub, $name);
        return $ret->replaceClass($stub, class_basename($name));
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return config('crud-tools.template_path') ? config('crud-tools.template_path') . '/Request.stub' : __DIR__ . '/../stubs/Request.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string $rootNamespace
     *
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace . '\\Http\Requests';
    }
}
