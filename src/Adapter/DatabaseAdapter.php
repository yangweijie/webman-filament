<?php

namespace FilamentWebmanAdapter\Adapter;

use Illuminate\Database\Connection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\QueryException;
use PDO;
use PDOException;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 数据库适配器
 * 
 * 在 webman 的连接池与长连接模型下，保持 Eloquent 的行为一致
 * 支持连接池管理、事务处理、查询优化、迁移管理
 */
class DatabaseAdapter
{
    private array $connections = [];
    private array $configurations = [];
    private LoggerInterface $logger;
    private array $statistics = [
        'total_connections' => 0,
        'active_connections' => 0,
        'failed_connections' => 0,
        'queries_executed' => 0,
        'transactions_rolled_back' => 0,
        'transactions_committed' => 0
    ];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->log('info', 'DatabaseAdapter initialized');
    }

    /**
     * 配置数据库连接
     */
    public function configureConnection(string $name, array $config): void
    {
        try {
            $this->configurations[$name] = array_merge([
                'driver' => 'mysql',
                'host' => '127.0.0.1',
                'port' => 3306,
                'database' => 'app',
                'username' => 'root',
                'password' => '',
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
                'engine' => null,
                'pool' => [
                    'min_connections' => 2,
                    'max_connections' => 10,
                    'connection_timeout' => 3,
                    'idle_timeout' => 300
                ]
            ], $config);

            $this->log('info', "Database connection configured: {$name}", ['config' => $config]);
        } catch (Throwable $e) {
            $this->log('error', "Failed to configure database connection {$name}: " . $e->getMessage());
            throw new \RuntimeException("Failed to configure database connection {$name}", 0, $e);
        }
    }

    /**
     * 获取数据库连接
     */
    public function getConnection(string $name = 'default'): ConnectionInterface
    {
        try {
            if (!isset($this->connections[$name])) {
                $this->connections[$name] = $this->createConnection($name);
                $this->statistics['active_connections']++;
            }

            $this->statistics['total_connections']++;
            $this->log('debug', "Database connection retrieved: {$name}");
            return $this->connections[$name];
        } catch (Throwable $e) {
            $this->statistics['failed_connections']++;
            $this->log('error', "Failed to get database connection {$name}: " . $e->getMessage());
            throw new \RuntimeException("Failed to get database connection {$name}", 0, $e);
        }
    }

    /**
     * 创建数据库连接
     */
    private function createConnection(string $name): ConnectionInterface
    {
        $config = $this->configurations[$name] ?? $this->configurations['default'] ?? [];
        
        if (empty($config)) {
            throw new \InvalidArgumentException("No configuration found for connection: {$name}");
        }

        $dsn = $this->buildDsn($config);
        $pdo = $this->createPdoConnection($dsn, $config);

        $connection = new Connection($pdo, $config['database'], $config['prefix']);
        
        // 配置连接选项
        $this->configureConnectionOptions($connection, $config);
        
        // 测试连接
        $this->testConnection($connection);
        
        return $connection;
    }

    /**
     * 构建 DSN 字符串
     */
    private function buildDsn(array $config): string
    {
        $driver = $config['driver'];
        
        switch ($driver) {
            case 'mysql':
                return "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            
            case 'sqlite':
                return "sqlite:{$config['database']}";
            
            case 'pgsql':
                return "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            
            default:
                throw new \InvalidArgumentException("Unsupported database driver: {$driver}");
        }
    }

    /**
     * 创建 PDO 连接
     */
    private function createPdoConnection(string $dsn, array $config): PDO
    {
        try {
            $username = $config['username'] ?? '';
            $password = $config['password'] ?? '';
            $options = $config['options'] ?? [];

            // 设置默认选项
            $defaultOptions = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$config['charset']} COLLATE {$config['collation']}"
            ];

            $options = array_merge($defaultOptions, $options);
            
            return new PDO($dsn, $username, $password, $options);
        } catch (PDOException $e) {
            $this->log('error', "PDO connection failed: " . $e->getMessage());
            throw new \RuntimeException("PDO connection failed", 0, $e);
        }
    }

    /**
     * 配置连接选项
     */
    private function configureConnectionOptions(Connection $connection, array $config): void
    {
        // 设置查询构建器选项
        if (isset($config['strict']) && $config['strict']) {
            $connection->statement("SET sql_mode='STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO'");
        }

        // 设置时区
        if (isset($config['timezone'])) {
            $connection->statement("SET time_zone = '{$config['timezone']}'");
        }

        // 设置字符集
        if (isset($config['charset'])) {
            $connection->statement("SET NAMES {$config['charset']} COLLATE {$config['collation']}");
        }
    }

    /**
     * 测试连接
     */
    private function testConnection(ConnectionInterface $connection): void
    {
        try {
            $connection->select('SELECT 1');
            $this->log('debug', 'Database connection test passed');
        } catch (QueryException $e) {
            $this->log('error', 'Database connection test failed: ' . $e->getMessage());
            throw new \RuntimeException("Database connection test failed", 0, $e);
        }
    }

    /**
     * 执行事务
     */
    public function transaction(callable $callback, int $attempts = 1, string $name = 'default'): mixed
    {
        $connection = $this->getConnection($name);
        
        return $connection->transaction(function() use ($callback, $name, &$attempts) {
            try {
                $result = $callback($connection);
                $this->statistics['transactions_committed']++;
                $this->log('debug', "Transaction committed for connection: {$name}");
                return $result;
            } catch (Throwable $e) {
                $this->statistics['transactions_rolled_back']++;
                $this->log('error', "Transaction rolled back for connection {$name}: " . $e->getMessage());
                throw $e;
            }
        }, $attempts);
    }

    /**
     * 执行原生查询
     */
    public function query(string $sql, array $bindings = [], string $name = 'default'): array
    {
        try {
            $connection = $this->getConnection($name);
            $result = $connection->select($sql, $bindings);
            $this->statistics['queries_executed']++;
            
            $this->log('debug', "Query executed for connection {$name}", [
                'sql' => $sql,
                'bindings' => $bindings,
                'result_count' => count($result)
            ]);
            
            return $result;
        } catch (QueryException $e) {
            $this->log('error', "Query failed for connection {$name}: " . $e->getMessage(), [
                'sql' => $sql,
                'bindings' => $bindings
            ]);
            throw new \RuntimeException("Query execution failed", 0, $e);
        }
    }

    /**
     * 获取 Eloquent 模型实例
     */
    public function getModel(string $modelClass): Model
    {
        try {
            if (!class_exists($modelClass)) {
                throw new \InvalidArgumentException("Model class not found: {$modelClass}");
            }

            $connection = $this->getConnection();
            $model = new $modelClass();
            
            if (!$model instanceof Model) {
                throw new \InvalidArgumentException("Class {$modelClass} is not an Eloquent model");
            }

            // 设置连接
            $model->setConnection($connection);
            
            $this->log('debug', "Eloquent model created: {$modelClass}");
            return $model;
        } catch (Throwable $e) {
            $this->log('error', "Failed to create model {$modelClass}: " . $e->getMessage());
            throw new \RuntimeException("Failed to create model {$modelClass}", 0, $e);
        }
    }

    /**
     * 批量插入
     */
    public function batchInsert(string $table, array $data, string $name = 'default'): bool
    {
        try {
            if (empty($data)) {
                return true;
            }

            $connection = $this->getConnection($name);
            $columns = array_keys($data[0]);
            $placeholders = '(' . implode(',', array_fill(0, count($columns), '?')) . ')';
            $sql = "INSERT INTO {$table} (" . implode(',', $columns) . ") VALUES ";
            
            $values = [];
            $bindings = [];
            
            foreach ($data as $row) {
                $values[] = $placeholders;
                $bindings = array_merge($bindings, array_values($row));
            }
            
            $sql .= implode(',', $values);
            
            $connection->insert($sql, $bindings);
            
            $this->log('info', "Batch insert completed for table {$table}", [
                'rows' => count($data),
                'columns' => count($columns)
            ]);
            
            return true;
        } catch (Throwable $e) {
            $this->log('error', "Batch insert failed for table {$table}: " . $e->getMessage(), [
                'data_count' => count($data)
            ]);
            throw new \RuntimeException("Batch insert failed", 0, $e);
        }
    }

    /**
     * 执行迁移
     */
    public function runMigrations(array $migrations, string $name = 'default'): bool
    {
        try {
            return $this->transaction(function() use ($migrations, $name) {
                foreach ($migrations as $migration) {
                    $this->executeMigration($migration, $name);
                }
                return true;
            }, 1, $name);
        } catch (Throwable $e) {
            $this->log('error', "Migration failed: " . $e->getMessage(), ['migrations' => $migrations]);
            throw new \RuntimeException("Migration execution failed", 0, $e);
        }
    }

    /**
     * 执行单个迁移
     */
    private function executeMigration(array $migration, string $name): void
    {
        $connection = $this->getConnection($name);
        
        // 检查迁移表是否存在
        if (!$connection->schema()->hasTable('migrations')) {
            $connection->schema()->create('migrations', function ($table) {
                $table->increments('id');
                $table->string('migration');
                $table->integer('batch');
            });
        }
        
        // 执行迁移 SQL
        foreach ($migration['sql'] as $sql) {
            $connection->statement($sql);
        }
        
        // 记录迁移
        $connection->table('migrations')->insert([
            'migration' => $migration['name'],
            'batch' => $migration['batch'] ?? 1,
            'created_at' => now()
        ]);
        
        $this->log('info', "Migration executed: {$migration['name']}");
    }

    /**
     * 释放连接
     */
    public function releaseConnection(string $name): void
    {
        if (isset($this->connections[$name])) {
            unset($this->connections[$name]);
            $this->statistics['active_connections']--;
            $this->log('debug', "Connection released: {$name}");
        }
    }

    /**
     * 获取统计信息
     */
    public function getStatistics(): array
    {
        return array_merge($this->statistics, [
            'configured_connections' => array_keys($this->configurations),
            'active_connection_names' => array_keys($this->connections)
        ]);
    }

    /**
     * 清理所有连接
     */
    public function clearConnections(): void
    {
        foreach ($this->connections as $name => $connection) {
            $this->releaseConnection($name);
        }
        
        $this->log('info', 'All database connections cleared');
    }

    /**
     * 获取连接池状态
     */
    public function getConnectionPoolStatus(string $name = 'default'): array
    {
        $config = $this->configurations[$name] ?? [];
        $poolConfig = $config['pool'] ?? [];
        
        return [
            'name' => $name,
            'is_active' => isset($this->connections[$name]),
            'pool_config' => $poolConfig,
            'statistics' => $this->statistics
        ];
    }

    /**
     * 日志记录
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}