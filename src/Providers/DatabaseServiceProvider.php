<?php

namespace App\Providers;

use App\Adapter\MigrationAdapter;
use App\Adapter\ModelAdapter;
use App\Handler\DatabaseHandler;
use Illuminate\Support\ServiceProvider;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\MigrationRepository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;

/**
 * 数据库服务提供者
 * 注册数据库迁移适配器和相关服务
 */
class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册数据库管理器
        $this->app->singleton('db', function ($app) {
            return new DatabaseManager($app, $app['db.factory']);
        });

        // 注册数据库处理器
        $this->app->singleton(DatabaseHandler::class, function ($app) {
            return new DatabaseHandler($app['db']);
        });

        // 注册迁移适配器
        $this->app->singleton(MigrationAdapter::class, function ($app) {
            return new MigrationAdapter(
                $app['db'],
                $app['files'],
                $app['migration.repository']
            );
        });

        // 注册模型适配器
        $this->app->singleton(ModelAdapter::class, function ($app) {
            return new ModelAdapter();
        });

        // 注册迁移存储库
        $this->app->singleton('migration.repository', function ($app) {
            $table = $app['config']['database.migrations'];
            return new MigrationRepository($app['db'], $table);
        });

        // 注册文件系统
        $this->app->singleton('files', function () {
            return new Filesystem();
        });

        // 注册数据库工厂
        $this->app->singleton('db.factory', function ($app) {
            return new \Illuminate\Database\Connectors\ConnectionFactory($app);
        });

        // 注册数据库连接解析器
        $this->app->singleton('db.connection.resolver', function ($app) {
            return new \Illuminate\Database\ConnectionResolver($app['config']['database']);
        });
    }

    /**
     * 启动服务
     */
    public function boot(): void
    {
        // 发布配置文件
        $this->publishes([
            __DIR__.'/../../database/config/database.php' => config_path('database.php'),
        ], 'database-config');

        // 发布迁移目录
        $this->publishes([
            __DIR__.'/../../database/migrations' => database_path('migrations'),
        ], 'database-migrations');

        // 注册命令
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Command\MigrateCommand::class,
            ]);
        }

        // 配置数据库事件监听
        $this->configureDatabaseEvents();

        // 初始化数据库连接池
        $this->initializeConnectionPool();
    }

    /**
     * 配置数据库事件
     */
    protected function configureDatabaseEvents(): void
    {
        // 监听查询事件（用于调试和监控）
        DB::listen(function ($query) {
            if (config('database.database_handler.monitoring.enabled', false)) {
                $time = $query->time;
                $threshold = config('database.database_handler.monitoring.slow_query_threshold', 1000);
                
                if ($time > $threshold) {
                    \Log::warning('Slow query detected', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $time,
                        'connection' => $query->connectionName,
                    ]);
                }
            }
        });

        // 监听连接事件
        DB::beforeExecuting(function ($query, $bindings, $connection) {
            \Log::debug('Executing query', [
                'sql' => $query,
                'connection' => $connection->getName(),
            ]);
        });
    }

    /**
     * 初始化连接池
     */
    protected function initializeConnectionPool(): void
    {
        $handler = app(DatabaseHandler::class);
        
        // 测试所有连接
        $connections = config('database.connections', []);
        
        foreach ($connections as $name => $config) {
            try {
                $result = $handler->testConnection($name);
                
                if ($result['success']) {
                    // 优化连接
                    $handler->optimizeConnection($name);
                    
                    \Log::info('Database connection initialized', [
                        'connection' => $name,
                        'driver' => $result['driver'],
                        'response_time' => $result['response_time'],
                    ]);
                } else {
                    \Log::error('Failed to initialize database connection', [
                        'connection' => $name,
                        'error' => $result['error'],
                    ]);
                }
            } catch (\Exception $e) {
                \Log::error('Database connection initialization error', [
                    'connection' => $name,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 获取提供的服务
     */
    public function provides(): array
    {
        return [
            'db',
            DatabaseHandler::class,
            MigrationAdapter::class,
            ModelAdapter::class,
            'migration.repository',
            'files',
            'db.factory',
            'db.connection.resolver',
        ];
    }
}