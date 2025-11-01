#!/usr/bin/env php
<?php

/**
 * Webman-Filament 扩展安装脚本
 * 
 * 此脚本用于自动化安装和配置 Webman-Filament 扩展
 * 
 * 使用方法：
 * php scripts/install.php [选项]
 * 
 * 选项：
 * --force    强制重新安装
 * --verbose  详细输出
 * --dry-run  模拟运行（不实际执行）
 */

declare(strict_types=1);

use WebmanFilament\Support\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

class Installer
{
    private array $options = [];
    private Logger $logger;
    private bool $verbose = false;
    private bool $dryRun = false;
    private bool $force = false;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->parseArguments();
    }

    /**
     * 解析命令行参数
     */
    private function parseArguments(): void
    {
        global $argv;
        
        foreach ($argv as $index => $arg) {
            if ($index === 0) continue; // 跳过脚本名称
            
            switch ($arg) {
                case '--verbose':
                case '-v':
                    $this->verbose = true;
                    break;
                case '--dry-run':
                case '-n':
                    $this->dryRun = true;
                    break;
                case '--force':
                case '-f':
                    $this->force = true;
                    break;
                case '--help':
                case '-h':
                    $this->showHelp();
                    exit(0);
            }
        }
    }

    /**
     * 显示帮助信息
     */
    private function showHelp(): void
    {
        echo <<<HELP
Webman-Filament 扩展安装脚本

使用方法:
    php scripts/install.php [选项]

选项:
    --force, -f      强制重新安装（覆盖现有配置）
    --verbose, -v    详细输出模式
    --dry-run, -n    模拟运行（不实际执行）
    --help, -h       显示此帮助信息

示例:
    php scripts/install.php
    php scripts/install.php --verbose
    php scripts/install.php --force
    php scripts/install.php --dry-run

HELP;
    }

    /**
     * 执行安装
     */
    public function install(): int
    {
        $this->log('开始安装 Webman-Filament 扩展...', 'info');
        
        try {
            // 1. 检查环境
            if (!$this->checkEnvironment()) {
                return 1;
            }
            
            // 2. 检查依赖
            if (!$this->checkDependencies()) {
                return 1;
            }
            
            // 3. 创建必要目录
            if (!$this->createDirectories()) {
                return 1;
            }
            
            // 4. 复制配置文件
            if (!$this->copyConfigFiles()) {
                return 1;
            }
            
            // 5. 安装资源文件
            if (!$this->installAssets()) {
                return 1;
            }
            
            // 6. 生成缓存
            if (!$this->generateCache()) {
                return 1;
            }
            
            // 7. 运行数据库迁移
            if (!$this->runMigrations()) {
                return 1;
            }
            
            $this->log('安装完成！', 'success');
            $this->showNextSteps();
            
            return 0;
            
        } catch (Exception $e) {
            $this->log('安装失败: ' . $e->getMessage(), 'error');
            $this->log('错误详情: ' . $e->getTraceAsString(), 'error');
            return 1;
        }
    }

    /**
     * 检查运行环境
     */
    private function checkEnvironment(): bool
    {
        $this->log('检查运行环境...', 'info');
        
        $checks = [
            'PHP 版本 >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'Composer 已安装' => $this->commandExists('composer'),
            'Webman 框架存在' => is_dir(__DIR__ . '/../vendor/workerman/webman-framework'),
            'Filament 包存在' => is_dir(__DIR__ . '/../vendor/filament/filament'),
        ];
        
        foreach ($checks as $check => $result) {
            if ($this->verbose) {
                $this->log("  - {$check}: " . ($result ? '✓' : '✗'), $result ? 'success' : 'error');
            }
            
            if (!$result) {
                $this->log("环境检查失败: {$check}", 'error');
                return false;
            }
        }
        
        return true;
    }

    /**
     * 检查依赖关系
     */
    private function checkDependencies(): bool
    {
        $this->log('检查依赖关系...', 'info');
        
        $requiredPackages = [
            'workerman/webman-framework',
            'filament/filament',
            'illuminate/support',
            'livewire/livewire',
        ];
        
        foreach ($requiredPackages as $package) {
            $exists = is_dir(__DIR__ . "/../vendor/{$package}");
            
            if ($this->verbose) {
                $this->log("  - {$package}: " . ($exists ? '✓' : '✗'), $exists ? 'success' : 'error');
            }
            
            if (!$exists) {
                $this->log("缺少依赖包: {$package}", 'error');
                $this->log('请运行: composer install', 'info');
                return false;
            }
        }
        
        return true;
    }

    /**
     * 创建必要目录
     */
    private function createDirectories(): bool
    {
        $this->log('创建必要目录...', 'info');
        
        $directories = [
            __DIR__ . '/../public/filament',
            __DIR__ . '/../storage/filament',
            __DIR__ . '/../storage/logs',
            __DIR__ . '/../bootstrap/cache',
        ];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) {
                if ($this->verbose) {
                    $this->log("  创建目录: {$dir}", 'info');
                }
                
                if (!$this->dryRun && !mkdir($dir, 0755, true)) {
                    $this->log("无法创建目录: {$dir}", 'error');
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * 复制配置文件
     */
    private function copyConfigFiles(): bool
    {
        $this->log('复制配置文件...', 'info');
        
        $configFiles = [
            __DIR__ . '/../config/filament.php' => 'Filament 配置文件',
        ];
        
        foreach ($configFiles as $file => $description) {
            if (file_exists($file)) {
                if ($this->verbose) {
                    $this->log("  - {$description}: 已存在", 'info');
                }
                
                if ($this->force && !$this->dryRun) {
                    if ($this->verbose) {
                        $this->log("    强制覆盖", 'info');
                    }
                }
            } else {
                if ($this->verbose) {
                    $this->log("  - {$description}: 缺失", 'warning');
                }
                
                // 这里可以添加默认配置文件的创建逻辑
            }
        }
        
        return true;
    }

    /**
     * 安装资源文件
     */
    private function installAssets(): bool
    {
        $this->log('安装资源文件...', 'info');
        
        $assetFiles = [
            'vendor/filament/filament/dist/filament.css' => 'public/filament/filament.css',
            'vendor/filament/filament/dist/filament.js' => 'public/filament/filament.js',
        ];
        
        foreach ($assetFiles as $source => $dest) {
            $sourcePath = __DIR__ . '/../' . $source;
            $destPath = __DIR__ . '/../' . $dest;
            
            if (file_exists($sourcePath)) {
                if ($this->verbose) {
                    $this->log("  复制: {$source} -> {$dest}", 'info');
                }
                
                if (!$this->dryRun) {
                    // 确保目标目录存在
                    $destDir = dirname($destPath);
                    if (!is_dir($destDir)) {
                        mkdir($destDir, 0755, true);
                    }
                    
                    if (!copy($sourcePath, $destPath)) {
                        $this->log("复制失败: {$source} -> {$dest}", 'error');
                        return false;
                    }
                }
            } else {
                if ($this->verbose) {
                    $this->log("  资源文件不存在: {$source}", 'warning');
                }
            }
        }
        
        return true;
    }

    /**
     * 生成缓存
     */
    private function generateCache(): bool
    {
        $this->log('生成缓存文件...', 'info');
        
        if ($this->dryRun) {
            if ($this->verbose) {
                $this->log('  跳过缓存生成（dry-run 模式）', 'info');
            }
            return true;
        }
        
        // 生成 Composer 自动加载缓存
        $composer = $this->runCommand('composer dump-autoload --optimize');
        if ($composer !== 0) {
            $this->log('Composer 自动加载缓存生成失败', 'error');
            return false;
        }
        
        return true;
    }

    /**
     * 运行数据库迁移
     */
    private function runMigrations(): bool
    {
        $this->log('运行数据库迁移...', 'info');
        
        if ($this->dryRun) {
            if ($this->verbose) {
                $this->log('  跳过数据库迁移（dry-run 模式）', 'info');
            }
            return true;
        }
        
        // 检查是否有迁移文件
        $migrationsDir = __DIR__ . '/../database/migrations';
        if (!is_dir($migrationsDir) || count(glob($migrationsDir . '/*.php')) === 0) {
            if ($this->verbose) {
                $this->log('  没有发现迁移文件', 'info');
            }
            return true;
        }
        
        // 这里可以添加具体的迁移逻辑
        // 由于这是 Webman 项目，可能需要不同的迁移处理方式
        
        return true;
    }

    /**
     * 显示后续步骤
     */
    private function showNextSteps(): void
    {
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "安装完成！后续步骤：\n\n";
        echo "1. 配置数据库连接\n";
        echo "2. 运行配置脚本：php scripts/configure.php\n";
        echo "3. 验证安装：php scripts/validate.php\n";
        echo "4. 启动 Webman 服务器\n\n";
        echo "更多信息请查看 README.md\n";
        echo str_repeat('=', 50) . "\n";
    }

    /**
     * 执行命令
     */
    private function runCommand(string $command): int
    {
        if ($this->verbose) {
            $this->log("执行命令: {$command}", 'info');
        }
        
        $output = [];
        $returnCode = 0;
        exec($command . ' 2>&1', $output, $returnCode);
        
        if ($returnCode !== 0 && $this->verbose) {
            $this->log('命令执行失败: ' . implode("\n", $output), 'error');
        }
        
        return $returnCode;
    }

    /**
     * 检查命令是否存在
     */
    private function commandExists(string $command): bool
    {
        $output = [];
        $returnCode = 0;
        exec("which {$command} 2>/dev/null", $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * 记录日志
     */
    private function log(string $message, string $level = 'info'): void
    {
        $prefix = match ($level) {
            'success' => '✓',
            'error' => '✗',
            'warning' => '⚠',
            default => 'ℹ'
        };
        
        $coloredMessage = match ($level) {
            'success' => "\033[32m{$prefix} {$message}\033[0m",
            'error' => "\033[31m{$prefix} {$message}\033[0m",
            'warning' => "\033[33m{$prefix} {$message}\033[0m",
            default => "\033[36m{$prefix} {$message}\033[0m"
        };
        
        echo $coloredMessage . "\n";
        
        // 记录到日志文件
        $this->logger->log($level, $message);
    }
}

// 运行安装程序
$installer = new Installer();
exit($installer->install());