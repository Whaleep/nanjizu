<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Promotion;
use App\Models\ShopCategory;
use App\Services\DiscountService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $discountService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->discountService = new DiscountService();
    }

    public function test_get_discounted_price_fixed_discount()
    {
        $product = Product::factory()->create(['price' => 1000]);

        $promotion = Promotion::create([
            'name' => 'Test Promo',
            'type' => 'direct',
            'scope' => 'product',
            'action_type' => 'fixed',
            'action_value' => 200,
            'is_active' => true,
        ]);
        $promotion->products()->attach($product->id);

        $this->assertEquals(800, $this->discountService->getDiscountedPrice($product));
    }

    public function test_get_discounted_price_percent_discount()
    {
        $product = Product::factory()->create(['price' => 1000]);

        $promotion = Promotion::create([
            'name' => '10% Off',
            'type' => 'direct',
            'scope' => 'product',
            'action_type' => 'percent',
            'action_value' => 10,
            'is_active' => true,
        ]);
        $promotion->products()->attach($product->id);

        $this->assertEquals(900, $this->discountService->getDiscountedPrice($product));
    }

    public function test_category_discount()
    {
        $category = ShopCategory::factory()->create();
        $product = Product::factory()->create(['shop_category_id' => $category->id, 'price' => 1000]);

        $promotion = Promotion::create([
            'name' => 'Category Discount',
            'type' => 'direct',
            'scope' => 'category',
            'action_type' => 'percent',
            'action_value' => 20,
            'is_active' => true,
        ]);
        $promotion->categories()->attach($category->id);

        $this->assertEquals(800, $this->discountService->getDiscountedPrice($product));
    }

    public function test_cart_threshold_promotion()
    {
        $product = Product::factory()->create(['price' => 1000]);

        $promotion = Promotion::create([
            'name' => 'Spend 2000 get 500 off',
            'type' => 'threshold_cart',
            'scope' => 'all',
            'action_type' => 'fixed',
            'action_value' => 500,
            'min_threshold' => 2000,
            'is_active' => true,
        ]);

        $cartDetails = collect([
            (object) ['subtotal' => 2500]
        ]);

        $applied = $this->discountService->getCartPromotions($cartDetails);

        $this->assertCount(1, $applied);
        $this->assertEquals(500, $applied->first()['discount_amount']);
    }

    public function test_inactive_promotion_is_ignored()
    {
        $product = Product::factory()->create(['price' => 1000]);

        $promotion = Promotion::create([
            'name' => 'Inactive Promo',
            'type' => 'direct',
            'scope' => 'product',
            'action_type' => 'fixed',
            'action_value' => 200,
            'is_active' => false,
        ]);
        $promotion->products()->attach($product->id);

        $this->assertEquals(1000, $this->discountService->getDiscountedPrice($product));
    }
}
