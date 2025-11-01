# API 参考文档

## 概述

本文档提供 webman-filament 扩展的完整 API 参考，包括所有类、方法、参数和返回值。

## 核心类

### WebmanFilamentServiceProvider

扩展的核心服务提供者，负责初始化和管理 Filament 集成。

```php
namespace WebmanFilament;

use Illuminate\Support\ServiceProvider;

class WebmanFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    public function boot(): void
    protected function registerAdapters(): void
    protected function registerMiddleware(): void
    protected function registerCommands(): void
}
```

#### 方法详情

##### register()

注册扩展服务到 IoC 容器。

```php
public function register(): void
```

**返回值:** `void`

**示例:**
```php
// 在应用启动时自动调用
$serviceProvider = app(WebmanFilamentServiceProvider::class);
```

##### boot()

启动扩展服务。

```php
public function boot(): void
```

**返回值:** `void`

**功能:**
- 初始化 Filament 面板
- 注册路由和中间件
- 设置事件监听器

---

### FilamentBridge

Filament 桥接器，管理 webman 与 Filament 之间的生命周期。

```php
namespace WebmanFilament\Bridge;

class FilamentBridge
{
    public function __construct(array $config = [])
    public function onStart(Worker $worker): void
    public function onReload(Worker $worker): void
    public function onStop(Worker $worker): void
    public function registerAdapter(string $name, object $adapter): void
    public function getAdapter(string $name): ?object
}
```

#### 方法详情

##### onStart()

webman 启动时调用，初始化 Filament 环境。

```php
public function onStart(Worker $worker): void
```

**参数:**
- `Worker $worker` - webman worker 实例

**功能:**
- 初始化 Filament 面板
- 注册资源、页面、组件
- 设置中间件栈
- 启动监控服务

**示例:**
```php
$bridge = new FilamentBridge();
$bridge->onStart($worker);
```

##### onReload()

webman 重载时调用，重新初始化组件。

```php
public function onReload(Worker $worker): void
```

**参数:**
- `Worker $worker` - webman worker 实例

**功能:**
- 清理旧的面板实例
- 重新注册资源
- 更新路由缓存
- 重新加载配置

##### onStop()

webman 停止时调用，清理资源。

```php
public function onStop(Worker $worker): void
```

**参数:**
- `Worker $worker` - webman worker 实例

**功能:**
- 保存会话数据
- 清理临时文件
- 关闭数据库连接
- 停止后台任务

##### registerAdapter()

注册自定义适配器。

```php
public function registerAdapter(string $name, object $adapter): void
```

**参数:**
- `string $name` - 适配器名称
- `object $adapter` - 适配器实例

**示例:**
```php
$bridge->registerAdapter('custom', new CustomAdapter());
```

##### getAdapter()

获取已注册的适配器。

```php
public function getAdapter(string $name): ?object
```

**参数:**
- `string $name` - 适配器名称

**返回值:** `object|null` - 适配器实例或 null

---

## 适配器类

### RequestResponseAdapter

请求响应适配器，处理 webman 与 Laravel 请求之间的转换。

```php
namespace WebmanFilament\Adapter;

class RequestResponseAdapter
{
    public function convertWebmanRequestToIlluminate($connection, $data): IlluminateRequest
    public function convertIlluminateResponseToWebman(IlluminateResponse $response): array
    public function handleJsonRequestResponse($connection, $data): ?array
    public function handleFileUpload($connection, $data): ?array
}
```

#### 方法详情

##### convertWebmanRequestToIlluminate()

转换 webman 请求为 Laravel 请求。

```php
public function convertWebmanRequestToIlluminate($connection, $data): IlluminateRequest
```

**参数:**
- `mixed $connection` - webman 连接实例
- `mixed $data` - 请求数据

**返回值:** `Illuminate\Http\Request` - Laravel 请求实例

**示例:**
```php
$adapter = new RequestResponseAdapter();
$illuminateRequest = $adapter->convertWebmanRequestToIlluminate($connection, $requestData);
```

##### convertIlluminateResponseToWebman()

