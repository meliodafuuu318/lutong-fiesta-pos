<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $category = 1;
        for ($i = 1; $i <= 20; $i++) {
            if (Item::where('category_id', $category)->count() >= 8) {
                $category++;
            }
            $price = rand(60, 100);
            if ($category == 2) {
                $price = rand(25, 30);
            } elseif ($category == 3) {
                $price = rand(10, 20);
            }
            Item::create([
                'name' => "Item $i",
                'default_price' => $price,
                'category_id' => $category,
            ]);
        }
    }
}
