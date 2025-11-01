# 基础使用指南

## 概述

本指南将帮助您快速上手使用 webman-filament 扩展，从安装到创建第一个后台管理面板。

## 快速开始

### 1. 环境要求

- PHP 8.1+
- Composer
- MySQL 5.7+ 或 PostgreSQL 9.6+
- Node.js 16+ (用于前端资源编译)

### 2. 安装扩展

```bash
# 安装依赖
composer install

# 运行安装脚本
composer run-script install

# 配置环境
composer run-script configure

# 安装静态资源
composer run-script install-assets

# 运行数据库迁移
composer run-script migrate
```

### 3. 启动服务

```bash
# 启动 webman 服务器
php start.php start

# 开发模式下启动（支持热重载）
php start.php start -d
```

### 4. 访问管理面板

启动成功后，访问 `http://localhost:8787/admin` 即可进入 Filament 管理面板。

## 创建第一个资源

### 1. 创建数据模型

```php
<?php
// app/Models/Article.php

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
        'author_id',
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'status' => 'boolean',
    ];

    // 关联作者
    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
```

### 2. 创建资源文件

```php
<?php
// app/Filament/Resources/ArticleResource.php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\Section;
use App\Models\Article;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;
    
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationLabel = '文章管理';
    
    protected static ?string $modelLabel = '文章';
    
    protected static ?string $pluralModelLabel = '文章';
    
    protected static ?string $navigationGroup = '内容管理';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('基本信息')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('标题')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', \Illuminate\Support\Str::slug($state)) : null),
                        
                        Forms\Components\TextInput::make('slug')
                            ->label('别名')
                            ->required()
                            ->maxLength(255)
                            ->unique(Article::class, 'slug', ignoreRecord: true)
                            ->regex('/^[a-z0-9-]+$/')
                            ->helperText('用于URL的唯一标识符，只能包含小写字母、数字和连字符'),
                    ])
                    ->columns(2),
                
                Forms\Components\Section::make('内容')
                    ->schema([
                        Forms\Components\RichEditor::make('content')
                            ->label('内容')
                            ->required()
                            ->columnSpanFull()
                            ->helperText('支持富文本编辑'),
                    ]),
                
                Forms\Components\Section::make('发布设置')
                    ->schema([
                        Forms\Components\Toggle::make('status')
                            ->label('发布状态')
                            ->required()
                            ->default(false)
                            ->helperText('开启后文章将对外可见'),
                        
                        Forms\Components\DateTimePicker::make('published_at')
                            ->label('发布时间')
                            ->native(false)
                            ->helperText('留空则立即发布'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('标题')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                
                Tables\Columns\TextColumn::make('author.name')
                    ->label('作者')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('status')
                    ->label('状态')
                    ->boolean()
                    ->sortable(),
                
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
                        '1' => '已发布',
                        '0' => '草稿',
                    ]),
                
                Tables\Filters\TernaryFilter::make('published_at')
                    ->label('已发布'),
                
                Tables\Filters\SelectFilter::make('author_id')
                    ->label('作者')
                    ->relationship('author', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('publish')
                        ->label('批量发布')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function ($records) {
                            $records->each->update(['status' => true]);
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50, 100])
            ->poll('30s');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make('文章详情')
                    ->schema([
                        TextEntry::make('title')->label('标题'),
                        TextEntry::make('slug')->label('别名'),
                        TextEntry::make('content')->label('内容')->html(),
                        TextEntry::make('status')->label('状态')->badge()
                            ->color(fn (string $state): string => match ($state) {
                                '1' => 'success',
                                '0' => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('published_at')->label('发布时间')->dateTime(),
                        TextEntry::make('author.name')->label('作者'),
                        TextEntry::make('created_at')->label('创建时间')->dateTime(),
                        TextEntry::make('updated_at')->label('更新时间')->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListArticles::route('/'),
            'create' => Pages\CreateArticle::route('/create'),
            'view' => Pages\ViewArticle::route('/{record}'),
            'edit' => Pages\EditArticle::route('/{record}/edit'),
        ];
    }
}
```

### 3. 创建页面类

```php
<?php
// app/Filament/Resources/ArticleResource/Pages/ListArticles.php

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
}
```

