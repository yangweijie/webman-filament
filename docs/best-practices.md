# 最佳实践指南

## 概述

本文档提供了使用 webman-filament 扩展的最佳实践，包括性能优化、安全配置、开发规范、部署建议等方面的指导。

## 性能优化最佳实践

### 1. 数据库优化

#### 1.1 查询优化

```php
<?php
// 好的做法：使用 select 限制查询字段
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->select([
            'id', 'name', 'email', 'status', 'created_at'
        ])
        ->where('status', 'active');
}

// 避免：查询所有字段
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery(); // 不推荐
}
```

#### 1.2 关联预加载

```php
<?php
// 好的做法：预加载关联数据
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->with([
            'category:id,name,slug',
            'tags:id,name',
            'author:id,name,email'
        ]);
}

// 在表格中使用时
public static function table(Tables\Table $table): Tables\Table
{
    return $table
        ->columns([
            Tables\Columns\TextColumn::make('category.name')
                ->label('分类'), // 不会触发 N+1 查询
        ]);
}
```

#### 1.3 索引优化

```php
<?php
// 在迁移中添加适当的索引
Schema::table('articles', function (Blueprint $table) {
    $table->index(['status', 'published_at']);
    $table->index('category_id');
    $table->index('author_id');
    $table->index(['title', 'status']); // 复合索引
});

// 在模型中使用查询作用域
class Article extends Model
{
    public function scopePublished($query)
    {
        return $query->where('status', true)
                    ->whereNotNull('published_at')
                    ->where('published_at', '<=', now());
    }
}
```

#### 1.4 分页优化

```php
<?php
// 好的做法：使用简单分页
public static function table(Tables\Table $table): Tables\Table
{
    return $table
        ->paginated([25, 50, 100])
        ->defaultPaginationPageOption(25);
}

// 对于大数据集，使用游标分页
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->orderBy('id'); // 游标分页需要排序字段
}

// 在资源中启用游标分页
public static function getTable(): Table
{
    return Table::make()
        ->poll('30s')
        ->cursorPaginate(15);
}
```

### 2. 缓存策略

#### 2.1 配置缓存

```php
<?php
// config/filament.php
return [
    'cache' => [
        'enabled' => true,
        'driver' => 'redis', // 推荐使用 Redis
        'prefix' => 'filament_cache',
        'ttl' => 3600, // 1小时
        'tags' => ['filament', 'resources'],
    ],
];
```

#### 2.2 查询缓存

```php
<?php
// 在资源中使用缓存
public static function getNavigationBadge(): ?string
{
    return Cache::remember(
        'articles_count_' . auth()->id(),
        300, // 5分钟缓存
        fn () => static::getModel()::where('user_id', auth()->id())->count()
    );
}

// 缓存统计数据
public static function getGloballySearchableAttributes(): array
{
    return Cache::remember(
        'searchable_attributes_' . static::getModel(),
        3600,
        function () {
            return parent::getGloballySearchableAttributes();
        }
    );
}
```

#### 2.3 视图缓存

```php
<?php
// 启用视图缓存
'view' => [
    'cache' => true,
    'compiled_path' => storage_path('framework/views'),
],

// 清除视图缓存
\Illuminate\Support\Facades\Artisan::call('view:clear');
```

### 3. 内存优化

#### 3.1 批量处理

```php
<?php
// 好的做法：使用批量操作
public static function bulkAction(Action $action): Action
{
    return $action
        ->action(function ($records) {
            $records->each->update(['status' => 'approved']);
        });
}

// 避免：逐个处理大量记录
public static function bulkAction(Action $action): Action
{
    return $action
        ->action(function ($records) {
            foreach ($records as $record) {
                $record->update(['status' => 'approved']);
                // 这样会导致内存溢出
            }
        });
}
```

#### 3.2 内存监控

```php
<?php
// 添加内存监控中间件
class MemoryMonitoringMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $startMemory = memory_get_usage();
        
        $response = $next($request);
        
        $endMemory = memory_get_usage();
        $memoryDiff = $endMemory - $startMemory;
        
        // 如果内存使用超过 10MB，记录警告
        if ($memoryDiff > 10 * 1024 * 1024) {
            Log::warning('High memory usage detected', [
                'endpoint' => $request->path(),
                'memory_diff' => $this->formatBytes($memoryDiff),
                'peak_memory' => $this->formatBytes(memory_get_peak_usage()),
            ]);
        }
        
        return $response;
    }
}
```

