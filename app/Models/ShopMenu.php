<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopMenu extends Model
{
    use HasFactory;
    protected $guarded = [];

    // 自動附加 link 屬性到 JSON
    protected $appends = ['link'];

    // 存取器：自動計算最終連結
    public function getLinkAttribute()
    {
        if ($this->type === 'link') {
            return $this->url;
        }

        if ($this->type === 'category') {
            $category = ShopCategory::find($this->target_id);
            // 這裡直接回傳網址字串
            return $category ? "/shop/category/{$category->slug}" : '#';
        }

        if ($this->type === 'tag') {
            $tag = ProductTag::find($this->target_id);
            return $tag ? "/shop?tag={$tag->slug}" : '#';
        }

        return '#';
    }
}
