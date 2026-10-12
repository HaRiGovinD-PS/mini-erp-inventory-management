<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function supplierSpend(): JsonResponse
    {
        $report = DB::table('purchase_orders')
            ->join('suppliers', 'purchase_orders.supplier_id', '=', 'suppliers.id')
            ->where('purchase_orders.status', 'RECEIVED')
            ->select(
                'suppliers.id as supplier_id',
                'suppliers.name as supplier_name'
            )
            ->selectRaw('SUM(purchase_orders.total_amount) as total_spend')
            ->selectRaw('COUNT(purchase_orders.id) as received_order_count')
            ->groupBy('suppliers.id', 'suppliers.name')
            ->orderByDesc('total_spend')
            ->paginate(10);

        return response()->json($report);
    }
}