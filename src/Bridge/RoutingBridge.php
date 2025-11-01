<?php

namespace FilamentWebmanAdapter\Bridge;

use Illuminate\Support\Facades\Route;
use Psr\Log\LoggerInterface;
use Throwable;
use Webman\Route as WebmanRoute;
use Workerman\Protocols\Http\Request as WebmanRequest;
use Workerman\Protocols\Http\Response as WebmanResponse;

/**
 * 路由桥接器
 * 
 * 将 Filament 的面板路由注册与 webman 的路由系统对齐
 * 支持路由保护、fallback 处理、认证中间件集成
 */
class RoutingBridge
{
    private LoggerInterface $logger;
    private array $registeredRoutes = [];
    private array $routeGroups = [];
    private array $protectedRoutes = [];
    private array $fallbackRoutes = [];
    private array $middlewareGroups = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->initializeMiddlewareGroups();
        $this->log('info', 'RoutingBridge initialized');
    }

    /**
     * 初始化中间件组
     */
    private function initializeMiddlewareGroups(): void
    {
        // Laravel 中间件组映射到 webman
        $this->middlewareGroups = [
            'web' => [
                'webman\middleware\Session::class',
                'webman\middleware\Csrf::class',
                'webman\middleware\SecurityHeaders::class'
            ],
            'auth' => [
                'FilamentWebmanAdapter\Middleware\AuthMiddleware'
            ],
            'guest' => [
                'FilamentWebmanAdapter\Middleware\GuestMiddleware'
            ],
            'throttle' => [
                'FilamentWebmanAdapter\Middleware\ThrottleMiddleware'
            ]
        ];
    }

    /**
     * 注册 Filament 面板路由
     */
    public function registerFilamentRoutes(string $panelPath = '/admin', array $middleware = ['web', 'auth']): void
    {
        try {
            // 注册面板基础路由
            $this->registerPanelBaseRoutes($panelPath, $middleware);
            
            // 注册资源路由
            $this->registerResourceRoutes($panelPath, $middleware);
            
            // 注册页面路由
            $this->registerPageRoutes($panelPath, $middleware);
            
            // 注册动作路由
            $this->registerActionRoutes($panelPath, $middleware);
            
            $this->log('info', 'Filament routes registered', [
                'panel_path' => $panelPath,
                'middleware' => $middleware
            ]);
        } catch (Throwable $e) {
            $this->log('error', 'Failed to register Filament routes: ' . $e->getMessage());
            throw new \RuntimeException("Failed to register Filament routes", 0, $e);
        }
    }

    /**
     * 注册面板基础路由
     */
    private function registerPanelBaseRoutes(string $panelPath, array $middleware): void
    {
        $basePath = rtrim($panelPath, '/');
        
        // 面板首页路由
        $this->registerRoute('GET', $basePath, [
            'controller' => 'FilamentWebmanAdapter\Controllers\PanelController@index',
            'middleware' => $middleware,
            'name' => 'filament.panel.index'
        ]);
        
        // 登录路由
        $this->registerRoute('GET', "{$basePath}/login", [
            'controller' => 'FilamentWebmanAdapter\Controllers\AuthController@showLogin',
            'middleware' => ['web', 'guest'],
            'name' => 'filament.admin.auth.login'
        ]);
        
        $this->registerRoute('POST', "{$basePath}/login", [
            'controller' => 'FilamentWebmanAdapter\Controllers\AuthController@login',
            'middleware' => ['web', 'guest'],
            'name' => 'filament.admin.auth.login.attempt'
        ]);
        
        // 登出路由
        $this->registerRoute('POST', "{$basePath}/logout", [
            'controller' => 'FilamentWebmanAdapter\Controllers\AuthController@logout',
            'middleware' => ['web', 'auth'],
            'name' => 'filament.admin.auth.logout'
        ]);
        
        // 密码重置路由
        $this->registerRoute('GET', "{$basePath}/password/reset", [
            'controller' => 'FilamentWebmanAdapter\Controllers\AuthController@showForgotPassword',
            'middleware' => ['web', 'guest'],
            'name' => 'filament.admin.auth.passwords.email'
        ]);
        
        $this->registerRoute('POST', "{$basePath}/password/reset", [
            'controller' => 'FilamentWebmanAdapter\Controllers\AuthController@sendResetLink',
            'middleware' => ['web', 'guest'],
            'name' => 'filament.admin.auth.passwords.send'
        ]);
        
        $this->registerRoute('GET', "{$basePath}/password/reset/{token}", [
            'controller' => 'FilamentWebmanAdapter\Controllers\AuthController@showResetPassword',
            'middleware' => ['web', 'guest'],
            'name' => 'filament.admin.auth.passwords.reset'
        ]);
        
        $this->registerRoute('POST', "{$basePath}/password/reset/{token}", [
            'controller' => 'FilamentWebmanAdapter\Controllers\AuthController@resetPassword',
            'middleware' => ['web', 'guest'],
            'name' => 'filament.admin.auth.passwords.update'
        ]);
    }

    /**
     * 注册资源路由
     */
    private function registerResourceRoutes(string $panelPath, array $middleware): void
    {
        $basePath = rtrim($panelPath, '/');
        
        // 资源列表路由
        $this->registerRoute('GET', "{$basePath}/resources/{resource}", [
            'controller' => 'FilamentWebmanAdapter\Controllers\ResourceController@index',
            'middleware' => $middleware,
            'name' => 'filament.resources.index'
        ]);
        
        // 资源创建路由
        $this->registerRoute('GET', "{$basePath}/resources/{resource}/create", [
            'controller' => 'FilamentWebmanAdapter\Controllers\ResourceController@create',
            'middleware' => $middleware,
            'name' => 'filament.resources.create'
        ]);
        
        $this->registerRoute('POST', "{$basePath}/resources/{resource}", [
            'controller' => 'FilamentWebmanAdapter\Controllers\ResourceController@store',
            'middleware' => $middleware,
            'name' => 'filament.resources.store'
        ]);
        
        // 资源编辑路由
        $this->registerRoute('GET', "{$basePath}/resources/{resource}/{record}/edit", [
            'controller' => 'FilamentWebmanAdapter\Controllers\ResourceController@edit',
            'middleware' => $middleware,
            'name' => 'filament.resources.edit'
        ]);
        
        $this->registerRoute('PUT', "{$basePath}/resources/{resource}/{record}", [
            'controller' => 'FilamentWebmanAdapter\Controllers\ResourceController@update',
            'middleware' => $middleware,
            'name' => 'filament.resources.update'
        ]);
        
        // 资源删除路由
        $this->registerRoute('DELETE', "{$basePath}/resources/{resource}/{record}", [
            'controller' => 'FilamentWebmanAdapter\Controllers\ResourceController@destroy',
            'middleware' => $middleware,
            'name' => 'filament.resources.destroy'
        ]);
        
        // 资源查看路由
        $this->registerRoute('GET', "{$basePath}/resources/{resource}/{record}", [
            'controller' => 'FilamentWebmanAdapter\Controllers\ResourceController@show',
            'middleware' => $middleware,
            'name' => 'filament.resources.show'
        ]);
    }

    /**
     * 注册页面路由
     */
    private function registerPageRoutes(string $panelPath, array $middleware): void
    {
        $basePath = rtrim($panelPath, '/');
        
        $this->registerRoute('GET', "{$basePath}/pages/{page}", [
            'controller' => 'FilamentWebmanAdapter\Controllers\PageController@show',
            'middleware' => $middleware,
            'name' => 'filament.pages.show'
        ]);
    }

    /**
     * 注册动作路由
     */
    private function registerActionRoutes(string $panelPath, array $middleware): void
    {
        $basePath = rtrim($panelPath, '/');
        
        $this->registerRoute('POST', "{$basePath}/actions/{action}", [
            'controller' => 'FilamentWebmanAdapter\Controllers\ActionController@execute',
            'middleware' => $middleware,
            'name' => 'filament.actions.execute'
        ]);
    }

    /**
     * 注册路由
     */
    private function registerRoute(string $method, string $path, array $options): void
    {
        try {
            $routeName = $options['name'] ?? null;
            $middleware = $options['middleware'] ?? [];
            $controller = $options['controller'] ?? null;
            
            // 构建 webman 路由
            $webmanRoute = WebmanRoute::{$this->normalizeMethod($method)}($path, function(WebmanRequest $request) use ($controller, $options) {
                return $this->handleRoute($request, $controller, $options);
            });
            
            // 添加中间件
            if (!empty($middleware)) {
                $webmanRoute->middleware($this->resolveMiddleware($middleware));
            }
            
            // 记录路由信息
            $this->registeredRoutes[] = [
                'method' => $method,
                'path' => $path,
                'name' => $routeName,
                'controller' => $controller,
                'middleware' => $middleware
            ];
            
            // 如果是受保护的路由，记录到保护列表
            if (in_array('auth', $middleware)) {
                $this->protectedRoutes[] = [
                    'method' => $method,
                    'path' => $path,
                    'name' => $routeName
                ];
            }
            
            $this->log('debug', 'Route registered', [
                'method' => $method,
                'path' => $path,
                'name' => $routeName,
                'middleware' => $middleware
            ]);
        } catch (Throwable $e) {
            $this->log('error', "Failed to register route {$method} {$path}: " . $e->getMessage());
            throw new \RuntimeException("Failed to register route {$method} {$path}", 0, $e);
        }
    }

    /**
     * 标准化 HTTP 方法
     */
    private function normalizeMethod(string $method): string
    {
        return strtolower($method);
    }

    /**
     * 解析中间件
     */
    private function resolveMiddleware(array $middleware): array
    {
        $resolvedMiddleware = [];
        
        foreach ($middleware as $middlewareName) {
            if (isset($this->middlewareGroups[$middlewareName])) {
                // 如果是中间件组，展开所有中间件
                $resolvedMiddleware = array_merge($resolvedMiddleware, $this->middlewareGroups[$middlewareName]);
            } else {
                // 单个中间件
                $resolvedMiddleware[] = $middlewareName;
            }
        }
        
        return $resolvedMiddleware;
    }

    /**
     * 处理路由请求
     */
    private function handleRoute(WebmanRequest $request, ?string $controller, array $options): WebmanResponse
    {
        try {
            // 如果有控制器，调用控制器
            if ($controller) {
                return $this->callController($controller, $request, $options);
            }
            
            // 默认响应
            return new WebmanResponse(200, ['Content-Type' => 'text/html'], 'OK');
        } catch (Throwable $e) {
            $this->log('error', 'Route handling failed: ' . $e->getMessage(), [
                'controller' => $controller,
                'options' => $options
            ]);
            return $this->handleRouteError($e);
        }
    }

    /**
     * 调用控制器
     */
    private function callController(string $controller, WebmanRequest $request, array $options): WebmanResponse
    {
        try {
            // 解析控制器和方法
            if (strpos($controller, '@') !== false) {
                [$controllerClass, $method] = explode('@', $controller, 2);
            } else {
                $controllerClass = $controller;
                $method = 'handle';
            }
            
            // 创建控制器实例
            if (!class_exists($controllerClass)) {
                throw new \InvalidArgumentException("Controller class not found: {$controllerClass}");
            }
            
            $controllerInstance = new $controllerClass();
            
            // 调用方法
            if (!method_exists($controllerInstance, $method)) {
                throw new \InvalidArgumentException("Method {$method} not found in controller {$controllerClass}");
            }
            
            $result = $controllerInstance->{$method}($request, $options);
            
            // 处理返回值
            return $this->formatResponse($result);
        } catch (Throwable $e) {
            $this->log('error', 'Controller call failed: ' . $e->getMessage(), [
                'controller' => $controller,
                'method' => $method ?? 'unknown'
            ]);
            throw $e;
        }
    }

    /**
     * 格式化响应
     */
    private function formatResponse($result): WebmanResponse
    {
        if ($result instanceof WebmanResponse) {
            return $result;
        }
        
        if (is_array($result)) {
            return new WebmanResponse(200, ['Content-Type' => 'application/json'], json_encode($result));
        }
        
        if (is_string($result)) {
            return new WebmanResponse(200, ['Content-Type' => 'text/html'], $result);
        }
        
        return new WebmanResponse(200, ['Content-Type' => 'text/html'], 'OK');
    }

    /**
     * 处理路由错误
     */
    private function handleRouteError(Throwable $exception): WebmanResponse
    {
        $message = $exception->getMessage();
        $code = $exception->getCode();
        
        // 根据异常类型返回不同的状态码
        $statusCode = 500;
        if ($exception instanceof \InvalidArgumentException) {
            $statusCode = 400;
        } elseif ($exception instanceof \UnauthorizedException) {
            $statusCode = 401;
        } elseif ($exception instanceof \ForbiddenException) {
            $statusCode = 403;
        } elseif ($exception instanceof \NotFoundException) {
            $statusCode = 404;
        }
        
        $errorResponse = [
            'error' => true,
            'message' => $message,
            'code' => $code,
            'status' => $statusCode
        ];
        
        return new WebmanResponse($statusCode, ['Content-Type' => 'application/json'], json_encode($errorResponse));
    }

    /**
     * 添加 fallback 路由
     */
    public function addFallbackRoute(callable $callback, array $options = []): void
    {
        try {
            $fallbackRoute = WebmanRoute::fallback($callback);
            
            // 添加中间件
            if (isset($options['middleware'])) {
                $fallbackRoute->middleware($this->resolveMiddleware($options['middleware']));
            }
            
            $this->fallbackRoutes[] = [
                'callback' => $callback,
                'options' => $options
            ];
            
            $this->log('info', 'Fallback route added', ['options' => $options]);
        } catch (Throwable $e) {
            $this->log('error', 'Failed to add fallback route: ' . $e->getMessage());
            throw new \RuntimeException("Failed to add fallback route", 0, $e);
        }
    }

    /**
     * 注册路由组
     */
    public function group(array $options, callable $callback): void
    {
        try {
            $groupName = $options['name'] ?? uniqid('group_');
            $middleware = $options['middleware'] ?? [];
            
            // 保存当前组信息
            $currentGroup = [
                'name' => $groupName,
                'middleware' => $middleware,
                'options' => $options
            ];
            
            $this->routeGroups[] = $currentGroup;
            
            // 执行组内路由注册
            $callback();
            
            $this->log('debug', 'Route group registered', [
                'name' => $groupName,
                'middleware' => $middleware
            ]);
        } catch (Throwable $e) {
            $this->log('error', 'Failed to register route group: ' . $e->getMessage());
            throw new \RuntimeException("Failed to register route group", 0, $e);
        }
    }

    /**
     * 获取已注册的路由
     */
    public function getRegisteredRoutes(): array
    {
        return $this->registeredRoutes;
    }

    /**
     * 获取受保护的路由
     */
    public function getProtectedRoutes(): array
    {
        return $this->protectedRoutes;
    }

    /**
     * 获取路由统计信息
     */
    public function getRouteStatistics(): array
    {
        return [
            'total_routes' => count($this->registeredRoutes),
            'protected_routes' => count($this->protectedRoutes),
            'fallback_routes' => count($this->fallbackRoutes),
            'route_groups' => count($this->routeGroups),
            'middleware_groups' => array_keys($this->middlewareGroups)
        ];
    }

    /**
     * 验证路由保护
     */
    public function validateRouteProtection(string $path, string $method = 'GET'): bool
    {
        foreach ($this->protectedRoutes as $route) {
            if ($route['method'] === $method && $route['path'] === $path) {
                return true;
            }
        }
        return false;
    }

    /**
     * 日志记录
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}