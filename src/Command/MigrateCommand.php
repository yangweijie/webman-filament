<?php

namespace App\Command;

use App\Adapter\MigrationAdapter;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

/**
 * 迁移命令
 * 提供数据库迁移相关操作
 */
class MigrateCommand extends Command
{
    /**
     * 命令名称和签名
     */
    protected $signature = 'db:migrate 
                            {--step : 逐步执行迁移}
                            {--force : 强制执行迁移}
                            {--pretend : 模拟执行迁移}
                            {--path= : 指定迁移文件路径}
                            {--realpath : 迁移路径是否为绝对路径}
                            {--seed : 迁移后执行数据库填充}
                            {--seeder= : 指定数据库填充类}
                            {--check : 检查迁移状态}
                            {--rollback : 回滚迁移}
                            {--reset : 重置所有迁移}
                            {--status : 显示迁移状态}
                            {--fresh : 清空数据库后重新迁移}
                            {--create= : 创建新表}
                            {--table= : 修改现有表}';

    /**
     * 命令描述
     */
    protected $description = '数据库迁移管理命令';

    /**
     * 迁移适配器
     */
    protected MigrationAdapter $migrationAdapter;

    /**
     * 构造函数
     */
    public function __construct(MigrationAdapter $migrationAdapter)
    {
        parent::__construct();
        $this->migrationAdapter = $migrationAdapter;
    }

    /**
     * 执行命令
     */
    public function handle(): int
    {
        try {
            // 检查数据库连接
            if (!$this->checkDatabaseConnection()) {
                return 1;
            }

            // 检查迁移表
            $this->ensureMigrationTable();

            // 处理各种选项
            if ($this->option('check')) {
                return $this->checkMigrations();
            }

            if ($this->option('status')) {
                return $this->showMigrationStatus();
            }

            if ($this->option('rollback')) {
                return $this->rollbackMigrations();
            }

            if ($this->option('reset')) {
                return $this->resetMigrations();
            }

            if ($this->option('fresh')) {
                return $this->freshMigrations();
            }

            if ($this->option('create')) {
                return $this->createMigration();
            }

            // 执行迁移
            return $this->runMigrations();

        } catch (\Exception $e) {
            $this->error('迁移执行失败: ' . $e->getMessage());
            Log::error('Migration command failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return 1;
        }
    }

    /**
     * 检查数据库连接
     */
    protected function checkDatabaseConnection(): bool
    {
        $this->info('检查数据库连接...');

        if (!$this->migrationAdapter->checkConnection()) {
            $this->error('数据库连接失败');
            return false;
        }

        $driverInfo = $this->migrationAdapter->getDriverInfo();
        $this->info("数据库驱动: {$driverInfo['driver']}");
        $this->info("数据库版本: {$driverInfo['version']}");

        if (!$driverInfo['supported']) {
            $this->warn('当前数据库驱动可能不完全支持');
        }

        return true;
    }

    /**
     * 确保迁移表存在
     */
    protected function ensureMigrationTable(): void
    {
        if (!$this->migrationAdapter->migrationTableExists()) {
            $this->info('创建迁移表...');
            $this->migrationAdapter->createMigrationTable();
            $this->info('迁移表创建成功');
        }
    }

    /**
     * 检查迁移
     */
    protected function checkMigrations(): int
    {
        $this->info('检查迁移状态...');

        $pending = $this->migrationAdapter->getPendingMigrations();
        
        if (empty($pending)) {
            $this->info('没有待执行的迁移');
            return 0;
        }

        $this->warn('发现 ' . count($pending) . ' 个待执行的迁移:');
        foreach ($pending as $migration) {
            $this->line('  - ' . $migration);
        }

        return 0;
    }

    /**
     * 显示迁移状态
     */
    protected function showMigrationStatus(): int
    {
        $this->info('迁移状态:');
        $this->newLine();

        $migrations = $this->migrationAdapter->getMigrationStatus();
        
        if (empty($migrations)) {
            $this->info('没有找到迁移文件');
            return 0;
        }

        $headers = ['迁移', '批次', '状态'];
        $rows = [];

        foreach ($migrations as $migration) {
            $status = $migration['ran'] ? '<fg=green>已执行</fg=green>' : '<fg=red>未执行</fg=red>';
            $batch = $migration['batch'] ?? '-';
            
            $rows[] = [
                $migration['migration'],
                $batch,
                $status,
            ];
        }

        $this->table($headers, $rows);
        
        $ranCount = count(array_filter($migrations, fn($m) => $m['ran']));
        $totalCount = count($migrations);
        
        $this->info("总计: {$totalCount}, 已执行: {$ranCount}, 待执行: " . ($totalCount - $ranCount));

        return 0;
    }

    /**
     * 回滚迁移
     */
    protected function rollbackMigrations(): int
    {
        $this->info('回滚迁移...');

        $notes = $this->migrationAdapter->rollbackMigrations();
        
        foreach ($notes as $note) {
            $this->line($note);
        }

        $this->info('迁移回滚完成');
        return 0;
    }

    /**
     * 重置迁移
     */
    protected function resetMigrations(): int
    {
        if (!$this->option('force') && !$this->confirm('确定要重置所有迁移吗？这将删除所有数据！')) {
            $this->info('操作已取消');
            return 0;
        }

        $this->info('重置迁移...');

        $notes = $this->migrationAdapter->resetMigrations();
        
        foreach ($notes as $note) {
            $this->line($note);
        }

        $this->info('迁移重置完成');
        return 0;
    }

    /**
     * 全新迁移
     */
    protected function freshMigrations(): int
    {
        if (!$this->option('force') && !$this->confirm('确定要清空数据库并重新迁移吗？这将删除所有数据！')) {
            $this->info('操作已取消');
            return 0;
        }

        $this->info('清空数据库...');
        
        $tables = $this->migrationAdapter->getTables();
        foreach ($tables as $table) {
            if ($table !== 'migrations') {
                Schema::dropIfExists($table);
            }
        }

        $this->info('重新执行迁移...');
        
        return $this->runMigrations();
    }

    /**
     * 创建迁移
     */
    protected function createMigration(): int
    {
        $tableName = $this->option('create');
        
        if (!$tableName) {
            $this->error('请指定表名');
            return 1;
        }

        $migrationName = 'create_' . $tableName . '_table';
        $path = $this->migrationAdapter->createMigration($migrationName, $tableName);

        $this->info("迁移文件已创建: {$path}");
        return 0;
    }

    /**
     * 执行迁移
     */
    protected function runMigrations(): int
    {
        $this->info('开始执行迁移...');

        $paths = [];
        if ($this->option('path')) {
            $path = $this->option('path');
            if ($this->option('realpath')) {
                $paths[] = $path;
            } else {
                $paths[] = database_path($path);
            }
        }

        // 检查是否有待执行的迁移
        $pending = $this->migrationAdapter->getPendingMigrations();
        if (empty($pending) && empty($paths)) {
            $this->info('没有待执行的迁移');
            return 0;
        }

        // 模拟执行
        if ($this->option('pretend')) {
            $this->info('模拟执行迁移（不会实际执行）');
            // 这里可以添加模拟执行的逻辑
            return 0;
        }

        $notes = $this->migrationAdapter->runMigrations($paths);
        
        foreach ($notes as $note) {
            $this->line($note);
        }

        $this->info('迁移执行完成');

        // 执行数据库填充
        if ($this->option('seed')) {
            $this->runSeeds();
        }

        return 0;
    }

    /**
     * 执行数据库填充
     */
    protected function runSeeds(): void
    {
        $this->info('开始执行数据库填充...');

        $seeder = $this->option('seeder');
        
        if ($seeder) {
            $this->call('db:seed', ['--class' => $seeder]);
        } else {
            $this->call('db:seed');
        }

        $this->info('数据库填充完成');
    }

    /**
     * 获取迁移文件列表
     */
    protected function getMigrationFiles(): array
    {
        return $this->migrationAdapter->getMigrationFiles();
    }

    /**
     * 验证迁移文件
     */
    protected function validateMigrationFiles(): bool
    {
        $files = $this->getMigrationFiles();
        $invalidFiles = [];

        foreach ($files as $file) {
            if (!$this->migrationAdapter->validateMigrationFile($file)) {
                $invalidFiles[] = $file;
            }
        }

        if (!empty($invalidFiles)) {
            $this->error('发现无效的迁移文件:');
            foreach ($invalidFiles as $file) {
                $this->line("  - {$file}");
            }
            return false;
        }

        return true;
    }

    /**
     * 显示迁移统计信息
     */
    protected function showMigrationStatistics(): void
    {
        $migrations = $this->migrationAdapter->getMigrationStatus();
        
        $stats = [
            'total' => count($migrations),
            'ran' => count(array_filter($migrations, fn($m) => $m['ran'])),
            'pending' => count(array_filter($migrations, fn($m) => !$m['ran'])),
        ];

        $this->info('迁移统计:');
        $this->line("  总计: {$stats['total']}");
        $this->line("  已执行: {$stats['ran']}");
        $this->line("  待执行: {$stats['pending']}");
    }

    /**
     * 获取帮助信息
     */
    public function getHelpFor($name)
    {
        $help = parent::getHelpFor($name);
        
        $help .= "\n\n数据库迁移选项:";
        $help .= "\n  --step           逐步执行迁移";
        $help .= "\n  --force          强制执行迁移";
        $help .= "\n  --pretend        模拟执行迁移";
        $help .= "\n  --path=          指定迁移文件路径";
        $help .= "\n  --seed           迁移后执行数据库填充";
        $help .= "\n  --check          检查迁移状态";
        $help .= "\n  --rollback       回滚迁移";
        $help .= "\n  --reset          重置所有迁移";
        $help .= "\n  --status         显示迁移状态";
        $help .= "\n  --fresh          清空数据库后重新迁移";
        $help .= "\n  --create=        创建新表";
        
        return $help;
    }
}