```php
<?php
// app/Filament/Resources/ArticleResource/Pages/CreateArticle.php

namespace App\Filament\Resources\ArticleResource\Pages;

use App\Filament\Resources\ArticleResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateArticle extends CreateRecord
{
    protected static string $resource = ArticleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // 设置当前用户为作者
        $data['author_id'] = auth()->id();
        
        // 如果设置了发布时间但没有设置状态，则自动发布
        if (!empty($data['published_at']) && !isset($data['status'])) {
            $data['status'] = true;
        }

        return $data;
    }
}
```

## 配置面板

### 1. 创建面板提供者

```php
<?php
// app/Filament/Providers/AdminPanelProvider.php

namespace App\Filament\Providers;

use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\FontProviders\GoogleFontProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => Color::Amber,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'danger' => Color::Red,
            ])
            ->font(
                provider: GoogleFontProvider::class,
                family: 'Inter:ital,wght@0,400;0,500;0,600;0,700;1,400;1,500',
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->middleware([
                \WebmanFilament\Support\Middleware\FilamentMiddleware::class,
            ])
            ->authMiddleware([
                \WebmanFilament\Support\Middleware\AuthMiddleware::class,
            ])
            ->plugins([
                // 可以在这里添加 Filament 插件
            ])
            ->viteTheme('resources/css/filament.css');
    }
}
```

### 2. 注册面板提供者

在 `config/filament.php` 中注册：

```php
<?php
// config/filament.php

return [
    'auto_register_routes' => true,
    
    'panels' => [
        'admin' => [
            'id' => 'admin',
            'path' => 'admin',
            'title' => '管理后台',
            'middleware' => ['web'],
            'auth' => [
                'guard' => 'web',
                'pages' => [
                    'login' => \Filament\Pages\Auth\Login::class,
                ],
            ],
            'panel_providers' => [
                \App\Filament\Providers\AdminPanelProvider::class,
            ],
        ],
    ],
    
    // 其他配置...
];
```

## 实际使用场景

### 场景1：内容管理系统

```php
// 文章管理
class ArticleResource extends Resource
{
    // 支持富文本编辑
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\RichEditor::make('content')
                ->label('文章内容')
                ->required()
                ->columnSpanFull(),
            
            Forms\Components\FileUpload::make('featured_image')
                ->label('特色图片')
                ->image()
                ->directory('articles')
                ->maxSize(5120), // 5MB
        ]);
    }
}
```

### 场景2：用户权限管理

```php
// 角色权限管理
class RoleResource extends Resource
{
    protected static ?string $model = Role::class;
    
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\TextInput::make('name')
                ->label('角色名称')
                ->required()
                ->unique(ignoreRecord: true),
            
            Forms\Components\Textarea::make('description')
                ->label('角色描述'),
            
            Forms\Components\CheckboxList::make('permissions')
                ->label('权限')
                ->relationship('permissions', 'name')
                ->columns(3)
                ->gridDirection('row'),
        ]);
    }
}
```

### 场景3：电商产品管理

```php
// 产品管理
class ProductResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('基本信息')
                ->schema([
                    Forms\Components\TextInput::make('name')
                        ->label('产品名称')
                        ->required(),
                    
                    Forms\Components\TextInput::make('sku')
                        ->label('产品编码')
                        ->required()
                        ->unique(ignoreRecord: true),
                    
                    Forms\Components\TextInput::make('price')
                        ->label('价格')
                        ->required()
                        ->numeric()
                        ->prefix('¥'),
                ])
                ->columns(3),
            
            Forms\Components\Section::make('库存管理')
                ->schema([
                    Forms\Components\TextInput::make('stock')
                        ->label('库存数量')
                        ->required()
                        ->numeric(),
                    
                    Forms\Components\TextInput::make('min_stock')
                        ->label('最低库存')
                        ->numeric(),
                ])
                ->columns(2),
            
            Forms\Components\Section::make('产品图片')
                ->schema([
                    Forms\Components\FileUpload::make('images')
                        ->label('产品图片')
                        ->multiple()
                        ->image()
                        ->directory('products')
                        ->maxFiles(5),
                ]),
        ]);
    }
}
```

## 常用组件使用

### 1. 表单组件