转换 Laravel 响应为 webman 响应。

```php
public function convertIlluminateResponseToWebman(IlluminateResponse $response): array
```

**参数:**
- `IlluminateResponse $response` - Laravel 响应实例

**返回值:** `array` - webman 响应数组

**返回结构:**
```php
[
    'status' => 200,
    'headers' => ['Content-Type' => 'text/html'],
    'content' => '<html>...</html>',
    'http_version' => '1.1'
]
```

##### handleJsonRequestResponse()

处理 JSON 请求响应。

```php
public function handleJsonRequestResponse($connection, $data): ?array
```

**参数:**
- `mixed $connection` - webman 连接实例
- `mixed $data` - 请求数据

**返回值:** `array|null` - JSON 响应数据或 null

##### handleFileUpload()

处理文件上传请求。

```php
public function handleFileUpload($connection, $data): ?array
```

**参数:**
- `mixed $connection` - webman 连接实例
- `mixed $data` - 请求数据

**返回值:** `array|null` - 上传结果或 null

---

### ServiceContainerAdapter

服务容器适配器，桥接 webman 与 Laravel 的服务容器。

```php
namespace WebmanFilament\Adapter;

class ServiceContainerAdapter
{
    public function __construct(Container $container)
    public function make(string $abstract, array $parameters = [])
    public function singleton(string $abstract, $concrete = null)
    public function bind(string $abstract, $concrete, bool $shared = false)
    public function call($callback, array $parameters = [], string $defaultMethod = null)
    public function has(string $abstract): bool
}
```

#### 方法详情

##### make()

从容器中解析服务。

```php
public function make(string $abstract, array $parameters = [])
```

**参数:**
- `string $abstract` - 服务标识
- `array $parameters` - 构造参数

**返回值:** `mixed` - 服务实例

**示例:**
```php
$adapter = new ServiceContainerAdapter(app());
$service = $adapter->make('App\\Services\\CustomService');
```

##### singleton()

注册单例服务。

```php
public function singleton(string $abstract, $concrete = null)
```

**参数:**
- `string $abstract` - 服务标识
- `mixed $concrete` - 具体实现

**返回值:** `void`

##### bind()

绑定服务到容器。

```php
public function bind(string $abstract, $concrete, bool $shared = false)
```

**参数:**
- `string $abstract` - 服务标识
- `mixed $concrete` - 具体实现
- `bool $shared` - 是否共享实例

**返回值:** `void`

##### call()

调用容器中的回调函数。

```php
public function call($callback, array $parameters = [], string $defaultMethod = null)
```

**参数:**
- `mixed $callback` - 回调函数
- `array $parameters` - 参数
- `string $defaultMethod` - 默认方法名

**返回值:** `mixed` - 回调结果

---

### DatabaseAdapter

数据库适配器，处理数据库连接和查询。

```php
namespace WebmanFilament\Adapter;

class DatabaseAdapter
{
    public function __construct(array $config = [])
    public function getConnection(string $name = null): Connection
    public function query(): Builder
    public function table(string $table): Builder
    public function transaction(callable $callback, int $attempts = 1)
    public function beginTransaction(): void
    public function commit(): void
    public function rollBack(): void
}
```

#### 方法详情

##### getConnection()

获取数据库连接。

```php
public function getConnection(string $name = null): Connection
```

**参数:**
- `string $name` - 连接名称，默认 'default'

**返回值:** `Illuminate\\Database\\Connection` - 数据库连接实例

**示例:**
```php
$adapter = new DatabaseAdapter();
$connection = $adapter->getConnection('mysql');
```

##### query()

创建查询构建器。

```php
public function query(): Builder
```

**返回值:** `Illuminate\\Database\\Query\\Builder` - 查询构建器

##### table()

从指定表创建查询。

```php
public function table(string $table): Builder
```

**参数:**
- `string $table` - 表名

**返回值:** `Illuminate\\Database\\Query\\Builder` - 查询构建器

##### transaction()

执行数据库事务。

```php
public function transaction(callable $callback, int $attempts = 1)
```

