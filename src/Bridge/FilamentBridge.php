<?php

declare(strict_types=1);

namespace WebmanFilament\Bridge;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Filament\Panel;
use Filament\Filament;
use WebmanFilament\Adapter\ContainerAdapter;
use WebmanFilament\Adapter\RequestResponseAdapter;
use WebmanFilament\Adapter\RouteAdapter;
use WebmanFilament\Adapter\MiddlewareAdapter;

/**
 * Filament 桥接器主类
 * 
 * 负责在 webman 生命周期与 Filament 之间建立桥接，包括：
 * - 生命周期事件处理
 * - 面板注册与管理
 * - 插件管理
 * - 路由与中间件桥接
 * - 资产注册与缓存
 */
class FilamentBridge
{
    /**
     * 容器适配器
     */
    protected ContainerAdapter $containerAdapter;

    /**
     * 请求响应适配器
     */
    protected RequestResponseAdapter $requestResponseAdapter;

    /**
     * 路由适配器
     */
    protected RouteAdapter $routeAdapter;

    /**
     * 中间件适配器
     */
    protected MiddlewareAdapter $middlewareAdapter;

    /**
     * 已注册的面板列表
     */
    protected array $registeredPanels = [];

    /**
     * 已注册的插件列表
     */
    protected array $registeredPlugins = [];

    /**
     * 生命周期状态
     */
    protected bool $isInitialized = false;
    protected bool $isStarted = false;
    protected bool $isReloaded = false;

    /**
     * 构造函数
     */
    public function __construct(
        ContainerAdapter $containerAdapter,
        RequestResponseAdapter $requestResponseAdapter,
        RouteAdapter $routeAdapter,
        MiddlewareAdapter $middlewareAdapter
    ) {
        $this->containerAdapter = $containerAdapter;
        $this->requestResponseAdapter = $requestResponseAdapter;
        $this->routeAdapter = $routeAdapter;
        $this->middlewareAdapter = $middlewareAdapter;
    }

