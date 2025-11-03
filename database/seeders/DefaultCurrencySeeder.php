<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Currency;

class DefaultCurrencySeeder extends Seeder
{
    /**
     * Run the database seeder.
     */
    public function run(): void
    {
        // Add default PKR currency for all existing users who don't have a base currency
        $users = User::all();
        
        foreach ($users as $user) {
            $hasBaseCurrency = Currency::where('user_id', $user->id)
                ->where('is_base', true)
                ->exists();
            
            if (!$hasBaseCurrency) {
                Currency::create([
                    'user_id' => $user->id,
                    'code' => 'PKR',
                    'name' => 'Pakistani Rupee',
                    'symbol' => 'Rs.',
                    'country' => 'Pakistan',
                    'conversion_rate' => 1,
                    'is_base' => true,
                    'is_active' => true,
                ]);
                
                $this->command->info("Added default PKR currency for user: {$user->name}");
            }
        }
    }
}