**参数:**
- `callable $callback` - 事务回调
- `int $attempts` - 重试次数

**返回值:** `mixed` - 事务结果

---

## 中间件类

### FilamentMiddleware

Filament 核心中间件，处理 Filament 请求。

```php
namespace WebmanFilament\Support\Middleware;

class FilamentMiddleware
{
    public function handle(Request $request, Closure $next, string $panel = null): Response
}
```

#### 方法详情

##### handle()

处理 Filament 请求。

```php
public function handle(Request $request, Closure $next, string $panel = null): Response
```

**参数:**
- `Request $request` - 请求实例
- `Closure $next` - 下一个中间件
- `string $panel` - 面板 ID

**返回值:** `Response` - 响应实例

**示例:**
```php
$middleware = new FilamentMiddleware();
$response = $middleware->handle($request, $next, 'admin');
```

---

### AuthMiddleware

认证中间件，处理用户认证。

```php
namespace WebmanFilament\Support\Middleware;

class AuthMiddleware
{
    public function handle(Request $request, Closure $next, string $guard = null): Response
}
```

#### 方法详情

##### handle()

处理认证请求。

```php
public function handle(Request $request, Closure $next, string $guard = null): Response
```

**参数:**
- `Request $request` - 请求实例
- `Closure $next` - 下一个中间件
- `string $guard` - 认证守护名

**返回值:** `Response` - 响应实例

---

## 工具类

### Logger

日志工具类，提供统一的日志记录功能。

```php
namespace WebmanFilament\Support;

class Logger
{
    public function __construct(string $channel = 'filament')
    public function info(string $message, array $context = []): void
    public function warning(string $message, array $context = []): void
    public function error(string $message, array $context = []): void
    public function debug(string $message, array $context = []): void
    public function log($level, string $message, array $context = []): void
}
```

#### 方法详情

##### info()

记录信息级别日志。

```php
public function info(string $message, array $context = []): void
```

**参数:**
- `string $message` - 日志消息
- `array $context` - 上下文数据

**示例:**
```php
$logger = new Logger();
$logger->info('User logged in', ['user_id' => 123]);
```

##### warning()

记录警告级别日志。

```php
public function warning(string $message, array $context = []): void
```

##### error()

记录错误级别日志。

```php
public function error(string $message, array $context = []): void
```

##### debug()

记录调试级别日志。

```php
public function debug(string $message, array $context = []): void
```

##### log()

记录指定级别的日志。

```php
public function log($level, string $message, array $context = []): void
```

**参数:**
- `mixed $level` - 日志级别
- `string $message` - 日志消息
- `array $context` - 上下文数据

---

## 配置类

### ConfigGenerator

配置生成器，用于生成和更新配置文件。

```php
namespace WebmanFilament\Generator;

class ConfigGenerator
{
    public function __construct(string $configPath = null)
    public function generateFilamentConfig(): array
    public function generateDatabaseConfig(): array
    public function generateAuthConfig(): array
    public function updateConfig(string $key, $value): bool
    public function getConfig(string $key, $default = null)
}
```

#### 方法详情

##### generateFilamentConfig()

生成 Filament 配置。

```php
public function generateFilamentConfig(): array
```

**返回值:** `array` - Filament 配置数组

**示例:**
```php
$generator = new ConfigGenerator();
$config = $generator->generateFilamentConfig();
```

##### generateDatabaseConfig()

生成数据库配置。

```php
public function generateDatabaseConfig(): array
```

**返回值:** `array` - 数据库配置数组

##### generateAuthConfig()

生成认证配置。

```php
public function generateAuthConfig(): array
```

**返回值:** `array` - 认证配置数组

##### updateConfig()

更新配置项。

```php
public function updateConfig(string $key, $value): bool
```

**参数:**
- `string $key` - 配置键
- `mixed $value` - 配置值

**返回值:** `bool` - 更新是否成功

##### getConfig()

获取配置项。

```php
public function getConfig(string $key, $default = null)
```

**参数:**
- `string $key` - 配置键
- `mixed $default` - 默认值

