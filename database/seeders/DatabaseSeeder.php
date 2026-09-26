<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Default Users (Vendor Admin & Consumers)
        $vendor = User::factory()->create([
            'name' => 'Gwallameji Vendor Admin',
            'email' => 'admin@booze.test',
            'password' => bcrypt('password'),
            'role' => 'vendor_admin',
            'phone' => '09031704109',
            'store_name' => 'Gwallameji Central Liquor Store',
        ]);

        $vendorAlt = User::factory()->create([
            'name' => 'Vendor Account (Legacy)',
            'email' => 'vendor@booze.test',
            'password' => bcrypt('password'),
            'role' => 'vendor_admin',
            'phone' => '08039998877',
            'store_name' => 'Bauchi Premium Drinks Depot',
        ]);

        $consumer1 = User::factory()->create([
            'name' => 'John Student',
            'email' => 'consumer@booze.test',
            'password' => bcrypt('password'),
            'role' => 'consumer',
            'phone' => '08069876543',
            'store_name' => null,
        ]);

        $consumer2 = User::factory()->create([
            'name' => 'Amina Bauchi',
            'email' => 'amina@booze.test',
            'password' => bcrypt('password'),
            'role' => 'consumer',
            'phone' => '08051112233',
            'store_name' => null,
        ]);

        // 2. Create Categories
        $beers = Category::create([
            'name' => 'Beers',
            'slug' => 'beers',
            'icon' => 'Beer',
            'description' => 'Cold lagers, stouts, and malt beverages',
        ]);

        $spirits = Category::create([
            'name' => 'Spirits',
            'slug' => 'spirits',
            'icon' => 'Flame',
            'description' => 'Whiskey, vodka, gin, cognac, and bitters',
        ]);

        $wines = Category::create([
            'name' => 'Wines',
            'slug' => 'wines',
            'icon' => 'Wine',
            'description' => 'Fine red, white, sparkling, and sweet wines',
        ]);

        $mixers = Category::create([
            'name' => 'Mixers',
            'slug' => 'mixers',
            'icon' => 'CupSoda',
            'description' => 'Sodas, tonic water, and energy drinks',
        ]);

        // 3. Global Product Catalog Data
        $catalog = [
            // BEERS
            [
                'category_id' => $beers->id,
                'name' => 'Trophy Lager (600ml)',
                'unit' => 'bottle',
                'cost_price' => 650.00,
                'selling_price' => 850.00,
                'stock_level' => 48,
                'image_url' => 'https://images.unsplash.com/photo-1608270586620-248524c67de9?auto=format&fit=crop&w=600&q=80',
                'description' => 'The Honourable Lager. Crisp and refreshing.',
            ],
            [
                'category_id' => $beers->id,
                'name' => 'Star Lager (600ml)',
                'unit' => 'bottle',
                'cost_price' => 650.00,
                'selling_price' => 850.00,
                'stock_level' => 36,
                'image_url' => 'https://images.unsplash.com/photo-1535958636474-b021ee887b13?auto=format&fit=crop&w=600&q=80',
                'description' => 'Shine on with classic Nigerian Star Lager.',
            ],
            [
                'category_id' => $beers->id,
                'name' => 'Guinness Extra Stout (600ml)',
                'unit' => 'bottle',
                'cost_price' => 850.00,
                'selling_price' => 1100.00,
                'stock_level' => 60,
                'image_url' => 'https://images.unsplash.com/photo-1567696911980-2eed69a46042?auto=format&fit=crop&w=600&q=80',
                'description' => 'Rich, dark, roasted malt taste of Guinness Stout.',
            ],
            [
                'category_id' => $beers->id,
                'name' => 'Heineken Premium (600ml)',
                'unit' => 'bottle',
                'cost_price' => 900.00,
                'selling_price' => 1200.00,
                'stock_level' => 24,
                'image_url' => 'https://images.unsplash.com/photo-1618886614638-80e3c103d31a?auto=format&fit=crop&w=600&q=80',
                'description' => 'International premium Dutch lager.',
            ],

            // SPIRITS
            [
                'category_id' => $spirits->id,
                'name' => 'Hennessy Very Special (70cl)',
                'unit' => 'bottle',
                'cost_price' => 42000.00,
                'selling_price' => 48000.00,
                'stock_level' => 8,
                'image_url' => 'https://images.unsplash.com/photo-1527281400683-1aae777175f8?auto=format&fit=crop&w=600&q=80',
                'description' => 'Premium Cognac with toasted notes and fruitiness.',
            ],
            [
                'category_id' => $spirits->id,
                'name' => 'Jameson Triple Distilled Whiskey (70cl)',
                'unit' => 'bottle',
                'cost_price' => 18500.00,
                'selling_price' => 22000.00,
                'stock_level' => 12,
                'image_url' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=600&q=80',
                'description' => 'Smooth Irish whiskey triple distilled for taste.',
            ],
            [
                'category_id' => $spirits->id,
                'name' => 'Orijin Herbal Bitters (75cl)',
                'unit' => 'bottle',
                'cost_price' => 2200.00,
                'selling_price' => 2800.00,
                'stock_level' => 30,
                'image_url' => 'https://images.unsplash.com/photo-1563227812-0ea4c22e6cc8?auto=format&fit=crop&w=600&q=80',
                'description' => 'Blend of African herbs and fruit extracts.',
            ],

            // WINES
            [
                'category_id' => $wines->id,
                'name' => 'Carlo Rossi Sweet Red Wine (75cl)',
                'unit' => 'bottle',
                'cost_price' => 6000.00,
                'selling_price' => 7800.00,
                'stock_level' => 15,
                'image_url' => 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?auto=format&fit=crop&w=600&q=80',
                'description' => 'California sweet red wine with floral aromas.',
            ],
            [
                'category_id' => $wines->id,
                'name' => 'Eva Non-Alcoholic Sparkling Wine (75cl)',
                'unit' => 'bottle',
                'cost_price' => 3000.00,
                'selling_price' => 3800.00,
                'stock_level' => 20,
                'image_url' => 'https://images.unsplash.com/photo-1558001373-7b9fcc48fac0?auto=format&fit=crop&w=600&q=80',
                'description' => 'Fruity sparkling non-alcoholic juice drink.',
            ],

            // MIXERS
            [
                'category_id' => $mixers->id,
                'name' => 'Coca-Cola Classic (50cl)',
                'unit' => 'bottle',
                'cost_price' => 300.00,
                'selling_price' => 400.00,
                'stock_level' => 100,
                'image_url' => 'https://images.unsplash.com/photo-1622483767028-3f66f32aef97?auto=format&fit=crop&w=600&q=80',
                'description' => 'The classic refreshing cola mixer.',
            ],
            [
                'category_id' => $mixers->id,
                'name' => 'Monster Energy Drink (50cl Can)',
                'unit' => 'can',
                'cost_price' => 900.00,
                'selling_price' => 1200.00,
                'stock_level' => 50,
                'image_url' => 'https://images.unsplash.com/photo-1622543925917-763c34d1a86e?auto=format&fit=crop&w=600&q=80',
                'description' => 'Unleash the Beast energy drink.',
            ],
        ];

        $createdProducts = [];

        foreach ($catalog as $item) {
            $createdProducts[$item['name']] = Product::create([
                'category_id' => $item['category_id'],
                'vendor_id' => $vendor->id, // Assigned directly to main store inventory
                'name' => $item['name'],
                'slug' => Str::slug($item['name'] . '-' . $vendor->id),
                'description' => $item['description'],
                'image_url' => $item['image_url'],
                'unit' => $item['unit'],
                'cost_price' => $item['cost_price'],
                'selling_price' => $item['selling_price'],
                'stock_level' => $item['stock_level'],
                'is_global' => false,
                'is_active' => true,
            ]);
        }

        // 4. Seed Initial Sample Order Linked Directly to Inventory
        $trophy = $createdProducts['Trophy Lager (600ml)'];
        $guinness = $createdProducts['Guinness Extra Stout (600ml)'];

        $orderSubtotal = (2 * $trophy->selling_price) + (1 * $guinness->selling_price); // 1700 + 1100 = 2800
        $deliveryFee = 500.00;

        $sampleOrder = \App\Models\Order::create([
            'user_id' => $consumer1->id,
            'vendor_id' => $vendor->id,
            'order_number' => 'BZ-DEMO9988',
            'status' => 'pending',
            'subtotal' => $orderSubtotal,
            'delivery_fee' => $deliveryFee,
            'total' => $orderSubtotal + $deliveryFee,
            'customer_name' => $consumer1->name,
            'customer_phone' => $consumer1->phone,
            'delivery_address' => 'Room 14, Executive Lodge, Gwallameji, Bauchi',
            'latitude' => 10.2847000,
            'longitude' => 9.7915000,
            'notes' => 'Please bring cold bottles!',
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $sampleOrder->id,
            'product_id' => $trophy->id,
            'product_name' => $trophy->name,
            'quantity' => 2,
            'unit_price' => $trophy->selling_price,
            'subtotal' => 2 * $trophy->selling_price,
        ]);

        \App\Models\OrderItem::create([
            'order_id' => $sampleOrder->id,
            'product_id' => $guinness->id,
            'product_name' => $guinness->name,
            'quantity' => 1,
            'unit_price' => $guinness->selling_price,
            'subtotal' => 1 * $guinness->selling_price,
        ]);

        // Decrement stock for the sampled order
        $trophy->decrement('stock_level', 2);
        $guinness->decrement('stock_level', 1);
    }
}
