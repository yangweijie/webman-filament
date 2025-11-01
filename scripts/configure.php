#!/usr/bin/env php
<?php

/**
 * Webman-Filament 扩展配置脚本
 * 
 * 此脚本用于配置和自定义 Webman-Filament 扩展的设置
 * 
 * 使用方法：
 * php scripts/configure.php [选项]
 * 
 * 选项：
 * --interactive  交互式配置模式
 * --database     配置数据库连接
 * --admin        配置管理员账户
 * --theme        配置主题设置
 * --reset        重置所有配置
 */

declare(strict_types=1);

use WebmanFilament\Support\Logger;

require_once __DIR__ . '/../vendor/autoload.php';

class Configurer
{
    private array $options = [];
    private Logger $logger;
    private bool $interactive = false;
    private bool $reset = false;
    private array $config = [];
    private string $configFile;

    public function __construct()
    {
        $this->logger = new Logger();
        $this->configFile = __DIR__ . '/../config/filament.php';
        $this->parseArguments();
        $this->loadConfig();
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
                case '--interactive':
                case '-i':
                    $this->interactive = true;
                    break;
                case '--database':
                case '-d':
                    $this->configureDatabase();
                    exit(0);
                case '--admin':
                case '-a':
                    $this->configureAdmin();
                    exit(0);
                case '--theme':
                case '-t':
                    $this->configureTheme();
                    exit(0);
                case '--reset':
                case '-r':
                    $this->reset = true;
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
Webman-Filament 扩展配置脚本

使用方法:
    php scripts/configure.php [选项]

选项:
    --interactive, -i  交互式配置模式
    --database, -d     配置数据库连接
    --admin, -a        配置管理员账户
    --theme, -t        配置主题设置
    --reset, -r        重置所有配置
    --help, -h         显示此帮助信息

示例:
    php scripts/configure.php
    php scripts/configure.php --interactive
    php scripts/configure.php --database
    php scripts/configure.php --reset

HELP;
    }

    /**
     * 加载现有配置
     */
    private function loadConfig(): void
    {
        if (file_exists($this->configFile)) {
            $this->config = include $this->configFile;
            if (!is_array($this->config)) {
                $this->config = [];
            }
        } else {
            $this->config = [];
        }
    }

    /**
     * 保存配置
     */
    private function saveConfig(): bool
    {
        $configContent = "<?php\n\nreturn " . var_export($this->config, true) . ";\n";
        
        if (file_put_contents($this->configFile, $configContent) === false) {
            $this->log('配置保存失败', 'error');
            return false;
        }
        
        $this->log('配置已保存', 'success');
        return true;
    }

    /**
     * 执行配置
     */
    public function configure(): int
    {
        $this->log('开始配置 Webman-Filament 扩展...', 'info');
        
        try {
            if ($this->reset) {
                $this->resetConfig();
            }
            
            if ($this->interactive) {
                return $this->interactiveConfigure();
            } else {
                return $this->defaultConfigure();
            }
            
        } catch (Exception $e) {
            $this->log('配置失败: ' . $e->getMessage(), 'error');
            return 1;
        }
    }

    /**
     * 交互式配置
     */
    private function interactiveConfigure(): int
    {
        $this->log('进入交互式配置模式', 'info');
        
        echo "\n" . str_repeat('=', 50) . "\n";
        echo "Webman-Filament 扩展配置向导\n";
        echo str_repeat('=', 50) . "\n\n";
        
        // 1. 数据库配置
        $this->configureDatabase(true);
        
        // 2. 管理员账户配置
        $this->configureAdmin(true);
        
        // 3. 主题配置
        $this->configureTheme(true);
        
        // 4. 其他设置
        $this->configureOtherSettings();
        
        // 保存配置
        if ($this->saveConfig()) {
            $this->log('交互式配置完成', 'success');
            return 0;
        }
        
        return 1;
    }

    /**
     * 默认配置
     */
    private function defaultConfigure(): int
    {
        $this->log('应用默认配置...', 'info');
        
        $this->configureDatabase();
        $this->configureAdmin();
        $this->configureTheme();
        $this->configureOtherSettings();
        
        if ($this->saveConfig()) {
            $this->log('默认配置完成', 'success');
            return 0;
        }
        
        return 1;
    }

    /**
     * 配置数据库
     */
    private function configureDatabase(bool $interactive = false): void
    {
        if ($interactive) {
            echo "\n--- 数据库配置 ---\n";
        }
        
        $this->log('配置数据库连接...', 'info');
        
        $defaults = [
            'host' => '127.0.0.1',
            'port' => '3306',
            'database' => 'webman_filament',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ];
        
        if ($interactive) {
            foreach ($defaults as $key => $default) {
                $prompt = "请输入数据库{$key} [默认: {$default}]: ";
                $value = $this->prompt($prompt, $default);
                $this->config['database'][$key] = $value;
            }
        } else {
            $this->config['database'] = array_merge($defaults, $this->config['database'] ?? []);
        }
        
        // 测试数据库连接
        if (!$interactive && $this->testDatabaseConnection()) {
            $this->log('数据库连接测试成功', 'success');
        }
    }

