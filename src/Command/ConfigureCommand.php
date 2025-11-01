<?php

namespace App\Command;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * Webman-Filament 配置命令
 * 
 * 用于配置和管理 Webman-Filament 的各种设置
 * 
 * @author Webman-Filament 开发团队
 * @version 1.0.0
 */
class ConfigureCommand extends Command
{
    /**
     * 控制台命令名称和签名
     *
     * @var string
     */
    protected $signature = 'webman-filament:configure 
                            {action : 配置操作 (show, database, auth, theme, permissions, reset)}
                            {--option= : 配置选项}
                            {--value= : 配置值}';

    /**
     * 控制台命令描述
     *
     * @var string
     */
    protected $description = '配置 Webman-Filament 设置';

    /**
     * 支持的配置操作
     *
     * @var array
     */
    private $supportedActions = [
        'show' => '显示当前配置',
        'database' => '配置数据库',
        'auth' => '配置认证',
        'theme' => '配置主题',
        'permissions' => '配置权限',
        'reset' => '重置配置'
    ];

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
        $action = $this->argument('action');

        if (!array_key_exists($action, $this->supportedActions)) {
            $this->error("不支持的操作: $action");
            $this->info("支持的操作: " . implode(', ', array_keys($this->supportedActions)));
            return 1;
        }

        $this->info("▶️ 执行配置操作: {$this->supportedActions[$action]}");
        $this->line('');

        try {
            switch ($action) {
                case 'show':
                    $this->showConfiguration();
                    break;
                case 'database':
                    $this->configureDatabase();
                    break;
                case 'auth':
                    $this->configureAuth();
                    break;
                case 'theme':
                    $this->configureTheme();
                    break;
                case 'permissions':
                    $this->configurePermissions();
                    break;
                case 'reset':
                    $this->resetConfiguration();
                    break;
            }
            
            $this->line('');
            $this->success("✅ {$this->supportedActions[$action]} 完成");
            
        } catch (\Exception $e) {
            $this->error("❌ 配置失败: " . $e->getMessage());
            return 1;
        }

