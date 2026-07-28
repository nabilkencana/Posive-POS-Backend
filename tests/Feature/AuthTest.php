<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('cashier can authenticate and obtain bearer token', function () {
    $user = User::factory()->create([
        'email' => 'testcashier@posive.id',
        'password' => bcrypt('password123'),
        'role' => 'cashier',
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'testcashier@posive.id',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonStructure([
            'message',
            'token_type',
            'access_token',
            'user' => ['id', 'name', 'email', 'role'],
        ]);
});

test('authenticated user can fetch me profile', function () {
    $user = User::factory()->create(['role' => 'cashier']);
    Sanctum::actingAs($user);

    $response = $this->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJson([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'role' => 'cashier',
            ],
        ]);
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test-token')->plainTextToken;

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson('/api/v1/auth/logout');

    $response->assertStatus(200)
        ->assertJson(['message' => 'Successfully logged out']);
});
