# 性能调优指南

本文档详细介绍Webman Filament Admin的性能优化策略和最佳实践。

## 目录

- [应用性能优化](#应用性能优化)
- [数据库性能调优](#数据库性能调优)
- [缓存优化](#缓存优化)
- [前端性能优化](#前端性能优化)
- [服务器配置优化](#服务器配置优化)
- [监控和分析](#监控和分析)
- [负载均衡](#负载均衡)
- [性能测试](#性能测试)

## 应用性能优化

### 框架配置优化

```php
// config/performance.php
return [
    'app' => [
        'debug' => env('APP_DEBUG', false),
        'cache' => [
            'config' => env('APP_ENV') === 'production',
            'route' => env('APP_ENV') === 'production',
            'view' => env('APP_ENV') === 'production',
            'events' => env('APP_ENV') === 'production',
            'facades' => env('APP_ENV') === 'production',
        ],
        'optimize' => [
            'autoload' => true,
            'config' => true,
            'route' => true,
            'view' => true,
        ],
    ],
    
    'queue' => [
        'default' => env('QUEUE_CONNECTION', 'redis'),
        'failed' => [
            'driver' => env('QUEUE_FAILED_DRIVER', 'database'),
            'database' => env('DB_CONNECTION', 'mysql'),
            'table' => 'failed_jobs',
        ],
        'worker' => [
            'sleep' => 3,
            'tries' => 3,
            'timeout' => 60,
            'force' => false,
            'memory' => 128,
        ],
    ],
    
    'horizon' => [
        'enabled' => env('HORIZON_ENABLED', true),
        'balance' => 'auto',
        'processes' => [
            'high' => 5,
            'default' => 3,
            'low' => 2,
        ],
        'supervisor' => [
            'min_processes' => 1,
            'max_processes' => 10,
            'balance_cooldown' => 3,
            'balance_max_shift' => 1,
        ],
    ],
];
```

### 内存优化

```php
// 内存使用优化配置
'memory' => [
    'limit' => '256M',
    'gc_probability' => 1,
    'gc_divisor' => 100,
    'gc_maxlifetime' => 1440,
    'memory_limit' => '512M',
    'max_execution_time' => 300,
    'max_input_time' => 300,
    'max_input_vars' => 3000,
    'post_max_size' => '50M',
    'upload_max_filesize' => '50M',
    'max_file_uploads' => 20,
],
```

### 自动加载优化

```php
// composer.json 优化配置
{
    "require": {
        "php": "^8.1",
        "laravel/framework": "^10.0",
        "laravel/sanctum": "^3.0",
        "spatie/laravel-permission": "^5.0",
        "laravel/horizon": "^5.0"
    },
    "require-dev": {
        "fakerphp/faker": "^1.9.1",
        "laravel/pint": "^1.0",
        "laravel/sail": "^1.0.1",
        "mockery/mockery": "^1.4.4",
        "nunomaduro/collision": "^7.0",
        "phpunit/phpunit": "^10.0",
        "spatie/laravel-ignition": "^2.0"
    },
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Tests\\": "tests/"
        }
    },
    "scripts": {
        "post-autoload-dump": [
            "Illuminate\\Foundation\\ComposerScripts::postAutoloadDump",
            "@php artisan package:discover --ansi"
        ],
        "post-update-cmd": [
            "@php artisan vendor:publish --tag=laravel-assets --ansi --force"
        ],
        "post-root-package-install": [
            "@php -r \"file_exists('.env') || copy('.env.example', '.env');\""
        ],
        "post-create-project-cmd": [
            "@php artisan key:generate --ansi"
        ],
        "optimize": [
            "@php artisan config:cache",
            "@php artisan route:cache",
            "@php artisan view:cache",
            "@php artisan event:cache"
        ],
        "optimize-clear": [
            "@php artisan config:clear",
            "@php artisan route:clear",
            "@php artisan view:clear",
            "@php artisan event:clear"
        ]
    },
    "extra": {
        "laravel": {
            "dont-discover": []
        }
    },
    "config": {
        "optimize-autoloader": true,
        "preferred-install": "dist",
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true,
            "php-http/discovery": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

## 数据库性能调优

### 查询优化

```php
// 数据库查询优化配置
'database' => [
    'optimization' => [
        'query_cache' => [
            'enabled' => true,
            'ttl' => 3600,
            'prefix' => 'db_query_cache:',
        ],
        'slow_query_log' => [
            'enabled' => true,
            'threshold' => 2.0, // 秒
            'log_file' => storage_path('logs/slow-queries.log'),
        ],
        'connection_pooling' => [
            'enabled' => true,
            'min_connections' => 5,
            'max_connections' => 25,
            'acquire_timeout' => 60,
            'idle_timeout' => 300,
        ],
        'index_optimization' => [
            'auto_analyze' => true,
            'maintenance_window' => '02:00-04:00',
            'unused_index_threshold' => 0.1,
        ],
    ],
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
            'engine' => 'InnoDB',
            'options' => extension_loaded('pdo_mysql') ? array_filter([
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                PDO::ATTR_TIMEOUT => 30,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                PDO::MYSQL_ATTR_FOUND_ROWS => true,
                PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false,
            ]) : [],
            'pool' => [
                'min_connections' => 5,
                'max_connections' => 25,
                'acquire_timeout' => 60,
                'idle_timeout' => 300,
                'wait_timeout' => 10,
            ],
        ],
    ],
],
```

### 索引优化

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseOptimizer
{
    /**
     * 分析查询性能
     */
    public function analyzeQueryPerformance()
    {
        // 启用慢查询日志
        DB::statement("SET GLOBAL slow_query_log = 'ON'");
        DB::statement("SET GLOBAL long_query_time = 2.0");
        
        // 获取慢查询
        $slowQueries = DB::select("
            SELECT 
                query_time,
                lock_time,
                rows_sent,
                rows_examined,
                sql_text
            FROM mysql.slow_log 
            ORDER BY query_time DESC 
            LIMIT 10
        ");
        
        return $slowQueries;
    }

    /**
     * 优化表
     */
    public function optimizeTable($tableName)
    {
        try {
            DB::statement("OPTIMIZE TABLE {$tableName}");
            return true;
        } catch (\Exception $e) {
            \Log::error("Table optimization failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 分析表索引
     */
    public function analyzeIndexes($tableName)
    {
        $indexes = DB::select("
            SELECT 
                INDEX_NAME,
                NON_UNIQUE,
                COLUMN_NAME,
                SEQ_IN_INDEX,
                CARDINALITY
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ?
            ORDER BY INDEX_NAME, SEQ_IN_INDEX
        ", [$tableName]);
        
        return $indexes;
    }

    /**
     * 查找未使用的索引
     */
    public function findUnusedIndexes($tableName)
    {
        $unusedIndexes = DB::select("
            SELECT 
                s.INDEX_NAME,
                s.CARDINALITY,
                s.INDEX_TYPE
            FROM INFORMATION_SCHEMA.STATISTICS s
            LEFT JOIN performance_schema.table_io_waits_summary_by_index_usage i
                ON i.OBJECT_SCHEMA = s.TABLE_SCHEMA
                AND i.OBJECT_NAME = s.TABLE_NAME
                AND i.INDEX_NAME = s.INDEX_NAME
            WHERE s.TABLE_SCHEMA = DATABASE()
            AND s.TABLE_NAME = ?
            AND s.INDEX_NAME != 'PRIMARY'
            AND i.COUNT_FETCH IS NULL
        ", [$tableName]);
        
        return $unusedIndexes;
    }

    /**
     * 重建索引
     */
    public function rebuildIndex($tableName, $indexName)
    {
        try {
            DB::statement("ALTER TABLE {$tableName} DROP INDEX {$indexName}");
            DB::statement("ALTER TABLE {$tableName} ADD INDEX {$indexName} (...)");
            return true;
        } catch (\Exception $e) {
            \Log::error("Index rebuild failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 数据库维护任务
     */
    public function maintenanceTasks()
    {
        $tables = Schema::getAllTables();
        $results = [];
        
        foreach ($tables as $table) {
            $tableName = $table->{'Tables_in_' . env('DB_DATABASE')};
            
            // 分析表
            DB::statement("ANALYZE TABLE {$tableName}");
            
            // 检查表完整性
            $checkResult = DB::select("CHECK TABLE {$tableName}");
            
            // 优化表
            $optimizeResult = DB::statement("OPTIMIZE TABLE {$tableName}");
            
            $results[$tableName] = [
                'analyzed' => true,
                'checked' => $checkResult[0]->Msg_text ?? 'OK',
                'optimized' => $optimizeResult,
            ];
        }
        
        return $results;
    }
}
```

### 查询缓存

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QueryCacheService
{
    protected $defaultTtl = 3600; // 1小时
    
    /**
     * 缓存查询结果
     */
    public function cachedQuery($query, $key, $ttl = null)
    {
        $ttl = $ttl ?? $this->defaultTtl;
        
        return Cache::remember($key, $ttl, function () use ($query) {
            return $query->get();
        });
    }
    
    /**
     * 缓存单条记录
     */
    public function cachedFind($model, $id, $key = null, $ttl = null)
    {
        $key = $key ?? "{$model}:{$id}";
        $ttl = $ttl ?? $this->defaultTtl;
        
        return Cache::remember($key, $ttl, function () use ($model, $id) {
            return $model::find($id);
        });
    }
    
    /**
     * 清除缓存
     */
    public function clearCache($pattern = null)
    {
        if ($pattern) {
            $keys = Cache::getStore()->getRedis()->keys($pattern);
            foreach ($keys as $key) {
                Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
            }
        } else {
            Cache::flush();
        }
    }
    
    /**
     * 缓存统计查询
     */
    public function cachedStats($key, $ttl = 300)
    {
        return Cache::remember($key, $ttl, function () {
            return [
                'users_count' => DB::table('users')->count(),
                'orders_count' => DB::table('orders')->count(),
                'revenue' => DB::table('orders')->sum('total_amount'),
            ];
        });
    }
}
```

## 缓存优化

### Redis配置优化

```php
// config/cache.php
return [
    'default' => env('CACHE_DRIVER', 'redis'),

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
            'lock_connection' => null,
        ],

        'file' => [
            'driver' => 'file',
            'path' => storage_path('framework/cache/data'),
            'lock_connection' => null,
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
            'lock_connection' => 'memcached',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'cache',
            'lock_connection' => 'cache:lock',
        ],

        'dynamodb' => [
            'driver' => 'dynamodb',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'table' => env('DYNAMODB_CACHE_TABLE', 'cache'),
            'endpoint' => env('DYNAMODB_ENDPOINT'),
            'attributes' => [
                'table' => [
                    'read_capacity_units' => 5,
                    'write_capacity_units' => 5,
                ],
            ],
            'lock_connection' => null,
        ],

        'octane' => [
            'driver' => 'octane',
        ],
    ],

    'prefix' => env('CACHE_PREFIX', 'webman_filament_cache'),

    // Redis连接配置
    'redis' => [
        'cluster' => env('REDIS_CLUSTER', false),
        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', 'webman_filament_'),
        ],
        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'timeout' => 5.0,
            'read_timeout' => 5.0,
            'persistent' => false,
        ],
        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'timeout' => 5.0,
            'read_timeout' => 5.0,
            'persistent' => false,
        ],
        'session' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
            'timeout' => 5.0,
            'read_timeout' => 5.0,
            'persistent' => false,
        ],
        'queue' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_QUEUE_DB', '3'),
            'timeout' => 5.0,
            'read_timeout' => 5.0,
            'persistent' => false,
        ],
        'horizon' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_HORIZON_DB', '4'),
            'timeout' => 5.0,
            'read_timeout' => 5.0,
            'persistent' => false,
        ],
    ],
];
```

### 缓存策略

```php
<?php

