<?php
/**
 * Webman-Filament 通用 PHP 安装脚本
 * 
 * 这个脚本可以在任何支持 PHP 的环境中运行
 * 支持跨平台安装和配置
 * 
 * @author Webman-Filament 开发团队
 * @version 1.0.0
 */

class WebmanFilamentInstaller
{
    private $projectDir;
    private $errors = [];
    private $warnings = [];
    private $successMessages = [];
    
    public function __construct($projectDir = null)
    {
        $this->projectDir = $projectDir ?: getcwd();
    }
    
    /**
     * 运行完整的安装流程
     */
    public function install()
    {
        $this->printHeader();
        
        // 环境检查
        $this->checkEnvironment();
        
        // 安装依赖
        $this->installDependencies();
        
        // 配置应用
        $this->configureApplication();
        
        // 数据库设置
        $this->setupDatabase();
        
        // Filament 配置
        $this->configureFilament();
        
        // 权限设置
        $this->setPermissions();
        
        // 清理缓存
        $this->clearCache();
        
        // 显示结果
        $this->showResults();
    }
    
    /**
     * 打印安装程序头部信息
     */
    private function printHeader()
    {
        $this->printMessage("================================================", 'info');
        $this->printMessage("Webman-Filament 安装程序", 'info');
        $this->printMessage("================================================", 'info');
        $this->printMessage("");
    }
    
    /**
     * 检查环境
     */
    private function checkEnvironment()
    {
        $this->printMessage("正在检查环境...", 'info');
        
        // 检查 PHP 版本
        $this->checkPhpVersion();
        
        // 检查 Composer
        $this->checkComposer();
        
        // 检查必要的 PHP 扩展
        $this->checkPhpExtensions();
        
        // 检查目录结构
        $this->checkDirectoryStructure();
        
        // 检查权限
        $this->checkPermissions();
        
        $this->printMessage("");
    }
    
    /**
     * 检查 PHP 版本
     */
    private function checkPhpVersion()
    {
        $phpVersion = PHP_VERSION;
        $minVersion = '8.1.0';
        
        if (version_compare($phpVersion, $minVersion, '>=')) {
            $this->printMessage("✅ PHP 版本检查通过: $phpVersion", 'success');
        } else {
            $this->printMessage("❌ PHP 版本过低: $phpVersion，需要 $minVersion 或更高版本", 'error');
            $this->errors[] = "PHP 版本检查失败";
        }
    }
    
    /**
     * 检查 Composer
     */
    private function checkComposer()
    {
        $composerPath = $this->findComposer();
        
        if ($composerPath) {
            $version = $this->executeCommand("$composerPath --version");
            $this->printMessage("✅ Composer 检查通过: " . trim($version), 'success');
        } else {
            $this->printMessage("❌ Composer 未找到", 'error');
            $this->errors[] = "Composer 检查失败";
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
                $this->printMessage("✅ PHP 扩展 $extension 已安装", 'success');
            } else {
                $this->printMessage("❌ PHP 扩展 $extension 未安装", 'error');
                $this->errors[] = "PHP 扩展 $extension 缺失";
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
            $fullPath = $this->projectDir . '/' . $dir;
            if (is_dir($fullPath)) {
                $this->printMessage("✅ 目录 $dir 存在", 'success');
            } else {
                $this->printMessage("❌ 目录 $dir 不存在", 'warning');
                $this->warnings[] = "目录 $dir 不存在";
            }
        }
        
        // 检查重要文件
        $requiredFiles = [
            'composer.json',
            '.env.example'
        ];
        
        foreach ($requiredFiles as $file) {
            $fullPath = $this->projectDir . '/' . $file;
            if (file_exists($fullPath)) {
                $this->printMessage("✅ 文件 $file 存在", 'success');
            } else {
                $this->printMessage("❌ 文件 $file 不存在", 'warning');
                $this->warnings[] = "文件 $file 不存在";
            }
        }
    }
    
