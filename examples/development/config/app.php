<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 开发环境应用配置
    |--------------------------------------------------------------------------
    |
    | 开发环境配置，专注于开发效率和调试便利性
    |
    */

    'name' => env('APP_NAME', 'Webman Filament'),

    'env' => 'development',

    'debug' => true,

    'url' => env('APP_URL', 'http://localhost:8787'),

    'timezone' => 'Asia/Shanghai',

    'locale' => 'zh_CN',

    'fallback_locale' => 'en',

    'faker_locale' => 'zh_CN',

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    'maintenance' => [
        'driver' => 'file',
    ],

    /*
    |--------------------------------------------------------------------------
    | 开发环境服务提供者
    |--------------------------------------------------------------------------
    |
    | 包含所有必要的服务提供者，包括开发工具
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
        Filament\CommandsServiceProvider::class,

        // Webman Filament Service Provider
        WebmanFilament\WebmanFilamentServiceProvider::class,

        // Development Tools...
        Laravel\Telescope\TelescopeServiceProvider::class,
        Clockwork\Support\Laravel\ClockworkServiceProvider::class,

        // Application Service Providers...
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        // App\Providers\EventServiceProvider::class,
        // App\Providers\RouteServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 类别名
    |--------------------------------------------------------------------------
    |
    | 开发环境下的完整别名映射
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

        // Development Facades
        'Telescope' => Laravel\Telescope\Telescope::class,
        'Clockwork' => Clockwork\Support\Laravel\Facades\Clockwork::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 开发工具配置
    |--------------------------------------------------------------------------
    */

    'development_tools' => [
        'enabled' => env('DEVELOPMENT_TOOLS_ENABLED', true),
        'debug_bar' => env('DEVELOPMENT_DEBUG_BAR_ENABLED', true),
        'telescope' => env('DEVELOPMENT_TELESCOPE_ENABLED', true),
        'clockwork' => env('DEVELOPMENT_CLOCKWORK_ENABLED', true),
        'profiling' => env('PROFILING_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 文件监控配置
    |--------------------------------------------------------------------------
    */

    'file_watcher' => [
        'enabled' => env('FILE_WATCHER_ENABLED', true),
        'extensions' => explode(',', env('FILE_WATCHER_EXTENSIONS', 'php,js,css,json,env')),
        'ignore_paths' => explode(',', env('FILE_WATCHER_IGNORE_PATHS', 'node_modules,vendor,storage/logs')),
        'watch_interval' => env('FILE_WATCHER_INTERVAL', 1000), // ms
    ],

    /*
    |--------------------------------------------------------------------------
    | 热重载配置
    |--------------------------------------------------------------------------
    */

    'hot_reload' => [
        'enabled' => env('HOT_RELOAD_ENABLED', true),
        'port' => env('HOT_RELOAD_PORT', 35729),
        'host' => env('HOT_RELOAD_HOST', '127.0.0.1'),
        'paths' => [
            app_path('Filament'),
            resource_path('views/filament'),
            base_path('config/filament.php'),
            base_path('config/auth.php'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 浏览器同步配置
    |--------------------------------------------------------------------------
    */

    'browser_sync' => [
        'enabled' => env('BROWSER_SYNC_ENABLED', true),
        'port' => env('BROWSER_SYNC_PORT', 3000),
        'host' => env('BROWSER_SYNC_HOST', '127.0.0.1'),
        'proxy' => env('BROWSER_SYNC_PROXY', 'http://localhost:8787'),
        'files' => [
            'app/**/*.php',
            'resources/views/**/*.php',
            'resources/views/**/*.blade.php',
            'config/**/*.php',
            'public/**/*.css',
            'public/**/*.js',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | WebSocket 配置
    |--------------------------------------------------------------------------
    */

    'websocket' => [
        'enabled' => env('WEBSOCKET_ENABLED', true),
        'port' => env('WEBSOCKET_PORT', 6001),
        'host' => env('WEBSOCKET_HOST', '127.0.0.1'),
        'path' => '/websocket',
    ],

    /*
    |--------------------------------------------------------------------------
    | 性能分析配置
    |--------------------------------------------------------------------------
    */

    'profiling' => [
        'enabled' => env('PROFILING_ENABLED', true),
        'xhprof' => [
            'enabled' => env('PROFILING_XHPROF_ENABLED', true),
            'output_dir' => storage_path('profiling'),
        ],
        'blackfire' => [
            'enabled' => env('PROFILING_BLACKFIRE_ENABLED', false),
            'server_id' => env('BLACKFIRE_SERVER_ID'),
            'server_token' => env('BLACKFIRE_SERVER_TOKEN'),
        ],
        'new_relic' => [
            'enabled' => env('PROFILING_NEW_RELIC_ENABLED', false),
            'app_name' => env('NEW_RELIC_APP_NAME', 'Webman Filament Dev'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 文档配置
    |--------------------------------------------------------------------------
    */

    'documentation' => [
        'enabled' => env('DOCS_ENABLED', true),
        'port' => env('DOCS_PORT', 8080),
        'host' => env('DOCS_HOST', '127.0.0.1'),
        'auto_generate' => env('DOCS_AUTO_GENERATE', true),
        'output_dir' => storage_path('docs'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 模拟数据配置
    |--------------------------------------------------------------------------
    */

    'mock_data' => [
        'enabled' => env('MOCK_DATA_ENABLED', true),
        'count' => env('MOCK_DATA_COUNT', 100),
        'factories' => env('MOCK_DATA_FACTORIES', 'App\\Database\\factories'),
        'seed_on_migrate' => env('DB_SEED_ON_MIGRATE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 测试配置
    |--------------------------------------------------------------------------
    */

    'testing' => [
        'enabled' => env('TESTING_ENABLED', true),
        'db_connection' => env('TESTING_DB_CONNECTION', 'sqlite'),
        'db_database' => env('TESTING_DB_DATABASE', ':memory:'),
        'cache_driver' => env('TESTING_CACHE_DRIVER', 'array'),
        'session_driver' => env('TESTING_SESSION_DRIVER', 'array'),
    ],

    /*
    |--------------------------------------------------------------------------
    | 开发插件配置
    |--------------------------------------------------------------------------
    */

    'development_plugins' => [
        'enabled' => env('DEVELOPMENT_PLUGINS_ENABLED', true),
        'debug_plugin' => env('DEVELOPMENT_DEBUG_PLUGIN_ENABLED', true),
        'routes_plugin' => env('DEVELOPMENT_ROUTES_PLUGIN_ENABLED', true),
        'database_plugin' => env('DEVELOPMENT_DATABASE_PLUGIN_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 调试配置
    |--------------------------------------------------------------------------
    */

    'debug' => [
        'enabled' => true,
        'verbose_errors' => true,
        'show_sql_queries' => true,
        'show_performance_metrics' => true,
        'show_memory_usage' => true,
        'show_included_files' => true,
        'show_included_files_count' => true,
        'show_loaded_extensions' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | 缓存配置（开发环境禁用）
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'config' => false,
        'routes' => false,
        'views' => false,
        'events' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | 日志配置（开发环境详细）
    |--------------------------------------------------------------------------
    */

    'logging' => [
        'level' => 'debug',
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['daily', 'slack'],
                'ignore_exceptions' => false,
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => storage_path('logs/laravel.log'),
                'level' => 'debug',
                'days' => 14,
            ],
            'slack' => [
                'driver' => 'slack',
                'url' => env('LOG_SLACK_WEBHOOK_URL'),
                'username' => 'Laravel Log',
                'emoji' => ':boom:',
                'level' => 'critical',
            ],
            'syslog' => [
                'driver' => 'syslog',
                'level' => 'debug',
            ],
            'errorlog' => [
                'driver' => 'errorlog',
                'level' => 'debug',
            ],
        ],
    ],
];