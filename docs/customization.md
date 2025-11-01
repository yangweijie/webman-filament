# 自定义开发指南

## 概述

本指南详细介绍如何深度定制和扩展 webman-filament 扩展，包括自定义适配器、桥接器、中间件、命令等核心组件。

## 核心架构理解

### 1. 适配器模式

webman-filament 使用适配器模式来实现 webman 与 Filament 的无缝集成：

```
┌─────────────────┐    适配器    ┌─────────────────┐
│   Webman        │◄──────────►│    Filament     │
│   (Request)     │   转换      │  (Illuminate)   │
└─────────────────┘             └─────────────────┘
```

### 2. 核心组件关系

```
WebmanFilamentServiceProvider
├── FilamentBridge (桥接器)
│   ├── onStart()     - 启动初始化
│   ├── onReload()    - 热重载处理
│   └── onStop()      - 停止清理
├── Adapter Layer (适配层)
│   ├── RequestResponseAdapter
│   ├── ServiceContainerAdapter
│   ├── DatabaseAdapter
│   └── AuthAdapter
└── Middleware Layer (中间件层)
    ├── FilamentMiddleware
    └── AuthMiddleware
```

## 自定义适配器

### 1. 创建新的适配器

```php
<?php
// src/Adapter/CustomAdapter.php

namespace WebmanFilament\Adapter;

use Workerman\Connection\TcpConnection;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response as IlluminateResponse;

class CustomAdapter
{
    protected $config;
    protected $logger;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->logger = new \WebmanFilament\Support\Logger();
    }

    /**
     * 转换 webman 请求为 Laravel 请求
     */
    public function convertWebmanRequestToIlluminate(TcpConnection $connection, $data): IlluminateRequest
    {
        // 解析请求数据
        $requestData = $this->parseRequestData($connection, $data);
        
        // 创建 Illuminate 请求对象
        $illuminateRequest = IlluminateRequest::create(
            $requestData['uri'],
            $requestData['method'],
            $requestData['data'],
            [],
            [],
            $requestData['headers'],
            $requestData['content']
        );
        
        // 添加 webman 特定的数据
        $illuminateRequest->attributes->set('webman_connection', $connection);
        $illuminateRequest->attributes->set('webman_worker_id', $connection->worker->id ?? null);
        
        $this->logger->info('Request converted', [
            'uri' => $requestData['uri'],
            'method' => $requestData['method'],
        ]);
        
        return $illuminateRequest;
    }

    /**
     * 转换 Laravel 响应为 webman 响应
     */
    public function convertIlluminateResponseToWebman(IlluminateResponse $illuminateResponse): array
    {
        // 提取响应数据
        $responseData = [
            'status' => $illuminateResponse->getStatusCode(),
            'headers' => $illuminateResponse->headers->all(),
            'content' => $illuminateResponse->getContent(),
        ];
        
        $this->logger->info('Response converted', [
            'status' => $responseData['status'],
            'content_length' => strlen($responseData['content']),
        ]);
        
        return $responseData;
    }

    /**
     * 解析请求数据
     */
    protected function parseRequestData(TcpConnection $connection, $data): array
    {
        // 解析 HTTP 请求行
        $requestLine = explode("\n", $data)[0];
        list($method, $uri, $version) = explode(' ', $requestLine);
        
        // 解析请求头
        $headers = [];
        $headerLines = explode("\n", $data);
        foreach ($headerLines as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[strtolower(trim($key))] = trim($value);
            }
        }
        
        // 解析请求体
        $body = '';
        if (strpos($data, "\r\n\r\n") !== false) {
            $body = substr($data, strpos($data, "\r\n\r\n") + 4);
        }
        
        // 解析查询参数
        $queryString = parse_url($uri, PHP_URL_QUERY);
        $queryData = [];
        if ($queryString) {
            parse_str($queryString, $queryData);
        }
        
        // 解析 POST 数据
        $postData = [];
        if ($method === 'POST' && $body) {
            if (isset($headers['content-type']) && 
                strpos($headers['content-type'], 'application/json') !== false) {
                $postData = json_decode($body, true) ?? [];
            } elseif (isset($headers['content-type']) && 
                     strpos($headers['content-type'], 'application/x-www-form-urlencoded') !== false) {
                parse_str($body, $postData);
            }
        }
        
        return [
            'method' => $method,
            'uri' => $uri,
            'version' => $version,
            'headers' => $headers,
            'content' => $body,
            'data' => array_merge($queryData, $postData),
        ];
    }

    /**
     * 处理特殊请求类型
     */
    public function handleSpecialRequests(IlluminateRequest $request): ?IlluminateResponse
    {
        // 处理文件上传
        if ($this->isFileUpload($request)) {
            return $this->handleFileUpload($request);
        }
        
        // 处理 WebSocket 升级请求
        if ($this->isWebSocketUpgrade($request)) {
            return $this->handleWebSocketUpgrade($request);
        }
        
        // 处理 SSE (Server-Sent Events)
        if ($this->isSSERequest($request)) {
            return $this->handleSSERequest($request);
        }
        
        return null;
    }

    protected function isFileUpload(IlluminateRequest $request): bool
    {
        return $request->hasFile('file') || $request->hasFile('files');
    }

    protected function handleFileUpload(IlluminateRequest $request): IlluminateResponse
    {
        // 自定义文件上传处理逻辑
        $files = $request->allFiles();
        
        foreach ($files as $key => $file) {
            if (is_array($file)) {
                foreach ($file as $singleFile) {
                    $this->processUploadedFile($singleFile);
                }
            } else {
                $this->processUploadedFile($file);
            }
        }
        
        return response()->json([
            'success' => true,
            'message' => '文件上传成功',
            'files' => array_keys($files),
        ]);
    }

    protected function processUploadedFile($file): void
    {
        if ($file->isValid()) {
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads', $filename, 'public');
            
            $this->logger->info('File uploaded', [
                'original_name' => $file->getClientOriginalName(),
                'stored_path' => $path,
                'size' => $file->getSize(),
            ]);
        }
    }

    protected function isWebSocketUpgrade(IlluminateRequest $request): bool
    {
        return $request->header('upgrade') === 'websocket';
    }

    protected function handleWebSocketUpgrade(IlluminateRequest $request): IlluminateResponse
    {
        // WebSocket 升级处理
        return response('', 101, [
            'Upgrade' => 'websocket',
            'Connection' => 'Upgrade',
            'Sec-WebSocket-Accept' => base64_encode(
                pack('H*', sha1($request->header('sec-websocket-key') . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11'))
            ),
        ]);
    }

    protected function isSSERequest(IlluminateRequest $request): bool
    {
        return $request->header('accept') === 'text/event-stream';
    }

    protected function handleSSERequest(IlluminateRequest $request): IlluminateResponse
    {
        return response()->stream(function () {
            while (true) {
                echo "data: " . json_encode(['time' => now()]) . "\n\n";
                ob_flush();
                flush();
                sleep(1);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
```