### 4. 静态资源优化

#### 4.1 资源压缩

```php
<?php
// 启用资源压缩
'assets' => [
    'compress' => true,
    'minify_css' => true,
    'minify_js' => true,
    'optimize_images' => true,
],

// 使用 CDN
'cdn' => [
    'enabled' => env('APP_CDN_ENABLED', false),
    'url' => env('APP_CDN_URL'),
],
```

#### 4.2 懒加载

```php
<?php
// 在组件中使用懒加载
class LazyLoadedWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    
    public function mount()
    {
        // 只在需要时加载数据
        $this->loadData();
    }
    
    protected function loadData()
    {
        $this->data = Cache::remember(
            'widget_data_' . $this->getId(),
            300,
            function () {
                return $this->fetchData();
            }
        );
    }
}
```

## 安全最佳实践

### 1. 认证和授权

#### 1.1 权限检查

```php
<?php
// 在资源中实现权限控制
class ArticleResource extends Resource
{
    public static function canViewAny(): bool
    {
        return auth()->user()->can('view articles');
    }
    
    public static function canCreate(): bool
    {
        return auth()->user()->can('create articles');
    }
    
    public static function canEdit(Model $record): bool
    {
        return auth()->user()->can('edit articles') && 
               ($record->author_id === auth()->id() || auth()->user()->isAdmin());
    }
    
    public static function canDelete(Model $record): bool
    {
        return auth()->user()->can('delete articles') && 
               ($record->author_id === auth()->id() || auth()->user()->isAdmin());
    }
}
```

#### 1.2 行级权限

```php
<?php
// 使用查询作用域实现行级权限
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->when(!auth()->user()->isAdmin(), function ($query) {
            return $query->where('author_id', auth()->id());
        });
}
```

#### 1.3 字段级权限

```php
<?php
// 在表单中实现字段级权限
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\TextInput::make('title')
            ->label('标题')
            ->required(),
            
        Forms\Components\TextInput::make('price')
            ->label('价格')
            ->required()
            ->visible(auth()->user()->can('edit prices')),
            
        Forms\Components\Select::make('status')
            ->label('状态')
            ->options([
                'draft' => '草稿',
                'published' => '已发布',
            ])
            ->visible(auth()->user()->isAdmin()),
    ]);
}
```

### 2. 输入验证

#### 2.1 表单验证

```php
<?php
// 使用 Laravel 验证规则
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\TextInput::make('email')
            ->label('邮箱')
            ->email()
            ->required()
            ->unique(ignoreRecord: true)
            ->maxLength(255),
            
        Forms\Components\TextInput::make('phone')
            ->label('电话')
            ->tel()
            ->unique(ignoreRecord: true)
            ->regex('/^1[3-9]\d{9}$/'),
            
        Forms\Components\FileUpload::make('avatar')
            ->label('头像')
            ->image()
            ->avatar()
            ->required()
            ->acceptedFileTypes(['image/jpeg', 'image/png'])
            ->maxSize(2048), // 2MB
    ]);
}
```

#### 2.2 自定义验证

```php
<?php
// 创建自定义验证规则
use Illuminate\Contracts\Validation\Rule;

class UniqueSlugRule implements Rule
{
    public function __construct(protected ?int $ignoreId = null) {}
    
    public function passes($attribute, $value)
    {
        $query = \App\Models\Article::where('slug', $value);
        
        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }
        
        return !$query->exists();
    }
    
    public function message()
    {
        return '该别名已被使用。';
    }
}

// 在表单中使用
public static function form(Form $form): Form
{
    return $form->schema([
        Forms\Components\TextInput::make('slug')
            ->label('别名')
            ->required()
            ->regex('/^[a-z0-9-]+$/')
            ->rule(new UniqueSlugRule($this->record?->id))
            ->helperText('只能包含小写字母、数字和连字符'),
    ]);
}
```

### 3. CSRF 保护

```php
<?php
// 确保 CSRF 保护已启用
'csrf' => [
    'enabled' => true,
    'timeout' => 7200, // 2小时
],

// 在表单中添加 CSRF 字段
public static function form(Form $form): Form
{
    return $form
        ->schema([
            // ... 其他字段
        ])
        ->statePath('data');
}
```

