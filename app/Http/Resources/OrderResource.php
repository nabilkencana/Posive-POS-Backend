<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'invoice_number' => $this->invoice_number,
            'table_id' => $this->table_id,
            'table_number' => $this->whenLoaded('table', fn () => $this->table->number),
            'user_id' => $this->user_id,
            'cashier_name' => $this->whenLoaded('user', fn () => $this->user->name),
            'shift_id' => $this->shift_id,
            'customer_name' => $this->customer_name,
            'order_type' => $this->order_type,
            'subtotal' => (float) $this->subtotal,
            'tax_amount' => (float) $this->tax_amount,
            'service_charge' => (float) $this->service_charge,
            'total_amount' => (float) $this->total_amount,
            'payment_method' => $this->payment_method,
            'payment_status' => $this->payment_status,
            'cash_received' => $this->cash_received !== null ? (float) $this->cash_received : null,
            'change_given' => $this->change_given !== null ? (float) $this->change_given : null,
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
            'table' => new TableResource($this->whenLoaded('table')),
            'cashier' => new UserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
