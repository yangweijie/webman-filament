<?php
/**
 * Webman-Filament 环境检查脚本
 * 
 * 用于检查系统环境是否满足 Webman-Filament 的运行要求
 * 
 * @author Webman-Filament 开发团队
 * @version 1.0.0
 */

class EnvironmentChecker
{
    private $errors = [];
    private $warnings = [];
    private $info = [];
    private $checks = [];

    public function __construct()
    {
        $this->checks = [
            'php_version' => 'PHP 版本检查',
            'php_extensions' => 'PHP 扩展检查',
            'composer' => 'Composer 检查',
            'node_npm' => 'Node.js 和 NPM 检查',
            'directories' => '目录结构检查',
            'permissions' => '文件权限检查',
            'database' => '数据库连接检查',
            'memory_limit' => '内存限制检查',
            'upload_limit' => '上传限制检查',
            'execution_time' => '执行时间限制检查'
        ];
    }

    /**
     * 运行所有检查
     */
    public function checkAll()
    {
        $this->printHeader();
        
        foreach ($this->checks as $check => $description) {
            $this->printCheckStart($description);
            call_user_func([$this, $check]);
            $this->printCheckEnd();
        }
        
        $this->printResults();
    }

    /**
     * 打印头部信息
     */
    private function printHeader()
    {
        echo "\033[1;36m";
        echo "================================================\n";
        echo "      Webman-Filament 环境检查程序\n";
        echo "================================================\n";
        echo "\033[0m\n";
        
        $this->info[] = "检查开始时间: " . date('Y-m-d H:i:s');
        $this->info[] = "PHP 版本: " . PHP_VERSION;
        $this->info[] = "操作系统: " . PHP_OS;
        $this->info[] = "服务器软件: " . ($_SERVER['SERVER_SOFTWARE'] ?? '未知');
        echo "\n";
    }

    /**
     * 打印检查开始
     */
    private function printCheckStart($description)
    {
        echo "\033[1;34m▶️  $description...\033[0m\n";
    }

    /**
     * 打印检查结束
     */
    private function printCheckEnd()
    {
        echo "\n";
    }

    /**
     * 检查 PHP 版本
     */
    private function php_version()
    {
        $phpVersion = PHP_VERSION;
        $minVersion = '8.1.0';
        $recommendedVersion = '8.2.0';
        
        if (version_compare($phpVersion, $minVersion, '>=')) {
            if (version_compare($phpVersion, $recommendedVersion, '>=')) {
                $this->printSuccess("PHP 版本符合要求: $phpVersion");
            } else {
                $this->printWarning("PHP 版本符合最低要求: $phpVersion，建议升级到 $recommendedVersion 或更高版本");
            }
        } else {
            $this->printError("PHP 版本过低: $phpVersion，需要 $minVersion 或更高版本");
        }
    }

    /**
     * 检查 PHP 扩展
     */
    private function php_extensions()
    {
        $requiredExtensions = [
            'mbstring' => '多字节字符串支持',
            'openssl' => 'OpenSSL 加密支持',
            'pdo' => 'PDO 数据库抽象层',
            'tokenizer' => 'PHP 标记器',
            'xml' => 'XML 支持',
            'ctype' => '字符类型检查',
            'json' => 'JSON 支持',
            'bcmath' => 'BCMath 任意精度数学',
            'fileinfo' => '文件信息检测',
            'gd' => 'GD 图像处理',
            'zip' => 'ZIP 压缩支持',
            'curl' => 'cURL 客户端',
            'dom' => 'DOM 文档对象模型'
        ];

        $optionalExtensions = [
            'redis' => 'Redis 缓存支持',
            'memcached' => 'Memcached 缓存支持',
            'imagick' => 'ImageMagick 图像处理',
            'intl' => '国际化支持'
        ];

        foreach ($requiredExtensions as $ext => $description) {
            if (extension_loaded($ext)) {
                $this->printSuccess("✅ $ext - $description");
            } else {
                $this->printError("❌ $ext - $description (必需)");
            }
        }

        foreach ($optionalExtensions as $ext => $description) {
            if (extension_loaded($ext)) {
                $this->printSuccess("✅ $ext - $description (可选)");
            } else {
                $this->printWarning("⚠️  $ext - $description (可选)");
            }
        }
    }

