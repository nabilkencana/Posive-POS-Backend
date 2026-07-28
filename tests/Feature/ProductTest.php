<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('products endpoint returns list filtered by search and category', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $cat1 = Category::create(['name' => 'Makanan', 'slug' => 'makanan']);
    $cat2 = Category::create(['name' => 'Minuman', 'slug' => 'minuman']);

    Product::create([
        'category_id' => $cat1->id,
        'name' => 'Nasi Goreng',
        'sku' => 'MK-101',
        'price' => 25000,
        'stock' => 10,
        'is_active' => true,
    ]);

    Product::create([
        'category_id' => $cat2->id,
        'name' => 'Es Teh',
        'sku' => 'MN-101',
        'price' => 5000,
        'stock' => 20,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/products?q=Nasi');

    $response->assertStatus(200)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Nasi Goreng');
});

test('cashier can quickly adjust stock quantity', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $cat = Category::create(['name' => 'General', 'slug' => 'general']);
    $product = Product::create([
        'category_id' => $cat->id,
        'name' => 'Air Mineral',
        'sku' => 'MN-999',
        'price' => 4000,
        'stock' => 10,
        'is_active' => true,
    ]);

    $response = $this->patchJson("/api/v1/products/{$product->id}/stock", [
        'adjustment' => 5,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('product.stock', 15);
});
