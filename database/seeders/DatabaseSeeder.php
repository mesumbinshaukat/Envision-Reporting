<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Run comprehensive currency seeder with multi-currency test data
        $this->call([
            ComprehensiveCurrencySeeder::class,
        ]);
    }
}