### 2. 注册自定义适配器

```php
<?php
// src/WebmanFilamentServiceProvider.php

namespace WebmanFilament;

use Illuminate\Support\ServiceProvider;
use WebmanFilament\Adapter\CustomAdapter;

class WebmanFilamentServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // 注册自定义适配器
        $this->app->singleton(CustomAdapter::class, function ($app) {
            return new CustomAdapter(config('filament.adapters.custom', []));
        });
        
        // 绑定适配器到容器
        $this->app->when(FilamentBridge::class)
                  ->needs(CustomAdapter::class)
                  ->give(function () {
                      return $this->app->make(CustomAdapter::class);
                  });
    }
    
    public function boot(): void
    {
        // 注册适配器到桥接器
        $this->app->make(FilamentBridge::class)
                  ->registerAdapter('custom', $this->app->make(CustomAdapter::class));
    }
}
```

## 自定义桥接器

### 1. 扩展现有桥接器

```php
<?php
// src/Bridge/ExtendedFilamentBridge.php

namespace WebmanFilament\Bridge;

use Workerman\Worker;
use Illuminate\Support\Facades\Log;

class ExtendedFilamentBridge extends FilamentBridge
{
    protected array $customHandlers = [];
    protected array $eventListeners = [];
    
    /**
     * 注册自定义事件处理器
     */
    public function registerEventListener(string $event, callable $handler): void
    {
        $this->eventListeners[$event][] = $handler;
    }
    
    /**
     * 处理自定义事件
     */
    protected function triggerCustomEvent(string $event, array $data = []): void
    {
        if (isset($this->eventListeners[$event])) {
            foreach ($this->eventListeners[$event] as $handler) {
                try {
                    $handler($data);
                } catch (\Exception $e) {
                    Log::error("Custom event handler failed: {$event}", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                    ]);
                }
            }
        }
    }
    
    /**
     * 扩展启动逻辑
     */
    public function onStart(Worker $worker): void
    {
        parent::onStart($worker);
        
        // 触发自定义启动事件
        $this->triggerCustomEvent('start', ['worker' => $worker]);
        
        // 初始化自定义组件
        $this->initializeCustomComponents();
        
        // 设置定时任务
        $this->setupScheduledTasks();
    }
    
    /**
     * 扩展重载逻辑
     */
    public function onReload(Worker $worker): void
    {
        parent::onReload($worker);
        
        $this->triggerCustomEvent('reload', ['worker' => $worker]);
        
        // 清理缓存
        $this->clearCustomCache();
        
        // 重新初始化组件
        $this->reinitializeComponents();
    }
    
    /**
     * 扩展停止逻辑
     */
    public function onStop(Worker $worker): void
    {
        $this->triggerCustomEvent('stop', ['worker' => $worker]);
        
        // 保存状态
        $this->saveApplicationState();
        
        parent::onStop($worker);
    }
    
    /**
     * 初始化自定义组件
     */
    protected function initializeCustomComponents(): void
    {
        // 初始化缓存系统
        $this->initializeCache();
        
        // 初始化监控系统
        $this->initializeMonitoring();
        
        // 初始化队列系统
        $this->initializeQueue();
    }
    
    /**
     * 设置定时任务
     */
    protected function setupScheduledTasks(): void
    {
        // 定期清理过期会话
        \Workerman\Lib\Timer::add(300, function () {
            $this->cleanupExpiredSessions();
        });
        
        // 定期更新统计数据
        \Workerman\Lib\Timer::add(60, function () {
            $this->updateStatistics();
        });
        
        // 定期备份数据
        \Workerman\Lib\Timer::add(3600, function () {
            $this->performBackup();
        });
    }
    
    /**
     * 清理过期会话
     */
    protected function cleanupExpiredSessions(): void
    {
        try {
            // 清理 Filament 会话
            $this->app->make('session')->forgetExpired();
            
            // 清理自定义缓存
            $this->app->make('cache')->flushStaleItems();
            
            Log::info('Expired sessions cleaned up');
        } catch (\Exception $e) {
            Log::error('Failed to cleanup expired sessions', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 更新统计数据
     */
    protected function updateStatistics(): void
    {
        try {
            $stats = [
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true),
                'active_connections' => $this->getActiveConnectionCount(),
                'active_sessions' => $this->getActiveSessionCount(),
                'cache_hits' => $this->getCacheHitCount(),
            ];
            
            // 存储统计数据
            $this->app->make('cache')->put('app_statistics', $stats, 300);
            
        } catch (\Exception $e) {
            Log::error('Failed to update statistics', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 执行数据备份
     */
    protected function performBackup(): void
    {
        try {
            $backupPath = storage_path('backups/' . date('Y-m-d-H-i-s') . '.sql');
            
            // 执行数据库备份
            $command = sprintf(
                'mysqldump -h%s -u%s -p%s %s > %s',
                config('database.connections.mysql.host'),
                config('database.connections.mysql.username'),
                config('database.connections.mysql.password'),
                config('database.connections.mysql.database'),
                $backupPath
            );
            
            exec($command);
            
            // 清理旧备份（保留最近7天）
            $this->cleanupOldBackups();
            
            Log::info('Database backup completed', ['path' => $backupPath]);
            
        } catch (\Exception $e) {
            Log::error('Failed to perform backup', [
                'error' => $e->getMessage(),
            ]);
        }
    }
    
    /**
     * 清理旧备份
     */
    protected function cleanupOldBackups(): void
    {
        $backupDir = storage_path('backups');
        $files = glob($backupDir . '/*.sql');
        
        if ($files) {
            $cutoffTime = time() - (7 * 24 * 60 * 60); // 7天前
            
            foreach ($files as $file) {
                if (filemtime($file) < $cutoffTime) {
                    unlink($file);
                    Log::info('Old backup removed', ['file' => $file]);
                }
            }
        }
    }
    
    /**
     * 获取活跃连接数
     */
    protected function getActiveConnectionCount(): int
    {
        // 实现活跃连接数统计逻辑
        return 0;
    }
    
    /**
     * 获取活跃会话数
     */
    protected function getActiveSessionCount(): int
    {
        return $this->app->make('session')->count();
    }
    
    /**
     * 获取缓存命中数
     */
    protected function getCacheHitCount(): int
    {
        return $this->app->make('cache')->getStore()->get('cache_hits', 0);
    }
}
```

