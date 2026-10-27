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
                'Chicken Pochero' => 80,
                'Ginataang Tulingan' => 70,
                'Ampalaya' => 55,
                'Sisig' => 80,
                'Pork Binagoongan' => 80,
                'Pork Bistek' => 80,
                'Pork Dinuguan' => 80,
                'Pininyahang Manok' => 80,
                'Labong' => 55,
                'Chicken BBQ' => 80,
                'Pork Igado' => 80,
                'Buttered Mixed Veggies' => 60,
                'Puto (6pcs)' => 65,
                'Roasted Chicken' => 100,
                'Chicken Tinola' => 80,
                'Pork Humba' => 80,
                'Ginataang Langka' => 55,
                'Stir-fry Kangkong' => 55,
                'Adobong Sitaw' => 50,
                'Sarsyadong Tilapia' => 70,
                'Pork Ribs Caldereta' => 80,
                'Afritada' => 80,
                'Munggo' => 55,
                'Chicken Hamonado' => 80,
                'Ginisang Upo' => 50,
                'Pata/Liempo Caldereta' => 80,
                'Escabeche Bangus' => 75,
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
                'Coke Mismo' => 20,
                'Royal Mismo' => 20,
                'Cobra' => 20,
                'Mineral Water' => 20,
            ],
            'Misc' => [
                'Loam Soil (Pickup)' => 90,
                'Loam Soil (Delivery)' => 100,
            ]
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
