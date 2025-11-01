# Webman Filament 快速开始指南

## 概述

本指南将帮助您在 15 分钟内快速上手 Webman Filament，创建一个功能完整的管理面板。

## 前置条件

- 已完成 Webman Filament 的安装（参考 [安装指南](installation-guide.md)）
- 具备基本的 PHP 和 Webman 框架知识
- 有一个正在运行的 Webman 项目

## 第一步：创建第一个资源

### 1. 生成资源文件

```bash
# 创建一个文章管理资源
php webman make:filament-resource Article

# 或者指定模型
php webman make:filament-resource Article --model=App\\Models\\Article
```

### 2. 查看生成的文件

```bash
# 查看生成的文件结构
find src/Filament/Resources -name "*.php"
```

生成的文件：
```
src/Filament/Resources/
└── ArticleResource.php
```

### 3. 编辑资源文件

打开 `src/Filament/Resources/ArticleResource.php`：

```php
<?php

namespace App\Filament\Resources;

use App\Models\Article;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = '文章管理';
    protected static ?string $modelLabel = '文章';
    protected static ?string $pluralModelLabel = '文章';
    protected static ?string $navigationGroup = '内容管理';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label('标题')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('content')
                    ->label('内容')
                    ->required()
                    ->rows(10),
                Forms\Components\Select::make('status')
                    ->label('状态')
                    ->options([
                        'draft' => '草稿',
                        'published' => '已发布',
                        'archived' => '已归档',
                    ])
                    ->default('draft')
                    ->required(),
                Forms\Components\DateTimePicker::make('published_at')
                    ->label('发布时间'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('状态')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'published' => 'success',
                        'archived' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('published_at')
                    ->label('发布时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('创建时间')
                    ->dateTime('Y-m-d H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('状态')
                    ->options([
                        'draft' => '草稿',
                        'published' => '已发布',
                        'archived' => '已归档',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
```

## 第二步：创建数据模型

### 1. 生成模型

```bash
# 创建 Article 模型
php webman make:model Article

# 或者使用完整命令
php webman make:model Article -mcr
```

### 2. 编辑模型

打开 `src/Models/Article.php`：

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    // 状态常量
    const STATUS_DRAFT = 'draft';
    const STATUS_PUBLISHED = 'published';
    const STATUS_ARCHIVED = 'archived';

    // 访问器
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => '草稿',
            self::STATUS_PUBLISHED => '已发布',
            self::STATUS_ARCHIVED => '已归档',
            default => '未知',
        };
    }

    // 作用域
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }
}
```

### 3. 创建数据库迁移

```bash
# 生成迁移文件
php webman make:migration create_articles_table

# 编辑迁移文件 database/migrations/xxxx_xx_xx_xxxxxx_create_articles_table.php
```

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->enum('status', ['draft', 'published', 'archived'])
                ->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
```

### 4. 运行迁移

```bash
# 运行迁移
php webman migrate

# 查看迁移状态
php webman migrate:status
```

## 第三步：创建页面类

### 1. 生成页面文件

```bash
# 创建页面类
php webman make:filament-page ListArticles
php webman make:filament-page CreateArticle
php webman make:filament-page EditArticle
```

### 2. 编辑列表页面

打开 `src/Filament/Resources/Pages/ListArticles.php`：

```php
<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListArticles extends ListRecords
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }
}
```

### 3. 编辑创建页面

打开 `src/Filament/Resources/Pages/CreateArticle.php`：

```php
<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 在创建前修改数据
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        // 创建后的逻辑
        $this->redirect($this->getResource()::getUrl('index'));
    }
}
```

### 4. 编辑编辑页面

打开 `src/Filament/Resources/Pages/EditArticle.php`：

```php
<?php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditArticle extends EditRecord
{
    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // 在保存前修改数据
        if ($data['status'] === 'published' && empty($data['published_at'])) {
            $data['published_at'] = now();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // 保存后的逻辑
    }
}
```

## 第四步：测试功能

### 1. 启动开发服务器

```bash
# 启动 Webman
php webman start

# 或者使用热重载
php webman start -d
```

### 2. 访问管理面板

打开浏览器，访问：`http://localhost:8787/admin`

### 3. 登录并测试

1. 使用管理员账号登录
2. 点击左侧导航的"文章管理"
3. 尝试创建、编辑、删除文章
4. 测试搜索和筛选功能

## 第五步：添加高级功能

### 1. 添加图片上传

在 `ArticleResource.php` 的表单中添加：

```php
Forms\Components\FileUpload::make('image')
    ->label('封面图片')
    ->image()
    ->disk('public')
    ->directory('articles')
    ->maxSize(2048)
    ->imageEditor(),
```

### 2. 添加关联关系

创建用户模型关联：

