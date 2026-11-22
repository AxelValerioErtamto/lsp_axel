<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin',
            'phone' => '081200000000',
            'email' => 'admin@garasi.test',
            'password' => 'password',
            'role' => Role::Admin,
            'status' => UserStatus::Approved,
        ]);
    }
}
