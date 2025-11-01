<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Filament 基本配置
    |--------------------------------------------------------------------------
    |
    | Filament 后台面板的基本配置选项
    |
    */

    /**
     * 是否启用自动路由注册
     */
    'auto_register_routes' => env('FILAMENT_AUTO_REGISTER_ROUTES', true),

    /**
     * 默认面板配置
     */
    'default_panel' => [
        'id' => env('FILAMENT_PANEL_ID', 'admin'),
        'path' => env('FILAMENT_PANEL_PATH', 'admin'),
        'domain' => env('FILAMENT_PANEL_DOMAIN', null),
        'title' => env('FILAMENT_PANEL_TITLE', 'Admin Panel'),
        'short_title' => env('FILAMENT_PANEL_SHORT_TITLE', 'Admin'),
        'url' => env('FILAMENT_PANEL_URL', null),
        'csp' => env('FILAMENT_PANEL_CSP', null),
    ],

    /**
     * 面板配置列表
     */
    'panels' => [
        'admin' => [
            'id' => 'admin',
            'path' => 'admin',
            'domain' => null,
            'title' => 'Admin Panel',
            'short_title' => 'Admin',
            'url' => null,
            'csp' => null,
            'middleware' => [
                'web',
                'auth',
            ],
            'auth' => [
                'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
                'login_route' => 'filament.admin.auth.login',
                'login_url' => '/admin/login',
                'logout_route' => 'filament.admin.auth.logout',
                'password_reset_route' => 'filament.admin.auth.password-reset.request',
                'email_verification_route' => 'filament.admin.auth.email-verification.prompt',
                'profile_route' => 'filament.admin.auth.profile',
            ],
            'database' => [
                'path' => database_path('filament/admin'),
            ],
            'discover_resources_in' => app_path('Filament/Resources'),
            'discover_pages_in' => app_path('Filament/Pages'),
            'discover_widgets_in' => app_path('Filament/Widgets'),
            'pages' => [],
            'resources' => [],
            'widgets' => [],
            'notifications' => [
                'database' => true,
            ],
            'dark_mode' => env('FILAMENT_DARK_MODE', false),
            'broadcasting' => [
                'channel' => 'filament.admin',
            ],
            'theme' => env('FILAMENT_THEME', null),
        ],
    ],

    /**
     * 面板提供者类映射
     */
    'panel_providers' => [
        'admin' => \App\Filament\AdminPanelProvider::class,
    ],

    /**
     * 插件配置
     */
    'plugins' => [
        // 示例插件
        // \Spatie\FilamentShield\FilamentShieldPlugin::make(),
        // \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
    ],

    /*
    |--------------------------------------------------------------------------
    | 适配器配置
    |--------------------------------------------------------------------------
    |
    | webman 与 Filament 之间的适配器配置
    |
    */

    /**
     * 请求响应适配器配置
     */
    'request_response_adapter' => [
        'timeout' => env('FILAMENT_REQUEST_TIMEOUT', 30),
        'max_redirects' => env('FILAMENT_MAX_REDIRECTS', 5),
        'preserve_fragments' => env('FILAMENT_PRESERVE_FRAGMENTS', false),
    ],

    /**
     * 容器适配器配置
     */
    'container_adapter' => [
        'prefer_laravel_container' => env('FILAMENT_PREFER_LARAVEL_CONTAINER', true),
        'enable_php_di' => env('FILAMENT_ENABLE_PHP_DI', false),
        'php_di_config_path' => env('FILAMENT_PHP_DI_CONFIG', base_path('di-config.php')),
    ],

    /**
     * 路由适配器配置
     */
    'route_adapter' => [
        'fallback_enabled' => env('FILAMENT_ROUTE_FALLBACK_ENABLED', true),
        'fallback_handler' => env('FILAMENT_ROUTE_FALLBACK_HANDLER', \WebmanFilament\Support\FallbackHandler::class),
        'route_prefix' => env('FILAMENT_ROUTE_PREFIX', ''),
        'middleware_groups' => [
            'web' => ['web'],
            'auth' => ['auth'],
            'guest' => ['guest'],
        ],
    ],

    /**
     * 中间件适配器配置
     */
    'middleware_adapter' => [
        'enable_laravel_middleware' => env('FILAMENT_ENABLE_LARAVEL_MIDDLEWARE', true),
        'middleware_aliases' => [
            'auth' => \Illuminate\Auth\Middleware\Authenticate::class,
            'guest' => \Illuminate\Auth\Middleware\RedirectIfAuthenticated::class,
            'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
            'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 生命周期配置
    |--------------------------------------------------------------------------
    |
    | 适配器生命周期管理配置
    |
    */

    /**
     * 桥接器配置
     */
    'bridge' => [
        'enable_lifecycle_events' => env('FILAMENT_LIFECYCLE_EVENTS_ENABLED', true),
        'enable_auto_reload' => env('FILAMENT_AUTO_RELOAD', true),
        'reload_strategy' => env('FILAMENT_RELOAD_STRATEGY', 'graceful'), // graceful, immediate
        'health_check_enabled' => env('FILAMENT_HEALTH_CHECK_ENABLED', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 性能优化配置
    |--------------------------------------------------------------------------
    |
    | 性能优化相关配置
    |
    */

    /**
     * 缓存配置
     */
    'cache' => [
        'enabled' => env('FILAMENT_CACHE_ENABLED', true),
        'driver' => env('FILAMENT_CACHE_DRIVER', 'file'),
        'ttl' => env('FILAMENT_CACHE_TTL', 3600),
        'key_prefix' => env('FILAMENT_CACHE_PREFIX', 'filament_'),
    ],

    /**
     * 连接池配置
     */
    'connection_pool' => [
        'enabled' => env('FILAMENT_CONNECTION_POOL_ENABLED', true),
        'min_connections' => env('FILAMENT_POOL_MIN_CONNECTIONS', 2),
        'max_connections' => env('FILAMENT_POOL_MAX_CONNECTIONS', 10),
        'connection_timeout' => env('FILAMENT_POOL_CONNECTION_TIMEOUT', 3),
        'idle_timeout' => env('FILAMENT_POOL_IDLE_TIMEOUT', 300),
    ],

    /**
     * 静态资源配置
     */
    'assets' => [
        'version' => env('FILAMENT_ASSETS_VERSION', '4.0.0'),
        'cdn_enabled' => env('FILAMENT_ASSETS_CDN_ENABLED', false),
        'cdn_url' => env('FILAMENT_ASSETS_CDN_URL', ''),
        'local_path' => env('FILAMENT_ASSETS_LOCAL_PATH', 'public/filament'),
        'cache_enabled' => env('FILAMENT_ASSETS_CACHE_ENABLED', true),
        'cache_max_age' => env('FILAMENT_ASSETS_CACHE_MAX_AGE', 31536000), // 1年
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全配置
    |--------------------------------------------------------------------------
    |
    | 安全相关配置
    |
    */

    /**
     * CSRF 配置
     */
    'csrf' => [
        'enabled' => env('FILAMENT_CSRF_ENABLED', true),
        'token_field' => '_token',
        'header_name' => 'X-CSRF-TOKEN',
    ],

    /**
     * 认证配置
     */
    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
        'provider' => env('FILAMENT_AUTH_PROVIDER', 'users'),
        'login_url' => '/admin/login',
        'logout_url' => '/admin/logout',
        'password_reset_url' => '/admin/password/reset',
        'email_verification_url' => '/admin/email/verify',
        'profile_url' => '/admin/profile',
        'mfa_enabled' => env('FILAMENT_MFA_ENABLED', false),
        'session_lifetime' => env('FILAMENT_SESSION_LIFETIME', 120),
    ],

    /**
     * 权限配置
     */
    'authorization' => [
        'enabled' => env('FILAMENT_AUTHORIZATION_ENABLED', true),
        'policies_enabled' => env('FILAMENT_POLICIES_ENABLED', true),
        'roles_enabled' => env('FILAMENT_ROLES_ENABLED', false),
        'permissions_enabled' => env('FILAMENT_PERMISSIONS_ENABLED', false),
        'shield_enabled' => env('FILAMENT_SHIELD_ENABLED', false),
        'spatie_enabled' => env('FILAMENT_SPATIE_ENABLED', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | 日志和监控配置
    |--------------------------------------------------------------------------
    |
    | 日志记录和监控相关配置
    |
    */

    /**
     * 日志配置
     */
    'logging' => [
        'enabled' => env('FILAMENT_LOGGING_ENABLED', true),
        'channel' => env('FILAMENT_LOG_CHANNEL', 'filament'),
        'level' => env('FILAMENT_LOG_LEVEL', 'info'),
        'log_requests' => env('FILAMENT_LOG_REQUESTS', false),
        'log_responses' => env('FILAMENT_LOG_RESPONSES', false),
        'log_performance' => env('FILAMENT_LOG_PERFORMANCE', false),
    ],

    /**
     * 监控配置
     */
    'monitoring' => [
        'enabled' => env('FILAMENT_MONITORING_ENABLED', true),
        'metrics_enabled' => env('FILAMENT_METRICS_ENABLED', true),
        'health_check_enabled' => env('FILAMENT_HEALTH_CHECK_ENABLED', true),
        'alerting_enabled' => env('FILAMENT_ALERTING_ENABLED', false),
        'alert_webhook' => env('FILAMENT_ALERT_WEBHOOK', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | 开发环境配置
    |--------------------------------------------------------------------------
    |
    | 开发环境特定配置
    |
    */

    /**
     * 调试配置
     */
    'debug' => [
        'enabled' => env('FILAMENT_DEBUG_ENABLED', env('APP_DEBUG', false)),
        'show_sql_queries' => env('FILAMENT_SHOW_SQL_QUERIES', false),
        'show_performance_metrics' => env('FILAMENT_SHOW_PERFORMANCE_METRICS', false),
        'verbose_error_messages' => env('FILAMENT_VERBOSE_ERROR_MESSAGES', false),
    ],

    /**
     * 热重载配置
     */
    'hot_reload' => [
        'enabled' => env('FILAMENT_HOT_RELOAD_ENABLED', env('APP_DEBUG', false)),
        'watch_paths' => [
            app_path('Filament'),
            resource_path('views/filament'),
            base_path('config/filament.php'),
        ],
        'ignored_paths' => [
            'node_modules',
            'vendor',
            'storage',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 实验性功能配置
    |--------------------------------------------------------------------------
    |
    | 实验性功能配置（谨慎使用）
    |
    */

    /**
     * 实验性功能
     */
    'experimental' => [
        'concurrent_requests' => env('FILAMENT_EXPERIMENTAL_CONCURRENT_REQUESTS', false),
        'streamed_responses' => env('FILAMENT_EXPERIMENTAL_STREAMED_RESPONSES', false),
        'websocket_support' => env('FILAMENT_EXPERIMENTAL_WEBSOCKET_SUPPORT', false),
        'graphql_api' => env('FILAMENT_EXPERIMENTAL_GRAPHQL_API', false),
    ],
];