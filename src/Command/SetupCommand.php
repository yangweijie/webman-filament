<?php

namespace App\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Webman-Filament 安装命令
 * 
 * 用于安装和配置 Webman-Filament 集成
 * 
 * @author Webman-Filament 开发团队
 * @version 1.0.0
 */
class SetupCommand extends Command
{
    /**
     * 控制台命令名称和签名
     *
     * @var string
     */
    protected $signature = 'webman-filament:setup 
                            {--force : 强制执行安装，不进行确认}
                            {--skip-deps : 跳过依赖安装}
                            {--skip-migrate : 跳过数据库迁移}
                            {--skip-config : 跳过配置发布}';

    /**
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = '安装和配置 Webman-Filament 集成';

    /**
     * 安装过程中的错误
     *
     * @var array
     */
    private $errors = [];

    /**
     * 安装过程中的警告
     *
     * @var array
     */
    private $warnings = [];

    /**
     * 安装过程中的成功消息
     *
     * @var array
     */
    private $successMessages = [];

    /**
     * 创建一个新的命令实例
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 执行控制台命令
     */
    public function handle()
    {
        $this->info('🚀 Webman-Filament 安装程序启动');
        $this->line('');

        // 显示欢迎信息
        $this->showWelcomeMessage();

        // 检查是否强制执行
        if (!$this->option('force')) {
            if (!$this->confirm('是否继续安装 Webman-Filament？')) {
                $this->info('安装已取消');
                return 0;
            }
        }

        // 执行安装步骤
        $this->install();

        // 显示结果
        $this->showResults();

        return 0;
    }

    /**
     * 显示欢迎信息
     */
    private function showWelcomeMessage()
    {
        $this->info('================================================');
        $this->info('           Webman-Filament 安装程序');
        $this->info('================================================');
        $this->line('');
        $this->info('这个命令将帮助您：');
        $this->line('• 检查系统环境');
        $this->line('• 安装必要的依赖');
        $this->line('• 配置数据库连接');
        $this->line('• 发布 Filament 资源');
        $this->line('• 设置文件权限');
        $this->line('• 优化应用配置');
        $this->line('');
    }

    /**
     * 执行完整的安装流程
     */
    private function install()
    {
        // 步骤 1: 环境检查
        $this->step('检查系统环境', [$this, 'checkEnvironment']);

        // 步骤 2: 安装依赖
        if (!$this->option('skip-deps')) {
            $this->step('安装依赖', [$this, 'installDependencies']);
        }

        // 步骤 3: 配置应用
        $this->step('配置应用', [$this, 'configureApplication']);

        // 步骤 4: 数据库设置
        if (!$this->option('skip-migrate')) {
            $this->step('设置数据库', [$this, 'setupDatabase']);
        }

        // 步骤 5: Filament 配置
        if (!$this->option('skip-config')) {
            $this->step('配置 Filament', [$this, 'configureFilament']);
        }

        // 步骤 6: 权限设置
        $this->step('设置权限', [$this, 'setPermissions']);

        // 步骤 7: 优化应用
        $this->step('优化应用', [$this, 'optimizeApplication']);
    }

    /**
     * 执行安装步骤
     */
    private function step($description, $callback)
    {
        $this->info("▶️  $description...");
        
        try {
            call_user_func($callback);
            $this->success("✅ $description 完成");
        } catch (\Exception $e) {
            $this->error("❌ $description 失败: " . $e->getMessage());
            $this->errors[] = "$description 失败: " . $e->getMessage();
        }
        
        $this->line('');
    }

    /**
     * 检查系统环境
     */
    private function checkEnvironment()
    {
        $this->line('  检查 PHP 版本...');
        $this->checkPhpVersion();

        $this->line('  检查必要的 PHP 扩展...');
        $this->checkPhpExtensions();

        $this->line('  检查目录结构...');
        $this->checkDirectoryStructure();

        $this->line('  检查文件权限...');
        $this->checkFilePermissions();
    }

    /**
     * 检查 PHP 版本
     */
    private function checkPhpVersion()
    {
        $phpVersion = PHP_VERSION;
        $minVersion = '8.1.0';

        if (version_compare($phpVersion, $minVersion, '>=')) {
            $this->success("  ✅ PHP 版本: $phpVersion");
        } else {
            throw new \Exception("PHP 版本过低: $phpVersion，需要 $minVersion 或更高版本");
        }
    }

