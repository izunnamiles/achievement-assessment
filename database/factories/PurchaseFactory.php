<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Purchase>
 */
class PurchaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'product_id' => Product::factory(),
            'quantity' => 1,
            'amount' => function (array $attributes) {
                $product = Product::find($attributes['product_id']);

                return $product
                    ? $product->price * $attributes['quantity']
                    : fake()->randomFloat(2, 5, 200);
            },
        ];
    }
}
