<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\SyncSalesRequest;

class SyncController extends Controller
{

    public function syncSales(SyncSalesRequest $request): JsonResponse
    {
        $results = [
            'success' => [],
            'failed' => [],
            'skipped' => [],
        ];

        foreach ($request->sales as $saleData) {
            try {
                DB::beginTransaction();

                $saleId = $saleData['id'];
                $existingSale = Sale::withTrashed()->find($saleId);

                if ($existingSale) {
                    if ($existingSale->user_id !== $request->user()->id) {
                        $results['failed'][] = [
                            'id' => $saleId,
                            'reason' => 'Sale belongs to another user',
                        ];
                        DB::rollBack();
                        continue;
                    }

                    // Timestamp check for conflict resolution
                    $clientTimestamp = isset($saleData['created_at_client']) 
                        ? strtotime($saleData['created_at_client']) 
                        : null;
                    $serverTimestamp = strtotime($existingSale->created_at);

                    // If timestamps match or client timestamp is older, skip
                    if (!$clientTimestamp || $clientTimestamp <= $serverTimestamp) {
                        $results['skipped'][] = [
                            'id' => $saleId,
                            'reason' => 'Already synced or server version is newer',
                        ];
                        DB::rollBack();
                        continue;
                    }

                    // Client version is newer - update (LWW)
                    $this->updateExistingSale($existingSale, $saleData, $results);
                } else {
                    // New sale - create it
                    $this->createNewSale($saleId, $saleData, $request->user()->id, $results);
                }

                DB::commit();

            } catch (\Exception $e) {
                DB::rollBack();
                $results['failed'][] = [
                    'id' => $saleData['id'] ?? 'unknown',
                    'reason' => $e->getMessage(),
                ];
            }
        }

        $totalProcessed = count($results['success']) + count($results['failed']) + count($results['skipped']);

        return response()->json([
            'success' => true,
            'message' => "Processed {$totalProcessed} sales",
            'results' => $results,
            'summary' => [
                'total' => $totalProcessed,
                'synced' => count($results['success']),
                'failed' => count($results['failed']),
                'skipped' => count($results['skipped']),
            ],
        ]);
    }

    private function createNewSale(string $saleId, array $saleData, int $userId, array &$results): void
    {
        $total = 0;
        $productData = [];

        // Validate stock and calculate total
        foreach ($saleData['products'] as $item) {
            $product = Product::find($item['product_id']);

            if (!$product) {
                throw new \Exception("Product {$item['product_id']} not found");
            }

            // Check stock availability
            if ($product->stock < $item['quantity']) {
                throw new \Exception("Insufficient stock for product: {$product->name}. Available: {$product->stock}, Requested: {$item['quantity']}");
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

        // Create sale with provided UUID
        $sale = Sale::create([
            'id' => $saleId,
            'user_id' => $userId,
            'total' => $total,
            'status' => $saleData['status'] ?? 'pending',
            'created_at' => $saleData['created_at_client'] ?? now(),
        ]);

        // Attach products with pivot data
        $sale->products()->attach($productData);

        $results['success'][] = [
            'id' => $saleId,
            'action' => 'created',
            'total' => $total,
        ];
    }


    private function updateExistingSale(Sale $existingSale, array $saleData, array &$results): void
    {
        // Restore stock from old sale
        foreach ($existingSale->products as $product) {
            $product->increment('stock', $product->pivot->quantity);
        }

        // Detach old products
        $existingSale->products()->detach();

        $total = 0;
        $productData = [];

        // Validate stock and calculate new total
        foreach ($saleData['products'] as $item) {
            $product = Product::find($item['product_id']);

            if (!$product) {
                throw new \Exception("Product {$item['product_id']} not found");
            }

            // Check stock availability
            if ($product->stock < $item['quantity']) {
                throw new \Exception("Insufficient stock for product: {$product->name}. Available: {$product->stock}, Requested: {$item['quantity']}");
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

        // Update sale
        $existingSale->update([
            'total' => $total,
            'status' => $saleData['status'] ?? $existingSale->status,
        ]);

        // Attach new products
        $existingSale->products()->attach($productData);

        $results['success'][] = [
            'id' => $existingSale->id,
            'action' => 'updated',
            'total' => $total,
        ];
    }
}
