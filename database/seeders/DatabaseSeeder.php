<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShopCategory;
use App\Models\Product;
use App\Models\ProductTag;
use App\Models\ProductVariant;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PageSeeder::class,
        ]);

        // \App\Models\User::factory(10)->create();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);

        // 1. 建立標籤
        $tags = ProductTag::factory()->count(5)->create();

        // 2. 建立分類 (2層結構: 品牌 -> 系列)
        $brands = ShopCategory::factory()->count(3)->create(['parent_id' => null]);

        foreach ($brands as $brand) {
            $seriesList = ShopCategory::factory()->count(3)->create(['parent_id' => $brand->id]);

            foreach ($seriesList as $series) {
                // 3. 每個系列建立 5 個商品
                $products = Product::factory()->count(5)->create(['shop_category_id' => $series->id]);

                foreach ($products as $product) {
                    // 4. 隨機關聯 1~2 個標籤
                    $product->tags()->attach($tags->random(rand(1, 2)));

                    // 5. 每個商品建立 2~3 個規格
                    ProductVariant::factory()->count(rand(2, 3))->create(['product_id' => $product->id]);
                }
            }
        }
    }
}
