<?php

use App\Events\TableStatusUpdated;
use App\Models\Table;
use App\Models\User;
use Illuminate\Support\Facades\Event;
use Laravel\Sanctum\Sanctum;

test('tables endpoint returns grouped tables by area', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Table::create(['number' => '101', 'area' => 'Indoor', 'status' => 'available', 'seats' => 4]);
    Table::create(['number' => '201', 'area' => 'Terrace', 'status' => 'available', 'seats' => 4]);

    $response = $this->getJson('/api/v1/tables');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'all',
            'grouped' => ['Indoor', 'Terrace'],
        ]);
});

test('cashier can update table status and broadcast event', function () {
    Event::fake([TableStatusUpdated::class]);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $table = Table::create(['number' => '102', 'area' => 'Indoor', 'status' => 'available', 'seats' => 4]);

    $response = $this->patchJson("/api/v1/tables/{$table->id}/status", [
        'status' => 'reserved',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('table.status', 'reserved');

    Event::assertDispatched(TableStatusUpdated::class);
});
