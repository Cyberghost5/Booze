<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('database seeder seeds essential users, categories and global catalog', function () {
    $this->seed(DatabaseSeeder::class);

    $vendor = User::where('email', 'vendor@booze.test')->first();
    $consumer = User::where('email', 'consumer@booze.test')->first();

    expect($vendor)->not->toBeNull()
        ->and($vendor->isVendor())->toBeTrue()
        ->and($consumer)->not->toBeNull()
        ->and($consumer->isConsumer())->toBeTrue()
        ->and(Category::count())->toBeGreaterThanOrEqual(4)
        ->and(Product::count())->toBeGreaterThanOrEqual(10);
});
