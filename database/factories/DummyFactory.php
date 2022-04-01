<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Thiagoprz\CrudTools\Models\Dummy;

class DummyFactory extends Factory
{

    protected $model = Dummy::class;

    public function definition()
    {
        return [
            'id' => $this->faker->numberBetween(1, 9999999),
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'phone' => $this->faker->phoneNumber(),
            'city' => $this->faker->city(),
        ];
    }
}