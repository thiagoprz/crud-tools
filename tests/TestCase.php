<?php
namespace Thiagoprz\CrudTools\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Capsule\Manager as DB;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Thiagoprz\CrudTools\CrudToolsServiceProvider;


/**
 * @package Thiagoprz\CrudTools\Tests
 */
class TestCase extends OrchestraTestCase
{
//    use RefreshDatabase;

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

    /**
     * @param $app
     * @return void
     */
    public function getEnvironmentSetUp($app)
    {
        $database = require __DIR__ . '/config/database.php';
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', $database['sqlite']);
        require_once __DIR__ . '/../database/migrations/create_dummy_table.php';
        (new \CreateDummyTable)->up();
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        (new \CreateDummyTable)->down();
    }
}