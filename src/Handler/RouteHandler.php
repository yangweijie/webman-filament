<?php

namespace WebmanFilament\Handler;

use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response as IlluminateResponse;
use WebmanFilament\Bridge\FilamentBridge;
use WebmanFilament\Translator\RequestTranslator;
use WebmanFilament\Translator\ResponseTranslator;
use WebmanFilament\Container\ContainerAdapter;
use Psr\Container\ContainerInterface;
use Illuminate\Routing\RouteCollection;
use Illuminate\Routing\Route;
use Throwable;

/**
 * 路由处理器
 * 
 * 负责 Filament 面板路由注册、匹配与保护策略
 * 实现路由映射与 fallback 处理
 */
class RouteHandler
{
    /**
     * @var FilamentBridge
     */
    protected FilamentBridge $bridge;

    /**
     * @var RequestTranslator
     */
    protected RequestTranslator $requestTranslator;

    /**
     * @var ResponseTranslator
     */
    protected ResponseTranslator $responseTranslator;

    /**
     * @var ContainerAdapter
     */
    protected ContainerAdapter $containerAdapter;

    /**
     * @var ContainerInterface
     */
    protected ContainerInterface $container;

    /**
     * @var RouteCollection
     */
    protected RouteCollection $routes;

    /**
     * 路由保护策略配置
     * @var array
     */
    protected array $protectionStrategies = [
        'auth' => ['middleware' => ['web', 'auth']],
        'guest' => ['middleware' => ['web', 'guest']],
        'public' => ['middleware' => ['web']],
    ];

    /**
     * Filament 面板路径配置
     * @var array
     */
    protected array $panelPaths = [
        'admin' => '/admin',
        'dashboard' => '/dashboard',
        'panel' => '/panel',
    ];

    /**
     * 路由映射表
     * @var array
     */
    protected array $routeMappings = [
        'filament.auth.login' => ['path' => '/admin/login', 'middleware' => ['web', 'guest']],
        'filament.auth.logout' => ['path' => '/admin/logout', 'middleware' => ['web', 'auth']],
        'filament.auth.password.request' => ['path' => '/admin/password/reset', 'middleware' => ['web', 'guest']],
        'filament.auth.password.reset' => ['path' => '/admin/password/reset/{token}', 'middleware' => ['web', 'guest']],
        'filament.dashboard' => ['path' => '/admin', 'middleware' => ['web', 'auth']],
        'filament.resources.index' => ['path' => '/admin/resources/{resource}', 'middleware' => ['web', 'auth']],
        'filament.pages.index' => ['path' => '/admin/pages/{page}', 'middleware' => ['web', 'auth']],
    ];

    public function __construct(
        FilamentBridge $bridge,
        RequestTranslator $requestTranslator,
        ResponseTranslator $responseTranslator,
        ContainerAdapter $containerAdapter,
        ContainerInterface $container
    ) {
        $this->bridge = $bridge;
        $this->requestTranslator = $requestTranslator;
        $this->responseTranslator = $responseTranslator;
        $this->containerAdapter = $containerAdapter;
        $this->container = $container;
        $this->routes = new RouteCollection();
    }

