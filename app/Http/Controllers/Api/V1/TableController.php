<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\TableStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Table\UpdateTableStatusRequest;
use App\Http\Resources\TableResource;
use App\Models\Table;
use Illuminate\Http\JsonResponse;

class TableController extends Controller
{
    /**
     * Get all tables grouped by area with live order summaries.
     */
    public function index(): JsonResponse
    {
        $tables = Table::with(['activeOrder.orderItems.product', 'activeOrder.user'])
            ->orderBy('number')
            ->get();

        $grouped = $tables->groupBy('area')->map(function ($items) {
            return TableResource::collection($items);
        });

        return response()->json([
            'all' => TableResource::collection($tables),
            'grouped' => $grouped,
        ]);
    }

    /**
     * Manual table status update.
     */
    public function updateStatus(UpdateTableStatusRequest $request, int $id): JsonResponse
    {
        $table = Table::findOrFail($id);
        $table->update(['status' => $request->status]);

        if ($request->status === 'available') {
            $table->orders()->where('payment_status', 'pending')->update([
                'payment_status' => 'paid',
            ]);
        }

        // Broadcast table status change
        event(new TableStatusUpdated($table));

        return response()->json([
            'message' => "Table {$table->number} status updated to {$table->status}.",
            'table' => new TableResource($table->fresh(['activeOrder.orderItems.product', 'activeOrder.user'])),
        ]);
    }
}