    /**
     * 检查权限
     */
    private function checkPermissions()
    {
        $writableDirs = [
            'storage',
            'bootstrap/cache'
        ];
        
        foreach ($writableDirs as $dir) {
            $fullPath = $this->projectDir . '/' . $dir;
            if (is_dir($fullPath)) {
                if (is_writable($fullPath)) {
                    $this->printMessage("✅ 目录 $dir 可写", 'success');
                } else {
                    $this->printMessage("❌ 目录 $dir 不可写", 'warning');
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
        $this->printMessage("正在安装依赖...", 'info');
        
        // 检查 composer.json
        $composerJsonPath = $this->projectDir . '/composer.json';
        if (!file_exists($composerJsonPath)) {
            $this->printMessage("❌ 未找到 composer.json 文件", 'error');
            return;
        }
        
        // 安装 PHP 依赖
        $composerPath = $this->findComposer();
        if ($composerPath) {
            $this->printMessage("安装 PHP 依赖...", 'info');
            $result = $this->executeCommand("cd {$this->projectDir} && $composerPath install --no-dev --optimize-autoloader");
            
            if ($result['exitCode'] === 0) {
                $this->printMessage("✅ PHP 依赖安装完成", 'success');
            } else {
                $this->printMessage("❌ PHP 依赖安装失败", 'error');
                $this->errors[] = "Composer 依赖安装失败";
            }
        }
        
        // 检查并安装 Node.js 依赖
        $packageJsonPath = $this->projectDir . '/package.json';
        if (file_exists($packageJsonPath)) {
            $this->printMessage("检查 Node.js 依赖...", 'info');
            
            if ($this->commandExists('npm')) {
                $result = $this->executeCommand("cd {$this->projectDir} && npm install");
                
                if ($result['exitCode'] === 0) {
                    $this->printMessage("✅ Node.js 依赖安装完成", 'success');
                    
                    // 尝试构建前端资源
                    $buildResult = $this->executeCommand("cd {$this->projectDir} && npm run build");
                    if ($buildResult['exitCode'] === 0) {
                        $this->printMessage("✅ 前端资源构建完成", 'success');
                    }
                } else {
                    $this->printMessage("⚠️ Node.js 依赖安装失败", 'warning');
                    $this->warnings[] = "NPM 依赖安装失败";
                }
            } else {
                $this->printMessage("⚠️ npm 未找到，跳过 Node.js 依赖安装", 'warning');
            }
        }
        
        $this->printMessage("");
    }
    
    /**
     * 配置应用
     */
    private function configureApplication()
    {
        $this->printMessage("正在配置应用...", 'info');
        
        // 生成应用密钥
        $this->generateAppKey();
        
        // 创建 .env 文件（如果不存在）
        $this->createEnvFile();
        
        $this->printMessage("");
    }
    
    /**
     * 生成应用密钥
     */
    private function generateAppKey()
    {
        $artisanPath = $this->projectDir . '/artisan';
        if (file_exists($artisanPath)) {
            $result = $this->executeCommand("cd {$this->projectDir} && php artisan key:generate --force");
            
            if ($result['exitCode'] === 0) {
                $this->printMessage("✅ 应用密钥生成完成", 'success');
            } else {
                $this->printMessage("❌ 应用密钥生成失败", 'error');
                $this->errors[] = "应用密钥生成失败";
            }
        }
    }
    
    /**
     * 创建 .env 文件
     */
    private function createEnvFile()
    {
        $envPath = $this->projectDir . '/.env';
        $envExamplePath = $this->projectDir . '/.env.example';
        
        if (!file_exists($envPath) && file_exists($envExamplePath)) {
            copy($envExamplePath, $envPath);
            $this->printMessage("✅ 已创建 .env 文件", 'success');
        } elseif (file_exists($envPath)) {
            $this->printMessage("✅ .env 文件已存在", 'success');
        }
    }
    
    /**
     * 设置数据库
     */
    private function setupDatabase()
    {
        $this->printMessage("正在设置数据库...", 'info');
        
        // 检查 .env 文件中的数据库配置
        $envPath = $this->projectDir . '/.env';
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            
            // 检查是否配置了数据库
            if (strpos($envContent, 'DB_CONNECTION=') !== false) {
                $this->runMigrations();
            } else {
                $this->printMessage("⚠️ 未检测到数据库配置，请手动配置 .env 文件", 'warning');
            }
        }
        
        $this->printMessage("");
    }
    
    /**
     * 运行数据库迁移
     */
    private function runMigrations()
    {
        $artisanPath = $this->projectDir . '/artisan';
        if (file_exists($artisanPath)) {
            $result = $this->executeCommand("cd {$this->projectDir} && php artisan migrate --force");
            
            if ($result['exitCode'] === 0) {
                $this->printMessage("✅ 数据库迁移完成", 'success');
            } else {
                $this->printMessage("❌ 数据库迁移失败", 'warning');
                $this->warnings[] = "数据库迁移失败";
            }
        }
    }
    
    /**
     * 配置 Filament
     */
    private function configureFilament()
    {
        $this->printMessage("正在配置 Filament...", 'info');
        
        $artisanPath = $this->projectDir . '/artisan';
        if (file_exists($artisanPath)) {
            // 发布 Filament 配置
            $commands = [
                'php artisan filament:install --force',
                'php artisan vendor:publish --tag=filament-config --force',
                'php artisan vendor:publish --tag=filament-assets --force'
            ];
            
            foreach ($commands as $command) {
                $result = $this->executeCommand("cd {$this->projectDir} && $command");
                
                if ($result['exitCode'] === 0) {
                    $this->printMessage("✅ " . explode(' ', $command)[3] . " 完成", 'success');
                } else {
                    $this->printMessage("⚠️ " . explode(' ', $command)[3] . " 失败", 'warning');
                }
            }
        }
        
        $this->printMessage("");
    }
    
    /**
     * 设置权限
     */
    private function setPermissions()
    {
        $this->printMessage("正在设置权限...", 'info');
        
        $writableDirs = [
            'storage',
            'bootstrap/cache'
        ];
        
        foreach ($writableDirs as $dir) {
            $fullPath = $this->projectDir . '/' . $dir;
            if (is_dir($fullPath)) {
                if (PHP_OS_FAMILY === 'Windows') {
                    // Windows 权限检查
                    if (is_writable($fullPath)) {
                        $this->printMessage("✅ 目录 $dir 权限正常", 'success');
                    } else {
                        $this->printMessage("⚠️ 目录 $dir 权限可能有问题", 'warning');
                    }
                } else {
                    // Unix/Linux/macOS 权限设置
                    if (chmod($fullPath, 0775)) {
                        $this->printMessage("✅ 目录 $dir 权限设置完成", 'success');
                    } else {
                        $this->printMessage("⚠️ 目录 $dir 权限设置失败", 'warning');
                    }
                }
            }
        }
        
        // 创建存储链接
        $artisanPath = $this->projectDir . '/artisan';
        if (file_exists($artisanPath)) {
            $result = $this->executeCommand("cd {$this->projectDir} && php artisan storage:link");
            
            if ($result['exitCode'] === 0) {
                $this->printMessage("✅ 存储链接创建完成", 'success');
            } else {
                $this->printMessage("⚠️ 存储链接创建失败", 'warning');
            }
        }
        
        $this->printMessage("");
    }
    
    /**
     * 清理缓存
     */
    private function clearCache()
    {
        $this->printMessage("正在清理缓存...", 'info');
        
        $artisanPath = $this->projectDir . '/artisan';
        if (file_exists($artisanPath)) {
            $cacheCommands = [
                'config:cache',
                'route:cache',
                'view:cache'
            ];
            
            foreach ($cacheCommands as $command) {
                $result = $this->executeCommand("cd {$this->projectDir} && php artisan $command");
                
                if ($result['exitCode'] === 0) {
                    $this->printMessage("✅ 缓存 $command 清理完成", 'success');
                } else {
                    $this->printMessage("⚠️ 缓存 $command 清理失败", 'warning');
                }
            }
        }
        
        $this->printMessage("");
    }
    
    /**
     * 显示安装结果
     */
    private function showResults()
    {
        $this->printMessage("================================================", 'info');
        
        if (empty($this->errors)) {
            $this->printMessage("✅ Webman-Filament 安装完成！", 'success');
        } else {
            $this->printMessage("⚠️ 安装完成，但有一些问题需要解决", 'warning');
        }
        
        $this->printMessage("================================================", 'info');
        $this->printMessage("");
        
        // 显示错误
        if (!empty($this->errors)) {
            $this->printMessage("错误:", 'error');
            foreach ($this->errors as $error) {
                $this->printMessage("  ❌ $error", 'error');
            }
            $this->printMessage("");
        }
        
        // 显示警告
        if (!empty($this->warnings)) {
            $this->printMessage("警告:", 'warning');
            foreach ($this->warnings as $warning) {
                $this->printMessage("  ⚠️ $warning", 'warning');
            }
            $this->printMessage("");
        }
        
        // 显示下一步操作
        $this->printMessage("下一步操作:", 'info');
        $this->printMessage("1. 访问您的应用 URL 查看效果", 'info');
        $this->printMessage("2. 如果需要创建管理员账户，运行：", 'info');
        $this->printMessage("   php artisan make:filament-user", 'info');
        $this->printMessage("3. 查看配置文件：config/filament.php", 'info');
        $this->printMessage("");
        
        $this->printMessage("常用命令:", 'info');
        $this->printMessage("• 启动开发服务器：php artisan serve", 'info');
        $this->printMessage("• 清理缓存：php artisan cache:clear", 'info');
        $this->printMessage("• 查看日志：tail -f storage/logs/laravel.log", 'info');
        $this->printMessage("");
        
        $this->printMessage("享受使用 Webman-Filament！", 'success');
    }
    
    /**
     * 打印消息
     */
    private function printMessage($message, $type = 'info')
    {
        $timestamp = date('Y-m-d H:i:s');
        
        switch ($type) {
            case 'success':
                echo "\033[0;32m[{$timestamp}] ✅ {$message}\033[0m\n";
                break;
            case 'error':
                echo "\033[0;31m[{$timestamp}] ❌ {$message}\033[0m\n";
                break;
            case 'warning':
                echo "\033[1;33m[{$timestamp}] ⚠️  {$message}\033[0m\n";
                break;
            default:
                echo "\033[0;34m[{$timestamp}] ℹ️  {$message}\033[0m\n";
                break;
        }
    }
    
    /**
     * 查找 Composer 可执行文件
     */
    private function findComposer()
    {
        $possiblePaths = [
            'composer',
            'composer.phar',
            'C:\Composer\Composer.phar',
            '/usr/local/bin/composer',
            '/usr/bin/composer'
        ];
        
        foreach ($possiblePaths as $path) {
            if ($this->commandExists($path)) {
                return $path;
            }
        }
        
        return null;
    }
    
    /**
     * 检查命令是否存在
     */
    private function commandExists($command)
    {
        $output = [];
        $returnCode = 0;
        
        if (PHP_OS_FAMILY === 'Windows') {
            exec("where $command 2>NUL", $output, $returnCode);
        } else {
            exec("which $command 2>/dev/null", $output, $returnCode);
        }
        
        return $returnCode === 0;
    }
    
    /**
     * 执行命令
     */
    private function executeCommand($command)
    {
        $output = [];
        $returnCode = 0;
        
        exec($command . ' 2>&1', $output, $returnCode);
        
        return [
            'output' => implode("\n", $output),
            'exitCode' => $returnCode
        ];
    }
}

// 如果直接运行此脚本
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $installer = new WebmanFilamentInstaller();
    $installer->install();
}