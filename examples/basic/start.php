<?php
/**
 * Webman Filament 基础启动配置
 * 
 * 这是 Webman 框架的基础启动文件
 * 包含了基本的路由、中间件和静态资源配置
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

// 加载应用配置
$appConfig = require __DIR__ . '/config/app.php';
$filamentConfig = require __DIR__ . '/config/filament.php';
$authConfig = require __DIR__ . '/config/auth.php';

// 静态资源配置
define('WEBMAN_STATIC_PATH', __DIR__ . '/public');

// 路由配置
Route::get('/', function() {
    return response()->redirect('/admin');
});

// Filament 路由
if ($filamentConfig['auto_register_routes']) {
    Route::group('/admin', function() {
        // Filament 路由会自动注册
    });
}

// API 路由示例
Route::group('/api', function() {
    Route::get('/health', function() {
        return json(['status' => 'ok', 'message' => 'Webman Filament is running']);
    });
    
    Route::get('/version', function() {
        return json(['version' => '1.0.0', 'filament' => '4.0.0']);
    });
});

// 中间件配置
Middleware::add([\WebmanFilament\Middleware\FilamentMiddleware::class]);

// 错误处理
set_exception_handler(function($exception) {
    if (config('app.debug', false)) {
        echo $exception;
    } else {
        echo 'Internal Server Error';
    }
});

// 启动服务器
return new class {
    public function start($worker)
    {
        echo "Webman Filament Server Started\n";
        echo "Admin Panel: http://localhost:8787/admin\n";
        echo "API Health Check: http://localhost:8787/api/health\n";
    }
};