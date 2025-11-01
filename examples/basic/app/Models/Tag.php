<?php
// examples/basic/app/Models/Tag.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // 关联文章
    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class);
    }

    // 获取标签颜色样式
    public function getColorStyleAttribute(): string
    {
        return $this->color ?: '#6B7280';
    }

    // 作用域：激活标签
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // 作用域：按使用频率排序
    public function scopePopular($query, int $limit = 10)
    {
        return $query->withCount('articles')
                    ->having('articles_count', '>', 0)
                    ->orderBy('articles_count', 'desc')
                    ->limit($limit);
    }

    // 获取使用次数
    public function getUsageCountAttribute(): int
    {
        return $this->articles()->published()->count();
    }
}