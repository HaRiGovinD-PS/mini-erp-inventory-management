@extends('layouts.app')

@section('title', $order->po_number . ' | Mini ERP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>{{ $order->po_number }}</h2>
            <p class="text-muted mb-0">Purchase-order details</p>
        </div>

        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">
            Back to Purchase Orders
        </a>
    </div>

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <strong>Supplier</strong>
                    <div>{{ $order->supplier->name }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Status</strong>
                    <div>
                        <span class="badge text-bg-secondary">{{ $order->status }}</span>
                    </div>
                </div>

                <div class="col-md-4">
                    <strong>Created At</strong>
                    <div>{{ $order->created_at->format('d M Y, h:i A') }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Approved At</strong>
                    <div>{{ $order->approved_at?->format('d M Y, h:i A') ?? '—' }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Received At</strong>
                    <div>{{ $order->received_at?->format('d M Y, h:i A') ?? '—' }}</div>
                </div>

                <div class="col-md-4">
                    <strong>Cancelled At</strong>
                    <div>{{ $order->cancelled_at?->format('d M Y, h:i A') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Order Items</h5>
        </div>

        <div class="table-responsive">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($order->items as $item)
                        <tr>
                            <td>{{ $item->product->sku }}</td>
                            <td>{{ $item->product->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td>₹{{ number_format((float) $item->unit_price, 2) }}</td>
                            <td>₹{{ number_format((float) $item->subtotal, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr>
                        <th colspan="4" class="text-end">Total</th>
                        <th>₹{{ number_format((float) $order->total_amount, 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    @if (in_array($order->status, ['DRAFT', 'APPROVED'], true))
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Update Status</h5>
            </div>

            <div class="card-body">
                <form
                    method="POST"
                    action="{{ route('purchase-orders.update-status', $order) }}"
                    class="d-flex flex-wrap gap-2"
                >
                    @csrf
                    @method('PATCH')

                    @if ($order->status === 'DRAFT')
                        <button
                            type="submit"
                            name="status"
                            value="APPROVED"
                            class="btn btn-primary"
                        >
                            Approve Order
                        </button>
                    @endif

                    @if ($order->status === 'APPROVED')
                        <button
                            type="submit"
                            name="status"
                            value="RECEIVED"
                            class="btn btn-success"
                        >
                            Mark as Received
                        </button>
                    @endif

                    <button
                        type="submit"
                        name="status"
                        value="CANCELLED"
                        class="btn btn-outline-danger"
                        onclick="return confirm('Are you sure you want to cancel this order?')"
                    >
                        Cancel Order
                    </button>
                </form>
            </div>
        </div>
    @endif
@endsection