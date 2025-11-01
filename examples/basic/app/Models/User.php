<?php
// examples/basic/app/Models/User.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'avatar',
        'phone',
        'bio',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_active' => 'boolean',
    ];

    // 用户角色常量
    const ROLE_ADMIN = 'admin';
    const ROLE_EDITOR = 'editor';
    const ROLE_AUTHOR = 'author';
    const ROLE_SUBSCRIBER = 'subscriber';

    // 关联文章
    public function articles()
    {
        return $this->hasMany(Article::class, 'author_id');
    }

    // 关联评论
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    // 检查是否为管理员
    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    // 检查是否为编辑
    public function isEditor(): bool
    {
        return $this->role === self::ROLE_EDITOR;
    }

    // 检查是否为作者
    public function isAuthor(): bool
    {
        return $this->role === self::ROLE_AUTHOR;
    }

    // 获取角色显示名称
    public function getRoleNameAttribute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN => '管理员',
            self::ROLE_EDITOR => '编辑',
            self::ROLE_AUTHOR => '作者',
            self::ROLE_SUBSCRIBER => '订阅者',
            default => '未知',
        };
    }

    // 获取头像 URL
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }
        
        return asset('images/default-avatar.png');
    }

    // 作用域：活跃用户
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // 作用域：按角色筛选
    public function scopeByRole($query, string $role)
    {
        return $query->where('role', $role);
    }

    // 作用域：管理员
    public function scopeAdmins($query)
    {
        return $query->where('role', self::ROLE_ADMIN);
    }
}