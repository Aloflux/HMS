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
            'onboarded_at' => now(),
        ];
    }

    /**
     * A self-registered facility whose owner has not finished the setup wizard.
     */
    public function pendingOnboarding(): static
    {
        return $this->state(fn (array $attributes) => [
            'onboarded_at' => null,
        ]);
    }
}