    /**
     * 检查 PHP 扩展
     */
    private function checkPhpExtensions()
    {
        $requiredExtensions = [
            'mbstring',
            'openssl',
            'pdo',
            'tokenizer',
            'xml',
            'ctype',
            'json',
            'bcmath',
            'fileinfo',
            'gd',
            'zip'
        ];

        foreach ($requiredExtensions as $extension) {
            if (extension_loaded($extension)) {
                $this->line("    ✅ $extension");
            } else {
                $this->warn("    ❌ $extension 未安装");
                $this->warnings[] = "PHP 扩展 $extension 未安装";
            }
        }
    }

    /**
     * 检查目录结构
     */
    private function checkDirectoryStructure()
    {
        $requiredDirs = [
            'app',
            'config',
            'database',
            'public',
            'resources',
            'routes',
            'storage',
            'tests'
        ];

        foreach ($requiredDirs as $dir) {
            if (is_dir(base_path($dir))) {
                $this->line("    ✅ $dir/");
            } else {
                $this->warn("    ❌ $dir/ 不存在");
                $this->warnings[] = "目录 $dir 不存在";
            }
        }
    }

    /**
     * 检查文件权限
     */
    private function checkFilePermissions()
    {
        $writableDirs = [
            'storage',
            'bootstrap/cache'
        ];

        foreach ($writableDirs as $dir) {
            $fullPath = base_path($dir);
            if (is_dir($fullPath)) {
                if (is_writable($fullPath)) {
                    $this->line("    ✅ $dir/ 可写");
                } else {
                    $this->warn("    ❌ $dir/ 不可写");
                    $this->warnings[] = "目录 $dir 不可写";
                }
            }
        }
    }

    /**
     * 安装依赖
     */
    private function installDependencies()
    {
        // 检查 composer.json
        if (!File::exists(base_path('composer.json'))) {
            throw new \Exception('未找到 composer.json 文件');
        }

        // 安装 Composer 依赖
        $this->line('  安装 Composer 依赖...');
        $result = $this->runCommand('composer install --no-dev --optimize-autoloader');

        if ($result === 0) {
            $this->success('  ✅ Composer 依赖安装完成');
        } else {
            throw new \Exception('Composer 依赖安装失败');
        }

        // 检查并安装 NPM 依赖
        if (File::exists(base_path('package.json'))) {
            $this->line('  检查 NPM 依赖...');
            
            if ($this->commandExists('npm')) {
                $result = $this->runCommand('npm install');

                if ($result === 0) {
                    $this->success('  ✅ NPM 依赖安装完成');

                    // 尝试构建前端资源
                    $this->line('  构建前端资源...');
                    $buildResult = $this->runCommand('npm run build');
                    
                    if ($buildResult === 0) {
                        $this->success('  ✅ 前端资源构建完成');
                    }
                } else {
                    $this->warn('  ⚠️ NPM 依赖安装失败');
                    $this->warnings[] = 'NPM 依赖安装失败';
                }
            } else {
                $this->warn('  ⚠️ npm 未找到，跳过 NPM 依赖安装');
            }
        }
    }

    /**
     * 配置应用
     */
    private function configureApplication()
    {
        // 生成应用密钥
        $this->line('  生成应用密钥...');
        $result = $this->runCommand('php artisan key:generate --force');

        if ($result === 0) {
            $this->success('  ✅ 应用密钥生成完成');
        } else {
            throw new \Exception('应用密钥生成失败');
        }

        // 创建 .env 文件
        $this->createEnvFile();
    }

    /**
     * 创建 .env 文件
     */
    private function createEnvFile()
    {
        $envPath = base_path('.env');
        $envExamplePath = base_path('.env.example');

        if (!File::exists($envPath) && File::exists($envExamplePath)) {
            File::copy($envExamplePath, $envPath);
            $this->success('  ✅ 已创建 .env 文件');
        } elseif (File::exists($envPath)) {
            $this->line('  ✅ .env 文件已存在');
        }
    }

    /**
     * 设置数据库
     */
    private function setupDatabase()
    {
        // 检查数据库配置
        $envPath = base_path('.env');
        if (File::exists($envPath)) {
            $envContent = File::get($envPath);
            
            if (Str::contains($envContent, 'DB_CONNECTION=')) {
                $this->line('  检测到数据库配置');
                
                // 测试数据库连接
                try {
                    DB::connection()->getPdo();
                    $this->success('  ✅ 数据库连接正常');
                } catch (\Exception $e) {
                    $this->warn('  ⚠️ 数据库连接失败: ' . $e->getMessage());
                    $this->warnings[] = '数据库连接失败';
                    return;
                }

                // 运行迁移
                $this->line('  运行数据库迁移...');
                $result = $this->runCommand('php artisan migrate --force');

                if ($result === 0) {
                    $this->success('  ✅ 数据库迁移完成');
                } else {
                    $this->warn('  ⚠️ 数据库迁移失败');
                    $this->warnings[] = '数据库迁移失败';
                }
            } else {
                $this->warn('  ⚠️ 未检测到数据库配置，请手动配置 .env 文件');
                $this->warnings[] = '数据库配置缺失';
            }
        }
    }

