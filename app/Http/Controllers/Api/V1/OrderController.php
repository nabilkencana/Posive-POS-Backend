<?php

namespace App\Http\Controllers\Api\V1;

use App\Events\OrderCreated;
use App\Events\TableStatusUpdated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Order\CreateOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Table;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OrderController extends Controller
{
    /**
     * Display a paginated listing of orders with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Order::with(['orderItems.product', 'table', 'user', 'shift'])
            ->latest();

        // Filter by Cashier (user_id)
        if ($request->has('user_id') && $request->user_id) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by Payment Status
        if ($request->has('payment_status') && $request->payment_status) {
            $query->where('payment_status', $request->payment_status);
        }

        // Filter by Order Type
        if ($request->has('order_type') && $request->order_type) {
            $query->where('order_type', $request->order_type);
        }

        // Filter by Specific Date (YYYY-MM-DD)
        if ($request->has('date') && $request->date) {
            $query->whereDate('created_at', $request->date);
        }

        // Filter by Date Range
        if ($request->has('start_date') && $request->start_date) {
            $query->whereDate('created_at', '>=', $request->start_date);
        }
        if ($request->has('end_date') && $request->end_date) {
            $query->whereDate('created_at', '<=', $request->end_date);
        }

        $perPage = (int) $request->get('per_page', 15);
        $orders = $query->paginate($perPage);

        return response()->json([
            'data' => OrderResource::collection($orders),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    /**
     * Process a complete order checkout atomically.
     */
    public function store(CreateOrderRequest $request): JsonResponse
    {
        $user = $request->user();

        // Fetch active shift for cashier
        $activeShift = Shift::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        try {
            $order = DB::transaction(function () use ($request, $user, $activeShift) {
                $subtotal = 0;
                $itemsToCreate = [];

                // 1. Check stock sufficiency and calculate line subtotals with pessimistic locking
                foreach ($request->items as $itemData) {
                    $product = Product::where('id', $itemData['product_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $product || ! $product->is_active) {
                        throw new Exception("Product ID {$itemData['product_id']} is not available for purchase.");
                    }

                    if ($product->stock < $itemData['quantity']) {
                        throw new Exception("Insufficient stock for product '{$product->name}'. Required: {$itemData['quantity']}, Available: {$product->stock}.");
                    }

                    $unitPrice = (float) $product->price;
                    $lineSubtotal = $unitPrice * $itemData['quantity'];
                    $subtotal += $lineSubtotal;

                    $itemsToCreate[] = [
                        'product' => $product,
                        'quantity' => $itemData['quantity'],
                        'unit_price' => $unitPrice,
                        'subtotal' => $lineSubtotal,
                        'notes' => $itemData['notes'] ?? null,
                    ];
                }

                // 2. Tax & Service Charge Calculations
                $taxAmount = round($subtotal * 0.10, 2);       // 10% Tax
                $serviceCharge = round($subtotal * 0.05, 2);   // 5% Service Fee
                $totalAmount = $subtotal + $taxAmount + $serviceCharge;

                // 3. Payment Cash Validation
                $cashReceived = null;
                $changeGiven = null;

                if ($request->payment_method === 'cash') {
                    $cashReceived = (float) ($request->cash_received ?? $totalAmount);
                    if ($cashReceived < $totalAmount) {
                        throw new Exception('Cash received is less than the total amount required.');
                    }
                    $changeGiven = round($cashReceived - $totalAmount, 2);
                }

                // 4. Generate Unique Invoice Number Format: INV/YYYYMMDD/XXXX
                $datePrefix = date('Ymd');
                $randomSuffix = strtoupper(Str::random(5));
                $invoiceNumber = "INV/{$datePrefix}/{$randomSuffix}";

                // 5. Create Order Record
                $order = Order::create([
                    'invoice_number' => $invoiceNumber,
                    'table_id' => $request->order_type === 'dine_in' ? $request->table_id : null,
                    'user_id' => $user->id,
                    'shift_id' => $activeShift?->id,
                    'customer_name' => $request->customer_name,
                    'order_type' => $request->order_type,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'service_charge' => $serviceCharge,
                    'total_amount' => $totalAmount,
                    'payment_method' => $request->payment_method,
                    'payment_status' => 'paid',
                    'cash_received' => $cashReceived,
                    'change_given' => $changeGiven,
                ]);

                // 6. Deduct stock atomically & create order items
                foreach ($itemsToCreate as $item) {
                    $item['product']->decrement('stock', $item['quantity']);

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product']->id,
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'subtotal' => $item['subtotal'],
                        'notes' => $item['notes'],
                    ]);
                }

                // 7. Update Table status if dine_in
                if ($request->order_type === 'dine_in' && $request->table_id) {
                    $table = Table::find($request->table_id);
                    if ($table) {
                        $table->update(['status' => 'occupied']);
                        event(new TableStatusUpdated($table));
                    }
                }

                return $order;
            });

            // Dispatch OrderCreated Event
            event(new OrderCreated($order));

            return response()->json([
                'message' => 'Order created and processed successfully.',
                'order' => new OrderResource($order->load(['orderItems.product', 'table', 'user'])),
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display detailed order invoice.
     */
    public function show(int $id): JsonResponse
    {
        $order = Order::with(['orderItems.product', 'table', 'user', 'shift'])
            ->findOrFail($id);

        return response()->json([
            'order' => new OrderResource($order),
        ]);
    }

    /**
     * Void an order and restore deducted product stock inside a DB transaction.
     */
    public function refund(int $id): JsonResponse
    {
        try {
            $order = DB::transaction(function () use ($id) {
                $order = Order::with('orderItems')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($order->payment_status === 'refunded') {
                    throw new Exception('Order has already been refunded.');
                }

                // Restore deducted stock for each item
                foreach ($order->orderItems as $item) {
                    $product = Product::where('id', $item->product_id)
                        ->lockForUpdate()
                        ->first();

                    if ($product) {
                        $product->increment('stock', $item->quantity);
                    }
                }

                // Update order status
                $order->update(['payment_status' => 'refunded']);

                // Reset table status if no active orders remain
                if ($order->table_id) {
                    $table = Table::find($order->table_id);
                    if ($table) {
                        $hasOtherPaidOrders = Order::where('table_id', $table->id)
                            ->where('payment_status', 'paid')
                            ->where('id', '!=', $order->id)
                            ->exists();

                        if (! $hasOtherPaidOrders) {
                            $table->update(['status' => 'available']);
                            event(new TableStatusUpdated($table));
                        }
                    }
                }

                return $order;
            });

            return response()->json([
                'message' => 'Order refunded and inventory restored successfully.',
                'order' => new OrderResource($order->load(['orderItems.product', 'table', 'user'])),
            ]);

        } catch (Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
