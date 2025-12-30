<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Brand extends Model
{
    protected $guarded = [];

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
