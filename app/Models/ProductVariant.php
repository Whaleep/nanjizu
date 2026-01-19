<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasMediaCollections;
use Spatie\MediaLibrary\HasMedia;

class ProductVariant extends Model implements HasMedia
{
    use HasFactory, HasMediaCollections;

    protected $guarded = [];

    protected $appends = ['variant_image_url'];

    public function getVariantImageUrlAttribute()
    {
        // 優先從自己的 Media Library 取得
        $url = $this->getFirstMediaUrl('variant_image');
        if ($url) return $url;

        // 最後回退到舊的 image 欄位路徑 (相容 FileUpload)
        return $this->image;
    }

    protected $casts = [
        'attributes' => 'array',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
