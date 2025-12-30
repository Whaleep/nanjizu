<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // 檢查優惠券是否有效
    public function isValid($subtotal)
    {
        if (!$this->is_active) return false;
        if ($this->start_at && now()->lt($this->start_at)) return false;
        if ($this->end_at && now()->gt($this->end_at)) return false;
        if ($this->usage_limit && $this->used_count >= $this->usage_limit) return false;
        if ($this->min_spend && $subtotal < $this->min_spend) return false;

        return true;
    }
}
