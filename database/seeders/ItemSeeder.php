<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $menu = [
            'Food' => [
                'Pork Adobo' => 80,
                'Adobong Atay Balunan' => 80,
                'Bicol Express' => 80,
                'Sinigang' => 80,
                'Chicken Adobo' => 80,
                'Pinakbet' => 55,
                'Paksiw' => 75,
                'Laing' => 55,
                'Torta Giniling' => 80,
                'Menudo' => 80,
                'Fried Tilapia' => 50,
                'Pork Shanghai' => 10,
                'Lumpiang Togue' => 15,
                'Rice' => 15,
            ],
            'Beverages' => [
                'Coke Mismo' => 25,
                'Sprite Mismo' => 25,
                'Royal Mismo' => 25,
                'Coke 1.5L' => 80,
                'Sprite 1.5L' => 80,
                'Royal 1.5L' => 80,
            ],
            'Add-ons' => [
                'Sweet Chili Dip' => 5,
                'Sweet and Sour Dip' => 5,
                'Spicy Vinegar Dip' => 5,
                'Toyomansi Dip' => 5,
            ],
        ];

        foreach ($menu as $categoryName => $items) {
            $categoryId = Category::where('name', $categoryName)->firstOrFail()->id;

            foreach ($items as $name => $price) {
                Item::updateOrCreate(
                    ['category_id' => $categoryId, 'name' => $name],
                    ['default_price' => $price],
                );
            }
        }
    }
}
