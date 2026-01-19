<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

use App\Traits\HasMediaCollections;
use Spatie\MediaLibrary\HasMedia;

class SecondHandDevice extends Model implements HasMedia
{
    use HasMediaCollections;
    protected $guarded = [];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('device_images')
            ->useDisk(config('media-library.disk_name'));
    }
}
