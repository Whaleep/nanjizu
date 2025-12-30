<?php

namespace App\Models;

// 1. 新增這兩行引入
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
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
    
    // 3. 新增這個方法，用來定義誰可以進入後台
    public function canAccessPanel(Panel $panel): bool
    {
        // 簡單做法：允許所有擁有帳號的人登入
        return true; 
        
        // 進階做法 (如果您只想讓特定 Email 登入)：
        // return str_ends_with($this->email, '@yourdomain.com') && $this->hasVerifiedEmail();
    }
}
