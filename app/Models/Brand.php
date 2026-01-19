<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

use App\Traits\HasMediaCollections;
use Spatie\MediaLibrary\HasMedia;

class Brand extends Model implements HasMedia
{
    use HasMediaCollections;
    protected $guarded = [];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('brand_logo')
            ->singleFile()
            ->useDisk(config('media-library.disk_name'));
    }

    public function deviceCategories()
    {
        return $this->hasMany(DeviceCategory::class);
    }

    // 為了方便，也可以保留直接抓底下所有型號的關聯 (透過 HasManyThrough)
    public function deviceModels()
    {
        return $this->hasMany(DeviceModel::class);
    }
}