namespace App\Services;

class CacheStrategy
{
    /**
     * 缓存分层策略
     */
    public static function multiLayerCache($key, $callable, $layers = [])
    {
        // L1: 内存缓存 (APCu)
        if (function_exists('apcu_fetch')) {
            $value = apcu_fetch($key, $success);
            if ($success) {
                return $value;
            }
        }
        
        // L2: Redis缓存
        $value = \Cache::get($key);
        if ($value !== null) {
            // 回填L1缓存
            if (function_exists('apcu_store')) {
                apcu_store($key, $value, 300); // 5分钟
            }
            return $value;
        }
        
        // L3: 数据库查询
        $value = $callable();
        
        // 回填所有缓存层
        self::storeToAllLayers($key, $value, $layers);
        
        return $value;
    }
    
    /**
     * 存储到所有缓存层
     */
    private static function storeToAllLayers($key, $value, $layers = [])
    {
        // L1: APCu
        if (function_exists('apcu_store')) {
            apcu_store($key, $value, 300);
        }
        
        // L2: Redis
        \Cache::put($key, $value, 3600); // 1小时
        
        // L3: 数据库 (如果需要)
        if (in_array('database', $layers)) {
            // 实现数据库缓存逻辑
        }
    }
    
    /**
     * 缓存标签
     */
    public static function taggedCache($tags, $key, $callable, $ttl = 3600)
    {
        return \Cache::tags($tags)->remember($key, $ttl, $callable);
    }
    
