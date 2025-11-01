# 数据库配置指南

本文档详细介绍Webman Filament Admin的数据库配置选项和最佳实践。

## 目录

- [数据库连接配置](#数据库连接配置)
- [数据库驱动](#数据库驱动)
- [连接池配置](#连接池配置)
- [迁移配置](#迁移配置)
- [种子配置](#种子配置)
- [性能优化](#性能优化)
- [备份策略](#备份策略)
- [监控和维护](#监控和维护)

## 数据库连接配置

### 基本连接配置

```php
// config/database.php
return [
    'default' => env('DB_CONNECTION', 'mysql'),
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'database' => env('DB_DATABASE', 'webman_filament'),
            'username' => env('DB_USERNAME', 'root'),
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
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]) : [],
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 10,
                'acquire_timeout' => 60,
                'idle_timeout' => 600,
                'wait_timeout' => 3,
            ],
        ],
    ],
];
```

### 环境变量配置

```env
# 数据库基础配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webman_filament
DB_USERNAME=root
DB_PASSWORD=your_secure_password

# 连接池配置
DB_POOL_MIN=1
DB_POOL_MAX=10
DB_POOL_ACQUIRE_TIMEOUT=60
DB_POOL_IDLE_TIMEOUT=600

# SSL配置
DB_SSL_MODE=disabled
DB_SSL_CA=/path/to/ca-cert.pem
DB_SSL_CERT=/path/to/client-cert.pem
DB_SSL_KEY=/path/to/client-key.pem

# 字符集配置
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

## 数据库驱动

### MySQL配置

```php
'mysql' => [
    'driver' => 'mysql',
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
    'engine' => 'InnoDB',
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => true,
    ]) : [],
    'pool' => [
        'min_connections' => 5,
        'max_connections' => 25,
        'acquire_timeout' => 60,
        'idle_timeout' => 300,
        'wait_timeout' => 10,
    ],
],
```

### PostgreSQL配置

```php
'pgsql' => [
    'driver' => 'pgsql',
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
    'options' => extension_loaded('pdo_pgsql') ? array_filter([
        PDO::PGSQL_ATTR_INIT_COMMAND => "SET NAMES utf8",
    ]) : [],
    'pool' => [
        'min_connections' => 5,
        'max_connections' => 25,
        'acquire_timeout' => 60,
        'idle_timeout' => 300,
        'wait_timeout' => 10,
    ],
],
```

### SQLite配置

```php
'sqlite' => [
    'driver' => 'sqlite',
    'database' => env('DB_DATABASE', database_path('database.sqlite')),
    'prefix' => '',
    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
    'options' => extension_loaded('pdo_sqlite') ? [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ] : [],
],
```

### Redis配置

```php
'redis' => [
    'client' => env('REDIS_CLIENT', 'phpredis'),
    'options' => [
        'cluster' => env('REDIS_CLUSTER', 'redis'),
        'prefix' => env('REDIS_PREFIX', 'webman_filament_'),
    ],
    'default' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_DB', '0'),
        'timeout' => 5.0,
        'read_timeout' => 5.0,
        'persistent' => false,
    ],
    'cache' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_CACHE_DB', '1'),
        'timeout' => 5.0,
        'read_timeout' => 5.0,
        'persistent' => false,
    ],
    'session' => [
        'url' => env('REDIS_URL'),
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', '6379'),
        'database' => env('REDIS_SESSION_DB', '2'),
        'timeout' => 5.0,
        'read_timeout' => 5.0,
        'persistent' => false,
    ],
],
```

## 连接池配置

### MySQL连接池优化

```php
'mysql' => [
    // ... 其他配置
    'pool' => [
        'min_connections' => env('DB_POOL_MIN', 5),
        'max_connections' => env('DB_POOL_MAX', 25),
        'acquire_timeout' => env('DB_POOL_ACQUIRE_TIMEOUT', 60),
        'idle_timeout' => env('DB_POOL_IDLE_TIMEOUT', 300),
        'wait_timeout' => env('DB_POOL_WAIT_TIMEOUT', 10),
        'max_idle_time' => env('DB_POOL_MAX_IDLE_TIME', 600),
        'validation_query' => 'SELECT 1',
        'validation_query_timeout' => 5,
        'leak_detection_threshold' => 60,
    ],
    'options' => [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        PDO::ATTR_TIMEOUT => 30,
        PDO::ATTR_PERSISTENT => true,
        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        PDO::MYSQL_ATTR_FOUND_ROWS => true,
    ],
],
```

### 连接池监控

```php
// 监控连接池状态
'pool_monitor' => [
    'enabled' => env('DB_POOL_MONITOR_ENABLED', true),
    'log_level' => env('DB_POOL_LOG_LEVEL', 'warning'),
    'metrics' => [
        'connections_active' => true,
        'connections_idle' => true,
        'wait_time' => true,
        'acquire_time' => true,
    ],
],
```

## 迁移配置

### 迁移基本配置

```php
// config/database.php
'migrations' => [
    'table' => 'migrations',
    'path' => database_path('migrations'),
    'recursive' => true,
    'timestamp' => true,
    'batch_size' => 10,
    'chunk_size' => 1000,
],

// 迁移锁定配置
'migration_lock' => [
    'enabled' => true,
    'timeout' => 30,
    'table' => 'migration_locks',
],
```

### 迁移最佳实践

1. **版本控制**：所有迁移文件都应提交到版本控制系统
2. **测试**：在生产环境前在测试环境运行所有迁移
3. **备份**：迁移前备份数据库
4. **回滚**：确保每个迁移都有对应的回滚操作

```php
// 示例迁移
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
            
            $table->index('email');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
```

## 种子配置

### 种子文件配置

```php
// config/database.php
'seeders' => [
    'path' => database_path('seeders'),
    'recursive' => true,
    'timestamp' => true,
    'batch_size' => 100,
],

// 种子环境配置
'seeding' => [
    'environments' => ['local', 'testing'],
    'exclude_tables' => ['users', 'password_resets'],
    'include_sample_data' => true,
    'sample_data_ratio' => 0.1, // 10%的样本数据
],
```

### 种子最佳实践

```php
// 示例种子文件
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // 创建管理员用户
        User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'role' => 'admin',
        ]);

        // 创建普通用户
        User::factory()
            ->count(50)
            ->create([
                'role' => 'user',
            ]);
    }
}
```

## 性能优化

### 查询优化

```php
// 数据库查询优化配置
'query_optimization' => [
    'slow_query_log' => env('DB_SLOW_QUERY_LOG', true),
    'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 2.0), // 秒
    'explain_analysis' => env('DB_EXPLAIN_ANALYSIS', true),
    'query_cache' => [
        'enabled' => env('DB_QUERY_CACHE_ENABLED', true),
        'ttl' => env('DB_QUERY_CACHE_TTL', 3600), // 1小时
        'max_size' => env('DB_QUERY_CACHE_MAX_SIZE', 100), // MB
    ],
],

