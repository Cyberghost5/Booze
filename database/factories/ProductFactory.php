<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->words(3, true);
        $cost = fake()->randomFloat(2, 500, 5000);
        return [
            'category_id' => Category::factory(),
            'vendor_id' => null,
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(),
            'image_url' => 'https://images.unsplash.com/photo-1527281400683-1aae777175f8?auto=format&fit=crop&w=600&q=80',
            'unit' => fake()->randomElement(['bottle', 'can', 'crate', 'pack']),
            'cost_price' => $cost,
            'selling_price' => $cost + fake()->randomFloat(2, 200, 1500),
            'stock_level' => fake()->numberBetween(10, 100),
            'is_global' => true,
            'is_active' => true,
        ];
    }

    public function vendor(User $vendor = null): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => $vendor?->id ?? User::factory()->vendor(),
            'is_global' => false,
        ]);
    }

    public function global(): static
    {
        return $this->state(fn (array $attributes) => [
            'vendor_id' => null,
            'is_global' => true,
        ]);
    }
}
