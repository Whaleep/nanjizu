<?php

namespace App\Models;

// 1. 新增這兩行引入
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',    // 新增
        'phone',   // 新增
        'address', // 新增
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    // 關鍵方法：定義誰能進後台
    public function canAccessPanel(Panel $panel): bool
    {
        // 只有 role 是 'admin' 且驗證過 Email (選填) 的人可以進後台
        // 這裡簡單判斷 role 即可
        return $this->role === 'admin';
    }

    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    // User 有很多收藏的商品
    public function wishlists()
    {
        return $this->belongsToMany(Product::class, 'wishlists', 'user_id', 'product_id')->withTimestamps();
    }
}
