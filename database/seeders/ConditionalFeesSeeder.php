<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ConditionalFee;

class ConditionalFeesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $applicationFees  = [
            ['currency_type' => 'USD', 'application_fee_amount' => '3000'],
            ['currency_type' => 'AUD', 'application_fee_amount' => '3200'],
            ['currency_type' => 'CAD', 'application_fee_amount' => '3500'],
            ['currency_type' => 'CHF', 'application_fee_amount' => '2000'],
            ['currency_type' => 'DKK', 'application_fee_amount' => '1500'],
            ['currency_type' => 'EUR', 'application_fee_amount' => '2000'],
            ['currency_type' => 'GBP', 'application_fee_amount' => '2000'],
            ['currency_type' => 'NZD', 'application_fee_amount' => '3700'],
            ['currency_type' => 'PLN', 'application_fee_amount' => '8500'],
            ['currency_type' => 'SEK', 'application_fee_amount' => '2300'],
            ['currency_type' => 'THB', 'application_fee_amount' => '7150'],
        ];

        foreach ($applicationFees as $applicationFee) {
            ConditionalFee::updateOrCreate([
                'currency_type'     => $applicationFee['currency_type'],
                'application_fee_amount' => $applicationFee['application_fee_amount']
            ]);
        }
    }
}