// 索引优化
'index_optimization' => [
    'auto_analyze' => true,
    'auto_optimize' => true,
    'maintenance_window' => '02:00-04:00',
    'index_usage_threshold' => 0.1, // 10%
],
```

### 缓存配置

```php
// 查询缓存
'query_cache' => [
    'driver' => env('DB_QUERY_CACHE_DRIVER', 'redis'),
    'ttl' => env('DB_QUERY_CACHE_TTL', 3600),
    'prefix' => 'db_query_cache:',
    'tags' => true,
],

// 结果缓存
'result_cache' => [
    'enabled' => true,
    'ttl' => 300, // 5分钟
    'max_memory' => '128M',
    'compression' => true,
],
```

## 备份策略

### 自动备份配置

```php
// config/backup.php
'backup' => [
    'enabled' => env('DB_BACKUP_ENABLED', true),
    'schedule' => [
        'full_backup' => '0 2 * * *', // 每天凌晨2点
        'incremental_backup' => '0 */6 * * *', // 每6小时
    ],
    'retention' => [
        'daily' => 7,
        'weekly' => 4,
        'monthly' => 12,
        'yearly' => 3,
    ],
    'storage' => [
        'local' => [
            'path' => storage_path('backups'),
            'compression' => 'gzip',
        ],
        's3' => [
            'bucket' => env('AWS_BACKUP_BUCKET'),
            'region' => env('AWS_DEFAULT_REGION'),
            'encryption' => 'AES256',
        ],
    ],
],
```

### 备份脚本

```bash
#!/bin/bash
# backup-database.sh

