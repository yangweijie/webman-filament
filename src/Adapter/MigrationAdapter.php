<?php

namespace App\Adapter;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Database\ConnectionResolverInterface;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 数据库迁移适配器
 * 负责处理数据库迁移的兼容性和适配
 */
class MigrationAdapter
{
    /**
     * 数据库连接解析器
     */
    protected ConnectionResolverInterface $db;

    /**
     * 文件系统实例
     */
    protected Filesystem $files;

    /**
     * 迁移存储库
     */
    protected MigrationRepositoryInterface $repository;

    /**
     * 迁移器实例
     */
    protected Migrator $migrator;

    /**
     * 支持的数据库类型
     */
    protected array $supportedDrivers = ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];

    /**
     * 构造函数
     */
    public function __construct(
        ConnectionResolverInterface $db,
        Filesystem $files,
        MigrationRepositoryInterface $repository
    ) {
        $this->db = $db;
        $this->files = $files;
        $this->repository = $repository;
        $this->migrator = new Migrator($repository, $db, $files);
    }

    /**
     * 检查数据库连接
     */
    public function checkConnection(): bool
    {
        try {
            $connection = $this->db->connection();
            $connection->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 获取数据库连接
     */
    public function getConnection(): ConnectionInterface
    {
        return $this->db->connection();
    }

    /**
     * 检查迁移表是否存在
     */
    public function migrationTableExists(): bool
    {
        return $this->repository->repositoryExists();
    }

    /**
     * 创建迁移表
     */
    public function createMigrationTable(): void
    {
        $this->repository->createRepository();
    }

    /**
     * 获取待执行的迁移
     */
    public function getPendingMigrations(): array
    {
        return $this->migrator->getPending();
    }

    /**
     * 执行迁移
     */
    public function runMigrations(array $paths = []): array
    {
        $paths = $paths ?: [database_path('migrations')];
        
        $this->migrator->run($paths);
        
        return $this->migrator->getNotes();
    }

    /**
     * 回滚迁移
     */
    public function rollbackMigrations(): array
    {
        $this->migrator->rollback();
        
        return $this->migrator->getNotes();
    }

    /**
     * 重置迁移
     */
    public function resetMigrations(): array
    {
        $this->migrator->reset();
        
        return $this->migrator->getNotes();
    }

    /**
     * 获取迁移状态
     */
    public function getMigrationStatus(): array
    {
        $ran = $this->repository->getRan();
        $batches = $this->repository->getMigrationBatches();
        
        $migrations = [];
        foreach ($this->files->files(database_path('migrations')) as $file) {
            $migration = $this->files->name($file);
            $migrations[] = [
                'migration' => $migration,
                'batch' => $batches[$migration] ?? null,
                'ran' => in_array($migration, $ran),
            ];
        }

        return $migrations;
    }

    /**
     * 检查数据库驱动是否支持
     */
    public function isDriverSupported(string $driver): bool
    {
        return in_array($driver, $this->supportedDrivers);
    }

    /**
     * 获取数据库驱动信息
     */
    public function getDriverInfo(): array
    {
        $connection = $this->getConnection();
        $driver = $connection->getDriverName();
        
        return [
            'driver' => $driver,
            'supported' => $this->isDriverSupported($driver),
            'version' => $connection->getPdo()->getAttribute(\PDO::ATTR_SERVER_VERSION),
        ];
    }

    /**
     * 检查数据库连接池配置
     */
    public function checkConnectionPool(): array
    {
        $connection = $this->getConnection();
        $config = $connection->getConfig();
        
        return [
            'pool_size' => $config['pool_size'] ?? null,
            'max_connections' => $config['max_connections'] ?? null,
            'connection_timeout' => $config['connection_timeout'] ?? null,
            'idle_timeout' => $config['idle_timeout'] ?? null,
        ];
    }

    /**
     * 验证迁移文件格式
     */
    public function validateMigrationFile(string $file): bool
    {
        $pattern = '/^\d{4}_\d{2}_\d{2}_\d{6}_.*\.php$/';
        return preg_match($pattern, basename($file)) === 1;
    }

    /**
     * 获取迁移文件列表
     */
    public function getMigrationFiles(): array
    {
        $files = $this->files->files(database_path('migrations'));
        $migrationFiles = [];
        
        foreach ($files as $file) {
            if ($this->validateMigrationFile($file)) {
                $migrationFiles[] = $file;
            }
        }
        
        sort($migrationFiles);
        
        return $migrationFiles;
    }

    /**
     * 创建迁移文件
     */
    public function createMigration(string $name, string $table = null): string
    {
        $path = $this->migrator->create($name, $table);
        
        return $path;
    }

    /**
     * 获取数据库表列表
     */
    public function getTables(): array
    {
        return Schema::getAllTables();
    }

    /**
     * 检查表是否存在
     */
    public function tableExists(string $table): bool
    {
        return Schema::hasTable($table);
    }

    /**
     * 获取表结构
     */
    public function getTableStructure(string $table): array
    {
        if (!$this->tableExists($table)) {
            return [];
        }

        return Schema::getColumnListing($table);
    }

    /**
     * 执行原生SQL查询
     */
    public function executeRaw(string $sql, array $bindings = []): mixed
    {
        return DB::connection()->getPdo()->prepare($sql)->execute($bindings);
    }

    /**
     * 获取数据库信息
     */
    public function getDatabaseInfo(): array
    {
        $connection = $this->getConnection();
        $driver = $connection->getDriverName();
        
        $info = [
            'driver' => $driver,
            'database' => $connection->getDatabaseName(),
            'charset' => null,
            'collation' => null,
        ];

        switch ($driver) {
            case 'mysql':
                $result = $this->executeRaw('SELECT @@character_set_database as charset, @@collation_database as collation');
                if ($result) {
                    $info['charset'] = $result['charset'] ?? null;
                    $info['collation'] = $result['collation'] ?? null;
                }
                break;
            case 'pgsql':
                $result = $this->executeRaw('SHOW server_encoding');
                if ($result) {
                    $info['charset'] = $result['server_encoding'] ?? null;
                }
                break;
        }

        return $info;
    }
}