### 2. 使用自定义桥接器

```php
<?php
// config/filament.php

return [
    'bridge' => [
        'class' => \WebmanFilament\Bridge\ExtendedFilamentBridge::class,
        'config' => [
            'enable_monitoring' => true,
            'enable_scheduled_tasks' => true,
            'backup_enabled' => true,
            'session_timeout' => 3600,
        ],
    ],
    
    // ... 其他配置
];
```

## 自定义中间件

### 1. 创建高级认证中间件

```php
<?php
// src/Middleware/AdvancedAuthMiddleware.php

namespace WebmanFilament\Support\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdvancedAuthMiddleware
{
    protected array $config;
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    /**
     * 处理请求
     */
    public function handle(Request $request, Closure $next, string $guard = null): Response
    {
        // 检查是否需要跳过认证
        if ($this->shouldSkipAuth($request)) {
            return $next($request);
        }
        
        // 基础认证检查
        if (!Auth::guard($guard)->check()) {
            return $this->unauthorizedResponse($request);
        }
        
        $user = Auth::guard($guard)->user();
        
        // 检查用户状态
        if (!$this->isUserActive($user)) {
            return $this->inactiveUserResponse($request, $user);
        }
        
        // 检查权限
        if (!$this->hasPermission($request, $user)) {
            return $this->forbiddenResponse($request, $user);
        }
        
        // 检查 IP 白名单
        if (!$this->isIpAllowed($request, $user)) {
            return $this->ipBlockedResponse($request, $user);
        }
        
        // 检查设备信任
        if (!$this->isDeviceTrusted($request, $user)) {
            return $this->deviceNotTrustedResponse($request, $user);
        }
        
        // 记录访问日志
        $this->logAccess($request, $user);
        
        // 更新用户最后活动时间
        $this->updateLastActivity($user);
        
        // 设置用户上下文
        $this->setUserContext($request, $user);
        
        return $next($request);
    }
    
    /**
     * 检查是否需要跳过认证
     */
    protected function shouldSkipAuth(Request $request): bool
    {
        $skipPaths = $this->config['skip_paths'] ?? [
            'login',
            'logout',
            'password.request',
            'password.email',
            'password.reset',
        ];
        
        $currentPath = $request->path();
        
        foreach ($skipPaths as $path) {
            if (str_starts_with($currentPath, $path)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检查用户是否激活
     */
    protected function isUserActive($user): bool
    {
        if (!method_exists($user, 'isActive')) {
            return true;
        }
        
        return $user->isActive();
    }
    
    /**
     * 检查用户权限
     */
    protected function hasPermission(Request $request, $user): bool
    {
        $currentRoute = $request->route();
        if (!$currentRoute) {
            return true;
        }
        
        $action = $currentRoute->getAction();
        $controller = $action['controller'] ?? null;
        
        // 检查方法级别的权限
        if (isset($action['permission'])) {
            return $user->can($action['permission']);
        }
        
        // 检查控制器级别的权限
        if ($controller && is_array($controller)) {
            $class = $controller[0];
            $method = $controller[1];
            
            $permission = $this->getPermissionFromController($class, $method);
            if ($permission) {
                return $user->can($permission);
            }
        }
        
        return true;
    }
    
    /**
     * 从控制器方法获取权限
     */
    protected function getPermissionFromController(string $class, string $method): ?string
    {
        $reflection = new \ReflectionClass($class);
        $reflectionMethod = $reflection->getMethod($method);
        
        $docComment = $reflectionMethod->getDocComment();
        
        if (preg_match('/@permission\s+(.+)/', $docComment, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }
    
    /**
     * 检查 IP 是否在白名单中
     */
    protected function isIpAllowed(Request $request, $user): bool
    {
        if (!$this->config['enable_ip_whitelist'] ?? false) {
            return true;
        }
        
        $userIp = $request->ip();
        $allowedIps = $user->allowed_ips ?? [];
        
        // 管理员可以访问任何 IP
        if ($user->hasRole('admin')) {
            return true;
        }
        
        // 检查 IP 白名单
        foreach ($allowedIps as $allowedIp) {
            if ($this->ipMatchesPattern($userIp, $allowedIp)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * IP 匹配模式检查
     */
    protected function ipMatchesPattern(string $ip, string $pattern): bool
    {
        // 精确匹配
        if ($ip === $pattern) {
            return true;
        }
        
        // CIDR 匹配
        if (strpos($pattern, '/') !== false) {
            list($subnet, $mask) = explode('/', $pattern);
            return (ip2long($ip) & ~((1 << (32 - $mask)) - 1)) === ip2long($subnet);
        }
        
        // 通配符匹配
        $pattern = str_replace('*', '.*', $pattern);
        return preg_match('/^' . $pattern . '$/', $ip);
    }
    
    /**
     * 检查设备信任
     */
    protected function isDeviceTrusted(Request $request, $user): bool
    {
        if (!$this->config['enable_device_trust'] ?? false) {
            return true;
        }
        
        $deviceId = $request->header('X-Device-ID');
        if (!$deviceId) {
            return false;
        }
        
        $trustedDevices = $user->trusted_devices ?? [];
        return in_array($deviceId, $trustedDevices);
    }
    
    /**
     * 记录访问日志
     */
    protected function logAccess(Request $request, $user): void
    {
        Log::channel('access')->info('User access', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'path' => $request->path(),
            'method' => $request->method(),
            'timestamp' => now(),
        ]);
    }
    
    /**
     * 更新用户最后活动时间
     */
    protected function updateLastActivity($user): void
    {
        if (method_exists($user, 'updateLastActivity')) {
            $user->updateLastActivity();
        }
    }
    
    /**
     * 设置用户上下文
     */
    protected function setUserContext(Request $request, $user): void
    {
        // 设置用户上下文到请求属性
        $request->attributes->set('current_user', $user);
        $request->attributes->set('user_permissions', $user->getAllPermissions()->pluck('name')->toArray());
        $request->attributes->set('user_roles', $user->getRoleNames()->toArray());
    }
    
    /**
     * 未授权响应
     */
    protected function unauthorizedResponse(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => '未授权访问',
                'error' => 'UNAUTHORIZED',
            ], 401);
        }
        
        return redirect()->guest(route('filament.auth.login'));
    }
    
    /**
     * 用户未激活响应
     */
    protected function inactiveUserResponse(Request $request, $user): Response
    {
        Auth::logout();
        
        if ($request->expectsJson()) {
            return response()->json([
                'message' => '账户已被禁用',
                'error' => 'ACCOUNT_DISABLED',
            ], 403);
        }
        
        return redirect()->route('filament.auth.login')
                        ->withErrors(['account' => '您的账户已被禁用，请联系管理员。']);
    }
    
    /**
     * 权限不足响应
     */
    protected function forbiddenResponse(Request $request, $user): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => '权限不足',
                'error' => 'FORBIDDEN',
            ], 403);
        }
        
        abort(403, '权限不足');
    }
    
    /**
     * IP 被阻止响应
     */
    protected function ipBlockedResponse(Request $request, $user): Response
    {
        Log::warning('IP blocked access attempt', [
            'user_id' => $user->id,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
        
        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'IP地址被阻止',
                'error' => 'IP_BLOCKED',
            ], 403);
        }
        
        abort(403, '您的IP地址被阻止访问此资源');
    }
    
    /**
     * 设备不受信任响应
     */
    protected function deviceNotTrustedResponse(Request $request, $user): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => '设备不受信任',
                'error' => 'DEVICE_NOT_TRUSTED',
                'action' => 'trust_device',
            ], 403);
        }
        
        return redirect()->route('filament.auth.device-trust')
                        ->with(['device_id' => $request->header('X-Device-ID')]);
    }
}
```

