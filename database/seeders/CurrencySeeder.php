<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        Currency::firstOrCreate(
            ['code' => 'GEL'],
            [
                'name' => 'Georgian Lari',
                'exchange_rate' => 1.0000,
                'symbol' => '₾',
                'is_active' => true,
            ]
        );

        Currency::firstOrCreate(
            ['code' => 'USD'],
            [
                'name' => 'US Dollar',
                'exchange_rate' => 2.7000,
                'symbol' => '$',
                'is_active' => true,
            ]
        );

        Currency::firstOrCreate(
            ['code' => 'EUR'],
            [
                'name' => 'Euro',
                'exchange_rate' => 2.9500,
                'symbol' => '€',
                'is_active' => true,
            ]
        );
    }
}
