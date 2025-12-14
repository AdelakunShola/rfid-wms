<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run()
    {
        $products = [
            [
                'sku' => 'PROD-001',
                'name' => 'Sample Product 1',
                'barcode' => '1234567890123',
                'description' => 'This is a sample product',
                'quantity' => 100,
                'price' => 29.99,
            ],
            [
                'sku' => 'PROD-002',
                'name' => 'Sample Product 2',
                'barcode' => '1234567890124',
                'description' => 'Another sample product',
                'quantity' => 50,
                'price' => 49.99,
            ],
            // Add more sample products as needed
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}