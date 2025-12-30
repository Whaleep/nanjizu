<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class SecondHandDevice extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::deleting(function (SecondHandDevice $device) {
            if ($device->image) {
                Storage::disk('public')->delete($device->image);
            }
        });
    }
}
