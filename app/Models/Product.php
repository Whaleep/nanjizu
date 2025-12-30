<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    // 關鍵：一個商品有多個規格
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    // 新增標籤關聯
    public function productTags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_product_tag');
    }

    // 保留這個別名以防其他地方有用到
    public function tags(): BelongsToMany
    {
        return $this->productTags();
    }
}