**返回值:** `mixed` - 配置值

---

## 命令类

### InstallCommand

安装命令，用于安装和配置扩展。

```php
namespace WebmanFilament\Command;

class InstallCommand extends Command
{
    protected static $defaultName = 'filament:install';
    protected static $defaultDescription = '安装 Filament 扩展';
    
    public function __construct()
    protected function execute(InputInterface $input, OutputInterface $output): int
    protected function installDependencies(): void
    protected function publishConfig(): void
    protected function installAssets(): void
    protected function runMigrations(): void
}
```

#### 方法详情

##### execute()

执行安装命令。

```php
protected function execute(InputInterface $input, OutputInterface $output): int
```

**参数:**
- `InputInterface $input` - 输入接口
- `OutputInterface $output` - 输出接口

**返回值:** `int` - 命令执行状态码

##### installDependencies()

安装依赖包。

```php
protected function installDependencies(): void
```

##### publishConfig()

发布配置文件。

```php
protected function publishConfig(): void
```

##### installAssets()

安装静态资源。

```php
protected function installAssets(): void
```

##### runMigrations()

运行数据库迁移。

```php
protected function runMigrations(): void
```

---

### ConfigureCommand

配置命令，用于配置扩展参数。

```php
namespace WebmanFilament\Command;

class ConfigureCommand extends Command
{
    protected static $defaultName = 'filament:configure';
    protected static $defaultDescription = '配置 Filament 扩展';
    
    public function __construct()
    protected function execute(InputInterface $input, OutputInterface $output): int
    protected function configureDatabase(): void
    protected function configureAuth(): void
    protected function configurePanels(): void
    protected function validateConfig(): bool
}
```

#### 方法详情

##### execute()

执行配置命令。

```php
protected function execute(InputInterface $input, OutputInterface $output): int
```

##### configureDatabase()

配置数据库设置。

```php
protected function configureDatabase(): void
```

##### configureAuth()

配置认证设置。

```php
protected function configureAuth(): void
```

##### configurePanels()

配置面板设置。

```php
protected function configurePanels(): void
```

##### validateConfig()

验证配置。

```php
protected function validateConfig(): bool
```

**返回值:** `bool` - 验证是否通过

---

## 事件类

### FilamentEvent

Filament 事件基类。

```php
namespace WebmanFilament\Events;

class FilamentEvent
{
    public function __construct(protected array $data = [])
    public function getData(): array
}
```

#### 方法详情

##### getData()

获取事件数据。

```php
public function getData(): array
```

**返回值:** `array` - 事件数据

---

### PanelInitialized

面板初始化事件。

```php
namespace WebmanFilament\Events;

class PanelInitialized extends FilamentEvent
{
    public function __construct(
        protected string $panelId,
        protected object $panel,
        array $data = []
    ) {
        parent::__construct($data);
    }
    
    public function getPanelId(): string
    public function getPanel(): object
}
```

#### 方法详情

##### getPanelId()

获取面板 ID。

```php
public function getPanelId(): string
```

##### getPanel()

获取面板实例。

```php
public function getPanel(): object
```

---

### ResourceRegistered

资源注册事件。

```php
namespace WebmanFilament\Events;

class ResourceRegistered extends FilamentEvent
{
    public function __construct(
        protected string $resourceClass,
        protected string $panelId,
        array $data = []
    ) {
        parent::__construct($data);
    }
    
    public function getResourceClass(): string
    public function getPanelId(): string
}
```

---

## 助手函数

### filament()

获取 Filament 面板实例。

```php
function filament(string $panelId = null): \Filament\Panel | null
```

**参数:**
- `string $panelId` - 面板 ID

**返回值:** `\\Filament\\Panel|null` - 面板实例

**示例:**
```php
$panel = filament('admin');
$resources = $panel->getResources();
```

### filament_asset()

生成 Filament 静态资源 URL。

```php
function filament_asset(string $path): string
```

**参数:**
- `string $path` - 资源路径

**返回值:** `string` - 完整 URL

**示例:**
```php
$cssUrl = filament_asset('css/app.css');
```

