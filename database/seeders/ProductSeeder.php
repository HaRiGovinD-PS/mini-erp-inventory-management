<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $products = [
            ['SKU001', 'Laptop', 55000, 15, 5],
            ['SKU002', 'Wireless Mouse', 750, 8, 10],
            ['SKU003', 'Keyboard', 1200, 20, 5],
            ['SKU004', 'Monitor', 12000, 12, 4],
            ['SKU005', 'USB Cable', 250, 6, 10],
            ['SKU006', 'Headphones', 1800, 25, 8],
            ['SKU007', 'Webcam', 2500, 10, 5],
            ['SKU008', 'Printer', 8500, 7, 3],
            ['SKU009', 'External SSD', 6500, 18, 5],
            ['SKU010', 'Router', 3200, 4, 5],
        ];

        foreach ($products as [$sku, $name, $price, $stock, $threshold]) {
            Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'unit_price' => $price,
                    'stock_quantity' => $stock,
                    'low_stock_threshold' => $threshold,
                    'is_active' => true,
                ]
            );
        }
    }
}
