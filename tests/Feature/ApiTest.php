<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Foundation\Testing\WithFaker;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;

class ApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_returns_a_sanctum_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'api-test@example.test',
            'password' => Hash::make('Test@12345'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'api-test@example.test',
            'password' => 'Test@12345',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'message',
                'user' => ['id', 'name', 'email'],
                'token',
                'token_type',
            ])
            ->assertJsonPath('token_type', 'Bearer');
    }

    public function test_inventory_api_requires_authentication(): void
    {
        $this->getJson('/api/inventory')
            ->assertUnauthorized();

        $this->getJson('/api/inventory/low-stock')
            ->assertUnauthorized();
    }

    public function test_authenticated_user_can_view_paginated_inventory(): void
    {
        $user = User::factory()->create();

        Product::create([
            'sku' => 'API001',
            'name' => 'API Test Product',
            'unit_price' => 500.00,
            'stock_quantity' => 4,
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->getJson('/api/inventory')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'API001');

        $this->getJson('/api/inventory/low-stock')
            ->assertOk()
            ->assertJsonPath('data.0.sku', 'API001');
    }

    public function test_purchase_order_api_rejects_zero_quantity(): void
    {
        $user = User::factory()->create();

        $supplier = Supplier::create([
            'name' => 'API Test Supplier',
            'email' => 'api-supplier@example.test',
            'is_active' => true,
        ]);

        $product = Product::create([
            'sku' => 'API002',
            'name' => 'API Test Product',
            'unit_price' => 100.00,
            'stock_quantity' => 10,
            'low_stock_threshold' => 2,
            'is_active' => true,
        ]);

        Sanctum::actingAs($user);

        $this->postJson('/api/purchase-orders', [
            'supplier_id' => $supplier->id,
            'items' => [
                [
                    'product_id' => $product->id,
                    'quantity' => 0,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.quantity']);

        $this->assertDatabaseCount('purchase_orders', 0);
    }
}
