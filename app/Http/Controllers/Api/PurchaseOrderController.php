<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PurchaseOrder;
use App\Services\PurchaseOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PurchaseOrderController extends Controller
{
    public function store(Request $request, PurchaseOrderService $purchaseOrderService): JsonResponse 
    {
        $validated = $request->validate([
            'supplier_id' => [
                'required',
                'integer',
                'exists:suppliers,id',
            ],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => [
                'required',
                'integer',
                'distinct',
                'exists:products,id',
            ],
            'items.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:1000000',
            ],
        ]);

        $order = $purchaseOrderService->createDraft(
            $validated,
            $request->user()->id
        );

        return response()->json([
            'message' => 'Purchase order created successfully.',
            'data' => $order,
        ], 201);
    }

    public function updateStatus(Request $request, int $id, PurchaseOrderService $purchaseOrderService): JsonResponse 
    {
        $validated = $request->validate([
            'status' => [
                'required',
                'string',
                Rule::in([
                    PurchaseOrder::STATUS_APPROVED,
                    PurchaseOrder::STATUS_RECEIVED,
                    PurchaseOrder::STATUS_CANCELLED,
                ]),
            ],
        ]);

        $order = $purchaseOrderService->transitionStatus(
            $id,
            $validated['status']
        );

        return response()->json([
            'message' => 'Purchase order status updated successfully.',
            'data' => $order,
        ]);
    }
}
