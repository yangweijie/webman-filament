#!/usr/bin/env php
<?php

/**
 * Webman-Filament 扩展验证脚本
 * 
 * 此脚本用于验证 Webman-Filament 扩展的安装和配置是否正确
 * 
 * 使用方法：
 * php scripts/validate.php [选项]
 * 
 * 选项：
 * --verbose  详细验证模式
 * --quick    快速验证（跳过耗时检查）
 * --health   健康检查模式
 * --report   生成验证报告
 */

declare(strict_types=1);

use WebmanFilament\Support\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

class Validator
{
    private array $options = [];
    private Logger $logger;
    private bool $verbose = false;
    private bool $quick = false;
    private bool $healthCheck = false;
    private bool $generateReport = false;
    private array $results = [];
    private string $reportFile;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->reportFile = __DIR__ . '/../storage/logs/validation-report.json';
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
                case '--quick':
                case '-q':
                    $this->quick = true;
                    break;
                case '--health':
                case '-h':
                    $this->healthCheck = true;
                    break;
                case '--report':
                case '-r':
                    $this->generateReport = true;
                    break;
                case '--help':
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
Webman-Filament 扩展验证脚本

使用方法:
    php scripts/validate.php [选项]

选项:
    --verbose, -v   详细验证模式
    --quick, -q     快速验证（跳过耗时检查）
    --health, -h    健康检查模式
    --report, -r    生成验证报告
    --help          显示此帮助信息

示例:
    php scripts/validate.php
    php scripts/validate.php --verbose
    php scripts/validate.php --quick
    php scripts/validate.php --health
    php scripts/validate.php --report

HELP;
    }

    /**
     * 执行验证
     */
    public function validate(): int
    {
        $this->log('开始验证 Webman-Filament 扩展...', 'info');
        
        $this->results = [
            'timestamp' => date('Y-m-d H:i:s'),
            'php_version' => PHP_VERSION,
            'checks' => [],
            'summary' => [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'warnings' => 0,
            ]
        ];
        
        try {
            // 1. 系统环境检查
            $this->validateEnvironment();
            
            // 2. 依赖包检查
            $this->validateDependencies();
            
            // 3. 文件和目录检查
            $this->validateFilesAndDirectories();
            
            // 4. 配置检查
            $this->validateConfiguration();
            
            // 5. 数据库检查
            if (!$this->quick) {
                $this->validateDatabase();
            }
            
            // 6. 服务检查
            $this->validateServices();
            
            // 7. 性能检查
            if (!$this->quick) {
                $this->validatePerformance();
            }
            
            // 8. 安全检查
            $this->validateSecurity();
            
            // 生成报告
            if ($this->generateReport) {
                $this->generateReportFile();
            }
            
            // 显示结果
            $this->showResults();
            
            return $this->results['summary']['failed'] > 0 ? 1 : 0;
            
        } catch (Exception $e) {
            $this->log('验证过程中发生错误: ' . $e->getMessage(), 'error');
            return 1;
        }
    }

    /**
     * 验证系统环境
     */
    private function validateEnvironment(): void
    {
        $this->log('验证系统环境...', 'info');
        
        $checks = [
            'PHP 版本 >= 8.1' => version_compare(PHP_VERSION, '8.1.0', '>='),
            'Composer 已安装' => $this->commandExists('composer'),
            'Node.js 已安装' => $this->commandExists('node'),
            'NPM 已安装' => $this->commandExists('npm'),
            'PHP 扩展: PDO' => extension_loaded('pdo'),
            'PHP 扩展: PDO_MySQL' => extension_loaded('pdo_mysql'),
            'PHP 扩展: mbstring' => extension_loaded('mbstring'),
            'PHP 扩展: openssl' => extension_loaded('openssl'),
            'PHP 扩展: curl' => extension_loaded('curl'),
            'PHP 扩展: json' => extension_loaded('json'),
            'PHP 扩展: fileinfo' => extension_loaded('fileinfo'),
            '内存限制 >= 256M' => $this->getMemoryLimit() >= 256 * 1024 * 1024,
            '执行时间限制 >= 300s' => ini_get('max_execution_time') == 0 || ini_get('max_execution_time') >= 300,
        ];
        
        foreach ($checks as $check => $result) {
            $this->addCheckResult('environment', $check, $result, $result ? null : '环境要求不满足');
        }
    }

    /**
     * 验证依赖包
     */
    private function validateDependencies(): void
    {
        $this->log('验证依赖包...', 'info');
        
        $requiredPackages = [
            'workerman/webman-framework' => 'Webman 框架',
            'filament/filament' => 'Filament 包',
            'illuminate/support' => 'Illuminate 支持包',
            'illuminate/database' => 'Illuminate 数据库包',
            'illuminate/routing' => 'Illuminate 路由包',
            'livewire/livewire' => 'Livewire 包',
        ];
        
        foreach ($requiredPackages as $package => $description) {
            $exists = is_dir(__DIR__ . "/../vendor/{$package}");
            $this->addCheckResult('dependencies', $description, $exists, $exists ? null : '包未安装');
        }
        
        // 检查开发依赖
        if (!$this->quick) {
            $devPackages = [
                'phpunit/phpunit' => 'PHPUnit 测试框架',
                'workerman/workerman' => 'Workerman 开发包',
            ];
            
            foreach ($devPackages as $package => $description) {
                $exists = is_dir(__DIR__ . "/../vendor/{$package}");
                $this->addCheckResult('dependencies', $description . ' (dev)', $exists, $exists ? null : '开发包未安装');
            }
        }
    }

    /**
     * 验证文件和目录
     */
    private function validateFilesAndDirectories(): void
    {
        $this->log('验证文件和目录...', 'info');
        
        $requiredPaths = [
            __DIR__ . '/../src' => '源码目录',
            __DIR__ . '/../config' => '配置目录',
            __DIR__ . '/../database' => '数据库目录',
            __DIR__ . '/../public' => '公共目录',
            __DIR__ . '/../storage' => '存储目录',
            __DIR__ . '/../vendor' => 'Vendor 目录',
        ];
        
        foreach ($requiredPaths as $path => $description) {
            $exists = is_dir($path);
            $writable = $exists && is_writable($path);
            
            $this->addCheckResult('files', $description, $exists, $exists ? null : '目录不存在');
            
            if ($exists) {
                $this->addCheckResult('files', $description . ' 可写', $writable, $writable ? null : '目录不可写');
            }
        }
        
        // 检查关键文件
        $requiredFiles = [
            __DIR__ . '/../composer.json' => 'Composer 配置文件',
            __DIR__ . '/../config/filament.php' => 'Filament 配置文件',
            __DIR__ . '/../src/WebmanFilamentServiceProvider.php' => '服务提供者文件',
        ];
        
        foreach ($requiredFiles as $file => $description) {
            $exists = file_exists($file);
            $this->addCheckResult('files', $description, $exists, $exists ? null : '文件不存在');
        }
    }

    /**
     * 验证配置
     */
    private function validateConfiguration(): void
    {
        $this->log('验证配置...', 'info');
        
        // 检查配置文件
        $configFile = __DIR__ . '/../config/filament.php';
        if (file_exists($configFile)) {
            $config = include $configFile;
            
            if (is_array($config)) {
                $this->addCheckResult('config', '配置文件格式正确', true);
                
                // 检查必要配置项
                $requiredConfigKeys = ['database', 'admin', 'theme', 'settings'];
                foreach ($requiredConfigKeys as $key) {
                    $exists = array_key_exists($key, $config);
                    $this->addCheckResult('config', "配置项: {$key}", $exists, $exists ? null : '配置项缺失');
                }
                
                // 检查数据库配置
                if (isset($config['database'])) {
                    $dbConfig = $config['database'];
                    $requiredDbKeys = ['host', 'port', 'database', 'username'];
                    foreach ($requiredDbKeys as $key) {
                        $exists = !empty($dbConfig[$key] ?? '');
                        $this->addCheckResult('config', "数据库配置: {$key}", $exists, $exists ? null : '配置值为空');
                    }
                }
            } else {
                $this->addCheckResult('config', '配置文件格式正确', false, '配置文件格式错误');
            }
        } else {
            $this->addCheckResult('config', '配置文件存在', false, '配置文件不存在');
        }
    }

    /**
     * 验证数据库
     */
    private function validateDatabase(): void
    {
        $this->log('验证数据库...', 'info');
        
        try {
            $configFile = __DIR__ . '/../config/filament.php';
            if (!file_exists($configFile)) {
                $this->addCheckResult('database', '数据库连接', false, '配置文件不存在');
                return;
            }
            
            $config = include $configFile;
            if (!isset($config['database'])) {
                $this->addCheckResult('database', '数据库配置', false, '数据库配置不存在');
                return;
            }
            
            $dbConfig = $config['database'];
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $dbConfig['host'] ?? '127.0.0.1',
                $dbConfig['port'] ?? '3306',
                $dbConfig['database'] ?? 'webman_filament',
                $dbConfig['charset'] ?? 'utf8mb4'
            );
            
            $pdo = new PDO($dsn, $dbConfig['username'] ?? 'root', $dbConfig['password'] ?? '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            $this->addCheckResult('database', '数据库连接', true);
            
            // 检查必要表
            $tables = ['users', 'migrations']; // 根据实际需要调整
            foreach ($tables as $table) {
                try {
                    $stmt = $pdo->query("SHOW TABLES LIKE '{$table}'");
                    $exists = $stmt->rowCount() > 0;
                    $this->addCheckResult('database', "数据表: {$table}", $exists, $exists ? null : '表不存在');
                } catch (PDOException $e) {
                    $this->addCheckResult('database', "数据表: {$table}", false, '查询失败');
                }
            }
            
        } catch (PDOException $e) {
            $this->addCheckResult('database', '数据库连接', false, $e->getMessage());
        }
    }

    /**
     * 验证服务
     */
    private function validateServices(): void
    {
        $this->log('验证服务...', 'info');
        
        // 检查 Webman 服务
        $webmanRunning = $this->isPortInUse(8787); // Webman 默认端口
        $this->addCheckResult('services', 'Webman 服务运行状态', $webmanRunning, $webmanRunning ? null : '服务未运行');
        
        // 检查 Filament 路由
        if ($webmanRunning) {
            $response = $this->checkHttpEndpoint('http://127.0.0.1:8787/admin');
            $this->addCheckResult('services', 'Filament 路由可达', $response, $response ? null : '路由不可达');
        }
    }

    /**
     * 验证性能
     */
    private function validatePerformance(): void
    {
        $this->log('验证性能...', 'info');
        
        // 检查自动加载缓存
        $autoloadFile = __DIR__ . '/../vendor/autoload.php';
        if (file_exists($autoloadFile)) {
            $autoloadTime = filemtime($autoloadFile);
            $cacheAge = time() - $autoloadTime;
            $cacheFresh = $cacheAge < 3600; // 1小时内
            
            $this->addCheckResult('performance', '自动加载缓存新鲜度', $cacheFresh, $cacheFresh ? null : '缓存可能过期');
        }
        
        // 检查内存使用
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = $this->getMemoryLimit();
        $memoryUsagePercent = ($memoryUsage / $memoryLimit) * 100;
        $memoryOk = $memoryUsagePercent < 80;
        
        $this->addCheckResult('performance', '内存使用率 < 80%', $memoryOk, $memoryOk ? null : "内存使用率: {$memoryUsagePercent}%");
        
        // 检查磁盘空间
        $diskSpace = disk_free_space(__DIR__ . '/..');
        $diskSpaceOk = $diskSpace > 100 * 1024 * 1024; // 100MB
        $this->addCheckResult('performance', '磁盘空间充足', $diskSpaceOk, $diskSpaceOk ? null : '磁盘空间不足');
    }

    /**
     * 验证安全性
     */
    private function validateSecurity(): void
    {
        $this->log('验证安全性...', 'info');
        
        // 检查敏感文件权限
        $sensitiveFiles = [
            __DIR__ . '/../config/filament.php' => '配置文件权限',
            __DIR__ . '/../.env' => '环境文件权限',
        ];
        
        foreach ($sensitiveFiles as $file => $description) {
            if (file_exists($file)) {
                $perms = fileperms($file);
                $worldWritable = ($perms & 0x0002) !== 0; // 检查是否全局可写
                $this->addCheckResult('security', $description, !$worldWritable, $worldWritable ? '文件权限过于宽松' : null);
            }
        }
        
        // 检查调试模式
        $debugEnabled = ini_get('display_errors') == '1' || ini_get('log_errors') == '1';
        $this->addCheckResult('security', '调试信息安全性', !$debugEnabled, $debugEnabled ? '调试模式可能暴露敏感信息' : null);
    }

    /**
     * 添加检查结果
     */
    private function addCheckResult(string $category, string $check, bool $passed, ?string $message = null): void
    {
        $result = [
            'category' => $category,
            'check' => $check,
            'passed' => $passed,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
        ];
        
        $this->results['checks'][] = $result;
        $this->results['summary']['total']++;
        
        if ($passed) {
            $this->results['summary']['passed']++;
            if ($this->verbose) {
                $this->log("✓ {$check}", 'success');
            }
        } else {
            if ($message && strpos($message, '警告') !== false) {
                $this->results['summary']['warnings']++;
                $this->log("⚠ {$check}: {$message}", 'warning');
            } else {
                $this->results['summary']['failed']++;
                $this->log("✗ {$check}: {$message}", 'error');
            }
        }
    }

    /**
     * 显示验证结果
     */
    private function showResults(): void
    {
        echo "\n" . str_repeat('=', 60) . "\n";
        echo "验证结果摘要\n";
        echo str_repeat('=', 60) . "\n";
        
        $summary = $this->results['summary'];
        echo "总检查项: {$summary['total']}\n";
        echo "通过: {$summary['passed']}\n";
        echo "失败: {$summary['failed']}\n";
        echo "警告: {$summary['warnings']}\n";
        
        if ($summary['failed'] === 0) {
            echo "\n🎉 所有检查通过！Webman-Filament 扩展安装正确。\n";
        } else {
            echo "\n❌ 发现 {$summary['failed']} 个问题，请检查上述错误信息。\n";
        }
        
        if ($summary['warnings'] > 0) {
            echo "\n⚠️  有 {$summary['warnings']} 个警告，建议处理。\n";
        }
        
        echo str_repeat('=', 60) . "\n";
    }

    /**
     * 生成验证报告
     */
    private function generateReportFile(): void
    {
        $reportDir = dirname($this->reportFile);
        if (!is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }
        
        $reportJson = json_encode($this->results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if (file_put_contents($this->reportFile, $reportJson) !== false) {
            $this->log("验证报告已生成: {$this->reportFile}", 'success');
        } else {
            $this->log('验证报告生成失败', 'error');
        }
    }

    /**
     * 获取内存限制
     */
    private function getMemoryLimit(): int
    {
        $limit = ini_get('memory_limit');
        if ($limit == -1) {
            return PHP_INT_MAX;
        }
        
        $limit = strtolower(trim($limit));
        $last = strtolower(substr($limit, -1));
        $value = (int) $limit;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
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
     * 检查端口是否被占用
     */
    private function isPortInUse(int $port): bool
    {
        $connection = @fsockopen('127.0.0.1', $port, $errno, $errstr, 1);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }

    /**
     * 检查 HTTP 端点
     */
    private function checkHttpEndpoint(string $url): bool
    {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'ignore_errors' => true,
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        return $response !== false;
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

// 运行验证程序
$validator = new Validator();
exit($validator->validate());