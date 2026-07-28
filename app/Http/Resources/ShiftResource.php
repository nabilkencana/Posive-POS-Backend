<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShiftResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'opening_cash' => (float) $this->opening_cash,
            'closing_cash' => $this->closing_cash !== null ? (float) $this->closing_cash : null,
            'expected_cash' => $this->expected_cash !== null ? (float) $this->expected_cash : null,
            'discrepancy' => $this->discrepancy !== null ? (float) $this->discrepancy : null,
            'notes' => $this->notes,
            'status' => $this->status,
            'opened_at' => $this->opened_at?->toIso8601String(),
            'closed_at' => $this->closed_at?->toIso8601String(),
            'total_sales' => $this->when(isset($this->total_sales), fn () => (float) $this->total_sales),
            'orders_count' => $this->when(isset($this->orders_count), fn () => (int) $this->orders_count),
        ];
    }
}
