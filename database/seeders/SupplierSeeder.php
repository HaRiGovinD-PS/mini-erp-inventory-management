<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Supplier;

class SupplierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $suppliers = [
            [
                'name' => 'ABC Electronics',
                'email' => 'sales@abcelectronics.test',
                'phone' => '9876500001',
                'address' => 'Kozhikode, Kerala',
                'is_active' => true,
            ],
            [
                'name' => 'Global Office Supplies',
                'email' => 'orders@globaloffice.test',
                'phone' => '9876500002',
                'address' => 'Kochi, Kerala',
                'is_active' => true,
            ],
            [
                'name' => 'TechWorld Distributors',
                'email' => 'contact@techworld.test',
                'phone' => '9876500003',
                'address' => 'Bengaluru, Karnataka',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                ['email' => $supplier['email']],
                $supplier
            );
        }
    }
}
