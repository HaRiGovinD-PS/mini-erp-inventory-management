@extends('layouts.app')

@section('title', 'Dashboard | Mini ERP')

@section('content')
    <div class="mb-4">
        <h2>Dashboard</h2>
        <p class="text-muted mb-0">Inventory and purchase-order overview</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Active Products</h6>
                    <h2>{{ $activeProducts }}</h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Low Stock Products</h6>
                    <h2 class="{{ $lowStockProducts > 0 ? 'text-danger' : 'text-success' }}">
                        {{ $lowStockProducts }}
                    </h2>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm h-100">
                <div class="card-body">
                    <h6 class="text-muted">Received Purchase Order Spend</h6>
                    <h2>₹{{ number_format((float) $totalSpend, 2) }}</h2>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-white">
            <h5 class="mb-0">Latest Purchase Orders</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-striped table-hover mb-0">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Created</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($latestPurchaseOrders as $order)
                        <tr>
                            <td>
                                <a href="{{ route('purchase-orders.show', $order) }}">
                                    {{ $order->po_number }}
                                </a>
                            </td>
                            <td>{{ $order->supplier->name }}</td>
                            <td>
                                <span class="badge text-bg-secondary">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td>₹{{ number_format((float) $order->total_amount, 2) }}</td>
                            <td>{{ $order->created_at->format('d M Y, h:i A') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4">
                                No purchase orders created yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection