<?php

namespace App\Handler;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use PDO;
use PDOException;

/**
 * 数据库处理器
 * 负责数据库连接池管理和操作
 */
class DatabaseHandler
{
    /**
     * 数据库管理器
     */
    protected DatabaseManager $db;

    /**
     * 连接池配置
     */
    protected array $poolConfig = [
        'min_connections' => 5,
        'max_connections' => 20,
        'acquire_timeout' => 60,
        'idle_timeout' => 600,
        'health_check_interval' => 60,
    ];

    /**
     * 健康检查配置
     */
    protected array $healthCheckConfig = [
        'enabled' => true,
        'interval' => 60, // 秒
        'timeout' => 5, // 秒
    ];

    /**
     * 连接状态缓存
     */
    protected array $connectionStatus = [];

    /**
     * 构造函数
     */
    public function __construct(DatabaseManager $db)
    {
        $this->db = $db;
        $this->initializeHealthCheck();
    }

    /**
     * 获取数据库连接
     */
    public function getConnection(?string $name = null): ConnectionInterface
    {
        return $this->db->connection($name);
    }

    /**
     * 测试数据库连接
     */
    public function testConnection(?string $connectionName = null): array
    {
        $connectionName = $connectionName ?: config('database.default');
        $connection = $this->getConnection($connectionName);
        
        $startTime = microtime(true);
        $result = [
            'connection' => $connectionName,
            'success' => false,
            'response_time' => 0,
            'error' => null,
            'driver' => null,
            'version' => null,
        ];

        try {
            $pdo = $connection->getPdo();
            $result['success'] = true;
            $result['driver'] = $connection->getDriverName();
            $result['version'] = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
            $result['response_time'] = round((microtime(true) - $startTime) * 1000, 2);
            
            // 更新连接状态
            $this->updateConnectionStatus($connectionName, true);
            
        } catch (PDOException $e) {
            $result['error'] = $e->getMessage();
            $this->updateConnectionStatus($connectionName, false);
            
            Log::error('Database connection test failed', [
                'connection' => $connectionName,
                'error' => $e->getMessage(),
            ]);
        }

        return $result;
    }

    /**
     * 获取所有连接状态
     */
    public function getAllConnectionStatus(): array
    {
        $connections = config('database.connections', []);
        $status = [];

        foreach ($connections as $name => $config) {
            $status[$name] = $this->testConnection($name);
        }

        return $status;
    }

    /**
     * 执行事务
     */
    public function transaction(\Closure $callback, int $attempts = 1, ?string $connectionName = null): mixed
    {
        $connection = $this->getConnection($connectionName);
        
        return $connection->transaction($callback, $attempts);
    }

