<?php

namespace App\Services;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PurchaseOrderService
{
    public function createDraft(array $data, ?int $userId = null): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $userId) {
            $supplier = Supplier::query()
                ->whereKey($data['supplier_id'])
                ->where('is_active', true)
                ->first();

            if (!$supplier) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'The selected supplier is invalid or inactive.',
                ]);
            }

            $items = collect($data['items']);

            if ($items->pluck('product_id')->unique()->count() !== $items->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Each product can appear only once in a purchase order.',
                ]);
            }

            $products = Product::query()
                ->whereIn('id', $items->pluck('product_id'))
                ->where('is_active', true)
                ->get()
                ->keyBy('id');

            if ($products->count() !== $items->count()) {
                throw ValidationException::withMessages([
                    'items' => 'One or more selected products are invalid or inactive.',
                ]);
            }

            $order = PurchaseOrder::create([
                'po_number' => 'PO-' . now()->format('YmdHis') . '-' . Str::upper(Str::random(6)),
                'supplier_id' => $supplier->id,
                'created_by' => $userId,
                'status' => PurchaseOrder::STATUS_DRAFT,
                'total_amount' => 0,
            ]);

            $totalCents = 0;

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                $quantity = (int) $item['quantity'];

                $unitPriceCents = $this->toCents($product->unit_price);
                $subtotalCents = $unitPriceCents * $quantity;

                PurchaseOrderItem::create([
                    'purchase_order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => number_format($unitPriceCents / 100, 2, '.', ''),
                    'subtotal' => number_format($subtotalCents / 100, 2, '.', ''),
                ]);

                $totalCents += $subtotalCents;
            }

            $order->update([
                'total_amount' => number_format($totalCents / 100, 2, '.', ''),
            ]);

            return $order->load(['supplier', 'items.product']);
        });
    }

    private function toCents(string|float|int $amount): int
    {
        $amount = number_format((float) $amount, 2, '.', '');
        [$whole, $fraction] = explode('.', $amount);

        return ((int) $whole * 100) + (int) $fraction;
    }

    public function transitionStatus(int $orderId, string $newStatus): PurchaseOrder 
    {
        return DB::transaction(function () use ($orderId, $newStatus) {
            $order = PurchaseOrder::query()
                ->lockForUpdate()
                ->findOrFail($orderId);

            $allowedTransitions = [
                PurchaseOrder::STATUS_DRAFT => [
                    PurchaseOrder::STATUS_APPROVED,
                    PurchaseOrder::STATUS_CANCELLED,
                ],
                PurchaseOrder::STATUS_APPROVED => [
                    PurchaseOrder::STATUS_RECEIVED,
                    PurchaseOrder::STATUS_CANCELLED,
                ],
                PurchaseOrder::STATUS_RECEIVED => [],
                PurchaseOrder::STATUS_CANCELLED => [],
            ];

            if (!in_array($newStatus, $allowedTransitions[$order->status] ?? [], true)) 
            {
                throw ValidationException::withMessages([
                    'status' => "Cannot change an order from {$order->status} to {$newStatus}.",
                ]);
            }

            if ($newStatus === PurchaseOrder::STATUS_RECEIVED) 
            {
                $items = $order->items()
                    ->orderBy('product_id')
                    ->get();

                if ($items->isEmpty()) {
                    throw ValidationException::withMessages([
                        'items' => 'A purchase order without items cannot be received.',
                    ]);
                }

                $products = Product::query()
                    ->whereIn('id', $items->pluck('product_id'))
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                foreach ($items as $item) {
                    $product = $products->get($item->product_id);

                    if (!$product) {
                        throw ValidationException::withMessages([
                            'items' => 'A product in this purchase order no longer exists.',
                        ]);
                    }

                    $product->increment('stock_quantity', $item->quantity);
                }

                $order->received_at = now();
            }

            if ($newStatus === PurchaseOrder::STATUS_APPROVED) {
                $order->approved_at = now();
            }

            if ($newStatus === PurchaseOrder::STATUS_CANCELLED) {
                $order->cancelled_at = now();
            }

            $order->status = $newStatus;
            $order->save();

            return $order->fresh(['supplier', 'items.product']);
        });
    }
}