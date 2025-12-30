<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShopCategory extends Model
{
    use HasFactory;
    protected $guarded = [];

    // 父分類
    public function parent(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'parent_id');
    }

    // 子分類
    public function children(): HasMany
    {
        return $this->hasMany(ShopCategory::class, 'parent_id');
    }

    // 分類下的商品
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // 取得所有祖先分類 (包含自己)，回傳一個 Collection
    public function getAncestorsAttribute()
    {
        $ancestors = collect([]);
        $category = $this;

        // 一直往上找 parent，直到沒有為止
        while ($category) {
            $ancestors->prepend($category); // 把找到的放最前面
            $category = $category->parent;
        }

        return $ancestors;
    }

    // 取得完整路徑名稱，例如: "Apple > iPhone 15 > 電池"
    public function getFullNameAttribute()
    {
        // 如果想要效能好一點，可以只抓 parent->name，但為了完整性，我們用剛剛寫的 ancestors
        return $this->ancestors->pluck('name')->join(' > ');
    }

    // 取得自己以及所有子孫分類的 ID 陣列
    public function getAllChildrenIds()
    {
        $ids = collect([$this->id]); // 先加入自己

        foreach ($this->children as $child) {
            // 遞迴呼叫
            $ids = $ids->merge($child->getAllChildrenIds());
        }

        return $ids;
    }
}