### 2. 创建性能监控中间件

```php
<?php
// src/Middleware/PerformanceMonitoringMiddleware.php

namespace WebmanFilament\Support\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoringMiddleware
{
    protected array $config;
    protected array $metrics = [];
    
    public function __construct(array $config = [])
    {
        $this->config = $config;
    }
    
    public function handle(Request $request, Closure $next): Response
    {
        // 开始计时
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // 记录查询次数
        $queryCount = 0;
        DB::listen(function ($query) use (&$queryCount) {
            $queryCount++;
            
            // 记录慢查询
            if ($query->time > 100) { // 超过100ms的查询
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                ]);
            }
        });
        
        // 处理请求
        $response = $next($request);
        
        // 计算性能指标
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $metrics = [
            'execution_time' => ($endTime - $startTime) * 1000, // 转换为毫秒
            'memory_usage' => $endMemory - $startMemory,
            'peak_memory' => memory_get_peak_usage(),
            'query_count' => $queryCount,
            'response_size' => strlen($response->getContent()),
        ];
        
        // 检查是否需要记录
        if ($this->shouldLogMetrics($metrics)) {
            $this->logMetrics($request, $metrics);
        }
        
        // 添加性能头信息
        $this->addPerformanceHeaders($response, $metrics);
        
        // 存储指标到缓存（用于仪表板显示）
        $this->storeMetrics($metrics);
        
        return $response;
    }
    
    protected function shouldLogMetrics(array $metrics): bool
    {
        $threshold = $this->config['log_threshold'] ?? 1000; // 1秒
        
        return $metrics['execution_time'] > $threshold ||
               $metrics['memory_usage'] > 10 * 1024 * 1024 || // 10MB
               $metrics['query_count'] > 50;
    }
    
    protected function logMetrics(Request $request, array $metrics): void
    {
        Log::channel('performance')->info('Performance metrics', [
            'method' => $request->method(),
            'path' => $request->path(),
            'user_id' => auth()->id(),
            'execution_time' => round($metrics['execution_time'], 2) . 'ms',
            'memory_usage' => $this->formatBytes($metrics['memory_usage']),
            'peak_memory' => $this->formatBytes($metrics['peak_memory']),
            'query_count' => $metrics['query_count'],
            'response_size' => $this->formatBytes($metrics['response_size']),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
        ]);
    }
    
    protected function addPerformanceHeaders(Response $response, array $metrics): void
    {
        if ($this->config['add_headers'] ?? true) {
            $response->headers->set('X-Execution-Time', round($metrics['execution_time'], 2) . 'ms');
            $response->headers->set('X-Memory-Usage', $this->formatBytes($metrics['memory_usage']));
            $response->headers->set('X-Query-Count', (string) $metrics['query_count']);
        }
    }
    
    protected function storeMetrics(array $metrics): void
    {
        $cacheKey = 'performance_metrics_' . date('Y-m-d-H');
        
        $existingMetrics = \Illuminate\Support\Facades\Cache::get($cacheKey, []);
        $existingMetrics[] = array_merge($metrics, [
            'timestamp' => now(),
            'route' => request()->route()?->getName(),
        ]);
        
        // 只保留最近1小时的数据
        $existingMetrics = array_slice($existingMetrics, -100);
        
        \Illuminate\Support\Facades\Cache::put($cacheKey, $existingMetrics, 3600);
    }
    
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
```