### 4. SQL 注入防护

```php
<?php
// 好的做法：使用参数化查询
public static function getEloquentQuery(): Builder
{
    return parent::getEloquentQuery()
        ->where('user_id', '=', auth()->id()) // 安全
        ->where('status', '=', 'active');
}

// 避免：字符串拼接
public static function getEloquentQuery(): Builder
{
    $userId = auth()->id();
    return parent::getEloquentQuery()
        ->whereRaw("user_id = {$userId}"); // 不安全
}
```

## 开发最佳实践

### 1. 代码组织

#### 1.1 目录结构

```
app/
├── Filament/
│   ├── Resources/
│   │   ├── ArticleResource.php
│   │   └── UserResource.php
│   ├── Pages/
│   │   └── Dashboard.php
│   ├── Widgets/
│   │   └── StatsOverview.php
│   ├── Panels/
│   │   └── AdminPanelProvider.php
│   └── RelationManagers/
│       └── ArticleRelationManager.php
├── Services/
│   ├── ArticleService.php
│   └── UserService.php
└── Models/
    ├── Article.php
    └── User.php
```

#### 1.2 服务类使用

```php
<?php
// 创建服务类处理业务逻辑
class ArticleService
{
    public function __construct(
        protected ArticleRepository $repository,
        protected CacheInterface $cache
    ) {}
    
    public function createArticle(array $data): Article
    {
        $article = $this->repository->create($data);
        
        // 清除相关缓存
        $this->cache->tags(['articles'])->flush();
        
        return $article;
    }
    
    public function publishArticle(Article $article): Article
    {
        $article->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        return $article;
    }
}

// 在资源中使用服务类
class ArticleResource extends Resource
{
    public static function form(Form $form): Form
    {
        return $form
            ->schema([/* ... */])
            ->statePath('data');
    }
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $service = app(ArticleService::class);
        $data['author_id'] = auth()->id();
        
        return $data;
    }
    
    protected function handleCreate(array $data): Model
    {
        $service = app(ArticleService::class);
        return $service->createArticle($data);
    }
}
```

### 2. 测试策略

#### 2.1 单元测试

```php
<?php
// tests/Feature/ArticleResourceTest.php

use App\Filament\Resources\ArticleResource;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleResourceTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_article()
    {
        $user = User::factory()->create();
        $data = [
            'title' => 'Test Article',
            'content' => 'Test content',
            'status' => false,
        ];
        
        $resource = new ArticleResource();
        $record = $resource->create($data);
        
        $this->assertInstanceOf(Article::class, $record);
        $this->assertEquals($data['title'], $record->title);
        $this->assertEquals($user->id, $record->author_id);
    }
    
    public function test_can_filter_articles_by_status()
    {
        Article::factory()->count(5)->create(['status' => true]);
        Article::factory()->count(3)->create(['status' => false]);
        
        $resource = new ArticleResource();
        $query = $resource->getEloquentQuery();
        
        $publishedCount = $query->where('status', true)->count();
        $draftCount = $query->where('status', false)->count();
        
        $this->assertEquals(5, $publishedCount);
        $this->assertEquals(3, $draftCount);
    }
}
```

#### 2.2 功能测试

```php
<?php
// tests/Browser/ArticleManagementTest.php

use App\Models\User;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ArticleManagementTest extends DuskTestCase
{
    public function test_user_can_create_article()
    {
        $user = User::factory()->create();
        
        $this->browse(function (Browser $browser) use ($user) {
            $browser->loginAs($user)
                    ->visit(new ArticleResource\Pages\CreateArticle)
                    ->type('@title', 'Test Article')
                    ->type('@content', 'Test content')
                    ->press('@create')
                    ->assertSee('Test Article');
        });
    }
}
```

### 3. 代码规范

#### 3.1 命名规范

```php
<?php
// 好的命名
class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    public static function form(Form $form): Form
    public static function table(Table $table): Table
    public static function getRelations(): array
}

// 避免的命名
class ArticleMgr extends Resource // 不清晰的缩写
{
    protected static $model = Article::class; // 缺少类型声明
}
```

#### 3.2 注释和文档

