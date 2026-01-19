<?php

namespace App\Traits;

use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

trait HasMediaCollections
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp', 'image/gif'])
            ->useDisk(config('media-library.disk_name'));

        $this->addMediaCollection('content')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk(config('media-library.disk_name'));

        $this->addMediaCollection('product_images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useDisk(config('media-library.disk_name'));

        $this->addMediaCollection('variant_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued(); // For now, keep it synchronous to see results immediately

        $this->addMediaConversion('medium')
            ->width(800)
            ->height(800)
            ->sharpen(10)
            ->nonQueued();
    }
}
