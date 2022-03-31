<?php
namespace Thiagoprz\CrudTools\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Thiagoprz\CrudTools\CrudToolsServiceProvider;


/**
 * @package Thiagoprz\CrudTools\Tests
 */
class TestCase extends OrchestraTestCase
{
    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
    }

    /**
     * @param $app
     * @return string[]
     */
    protected function getPackageProviders($app): array
    {
        return [
            CrudToolsServiceProvider::class,
        ];
    }
}