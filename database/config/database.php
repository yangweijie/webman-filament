<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Database Connection Name
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the database connections below you wish
    | to use as your default connection for all database work. Of course
    | you may use many connections at once using the Database library.
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Database Connections
    |--------------------------------------------------------------------------
    |
    | Here are each of the database connections setup for your application.
    | Of course, examples of configuring each database platform that is
    | supported by Laravel is shown below to make development simple.
    |
    |
    | All database work in Laravel is done through the PHP PDO facilities
    | so make sure you have the driver for your particular database of
    | choice installed on your machine before you begin development.
    |
    */

    'connections' => [

        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            // SQLite 连接池配置（简化版）
            'pool_size' => 5,
            'max_connections' => 10,
        ],

        'mysql' => [
            'driver' => 'mysql',
            'url' => env('DATABASE_URL'),
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
            
            // 连接池配置
            'pool_size' => 10,              // 最小连接数
            'max_connections' => 50,        // 最大连接数
            'connection_timeout' => 60,     // 连接超时时间（秒）
            'idle_timeout' => 600,          // 空闲超时时间（秒）
            'acquire_timeout' => 30,        // 获取连接超时时间（秒）
            'wait_timeout' => 28800,        // MySQL 等待超时
            'interactive_timeout' => 28800, // MySQL 交互超时
            
            // 健康检查配置
            'health_check_interval' => 60,  // 健康检查间隔（秒）
            'health_check_timeout' => 5,    // 健康检查超时（秒）
            
            // 性能优化配置
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'init_commands' => [
                "SET SESSION wait_timeout = 28800",
                "SET SESSION interactive_timeout = 28800",
                "SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'",
            ],
        ],

        'pgsql' => [
            'driver' => 'pgsql',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5432'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
            
            // 连接池配置
            'pool_size' => 10,
            'max_connections' => 50,
            'connection_timeout' => 60,
            'idle_timeout' => 600,
            'acquire_timeout' => 30,
            
            // 健康检查配置
            'health_check_interval' => 60,
            'health_check_timeout' => 5,
            
            // PostgreSQL 特定配置
            'init_commands' => [
                "SET statement_timeout = 0",
                "SET lock_timeout = 0",
                "SET idle_in_transaction_session_timeout = 0",
            ],
        ],

        'sqlsrv' => [
            'driver' => 'sqlsrv',
            'url' => env('DATABASE_URL'),
            'host' => env('DB_HOST', 'localhost'),
            'port' => env('DB_PORT', '1433'),
            'database' => env('DB_DATABASE', 'forge'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            
            // 连接池配置
            'pool_size' => 10,
            'max_connections' => 50,
            'connection_timeout' => 60,
            'idle_timeout' => 600,
            'acquire_timeout' => 30,
            
            // 健康检查配置
            'health_check_interval' => 60,
            'health_check_timeout' => 5,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Repository Table
    |--------------------------------------------------------------------------
    |
    | This table keeps track of all the migrations that have already run for
    | your application. Using this information, we can determine which of
    | the migrations on disk haven't actually been run in the database.
    |
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis Databases
    |--------------------------------------------------------------------------
    |
    | Redis is an open source, fast, and advanced key-value store that also
    | provides a richer body of commands than a typical key-value system
    | such as APC or Memcached. Laravel makes it easy to dig right in.
    |
    */

    'redis' => [

        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', Str::slug(env('APP_NAME', 'laravel'), '_').'_database_'),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Migration Adapter Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the database migration adapter system.
    |
    */

    'migration_adapter' => [
        // 支持的数据库驱动
        'supported_drivers' => ['mysql', 'pgsql', 'sqlite', 'sqlsrv'],
        
        // 默认连接池配置
        'default_pool_config' => [
            'min_connections' => 5,
            'max_connections' => 20,
            'connection_timeout' => 60,
            'idle_timeout' => 600,
            'acquire_timeout' => 30,
        ],
        
        // 健康检查配置
        'health_check' => [
            'enabled' => true,
            'interval' => 60, // 秒
            'timeout' => 5,   // 秒
        ],
        
        // 缓存配置
        'cache' => [
            'ttl' => 3600, // 1小时
            'prefix' => 'migration_adapter_',
        ],
        
        // 日志配置
        'logging' => [
            'enabled' => true,
            'level' => 'info',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Adapter Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the Eloquent model adapter.
    |
    */

    'model_adapter' => [
        // 缓存配置
        'cache' => [
            'enabled' => true,
            'ttl' => 3600, // 1小时
            'prefix' => 'model_adapter_',
        ],
        
        // 批量操作配置
        'batch' => [
            'default_chunk_size' => 1000,
            'max_chunk_size' => 5000,
        ],
        
        // 查询优化配置
        'query' => [
            'enable_cache' => true,
            'cache_ttl' => 1800, // 30分钟
        ],
        
        // 日志配置
        'logging' => [
            'enabled' => true,
            'level' => 'warning',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Handler Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the database connection pool handler.
    |
    */

    'database_handler' => [
        // 连接池配置
        'pool' => [
            'min_connections' => 5,
            'max_connections' => 20,
            'acquire_timeout' => 60,
            'idle_timeout' => 600,
            'health_check_interval' => 60,
        ],
        
        // 健康检查配置
        'health_check' => [
            'enabled' => true,
            'interval' => 60,
            'timeout' => 5,
            'max_failures' => 3,
        ],
        
        // 事务配置
        'transaction' => [
            'timeout' => 30,
            'max_retries' => 3,
        ],
        
        // 性能监控
        'monitoring' => [
            'enabled' => true,
            'slow_query_threshold' => 1000, // 毫秒
        ],
        
        // 日志配置
        'logging' => [
            'enabled' => true,
            'level' => 'info',
            'slow_queries' => true,
        ],
    ],
];