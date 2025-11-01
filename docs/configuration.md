# 配置文档

本文档介绍Webman Filament Admin的配置选项和设置方法。

## 目录

- [基本配置](#基本配置)
- [应用配置](#应用配置)
- [服务配置](#服务配置)
- [中间件配置](#中间件配置)
- [路由配置](#路由配置)
- [文件配置](#文件配置)
- [缓存配置](#缓存配置)
- [日志配置](#日志配置)

## 基本配置

### 配置文件位置

主要配置文件位于 `config/` 目录下：

- `app.php` - 应用核心配置
- `auth.php` - 认证配置
- `filament.php` - Filament管理面板配置
- `database.php` - 数据库配置
- `cache.php` - 缓存配置
- `logging.php` - 日志配置

### 环境配置

通过 `.env` 文件进行环境特定配置：

```env
# 应用基础配置
APP_NAME="Webman Filament Admin"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8787

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webman_filament
DB_USERNAME=root
DB_PASSWORD=

# 缓存配置
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# 会话配置
SESSION_DRIVER=redis
SESSION_LIFETIME=120

# 邮件配置
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
```

## 应用配置

### 基础设置

```php
// config/app.php
return [
    'name' => env('APP_NAME', 'Webman Filament Admin'),
    'env' => env('APP_ENV', 'production'),
    'debug' => (bool) env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),
    'timezone' => 'Asia/Shanghai',
    'locale' => 'zh_CN',
    'fallback_locale' => 'en',
    'faker_locale' => 'zh_CN',
    'key' => env('APP_KEY'),
    'cipher' => 'AES-256-CBC',
    
    // 日志配置
    'log' => env('APP_LOG', 'daily'),
    'log_level' => env('APP_LOG_LEVEL', 'debug'),
    
    // 维护模式
    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],
];
```

### 最佳实践

1. **环境隔离**：开发、测试、生产环境使用不同的配置文件
2. **敏感信息**：使用环境变量存储敏感信息，如API密钥、数据库密码
3. **版本控制**：`.env` 文件不应该提交到版本控制系统
4. **默认值**：为配置项提供合理的默认值

## 服务配置

### 数据库配置

```php
// config/database.php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'unix_socket' => env('DB_SOCKET', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'prefix_indexes' => true,
            'strict' => true,
            'engine' => null,
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
            ]) : [],
        ],
    ],
];
```

### 缓存配置

```php
// config/cache.php
return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores' => [
        'apc' => [
            'driver' => 'apc',
        ],
        'array' => [
            'driver' => 'array',
            'serialize' => false,
        ],
        'database' => [
            'driver' => 'database',
            'table' => 'cache',
            'connection' => null,
        ],
        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
        ],
        'memcached' => [
            'driver' => 'memcached',
            'persistent_id' => env('MEMCACHED_PERSISTENT_ID'),
            'sasl' => [
                env('MEMCACHED_USERNAME'),
                env('MEMCACHED_PASSWORD'),
            ],
            'options' => [
                // Memcached::OPT_CONNECT_TIMEOUT => 2000,
            ],
            'servers' => [
                [
                    'host' => env('MEMCACHED_HOST', '127.0.0.1'),
                    'port' => env('MEMCACHED_PORT', 11211),
                    'weight' => 100,
                ],
            ],
        ],
        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
        ],
    ],
    'prefix' => env('CACHE_PREFIX', 'webman_filament_cache'),
];
```

## 中间件配置

### 全局中间件

```php
// config/middleware.php
return [
    'global' => [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
    ],
    
    'web' => [
        \App\Http\Middleware\EncryptCookies::class,
        \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
        \Illuminate\Session\Middleware\StartSession::class,
        \Illuminate\View\Middleware\ShareErrorsFromSession::class,
        \App\Http\Middleware\VerifyCsrfToken::class,
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
    
    'api' => [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'throttle:api',
        \Illuminate\Routing\Middleware\SubstituteBindings::class,
    ],
];
```

## 路由配置

### Web路由

```php
// config/routes.php
return [
    'web' => [
        'prefix' => '',
        'middleware' => ['web'],
        'namespaces' => [
            'admin' => 'App\\Http\\Controllers\\Admin',
        ],
    ],
    
    'api' => [
        'prefix' => 'api',
        'middleware' => ['api'],
        'namespaces' => [
            'v1' => 'App\\Http\\Controllers\\Api\\V1',
        ],
    ],
    
    'admin' => [
        'prefix' => 'admin',
        'middleware' => ['web', 'auth'],
        'namespaces' => [
            'dashboard' => 'App\\Filament\\Resources\\Dashboard',
        ],
    ],
];
```

## 文件配置

### 文件存储

```php
// config/filesystems.php
return [
    'default' => env('FILESYSTEM_DISK', 'local'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],
        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],
    ],
    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],
];
```

## 缓存配置

### 缓存策略

1. **路由缓存**：生产环境启用路由缓存
2. **配置缓存**：生产环境缓存配置
3. **视图缓存**：启用Blade模板缓存
4. **类加载缓存**：优化自动加载

```php
// 缓存优化配置
'cache' => [
    'route' => env('APP_ENV') === 'production',
    'config' => env('APP_ENV') === 'production',
    'view' => env('APP_ENV') === 'production',
    'events' => env('APP_ENV') === 'production',
    'facades' => env('APP_ENV') === 'production',
],
```

## 日志配置

### 日志设置

```php
// config/logging.php
return [
    'default' => env('LOG_CHANNEL', 'stack'),
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['single', 'daily'],
            'ignore_exceptions' => false,
        ],
        'single' => [
            'driver' => 'single',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => env('LOG_LEVEL', 'debug'),
            'days' => 14,
        ],
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => env('LOG_LEVEL', 'critical'),
        ],
        'papertrail' => [
            'driver' => 'monolog',
            'level' => env('LOG_LEVEL', 'debug'),
            'handler' => 'SyslogUdpHandler',
            'handler_with' => [
                'host' => env('PAPERTRAIL_HANDLER_HOST'),
                'port' => env('PAPERTRAIL_HANDLER_PORT'),
            ],
        ],
        'stderr' => [
            'driver' => 'monolog',
            'handler' => 'StreamHandler',
            'formatter' => env('LOG_STDERR_FORMATTER'),
            'with' => [
                'stream' => 'php://stderr',
            ],
        ],
        'syslog' => [
            'driver' => 'syslog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'errorlog' => [
            'driver' => 'errorlog',
            'level' => env('LOG_LEVEL', 'debug'),
        ],
        'null' => [
            'driver' => 'monolog',
            'handler' => 'NullHandler',
        ],
        'emergency' => [
            'path' => storage_path('logs/laravel.log'),
        ],
    ],
];
```

## 注意事项

1. **安全性**：确保敏感信息不硬编码在配置文件中
2. **性能**：生产环境启用所有缓存选项
3. **监控**：配置适当的日志级别和监控
4. **备份**：定期备份配置文件
5. **测试**：在部署前测试所有配置更改

## 故障排除

### 常见问题

1. **配置不生效**：清除配置缓存 `php artisan config:clear`
2. **权限问题**：确保文件权限正确设置
3. **环境变量**：检查 `.env` 文件语法和路径
4. **服务依赖**：确保所有依赖服务正常运行

### 调试命令

```bash
# 查看当前配置
php artisan config:show

# 缓存配置
php artisan config:cache

# 清除配置缓存
php artisan config:clear

# 查看环境变量
php artisan env:show
```