    /**
     * 配置管理员账户
     */
    private function configureAdmin(bool $interactive = false): void
    {
        if ($interactive) {
            echo "\n--- 管理员账户配置 ---\n";
        }
        
        $this->log('配置管理员账户...', 'info');
        
        if ($interactive) {
            $this->config['admin']['name'] = $this->prompt('请输入管理员姓名: ', 'Admin');
            $this->config['admin']['email'] = $this->prompt('请输入管理员邮箱: ', 'admin@example.com');
            $this->config['admin']['password'] = $this->prompt('请输入管理员密码: ', '', true);
        } else {
            $defaults = [
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => '', // 需要用户设置
            ];
            $this->config['admin'] = array_merge($defaults, $this->config['admin'] ?? []);
        }
    }

    /**
     * 配置主题
     */
    private function configureTheme(bool $interactive = false): void
    {
        if ($interactive) {
            echo "\n--- 主题配置 ---\n";
        }
        
        $this->log('配置主题设置...', 'info');
        
        $defaults = [
            'dark_mode' => false,
            'primary_color' => '#6366f1',
            'secondary_color' => '#64748b',
            'brand_name' => 'Webman Filament',
            'logo' => null,
            'favicon' => null,
        ];
        
        if ($interactive) {
            $this->config['theme']['dark_mode'] = $this->confirm('启用深色模式?', false);
            $this->config['theme']['primary_color'] = $this->prompt('主色调 (十六进制): ', '#6366f1');
            $this->config['theme']['secondary_color'] = $this->prompt('次要色调 (十六进制): ', '#64748b');
            $this->config['theme']['brand_name'] = $this->prompt('品牌名称: ', 'Webman Filament');
            $this->config['theme']['logo'] = $this->prompt('Logo 路径 (可选): ', '');
            $this->config['theme']['favicon'] = $this->prompt('Favicon 路径 (可选): ', '');
        } else {
            $this->config['theme'] = array_merge($defaults, $this->config['theme'] ?? []);
        }
    }

    /**
     * 配置其他设置
     */
    private function configureOtherSettings(): void
    {
        $this->log('配置其他设置...', 'info');
        
        $defaults = [
            'debug' => false,
            'timezone' => 'Asia/Shanghai',
            'locale' => 'zh_CN',
            'cache_enabled' => true,
            'log_level' => 'info',
            'assets_url' => '/filament',
            'middleware' => [
                'web',
                'auth',
            ],
            'route_prefix' => 'admin',
            'api_prefix' => 'filament/api',
        ];
        
        $this->config['settings'] = array_merge($defaults, $this->config['settings'] ?? []);
    }

    /**
     * 重置配置
     */
    private function resetConfig(): void
    {
        $this->log('重置所有配置...', 'info');
        
        if (file_exists($this->configFile)) {
            if (unlink($this->configFile)) {
                $this->log('配置文件已删除', 'success');
            } else {
                $this->log('无法删除配置文件', 'error');
            }
        }
        
        $this->config = [];
    }

    /**
     * 测试数据库连接
     */
    private function testDatabaseConnection(): bool
    {
        $this->log('测试数据库连接...', 'info');
        
        try {
            $dbConfig = $this->config['database'] ?? [];
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $dbConfig['host'] ?? '127.0.0.1',
                $dbConfig['port'] ?? '3306',
                $dbConfig['database'] ?? 'webman_filament',
                $dbConfig['charset'] ?? 'utf8mb4'
            );
            
            $pdo = new PDO($dsn, $dbConfig['username'] ?? 'root', $dbConfig['password'] ?? '');
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            return true;
        } catch (PDOException $e) {
            $this->log('数据库连接失败: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * 提示用户输入
     */
    private function prompt(string $message, string $default = '', bool $hidden = false): string
    {
        echo $message;
        
        if ($hidden) {
            // 隐藏输入（用于密码）
            $command = "/bin/bash -c 'read -s mypass && echo \$mypass'";
            $input = trim(shell_exec($command));
        } else {
            $input = trim(fgets(STDIN));
        }
        
        return $input ?: $default;
    }

    /**
     * 确认操作
     */
    private function confirm(string $message, bool $default = false): bool
    {
        $defaultText = $default ? ' [Y/n]' : ' [y/N]';
        $answer = $this->prompt($message . $defaultText);
        
        if (empty($answer)) {
            return $default;
        }
        
        return strtolower($answer) === 'y' || strtolower($answer) === 'yes';
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

// 运行配置程序
$configurer = new Configurer();
exit($configurer->configure());