## 自定义命令

### 1. 创建维护命令

```php
<?php
// src/Command/MaintenanceCommand.php

namespace WebmanFilament\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'filament:maintenance')]
class MaintenanceCommand extends Command
{
    protected static $defaultName = 'filament:maintenance';
    protected static $defaultDescription = '执行 Filament 维护任务';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $action = $input->getOption('action');
        
        match ($action) {
            'clear-cache' => $this->clearCache($io),
            'optimize' => $this->optimizeApplication($io),
            'health-check' => $this->healthCheck($io),
            'backup' => $this->performBackup($io),
            'cleanup' => $this->cleanup($io),
            default => $this->showHelp($io),
        };
        
        return Command::SUCCESS;
    }
    
    protected function configure(): void
    {
        $this->addOption(
            'action',
            'a',
            InputOption::VALUE_REQUIRED,
            '要执行的操作 (clear-cache|optimize|health-check|backup|cleanup)',
            'help'
        );
    }
    
    protected function clearCache(SymfonyStyle $io): void
    {
        $io->title('清除 Filament 缓存');
        
        $caches = [
            'config' => '配置缓存',
            'routes' => '路由缓存',
            'views' => '视图缓存',
            'filament-assets' => 'Filament 静态资源',
            'filament-config' => 'Filament 配置',
        ];
        
        foreach ($caches as $type => $description) {
            $io->section($description);
            
            try {
                switch ($type) {
                    case 'config':
                        \Illuminate\Support\Facades\Artisan::call('config:clear');
                        break;
                    case 'routes':
                        \Illuminate\Support\Facades\Artisan::call('route:clear');
                        break;
                    case 'views':
                        \Illuminate\Support\Facades\Artisan::call('view:clear');
                        break;
                    case 'filament-assets':
                        $this->clearFilamentAssets();
                        break;
                    case 'filament-config':
                        $this->clearFilamentConfig();
                        break;
                }
                
                $io->success($description . ' 已清除');
            } catch (\Exception $e) {
                $io->error($description . ' 清除失败: ' . $e->getMessage());
            }
        }
        
        $io->success('缓存清除完成');
    }
    
    protected function optimizeApplication(SymfonyStyle $io): void
    {
        $io->title('优化应用程序');
        
        $optimizations = [
            'config:cache' => '配置缓存',
            'route:cache' => '路由缓存',
            'view:cache' => '视图缓存',
            'filament:optimize' => 'Filament 优化',
        ];
        
        foreach ($optimizations as $command => $description) {
            $io->section($description);
            
            try {
                if ($command === 'filament:optimize') {
                    $this->optimizeFilament();
                } else {
                    \Illuminate\Support\Facades\Artisan::call($command);
                }
                $io->success($description . ' 完成');
            } catch (\Exception $e) {
                $io->error($description . ' 失败: ' . $e->getMessage());
            }
        }
        
        $io->success('应用程序优化完成');
    }
    
    protected function healthCheck(SymfonyStyle $io): void
    {
        $io->title('Filament 健康检查');
        
        $checks = [
            'database' => '数据库连接',
            'cache' => '缓存系统',
            'storage' => '存储权限',
            'assets' => '静态资源',
            'extensions' => '扩展兼容性',
        ];
        
        foreach ($checks as $type => $description) {
            $io->section($description);
            
            try {
                $result = $this->performHealthCheck($type);
                
                if ($result['status'] === 'ok') {
                    $io->success($description . ' 正常');
                } else {
                    $io->warning($description . ' 警告: ' . $result['message']);
                }
            } catch (\Exception $e) {
                $io->error($description . ' 错误: ' . $e->getMessage());
            }
        }
    }
    
    protected function performHealthCheck(string $type): array
    {
        return match ($type) {
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'storage' => $this->checkStorage(),
            'assets' => $this->checkAssets(),
            'extensions' => $this->checkExtensions(),
            default => ['status' => 'unknown', 'message' => '未知的检查类型'],
        };
    }
    
    protected function checkDatabase(): array
    {
        try {
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            return ['status' => 'ok', 'message' => '数据库连接正常'];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => '数据库连接失败: ' . $e->getMessage()];
        }
    }
    
    protected function checkCache(): array
    {
        try {
            $testKey = 'health_check_' . time();
            \Illuminate\Support\Facades\Cache::put($testKey, 'test', 60);
            $value = \Illuminate\Support\Facades\Cache::get($testKey);
            \Illuminate\Support\Facades\Cache::forget($testKey);
            
            if ($value === 'test') {
                return ['status' => 'ok', 'message' => '缓存系统正常'];
            } else {
                return ['status' => 'error', 'message' => '缓存读写异常'];
            }
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => '缓存系统错误: ' . $e->getMessage()];
        }
    }
    
    protected function checkStorage(): array
    {
        $paths = [
            storage_path('app'),
            storage_path('framework'),
            storage_path('logs'),
            public_path('filament'),
        ];
        
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                return ['status' => 'error', 'message' => '目录不存在: ' . $path];
            }
            
            if (!is_writable($path)) {
                return ['status' => 'error', 'message' => '目录不可写: ' . $path];
            }
        }
        
        return ['status' => 'ok', 'message' => '存储权限正常'];
    }
    
    protected function checkAssets(): array
    {
        $requiredAssets = [
            public_path('filament/filament.css'),
            public_path('filament/filament.js'),
        ];
        
        foreach ($requiredAssets as $asset) {
            if (!file_exists($asset)) {
                return ['status' => 'error', 'message' => '静态资源缺失: ' . $asset];
            }
        }
        
        return ['status' => 'ok', 'message' => '静态资源完整'];
    }
    
    protected function checkExtensions(): array
    {
        $requiredExtensions = ['pdo', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'json', 'bcmath', 'fileinfo'];
        $missing = [];
        
        foreach ($requiredExtensions as $extension) {
            if (!extension_loaded($extension)) {
                $missing[] = $extension;
            }
        }
        
        if (empty($missing)) {
            return ['status' => 'ok', 'message' => '所有必需扩展已安装'];
        } else {
            return ['status' => 'error', 'message' => '缺少扩展: ' . implode(', ', $missing)];
        }
    }
    
    protected function optimizeFilament(): void
    {
        // 预编译 Filament 组件
        \Illuminate\Support\Facades\Artisan::call('filament:cache-components');
        
        // 优化静态资源
        $this->optimizeAssets();
        
        // 预编译配置
        \Illuminate\Support\Facades\Artisan::call('filament:cache-config');
    }
    
    protected function optimizeAssets(): void
    {
        $publicPath = public_path('filament');
        
        if (is_dir($publicPath)) {
            // 压缩 CSS 文件
            $cssFiles = glob($publicPath . '/*.css');
            foreach ($cssFiles as $file) {
                $this->minifyCSS($file);
            }
            
            // 压缩 JS 文件
            $jsFiles = glob($publicPath . '/*.js');
            foreach ($jsFiles as $file) {
                $this->minifyJS($file);
            }
        }
    }
    
    protected function minifyCSS(string $file): void
    {
        $content = file_get_contents($file);
        // 简单的 CSS 压缩
        $content = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $content);
        $content = str_replace(["\r\n", "\r", "\n", "\t", '  ', '    ', '    '], '', $content);
        file_put_contents($file, $content);
    }
    
    protected function minifyJS(string $file): void
    {
        // 这里可以集成更复杂的 JS 压缩工具
        // 目前仅做基础处理
    }
    
    protected function clearFilamentAssets(): void
    {
        $publicPath = public_path('filament');
        if (is_dir($publicPath)) {
            array_map('unlink', glob($publicPath . '/*'));
        }
    }
    
    protected function clearFilamentConfig(): void
    {
        $cachePath = storage_path('framework/cache/filament.php');
        if (file_exists($cachePath)) {
            unlink($cachePath);
        }
    }
    
    protected function showHelp(SymfonyStyle $io): void
    {
        $io->title('Filament 维护命令');
        
        $io->text([
            '可用操作:',
            '  clear-cache    清除所有缓存',
            '  optimize       优化应用程序',
            '  health-check   执行健康检查',
            '  backup         执行数据备份',
            '  cleanup        清理临时文件',
            '',
            '示例:',
            '  php artisan filament:maintenance --action=clear-cache',
            '  php artisan filament:maintenance --action=health-check',
        ]);
    }
}
```