    /**
     * 缓存失效
     */
    public static function invalidateCache($pattern)
    {
        // 清除Redis缓存
        $keys = \Cache::getStore()->getRedis()->keys($pattern);
        foreach ($keys as $key) {
            \Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
        }
        
        // 清除APCu缓存
        if (function_exists('apcu_delete')) {
            $apcuKeys = [];
            foreach ($keys as $key) {
                $apcuKeys[] = str_replace(config('cache.prefix') . ':', '', $key);
            }
            apcu_delete($apcuKeys);
        }
    }
}
```

## 前端性能优化

### 资源优化配置

```php
// config/assets.php
return [
    'versioning' => [
        'enabled' => env('ASSET_VERSIONING', true),
        'strategy' => 'hash', // hash, timestamp, query
    ],
    'compression' => [
        'css' => [
            'enabled' => env('CSS_COMPRESSION', true),
            'level' => 7,
        ],
        'js' => [
            'enabled' => env('JS_COMPRESSION', true),
            'level' => 7,
        ],
        'images' => [
            'enabled' => env('IMAGE_COMPRESSION', true),
            'quality' => 85,
            'formats' => ['webp', 'avif'],
        ],
    ],
    'cdn' => [
        'enabled' => env('CDN_ENABLED', false),
        'domain' => env('CDN_DOMAIN'),
        'https' => env('CDN_HTTPS', true),
    ],
    'preload' => [
        'enabled' => env('PRELOAD_ENABLED', true),
        'resources' => [
            'critical_css' => true,
            'critical_js' => true,
            'fonts' => true,
            'images' => true,
        ],
    ],
    'lazy_loading' => [
        'enabled' => env('LAZY_LOADING', true),
        'threshold' => '50px',
    ],
];
```

### Vite配置优化

```php
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'path';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/css/filament.css',
                'resources/js/filament.js',
            ],
            refresh: true,
        }),
    ],
    resolve: {
        alias: {
            '@': resolve(__dirname, 'resources/js'),
            '~': resolve(__dirname, 'resources'),
        },
    },
    build: {
        target: 'es2015',
        minify: 'terser',
        sourcemap: env('APP_ENV') !== 'production',
        rollupOptions: {
            output: {
                manualChunks: {
                    vendor: ['vue', 'vue-router', 'pinia'],
                    filament: ['@filament/filament', '@filament/support'],
                    utils: ['lodash', 'axios', 'dayjs'],
                },
            },
        },
        terserOptions: {
            compress: {
                drop_console: env('APP_ENV') === 'production',
                drop_debugger: env('APP_ENV') === 'production',
            },
        },
    },
    server: {
        host: '0.0.0.0',
        port: 3000,
        hmr: {
            host: 'localhost',
        },
    },
    optimizeDeps: {
        include: [
            'vue',
            'vue-router',
            'pinia',
            '@inertiajs/inertia',
            '@inertiajs/inertia-vue3',
        ],
    },
});
```

## 服务器配置优化

### Nginx配置

```nginx
# /etc/nginx/sites-available/webman-filament
server {
    listen 80;
    listen [::]:80;
    server_name example.com;
    root /var/www/webman-filament/public;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip压缩
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/javascript
        application/xml+rss
        application/json;

    # Brotli压缩 (如果支持)
    brotli on;
    brotli_comp_level 6;
    brotli_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/javascript
        application/xml+rss
        application/json;

    # 静态资源缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt|tar|gz)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        add_header Vary Accept-Encoding;
        access_log off;
    }

    # 安全配置
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # API缓存
    location /api/ {
        proxy_cache api_cache;
        proxy_cache_valid 200 302 10m;
        proxy_cache_valid 404 1m;
        proxy_cache_bypass $http_pragma;
        proxy_cache_revalidate on;
        proxy_cache_lock on;
        add_header X-Cache-Status $upstream_cache_status;
    }

    # 主应用
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        
        # 性能优化
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }

    client_max_body_size 50M;
    
    # 上传优化
    client_body_buffer_size 128k;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 4k;
    output_buffers 1 32k;
    postpone_output 1460;
}
```

### PHP-FPM配置

```ini
; /etc/php/8.2/fpm/pool.d/www.conf
[www]
user = www-data
group = www-data
listen = /var/run/php/php8.2-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; 性能调优
request_terminate_timeout = 300
rlimit_files = 131072
rlimit_core = unlimited

