<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\UpdateStockRequest;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * List active products with category filtering and search query.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with('category')
            ->where('is_active', true);

        // Filter by category_id
        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        // Filter by category slug
        if ($request->has('category_slug') && $request->category_slug) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category_slug);
            });
        }

        // Search by product name or SKU
        $searchTerm = $request->get('q') ?? $request->get('search');
        if ($searchTerm) {
            $operator = \Illuminate\Support\Facades\DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($searchTerm, $operator) {
                $q->where('name', $operator, "%{$searchTerm}%")
                    ->orWhere('sku', $operator, "%{$searchTerm}%");
            });
        }

        $products = $query->orderBy('name')->get();

        return response()->json([
            'data' => ProductResource::collection($products),
        ]);
    }

    /**
     * List all categories.
     */
    public function categories(): JsonResponse
    {
        $categories = Category::withCount('products')->get();

        return response()->json([
            'data' => CategoryResource::collection($categories),
        ]);
    }

    /**
     * Create a new product item.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'category_name' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'nullable|string|max:255|unique:products,sku',
            'image_url' => 'nullable|string',
        ]);

        $category = Category::where('name', $validated['category_name'] ?? 'Makanan Utama')->first() 
            ?? Category::first();

        $sku = $validated['sku'] ?? ('FNB-' . rand(100, 999));

        $product = Product::create([
            'category_id' => $category ? $category->id : 1,
            'name' => $validated['name'],
            'sku' => $sku,
            'price' => $validated['price'],
            'cost_price' => round($validated['price'] * 0.5, 2),
            'stock' => $validated['stock'],
            'is_active' => true,
            'image_url' => $validated['image_url'] ?? null,
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => new ProductResource($product->load('category')),
        ], 201);
    }

    /**
     * Quick adjustment of product stock quantity (+ / -).
     */
    public function updateStock(UpdateStockRequest $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        if ($request->has('adjustment')) {
            $newStock = max(0, $product->stock + (int) $request->adjustment);
            $product->update(['stock' => $newStock]);
        } elseif ($request->has('stock')) {
            $product->update(['stock' => (int) $request->stock]);
        }

        return response()->json([
            'message' => 'Product stock updated successfully.',
            'product' => new ProductResource($product->fresh('category')),
        ]);
    }
}
