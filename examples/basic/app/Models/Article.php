<?php
// examples/basic/app/Models/Article.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'excerpt',
        'featured_image',
        'status',
        'published_at',
        'author_id',
        'category_id',
        'view_count',
        'reading_time',
        'meta_title',
        'meta_description',
        'tags',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'status' => 'boolean',
        'view_count' => 'integer',
        'reading_time' => 'integer',
        'tags' => 'array',
    ];

    protected $dates = [
        'published_at',
        'deleted_at',
    ];

    // 文章状态常量
    const STATUS_DRAFT = false;
    const STATUS_PUBLISHED = true;

    // 关联作者
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    // 关联分类
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // 关联评论
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    // 关联标签
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    // 获取状态显示名称
    public function getStatusNameAttribute(): string
    {
        return $this->status ? '已发布' : '草稿';
    }

    // 获取状态颜色
    public function getStatusColorAttribute(): string
    {
        return $this->status ? 'success' : 'gray';
    }

    // 获取阅读时间（分钟）
    public function getReadingTimeMinutesAttribute(): int
    {
        $wordCount = str_word_count(strip_tags($this->content));
        return max(1, ceil($wordCount / 200)); // 假设每分钟阅读200字
    }

    // 获取摘要
    public function getExcerptAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // 从内容中提取摘要
        $text = strip_tags($this->content);
        return \Str::limit($text, 150);
    }

    // 获取特色图片 URL
    public function getFeaturedImageUrlAttribute(): ?string
    {
        if ($this->featured_image) {
            return asset('storage/' . $this->featured_image);
        }
        
        return null;
    }

    // 获取标签数组
    public function getTagListAttribute(): string
    {
        return implode(', ', $this->tags ?? []);
    }

    // 作用域：已发布
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }

    // 作用域：草稿
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    // 作用域：按作者筛选
    public function scopeByAuthor($query, $authorId)
    {
        return $query->where('author_id', $authorId);
    }

    // 作用域：按分类筛选
    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    // 作用域：按标签筛选
    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    // 作用域：热门文章
    public function scopePopular($query, int $days = 30)
    {
        return $query->published()
                    ->where('published_at', '>=', now()->subDays($days))
                    ->orderBy('view_count', 'desc');
    }

    // 作用域：最近文章
    public function scopeRecent($query, int $limit = 10)
    {
        return $query->published()
                    ->orderBy('published_at', 'desc')
                    ->limit($limit);
    }

    // 增加浏览量
    public function incrementViewCount(): void
    {
        $this->increment('view_count');
    }

    // 发布文章
    public function publish(): void
    {
        $this->update([
            'status' => self::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);
    }

    // 取消发布
    public function unpublish(): void
    {
        $this->update([
            'status' => self::STATUS_DRAFT,
        ]);
    }

    // 复制文章
    public function duplicate(): self
    {
        $newArticle = $this->replicate();
        $newArticle->title = $this->title . ' (副本)';
        $newArticle->slug = null; // 重新生成 slug
        $newArticle->status = self::STATUS_DRAFT;
        $newArticle->published_at = null;
        $newArticle->view_count = 0;
        $newArticle->save();

        return $newArticle;
    }
}