; 慢日志
slowlog = /var/log/php8.2-fpm-slow.log
request_slowlog_timeout = 5s

; 环境变量
env[PATH] = /usr/local/bin:/usr/bin:/bin
env[TMP] = /tmp
env[TMPDIR] = /tmp
env[TEMP] = /tmp

; PHP配置
php_admin_value[error_log] = /var/log/php8.2-fpm-error.log
php_admin_flag[log_errors] = on
php_value[session.save_handler] = files
php_value[session.save_path] = /var/lib/php/sessions
php_value[soap.wsdl_cache_dir] = /var/lib/php/wsdlcache
```

### MySQL配置

```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
# 基础配置
user = mysql
pid-file = /var/run/mysqld/mysqld.pid
socket = /var/run/mysqld/mysqld.sock
port = 3306
basedir = /usr
datadir = /var/lib/mysql
tmpdir = /tmp
lc-messages-dir = /usr/share/mysql

# 字符集
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# 性能配置
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_log_buffer_size = 8M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
innodb_open_files = 500
innodb_io_capacity = 500

# 连接配置
max_connections = 200
max_connect_errors = 1000000
connect_timeout = 60
wait_timeout = 28800
interactive_timeout = 28800

# 查询缓存
query_cache_type = 1
query_cache_size = 64M
query_cache_limit = 2M