    /**
     * 注册 Filament 面板路由
     */
    public function registerPanelRoutes(): void
    {
        try {
            // 获取面板配置
            $panelConfig = $this->bridge->getPanelConfig();
            
            // 注册面板路由
            $this->registerFilamentRoutes($panelConfig);
            
            // 注册资源路由
            $this->registerResourceRoutes();
            
            // 注册页面路由
            $this->registerPageRoutes();
            
            // 注册动作路由
            $this->registerActionRoutes();
            
            // 设置 fallback 路由
            $this->setupFallbackRoutes();
            
        } catch (Throwable $e) {
            error_log("RouteHandler: Failed to register panel routes: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 注册 Filament 核心路由
     */
    protected function registerFilamentRoutes(array $panelConfig): void
    {
        $panelId = $panelConfig['id'] ?? 'default';
        $path = $panelConfig['path'] ?? '/admin';
        
        // 认证路由
        $this->registerAuthRoutes($path);
        
        // 面板主页路由
        $this->registerPanelHomeRoute($path);
    }

    /**
     * 注册认证路由
     */
    protected function registerAuthRoutes(string $panelPath): void
    {
        $authRoutes = [
            'login' => $panelPath . '/login',
            'logout' => $panelPath . '/logout',
            'password.request' => $panelPath . '/password/reset',
            'password.reset' => $panelPath . '/password/reset/{token}',
        ];

        foreach ($authRoutes as $name => $path) {
            $route = new Route(['GET', 'POST'], $path, [
                'uses' => [$this, 'handleAuthRoute'],
                'as' => 'filament.auth.' . $name,
            ]);
            
            // 添加中间件
            $route->middleware($this->getMiddlewareForRoute('auth', $name));
            
            $this->routes->add('filament.auth.' . $name, $route);
        }
    }

    /**
     * 注册面板主页路由
     */
    protected function registerPanelHomeRoute(string $panelPath): void
    {
        $route = new Route(['GET'], $panelPath, [
            'uses' => [$this, 'handlePanelRoute'],
            'as' => 'filament.dashboard',
        ]);
        
        $route->middleware($this->getMiddlewareForRoute('dashboard'));
        
        $this->routes->add('filament.dashboard', $route);
    }

    /**
     * 注册资源路由
     */
    protected function registerResourceRoutes(): void
    {
        $resources = $this->bridge->getRegisteredResources();
        
        foreach ($resources as $resource) {
            $this->registerSingleResourceRoutes($resource);
        }
    }

    /**
     * 注册单个资源路由
     */
    protected function registerSingleResourceRoutes(array $resource): void
    {
        $resourceName = $resource['name'];
        $basePath = '/admin/resources/' . $resourceName;
        
        // 资源列表
        $this->addResourceRoute($resourceName, 'index', $basePath, 'GET');
        
        // 创建资源
        $this->addResourceRoute($resourceName, 'create', $basePath . '/create', 'GET');
        
        // 编辑资源
        $this->addResourceRoute($resourceName, 'edit', $basePath . '/{record}/edit', 'GET');
        
        // 查看资源
        $this->addResourceRoute($resourceName, 'view', $basePath . '/{record}', 'GET');
        
        // 资源操作
        $this->addResourceRoute($resourceName, 'action', $basePath . '/{record}/actions/{action}', 'POST');
    }

    /**
     * 添加资源路由
     */
    protected function addResourceRoute(string $resourceName, string $action, string $path, string $method): void
    {
        $routeName = "filament.resources.{$resourceName}.{$action}";
        
        $route = new Route([$method], $path, [
            'uses' => [$this, 'handleResourceRoute'],
            'as' => $routeName,
        ]);
        
        $route->middleware($this->getMiddlewareForRoute('resource', $action));
        
        $this->routes->add($routeName, $route);
    }

    /**
     * 注册页面路由
     */
    protected function registerPageRoutes(): void
    {
        $pages = $this->bridge->getRegisteredPages();
        
        foreach ($pages as $page) {
            $pageName = $page['name'];
            $pagePath = $page['path'] ?? '/admin/pages/' . $pageName;
            
            $route = new Route(['GET'], $pagePath, [
                'uses' => [$this, 'handlePageRoute'],
                'as' => 'filament.pages.' . $pageName,
            ]);
            
            $route->middleware($this->getMiddlewareForRoute('page'));
            
            $this->routes->add('filament.pages.' . $pageName, $route);
        }
    }

    /**
     * 注册动作路由
     */
    protected function registerActionRoutes(): void
    {
        $route = new Route(['POST'], '/admin/actions/{action}', [
            'uses' => [$this, 'handleActionRoute'],
            'as' => 'filament.actions.trigger',
        ]);
        
        $route->middleware($this->getMiddlewareForRoute('action'));
        
        $this->routes->add('filament.actions.trigger', $route);
    }

    /**
     * 设置 fallback 路由
     */
    protected function setupFallbackRoutes(): void
    {
        // 404 处理
        $fallbackRoute = new Route(['GET', 'POST'], '/{path}', [
            'uses' => [$this, 'handleFallback'],
        ]);
        
        $fallbackRoute->middleware(['web']);
        
        $this->routes->add('filament.fallback', $fallbackRoute);
    }

    /**
     * 匹配路由
     */
    public function matchRoute(IlluminateRequest $request): ?Route
    {
        $uri = $request->path();
        $method = $request->method();

        // 遍历所有路由进行匹配
        foreach ($this->routes as $name => $route) {
            if ($this->routeMatches($route, $uri, $method)) {
                return $route;
            }
        }

        return null;
    }

    /**
     * 检查路由是否匹配
     */
    protected function routeMatches(Route $route, string $uri, string $method): bool
    {
        // 检查 HTTP 方法
        if (!in_array($method, $route->methods())) {
            return false;
        }

        // 检查 URI 模式
        $pattern = $route->getPath();
        return $this->patternMatches($pattern, $uri);
    }

    /**
     * 模式匹配
     */
    protected function patternMatches(string $pattern, string $uri): bool
    {
        // 简单的模式匹配实现
        // 实际实现中可以使用更复杂的路由解析器
        return fnmatch($pattern, $uri);
    }

    /**
     * 获取路由中间件
     */
    protected function getMiddlewareForRoute(string $type, string $action = ''): array
    {
        switch ($type) {
            case 'auth':
                return ['web', 'guest'];
            case 'dashboard':
                return ['web', 'auth'];
            case 'resource':
                return $this->getResourceMiddleware($action);
            case 'page':
                return ['web', 'auth'];
            case 'action':
                return ['web', 'auth'];
            default:
                return ['web'];
        }
    }

    /**
     * 获取资源中间件
     */
    protected function getResourceMiddleware(string $action): array
    {
        $baseMiddleware = ['web'];
        
        switch ($action) {
            case 'index':
            case 'view':
                return array_merge($baseMiddleware, ['auth']);
            case 'create':
            case 'edit':
                return array_merge($baseMiddleware, ['auth']);
            case 'action':
                return array_merge($baseMiddleware, ['auth']);
            default:
                return array_merge($baseMiddleware, ['auth']);
        }
    }

    /**
     * 处理认证路由
     */
    public function handleAuthRoute(IlluminateRequest $request): IlluminateResponse
    {
        return $this->bridge->handleAuthRoute($request);
    }

    /**
     * 处理面板路由
     */
    public function handlePanelRoute(IlluminateRequest $request): IlluminateResponse
    {
        return $this->bridge->handlePanelRoute($request);
    }

    /**
     * 处理资源路由
     */
    public function handleResourceRoute(IlluminateRequest $request): IlluminateResponse
    {
        return $this->bridge->handleResourceRoute($request);
    }

    /**
     * 处理页面路由
     */
    public function handlePageRoute(IlluminateRequest $request): IlluminateResponse
    {
        return $this->bridge->handlePageRoute($request);
    }

    /**
     * 处理动作路由
     */
    public function handleActionRoute(IlluminateRequest $request): IlluminateResponse
    {
        return $this->bridge->handleActionRoute($request);
    }

    /**
     * 处理 fallback 路由
     */
    public function handleFallback(IlluminateRequest $request): IlluminateResponse
    {
        return new IlluminateResponse('Not Found', 404);
    }

    /**
     * 获取路由映射
     */
    public function getRouteMappings(): array
    {
        return $this->routeMappings;
    }

    /**
     * 获取保护策略
     */
    public function getProtectionStrategies(): array
    {
        return $this->protectionStrategies;
    }

    /**
     * 获取注册的路由数量
     */
    public function getRouteCount(): int
    {
        return $this->routes->count();
    }

    /**
     * 获取所有路由
     */
    public function getRoutes(): array
    {
        return $this->routes->all();
    }
}