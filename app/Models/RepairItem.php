<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RepairItem extends Model
{
    protected $guarded = [];
    public function prices(): HasMany
    {
        return $this->hasMany(DeviceRepairPrice::class);
    }
}