# 配置变量
DB_NAME="webman_filament"
DB_USER="root"
DB_PASS="your_password"
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)

# 创建备份目录
mkdir -p $BACKUP_DIR

# 执行备份
mysqldump -u$DB_USER -p$DB_PASS \
    --single-transaction \
    --routines \
    --triggers \
    --events \
    $DB_NAME | gzip > $BACKUP_DIR/${DB_NAME}_$DATE.sql.gz

# 删除7天前的备份
find $BACKUP_DIR -name "${DB_NAME}_*.sql.gz" -mtime +7 -delete

echo "Database backup completed: ${DB_NAME}_$DATE.sql.gz"
```

## 监控和维护

### 数据库监控

```php
// config/database.php
'monitoring' => [
    'enabled' => env('DB_MONITORING_ENABLED', true),
    'metrics' => [
        'connections' => [
            'enabled' => true,
            'threshold' => 80, // 连接数阈值百分比
        ],
        'slow_queries' => [
            'enabled' => true,
            'threshold' => 2.0, // 秒
            'alert_threshold' => 10, // 每小时查询数
        ],
        'locks' => [
            'enabled' => true,
            'threshold' => 5, // 秒
        ],
        'disk_usage' => [
            'enabled' => true,
            'threshold' => 85, // 磁盘使用率百分比
        ],
    ],
    'alerts' => [
        'email' => env('DB_ALERT_EMAIL'),
        'slack_webhook' => env('DB_ALERT_SLACK_WEBHOOK'),
        'sms' => env('DB_ALERT_SMS'),
    ],
],
```

### 维护任务

```php
// config/maintenance.php
'maintenance' => [
    'schedule' => [
        'optimize_tables' => '0 3 * * 0', // 每周日凌晨3点
        'analyze_tables' => '0 4 * * *', // 每天凌晨4点
        'check_integrity' => '0 5 * * *', // 每天凌晨5点
        'update_statistics' => '0 6 * * *', // 每天凌晨6点
    ],
    'notification' => [
        'email' => env('MAINTENANCE_NOTIFICATION_EMAIL'),
        'slack_webhook' => env('MAINTENANCE_SLACK_WEBHOOK'),
    ],
],
```

## 最佳实践

### 1. 安全性

- 使用强密码
- 限制数据库用户权限
- 启用SSL连接
- 定期更新数据库软件

### 2. 性能

- 合理设计索引
- 使用连接池
- 启用查询缓存
- 定期优化表

### 3. 备份

- 定期自动备份
- 测试备份恢复
- 多地点存储备份
- 监控备份状态

### 4. 监控

- 实时监控数据库性能
- 设置告警阈值
- 分析慢查询日志
- 跟踪连接状态

## 故障排除

### 常见问题

1. **连接超时**
   ```php
   // 增加连接超时时间
   'options' => [
       PDO::ATTR_TIMEOUT => 60,
       PDO::MYSQL_ATTR_INIT_COMMAND => "SET SESSION timeout=60",
   ]
   ```

2. **字符集问题**
   ```php
   // 确保使用正确的字符集
   'charset' => 'utf8mb4',
   'collation' => 'utf8mb4_unicode_ci',
   ```

3. **连接池耗尽**
   ```php
   // 调整连接池大小
   'pool' => [
       'max_connections' => 50,
       'idle_timeout' => 600,
   ]
   ```

### 调试命令

```bash
# 检查数据库连接
php artisan tinker
DB::connection()->getPdo();

# 查看数据库状态
SHOW PROCESSLIST;
SHOW STATUS LIKE 'Threads%';
SHOW STATUS LIKE 'Connections';

# 优化表
OPTIMIZE TABLE table_name;

# 分析表
ANALYZE TABLE table_name;

# 检查表
CHECK TABLE table_name;
```