# 临时表
tmp_table_size = 64M
max_heap_table_size = 64M

# 排序缓冲区
sort_buffer_size = 2M
read_buffer_size = 2M
read_rnd_buffer_size = 8M

# MyISAM配置
key_buffer_size = 32M

# 慢查询日志
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 2
log_queries_not_using_indexes = 1

# 二进制日志
log-bin = mysql-bin
binlog_format = ROW
expire_logs_days = 7
max_binlog_size = 100M

# 安全配置
sql_mode = STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO
```

## 监控和分析

### 性能监控配置

```php
// config/monitoring.php
return [
    'enabled' => env('MONITORING_ENABLED', true),
    'metrics' => [
        'application' => [
            'response_time' => true,
            'memory_usage' => true,
            'cpu_usage' => true,
            'error_rate' => true,
            'throughput' => true,
        ],
        'database' => [
            'query_time' => true,
            'connection_count' => true,
            'slow_queries' => true,
            'cache_hit_ratio' => true,
        ],
        'cache' => [
            'hit_ratio' => true,
            'memory_usage' => true,
            'eviction_rate' => true,
        ],
        'queue' => [
            'job_rate' => true,
            'failed_jobs' => true,
            'queue_length' => true,
            'processing_time' => true,
        ],
    ],
    'alerts' => [
        'email' => env('MONITORING_ALERT_EMAIL'),
        'slack' => env('MONITORING_ALERT_SLACK'),
        'thresholds' => [
            'response_time' => 1000, // ms
            'memory_usage' => 80, // %
            'cpu_usage' => 80, // %
            'error_rate' => 5, // %
            'database_slow_queries' => 10, // per hour
        ],
    ],
    'storage' => [
        'driver' => env('MONITORING_STORAGE_DRIVER', 'database'),
        'retention_days' => env('MONITORING_RETENTION_DAYS', 30),
    ],
];
```

### 性能分析服务

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PerformanceAnalyzer
{
    protected $metrics = [];
    
    /**
     * 开始性能测量
     */
    public function startMeasure($name)
    {
        $this->metrics[$name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
        ];
    }
    
    /**
     * 结束性能测量
     */
    public function endMeasure($name)
    {
        if (!isset($this->metrics[$name])) {
            return null;
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $duration = $endTime - $this->metrics[$name]['start_time'];
        $memoryUsed = $endMemory - $this->metrics[$name]['start_memory'];
        
        $result = [
            'duration' => $duration * 1000, // ms
            'memory_used' => $memoryUsed,
            'peak_memory' => memory_get_peak_usage(),
        ];
        
        // 记录性能指标
        $this->recordMetric($name, $result);
        
        unset($this->metrics[$name]);
        
        return $result;
    }
    
    /**
     * 记录性能指标
     */
    protected function recordMetric($name, $data)
    {
        $key = "metrics:{$name}:" . date('Y-m-d-H');
        
        $metrics = Cache::get($key, []);
        $metrics[] = array_merge($data, ['timestamp' => now()]);
        
        Cache::put($key, $metrics, 3600);
    }
    
    /**
     * 分析数据库性能
     */
    public function analyzeDatabasePerformance()
    {
        $slowQueries = DB::select("
            SELECT 
                query_time,
                lock_time,
                rows_sent,
                rows_examined,
                sql_text,
                last_seen
            FROM mysql.slow_log 
            WHERE start_time > DATE_SUB(NOW(), INTERVAL 1 HOUR)
            ORDER BY query_time DESC 
            LIMIT 20
        ");
        
        $connectionStats = DB::select("
            SHOW STATUS LIKE 'Threads%'
        ");
        
        $cacheStats = DB::select("
            SHOW STATUS LIKE 'Qcache%'
        ");
        
        return [
            'slow_queries' => $slowQueries,
            'connections' => $connectionStats,
            'cache' => $cacheStats,
        ];
    }
    
    /**
     * 分析缓存性能
     */
    public function analyzeCachePerformance()
    {
        if (!app()->environment('local')) {
            $redis = Cache::getStore()->getRedis();
            
            $info = $redis->info();
            $stats = $redis->info('stats');
            
            return [
                'memory_usage' => $info['used_memory_human'],
                'connected_clients' => $info['connected_clients'],
                'total_commands_processed' => $stats['total_commands_processed'],
                'keyspace_hits' => $stats['keyspace_hits'],
                'keyspace_misses' => $stats['keyspace_misses'],
                'hit_ratio' => $stats['keyspace_hits'] / ($stats['keyspace_hits'] + $stats['keyspace_misses']),
            ];
        }
        
        return null;
    }
    
    /**
     * 生成性能报告
     */
    public function generatePerformanceReport()
    {
        $report = [
            'timestamp' => now(),
            'application' => [
                'response_time' => $this->getAverageResponseTime(),
                'memory_usage' => memory_get_usage(true),
                'peak_memory' => memory_get_peak_usage(true),
            ],
            'database' => $this->analyzeDatabasePerformance(),
            'cache' => $this->analyzeCachePerformance(),
        ];
        
        // 发送告警
        $this->checkAlerts($report);
        
        return $report;
    }
    
    /**
     * 检查告警阈值
     */
    protected function checkAlerts($report)
    {
        $thresholds = config('monitoring.alerts.thresholds');
        
        foreach ($thresholds as $metric => $threshold) {
            $value = $this->getMetricValue($report, $metric);
            
            if ($value > $threshold) {
                $this->sendAlert($metric, $value, $threshold);
            }
        }
    }
    
    /**
     * 发送告警
     */
    protected function sendAlert($metric, $value, $threshold)
    {
        $message = "Performance Alert: {$metric} is {$value}, threshold is {$threshold}";
        
        Log::warning($message);
        
        // 发送邮件告警
        if (config('monitoring.alerts.email')) {
            // 实现邮件发送逻辑
        }
        
        // 发送Slack告警
        if (config('monitoring.alerts.slack')) {
            // 实现Slack发送逻辑
        }
    }
}
```

