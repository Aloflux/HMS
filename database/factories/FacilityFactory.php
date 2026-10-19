<?php

namespace Database\Factories;

use App\Models\Facility;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Facility>
 */
class FacilityFactory extends Factory
{
    protected $model = Facility::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company().' Clinic',
            'code' => strtoupper(fake()->unique()->bothify('??##')),
            'kind' => 'clinic',
            'country' => 'UG',
            'district' => 'Kampala',
            'bed_capacity' => 40,
            'is_active' => true,
        ];
    }
}