```php
<?php
/**
 * 文章资源管理
 * 
 * 提供文章的创建、编辑、删除和列表功能
 * 支持富文本编辑、分类管理和标签系统
 * 
 * @package App\Filament\Resources
 */
class ArticleResource extends Resource
{
    /**
     * 关联的模型类
     *
     * @var class-string<Article>
     */
    protected static ?string $model = Article::class;
    
    /**
     * 导航图标
     *
     * @var string
     */
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    /**
     * 创建文章表单
     *
     * @param Form $form 表单实例
     * @return Form 配置后的表单
     */
    public static function form(Form $form): Form
    {
        // 实现逻辑
    }
}
```

## 部署最佳实践

### 1. 环境配置

#### 1.1 生产环境配置

```php
<?php
// .env.production
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Redis 配置
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=your_redis_password
REDIS_PORT=6379

# 缓存配置
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

# Filament 配置
FILAMENT_CACHE_ENABLED=true
FILAMENT_ASSET_URL=/assets
```

#### 1.2 性能配置

```php
<?php
// config/filament.php
return [
    'cache' => [
        'enabled' => env('FILAMENT_CACHE_ENABLED', true),
        'driver' => env('CACHE_DRIVER', 'file'),
        'ttl' => 3600,
    ],
    
    'assets' => [
        'cdn_url' => env('FILAMENT_CDN_URL'),
        'version' => env('FILAMENT_ASSET_VERSION', 'v1.0.0'),
    ],
    
    'performance' => [
        'query_cache' => true,
        'view_cache' => true,
        'config_cache' => true,
        'route_cache' => true,
    ],
];
```

### 2. 监控和日志

#### 2.1 性能监控

```php
<?php
// 配置性能监控
'monitoring' => [
    'enabled' => true,
    'log_slow_queries' => true,
    'slow_query_threshold' => 1000, // 毫秒
    'memory_threshold' => 128 * 1024 * 1024, // 128MB
    'response_time_threshold' => 2000, // 毫秒
],

// 使用监控中间件
class PerformanceMonitor
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $next($request);
        
        $this->recordMetrics($request, $startTime, $startMemory);
        
        return $response;
    }
}
```

#### 2.2 错误监控

```php
<?php
// 配置错误监控
'monitoring' => [
    'sentry' => [
        'enabled' => env('SENTRY_ENABLED', false),
        'dsn' => env('SENTRY_DSN'),
        'environment' => env('APP_ENV'),
    ],
],

// 记录关键操作日志
class AuditLogger
{
    public function logUserAction(string $action, array $context = []): void
    {
        Log::channel('audit')->info($action, array_merge($context, [
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email ?? 'guest',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]));
    }
}
```

### 3. 备份策略

#### 3.1 数据库备份

```php
<?php
// 自动化备份脚本
class DatabaseBackup
{
    public function createBackup(): string
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $filename = "backup_{$timestamp}.sql";
        $path = storage_path("backups/{$filename}");
        
        $command = sprintf(
            'mysqldump -h%s -u%s -p%s %s > %s',
            config('database.connections.mysql.host'),
            config('database.connections.mysql.username'),
            config('database.connections.mysql.password'),
            config('database.connections.mysql.database'),
            $path
        );
        
        exec($command);
        
        // 压缩备份文件
        $this->compressBackup($path);
        
        // 清理旧备份
        $this->cleanupOldBackups();
        
        return $path;
    }
    
    protected function cleanupOldBackups(): void
    {
        $backupDir = storage_path('backups');
        $files = glob($backupDir . '/*.sql*');
        $cutoffTime = now()->subDays(30);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime->timestamp) {
                unlink($file);
            }
        }
    }
}
```

#### 3.2 文件备份

```php
<?php
// 备份上传的文件
class FileBackup
{
    public function backupUploads(): void
    {
        $sourceDir = storage_path('app/public/uploads');
        $backupDir = storage_path('backups/uploads/' . date('Y-m-d'));
        
        if (is_dir($sourceDir)) {
            $this->copyDirectory($sourceDir, $backupDir);
        }
    }
}
```

### 4. 扩展性设计

#### 4.1 插件架构

