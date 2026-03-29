<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Jam Jam Admin',
            'email' => 'jamjam@gmail.com',
            'password' => bcrypt('jamjam!@12L'),
            'role' => 'admin',
        ]);
    }
}
