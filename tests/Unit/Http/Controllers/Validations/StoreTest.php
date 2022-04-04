<?php declare(strict_types = 1);

namespace Unit\Http\Controllers\Validations;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Thiagoprz\CrudTools\Tests\TestCase;
use Unit\Http\Controllers\DummyController;
use Unit\Models\Dummy;

class StoreTest extends TestCase
{

    public function setUp(): void
    {
        parent::setUp();
        Route::post('/dummy', [DummyController::class, 'store']);
    }

    /**
     * @return void
     * @testdox Tests with working validations
     */
    public function test_validation_success()
    {
        /* @var Dummy $factoryData */
        $factoryData = Dummy::factory()->make();
        $response = $this->postJson('/dummy', [
            'name' => $factoryData->name,
            'email' => $factoryData->email,
            'phone' => $factoryData->phone,
            'city' => $factoryData->city,
        ]);
        $resource = $response->json();
        $this->assertEquals($factoryData->name, $resource['name']);
        $this->assertEquals($factoryData->email, $resource['email']);
        $this->assertEquals($factoryData->phone, $resource['phone']);
        $this->assertEquals($factoryData->city, $resource['city']);
        $this->assertEquals(Response::HTTP_OK, $response->status());
    }

    /**
     * @return void
     * @testdox Tests with failing validations
     */
    public function test_validation_failure()
    {
        $response = $this->postJson('/dummy', []);
        $resource = $response->json();
        $requiredMessage = 'The :field field is required.';
        $this->assertEquals([
            'error' => true,
            'errors' => [
                'name' => [
                    Str::replace(':field', 'name', $requiredMessage),
                ],
                'email' => [
                    Str::replace(':field', 'email', $requiredMessage),
                ],
                'phone' => [
                    Str::replace(':field', 'phone', $requiredMessage),
                ],
                'city' => [
                    Str::replace(':field', 'city', $requiredMessage),
                ],
            ],
        ], $resource);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->status());
    }

}