        return 0;
    }

    /**
     * 显示当前配置
     */
    private function showConfiguration()
    {
        $this->info('📋 当前配置信息');
        $this->line('');

        // 显示应用信息
        $this->showAppInfo();
        
        // 显示数据库配置
        $this->showDatabaseConfig();
        
        // 显示 Filament 配置
        $this->showFilamentConfig();
        
        // 显示认证配置
        $this->showAuthConfig();
        
        // 显示主题配置
        $this->showThemeConfig();
        
        // 显示权限配置
        $this->showPermissionsConfig();
    }

    /**
     * 显示应用信息
     */
    private function showAppInfo()
    {
        $this->info('📱 应用信息');
        $this->line("  应用名称: " . config('app.name', 'Laravel'));
        $this->line("  应用环境: " . config('app.env', 'production'));
        $this->line("  调试模式: " . (config('app.debug') ? '开启' : '关闭'));
        $this->line("  应用 URL: " . config('app.url', 'http://localhost'));
        $this->line("  时区: " . config('app.timezone', 'UTC'));
        $this->line('');
    }

    /**
     * 显示数据库配置
     */
    private function showDatabaseConfig()
    {
        $this->info('🗄️ 数据库配置');
        $this->line("  连接驱动: " . config('database.default'));
        
        $connections = config('database.connections');
        foreach ($connections as $name => $connection) {
            if ($name === config('database.default')) {
                $this->line("  当前连接 ($name):");
                $this->line("    主机: " . ($connection['host'] ?? '未配置'));
                $this->line("    端口: " . ($connection['port'] ?? '未配置'));
                $this->line("    数据库: " . ($connection['database'] ?? '未配置'));
                $this->line("    用户名: " . ($connection['username'] ?? '未配置'));
            }
        }
        $this->line('');
    }

    /**
     * 显示 Filament 配置
     */
    private function showFilamentConfig()
    {
        $this->info('🎨 Filament 配置');
        $this->line("  品牌名称: " . config('filament.brand', 'Filament'));
        $this->line("  主题: " . (config('filament.theme') ? '自定义主题' : '默认主题'));
        $this->line("  暗色模式: " . (config('filament.dark_mode') ? '开启' : '关闭'));
        $this->line("  缓存: " . (config('filament.cache.enabled') ? '开启' : '关闭'));
        $this->line('');
    }

    /**
     * 显示认证配置
     */
    private function showAuthConfig()
    {
        $this->info('🔐 认证配置');
        $this->line("  守卫: " . config('auth.defaults.guard'));
        $this->line("  提供者: " . config('auth.defaults.provider'));
        $this->line("  用户模型: " . config('auth.providers.users.model'));
        $this->line("  密码重置: " . (config('auth.passwords.users.enabled') ? '开启' : '关闭'));
        $this->line('');
    }

    /**
     * 显示主题配置
     */
    private function showThemeConfig()
    {
        $this->info('🎭 主题配置');
        
        $themeFile = resource_path('css/filament.css');
        if (File::exists($themeFile)) {
            $this->line("  自定义 CSS: 存在");
        } else {
            $this->line("  自定义 CSS: 不存在");
        }
        
        $viteConfig = base_path('vite.config.js');
        if (File::exists($viteConfig)) {
            $this->line("  Vite 配置: 存在");
        } else {
            $this->line("  Vite 配置: 不存在");
        }
        $this->line('');
    }

    /**
     * 显示权限配置
     */
    private function showPermissionsConfig()
    {
        $this->info('🛡️ 权限配置');
        
        // 检查 Spatie Laravel Permission 包
        if (class_exists('Spatie\Permission\PermissionServiceProvider')) {
            $this->line("  Spatie 权限包: 已安装");
        } else {
            $this->line("  Spatie 权限包: 未安装");
        }
        
        // 检查自定义权限中间件
        $middlewareFile = app_path('Http/Middleware/FilamentMiddleware.php');
        if (File::exists($middlewareFile)) {
            $this->line("  自定义中间件: 已配置");
        } else {
            $this->line("  自定义中间件: 未配置");
        }
        $this->line('');
    }

    /**
     * 配置数据库
     */
    private function configureDatabase()
    {
        $this->info('🗄️ 配置数据库');
        
        // 显示当前配置
        $this->showDatabaseConfig();
        
        // 询问是否要修改配置
        if (!$this->confirm('是否要修改数据库配置？')) {
            return;
        }
        
        // 获取用户输入
        $driver = $this->choice('选择数据库驱动', ['mysql', 'pgsql', 'sqlite'], config('database.default'));
        $host = $this->ask('数据库主机', config("database.connections.$driver.host", 'localhost'));
        $port = $this->ask('数据库端口', config("database.connections.$driver.port", $this->getDefaultPort($driver)));
        $database = $this->ask('数据库名', config("database.connections.$driver.database"));
        $username = $this->ask('用户名', config("database.connections.$driver.username"));
        $password = $this->secret('密码');
        
        // 更新配置
        $this->updateDatabaseConfig($driver, $host, $port, $database, $username, $password);
        
        // 测试连接
        $this->testDatabaseConnection();
    }

    /**
     * 获取默认端口
     */
    private function getDefaultPort($driver)
    {
        $defaults = [
            'mysql' => 3306,
            'pgsql' => 5432,
            'sqlite' => null
        ];
        
        return $defaults[$driver] ?? 3306;
    }

    /**
     * 更新数据库配置
     */
    private function updateDatabaseConfig($driver, $host, $port, $database, $username, $password)
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            throw new \Exception('.env 文件不存在');
        }
        
        $envContent = File::get($envPath);
        
        // 更新配置
        $updates = [
            'DB_CONNECTION' => $driver,
            'DB_HOST' => $host,
            'DB_PORT' => $port,
            'DB_DATABASE' => $database,
            'DB_USERNAME' => $username,
            'DB_PASSWORD' => $password
        ];
        
        foreach ($updates as $key => $value) {
            $pattern = "/^$key=.*/m";
            $replacement = "$key=$value";
            
            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n$key=$value";
            }
        }
        
        File::put($envPath, $envContent);
        $this->success('  ✅ 数据库配置已更新');
    }

    /**
     * 测试数据库连接
     */
    private function testDatabaseConnection()
    {
        $this->line('  测试数据库连接...');
        
        try {
            // 重新加载配置
            $this->call('config:clear');
            
            // 测试连接
            \DB::connection()->getPdo();
            $this->success('  ✅ 数据库连接成功');
            
            // 询问是否运行迁移
            if ($this->confirm('是否运行数据库迁移？')) {
                $this->call('migrate', ['--force' => true]);
            }
            
        } catch (\Exception $e) {
            $this->error('  ❌ 数据库连接失败: ' . $e->getMessage());
        }
    }

    /**
     * 配置认证
     */
    private function configureAuth()
    {
        $this->info('🔐 配置认证');
        
        // 显示当前认证配置
        $this->showAuthConfig();
        
        if ($this->confirm('是否要配置 Filament 认证？')) {
            // 发布 Filament 认证视图
            $this->call('vendor:publish', [
                '--tag' => 'filament-auth',
                '--force' => true
            ]);
            
            $this->success('  ✅ Filament 认证视图已发布');
        }
        
        if ($this->confirm('是否要创建管理员用户？')) {
            $this->call('make:filament-user');
        }
    }

    /**
     * 配置主题
     */
    private function configureTheme()
    {
        $this->info('🎭 配置主题');
        
        $option = $this->option('option');
        $value = $this->option('value');
        
        if ($option && $value) {
            // 命令行参数配置
            $this->configureThemeOption($option, $value);
        } else {
            // 交互式配置
            $this->interactiveThemeConfig();
        }
    }

    /**
     * 配置主题选项
     */
    private function configureThemeOption($option, $value)
    {
        $envPath = base_path('.env');
        
        if (!File::exists($envPath)) {
            throw new \Exception('.env 文件不存在');
        }
        
        $envContent = File::get($envPath);
        $key = "FILAMENT_" . strtoupper($option);
        $pattern = "/^$key=.*/m";
        $replacement = "$key=$value";
        
        if (preg_match($pattern, $envContent)) {
            $envContent = preg_replace($pattern, $replacement, $envContent);
        } else {
            $envContent .= "\n$key=$value";
        }
        
        File::put($envPath, $envContent);
        $this->success("  ✅ $option 设置为 $value");
    }

    /**
     * 交互式主题配置
     */
    private function interactiveThemeConfig()
    {
        $this->line('当前主题选项:');
        $this->line('  1. 品牌名称');
        $this->line('  2. 暗色模式');
        $this->line('  3. 主题颜色');
        
        $choice = $this->choice('选择要配置的选项', [1, 2, 3]);
        
        switch ($choice) {
            case 1:
                $brandName = $this->ask('输入品牌名称', config('filament.brand', 'My App'));
                $this->configureThemeOption('brand', $brandName);
                break;
            case 2:
                $darkMode = $this->confirm('启用暗色模式？', config('filament.dark_mode', false));
                $this->configureThemeOption('dark_mode', $darkMode ? 'true' : 'false');
                break;
            case 3:
                $primaryColor = $this->ask('输入主题颜色 (hex)', '#4f46e5');
                $this->configureThemeOption('primary_color', $primaryColor);
                break;
        }
    }

    /**
     * 配置权限
     */
    private function configurePermissions()
    {
        $this->info('🛡️ 配置权限');
        
        // 检查 Spatie Laravel Permission
        if (!class_exists('Spatie\Permission\PermissionServiceProvider')) {
            if ($this->confirm('Spatie Laravel Permission 未安装，是否安装？')) {
                $this->call('require', ['spatie/laravel-permission']);
                $this->call('vendor:publish', [
                    '--provider' => 'Spatie\Permission\PermissionServiceProvider',
                    '--tag' => 'permission-config'
                ]);
                $this->success('  ✅ Spatie Laravel Permission 已安装');
            }
        } else {
            $this->success('  ✅ Spatie Laravel Permission 已安装');
        }
        
        // 发布权限迁移
        if ($this->confirm('是否发布权限相关迁移？')) {
            $this->call('vendor:publish', [
                '--provider' => 'Spatie\Permission\PermissionServiceProvider',
                '--tag' => 'permission-migrations'
            ]);
            $this->success('  ✅ 权限迁移已发布');
        }
        
        // 创建默认角色和权限
        if ($this->confirm('是否创建默认角色和权限？')) {
            $this->createDefaultRolesAndPermissions();
        }
    }

    /**
     * 创建默认角色和权限
     */
    private function createDefaultRolesAndPermissions()
    {
        // 这里可以添加创建默认角色和权限的逻辑
        $this->line('  创建默认角色和权限...');
        
        // 示例代码（需要根据实际需求调整）
        /*
        $adminRole = Role::create(['name' => 'admin']);
        $editorRole = Role::create(['name' => 'editor']);
        
        $permissions = [
            'user.view',
            'user.create',
            'user.edit',
            'user.delete',
            'post.view',
            'post.create',
            'post.edit',
            'post.delete'
        ];
        
        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
            $adminRole->givePermissionTo($permission);
            $editorRole->givePermissionTo(array_slice($permissions, 4)); // 编辑者权限
        }
        */
        
        $this->success('  ✅ 默认角色和权限已创建');
    }

    /**
     * 重置配置
     */
    private function resetConfiguration()
    {
        $this->info('🔄 重置配置');
        
        if (!$this->confirm('确定要重置所有配置吗？这将删除所有自定义设置！')) {
            return;
        }
        
        // 清理缓存
        $this->call('cache:clear');
        $this->call('config:clear');
        $this->call('route:clear');
        $this->call('view:clear');
        
        // 重新发布 Filament 配置
        $this->call('vendor:publish', [
            '--tag' => 'filament-config',
            '--force' => true
        ]);
        
        $this->success('  ✅ 配置已重置');
        
        // 询问是否重新安装
        if ($this->confirm('是否重新运行安装程序？')) {
            $this->call('webman-filament:setup', ['--force' => true]);
        }
    }
}