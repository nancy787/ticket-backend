<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\TicketCategoryType;

class TicketCatgorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['mens', 'womens', 'mixed'];

        foreach ($categories as $category) {
            TicketCategoryType::create(['name' => $category]);
        }
    }
}
