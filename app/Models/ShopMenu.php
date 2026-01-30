<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopMenu extends Model
{
    use HasFactory;
    protected $guarded = [];

    // 自動附加 link 屬性到 JSON
    protected $appends = ['link', 'category_children'];

    /**
     * 1. 定義關聯 (Relationships)
     */

    // 關聯到商品分類
    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'target_id');
    }

    // 關聯到商品標籤
    public function tag(): BelongsTo
    {
        return $this->belongsTo(ProductTag::class, 'target_id');
    }

    // 關聯到特惠活動
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class, 'target_id');
    }

    /**
     * 存取器：自動計算最終連結
     */
    public function getLinkAttribute()
    {
        if ($this->type === 'link') {
            return $this->url;
        }

        if ($this->type === 'category') {
            return $this->category ? "/shop/category/{$this->category->slug}" : '#';
        }

        if ($this->type === 'tag') {
            return $this->tag ? "/shop?tag={$this->tag->slug}" : '#';
        }

        // 特惠活動連結，這裡不需要 slug，直接用 ID 查詢即可 (配合 ShopService 的邏輯)
        if ($this->type === 'promotion') {
            return "/shop?promotion={$this->target_id}";
        }

        return '#';
    }

    // 新增：取得子分類 (如果是 Category 類型)
    public function getCategoryChildrenAttribute()
    {
        if ($this->type === 'category' && $this->category) {
            return $this->category->children ?? [];
        }
        return [];
    }
}
