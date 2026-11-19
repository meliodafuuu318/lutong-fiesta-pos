<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = ['Meals', 'Snacks', 'Beverages', 'Frozen', 'Add-ons', 'Misc'];
        $i = 0;

        foreach ($categories as $category) {
            Category::create([
                'name' => $category,
                'order' => $i++
            ]);
        }
    }
}
