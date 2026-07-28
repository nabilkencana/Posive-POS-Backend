<?php

use App\Events\OrderCreated;
use App\Events\TableStatusUpdated;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

test('order checkout calculates tax, service, total, deducts stock, and occupies table', function () {
    Event::fake([OrderCreated::class, TableStatusUpdated::class]);

    $user = User::factory()->create(['role' => 'cashier']);
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Food', 'slug' => 'food']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Nasi Goreng',
        'sku' => 'TEST-001',
        'price' => 20000,
        'cost_price' => 10000,
        'stock' => 10,
        'is_active' => true,
    ]);

    $table = Table::create([
        'number' => '999',
        'area' => 'Indoor',
        'status' => 'available',
        'seats' => 4,
    ]);

    $payload = [
        'table_id' => $table->id,
        'customer_name' => 'Budi',
        'order_type' => 'dine_in',
        'payment_method' => 'cash',
        'cash_received' => 50000,
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 2,
                'notes' => 'Pedas',
            ],
        ],
    ];

    $response = $this->postJson('/api/v1/orders', $payload);

    $response->assertStatus(201)
        ->assertJsonPath('order.subtotal', 40000)
        ->assertJsonPath('order.tax_amount', 4000)
        ->assertJsonPath('order.service_charge', 2000)
        ->assertJsonPath('order.total_amount', 46000)
        ->assertJsonPath('order.change_given', 4000);

    expect($product->fresh()->stock)->toBe(8);
    expect($table->fresh()->status)->toBe('occupied');

    Event::assertDispatched(OrderCreated::class);
    Event::assertDispatched(TableStatusUpdated::class);
});

test('order checkout fails when product stock is insufficient', function () {
    $user = User::factory()->create(['role' => 'cashier']);
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Drinks', 'slug' => 'drinks']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Kopi Susu',
        'sku' => 'TEST-002',
        'price' => 15000,
        'cost_price' => 5000,
        'stock' => 1,
        'is_active' => true,
    ]);

    $response = $this->postJson('/api/v1/orders', [
        'order_type' => 'take_away',
        'payment_method' => 'qris',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 5,
            ],
        ],
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('message', "Insufficient stock for product 'Kopi Susu'. Required: 5, Available: 1.");

    expect($product->fresh()->stock)->toBe(1);
});

test('order refund voids order and restores product stock', function () {
    $user = User::factory()->create(['role' => 'cashier']);
    Sanctum::actingAs($user);

    $category = Category::create(['name' => 'Snacks', 'slug' => 'snacks']);
    $product = Product::create([
        'category_id' => $category->id,
        'name' => 'Kentang Goreng',
        'sku' => 'TEST-003',
        'price' => 15000,
        'cost_price' => 5000,
        'stock' => 5,
        'is_active' => true,
    ]);

    $orderResponse = $this->postJson('/api/v1/orders', [
        'order_type' => 'take_away',
        'payment_method' => 'card',
        'items' => [
            [
                'product_id' => $product->id,
                'quantity' => 3,
            ],
        ],
    ]);

    $orderId = $orderResponse->json('order.id');
    expect($product->fresh()->stock)->toBe(2);

    $refundResponse = $this->postJson("/api/v1/orders/{$orderId}/refund");

    $refundResponse->assertStatus(200)
        ->assertJsonPath('order.payment_status', 'refunded');

    expect($product->fresh()->stock)->toBe(5);
});
