<?php declare(strict_types = 1);

namespace Thiagoprz\CrudTools\Tests;

use Orchestra\Testbench\TestCase as OrchestraTestCase;
use Thiagoprz\CrudTools\CrudToolsServiceProvider;
use Thiagoprz\CrudTools\Tests\database\migrations\CreateDummyTable;


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
        $app['config']->set('app.debug', true);
        $app['config']->set('logging.default', 'daily');
        $app['config']->set('logging.channels.daily.path', 'logs/testing.log');
        touch(__DIR__ . '/../database/tests/testing.sqlite');
        require_once __DIR__ . '/database/migrations/create_dummy_table.php';
        (new CreateDummyTable)->up();
    }

    /**
     * @return void
     */
    public function tearDown(): void
    {
        (new CreateDummyTable)->down();
        touch(__DIR__ . '/../database/tests/testing.sqlite');
    }
}
