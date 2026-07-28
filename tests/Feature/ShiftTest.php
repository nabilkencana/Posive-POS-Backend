<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('cashier can open shift with float cash', function () {
    $user = User::factory()->create(['role' => 'cashier']);
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/shifts/open', [
        'opening_cash' => 200000,
        'notes' => 'Shift pagi',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('shift.opening_cash', 200000)
        ->assertJsonPath('shift.status', 'open');
});

test('cashier cannot open duplicate active shift', function () {
    $user = User::factory()->create(['role' => 'cashier']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/shifts/open', [
        'opening_cash' => 200000,
    ]);

    $secondAttempt = $this->postJson('/api/v1/shifts/open', [
        'opening_cash' => 300000,
    ]);

    $secondAttempt->assertStatus(422)
        ->assertJsonPath('message', 'You already have an active open shift.');
});