## 自定义服务提供者

### 1. 扩展服务提供者

```php
<?php
// src/Providers/CustomWebmanFilamentServiceProvider.php

namespace WebmanFilament\Providers;

use Illuminate\Support\ServiceProvider;
use WebmanFilament\WebmanFilamentServiceProvider;
use App\Filament\CustomPanelProvider;
use App\Services\CustomFilamentService;

class CustomWebmanFilamentServiceProvider extends WebmanFilamentServiceProvider
{
    protected array $customPanelProviders = [
        'admin' => CustomPanelProvider::class,
        'api' => \App\Filament\ApiPanelProvider::class,
    ];
    
    protected array $customServices = [
        \App\Services\CustomAuthService::class,
        \App\Services\CustomCacheService::class,
        \App\Services\CustomNotificationService::class,
    ];
    
    public function register(): void
    {
        parent::register();
        
        // 注册自定义服务
        foreach ($this->customServices as $service) {
            $this->app->singleton($service);
        }
        
        // 注册自定义 Filament 服务
        $this->app->singleton(CustomFilamentService::class, function ($app) {
            return new CustomFilamentService(
                $app->make('config'),
                $app->make('cache'),
                $app->make('log')
            );
        });
    }
    
    public function boot(): void
    {
        parent::boot();
        
        // 注册自定义面板提供者
        $this->registerCustomPanelProviders();
        
        // 注册自定义事件监听器
        $this->registerEventListeners();
        
        // 注册自定义中间件
        $this->registerMiddleware();
        
        // 注册自定义命令
        $this->registerCommands();
        
        // 注册自定义路由
        $this->registerRoutes();
    }
    
    protected function registerCustomPanelProviders(): void
    {
        foreach ($this->customPanelProviders as $panelId => $providerClass) {
            $this->app->when($providerClass)
                      ->needs('$config')
                      ->give(config("filament.panels.{$panelId}", []));
        }
    }
    
    protected function registerEventListeners(): void
    {
        // 用户登录事件
        \Illuminate\Support\Facades\Auth::viaRequest('web', function ($request) {
            if (auth()->check()) {
                event(new \App\Events\UserLoggedIn(auth()->user(), $request));
            }
        });
        
        // Filament 面板初始化事件
        $this->app->make('events')->listen('filament.panel.initializing', function ($panel) {
            // 自定义面板初始化逻辑
        });
        
        // 资源创建事件
        $this->app->make('events')->listen('filament.resource.creating', function ($resource, $data) {
            // 自定义资源创建逻辑
        });
    }
    
    protected function registerMiddleware(): void
    {
        // 注册全局中间件
        $this->app['router']->pushMiddlewareToGroup('web', \WebmanFilament\Support\Middleware\AdvancedAuthMiddleware::class);
        $this->app['router']->pushMiddlewareToGroup('web', \WebmanFilament\Support\Middleware\PerformanceMonitoringMiddleware::class);
        
        // 注册路由中间件
        $this->app['router']->aliasMiddleware('filament.auth.advanced', \WebmanFilament\Support\Middleware\AdvancedAuthMiddleware::class);
        $this->app['router']->aliasMiddleware('filament.performance', \WebmanFilament\Support\Middleware\PerformanceMonitoringMiddleware::class);
    }
    
    protected function registerCommands(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                \WebmanFilament\Command\MaintenanceCommand::class,
                \App\Console\Commands\CustomFilamentCommand::class,
            ]);
        }
    }
    
    protected function registerRoutes(): void
    {
        // 注册自定义 API 路由
        $this->app['router']->group([
            'prefix' => 'api/filament',
            'middleware' => ['api', 'filament.auth.advanced'],
        ], function ($router) {
            require base_path('routes/filament-api.php');
        });
        
        // 注册 Webhook 路由
        $this->app['router']->group([
            'prefix' => 'webhooks/filament',
            'middleware' => ['api', 'filament.webhook'],
        ], function ($router) {
            require base_path('routes/filament-webhooks.php');
        });
    }
}
```

