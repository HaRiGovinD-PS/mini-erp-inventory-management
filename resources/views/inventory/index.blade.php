@extends('layouts.app')

@section('title', 'Inventory | Mini ERP')

@section('content')
    <div class="mb-4">
        <h2>Inventory</h2>
        <p class="text-muted mb-0">Monitor stock levels and product availability.</p>
    </div>

    @php
        $lowStockCount = $products->getCollection()
            ->filter(fn ($product) =>
                $product->is_active &&
                $product->stock_quantity <= $product->low_stock_threshold
            )
            ->count();
    @endphp

    <div class="alert alert-info">
        Showing {{ $products->count() }} products on this page.
        {{ $lowStockCount }} active products are at or below their low-stock threshold.
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover table-striped mb-0">
                <thead>
                    <tr>
                        <th>SKU</th>
                        <th>Product</th>
                        <th>Unit Price</th>
                        <th>Available Stock</th>
                        <th>Low-Stock Threshold</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($products as $product)
                        @php
                            $isLowStock = $product->is_active &&
                                $product->stock_quantity <= $product->low_stock_threshold;
                        @endphp

                        <tr class="{{ $isLowStock ? 'table-danger' : '' }}">
                            <td>{{ $product->sku }}</td>
                            <td>{{ $product->name }}</td>
                            <td>₹{{ number_format((float) $product->unit_price, 2) }}</td>
                            <td>{{ $product->stock_quantity }}</td>
                            <td>{{ $product->low_stock_threshold }}</td>
                            <td>
                                @if (!$product->is_active)
                                    <span class="badge text-bg-secondary">Inactive</span>
                                @elseif ($isLowStock)
                                    <span class="badge text-bg-danger">Low Stock</span>
                                @else
                                    <span class="badge text-bg-success">In Stock</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                No products found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer bg-white">
            {{ $products->links('pagination::bootstrap-5') }}
        </div>
    </div>
@endsection