```php
<?php
// 定义插件接口
interface FilamentPluginInterface
{
    public function getName(): string;
    public function getVersion(): string;
    public function boot(): void;
    public function register(): void;
    public function isEnabled(): bool;
}

// 插件管理器
class PluginManager
{
    protected array $plugins = [];
    
    public function registerPlugin(FilamentPluginInterface $plugin): void
    {
        if ($plugin->isEnabled()) {
            $this->plugins[$plugin->getName()] = $plugin;
        }
    }
    
    public function bootPlugins(): void
    {
        foreach ($this->plugins as $plugin) {
            $plugin->boot();
        }
    }
}
```

#### 4.2 事件驱动架构

```php
<?php
// 定义事件
class ArticleCreated
{
    public function __construct(
        public Article $article,
        public User $user
    ) {}
}

// 事件监听器
class ArticleCreatedListener
{
    public function handle(ArticleCreated $event): void
    {
        // 发送通知
        Notification::send($event->user, new ArticlePublished($event->article));
        
        // 更新统计
        Stats::increment('articles.created');
        
        // 触发其他事件
        event(new ArticleIndexUpdated($event->article));
    }
}

// 注册事件监听器
Event::listen(ArticleCreated::class, ArticleCreatedListener::class);
```

## 故障排除最佳实践

### 1. 常见问题诊断

#### 1.1 性能问题

```php
<?php
// 性能诊断工具
class PerformanceDiagnostic
{
    public function diagnose(): array
    {
        return [
            'memory_usage' => $this->getMemoryUsage(),
            'query_count' => $this->getQueryCount(),
            'slow_queries' => $this->getSlowQueries(),
            'cache_hit_rate' => $this->getCacheHitRate(),
            'response_time' => $this->getAverageResponseTime(),
        ];
    }
    
    protected function getSlowQueries(): array
    {
        return DB::getQueryLog()
            ->filter(fn ($query) => $query['time'] > 1000)
            ->values()
            ->toArray();
    }
}
```

#### 1.2 内存泄漏检测

```php
<?php
// 内存泄漏检测
class MemoryLeakDetector
{
    public function checkForLeaks(): array
    {
        $currentMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();
        
        return [
            'current_usage' => $currentMemory,
            'peak_usage' => $peakMemory,
            'usage_growth' => $this->getMemoryGrowth(),
            'potential_leaks' => $this->detectPotentialLeaks(),
        ];
    }
}
```

### 2. 调试工具

#### 2.1 调试面板

```php
<?php
// 创建调试面板
class DebugPanel extends \Filament\PanelPlugin
{
    public static function make(): static
    {
        return parent::make()
            ->id('debug')
            ->title('调试面板')
            ->viteTheme('resources/css/debug-panel.css');
    }
    
    public function register(Panel $panel): void
    {
        $panel->pages([
            DebugPage::class,
        ]);
        
        $panel->widgets([
            QueryLogWidget::class,
            MemoryUsageWidget::class,
            CacheStatsWidget::class,
        ]);
    }
}
```

#### 2.2 日志分析

```php
<?php
// 日志分析工具
class LogAnalyzer
{
    public function analyzeErrors(string $timeframe = '1 hour'): array
    {
        $logFile = storage_path('logs/filament.log');
        $errors = [];
        
        if (file_exists($logFile)) {
            $lines = file($logFile);
            $cutoffTime = now()->sub($timeframe);
            
            foreach ($lines as $line) {
                if (strpos($line, 'ERROR') !== false) {
                    $timestamp = $this->extractTimestamp($line);
                    if ($timestamp && $timestamp > $cutoffTime) {
                        $errors[] = $this->parseErrorLine($line);
                    }
                }
            }
        }
        
        return $errors;
    }
}
```

## 总结

遵循这些最佳实践可以确保：

1. **高性能** - 优化的查询、缓存策略和资源管理
2. **高安全性** - 完善的认证授权和输入验证
3. **可维护性** - 清晰的代码组织和规范的开发流程
4. **可扩展性** - 插件架构和事件驱动设计
5. **可监控性** - 全面的日志记录和性能监控

## 下一步

- 查看 [基础使用指南](basic-usage.md) 学习基础功能
- 阅读 [高级功能指南](advanced-features.md) 了解高级特性
- 参考 [自定义开发指南](customization.md) 进行深度定制
- 查看 [API 参考文档](api-reference.md) 了解详细 API