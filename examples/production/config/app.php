<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 生产环境应用配置
    |--------------------------------------------------------------------------
    |
    | 生产环境下的应用配置，专注于性能、安全和稳定性
    |
    */

    'name' => env('APP_NAME', 'Webman Filament'),

    'env' => 'production',

    'debug' => false,

    'url' => env('APP_URL', 'https://your-domain.com'),

    'timezone' => 'Asia/Shanghai',

    'locale' => 'zh_CN',

    'fallback_locale' => 'en',

    'faker_locale' => 'en_US',

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],

    /*
    |--------------------------------------------------------------------------
    | 生产环境服务提供者
    |--------------------------------------------------------------------------
    |
    | 生产环境下只加载必要的服务提供者
    |
    */

    'providers' => [
        // Laravel Framework Service Providers...
        Illuminate\Auth\AuthServiceProvider::class,
        Illuminate\Broadcasting\BroadcastServiceProvider::class,
        Illuminate\Bus\BusServiceProvider::class,
        Illuminate\Cache\CacheServiceProvider::class,
        Illuminate\Foundation\Providers\ConsoleSupportServiceProvider::class,
        Illuminate\Cookie\CookieServiceProvider::class,
        Illuminate\Database\DatabaseServiceProvider::class,
        Illuminate\Encryption\EncryptionServiceProvider::class,
        Illuminate\Filesystem\FilesystemServiceProvider::class,
        Illuminate\Foundation\Providers\FoundationServiceProvider::class,
        Illuminate\Hashing\HashServiceProvider::class,
        Illuminate\Mail\MailServiceProvider::class,
        Illuminate\Notifications\NotificationServiceProvider::class,
        Illuminate\Pagination\PaginationServiceProvider::class,
        Illuminate\Pipeline\PipelineServiceProvider::class,
        Illuminate\Queue\QueueServiceProvider::class,
        Illuminate\Redis\RedisServiceProvider::class,
        Illuminate\Auth\Passwords\PasswordResetServiceProvider::class,
        Illuminate\Session\SessionServiceProvider::class,
        Illuminate\Translation\TranslationServiceProvider::class,
        Illuminate\Validation\ValidationServiceProvider::class,
        Illuminate\View\ViewServiceProvider::class,

        // Filament Service Providers...
        Filament\FilamentServiceProvider::class,
        Filament\LivewireServiceProvider::class,

        // Webman Filament Service Provider
        WebmanFilament\WebmanFilamentServiceProvider::class,

        // Application Service Providers...
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 类别名
    |--------------------------------------------------------------------------
    |
    | 生产环境下使用缓存的别名映射
    |
    */

    'aliases' => [
        'App' => Illuminate\Support\Facades\App::class,
        'Arr' => Illuminate\Support\Arr::class,
        'Artisan' => Illuminate\Support\Facades\Artisan::class,
        'Auth' => Illuminate\Support\Facades\Auth::class,
        'Blade' => Illuminate\Support\Facades\Blade::class,
        'Broadcast' => Illuminate\Support\Facades\Broadcast::class,
        'Bus' => Illuminate\Support\Facades\Bus::class,
        'Cache' => Illuminate\Support\Facades\Cache::class,
        'Config' => Illuminate\Support\Facades\Config::class,
        'Cookie' => Illuminate\Support\Facades\Cookie::class,
        'Crypt' => Illuminate\Support\Facades\Crypt::class,
        'Date' => Illuminate\Support\Facades\Date::class,
        'DB' => Illuminate\Support\Facades\DB::class,
        'Eloquent' => Illuminate\Database\Eloquent\Model::class,
        'Event' => Illuminate\Support\Facades\Event::class,
        'File' => Illuminate\Support\Facades\File::class,
        'Gate' => Illuminate\Support\Facades\Gate::class,
        'Hash' => Illuminate\Support\Facades\Hash::class,
        'Http' => Illuminate\Support\Facades\Http::class,
        'Lang' => Illuminate\Support\Facades\Lang::class,
        'Log' => Illuminate\Support\Facades\Log::class,
        'Mail' => Illuminate\Support\Facades\Mail::class,
        'Notification' => Illuminate\Support\Facades\Notification::class,
        'Password' => Illuminate\Support\Facades\Password::class,
        'Queue' => Illuminate\Support\Facades\Queue::class,
        'RateLimiter' => Illuminate\Support\Facades\RateLimiter::class,
        'Redirect' => Illuminate\Support\Facades\Redirect::class,
        'Redis' => Illuminate\Support\Facades\Redis::class,
        'Request' => Illuminate\Support\Facades\Request::class,
        'Response' => Illuminate\Support\Facades\Response::class,
        'Route' => Illuminate\Support\Facades\Route::class,
        'Schema' => Illuminate\Support\Facades\Schema::class,
        'Session' => Illuminate\Support\Facades\Session::class,
        'Storage' => Illuminate\Support\Facades\Storage::class,
        'Str' => Illuminate\Support\Str::class,
        'URL' => Illuminate\Support\Facades\URL::class,
        'Validator' => Illuminate\Support\Facades\Validator::class,
        'View' => Illuminate\Support\Facades\View::class,

        // Filament Facades
        'Filament' => Filament\Facades\Filament::class,
        'Panel' => Filament\Facades\Panel::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 生产环境优化配置
    |--------------------------------------------------------------------------
    */

    'optimize' => [
        'config' => true,
        'routes' => true,
        'views' => true,
        'events' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 缓存配置
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'config' => env('CACHE_CONFIG_ENABLED', true),
        'routes' => env('CACHE_ROUTES_ENABLED', true),
        'views' => env('CACHE_VIEWS_ENABLED', true),
        'events' => env('CACHE_EVENTS_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 性能监控
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'enabled' => env('PERFORMANCE_MONITORING_ENABLED', true),
        'slow_query_threshold' => env('PERFORMANCE_SLOW_QUERY_THRESHOLD', 1000),
        'memory_threshold' => env('PERFORMANCE_MEMORY_THRESHOLD', 128), // MB
        'cpu_threshold' => env('PERFORMANCE_CPU_THRESHOLD', 80), // %
        'disk_threshold' => env('PERFORMANCE_DISK_THRESHOLD', 90), // %
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全配置
    |--------------------------------------------------------------------------
    */

    'security' => [
        'force_https' => env('FORCE_HTTPS', true),
        'trust_proxies' => env('TRUST_PROXIES', true),
        'trusted_proxies' => explode(',', env('TRUSTED_PROXIES', '127.0.0.1')),
        'security_headers' => env('SECURITY_HEADERS_ENABLED', true),
        'content_security_policy' => env('CONTENT_SECURITY_POLICY'),
        'x_frame_options' => env('X_FRAME_OPTIONS', 'DENY'),
        'x_content_type_options' => env('X_CONTENT_TYPE_OPTIONS', 'nosniff'),
        'x_xss_protection' => env('X_XSS_PROTECTION', '1; mode=block'),
        'strict_transport_security' => env('STRICT_TRANSPORT_SECURITY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 维护模式
    |--------------------------------------------------------------------------
    */

    'maintenance' => [
        'enabled' => env('MAINTENANCE_MODE_ENABLED', false),
        'message' => env('MAINTENANCE_MODE_MESSAGE', '系统维护中，请稍后访问'),
        'allowed_ips' => explode(',', env('MAINTENANCE_MODE_ALLOWED_IPS', '127.0.0.1')),
    ],

    /*
    |--------------------------------------------------------------------------
    | 错误报告
    |--------------------------------------------------------------------------
    */

    'error_reporting' => [
        'enabled' => env('ERROR_REPORTING_ENABLED', true),
        'notification_email' => env('ERROR_NOTIFICATION_EMAIL'),
        'slack_webhook' => env('ERROR_SLACK_WEBHOOK'),
        'sentry_dsn' => env('ERROR_SENTRY_DSN'),
    ],
];