## 负载均衡

### 负载均衡配置

```nginx
# /etc/nginx/sites-available/load-balancer
upstream webman_backend {
    least_conn;
    server 10.0.1.10:80 max_fails=3 fail_timeout=30s;
    server 10.0.1.11:80 max_fails=3 fail_timeout=30s;
    server 10.0.1.12:80 max_fails=3 fail_timeout=30s backup;
    
    keepalive 32;
}

server {
    listen 80;
    listen [::]:80;
    server_name example.com;
    
    # 健康检查
    location /health {
        access_log off;
        return 200 "healthy\n";
        add_header Content-Type text/plain;
    }
    
    # 负载均衡
    location / {
        proxy_pass http://webman_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # 连接优化
        proxy_connect_timeout 5s;
        proxy_send_timeout 60s;
        proxy_read_timeout 60s;
        
        # 缓冲设置
        proxy_buffering on;
        proxy_buffer_size 4k;
        proxy_buffers 8 4k;
        proxy_busy_buffers_size 8k;
        
        # HTTP/1.1支持
        proxy_http_version 1.1;
        proxy_set_header Connection "";
    }
}
```

## 性能测试

### 性能测试脚本

```php
<?php

namespace Tests\Performance;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PerformanceTest
{
    protected $baseUrl;
    protected $results = [];
    
    public function __construct($baseUrl)
    {
        $this->baseUrl = $baseUrl;
    }
    
    /**
     * 压力测试
     */
    public function stressTest($concurrentUsers = 10, $duration = 60)
    {
        $startTime = microtime(true);
        $endTime = $startTime + $duration;
        $requests = 0;
        $errors = 0;
        $responseTimes = [];
        
        while (microtime(true) < $endTime) {
            $promises = [];
            
            for ($i = 0; $i < $concurrentUsers; $i++) {
                $promises[] = $this->makeRequest();
            }
            
            $responses = Http::pool(function ($pool) use ($promises) {
                foreach ($promises as $promise) {
                    $promise($pool);
                }
            });
            
            foreach ($responses as $response) {
                if ($response->failed()) {
                    $errors++;
                } else {
                    $responseTimes[] = $response->transferStats ? $response->transferStats->getTotalTime() * 1000 : 0;
                }
                $requests++;
            }
            
            usleep(100000); // 100ms延迟
        }
        
        return [
            'duration' => microtime(true) - $startTime,
            'total_requests' => $requests,
            'concurrent_users' => $concurrentUsers,
            'requests_per_second' => $requests / (microtime(true) - $startTime),
            'error_rate' => ($errors / $requests) * 100,
            'average_response_time' => array_sum($responseTimes) / count($responseTimes),
            'max_response_time' => max($responseTimes),
            'min_response_time' => min($responseTimes),
        ];
    }
    
    /**
     * 数据库性能测试
     */
    public function databasePerformanceTest()
    {
        $queries = [
            'simple_select' => function() {
                return DB::table('users')->limit(100)->get();
            },
            'complex_join' => function() {
                return DB::table('users')
                    ->join('orders', 'users.id', '=', 'orders.user_id')
                    ->select('users.name', 'orders.total')
                    ->limit(1000)
                    ->get();
            },
            'aggregation' => function() {
                return DB::table('orders')
                    ->selectRaw('user_id, COUNT(*) as order_count, SUM(total_amount) as total_revenue')
                    ->groupBy('user_id')
                    ->get();
            },
        ];
        
        $results = [];
        
        foreach ($queries as $name => $query) {
            $times = [];
            
            for ($i = 0; $i < 100; $i++) {
                $start = microtime(true);
                $query();
                $times[] = (microtime(true) - $start) * 1000;
            }
            
            $results[$name] = [
                'average' => array_sum($times) / count($times),
                'min' => min($times),
                'max' => max($times),
                'median' => $this->calculateMedian($times),
                'p95' => $this->calculatePercentile($times, 95),
                'p99' => $this->calculatePercentile($times, 99),
            ];
        }
        
        return $results;
    }
    
    /**
     * 缓存性能测试
     */
    public function cachePerformanceTest()
    {
        $testData = str_repeat('x', 1024); // 1KB数据
        
        $results = [];
        
        // 测试写入性能
        $writeTimes = [];
        for ($i = 0; $i < 1000; $i++) {
            $start = microtime(true);
            Cache::put("test_key_{$i}", $testData, 3600);
            $writeTimes[] = (microtime(true) - $start) * 1000;
        }
        
        // 测试读取性能
        $readTimes = [];
        for ($i = 0; $i < 1000; $i++) {
            $start = microtime(true);
            Cache::get("test_key_{$i}");
            $readTimes[] = (microtime(true) - $start) * 1000;
        }
        
        $results['write'] = [
            'average' => array_sum($writeTimes) / count($writeTimes),
            'min' => min($writeTimes),
            'max' => max($writeTimes),
        ];
        
        $results['read'] = [
            'average' => array_sum($readTimes) / count($readTimes),
            'min' => min($readTimes),
            'max' => max($readTimes),
        ];
        
        // 清理测试数据
        for ($i = 0; $i < 1000; $i++) {
            Cache::forget("test_key_{$i}");
        }
        
        return $results;
    }
    
    protected function makeRequest()
    {
        return function($pool) {
            $pool->get($this->baseUrl . '/api/test');
        };
    }
    
    protected function calculateMedian($values)
    {
        sort($values);
        $count = count($values);
        $middle = floor($count / 2);
        
        if ($count % 2 == 0) {
            return ($values[$middle - 1] + $values[$middle]) / 2;
        }
        
        return $values[$middle];
    }
    
    protected function calculatePercentile($values, $percentile)
    {
        sort($values);
        $count = count($values);
        $index = ($percentile / 100) * ($count - 1);
        
        if (is_float($index)) {
            $lower = floor($index);
            $upper = ceil($index);
            $weight = $index - $lower;
            
            return $values[$lower] * (1 - $weight) + $values[$upper] * $weight;
        }
        
        return $values[$index];
    }
}
```

