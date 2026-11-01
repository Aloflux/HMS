<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Facility;
use App\Models\Membership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Membership>
 */
class MembershipFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'facility_id' => Facility::factory(),
            'user_id' => User::factory()->withoutMembership(),
            'role' => Role::Administrator,
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'accepted_at' => now(),
        ];
    }

    /**
     * An invitation the person has not accepted yet.
     */
    public function invited(): static
    {
        return $this->state(fn (array $attributes) => [
            'accepted_at' => null,
            'invited_at' => now(),
        ]);
    }
}
