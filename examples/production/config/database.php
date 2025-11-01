<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 生产环境数据库配置
    |--------------------------------------------------------------------------
    |
    | 生产环境下的数据库配置，优化性能和安全性
    |
    */

    'default' => env('DB_CONNECTION', 'mysql'),

    'connections' => [
        'sqlite' => [
            'driver' => 'sqlite',
            'url' => env('DATABASE_URL'),
            'database' => env('DB_DATABASE', database_path('database.sqlite')),
            'prefix' => '',
            'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
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
                PDO::ATTR_TIMEOUT => 30,
                PDO::ATTR_PERSISTENT => true,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'",
            ]) : [],
            'pool' => [
                'min_connections' => env('DB_POOL_MIN_CONNECTIONS', 5),
                'max_connections' => env('DB_POOL_MAX_CONNECTIONS', 20),
                'acquire_timeout' => env('DB_POOL_ACQUIRE_TIMEOUT', 60),
                'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 600),
                'wait_timeout' => env('DB_POOL_WAIT_TIMEOUT', 3),
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
            'options' => array_filter([
                PDO::ATTR_TIMEOUT => 30,
                PDO::ATTR_PERSISTENT => true,
            ]),
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
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 迁移存储目录
    |--------------------------------------------------------------------------
    */

    'migrations' => 'migrations',

    /*
    |--------------------------------------------------------------------------
    | Redis 配置
    |--------------------------------------------------------------------------
    */

    'redis' => [
        'client' => env('REDIS_CLIENT', 'phpredis'),

        'options' => [
            'cluster' => env('REDIS_CLUSTER', 'redis'),
            'prefix' => env('REDIS_PREFIX', 'webman_filament_prod_'),
            'read_timeout' => env('REDIS_READ_TIMEOUT', 5),
            'write_timeout' => env('REDIS_WRITE_TIMEOUT', 5),
            'connect_timeout' => env('REDIS_CONNECT_TIMEOUT', 5),
            'persistent' => env('REDIS_PERSISTENT', true),
        ],

        'default' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_DB', '0'),
            'timeout' => env('REDIS_TIMEOUT', 5),
            'read_timeout' => env('REDIS_READ_TIMEOUT', 5),
            'write_timeout' => env('REDIS_WRITE_TIMEOUT', 5),
            'persistent' => env('REDIS_PERSISTENT', true),
        ],

        'cache' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_CACHE_DB', '1'),
            'timeout' => env('REDIS_TIMEOUT', 5),
        ],

        'sessions' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_SESSION_DB', '2'),
            'timeout' => env('REDIS_TIMEOUT', 5),
        ],

        'queue' => [
            'url' => env('REDIS_URL'),
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'username' => env('REDIS_USERNAME'),
            'password' => env('REDIS_PASSWORD'),
            'port' => env('REDIS_PORT', '6379'),
            'database' => env('REDIS_QUEUE_DB', '3'),
            'timeout' => env('REDIS_TIMEOUT', 5),
        ],

        'cluster' => [
            'default' => [
                [
                    'host' => env('REDIS_CLUSTER_HOST_1', '127.0.0.1'),
                    'port' => env('REDIS_CLUSTER_PORT_1', '6379'),
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | 数据库备份配置
    |--------------------------------------------------------------------------
    */

    'backup' => [
        'enabled' => env('BACKUP_ENABLED', true),
        'disk' => env('BACKUP_DISK', 's3'),
        'schedule' => env('BACKUP_SCHEDULE', '0 2 * * *'),
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'compression' => env('BACKUP_COMPRESSION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | 连接池配置
    |--------------------------------------------------------------------------
    */

    'connection_pool' => [
        'enabled' => env('DB_CONNECTION_POOL_ENABLED', true),
        'min_connections' => env('DB_POOL_MIN_CONNECTIONS', 5),
        'max_connections' => env('DB_POOL_MAX_CONNECTIONS', 20),
        'acquire_timeout' => env('DB_POOL_ACQUIRE_TIMEOUT', 60),
        'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 600),
        'wait_timeout' => env('DB_POOL_WAIT_TIMEOUT', 3),
        'max_lifetime' => env('DB_POOL_MAX_LIFETIME', 1800),
    ],

    /*
    |--------------------------------------------------------------------------
    | 性能监控
    |--------------------------------------------------------------------------
    */

    'monitoring' => [
        'slow_query_log' => env('DB_SLOW_QUERY_LOG', true),
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000), // ms
        'query_cache_enabled' => env('DB_QUERY_CACHE_ENABLED', true),
        'query_cache_ttl' => env('DB_QUERY_CACHE_TTL', 300), // seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | 安全配置
    |--------------------------------------------------------------------------
    */

    'security' => [
        'strict_trans_tables' => true,
        'no_zero_date' => true,
        'no_zero_in_date' => true,
        'error_for_division_by_zero' => true,
        'sql_mode' => 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO',
    ],
];