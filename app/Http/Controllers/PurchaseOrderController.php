<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;

class PurchaseOrderController extends Controller
{
    public function index(): View
    {
        $purchaseOrders = PurchaseOrder::query()
            ->with('supplier')
            ->latest()
            ->paginate(15);

        return view('purchase-orders.index', compact('purchaseOrders'));
    }

    public function create(): View
    {
        $suppliers = Supplier::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $products = Product::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('purchase-orders.create', compact(
            'suppliers',
            'products'
        ));
    }

    public function store(Request $request, PurchaseOrderService $purchaseOrderService): RedirectResponse 
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
            $request->user()?->id
        );

        return redirect()
            ->route('purchase-orders.show', $order)
            ->with('success', 'Purchase order created successfully.');
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load(['supplier', 'creator', 'items.product']);

        return view('purchase-orders.show', [
            'order' => $purchaseOrder,
        ]);
    }

    public function updateStatus(Request $request, PurchaseOrder $purchaseOrder, PurchaseOrderService $purchaseOrderService): RedirectResponse 
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

        $purchaseOrderService->transitionStatus(
            $purchaseOrder->id,
            $validated['status']
        );

        return redirect()
            ->route('purchase-orders.show', $purchaseOrder)
            ->with('success', 'Purchase order status updated successfully.');
    }
}
