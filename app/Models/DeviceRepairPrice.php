<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceRepairPrice extends Model
{
    protected $guarded = [];

    public function deviceModel(): BelongsTo
    {
        return $this->belongsTo(DeviceModel::class);
    }

    public function repairItem(): BelongsTo
    {
        return $this->belongsTo(RepairItem::class);
    }
}
