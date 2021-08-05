<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductSku;
use Illuminate\Database\Seeder;

class ProductsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $products = Product::factory()->count(30)->create();
        foreach ($products as $product) {
            $skus = ProductSku::factory()->count(3)->create(['product_id' => $product->id]);
            $product->update(['price' => $skus->min('price')]);
        }
    }
}
