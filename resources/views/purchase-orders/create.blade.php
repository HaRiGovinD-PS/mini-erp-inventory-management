@extends('layouts.app')

@section('title', 'Create Purchase Order | Mini ERP')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Create Purchase Order</h2>
            <p class="text-muted mb-0">Create a draft order for a supplier.</p>
        </div>

        <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">
            Back to Purchase Orders
        </a>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul class="mb-0 mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('purchase-orders.store') }}" id="purchaseOrderForm">
        @csrf

        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <label for="supplier_id" class="form-label">Supplier</label>

                <select
                    name="supplier_id"
                    id="supplier_id"
                    class="form-select @error('supplier_id') is-invalid @enderror"
                    required
                >
                    <option value="">Select a supplier</option>

                    @foreach ($suppliers as $supplier)
                        <option
                            value="{{ $supplier->id }}"
                            @selected(old('supplier_id') == $supplier->id)
                        >
                            {{ $supplier->name }}
                        </option>
                    @endforeach
                </select>

                @error('supplier_id')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Order Items</h5>

                <button type="button" id="addItemButton" class="btn btn-sm btn-outline-primary">
                    + Add Product
                </button>
            </div>

            <div class="card-body">
                <div id="itemsContainer"></div>

                <div class="text-end border-top pt-3 mt-3">
                    <span class="text-muted me-2">Grand Total</span>
                    <strong class="fs-4" id="grandTotal">₹0.00</strong>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('purchase-orders.index') }}" class="btn btn-outline-secondary">
                Cancel
            </a>

            <button type="submit" class="btn btn-primary">
                Create Draft Order
            </button>
        </div>
    </form>

    <template id="itemRowTemplate">
        <div class="row g-3 align-items-end order-item-row border-bottom pb-3 mb-3">
            <div class="col-md-5">
                <label class="form-label">Product</label>

                <select
                    class="form-select product-select"
                    name="items[__INDEX__][product_id]"
                    required
                >
                    <option value="">Select a product</option>

                    @foreach ($products as $product)
                        <option
                            value="{{ $product->id }}"
                            data-price="{{ $product->unit_price }}"
                        >
                            {{ $product->sku }} — {{ $product->name }}
                            (₹{{ number_format((float) $product->unit_price, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2">
                <label class="form-label">Unit Price</label>
                <input type="text" class="form-control unit-price" value="0.00" readonly>
            </div>

            <div class="col-md-2">
                <label class="form-label">Quantity</label>

                <input
                    type="number"
                    class="form-control quantity-input"
                    name="items[__INDEX__][quantity]"
                    value="1"
                    min="1"
                    max="1000000"
                    step="1"
                    required
                >
            </div>

            <div class="col-md-2">
                <label class="form-label">Subtotal</label>
                <input type="text" class="form-control subtotal" value="0.00" readonly>
            </div>

            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger remove-item w-100">
                    &times;
                </button>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('itemsContainer');
        const template = document.getElementById('itemRowTemplate');
        const addButton = document.getElementById('addItemButton');
        const grandTotalElement = document.getElementById('grandTotal');

        let rowIndex = 0;

        function formatMoney(cents) {
            return (cents / 100).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        function toCents(value) {
            const amount = Number(value);

            if (!Number.isFinite(amount) || amount < 0) {
                return 0;
            }

            return Math.round(amount * 100);
        }

        function updateProductOptions() {
            const selects = [...container.querySelectorAll('.product-select')];
            const selectedValues = selects.map(select => select.value).filter(Boolean);

            selects.forEach(select => {
                [...select.options].forEach(option => {
                    if (!option.value) {
                        return;
                    }

                    option.disabled =
                        selectedValues.includes(option.value) &&
                        select.value !== option.value;
                });
            });
        }

        function updateRow(row) {
            const select = row.querySelector('.product-select');
            const quantityInput = row.querySelector('.quantity-input');
            const priceInput = row.querySelector('.unit-price');
            const subtotalInput = row.querySelector('.subtotal');

            const selectedOption = select.selectedOptions[0];
            const unitPriceCents = toCents(selectedOption?.dataset.price ?? 0);

            const quantity = Number.parseInt(quantityInput.value, 10);
            const validQuantity =
                Number.isInteger(quantity) && quantity >= 1 && quantity <= 1000000;

            priceInput.value = formatMoney(unitPriceCents);
            subtotalInput.value = formatMoney(
                validQuantity ? unitPriceCents * quantity : 0
            );

            return validQuantity ? unitPriceCents * quantity : 0;
        }

        function updateTotals() {
            let totalCents = 0;

            container.querySelectorAll('.order-item-row').forEach(row => {
                totalCents += updateRow(row);
            });

            grandTotalElement.textContent = '₹' + formatMoney(totalCents);
        }

        function addRow() {
            const html = template.innerHTML.replaceAll('__INDEX__', rowIndex++);
            container.insertAdjacentHTML('beforeend', html);

            const rows = container.querySelectorAll('.order-item-row');
            const row = rows[rows.length - 1];

            row.querySelector('.product-select').addEventListener('change', function () {
                updateProductOptions();
                updateTotals();
            });

            row.querySelector('.quantity-input').addEventListener('input', updateTotals);

            row.querySelector('.remove-item').addEventListener('click', function () {
                if (container.querySelectorAll('.order-item-row').length === 1) {
                    row.querySelector('.product-select').value = '';
                    row.querySelector('.quantity-input').value = 1;
                } else {
                    row.remove();
                }

                updateProductOptions();
                updateTotals();
            });

            updateProductOptions();
            updateTotals();
        }

        addButton.addEventListener('click', addRow);

        document.getElementById('purchaseOrderForm').addEventListener('submit', function (event) {
            const rows = [...container.querySelectorAll('.order-item-row')];

            const hasInvalidRow = rows.some(row => {
                const productId = row.querySelector('.product-select').value;
                const quantityInput = row.querySelector('.quantity-input');
                const quantity = Number(quantityInput.value);

                return !productId ||
                    !Number.isInteger(quantity) ||
                    quantity < 1 ||
                    quantity > 1000000;
            });

            const selectedProductIds = rows.map(row =>
                row.querySelector('.product-select').value
            );

            const hasDuplicates =
                new Set(selectedProductIds).size !== selectedProductIds.length;

            if (hasInvalidRow || hasDuplicates) {
                event.preventDefault();
                alert('Select a product for each row and enter a valid quantity. Each product can appear only once.');
            }
        });

        addRow();
    });
</script>
@endpush