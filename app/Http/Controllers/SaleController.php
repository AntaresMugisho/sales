<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $status = $request->input('status');
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = Sale::with(['user', 'products'])
            ->where('user_id', $request->user()->id);

        // Filters
        if ($status) {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        $sales = $query->orderBy('created_at', 'desc')->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $sales,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $total = 0;
            $productData = [];

            // Validate stock and calculate total
            foreach ($request->products as $item) {
                $product = Product::findOrFail($item['product_id']);

                // Check stock availability
                if ($product->stock < $item['quantity']) {
                    return response()->json([
                        'success' => false,
                        'message' => "Insufficient stock for product: {$product->name}. Available: {$product->stock}",
                    ], 422);
                }

                $unitPrice = $product->price;
                $subtotal = $unitPrice * $item['quantity'];
                $total += $subtotal;

                $productData[$product->id] = [
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ];

                // Deduct stock
                $product->decrement('stock', $item['quantity']);
            }

            // Create sale
            $sale = Sale::create([
                'user_id' => $request->user()->id,
                'total' => $total,
                'status' => $request->input('status', 'pending'),
            ]);

            // Attach products with pivot data
            $sale->products()->attach($productData);

            // Load relationships for response
            $sale->load('products');

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale created successfully',
                'data' => $sale,
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to create sale: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale): JsonResponse
    {
        // Ensure user can only view their own sales
        if ($sale->user_id !== request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $sale->load(['user', 'products']);

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSaleRequest $request, Sale $sale): JsonResponse
    {
        // Ensure user can only update their own sales
        if ($sale->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        $sale->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Sale updated successfully',
            'data' => $sale->fresh(['user', 'products']),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale): JsonResponse
    {
        // Ensure user can only delete their own sales
        if ($sale->user_id !== request()->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access',
            ], 403);
        }

        try {
            DB::beginTransaction();

            // Restore stock if sale is being deleted
            foreach ($sale->products as $product) {
                $product->increment('stock', $product->pivot->quantity);
            }

            $sale->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale deleted successfully',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete sale: ' . $e->getMessage(),
            ], 500);
        }
    }
}