```php
// 文本输入
Forms\Components\TextInput::make('title')
    ->label('标题')
    ->required()
    ->maxLength(255)
    ->live(onBlur: true)
    ->afterStateUpdated(fn ($state) => /* 状态更新逻辑 */),

// 下拉选择
Forms\Components\Select::make('category_id')
    ->label('分类')
    ->relationship('category', 'name')
    ->searchable()
    ->preload()
    ->required(),

// 多选
Forms\Components\CheckboxList::make('tags')
    ->label('标签')
    ->relationship('tags', 'name')
    ->columns(3),

// 文件上传
Forms\Components\FileUpload::make('avatar')
    ->label('头像')
    ->image()
    ->avatar()
    ->directory('avatars'),

// 富文本编辑
Forms\Components\RichEditor::make('description')
    ->label('描述')
    ->columnSpanFull(),
```

### 2. 表格列

```php
// 文本列
Tables\Columns\TextColumn::make('title')
    ->label('标题')
    ->searchable()
    ->sortable()
    ->wrap(),

// 图标列
Tables\Columns\IconColumn::make('status')
    ->label('状态')
    ->boolean()
    ->sortable(),

// 标签列
Tables\Columns\BadgeColumn::make('status')
    ->label('状态')
    ->color(fn (string $state): string => match ($state) {
        'active' => 'success',
        'inactive' => 'danger',
        default => 'gray',
    }),

// 图片列
Tables\Columns\ImageColumn::make('avatar')
    ->label('头像')
    ->circular()
    ->defaultImageUrl('/images/default-avatar.png'),
```

### 3. 过滤器

```php
// 文本过滤器
Tables\Filters\Filter::make('created_at')
    ->form([
        Forms\Components\DatePicker::make('created_from'),
        Forms\Components\DatePicker::make('created_until'),
    ])
    ->query(function (Builder $query, array $data): Builder {
        return $query
            ->when(
                $data['created_from'],
                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
            )
            ->when(
                $data['created_until'],
                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
            );
    }),

// 选择过滤器
Tables\Filters\SelectFilter::make('status')
    ->options([
        'active' => '活跃',
        'inactive' => '非活跃',
    ]),
```

## 注意事项

### 1. 性能优化

- 合理使用 `->select()` 和 `->with()` 避免 N+1 查询
- 对于大数据集，使用分页而非一次性加载所有数据
- 启用查询缓存以减少数据库访问

```php
public static function getRelations(): array
{
    return [
        RelationManagers\CommentsRelationManager::class,
    ];
}

public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
}
```

### 2. 安全性

- 验证用户权限，使用 Filament 的权限系统
- 对敏感操作添加确认对话框
- 使用 CSRF 保护

```php
protected static ?string $recordTitleAttribute = 'name';

public static function canViewAny(): bool
{
    return auth()->user()->can('view articles');
}

public static function canCreate(): bool
{
    return auth()->user()->can('create articles');
}
```

### 3. 数据验证

- 在表单中添加适当的验证规则
- 使用自定义验证方法
- 处理文件上传的安全问题

```php
Forms\Components\TextInput::make('email')
    ->email()
    ->required()
    ->unique(ignoreRecord: true)
    ->maxLength(255),

Forms\Components\FileUpload::make('document')
    ->required()
    ->acceptedFileTypes(['application/pdf'])
    ->maxSize(10240), // 10MB
```

## 故障排除

### 1. 常见错误

**路由未找到错误**
```bash
# 清除路由缓存
php artisan route:clear

# 重新注册路由
composer run-script configure
```

**权限验证失败**
```php
// 检查中间件配置
'middleware' => ['web', 'auth'],

// 确保用户已登录
public static function canAccess(): bool
{
    return auth()->check();
}
```

**静态资源加载失败**
```bash
# 重新安装静态资源
composer run-script install-assets
```

### 2. 调试技巧

启用调试模式查看详细错误信息：

```php
// config/app.php
'debug' => env('APP_DEBUG', true),
```

使用 Filament 的调试工具：

```php
// 在资源中添加调试信息
public static function getNavigationBadge(): ?string
{
    return static::getModel()::count();
}
```

## 下一步

- 查看 [高级功能指南](advanced-features.md) 了解更多特性
- 阅读 [自定义开发指南](customization.md) 进行深度定制
- 参考 [API 参考文档](api-reference.md) 了解详细 API
- 查看 [最佳实践指南](best-practices.md) 了解推荐做法