    /**
     * 开始事务
     */
    public function beginTransaction(?string $connectionName = null): bool
    {
        try {
            $connection = $this->getConnection($connectionName);
            return $connection->getPdo()->beginTransaction();
        } catch (PDOException $e) {
            Log::error('Begin transaction failed', [
                'connection' => $connectionName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 提交事务
     */
    public function commit(?string $connectionName = null): bool
    {
        try {
            $connection = $this->getConnection($connectionName);
            return $connection->getPdo()->commit();
        } catch (PDOException $e) {
            Log::error('Commit transaction failed', [
                'connection' => $connectionName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 回滚事务
     */
    public function rollback(?string $connectionName = null): bool
    {
        try {
            $connection = $this->getConnection($connectionName);
            return $connection->getPdo()->rollBack();
        } catch (PDOException $e) {
            Log::error('Rollback transaction failed', [
                'connection' => $connectionName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 执行原生SQL
     */
    public function executeRaw(string $sql, array $bindings = [], ?string $connectionName = null): mixed
    {
        try {
            $connection = $this->getConnection($connectionName);
            return $connection->select($sql, $bindings);
        } catch (\Exception $e) {
            Log::error('Execute raw SQL failed', [
                'sql' => $sql,
                'bindings' => $bindings,
                'connection' => $connectionName,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 获取数据库信息
     */
    public function getDatabaseInfo(?string $connectionName = null): array
    {
        $connection = $this->getConnection($connectionName);
        $driver = $connection->getDriverName();
        
        $info = [
            'connection' => $connectionName ?: config('database.default'),
            'driver' => $driver,
            'database' => $connection->getDatabaseName(),
            'host' => null,
            'port' => null,
            'charset' => null,
            'collation' => null,
        ];

        try {
            $config = $connection->getConfig();
            $info['host'] = $config['host'] ?? null;
            $info['port'] = $config['port'] ?? null;

            switch ($driver) {
                case 'mysql':
                    $result = $this->executeRaw('SELECT @@character_set_database as charset, @@collation_database as collation');
                    if ($result) {
                        $info['charset'] = $result[0]['charset'] ?? null;
                        $info['collation'] = $result[0]['collation'] ?? null;
                    }
                    break;
                    
                case 'pgsql':
                    $result = $this->executeRaw('SHOW server_encoding');
                    if ($result) {
                        $info['charset'] = $result[0]['server_encoding'] ?? null;
                    }
                    break;
                    
                case 'sqlite':
                    $info['charset'] = 'UTF-8';
                    break;
            }
        } catch (\Exception $e) {
            Log::warning('Failed to get database info', [
                'connection' => $connectionName,
                'error' => $e->getMessage(),
            ]);
        }

        return $info;
    }

    /**
     * 获取数据库表列表
     */
    public function getTables(?string $connectionName = null): array
    {
        $connection = $this->getConnection($connectionName);
        $driver = $connection->getDriverName();
        
        try {
            switch ($driver) {
                case 'mysql':
                    $sql = "SELECT TABLE_NAME as name FROM information_schema.tables WHERE table_schema = ?";
                    $bindings = [$connection->getDatabaseName()];
                    break;
                    
                case 'pgsql':
                    $sql = "SELECT tablename as name FROM pg_tables WHERE schemaname = 'public'";
                    $bindings = [];
                    break;
                    
                case 'sqlite':
                    $sql = "SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'";
                    $bindings = [];
                    break;
                    
                default:
                    throw new \Exception("Unsupported database driver: {$driver}");
            }
            
            $result = $this->executeRaw($sql, $bindings, $connectionName);
            return array_column($result, 'name');
            
        } catch (\Exception $e) {
            Log::error('Failed to get tables', [
                'connection' => $connectionName,
                'driver' => $driver,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * 获取表结构
     */
    public function getTableStructure(string $table, ?string $connectionName = null): array
    {
        $connection = $this->getConnection($connectionName);
        $driver = $connection->getDriverName();
        
        try {
            switch ($driver) {
                case 'mysql':
                    $sql = "DESCRIBE `{$table}`";
                    break;
                    
                case 'pgsql':
                    $sql = "SELECT column_name, data_type, is_nullable, column_default 
                           FROM information_schema.columns 
                           WHERE table_name = ? AND table_schema = 'public'";
                    $bindings = [$table];
                    break;
                    
                case 'sqlite':
                    $sql = "PRAGMA table_info(`{$table}`)";
                    $bindings = [];
                    break;
                    
                default:
                    throw new \Exception("Unsupported database driver: {$driver}");
            }
            
            $result = $this->executeRaw($sql, $bindings, $connectionName);
            
            $columns = [];
            foreach ($result as $row) {
                $columns[] = [
                    'name' => $row['Field'] ?? $row['column_name'] ?? $row['name'] ?? '',
                    'type' => $row['Type'] ?? $row['data_type'] ?? '',
                    'null' => ($row['Null'] ?? $row['is_nullable'] ?? '') === 'YES',
                    'key' => $row['Key'] ?? '',
                    'default' => $row['Default'] ?? $row['column_default'] ?? null,
                    'extra' => $row['Extra'] ?? '',
                ];
            }
            
            return $columns;
            
        } catch (\Exception $e) {
            Log::error('Failed to get table structure', [
                'table' => $table,
                'connection' => $connectionName,
                'driver' => $driver,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * 检查表是否存在
     */
    public function tableExists(string $table, ?string $connectionName = null): bool
    {
        $tables = $this->getTables($connectionName);
        return in_array($table, $tables);
    }

    /**
     * 获取连接池统计信息
     */
    public function getPoolStatistics(?string $connectionName = null): array
    {
        $connection = $this->getConnection($connectionName);
        $config = $connection->getConfig();
        
        return [
            'min_connections' => $config['min_connections'] ?? $this->poolConfig['min_connections'],
            'max_connections' => $config['max_connections'] ?? $this->poolConfig['max_connections'],
            'acquire_timeout' => $config['acquire_timeout'] ?? $this->poolConfig['acquire_timeout'],
            'idle_timeout' => $config['idle_timeout'] ?? $this->poolConfig['idle_timeout'],
        ];
    }

    /**
     * 优化数据库连接
     */
    public function optimizeConnection(?string $connectionName = null): bool
    {
        try {
            $connection = $this->getConnection($connectionName);
            $driver = $connection->getDriverName();
            
            switch ($driver) {
                case 'mysql':
                    $this->executeRaw('SET SESSION wait_timeout = 28800', [], $connectionName);
                    $this->executeRaw('SET SESSION interactive_timeout = 28800', [], $connectionName);
                    break;
                    
                case 'pgsql':
                    $this->executeRaw('SET statement_timeout = 0', [], $connectionName);
                    break;
            }
            
            Log::info('Connection optimized', ['connection' => $connectionName]);
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to optimize connection', [
                'connection' => $connectionName,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * 初始化健康检查
     */
    protected function initializeHealthCheck(): void
    {
        if (!$this->healthCheckConfig['enabled']) {
            return;
        }

        // 这里可以添加定时健康检查逻辑
        // 例如使用 Laravel 的调度任务或守护进程
    }

    /**
     * 更新连接状态
     */
    protected function updateConnectionStatus(string $connectionName, bool $success): void
    {
        $this->connectionStatus[$connectionName] = [
            'success' => $success,
            'last_check' => time(),
            'consecutive_failures' => $success ? 0 : 
                ($this->connectionStatus[$connectionName]['consecutive_failures'] ?? 0) + 1,
        ];
    }

    /**
     * 获取连接健康状态
     */
    public function getConnectionHealth(?string $connectionName = null): array
    {
        if ($connectionName) {
            return $this->connectionStatus[$connectionName] ?? [
                'success' => false,
                'last_check' => 0,
                'consecutive_failures' => 0,
            ];
        }

        return $this->connectionStatus;
    }

    /**
     * 清理无效连接
     */
    public function cleanupConnections(): void
    {
        $connections = config('database.connections', []);
        
        foreach ($connections as $name => $config) {
            $status = $this->connectionStatus[$name] ?? null;
            
            if ($status && !$status['success'] && $status['consecutive_failures'] >= 3) {
                Log::warning('Cleaning up failed connection', [
                    'connection' => $name,
                    'consecutive_failures' => $status['consecutive_failures'],
                ]);
                
                // 强制重新连接
                $this->db->purge($name);
                unset($this->connectionStatus[$name]);
            }
        }
    }
}