    /**
     * 检查 Composer
     */
    private function composer()
    {
        $composerPath = $this->findComposer();
        
        if ($composerPath) {
            $version = $this->executeCommand("$composerPath --version");
            $this->printSuccess("Composer 已安装: " . trim($version));
            
            // 检查 Composer 版本
            if (preg_match('/Composer version (\d+\.\d+\.\d+)/', $version, $matches)) {
                $composerVersion = $matches[1];
                if (version_compare($composerVersion, '2.0.0', '>=')) {
                    $this->printSuccess("Composer 版本符合要求: $composerVersion");
                } else {
                    $this->printWarning("Composer 版本较低: $composerVersion，建议升级到 2.0.0 或更高版本");
                }
            }
        } else {
            $this->printError("Composer 未找到，请安装 Composer");
        }
    }

    /**
     * 检查 Node.js 和 NPM
     */
    private function node_npm()
    {
        if ($this->commandExists('node')) {
            $nodeVersion = $this->executeCommand('node --version');
            $this->printSuccess("Node.js 已安装: " . trim($nodeVersion));
            
            if ($this->commandExists('npm')) {
                $npmVersion = $this->executeCommand('npm --version');
                $this->printSuccess("npm 已安装: " . trim($npmVersion));
            } else {
                $this->printWarning("npm 未找到，部分前端功能可能不可用");
            }
        } else {
            $this->printWarning("Node.js 未安装，部分前端功能可能不可用");
        }
    }

    /**
     * 检查目录结构
     */
    private function directories()
    {
        $requiredDirs = [
            'app' => '应用核心目录',
            'config' => '配置文件目录',
            'database' => '数据库目录',
            'public' => '公共文件目录',
            'resources' => '资源文件目录',
            'routes' => '路由文件目录',
            'storage' => '存储目录',
            'tests' => '测试目录'
        ];

        foreach ($requiredDirs as $dir => $description) {
            if (is_dir($dir)) {
                $this->printSuccess("✅ $dir/ - $description");
            } else {
                $this->printError("❌ $dir/ - $description (缺失)");
            }
        }

        // 检查重要文件
        $requiredFiles = [
            'composer.json' => 'Composer 依赖配置',
            '.env.example' => '环境变量示例',
            'artisan' => 'Laravel 命令行工具'
        ];

        foreach ($requiredFiles as $file => $description) {
            if (file_exists($file)) {
                $this->printSuccess("✅ $file - $description");
            } else {
                $this->printError("❌ $file - $description (缺失)");
            }
        }
    }

    /**
     * 检查文件权限
     */
    private function permissions()
    {
        $writableDirs = [
            'storage' => '存储目录',
            'bootstrap/cache' => '缓存目录'
        ];

        foreach ($writableDirs as $dir => $description) {
            if (is_dir($dir)) {
                if (is_writable($dir)) {
                    $this->printSuccess("✅ $dir/ - $description (可写)");
                } else {
                    $this->printError("❌ $dir/ - $description (不可写)");
                }
            } else {
                $this->printWarning("⚠️  $dir/ - $description (不存在)");
            }
        }

        // 检查 public 目录权限
        if (is_dir('public')) {
            if (is_readable('public')) {
                $this->printSuccess("✅ public/ - 公共目录 (可读)");
            } else {
                $this->printError("❌ public/ - 公共目录 (不可读)");
            }
        }
    }

