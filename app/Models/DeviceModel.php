<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceModel extends Model
{
    protected $guarded = [];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function deviceCategory()
    {
        return $this->belongsTo(DeviceCategory::class);
    }
    
    public function prices(): HasMany
    {
        return $this->hasMany(DeviceRepairPrice::class);
    }
}
