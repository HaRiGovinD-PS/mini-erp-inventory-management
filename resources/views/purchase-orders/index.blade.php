@extends('layouts.app')

@section('title', 'Purchase Orders | Mini ERP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Purchase Orders</h2>
            <p class="text-muted mb-0">Manage your purchase-order lifecycle.</p>
        </div>

        <a href="{{ route('purchase-orders.create') }}" class="btn btn-primary">
            Create Purchase Order
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>PO Number</th>
                        <th>Supplier</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Created At</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($purchaseOrders as $order)
                        <tr>
                            <td>{{ $order->po_number }}</td>
                            <td>{{ $order->supplier->name }}</td>
                            <td>
                                @php
                                    $statusClass = match ($order->status) {
                                        'DRAFT' => 'secondary',
                                        'APPROVED' => 'primary',
                                        'RECEIVED' => 'success',
                                        'CANCELLED' => 'danger',
                                        default => 'secondary',
                                    };
                                @endphp

                                <span class="badge text-bg-{{ $statusClass }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                            <td>₹{{ number_format((float) $order->total_amount, 2) }}</td>
                            <td>{{ $order->created_at->format('d M Y, h:i A') }}</td>
                            <td>
                                <a
                                    href="{{ route('purchase-orders.show', $order) }}"
                                    class="btn btn-sm btn-outline-primary"
                                >
                                    View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                No purchase orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">
            {{ $purchaseOrders->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection