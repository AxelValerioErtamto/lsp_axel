<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nik' => fake()->unique()->numerify('################'),
            'name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'email' => fake()->unique()->safeEmail(),
            'password' => 'password',
            'role' => Role::Tenant,
            'status' => UserStatus::Approved,
        ];
    }
}