## 扩展配置文件

### 1. 自定义配置文件

```php
<?php
// config/filament-custom.php

return [
    'custom' => [
        'features' => [
            'advanced_auth' => [
                'enabled' => true,
                'ip_whitelist' => false,
                'device_trust' => true,
                'session_timeout' => 3600,
            ],
            
            'performance_monitoring' => [
                'enabled' => true,
                'log_threshold' => 1000, // 毫秒
                'add_headers' => true,
            ],
            
            'maintenance_mode' => [
                'enabled' => true,
                'allowed_ips' => [],
                'allowed_users' => [],
            ],
        ],
        
        'services' => [
            'notification' => [
                'driver' => 'database', // database, redis, array
                'channels' => ['database', 'broadcast'],
            ],
            
            'cache' => [
                'driver' => 'redis',
                'prefix' => 'filament_cache',
                'ttl' => 3600,
            ],
            
            'queue' => [
                'driver' => 'redis',
                'connection' => 'default',
                'queue' => 'filament',
            ],
        ],
        
        'ui' => [
            'theme' => [
                'primary_color' => '#3B82F6',
                'dark_mode' => true,
                'custom_css' => '',
            ],
            
            'navigation' => [
                'sort' => true,
                'collapsible' => true,
                'max_depth' => 3,
            ],
            
            'dashboard' => [
                'widgets' => [
                    'App\\Filament\\Widgets\\CustomStatsWidget',
                    'App\\Filament\\Widgets\\RecentActivityWidget',
                ],
                'refresh_interval' => 30,
            ],
        ],
        
        'security' => [
            'csrf' => [
                'enabled' => true,
                'timeout' => 7200,
            ],
            
            'rate_limiting' => [
                'enabled' => true,
                'max_requests' => 60,
                'decay_minutes' => 1,
            ],
            
            'audit_log' => [
                'enabled' => true,
                'retention_days' => 90,
            ],
        ],
    ],
];
```

## 总结

通过本指南，您已经了解了如何：

1. **创建自定义适配器** - 扩展请求响应转换逻辑
2. **开发自定义桥接器** - 管理应用生命周期
3. **实现高级中间件** - 添加认证、监控等功能
4. **创建自定义命令** - 提供维护和管理工具
5. **扩展服务提供者** - 注册自定义服务
6. **配置深度定制** - 灵活配置系统

这些自定义功能让 webman-filament 扩展能够满足各种复杂的业务需求和性能要求。

## 下一步

- 查看 [API 参考文档](api-reference.md) 了解详细 API
- 参考 [最佳实践指南](best-practices.md) 了解推荐做法
- 查看 [基础使用指南](basic-usage.md) 复习基础功能