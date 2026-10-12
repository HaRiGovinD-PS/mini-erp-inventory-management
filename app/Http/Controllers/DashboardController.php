<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

use App\Models\Product;
use App\Models\PurchaseOrder;

class DashboardController extends Controller
{
    public function index(): View
    {
        $activeProducts = Product::where('is_active', true)->count();

        $lowStockProducts = Product::query()
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();

        $totalSpend = PurchaseOrder::query()
            ->where('status', PurchaseOrder::STATUS_RECEIVED)
            ->sum('total_amount');

        $latestPurchaseOrders = PurchaseOrder::query()
            ->with('supplier')
            ->latest()
            ->take(5)
            ->get();

        return view('dashboard', compact(
            'activeProducts',
            'lowStockProducts',
            'totalSpend',
            'latestPurchaseOrders'
        ));
    }
}
