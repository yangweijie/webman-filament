<?php

/**
 * Filament 路由配置
 * 
 * 定义 Filament 面板路由映射与保护策略
 * 支持多面板配置与动态路由注册
 */

return [
    /**
     * 面板配置
     */
    'panels' => [
        'default' => [
            'id' => 'default',
            'name' => 'Admin Panel',
            'path' => '/admin',
            'domain' => null, // null 表示使用当前域名
            'middleware' => ['web', 'auth'],
            'auth' => [
                'guard' => 'web',
                'login_url' => '/admin/login',
                'logout_url' => '/admin/logout',
                'password_reset_url' => '/admin/password/reset',
            ],
            'branding' => [
                'title' => 'Admin Panel',
                'logo' => '/filament/assets/images/logo.png',
                'colors' => [
                    'primary' => '#6366f1',
                    'secondary' => '#64748b',
                ],
            ],
        ],
        
        'dashboard' => [
            'id' => 'dashboard',
            'name' => 'Dashboard',
            'path' => '/dashboard',
            'domain' => null,
            'middleware' => ['web', 'auth'],
            'auth' => [
                'guard' => 'web',
                'login_url' => '/dashboard/login',
                'logout_url' => '/dashboard/logout',
            ],
        ],
    ],

    /**
     * 路由映射
     * 
     * 定义 Filament 核心路由与自定义路由的映射关系
     */
    'routes' => [
        /**
         * 认证路由
         */
        'auth' => [
            'login' => [
                'path' => '/login',
                'name' => 'filament.auth.login',
                'methods' => ['GET', 'POST'],
                'middleware' => ['web', 'guest'],
                'controller' => \WebmanFilament\Controllers\AuthController::class,
                'action' => 'login',
            ],
            'logout' => [
                'path' => '/logout',
                'name' => 'filament.auth.logout',
                'methods' => ['POST'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\AuthController::class,
                'action' => 'logout',
            ],
            'password.request' => [
                'path' => '/password/reset',
                'name' => 'filament.auth.password.request',
                'methods' => ['GET', 'POST'],
                'middleware' => ['web', 'guest'],
                'controller' => \WebmanFilament\Controllers\AuthController::class,
                'action' => 'password.request',
            ],
            'password.reset' => [
                'path' => '/password/reset/{token}',
                'name' => 'filament.auth.password.reset',
                'methods' => ['GET', 'POST'],
                'middleware' => ['web', 'guest'],
                'controller' => \WebmanFilament\Controllers\AuthController::class,
                'action' => 'password.reset',
            ],
        ],

        /**
         * 面板路由
         */
        'panel' => [
            'home' => [
                'path' => '/',
                'name' => 'filament.dashboard',
                'methods' => ['GET'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\PanelController::class,
                'action' => 'dashboard',
            ],
            'profile' => [
                'path' => '/profile',
                'name' => 'filament.profile',
                'methods' => ['GET', 'POST'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\ProfileController::class,
                'action' => 'edit',
            ],
        ],

        /**
         * 资源路由
         */
        'resources' => [
            'index' => [
                'path' => '/resources/{resource}',
                'name' => 'filament.resources.index',
                'methods' => ['GET'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\ResourceController::class,
                'action' => 'index',
            ],
            'create' => [
                'path' => '/resources/{resource}/create',
                'name' => 'filament.resources.create',
                'methods' => ['GET'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\ResourceController::class,
                'action' => 'create',
            ],
            'edit' => [
                'path' => '/resources/{resource}/{record}/edit',
                'name' => 'filament.resources.edit',
                'methods' => ['GET'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\ResourceController::class,
                'action' => 'edit',
            ],
            'view' => [
                'path' => '/resources/{resource}/{record}',
                'name' => 'filament.resources.view',
                'methods' => ['GET'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\ResourceController::class,
                'action' => 'view',
            ],
            'store' => [
                'path' => '/resources/{resource}',
                'name' => 'filament.resources.store',
                'methods' => ['POST'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\ResourceController::class,
                'action' => 'store',
            ],
            'update' => [
                'path' => '/resources/{resource}/{record}',
                'name' => 'filament.resources.update',
                'methods' => ['PUT', 'PATCH'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\ResourceController::class,
                'action' => 'update',
            ],
            'delete' => [
                'path' => '/resources/{resource}/{record}',
                'name' => 'filament.resources.delete',
                'methods' => ['DELETE'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\ResourceController::class,
                'action' => 'delete',
            ],
        ],

        /**
         * 页面路由
         */
        'pages' => [
            'custom' => [
                'path' => '/pages/{page}',
                'name' => 'filament.pages.custom',
                'methods' => ['GET'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\PageController::class,
                'action' => 'custom',
            ],
        ],

        /**
         * 动作路由
         */
        'actions' => [
            'trigger' => [
                'path' => '/actions/{action}',
                'name' => 'filament.actions.trigger',
                'methods' => ['POST'],
                'middleware' => ['web', 'auth'],
                'controller' => \WebmanFilament\Controllers\ActionController::class,
                'action' => 'trigger',
            ],
        ],

        /**
         * API 路由
         */
        'api' => [
            'resources' => [
                'path' => '/api/resources/{resource}',
                'name' => 'filament.api.resources',
                'methods' => ['GET', 'POST', 'PUT', 'DELETE'],
                'middleware' => ['web', 'auth', 'api'],
                'controller' => \WebmanFilament\Controllers\ApiController::class,
                'action' => 'resources',
            ],
            'search' => [
                'path' => '/api/search',
                'name' => 'filament.api.search',
                'methods' => ['GET'],
                'middleware' => ['web', 'auth', 'api'],
                'controller' => \WebmanFilament\Controllers\ApiController::class,
                'action' => 'search',
            ],
        ],
    ],

    /**
     * 中间件配置
     */
    'middleware' => [
        /**
         * 全局中间件
         */
        'global' => [
            'webman\filament\middleware\FilamentMiddleware',
            'webman\filament\middleware\AssetMiddleware',
            'webman\filament\middleware\ErrorHandlerMiddleware',
        ],

        /**
         * 路由中间件组
         */
        'groups' => [
            'web' => [
                'webman\filament\middleware\SessionMiddleware',
                'webman\filament\middleware\CsrfMiddleware',
                'webman\filament\middleware\SecurityMiddleware',
            ],
            'auth' => [
                'webman\filament\middleware\AuthMiddleware',
            ],
            'guest' => [
                'webman\filament\middleware\GuestMiddleware',
            ],
            'api' => [
                'webman\filament\middleware\ApiMiddleware',
                'webman\filament\middleware\RateLimitMiddleware',
            ],
        ],

        /**
         * 路由特定中间件
         */
        'route' => [
            'auth' => 'webman\filament\middleware\AuthMiddleware',
            'guest' => 'webman\filament\middleware\GuestMiddleware',
            'throttle' => 'webman\filament\middleware\ThrottleMiddleware',
            'verified' => 'webman\filament\middleware\VerifiedMiddleware',
        ],
    ],

    /**
     * 静态资源配置
     */
    'assets' => [
        'base_path' => '/filament/assets',
        'version' => '4.x',
        'cache' => [
            'enabled' => true,
            'max_age' => 31536000, // 1年
            'immutable' => true,
        ],
        'compression' => [
            'gzip' => true,
            'brotli' => true,
        ],
        'cdn' => [
            'enabled' => false,
            'url' => null,
        ],
    ],

    /**
     * 保护策略
     */
    'protection' => [
        /**
         * 认证配置
         */
        'auth' => [
            'guard' => 'web',
            'provider' => 'users',
            'password_broker' => 'users',
            'remember_token' => 'remember_token',
        ],

        /**
         * 权限配置
         */
        'permissions' => [
            'enabled' => true,
            'policy_namespace' => 'App\\Policies',
            'cache_enabled' => true,
            'cache_ttl' => 3600,
        ],

        /**
         * 速率限制
         */
        'rate_limit' => [
            'enabled' => true,
            'max_attempts' => 60,
            'decay_minutes' => 1,
            'prefix' => 'filament',
        ],

        /**
         * CSRF 保护
         */
        'csrf' => [
            'enabled' => true,
            'except' => [
                '/api/*',
                '/webhook/*',
            ],
        ],
    ],

    /**
     * 错误处理
     */
    'errors' => [
        '404' => [
            'enabled' => true,
            'template' => 'filament::errors.404',
            'redirect' => '/admin',
        ],
        '403' => [
            'enabled' => true,
            'template' => 'filament::errors.403',
            'redirect' => '/admin',
        ],
        '500' => [
            'enabled' => true,
            'template' => 'filament::errors.500',
            'log' => true,
        ],
    ],

    /**
     * 开发配置
     */
    'development' => [
        'debug' => env('APP_DEBUG', false),
        'hot_reload' => env('FILAMENT_HOT_RELOAD', false),
        'asset_versioning' => false,
        'cache_disabled' => true,
    ],

    /**
     * 性能配置
     */
    'performance' => [
        'opcache' => [
            'enabled' => true,
            'jit_buffer_size' => '64M',
        ],
        'memory_limit' => '256M',
        'max_execution_time' => 60,
        'connection_pool' => [
            'enabled' => true,
            'max_connections' => 32,
            'min_connections' => 4,
        ],
    ],

    /**
     * 日志配置
     */
    'logging' => [
        'enabled' => true,
        'level' => env('LOG_LEVEL', 'info'),
        'channels' => [
            'filament' => [
                'driver' => 'daily',
                'path' => storage_path('logs/filament.log'),
                'level' => 'debug',
                'days' => 30,
            ],
        ],
    ],

    /**
     * 插件配置
     */
    'plugins' => [
        'enabled' => true,
        'auto_discover' => true,
        'paths' => [
            app_path('Filament/Plugins'),
            base_path('plugins/filament'),
        ],
    ],

    /**
     * 主题配置
     */
    'theme' => [
        'default' => 'filament',
        'custom_css' => null,
        'custom_js' => null,
        'tailwind_config' => null,
    ],

    /**
     * 数据库配置
     */
    'database' => [
        'default' => env('DB_CONNECTION', 'mysql'),
        'migrations' => [
            'path' => database_path('migrations/filament'),
            'namespace' => 'Filament\\Migrations',
        ],
        'seeders' => [
            'path' => database_path('seeders/Filament'),
            'namespace' => 'Filament\\Seeders',
        ],
    ],

    /**
     * 缓存配置
     */
    'cache' => [
        'driver' => env('CACHE_DRIVER', 'file'),
        'prefix' => 'filament',
        'ttl' => [
            'config' => 3600,
            'routes' => 1800,
            'permissions' => 3600,
            'assets' => 86400,
        ],
    ],

    /**
     * 事件配置
     */
    'events' => [
        'enabled' => true,
        'listeners' => [
            'route.registered' => [\WebmanFilament\Listeners\RouteRegisteredListener::class],
            'auth.login' => [\WebmanFilament\Listeners\AuthLoginListener::class],
            'auth.logout' => [\WebmanFilament\Listeners\AuthLogoutListener::class],
            'resource.created' => [\WebmanFilament\Listeners\ResourceCreatedListener::class],
            'resource.updated' => [\WebmanFilament\Listeners\ResourceUpdatedListener::class],
            'resource.deleted' => [\WebmanFilament\Listeners\ResourceDeletedListener::class],
        ],
    ],
];
