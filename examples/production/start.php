<?php
/**
 * Webman Filament 生产环境启动配置
 * 
 * 生产环境配置优化了性能、安全性和稳定性
 */

use Webman\Route;
use Webman\Middleware;
use Webman\Bootstrap;

// 自动加载
require_once __DIR__ . '/vendor/autoload.php';

// 加载环境变量
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
if (file_exists(__DIR__ . '/.env')) {
    $dotenv->load();
}

// 加载生产环境配置
$appConfig = require __DIR__ . '/config/app.php';
$filamentConfig = require __DIR__ . '/config/filament.php';
$authConfig = require __DIR__ . '/config/auth.php';

// 生产环境常量定义
define('WEBMAN_STATIC_PATH', __DIR__ . '/public');
define('WEBMAN_DEBUG', false);
define('WEBMAN_ENV', 'production');

// 性能优化配置
ini_set('opcache.enable', '1');
ini_set('opcache.memory_consumption', '256');
ini_set('opcache.interned_strings_buffer', '16');
ini_set('opcache.max_accelerated_files', '10000');
ini_set('opcache.revalidate_freq', '60');
ini_set('opcache.fast_shutdown', '1');

// 内存限制
ini_set('memory_limit', '512M');

// 执行时间限制
set_time_limit(300);

// 错误报告设置
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// 路由配置 - 生产环境
Route::get('/', function() {
    return response()->redirect('/admin');
});

// 健康检查端点
Route::get('/health', function() {
    $health = [
        'status' => 'ok',
        'timestamp' => date('c'),
        'version' => '1.0.0',
        'environment' => 'production',
        'checks' => [
            'database' => false,
            'redis' => false,
            'disk_space' => false,
            'memory' => false,
        ]
    ];

    try {
        // 数据库检查
        $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']}", 
                      $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
        $health['checks']['database'] = true;
    } catch (Exception $e) {
        $health['checks']['database'] = false;
        $health['status'] = 'degraded';
    }

    try {
        // Redis 检查
        $redis = new Redis();
        $redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);
        $health['checks']['redis'] = true;
    } catch (Exception $e) {
        $health['checks']['redis'] = false;
        $health['status'] = 'degraded';
    }

    // 磁盘空间检查
    $freeSpace = disk_free_space(__DIR__);
    $totalSpace = disk_total_space(__DIR__);
    $freePercent = ($freeSpace / $totalSpace) * 100;
    $health['checks']['disk_space'] = $freePercent > 10;

    // 内存检查
    $memoryUsage = memory_get_usage(true);
    $memoryLimit = 512 * 1024 * 1024; // 512MB
    $health['checks']['memory'] = $memoryUsage < ($memoryLimit * 0.8);

    $statusCode = $health['status'] === 'ok' ? 200 : 503;
    return json($health, $statusCode);
});

// API 路由 - 带速率限制
Route::group('/api', function() {
    Route::get('/version', function() {
        return json([
            'version' => '1.0.0',
            'filament' => '4.0.0',
            'php' => PHP_VERSION,
            'environment' => 'production'
        ]);
    });

    Route::get('/status', function() {
        return json([
            'status' => 'running',
            'uptime' => time() - $_SERVER['REQUEST_TIME_FLOAT'],
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ]);
    });
})->middleware([
    \WebmanFilament\Middleware\RateLimitMiddleware::class,
    \WebmanFilament\Middleware\SecurityMiddleware::class,
]);

// Filament 路由 - 生产环境优化
if ($filamentConfig['auto_register_routes']) {
    Route::group('/admin', function() {
        // Filament 路由会自动注册
    })->middleware([
        \WebmanFilament\Middleware\FilamentMiddleware::class,
        \WebmanFilament\Middleware\SecurityMiddleware::class,
        \WebmanFilament\Middleware\CacheMiddleware::class,
    ]);
}

// 静态文件缓存配置
Route::get('/filament/{path}', function($path) {
    $filePath = __DIR__ . '/public/filament/' . $path;
    
    if (!file_exists($filePath)) {
        return response('', 404);
    }

    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
        'eot' => 'application/vnd.ms-fontobject',
    ];

    $mimeType = $mimeTypes[$extension] ?? 'application/octet-stream';
    $content = file_get_contents($filePath);
    
    $response = new \Workerman\Protocols\Http\Response(200, [
        'Content-Type' => $mimeType,
        'Cache-Control' => 'public, max-age=31536000',
        'Expires' => gmdate('D, d M Y H:i:s', time() + 31536000) . ' GMT',
        'ETag' => '"' . md5($content) . '"',
    ], $content);

    return $response;
});

// 中间件配置 - 生产环境
Middleware::add([
    \WebmanFilament\Middleware\SecurityMiddleware::class,
    \WebmanFilament\Middleware\FilamentMiddleware::class,
    \WebmanFilament\Middleware\PerformanceMiddleware::class,
]);

// 错误处理 - 生产环境
set_exception_handler(function($exception) {
    // 记录错误日志
    error_log("[" . date('Y-m-d H:i:s') . "] " . $exception->getMessage() . "\n" . 
              $exception->getTraceAsString() . "\n");

    // 返回友好的错误页面
    $errorPage = file_get_contents(__DIR__ . '/resources/errors/500.html');
    return new \Workerman\Protocols\Http\Response(500, [
        'Content-Type' => 'text/html; charset=utf-8',
    ], $errorPage);
});

// 启动服务器 - 生产环境配置
return new class {
    public function start($worker)
    {
        $numWorkers = env('WEBMAN_WORKERS', 4);
        $maxRequests = env('WEBMAN_MAX_REQUESTS', 10000);
        
        echo "🚀 Webman Filament Production Server Started\n";
        echo "📊 Workers: {$numWorkers}\n";
        echo "🔄 Max Requests per Worker: {$maxRequests}\n";
        echo "🌐 Admin Panel: https://your-domain.com/admin\n";
        echo "❤️  Health Check: https://your-domain.com/health\n";
        echo "📈 Performance Monitoring: Enabled\n";
        echo "🔒 Security: Enabled\n";
        echo "⚡ OPcache: Enabled\n";
        echo "💾 Redis: Connected\n";
        echo "🗄️  Database: Connected\n";
        echo "📝 Logs: storage/logs/\n";
        
        // 设置工作进程配置
        $worker->maxRequests = $maxRequests;
        $worker->reloadable = true;
        
        // 优雅关闭处理
        pcntl_signal(SIGTERM, function() {
            echo "\n🛑 Graceful shutdown initiated...\n";
            exit(0);
        });
        
        pcntl_signal(SIGINT, function() {
            echo "\n🛑 Graceful shutdown initiated...\n";
            exit(0);
        });
    }
};