<?php declare(strict_types = 1);

namespace Unit\Http\Controllers\Validations;

use Faker\Factory;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Thiagoprz\CrudTools\Tests\TestCase;
use Unit\Http\Controllers\DummyController;
use Unit\Models\Dummy;

class UpdateTest extends TestCase
{

    /**
     * @var \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model
     */
    private Dummy $dummy;

    public function setUp(): void
    {
        parent::setUp();
        Route::put('/dummy/{id}', [DummyController::class, 'update']);
        /* @var Dummy $factoryData */
        Dummy::factory()->create();
        $this->dummy = Dummy::first();
    }

    /**
     * @return void
     * @testdox Tests with working validations
     */
    public function test_validation_success()
    {
        $factory = Factory::create();
        $response = $this->putJson('/dummy/' . $this->dummy->id, [
            'name' => $factory->name,
            'email' => $factory->email,
            'phone' => $factory->phoneNumber,
            'city' => $factory->city,
        ]);
        $resource = $response->json();
        $this->assertNotEquals($this->dummy->name, $resource['name']);
        $this->assertNotEquals($this->dummy->email, $resource['email']);
        $this->assertNotEquals($this->dummy->phone, $resource['phone']);
        $this->assertNotEquals($this->dummy->city, $resource['city']);
        $this->assertEquals(Response::HTTP_OK, $response->status());
    }

    /**
     * @return void
     * @testdox Tests with failing validations
     */
    public function test_validation_failure()
    {
        $response = $this->putJson('/dummy/' . $this->dummy->id, []);
        $resource = $response->json();
        $requiredMessage = 'The :field field is required.';
        $this->assertEquals([
            'message' => 'The given data was invalid.',
            'errors' => [
                'name' => [
                    Str::replace(':field', 'name', $requiredMessage),
                ],
            ],
        ], $resource);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->status());
    }

}
