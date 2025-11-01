<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 认证默认值
    |--------------------------------------------------------------------------
    |
    | 此选项控制默认的认证"guard"和密码重置"broker"配置
    | 您可以根据应用程序的需求更改这些设置
    |
    */

    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    /*
    |--------------------------------------------------------------------------
    | 认证守护者
    |--------------------------------------------------------------------------
    |
    | 接下来，您可以定义每个认证守护者的配置
    | 包括会话、API Token 和 Sanctum 认证方式
    | 支持多 Guard 认证架构
    |
    */

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],

        'api' => [
            'driver' => 'token',
            'provider' => 'users',
            'hash' => false,
        ],

        'sanctum' => [
            'driver' => 'sanctum',
            'provider' => 'users',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 用户认证提供者
    |--------------------------------------------------------------------------
    |
    | 所有认证守护者都有一个认证提供者
    | 定义用户如何从底层持久化存储中检索
    | 支持 Eloquent 和数据库认证提供者
    |
    */

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],

        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 密码重置配置
    |--------------------------------------------------------------------------
    |
    | 您可以指定多个密码重置配置，如果您在应用程序中有多个用户表
    | 并希望为每个表设置单独的密码重置设置
    |
    */

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60, // 密码重置链接过期时间（分钟）
            'throttle' => 60, // 密码重置请求间隔（秒）
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 密码确认超时时间
    |--------------------------------------------------------------------------
    |
    | 这里您可以设置密码确认的超时时间（以分钟为单位）
    | 在此时间后，用户将被要求重新输入密码
    |
    */

    'password_timeout' => 10800, // 3小时

    /*
    |--------------------------------------------------------------------------
    | 超级管理员配置
    |--------------------------------------------------------------------------
    |
    | 定义超级管理员邮箱列表，这些用户拥有所有权限
    |
    */

    'super_admins' => [
        'admin@example.com',
        // 添加更多超级管理员邮箱
    ],

    /*
    |--------------------------------------------------------------------------
    | 多因素认证配置
    |--------------------------------------------------------------------------
    |
    | MFA 相关配置选项
    |
    */

    'mfa' => [
        'enabled' => true,
        'issuer' => env('APP_NAME', 'Laravel'),
        'digits' => 6,
        'period' => 30, // TOTP 时间窗口（秒）
        'algorithm' => 'SHA1',
        
        // 需要 MFA 验证的路由
        'required_routes' => [
            'admin.dashboard',
            'admin.users.*',
            'admin.roles.*',
            'admin.permissions.*',
            'admin.settings.*',
        ],

        // MFA 验证页面路由
        'verify_route' => 'mfa.verify',
        'setup_route' => 'mfa.setup',
    ],

    /*
    |--------------------------------------------------------------------------
    | 认证路由配置
    |--------------------------------------------------------------------------
    |
    | 定义认证相关的路由名称
    |
    */

    'routes' => [
        'login' => 'login',
        'logout' => 'logout',
        'register' => 'register',
        'password.request' => 'password.request',
        'password.email' => 'password.email',
        'password.reset' => 'password.reset',
        'password.update' => 'password.update',
        'verification.notice' => 'verification.notice',
        'verification.verify' => 'verification.verify',
        'verification.send' => 'verification.send',
        'unauthorized' => 'unauthorized',
        'mfa.verify' => 'mfa.verify',
        'mfa.setup' => 'mfa.setup',
        'mfa.enable' => 'mfa.enable',
        'mfa.disable' => 'mfa.disable',
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全配置
    |--------------------------------------------------------------------------
    |
    | 认证安全相关配置
    |
    */

    'security' => [
        // 最大登录失败次数
        'max_login_attempts' => 5,
        
        // 账户锁定时间（分钟）
        'lockout_duration' => 30,
        
        // 密码最小长度
        'min_password_length' => 8,
        
        // 密码复杂度要求
        'password_requirements' => [
            'uppercase' => true,  // 需要大写字母
            'lowercase' => true,  // 需要小写字母
            'numbers' => true,    // 需要数字
            'symbols' => false,   // 需要特殊字符
        ],

        // 会话配置
        'session' => [
            'lifetime' => 120, // 会话生命周期（分钟）
            'expire_on_close' => false,
            'encrypt' => false,
            'files' => storage_path('framework/sessions'),
            'connection' => null,
            'table' => 'sessions',
            'store' => null,
            'lottery' => [2, 100],
            'cookie' => 'laravel_session',
            'path' => '/',
            'domain' => env('SESSION_DOMAIN'),
            'secure' => env('SESSION_SECURE_COOKIE'),
            'http_only' => true,
            'same_site' => 'lax',
            'partitioned' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 权限系统配置
    |--------------------------------------------------------------------------
    |
    | 权限管理相关配置
    |
    */

    'permissions' => [
        // 启用权限缓存
        'cache_enabled' => true,
        
        // 缓存过期时间（秒）
        'cache_ttl' => 3600,
        
        // 权限缓存键前缀
        'cache_prefix' => 'permissions:',

        // 默认角色
        'default_roles' => [
            'user' => '普通用户',
            'editor' => '编辑',
            'admin' => '管理员',
            'super_admin' => '超级管理员',
        ],

        // 模型映射（用于权限检查）
        'model_mappings' => [
            'user' => App\Models\User::class,
            'role' => App\Models\Role::class,
            'permission' => App\Models\Permission::class,
            'post' => App\Models\Post::class,
            'category' => App\Models\Category::class,
        ],

        // 权限类型定义
        'permission_types' => [
            'view' => '查看',
            'create' => '创建',
            'edit' => '编辑',
            'delete' => '删除',
            'manage' => '管理',
            'export' => '导出',
            'import' => '导入',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 日志配置
    |--------------------------------------------------------------------------
    |
    | 认证日志相关配置
    |
    */

    'logging' => [
        // 认证日志通道
        'channel' => 'auth',
        
        // 记录级别
        'level' => 'info',
        
        // 记录内容
        'log_auth_events' => true,
        'log_permission_checks' => false,
        'log_security_events' => true,
        
        // 日志文件轮转
        'rotate_files' => true,
        'max_files' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | API 认证配置
    |--------------------------------------------------------------------------
    |
    | API 认证相关配置
    |
    */

    'api' => [
        // Token 过期时间（天）
        'token_expiry_days' => 30,
        
        // 刷新 Token 过期时间（天）
        'refresh_token_expiry_days' => 60,
        
        // API 限流配置
        'rate_limiting' => [
            'enabled' => true,
            'max_requests' => 60, // 每分钟最大请求数
            'decay_minutes' => 1,
        ],

        // CORS 配置
        'cors' => [
            'allowed_origins' => [
                'http://localhost:3000',
                'http://localhost:8080',
                // 添加更多允许的源
            ],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
            'exposed_headers' => [],
            'max_age' => 86400,
            'supports_credentials' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Filament 集成配置
    |--------------------------------------------------------------------------
    |
    | Filament 后台面板集成配置
    |
    */

    'filament' => [
        // 启用 Filament 权限系统
        'enable_permissions' => true,
        
        // 使用 Filament Shield
        'use_shield' => true,
        
        // 权限检查策略
        'policy_checking' => true,
        
        // 自定义权限组
        'permission_groups' => [
            'user_management' => '用户管理',
            'role_management' => '角色管理',
            'permission_management' => '权限管理',
            'system_settings' => '系统设置',
            'content_management' => '内容管理',
        ],

        // 面板配置
        'panel' => [
            'id' => 'admin',
            'path' => 'admin',
            'title' => 'Admin Panel',
            'favicon' => null,
            'brand' => env('APP_NAME', 'Laravel'),
            'dark_mode' => false,
            'auth' => [
                'guard' => 'web',
                'login' => 'login',
                'password_reset' => 'password.request',
                'email_verification' => 'verification.notice',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | webman 集成配置
    |--------------------------------------------------------------------------
    |
    | webman 框架集成相关配置
    |
    */

    'webman' => [
        // 适配器配置
        'adapters' => [
            'auth' => App\Adapter\AuthAdapter::class,
            'permission' => App\Adapter\PermissionAdapter::class,
        ],

        // 中间件配置
        'middleware' => [
            'auth' => App\Middleware\AuthMiddleware::class,
        ],

        // 处理器配置
        'handlers' => [
            'auth' => App\Handler\AuthHandler::class,
        ],

        // 请求转换器
        'translator' => [
            'request' => App\Bridge\RequestTranslator::class,
            'response' => App\Bridge\ResponseTranslator::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 事件配置
    |--------------------------------------------------------------------------
    |
    | 认证相关事件监听器配置
    |
    */

    'events' => [
        'listeners' => [
            'login' => [
                App\Listeners\LogSuccessfulLogin::class,
                App\Listeners\UpdateLastLoginTime::class,
            ],
            'logout' => [
                App\Listeners\LogSuccessfulLogout::class,
                App\Listeners\ClearUserCache::class,
            ],
            'failed_login' => [
                App\Listeners\LogFailedLogin::class,
                App\Listeners\CheckLoginAttempts::class,
            ],
            'password_reset' => [
                App\Listeners\LogPasswordReset::class,
                App\Listeners\SendPasswordResetNotification::class,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 开发环境配置
    |--------------------------------------------------------------------------
    |
    | 开发环境下的特殊配置
    |
    */

    'development' => [
        // 开发环境下允许的测试用户
        'test_users' => [
            'admin@test.com' => 'password',
            'user@test.com' => 'password',
        ],

        // 跳过某些安全检查
        'skip_security_checks' => env('AUTH_SKIP_SECURITY_CHECKS', false),
        
        // 详细日志
        'verbose_logging' => env('AUTH_VERBOSE_LOGGING', false),
    ],

];