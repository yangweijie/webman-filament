<?php
// examples/basic/app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'sort_order',
        'is_active',
        'image',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    // 关联文章
    public function articles(): HasMany
    {
        return $this->hasMany(Article::class);
    }

    // 关联子分类
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    // 关联父分类
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    // 获取所有子分类（包括递归）
    public function descendants(): HasMany
    {
        return $this->children()->with('descendants');
    }

    // 获取祖先分类（包括递归）
    public function ancestors(): BelongsTo
    {
        return $this->parent()->with('ancestors');
    }

    // 获取分类层级
    public function getLevelAttribute(): int
    {
        $level = 0;
        $parent = $this->parent;

        while ($parent) {
            $level++;
            $parent = $parent->parent;
        }

        return $level;
    }

    // 获取完整路径
    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' / ', $path);
    }

    // 获取图片 URL
    public function getImageUrlAttribute(): ?string
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        
        return null;
    }

    // 作用域：激活分类
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // 作用域：顶级分类
    public function scopeRoot($query)
    {
        return $query->whereNull('parent_id');
    }

    // 作用域：按排序
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    // 获取文章数量
    public function getArticlesCountAttribute(): int
    {
        return $this->articles()->published()->count();
    }

    // 获取子分类数量
    public function getChildrenCountAttribute(): int
    {
        return $this->children()->count();
    }
}