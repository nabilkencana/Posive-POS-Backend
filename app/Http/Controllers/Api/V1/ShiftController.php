<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shift\CloseShiftRequest;
use App\Http\Requests\Shift\OpenShiftRequest;
use App\Http\Resources\ShiftResource;
use App\Models\Order;
use App\Models\Shift;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    /**
     * Open a new cashier shift. Ensure only ONE open shift per cashier.
     */
    public function open(OpenShiftRequest $request): JsonResponse
    {
        $user = $request->user();

        $activeShift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if ($activeShift) {
            return response()->json([
                'message' => 'You already have an active open shift.',
                'shift' => new ShiftResource($activeShift),
            ], 422);
        }

        $shift = Shift::create([
            'user_id' => $user->id,
            'opening_cash' => $request->opening_cash,
            'notes' => $request->notes,
            'status' => 'open',
            'opened_at' => now(),
        ]);

        return response()->json([
            'message' => 'Shift opened successfully.',
            'shift' => new ShiftResource($shift),
        ], 201);
    }

    /**
     * Calculate total cash sales vs expected cash, record discrepancy, and close shift.
     */
    public function close(CloseShiftRequest $request): JsonResponse
    {
        $user = $request->user();

        $shift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (! $shift) {
            return response()->json([
                'message' => 'No active open shift found for this user.',
            ], 404);
        }

        $totalCashSales = Order::where('shift_id', $shift->id)
            ->where('payment_status', 'paid')
            ->where('payment_method', 'cash')
            ->sum('total_amount');

        $expectedCash = (float) $shift->opening_cash + (float) $totalCashSales;
        $closingCash = (float) $request->closing_cash;
        $discrepancy = $closingCash - $expectedCash;

        $shift->update([
            'closing_cash' => $closingCash,
            'expected_cash' => $expectedCash,
            'discrepancy' => $discrepancy,
            'notes' => $request->notes ?? $shift->notes,
            'status' => 'closed',
            'closed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Shift closed successfully.',
            'shift' => new ShiftResource($shift),
        ]);
    }

    /**
     * Retrieve active shift metrics for the authenticated cashier.
     */
    public function current(Request $request): JsonResponse
    {
        $user = $request->user();

        $shift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        if (! $shift) {
            return response()->json([
                'message' => 'No active shift found.',
                'active' => false,
                'shift' => null,
            ]);
        }

        $paidOrdersQuery = Order::where('shift_id', $shift->id)
            ->where('payment_status', 'paid');

        $totalSales = (float) (clone $paidOrdersQuery)->sum('total_amount');
        $cashSales = (float) (clone $paidOrdersQuery)->where('payment_method', 'cash')->sum('total_amount');
        $qrisSales = (float) (clone $paidOrdersQuery)->where('payment_method', 'qris')->sum('total_amount');
        $cardSales = (float) (clone $paidOrdersQuery)->where('payment_method', 'card')->sum('total_amount');
        $ordersCount = (clone $paidOrdersQuery)->count();

        $shift->total_sales = $totalSales;
        $shift->orders_count = $ordersCount;

        return response()->json([
            'active' => true,
            'metrics' => [
                'opening_cash' => (float) $shift->opening_cash,
                'expected_cash' => (float) $shift->opening_cash + $cashSales,
                'total_sales' => $totalSales,
                'cash_sales' => $cashSales,
                'qris_sales' => $qrisSales,
                'card_sales' => $cardSales,
                'orders_count' => $ordersCount,
            ],
            'shift' => new ShiftResource($shift),
        ]);
    }
}
