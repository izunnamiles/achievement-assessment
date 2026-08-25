<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $products = [
            [
                'name' => 'Wireless Mouse',
                'price' => 1500,
                'stock' => 50,
            ],
            [
                'name' => 'Mechanical Keyboard',
                'price' => 5000,
                'stock' => 30,
            ],
            [
                'name' => 'USB-C Hub',
                'price' => 4000,
                'stock' => 40,
            ],
            [
                'name' => 'Laptop Stand',
                'price' => 3000,
                'stock' => 25,
            ],
            [
                'name' => 'Noise Cancelling Headphones',
                'price' => 9000,
                'stock' => 15,
            ],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['name' => $product['name']],
                $product,
            );
        }
    }
}
