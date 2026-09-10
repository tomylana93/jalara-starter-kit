<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MasterData\User;
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
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@jalara.dev',
        ]);

        User::factory(200)->create();
    }
}
