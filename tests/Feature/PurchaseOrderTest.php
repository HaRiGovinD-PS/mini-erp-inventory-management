<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\PurchaseOrderService;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PurchaseOrderTest extends TestCase
{
    use RefreshDatabase;

    private Supplier $supplier;
    private Product $product1;
    private Product $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->supplier = Supplier::create([
            'name' => 'Test Supplier',
            'email' => 'supplier@example.test',
            'is_active' => true,
        ]);

        $this->product1 = Product::create([
            'sku' => 'TEST001',
            'name' => 'Test Laptop',
            'unit_price' => 1000.00,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        $this->product2 = Product::create([
            'sku' => 'TEST002',
            'name' => 'Test Mouse',
            'unit_price' => 250.00,
            'stock_quantity' => 20,
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);
    }

    public function test_it_creates_a_draft_purchase_order_with_correct_total(): void
    {
        $order = app(PurchaseOrderService::class)->createDraft([
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 2,
                ],
                [
                    'product_id' => $this->product2->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $this->assertSame(PurchaseOrder::STATUS_DRAFT, $order->status);
        $this->assertSame('2750.00', $order->total_amount);
        $this->assertSame(2, $order->items->count());

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $order->id,
            'product_id' => $this->product1->id,
            'quantity' => 2,
            'subtotal' => '2000.00',
        ]);

        $this->assertDatabaseHas('purchase_order_items', [
            'purchase_order_id' => $order->id,
            'product_id' => $this->product2->id,
            'quantity' => 3,
            'subtotal' => '750.00',
        ]);
    }

    public function test_it_rejects_duplicate_products_in_an_order(): void
    {
        try {
            app(PurchaseOrderService::class)->createDraft([
                'supplier_id' => $this->supplier->id,
                'items' => [
                    [
                        'product_id' => $this->product1->id,
                        'quantity' => 1,
                    ],
                    [
                        'product_id' => $this->product1->id,
                        'quantity' => 2,
                    ],
                ],
            ]);

            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertDatabaseCount('purchase_orders', 0);
    }

    public function test_it_rejects_inactive_products(): void
    {
        $this->product1->update(['is_active' => false]);

        $this->expectException(ValidationException::class);

        app(PurchaseOrderService::class)->createDraft([
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 1,
                ],
            ],
        ]);
    }

    public function test_receiving_an_approved_order_increases_stock(): void
    {
        $order = app(PurchaseOrderService::class)->createDraft([
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $service = app(PurchaseOrderService::class);

        $service->transitionStatus(
            $order->id,
            PurchaseOrder::STATUS_APPROVED
        );

        $receivedOrder = $service->transitionStatus(
            $order->id,
            PurchaseOrder::STATUS_RECEIVED
        );

        $this->assertSame(
            PurchaseOrder::STATUS_RECEIVED,
            $receivedOrder->status
        );

        $this->assertSame(
            13,
            $this->product1->fresh()->stock_quantity
        );
    }

    public function test_received_order_cannot_be_received_twice(): void
    {
        $order = app(PurchaseOrderService::class)->createDraft([
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $service = app(PurchaseOrderService::class);

        $service->transitionStatus(
            $order->id,
            PurchaseOrder::STATUS_APPROVED
        );

        $service->transitionStatus(
            $order->id,
            PurchaseOrder::STATUS_RECEIVED
        );

        try {
            $service->transitionStatus(
                $order->id,
                PurchaseOrder::STATUS_RECEIVED
            );

            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame(
            13,
            $this->product1->fresh()->stock_quantity
        );
    }

    public function test_cancelled_order_cannot_be_received(): void
    {
        $order = app(PurchaseOrderService::class)->createDraft([
            'supplier_id' => $this->supplier->id,
            'items' => [
                [
                    'product_id' => $this->product1->id,
                    'quantity' => 3,
                ],
            ],
        ]);

        $service = app(PurchaseOrderService::class);

        $service->transitionStatus(
            $order->id,
            PurchaseOrder::STATUS_CANCELLED
        );

        try {
            $service->transitionStatus(
                $order->id,
                PurchaseOrder::STATUS_RECEIVED
            );

            $this->fail('Expected a validation exception.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('status', $exception->errors());
        }

        $this->assertSame(
            10,
            $this->product1->fresh()->stock_quantity
        );
    }
}