### filament_version()

获取 Filament 版本。

```php
function filament_version(): string
```

**返回值:** `string` - 版本号

---

## 常量

### 错误代码常量

```php
namespace WebmanFilament\Constants;

class ErrorCodes
{
    const SUCCESS = 0;
    const CONFIG_NOT_FOUND = 1001;
    const PANEL_NOT_FOUND = 1002;
    const RESOURCE_NOT_FOUND = 1003;
    const AUTHENTICATION_FAILED = 1004;
    const AUTHORIZATION_FAILED = 1005;
    const DATABASE_ERROR = 1006;
    const CACHE_ERROR = 1007;
    const ASSET_ERROR = 1008;
}
```

### 事件常量

```php
namespace WebmanFilament\Constants;

class EventNames
{
    const PANEL_INITIALIZED = 'filament.panel.initialized';
    const RESOURCE_REGISTERED = 'filament.resource.registered';
    const USER_LOGGED_IN = 'filament.user.logged_in';
    const USER_LOGGED_OUT = 'filament.user.logged_out';
    const ASSET_PUBLISHED = 'filament.asset.published';
    const CONFIG_UPDATED = 'filament.config.updated';
}
```

---

## 异常类

### FilamentException

Filament 基础异常类。

```php
namespace WebmanFilament\Exceptions;

class FilamentException extends \Exception
{
    protected $code = 0;
    protected $message = 'An error occurred';
}
```

### ConfigException

配置相关异常。

```php
namespace WebmanFilament\Exceptions;

class ConfigException extends FilamentException
{
    public function __construct(string $message = 'Configuration error', int $code = 1001)
    {
        parent::__construct($message, $code);
    }
}
```

### PanelException

面板相关异常。

```php
namespace WebmanFilament\Exceptions;

class PanelException extends FilamentException
{
    public function __construct(string $message = 'Panel error', int $code = 1002)
    {
        parent::__construct($message, $code);
    }
}
```

### AuthException

认证相关异常。

```php
namespace WebmanFilament\Exceptions;

class AuthException extends FilamentException
{
    public function __construct(string $message = 'Authentication error', int $code = 1004)
    {
        parent::__construct($message, $code);
    }
}
```

---

## 回调和钩子

### 面板回调

```php
// 在面板提供者中使用回调
class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->title('Admin Panel')
            ->middleware(['web', 'auth'])
            ->bootUsing(function (Panel $panel) {
                // 面板启动时执行
            })
            ->registerUsing(function (Panel $panel) {
                // 面板注册时执行
            });
    }
}
```

### 资源回调

```php
// 在资源中使用回调
class UserResource extends Resource
{
    protected static ?string $model = User::class;
    
    public static function getRelations(): array
    {
        return [
            RelationManagers\PostsRelationManager::class,
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return static::getModel()::count() > 50 ? 'warning' : 'success';
    }
}
```

---

## 最佳实践

### 1. 错误处理

```php
try {
    $panel = filament('admin');
    if (!$panel) {
        throw new PanelException('Admin panel not found');
    }
} catch (PanelException $e) {
    Log::error('Panel error', ['error' => $e->getMessage()]);
    return response('Panel not available', 503);
}
```

### 2. 性能优化

```php
// 使用缓存
$resources = Cache::remember('filament_resources', 3600, function () {
    return filament('admin')->getResources();
});

// 使用连接池
$connection = app(DatabaseAdapter::class)->getConnection();
$connection->enableQueryLog();
```

### 3. 安全考虑

```php
// 验证用户权限
public static function canViewAny(): bool
{
    return auth()->user()->can('view admin panel');
}

// 使用中间件保护路由
Route::middleware(['filament.auth.advanced'])->group(function () {
    // 受保护的路由
});
```

## 下一步

- 查看 [基础使用指南](basic-usage.md) 学习基础功能
- 阅读 [高级功能指南](advanced-features.md) 了解高级特性
- 参考 [自定义开发指南](customization.md) 进行深度定制
- 查看 [最佳实践指南](best-practices.md) 了解推荐做法