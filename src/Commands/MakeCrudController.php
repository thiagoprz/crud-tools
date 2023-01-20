<?php

namespace Thiagoprz\CrudTools\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

/**
 * Class MakeCrudController
 * @package Thiagoprz\CrudTools\Commands
 */
class MakeCrudController extends GeneratorCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:crud-controller
                            {name : The name of the controler (with namespace).}
                            {model : The name of the Model (with namespace, eg: User or Models/User).}';

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
    protected $type = 'Controller';

    /**
     * Build the model class with the given name.
     *
     * @param string $name
     * @return string
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function buildClass($name)
    {
        $stub = $this->files->get($this->getStub());
        if (strpos($this->argument('model'), '/') === 0) { // External Model class
            $modelClass = str_replace('/', '\\', $this->argument('model'));
        } else {
            $modelClass = $this->rootNamespace() . str_replace('/', '\\', $this->argument('model'));
        }

        $class = new \ReflectionClass($modelClass);
        $stub = str_replace(
            ['{{modelName}}', '{{modelNamespace}}'],
            [$class->getShortName(), $class->getName()],
            $stub
        );

        $this->call('make:crud-request', [
            'name' => 'request',
            'scenario' => 'create',
            'model' => $class->getName(),
        ]);
        $this->call('make:crud-request', [
            'name' => 'request',
            'scenario' => 'update',
            'model' => $class->getName(),
        ]);

        return $this->replaceNamespace($stub, $name)
            ->replaceClass($stub, $name);
    }


    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return config('crud-tools.template_path')
            ? config('crud-tools.template_path') . '/Controller.stub'
            : __DIR__ . '/../stubs/Controller.stub';
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
        return $rootNamespace . '\\Http\Controllers';
    }
}