    /**
     * 配置 Filament
     */
    private function configureFilament()
    {
        $commands = [
            'php artisan filament:install --force' => 'Filament 安装',
            'php artisan vendor:publish --tag=filament-config --force' => 'Filament 配置发布',
            'php artisan vendor:publish --tag=filament-assets --force' => 'Filament 资源发布'
        ];

        foreach ($commands as $command => $description) {
            $this->line("  $description...");
            $result = $this->runCommand($command);

            if ($result === 0) {
                $this->success("  ✅ $description 完成");
            } else {
                $this->warn("  ⚠️ $description 失败");
                $this->warnings[] = "$description 失败";
            }
        }
    }

    /**
     * 设置权限
     */
    private function setPermissions()
    {
        $writableDirs = [
            'storage',
            'bootstrap/cache'
        ];

        foreach ($writableDirs as $dir) {
            $fullPath = base_path($dir);
            if (is_dir($fullPath)) {
                if (PHP_OS_FAMILY === 'Windows') {
                    // Windows 权限检查
                    if (is_writable($fullPath)) {
                        $this->line("  ✅ $dir/ 权限正常");
                    } else {
                        $this->warn("  ⚠️ $dir/ 权限可能有问题");
                    }
                } else {
                    // Unix/Linux/macOS 权限设置
                    if (chmod($fullPath, 0775)) {
                        $this->success("  ✅ $dir/ 权限设置完成");
                    } else {
                        $this->warn("  ⚠️ $dir/ 权限设置失败");
                    }
                }
            }
        }

        // 创建存储链接
        $this->line('  创建存储链接...');
        $result = $this->runCommand('php artisan storage:link');

        if ($result === 0) {
            $this->success('  ✅ 存储链接创建完成');
        } else {
            $this->warn('  ⚠️ 存储链接创建失败');
        }
    }

    /**
     * 优化应用
     */
    private function optimizeApplication()
    {
        $cacheCommands = [
            'config:cache' => '配置缓存',
            'route:cache' => '路由缓存',
            'view:cache' => '视图缓存'
        ];

        foreach ($cacheCommands as $command => $description) {
            $this->line("  清理 $description...");
            $result = $this->runCommand("php artisan $command");

            if ($result === 0) {
                $this->success("  ✅ $description 完成");
            } else {
                $this->warn("  ⚠️ $description 失败");
            }
        }
    }

    /**
     * 显示安装结果
     */
    private function showResults()
    {
        $this->info('================================================');
        
        if (empty($this->errors)) {
            $this->info('✅ Webman-Filament 安装完成！');
        } else {
            $this->warn('⚠️ 安装完成，但有一些问题需要解决');
        }
        
        $this->info('================================================');
        $this->line('');

        // 显示错误
        if (!empty($this->errors)) {
            $this->error('错误:');
            foreach ($this->errors as $error) {
                $this->line("  ❌ $error");
            }
            $this->line('');
        }

        // 显示警告
        if (!empty($this->warnings)) {
            $this->warn('警告:');
            foreach ($this->warnings as $warning) {
                $this->line("  ⚠️ $warning");
            }
            $this->line('');
        }

        // 显示下一步操作
        $this->info('下一步操作:');
        $this->line('1. 访问您的应用 URL 查看效果');
        $this->line('2. 如果需要创建管理员账户，运行:');
        $this->line('   php artisan make:filament-user');
        $this->line('3. 查看配置文件: config/filament.php');
        $this->line('');

        $this->info('常用命令:');
        $this->line('• 启动开发服务器: php artisan serve');
        $this->line('• 清理缓存: php artisan cache:clear');
        $this->line('• 查看日志: tail -f storage/logs/laravel.log');
        $this->line('');

        $this->info('🎉 享受使用 Webman-Filament！');
    }

    /**
     * 运行命令
     */
    private function runCommand($command)
    {
        $process = new \Symfony\Component\Process\Process(explode(' ', $command), base_path());
        $process->setTimeout(300);
        $process->run();

        return $process->getExitCode();
    }

    /**
     * 检查命令是否存在
     */
    private function commandExists($command)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $output = [];
            $returnCode = 0;
            exec("where $command 2>NUL", $output, $returnCode);
            return $returnCode === 0;
        } else {
            return !empty(shell_exec("which $command"));
        }
    }
}