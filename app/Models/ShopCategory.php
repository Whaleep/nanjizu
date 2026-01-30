<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasMediaCollections;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Support\Facades\Log;

class ShopCategory extends Model implements HasMedia
{
    use HasFactory, HasMediaCollections;
    protected $guarded = [];

    protected $appends = ['category_icon_url'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('category_icon')
            ->singleFile()
            ->useDisk(config('media-library.disk_name'));
    }

    public function getCategoryIconUrlAttribute()
    {
        return $this->getFirstMediaUrl('category_icon');
    }

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

    // 取得所有祖先分類 (包含自己)
    public function getAncestorsAttribute(): array
    {
        // 用於快取已計算的結果，避免重複查詢
        if (isset($this->attributes['cached_ancestors'])) {
            return $this->attributes['cached_ancestors'];
        }
        $ancestors = [];
        $current = $this;
        $visitedIds = [$this->id]; // 防止循環引用
        $depth = 0;
        $maxDepth = 10; // 防止異常深度的分類結構
        while ($current->parent && $depth < $maxDepth) {
            $parent = $current->parent;
            // 安全檢查：防止循環引用
            if (in_array($parent->id, $visitedIds)) {
                Log::warning('ShopCategory 循環引用檢測', [
                    'category_id' => $this->id,
                    'circular_id' => $parent->id,
                ]);
                break;
            }
            $visitedIds[] = $parent->id;
            // 放入陣列開頭，確保順序是「最上層 → 下層」
            array_unshift($ancestors, [
                'id' => $parent->id,
                'name' => $parent->name,
                'slug' => $parent->slug,
            ]);
            $current = $parent;
            $depth++;
        }
        // 可選擇快取結果（適用於多次存取場景）
        // $this->attributes['cached_ancestors'] = $ancestors;
        return $ancestors;
    }

        /**
     * 取得完整麵包屑路徑 Accessor（含自己）
     * 用途：前端直接使用，省去組裝邏輯
     */
    public function getBreadcrumbPathAttribute(): array
    {
        $path = $this->ancestors; // 呼叫上面的 accessor
        $path[] = [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
        ];
        return $path;
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
