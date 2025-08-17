<?php

namespace Database\Seeders;

use App\Models\Criterion;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $criteria = [
            ['id' => 1, 'name' => 'Kemampuan Komunikasi'],
            ['id' => 2, 'name' => 'Kontribusi Organisasi'],
            ['id' => 3, 'name' => 'Pengalaman Organisasi'],
        ];

        foreach ($criteria as $criterion) {
            Criterion::updateOrCreate([
                'name' => $criterion['name'],
            ]);
        }

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