    /**
     * 检查数据库连接
     */
    private function database()
    {
        $envFile = '.env';
        if (!file_exists($envFile)) {
            $this->printWarning("⚠️  .env 文件不存在，无法检查数据库连接");
            return;
        }

        // 简单的环境变量解析
        $env = $this->parseEnvFile($envFile);
        
        if (empty($env['DB_CONNECTION']) || $env['DB_CONNECTION'] === 'sqlite') {
            $this->printInfo("ℹ️  使用 SQLite 数据库或未配置数据库");
            return;
        }

        try {
            switch ($env['DB_CONNECTION']) {
                case 'mysql':
                    $this->testMysqlConnection($env);
                    break;
                case 'pgsql':
                    $this->testPgsqlConnection($env);
                    break;
                default:
                    $this->printInfo("ℹ️  数据库类型: " . $env['DB_CONNECTION']);
            }
        } catch (\Exception $e) {
            $this->printError("❌ 数据库连接失败: " . $e->getMessage());
        }
    }

    /**
     * 测试 MySQL 连接
     */
    private function testMysqlConnection($env)
    {
        $host = $env['DB_HOST'] ?? 'localhost';
        $port = $env['DB_PORT'] ?? 3306;
        $database = $env['DB_DATABASE'] ?? '';
        $username = $env['DB_USERNAME'] ?? 'root';
        $password = $env['DB_PASSWORD'] ?? '';

        if (!extension_loaded('pdo_mysql')) {
            throw new \Exception('pdo_mysql 扩展未安装');
        }

        $dsn = "mysql:host=$host;port=$port;dbname=$database";
        $pdo = new PDO($dsn, $username, $password);
        
        $this->printSuccess("✅ MySQL 数据库连接正常");
        
        // 检查版本
        $version = $pdo->query('SELECT VERSION()')->fetchColumn();
        $this->printInfo("ℹ️  MySQL 版本: $version");
    }

    /**
     * 测试 PostgreSQL 连接
     */
    private function testPgsqlConnection($env)
    {
        $host = $env['DB_HOST'] ?? 'localhost';
        $port = $env['DB_PORT'] ?? 5432;
        $database = $env['DB_DATABASE'] ?? '';
        $username = $env['DB_USERNAME'] ?? 'postgres';
        $password = $env['DB_PASSWORD'] ?? '';

        if (!extension_loaded('pdo_pgsql')) {
            throw new \Exception('pdo_pgsql 扩展未安装');
        }

        $dsn = "pgsql:host=$host;port=$port;dbname=$database";
        $pdo = new PDO($dsn, $username, $password);
        
        $this->printSuccess("✅ PostgreSQL 数据库连接正常");
        
        // 检查版本
        $version = $pdo->query('SELECT version()')->fetchColumn();
        $this->printInfo("ℹ️  PostgreSQL 版本: $version");
    }

    /**
     * 检查内存限制
     */
    private function memory_limit()
    {
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $requiredBytes = 256 * 1024 * 1024; // 256MB
        $recommendedBytes = 512 * 1024 * 1024; // 512MB

        if ($memoryLimitBytes >= $recommendedBytes) {
            $this->printSuccess("✅ 内存限制: $memoryLimit (符合推荐)");
        } elseif ($memoryLimitBytes >= $requiredBytes) {
            $this->printWarning("⚠️  内存限制: $memoryLimit (符合最低要求，建议 $recommendedBytes)");
        } else {
            $this->printError("❌ 内存限制: $memoryLimit (需要至少 256M)");
        }
    }

    /**
     * 检查上传限制
     */
    private function upload_limit()
    {
        $uploadMaxFilesize = ini_get('upload_max_filesize');
        $postMaxSize = ini_get('post_max_size');
        
        $uploadBytes = $this->convertToBytes($uploadMaxFilesize);
        $postBytes = $this->convertToBytes($postMaxSize);
        $requiredBytes = 10 * 1024 * 1024; // 10MB

        if ($uploadBytes >= $requiredBytes && $postBytes >= $requiredBytes) {
            $this->printSuccess("✅ 上传限制: 上传文件 $uploadMaxFilesize, POST 数据 $postMaxSize");
        } else {
            $this->printWarning("⚠️  上传限制可能不足: 上传文件 $uploadMaxFilesize, POST 数据 $postMaxSize");
        }
    }

