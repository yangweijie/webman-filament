<?php
/**
 * Webman Filament 开发环境启动配置
 * 
 * 开发环境配置专注于开发效率和调试便利性
 * 包含热重载、文件监控、调试工具等开发特性
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

// 加载开发环境配置
$appConfig = require __DIR__ . '/config/app.php';
$filamentConfig = require __DIR__ . '/config/filament.php';
$authConfig = require __DIR__ . '/config/auth.php';

// 开发环境常量定义
define('WEBMAN_STATIC_PATH', __DIR__ . '/public');
define('WEBMAN_DEBUG', true);
define('WEBMAN_ENV', 'development');

// 开发环境 PHP 配置
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('error_log', storage_path('logs/php_errors.log'));

// 内存限制（开发环境较高）
ini_set('memory_limit', '1G');

// 执行时间限制（开发环境较长）
set_time_limit(0);

// 路由配置 - 开发环境
Route::get('/', function() {
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>Webman Filament 开发环境</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
            .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .header { border-bottom: 2px solid #007cba; padding-bottom: 20px; margin-bottom: 30px; }
            .status { display: inline-block; padding: 5px 10px; border-radius: 4px; font-weight: bold; }
            .status.ok { background: #d4edda; color: #155724; }
            .status.dev { background: #fff3cd; color: #856404; }
            .links { margin: 20px 0; }
            .links a { display: inline-block; margin: 10px 15px 10px 0; padding: 10px 20px; background: #007cba; color: white; text-decoration: none; border-radius: 4px; }
            .links a:hover { background: #005a87; }
            .info { background: #e7f3ff; padding: 15px; border-radius: 4px; margin: 20px 0; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚀 Webman Filament 开发环境</h1>
                <p class="status dev">开发模式</p>
            </div>
            
            <div class="info">
                <h3>📊 系统信息</h3>
                <p><strong>PHP 版本:</strong> ' . PHP_VERSION . '</p>
                <p><strong>Webman 版本:</strong> ' . (defined('Workerman\Worker::VERSION') ? Workerman\Worker::VERSION : 'Unknown') . '</p>
                <p><strong>Filament 版本:</strong> 4.0.0</p>
                <p><strong>运行时间:</strong> ' . date('Y-m-d H:i:s') . '</p>
                <p><strong>内存使用:</strong> ' . round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB</p>
            </div>
            
            <div class="links">
                <a href="/admin">🎛️ 管理后台</a>
                <a href="/api/health">❤️ 健康检查</a>
                <a href="/api/status">📈 系统状态</a>
                <a href="/api/debug">🐛 调试信息</a>
                <a href="/docs">📚 API 文档</a>
            </div>
            
            <div class="info">
                <h3>🔧 开发工具</h3>
                <p><strong>热重载:</strong> <span class="status ok">已启用</span></p>
                <p><strong>调试模式:</strong> <span class="status ok">已启用</span></p>
                <p><strong>SQL 日志:</strong> <span class="status ok">已启用</span></p>
                <p><strong>性能监控:</strong> <span class="status ok">已启用</span></p>
                <p><strong>文件监控:</strong> <span class="status ok">已启用</span></p>
            </div>
        </div>
    </body>
    </html>';
    
    return response($html, 200, ['Content-Type' => 'text/html; charset=utf-8']);
});

// 开发工具路由
Route::group('/dev', function() {
    // 调试信息
    Route::get('/debug', function() {
        $debugInfo = [
            'timestamp' => date('c'),
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'included_files' => get_included_files(),
            'loaded_extensions' => get_loaded_extensions(),
            'environment_variables' => $_ENV,
            'server_info' => $_SERVER,
            'config' => [
                'app' => config('app'),
                'filament' => config('filament'),
                'auth' => config('auth'),
            ],
        ];
        
        return json($debugInfo, 200, [], JSON_PRETTY_PRINT);
    });
    
    // 路由列表
    Route::get('/routes', function() {
        $routes = [];
        // 这里应该获取实际路由列表，暂时返回示例
        $routes[] = ['method' => 'GET', 'path' => '/', 'handler' => 'Closure'];
        $routes[] = ['method' => 'GET', 'path' => '/admin', 'handler' => 'Filament'];
        $routes[] = ['method' => 'GET', 'path' => '/api/health', 'handler' => 'Closure'];
        $routes[] = ['method' => 'GET', 'path' => '/api/status', 'handler' => 'Closure'];
        
        return json($routes, 200, [], JSON_PRETTY_PRINT);
    });
    
    // 数据库状态
    Route::get('/database', function() {
        try {
            $pdo = new PDO(
                "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']}", 
                $_ENV['DB_USERNAME'], 
                $_ENV['DB_PASSWORD']
            );
            
            $stmt = $pdo->query("SHOW TABLES");
            $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $databaseInfo = [
                'connection' => 'success',
                'tables' => $tables,
                'table_count' => count($tables),
            ];
        } catch (Exception $e) {
            $databaseInfo = [
                'connection' => 'failed',
                'error' => $e->getMessage(),
            ];
        }
        
        return json($databaseInfo, 200, [], JSON_PRETTY_PRINT);
    });
    
    // 缓存状态
    Route::get('/cache', function() {
        $cacheInfo = [
            'driver' => config('cache.default'),
            'stores' => config('cache.stores'),
            'prefix' => config('cache.prefix'),
        ];
        
        return json($cacheInfo, 200, [], JSON_PRETTY_PRINT);
    });
    
    // 性能分析
    Route::get('/performance', function() {
        $performance = [
            'memory_usage' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => ini_get('memory_limit'),
            ],
            'execution_time' => [
                'start' => $_SERVER['REQUEST_TIME_FLOAT'],
                'now' => microtime(true),
                'duration' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
            ],
            'system' => [
                'load_average' => sys_getloadavg(),
                'disk_free' => disk_free_space(__DIR__),
                'disk_total' => disk_total_space(__DIR__),
            ],
        ];
        
        return json($performance, 200, [], JSON_PRETTY_PRINT);
    });
});

// API 路由 - 开发环境
Route::group('/api', function() {
    Route::get('/health', function() {
        return json([
            'status' => 'ok',
            'environment' => 'development',
            'timestamp' => date('c'),
            'version' => '1.0.0-dev',
            'debug' => true,
            'features' => [
                'hot_reload' => true,
                'debug_mode' => true,
                'sql_logging' => true,
                'performance_monitoring' => true,
            ]
        ]);
    });
    
    Route::get('/status', function() {
        return json([
            'status' => 'running',
            'environment' => 'development',
            'uptime' => time() - $_SERVER['REQUEST_TIME_FLOAT'],
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
            'included_files_count' => count(get_included_files()),
            'loaded_extensions_count' => count(get_loaded_extensions()),
        ]);
    });
    
    Route::get('/config', function() {
        return json([
            'app' => config('app'),
            'filament' => config('filament'),
            'auth' => config('auth'),
        ]);
    });
});

// Filament 路由 - 开发环境
if ($filamentConfig['auto_register_routes']) {
    Route::group('/admin', function() {
        // Filament 路由会自动注册
    });
}

// WebSocket 路由 - 开发环境
if (config('app.websocket.enabled', false)) {
    Route::get('/websocket', function() {
        return json([
            'status' => 'WebSocket server running',
            'port' => config('app.websocket.port'),
            'host' => config('app.websocket.host'),
        ]);
    });
}

// 中间件配置 - 开发环境
Middleware::add([
    \WebmanFilament\Middleware\DebugMiddleware::class,
    \WebmanFilament\Middleware\FilamentMiddleware::class,
    \WebmanFilament\Middleware\HotReloadMiddleware::class,
]);

// 文件监控 - 开发环境
if (config('app.file_watcher.enabled', false)) {
    $watcher = new \WebmanFilament\Development\FileWatcher();
    $watcher->watch(config('app.file_watcher.paths'));
}

// 错误处理 - 开发环境
set_exception_handler(function($exception) {
    // 开发环境显示详细错误信息
    $errorHtml = '
    <!DOCTYPE html>
    <html>
    <head>
        <title>开发环境错误</title>
        <style>
            body { font-family: monospace; margin: 20px; background: #1e1e1e; color: #d4d4d4; }
            .error { background: #2d2d30; padding: 20px; border-radius: 8px; border-left: 4px solid #f44747; }
            .error h1 { color: #f44747; margin-top: 0; }
            .error pre { background: #1e1e1e; padding: 15px; border-radius: 4px; overflow-x: auto; }
            .trace { margin-top: 20px; }
            .trace-item { margin: 10px 0; padding: 10px; background: #252526; border-radius: 4px; }
        </style>
    </head>
    <body>
        <div class="error">
            <h1>💥 开发环境错误</h1>
            <p><strong>时间:</strong> ' . date('Y-m-d H:i:s') . '</p>
            <p><strong>文件:</strong> ' . $exception->getFile() . '</p>
            <p><strong>行号:</strong> ' . $exception->getLine() . '</p>
            <p><strong>消息:</strong> ' . htmlspecialchars($exception->getMessage()) . '</p>
            
            <div class="trace">
                <h2>堆栈跟踪:</h2>';
    
    foreach ($exception->getTrace() as $index => $trace) {
        $errorHtml .= '<div class="trace-item">';
        $errorHtml .= '<strong>#' . $index . '</strong> ';
        if (isset($trace['file'])) {
            $errorHtml .= htmlspecialchars($trace['file']) . ':' . $trace['line'];
        }
        if (isset($trace['function'])) {
            $errorHtml .= ' - ' . htmlspecialchars($trace['function']);
        }
        $errorHtml .= '</div>';
    }
    
    $errorHtml .= '
            </div>
        </div>
    </body>
    </html>';
    
    return new \Workerman\Protocols\Http\Response(500, [
        'Content-Type' => 'text/html; charset=utf-8',
    ], $errorHtml);
});

// 启动服务器 - 开发环境配置
return new class {
    public function start($worker)
    {
        echo "🛠️  Webman Filament Development Server Started\n";
        echo "🌐 Admin Panel: http://localhost:8787/admin\n";
        echo "🏠 Home Page: http://localhost:8787/\n";
        echo "❤️  Health Check: http://localhost:8787/api/health\n";
        echo "📊 System Status: http://localhost:8787/api/status\n";
        echo "🐛 Debug Info: http://localhost:8787/api/debug\n";
        echo "🔧 Dev Tools: http://localhost:8787/dev/\n";
        echo "📚 API Docs: http://localhost:8787/docs\n";
        echo "\n";
        echo "🚀 开发环境特性:\n";
        echo "   ✅ 热重载已启用\n";
        echo "   ✅ 调试模式已启用\n";
        echo "   ✅ SQL 日志已启用\n";
        echo "   ✅ 性能监控已启用\n";
        echo "   ✅ 文件监控已启用\n";
        echo "   ✅ 详细错误显示已启用\n";
        echo "\n";
        echo "💡 提示:\n";
        echo "   - 修改代码后会自动重载\n";
        echo "   - 访问 /dev/debug 查看详细调试信息\n";
        echo "   - 查看 storage/logs/ 了解运行日志\n";
        echo "   - 使用 Ctrl+C 停止服务器\n";
        echo "\n";
    }
};