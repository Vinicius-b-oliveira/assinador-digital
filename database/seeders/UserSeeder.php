<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Dev Admin',
            'email' => 'dev@dev.com',
        ]);

        User::factory()->create([
            'name' => 'Outro Usuário',
            'email' => 'other@dev.com',
        ]);

        User::factory()->count(5)->create();
    }
}
