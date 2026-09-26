<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 2000, 15000);
        $deliveryFee = 500.00;
        return [
            'user_id' => User::factory()->consumer(),
            'vendor_id' => User::factory()->vendor(),
            'order_number' => 'BZ-' . strtoupper(\Illuminate\Support\Str::random(8)),
            'status' => 'pending',
            'subtotal' => $subtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $subtotal + $deliveryFee,
            'customer_name' => fake()->name(),
            'customer_phone' => '080' . fake()->numerify('########'),
            'delivery_address' => 'Lodge ' . fake()->numberBetween(1, 40) . ', Gwallameji, Bauchi',
            'latitude' => 10.2847000 + (fake()->numberBetween(-100, 100) / 10000),
            'longitude' => 9.7915000 + (fake()->numberBetween(-100, 100) / 10000),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
