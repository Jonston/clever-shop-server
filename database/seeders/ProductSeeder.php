<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $electronics = Category::where('slug', 'electronics')->first();
        $books = Category::where('slug', 'books')->first();
        $clothing = Category::where('slug', 'clothing')->first();
        $homeGarden = Category::where('slug', 'home-garden')->first();
        $sports = Category::where('slug', 'sports-outdoors')->first();

        $products = [
            ['name' => 'MacBook Pro', 'description' => 'Powerful laptop', 'price' => 2499, 'discount' => 0, 'category_id' => $electronics->id],
            ['name' => 'iPad Air', 'description' => 'Tablet device', 'price' => 599, 'discount' => 10, 'category_id' => $electronics->id],
            ['name' => 'AirPods Pro', 'description' => 'Wireless earbuds', 'price' => 249, 'discount' => 0, 'category_id' => $electronics->id],

            ['name' => 'Clean Code', 'description' => 'Programming book', 'price' => 45, 'discount' => 15, 'category_id' => $books->id],
            ['name' => 'The Pragmatic Programmer', 'description' => 'Essential read for developers', 'price' => 50, 'discount' => 0, 'category_id' => $books->id],

            ['name' => 'Nike Running Shoes', 'description' => 'Comfortable running shoes', 'price' => 120, 'discount' => 20, 'category_id' => $clothing->id],
            ['name' => 'Adidas T-Shirt', 'description' => 'Cotton t-shirt', 'price' => 35, 'discount' => 0, 'category_id' => $clothing->id],

            ['name' => 'Garden Tools Set', 'description' => 'Complete gardening set', 'price' => 89, 'discount' => 10, 'category_id' => $homeGarden->id],
            ['name' => 'LED Lamp', 'description' => 'Energy efficient lamp', 'price' => 25, 'discount' => 0, 'category_id' => $homeGarden->id],

            ['name' => 'Yoga Mat', 'description' => 'Non-slip yoga mat', 'price' => 40, 'discount' => 5, 'category_id' => $sports->id],
            ['name' => 'Dumbbells Set', 'description' => '20kg adjustable dumbbells', 'price' => 150, 'discount' => 0, 'category_id' => $sports->id],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}