```php
// Article.php 模型
public function user()
{
    return $this->belongsTo(User::class);
}

// ArticleResource.php 中添加
Forms\Components\Select::make('user_id')
    ->label('作者')
    ->relationship('user', 'name')
    ->searchable()
    ->preload(),
```

### 3. 添加自定义操作

```php
// 在 ArticleResource.php 的 actions 中添加
Actions\Action::make('publish')
    ->label('发布')
    ->icon('heroicon-m-check-circle')
    ->color('success')
    ->requiresConfirmation()
    ->action(function ($record) {
        $record->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
    }),
```

### 4. 添加自定义过滤器

```php
// 在 table 的 filters 中添加
Tables\Filters\Filter::make('published_recently')
    ->label('最近发布')
    ->query(fn (Builder $query): Builder => 
        $query->where('status', 'published')
              ->where('published_at', '>=', now()->subDays(30))
    )
    ->indicateUsing(function (array $data): ?string {
        if (!$data['published_recently'] ?? null) {
            return null;
        }

        return '最近30天发布';
    }),
```

## 第六步：自定义主题

### 1. 发布主题文件

```bash
# 发布主题资源
php webman filament:theme:publish
```

### 2. 自定义颜色

编辑 `resources/css/filament.css`：

```css
:root {
    --primary-50: #eff6ff;
    --primary-100: #dbeafe;
    --primary-500: #3b82f6;
    --primary-600: #2563eb;
    --primary-700: #1d4ed8;
}

.fi-btn {
    border-radius: 0.5rem;
}

.fi-modal {
    border-radius: 1rem;
}
```

### 3. 自定义布局

```php
// 在 ArticleResource.php 中
protected static ?string $title = '自定义标题';

protected static ?string $navigationBadgeTooltip = '总文章数';

public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}
```

## 第七步：性能优化

### 1. 添加索引

```php
// 在模型中
protected static function boot()
{
    parent::boot();
    
    static::addGlobalScope('published', function (Builder $builder) {
        if (request()->is('admin/articles*')) {
            $builder->published();
        }
    });
}
```

### 2. 缓存查询

```php
// 在 ArticleResource.php 中
protected function mutateFormDataBeforeCreate(array $data): array
{
    // 缓存热门标签
    $data['popular_tags'] = Cache::remember('popular_tags', 3600, function () {
        return Tag::popular()->pluck('name')->toArray();
    });

    return $data;
}
```

### 3. 分页优化

```php
// 在列表页面中
protected function getTableRecordsPerPageSelectOptions(): array
{
    return [10, 25, 50, 100, 250];
}

protected function getTableQuery(): Builder
{
    return parent::getTableQuery()
        ->with(['user', 'tags'])
        ->latest();
}
```

## 常见问题解决

### 问题 1：资源页面不显示

**检查项目**：
1. 模型是否存在且正确
2. 资源文件是否在正确位置
3. 路由是否正确注册

**解决方案**：
```bash
# 清除缓存
php webman route:clear
php webman config:clear

# 检查资源
php webman filament:list
```

### 问题 2：表单验证失败

**常见原因**：
1. 模型fillable属性设置错误
2. 数据库字段类型不匹配
3. 表单组件配置错误

**解决方案**：
```php
// 检查模型
protected $fillable = ['title', 'content', 'status', 'published_at'];

// 检查表单组件
Forms\Components\TextInput::make('title')
    ->required()
    ->maxLength(255),
```

### 问题 3：权限错误

**解决方案**：
```php
// 在资源中添加权限检查
public static function canViewAny(): bool
{
    return auth()->user()->can('view articles');
}

public static function canCreate(): bool
{
    return auth()->user()->can('create articles');
}
```

## 下一步

完成快速开始后，您可以：

1. **创建更多资源**：用户管理、分类管理、标签管理等
2. **自定义仪表板**：添加图表和统计信息
3. **设置权限**：配置角色和权限
4. **添加通知**：邮件通知、Webhook等
5. **集成第三方服务**：支付、地图、文件存储等

## 示例代码

完整的示例代码可以在 `examples/quick-start/` 目录中找到：

```bash
# 查看示例
ls examples/quick-start/
```

示例包含：
- 完整的模型文件
- 资源文件
- 页面类
- 迁移文件
- 种子文件

## 支持资源

- 📚 [官方文档](https://filamentphp.com/docs)
- 💬 [社区论坛](https://github.com/filamentphp/filament/discussions)
- 🐛 [问题反馈](https://github.com/filamentphp/filament/issues)
- 📹 [视频教程](https://www.youtube.com/playlist?list=PLcyQucyOIh5y1f8aHr5oC9)

---

**快速链接**：
- [安装指南](installation-guide.md) ← 回到安装
- [系统要求](requirements.md) ← 查看要求
- [升级指南](upgrade-guide.md) ← 了解升级

**更新时间**: 2025-11-01  
**版本**: 1.0.0