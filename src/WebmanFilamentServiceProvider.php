<?php

declare(strict_types=1);

namespace WebmanFilament;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use WebmanFilament\Bridge\FilamentBridge;
use WebmanFilament\Adapter\RequestResponseAdapter;
use WebmanFilament\Adapter\ContainerAdapter;
use WebmanFilament\Adapter\RouteAdapter;
use WebmanFilament\Adapter\MiddlewareAdapter;
use WebmanFilament\Support\LaravelContainerBridge;

/**
 * webman-filament 扩展核心服务提供者
 * 
 * 负责集成 Filament 到 webman 框架，包括：
 * - 生命周期桥接与初始化
 * - 服务容器适配
 * - 路由与中间件桥接
 * - 适配器模式实现
 */
class WebmanFilamentServiceProvider extends ServiceProvider
{
    /**
     * 扩展包基本信息
     */
    protected $packageName = 'webman-filament';
    protected $packageVersion = '1.0.0';

    /**
     * 服务提供者是否延迟加载
     */
    protected $defer = false;

    /**
     * 核心适配器实例
     */
    protected FilamentBridge $bridge;
    protected RequestResponseAdapter $requestResponseAdapter;
    protected ContainerAdapter $containerAdapter;
    protected RouteAdapter $routeAdapter;
    protected MiddlewareAdapter $middlewareAdapter;

    /**
     * 注册服务
     */
    public function register(): void
    {
        // 注册配置文件
        $this->mergeConfigFrom(
            __DIR__ . '/../config/filament.php',
            'filament'
        );

        // 初始化适配器层
        $this->initializeAdapters();

        // 注册核心服务
        $this->registerCoreServices();

        // 注册适配器服务
        $this->registerAdapterServices();

        // 注册面板提供者
        $this->registerPanelProviders();
    }

    /**
     * 启动服务
     */
    public function boot(): void
    {
        // 发布配置文件
        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__ . '/../config/filament.php' => config_path('filament.php'),
            ], 'filament-config');
        }

        // 发布静态资源
        if (function_exists('public_path')) {
            $this->publishes([
                __DIR__ . '/../public' => public_path('filament'),
            ], 'filament-assets');
        }

        // 初始化 Filament 面板
        $this->initializeFilament();

        // 注册路由
        $this->registerRoutes();

        // 注册中间件
        $this->registerMiddleware();

        // 启动桥接器
        $this->bootstrapBridge();
    }

    /**
     * 初始化适配器层
     */
    protected function initializeAdapters(): void
    {
        // 初始化容器适配器
        $this->containerAdapter = new ContainerAdapter(
            new LaravelContainerBridge($this->app->getContainer())
        );

        // 初始化请求响应适配器
        $this->requestResponseAdapter = new RequestResponseAdapter();

        // 初始化路由适配器
        $this->routeAdapter = new RouteAdapter(
            $this->containerAdapter
        );

        // 初始化中间件适配器
        $this->middlewareAdapter = new MiddlewareAdapter(
            $this->containerAdapter
        );

        // 初始化 Filament 桥接器
        $this->bridge = new FilamentBridge(
            $this->containerAdapter,
            $this->requestResponseAdapter,
            $this->routeAdapter,
            $this->middlewareAdapter
        );
    }

    /**
     * 注册核心服务
     */
    protected function registerCoreServices(): void
    {
        // 注册 Filament 桥接器
        $this->app->singleton(FilamentBridge::class, function () {
            return $this->bridge;
        });

        // 注册请求响应适配器
        $this->app->singleton(RequestResponseAdapter::class, function () {
            return $this->requestResponseAdapter;
        });

        // 注册容器适配器
        $this->app->singleton(ContainerAdapter::class, function () {
            return $this->containerAdapter;
        });
    }

    /**
     * 注册适配器服务
     */
    protected function registerAdapterServices(): void
    {
        // 注册路由适配器
        $this->app->singleton(RouteAdapter::class, function () {
            return $this->routeAdapter;
        });

        // 注册中间件适配器
        $this->app->singleton(MiddlewareAdapter::class, function () {
            return $this->middlewareAdapter;
        });

        // 注册生命周期事件监听器
        $this->registerLifecycleListeners();
    }

    /**
     * 注册面板提供者
     */
    protected function registerPanelProviders(): void
    {
        // 从配置中获取面板提供者
        $panelProviders = config('filament.panel_providers', []);
        
        foreach ($panelProviders as $panelId => $providerClass) {
            if (class_exists($providerClass)) {
                $this->app->singleton($providerClass);
            }
        }
    }

    /**
     * 初始化 Filament 面板
     */
    protected function initializeFilament(): void
    {
        // 设置 Filament 配置
        $this->configureFilament();

        // 注册面板
        $this->registerPanels();

        // 初始化插件
        $this->initializePlugins();
    }

    /**
     * 配置 Filament
     */
    protected function configureFilament(): void
    {
        // 设置默认面板配置
        $defaultPanelConfig = config('filament.default_panel', []);
        
        if (!empty($defaultPanelConfig)) {
            // 这里可以添加 Filament 面板的默认配置
            // 例如：面板 ID、路径、中间件等
        }
    }

    /**
     * 注册面板
     */
    protected function registerPanels(): void
    {
        $panels = config('filament.panels', []);
        
        foreach ($panels as $panelId => $panelConfig) {
            $this->bridge->registerPanel($panelId, $panelConfig);
        }
    }

    /**
     * 初始化插件
     */
    protected function initializePlugins(): void
    {
        $plugins = config('filament.plugins', []);
        
        foreach ($plugins as $pluginClass) {
            if (class_exists($pluginClass)) {
                $this->bridge->registerPlugin($pluginClass);
            }
        }
    }

    /**
     * 注册路由
     */
    protected function registerRoutes(): void
    {
        // 检查是否启用路由自动注册
        if (!config('filament.auto_register_routes', true)) {
            return;
        }

        // 获取路由配置
        $routeConfig = config('filament.routes', []);
        
        // 注册 Filament 路由
        $this->routeAdapter->registerFilamentRoutes($routeConfig);
    }

    /**
     * 注册中间件
     */
    protected function registerMiddleware(): void
    {
        // 注册 Filament 中间件
        $this->middlewareAdapter->registerFilamentMiddleware();
    }

    /**
     * 启动桥接器
     */
    protected function bootstrapBridge(): void
    {
        // 触发桥接器启动事件
        $this->bridge->onStart();
    }

    /**
     * 注册生命周期事件监听器
     */
    protected function registerLifecycleListeners(): void
    {
        // webman 启动事件
        if (function_exists('event_add')) {
            event_add('webman.start', [$this, 'onWebmanStart']);
            event_add('webman.reload', [$this, 'onWebmanReload']);
            event_add('webman.stop', [$this, 'onWebmanStop']);
        }
    }

    /**
     * webman 启动事件处理
     */
    public function onWebmanStart(): void
    {
        $this->bridge->onStart();
    }

    /**
     * webman 重载事件处理
     */
    public function onWebmanReload(): void
    {
        $this->bridge->onReload();
    }

    /**
     * webman 停止事件处理
     */
    public function onWebmanStop(): void
    {
        $this->bridge->onStop();
    }

    /**
     * 获取提供的服务
     */
    public function provides(): array
    {
        return [
            FilamentBridge::class,
            RequestResponseAdapter::class,
            ContainerAdapter::class,
            RouteAdapter::class,
            MiddlewareAdapter::class,
        ];
    }
}