## 最佳实践

### 1. 应用层优化

- 启用所有缓存选项
- 使用队列处理耗时任务
- 优化数据库查询
- 实施代码分割

### 2. 数据库层优化

- 合理设计索引
- 使用连接池
- 启用查询缓存
- 定期维护数据库

### 3. 缓存层优化

- 多层缓存架构
- 合理设置TTL
- 使用缓存标签
- 监控缓存命中率

### 4. 服务器层优化

- 配置负载均衡
- 启用压缩
- 优化静态资源
- 配置CDN

### 5. 监控和调优

- 实时性能监控
- 定期性能测试
- 容量规划
- 告警机制

## 故障排除

### 性能问题诊断

1. **响应时间过长**
   - 检查数据库查询
   - 分析缓存命中率
   - 查看服务器资源使用

2. **内存使用过高**
   - 检查内存泄漏
   - 优化数据结构
   - 调整PHP内存限制

3. **数据库性能问题**
   - 分析慢查询日志
   - 检查索引使用情况
   - 优化表结构

### 优化命令

```bash
# 清除所有缓存
php artisan optimize:clear

# 重新生成缓存
php artisan optimize

# 重新编译类
composer dump-autoload --optimize

# 数据库分析
php artisan tinker
DB::statement("ANALYZE TABLE users");

# 性能分析
php artisan profile:start
# 执行操作
php artisan profile:stop
```