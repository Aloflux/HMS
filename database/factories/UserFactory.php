<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Models\Facility;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * Attributes that describe the person's place at a facility rather than the person.
     * Passing them to the factory gives the new user a membership with these values.
     *
     * @var list<string>
     */
    private const MEMBERSHIP_ATTRIBUTES = ['facility_id', 'role', 'permissions', 'department_id'];

    protected static ?string $password;

    /**
     * @var array<int, array<string, mixed>>
     */
    private static array $pendingMemberships = [];

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'facility_id' => Facility::factory(),
            'role' => Role::Administrator,
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this
            ->afterMaking(function (User $user): void {
                $raw = $user->getAttributes();
                $membership = array_intersect_key($raw, array_flip(self::MEMBERSHIP_ATTRIBUTES));

                foreach (self::MEMBERSHIP_ATTRIBUTES as $attribute) {
                    unset($user[$attribute]);
                }

                $role = $membership['role'] ?? null;
                $role = $role instanceof Role ? $role : Role::tryFrom((string) $role);

                if ($role === Role::PlatformAdmin) {
                    $user->is_platform_admin = true;

                    return;
                }

                if (($membership['facility_id'] ?? null) !== null) {
                    self::$pendingMemberships[spl_object_id($user)] = [...$membership, 'role' => $role ?? Role::Administrator];
                }
            })
            ->afterCreating(function (User $user): void {
                $membership = self::$pendingMemberships[spl_object_id($user)] ?? null;
                unset(self::$pendingMemberships[spl_object_id($user)]);

                if ($membership !== null) {
                    $user->memberships()->create([...$membership, 'is_active' => true, 'accepted_at' => now()]);
                }
            });
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function platformAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => Role::PlatformAdmin,
            'facility_id' => null,
        ]);
    }

    /**
     * A person with no facility yet, e.g. one about to be given a membership by hand.
     */
    public function withoutMembership(): static
    {
        return $this->state(fn (array $attributes) => [
            'facility_id' => null,
            'role' => null,
        ]);
    }
}