    /**
     * 检查执行时间限制
     */
    private function execution_time()
    {
        $maxExecutionTime = ini_get('max_execution_time');
        $maxInputTime = ini_get('max_input_time');
        
        if ($maxExecutionTime == 0 || $maxExecutionTime >= 300) {
            $this->printSuccess("✅ 执行时间限制: $maxExecutionTime 秒");
        } else {
            $this->printWarning("⚠️  执行时间限制较低: $maxExecutionTime 秒，建议至少 300 秒");
        }
        
        if ($maxInputTime == 0 || $maxInputTime >= 300) {
            $this->printSuccess("✅ 输入时间限制: $maxInputTime 秒");
        } else {
            $this->printWarning("⚠️  输入时间限制较低: $maxInputTime 秒，建议至少 300 秒");
        }
    }

    /**
     * 打印检查结果
     */
    private function printResults()
    {
        echo "\033[1;36m";
        echo "================================================\n";
        echo "              检查结果汇总\n";
        echo "================================================\n";
        echo "\033[0m\n";

        $totalChecks = count($this->checks);
        $errorCount = count($this->errors);
        $warningCount = count($this->warnings);

        echo "总检查项目: $totalChecks\n";
        echo "错误: $errorCount\n";
        echo "警告: $warningCount\n";
        echo "\n";

        if ($errorCount > 0) {
            echo "\033[1;31m错误列表:\033[0m\n";
            foreach ($this->errors as $error) {
                echo "❌ $error\n";
            }
            echo "\n";
        }

        if ($warningCount > 0) {
            echo "\033[1;33m警告列表:\033[0m\n";
            foreach ($this->warnings as $warning) {
                echo "⚠️  $warning\n";
            }
            echo "\n";
        }

        // 显示建议
        $this->printRecommendations();

        // 显示状态
        if ($errorCount > 0) {
            echo "\033[1;31m❌ 环境检查未通过，请解决上述错误后重新检查\033[0m\n";
        } elseif ($warningCount > 0) {
            echo "\033[1;33m⚠️  环境检查基本通过，但有一些警告\033[0m\n";
        } else {
            echo "\033[1;32m✅ 环境检查完全通过！\033[0m\n";
        }
    }

    /**
     * 打印建议
     */
    private function printRecommendations()
    {
        echo "\033[1;36m建议:\033[0m\n";
        echo "• 确保所有必需的 PHP 扩展都已安装\n";
        echo "• 建议使用 PHP 8.2 或更高版本以获得最佳性能\n";
        echo "• 建议设置内存限制为 512M 或更高\n";
        echo "• 在生产环境中禁用调试模式\n";
        echo "• 定期更新依赖包到最新版本\n";
        echo "\n";
    }

    /**
     * 打印成功消息
     */
    private function printSuccess($message)
    {
        echo "\033[0;32m  ✅ $message\033[0m\n";
    }

    /**
     * 打印错误消息
     */
    private function printError($message)
    {
        echo "\033[0;31m  ❌ $message\033[0m\n";
        $this->errors[] = $message;
    }

    /**
     * 打印警告消息
     */
    private function printWarning($message)
    {
        echo "\033[1;33m  ⚠️  $message\033[0m\n";
        $this->warnings[] = $message;
    }

    /**
     * 打印信息消息
     */
    private function printInfo($message)
    {
        echo "\033[0;34m  ℹ️  $message\033[0m\n";
        $this->info[] = $message;
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
            '/usr/bin/composer',
            '/opt/homebrew/bin/composer', // macOS Apple Silicon
            'C:\Program Files\Composer\composer.phar'
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

        return implode("\n", $output);
    }

    /**
     * 解析 .env 文件
     */
    private function parseEnvFile($file)
    {
        $env = [];
        
        if (!file_exists($file)) {
            return $env;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $env[trim($key)] = trim($value, '"\'');
            }
        }

        return $env;
    }

    /**
     * 转换字节单位
     */
    private function convertToBytes($value)
    {
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
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
}

// 如果直接运行此脚本
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    $checker = new EnvironmentChecker();
    $checker->checkAll();
}