    /**
     * webman 启动事件处理
     */
    public function onStart(): void
    {
        try {
            Log::info('[FilamentBridge] 开始启动 Filament 桥接器');

            // 初始化 Filament 环境
            $this->initializeFilamentEnvironment();

            // 注册面板
            $this->registerPanels();

            // 注册插件
            $this->registerPlugins();

            // 初始化路由
            $this->initializeRoutes();

            // 初始化中间件
            $this->initializeMiddleware();

            // 注册资产
            $this->registerAssets();

            // 执行迁移（如果需要）
            $this->runMigrationsIfNeeded();

            // 标记为已启动
            $this->isStarted = true;
            $this->isInitialized = true;

            Log::info('[FilamentBridge] Filament 桥接器启动完成', [
                'panels' => count($this->registeredPanels),
                'plugins' => count($this->registeredPlugins),
            ]);

        } catch (\Exception $e) {
            Log::error('[FilamentBridge] 启动失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * webman 重载事件处理
     */
    public function onReload(): void
    {
        try {
            Log::info('[FilamentBridge] 开始重载 Filament 桥接器');

            // 清理路由缓存
            $this->clearRouteCache();

            // 重新注册面板
            $this->reregisterPanels();

            // 重新注册插件
            $this->reregisterPlugins();

            // 重新初始化路由
            $this->reinitializeRoutes();

            // 标记为已重载
            $this->isReloaded = true;

            Log::info('[FilamentBridge] Filament 桥接器重载完成');

        } catch (\Exception $e) {
            Log::error('[FilamentBridge] 重载失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * webman 停止事件处理
     */
    public function onStop(): void
    {
        try {
            Log::info('[FilamentBridge] 开始停止 Filament 桥接器');

            // 清理资源
            $this->cleanupResources();

            // 断开连接
            $this->disconnectConnections();

            // 标记为已停止
            $this->isStarted = false;

            Log::info('[FilamentBridge] Filament 桥接器停止完成');

        } catch (\Exception $e) {
            Log::error('[FilamentBridge] 停止失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * 路由注册后事件处理
     */
    public function onRouteRegistered(): void
    {
        try {
            Log::debug('[FilamentBridge] 路由注册完成');

            // 验证路由保护策略
            $this->validateRouteProtection();

            // 检查 fallback 配置
            $this->checkFallbackConfiguration();

        } catch (\Exception $e) {
            Log::error('[FilamentBridge] 路由注册后处理失败', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 资产加载后事件处理
     */
    public function onAssetsReady(): void
    {
        try {
            Log::debug('[FilamentBridge] 资产加载完成');

            // 生成资产缓存清单
            $this->generateAssetCacheManifest();

            // 更新资产版本
            $this->updateAssetVersions();

        } catch (\Exception $e) {
            Log::error('[FilamentBridge] 资产加载后处理失败', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * 注册面板
     */
    public function registerPanel(string $panelId, array $config): void
    {
        try {
            Log::info("[FilamentBridge] 注册面板: {$panelId}");

            // 获取面板提供者
            $panelProvider = $this->getPanelProvider($panelId);
            
            if (!$panelProvider) {
                throw new \RuntimeException("面板提供者未找到: {$panelId}");
            }

            // 创建面板实例
            $panel = $this->createPanel($panelId, $config, $panelProvider);

            // 注册面板
            Filament::registerPanel($panel);

            // 记录已注册的面板
            $this->registeredPanels[$panelId] = [
                'panel' => $panel,
                'config' => $config,
                'provider' => $panelProvider,
                'registered_at' => now(),
            ];

            Log::info("[FilamentBridge] 面板注册成功: {$panelId}");

        } catch (\Exception $e) {
            Log::error("[FilamentBridge] 面板注册失败: {$panelId}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 注册插件
     */
    public function registerPlugin(string $pluginClass): void
    {
        try {
            if (!class_exists($pluginClass)) {
                throw new \RuntimeException("插件类不存在: {$pluginClass}");
            }

            Log::info("[FilamentBridge] 注册插件: {$pluginClass}");

            // 创建插件实例
            $plugin = new $pluginClass();

            // 注册插件到所有面板
            foreach ($this->registeredPanels as $panelId => $panelData) {
                $panelData['panel']->plugin($plugin);
            }

            // 记录已注册的插件
            $this->registeredPlugins[] = [
                'class' => $pluginClass,
                'instance' => $plugin,
                'registered_at' => now(),
                'panels' => array_keys($this->registeredPanels),
            ];

            Log::info("[FilamentBridge] 插件注册成功: {$pluginClass}");

        } catch (\Exception $e) {
            Log::error("[FilamentBridge] 插件注册失败: {$pluginClass}", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 初始化 Filament 环境
     */
    protected function initializeFilamentEnvironment(): void
    {
        // 设置 Filament 配置
        $this->configureFilament();

        // 初始化 Filament 内核
        $this->initializeFilamentKernel();

        // 设置错误处理
        $this->setupErrorHandling();
    }

    /**
     * 配置 Filament
     */
    protected function configureFilament(): void
    {
        // 从配置中获取 Filament 设置
        $config = config('filament', []);

        // 设置默认配置
        $this->setDefaultConfiguration($config);
    }

    /**
     * 初始化 Filament 内核
     */
    protected function initializeFilamentKernel(): void
    {
        // 这里可以添加 Filament 内核的初始化逻辑
        // 例如：设置配置、注册服务等
    }

    /**
     * 设置错误处理
     */
    protected function setupErrorHandling(): void
    {
        // 设置 Filament 错误处理
        // 这里可以添加自定义错误处理逻辑
    }

    /**
     * 注册面板
     */
    protected function registerPanels(): void
    {
        $panels = config('filament.panels', []);

        foreach ($panels as $panelId => $panelConfig) {
            $this->registerPanel($panelId, $panelConfig);
        }
    }

    /**
     * 注册插件
     */
    protected function registerPlugins(): void
    {
        $plugins = config('filament.plugins', []);

        foreach ($plugins as $pluginClass) {
            $this->registerPlugin($pluginClass);
        }
    }

    /**
     * 初始化路由
     */
    protected function initializeRoutes(): void
    {
        $this->routeAdapter->initializeRoutes();
    }

    /**
     * 初始化中间件
     */
    protected function initializeMiddleware(): void
    {
        $this->middlewareAdapter->initializeMiddleware();
    }

    /**
     * 注册资产
     */
    protected function registerAssets(): void
    {
        // 注册 Filament 资产
        $this->registerFilamentAssets();

        // 触发资产就绪事件
        $this->onAssetsReady();
    }

    /**
     * 注册 Filament 资产
     */
    protected function registerFilamentAssets(): void
    {
        // 这里可以添加 Filament 资产的注册逻辑
        // 例如：CSS、JS 文件的注册
    }

    /**
     * 执行迁移（如果需要）
     */
    protected function runMigrationsIfNeeded(): void
    {
        // 检查是否需要运行迁移
        if ($this->shouldRunMigrations()) {
            Log::info('[FilamentBridge] 开始运行 Filament 迁移');

            try {
                // 运行 Filament 迁移
                Artisan::call('filament:install', ['--panels' => true]);
                
                Log::info('[FilamentBridge] Filament 迁移完成');

            } catch (\Exception $e) {
                Log::warning('[FilamentBridge] Filament 迁移失败', [
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 清理路由缓存
     */
    protected function clearRouteCache(): void
    {
        // 清理 Filament 路由缓存
        Artisan::call('route:clear');
    }

    /**
     * 重新注册面板
     */
    protected function reregisterPanels(): void
    {
        // 清理已注册的面板
        $this->registeredPanels = [];

        // 重新注册面板
        $this->registerPanels();
    }

    /**
     * 重新注册插件
     */
    protected function reregisterPlugins(): void
    {
        // 清理已注册的插件
        $this->registeredPlugins = [];

        // 重新注册插件
        $this->registerPlugins();
    }

    /**
     * 重新初始化路由
     */
    protected function reinitializeRoutes(): void
    {
        $this->routeAdapter->reinitializeRoutes();
    }

    /**
     * 清理资源
     */
    protected function cleanupResources(): void
    {
        // 清理缓存
        $this->cleanupCache();

        // 清理临时文件
        $this->cleanupTempFiles();
    }

    /**
     * 断开连接
     */
    protected function disconnectConnections(): void
    {
        // 断开数据库连接
        \DB::disconnect();

        // 断开缓存连接
        \Cache::store()->getStore()->flush();
    }

    /**
     * 获取面板提供者
     */
    protected function getPanelProvider(string $panelId): ?object
    {
        $panelProviders = config('filament.panel_providers', []);
        
        return $panelProviders[$panelId] ?? null;
    }

    /**
     * 创建面板实例
     */
    protected function createPanel(string $panelId, array $config, string $providerClass): Panel
    {
        // 创建面板实例
        $panel = Panel::make($panelId)
            ->id($config['id'] ?? $panelId)
            ->path($config['path'] ?? $panelId)
            ->title($config['title'] ?? 'Admin Panel')
            ->shortTitle($config['short_title'] ?? 'Admin');

        // 设置其他配置
        if (isset($config['domain'])) {
            $panel->domain($config['domain']);
        }

        if (isset($config['url'])) {
            $panel->url($config['url']);
        }

        if (isset($config['csp'])) {
            $panel->csp($config['csp']);
        }

        // 设置中间件
        if (isset($config['middleware'])) {
            $panel->middleware($config['middleware']);
        }

        // 设置认证配置
        if (isset($config['auth'])) {
            $panel->auth($config['auth']);
        }

        // 设置数据库配置
        if (isset($config['database'])) {
            $panel->database($config['database']);
        }

        // 设置发现路径
        if (isset($config['discover_resources_in'])) {
            $panel->discoverResourcesIn($config['discover_resources_in']);
        }

        if (isset($config['discover_pages_in'])) {
            $panel->discoverPagesIn($config['discover_pages_in']);
        }

        if (isset($config['discover_widgets_in'])) {
            $panel->discoverWidgetsIn($config['discover_widgets_in']);
        }

        // 设置页面
        if (isset($config['pages'])) {
            $panel->pages($config['pages']);
        }

        // 设置资源
        if (isset($config['resources'])) {
            $panel->resources($config['resources']);
        }

        // 设置小组件
        if (isset($config['widgets'])) {
            $panel->widgets($config['widgets']);
        }

        // 设置通知
        if (isset($config['notifications'])) {
            $panel->notifications($config['notifications']);
        }

        // 设置暗色模式
        if (isset($config['dark_mode'])) {
            $panel->darkMode($config['dark_mode']);
        }

        // 设置主题
        if (isset($config['theme'])) {
            $panel->theme($config['theme']);
        }

        return $panel;
    }

    /**
     * 设置默认配置
     */
    protected function setDefaultConfiguration(array $config): void
    {
        // 设置默认配置值
        // 这里可以添加默认配置逻辑
    }

    /**
     * 验证路由保护策略
     */
    protected function validateRouteProtection(): void
    {
        // 验证路由保护策略
        // 这里可以添加验证逻辑
    }

    /**
     * 检查 fallback 配置
     */
    protected function checkFallbackConfiguration(): void
    {
        // 检查 fallback 配置
        // 这里可以添加检查逻辑
    }

    /**
     * 生成资产缓存清单
     */
    protected function generateAssetCacheManifest(): void
    {
        // 生成资产缓存清单
        // 这里可以添加清单生成逻辑
    }

    /**
     * 更新资产版本
     */
    protected function updateAssetVersions(): void
    {
        // 更新资产版本
        // 这里可以添加版本更新逻辑
    }

    /**
     * 是否应该运行迁移
     */
    protected function shouldRunMigrations(): bool
    {
        return config('filament.run_migrations', false);
    }

    /**
     * 清理缓存
     */
    protected function cleanupCache(): void
    {
        // 清理缓存
        // 这里可以添加缓存清理逻辑
    }

    /**
     * 清理临时文件
     */
    protected function cleanupTempFiles(): void
    {
        // 清理临时文件
        // 这里可以添加临时文件清理逻辑
    }

    /**
     * 获取已注册的面板
     */
    public function getRegisteredPanels(): array
    {
        return $this->registeredPanels;
    }

    /**
     * 获取已注册的插件
     */
    public function getRegisteredPlugins(): array
    {
        return $this->registeredPlugins;
    }

    /**
     * 检查是否已初始化
     */
    public function isInitialized(): bool
    {
        return $this->isInitialized;
    }

    /**
     * 检查是否已启动
     */
    public function isStarted(): bool
    {
        return $this->isStarted;
    }

    /**
     * 检查是否已重载
     */
    public function isReloaded(): bool
    {
        return $this->isReloaded;
    }
}