<?php

namespace FilamentWebmanAdapter\Bridge;

use Psr\Log\LoggerInterface;
use Throwable;
use Workerman\Protocols\Http\Request as WebmanRequest;
use Workerman\Protocols\Http\Response as WebmanResponse;

/**
 * 中间件桥接器
 * 
 * 将 Laravel 的中间件栈与 webman 的洋葱模型对齐
 * 支持认证、限流、日志、错误处理等横切逻辑
 */
class MiddlewareBridge
{
    private LoggerInterface $logger;
    private array $globalMiddleware = [];
    private array $routeMiddleware = [];
    private array $middlewareGroups = [];
    private array $middlewareStack = [];
    private array $executionOrder = [];

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->initializeMiddlewareSystem();
        $this->log('info', 'MiddlewareBridge initialized');
    }

    /**
     * 初始化中间件系统
     */
    private function initializeMiddlewareSystem(): void
    {
        // 全局中间件
        $this->globalMiddleware = [
            'FilamentWebmanAdapter\Middleware\SecurityHeadersMiddleware',
            'FilamentWebmanAdapter\Middleware\RequestLoggingMiddleware',
            'FilamentWebmanAdapter\Middleware\ErrorHandlingMiddleware'
        ];

        // 路由中间件
        $this->routeMiddleware = [
            'auth' => 'FilamentWebmanAdapter\Middleware\AuthMiddleware',
            'guest' => 'FilamentWebmanAdapter\Middleware\GuestMiddleware',
            'throttle' => 'FilamentWebmanAdapter\Middleware\ThrottleMiddleware',
            'verified' => 'FilamentWebmanAdapter\Middleware\EmailVerifiedMiddleware',
            'can' => 'FilamentWebmanAdapter\Middleware\AuthorizationMiddleware'
        ];

        // 中间件组
        $this->middlewareGroups = [
            'web' => [
                'FilamentWebmanAdapter\Middleware\SessionMiddleware',
                'FilamentWebmanAdapter\Middleware\CsrfMiddleware',
                'FilamentWebmanAdapter\Middleware\ShareErrorsFromSessionMiddleware'
            ],
            'api' => [
                'FilamentWebmanAdapter\Middleware\ThrottleMiddleware',
                'FilamentWebmanAdapter\Middleware\EncryptCookiesMiddleware'
            ],
            'auth' => [
                'FilamentWebmanAdapter\Middleware\AuthMiddleware'
            ],
            'guest' => [
                'FilamentWebmanAdapter\Middleware\GuestMiddleware'
            ]
        ];

        // 执行顺序定义
        $this->executionOrder = [
            'global_before' => 0,
            'group_middleware' => 10,
            'route_middleware' => 20,
            'controller_before' => 30,
            'controller_execution' => 40,
            'controller_after' => 50,
            'response_middleware' => 60,
            'global_after' => 70
        ];
    }

    /**
     * 添加全局中间件
     */
    public function addGlobalMiddleware(string $middleware, int $priority = 0): void
    {
        try {
            $this->globalMiddleware[] = [
                'middleware' => $middleware,
                'priority' => $priority
            ];
            
            // 按优先级排序
            usort($this->globalMiddleware, function($a, $b) {
                return $a['priority'] <=> $b['priority'];
            });
            
            $this->log('debug', 'Global middleware added', ['middleware' => $middleware, 'priority' => $priority]);
        } catch (Throwable $e) {
            $this->log('error', 'Failed to add global middleware: ' . $e->getMessage());
            throw new \RuntimeException("Failed to add global middleware", 0, $e);
        }
    }

    /**
     * 添加路由中间件
     */
    public function addRouteMiddleware(string $name, string $middleware): void
    {
        try {
            $this->routeMiddleware[$name] = $middleware;
            $this->log('debug', 'Route middleware added', ['name' => $name, 'middleware' => $middleware]);
        } catch (Throwable $e) {
            $this->log('error', 'Failed to add route middleware: ' . $e->getMessage());
            throw new \RuntimeException("Failed to add route middleware", 0, $e);
        }
    }

    /**
     * 添加中间件组
     */
    public function addMiddlewareGroup(string $name, array $middleware): void
    {
        try {
            $this->middlewareGroups[$name] = $middleware;
            $this->log('debug', 'Middleware group added', ['name' => $name, 'middleware' => $middleware]);
        } catch (Throwable $e) {
            $this->log('error', 'Failed to add middleware group: ' . $e->getMessage());
            throw new \RuntimeException("Failed to add middleware group", 0, $e);
        }
    }

    /**
     * 处理请求中间件栈
     */
    public function handleRequest(WebmanRequest $request, callable $next): WebmanResponse
    {
        try {
            $this->log('debug', 'Starting middleware pipeline', ['uri' => $request->uri()]);
            
            // 构建洋葱模型的中间件栈
            $middlewareStack = $this->buildMiddlewareStack($request);
            
            // 执行中间件栈
            $response = $this->executeMiddlewareStack($middlewareStack, $request, $next);
            
            $this->log('debug', 'Middleware pipeline completed', ['status' => $response->getStatusCode()]);
            
            return $response;
        } catch (Throwable $e) {
            $this->log('error', 'Middleware pipeline failed: ' . $e->getMessage());
            return $this->handleMiddlewareError($e, $request);
        }
    }

    /**
     * 构建中间件栈
     */
    private function buildMiddlewareStack(WebmanRequest $request): array
    {
        $stack = [];
        
        // 1. 全局前置中间件
        foreach ($this->globalMiddleware as $globalMiddleware) {
            if (is_array($globalMiddleware)) {
                $middleware = $globalMiddleware['middleware'];
            } else {
                $middleware = $globalMiddleware;
            }
            $stack[] = [
                'type' => 'global_before',
                'middleware' => $middleware,
                'priority' => $globalMiddleware['priority'] ?? 0
            ];
        }
        
        // 2. 路由中间件组
        $routeMiddlewareGroups = $this->getRouteMiddlewareGroups($request);
        foreach ($routeMiddlewareGroups as $group) {
            foreach ($group as $middleware) {
                $stack[] = [
                    'type' => 'group_middleware',
                    'middleware' => $middleware
                ];
            }
        }
        
        // 3. 特定路由中间件
        $routeSpecificMiddleware = $this->getRouteSpecificMiddleware($request);
        foreach ($routeSpecificMiddleware as $middleware) {
            $stack[] = [
                'type' => 'route_middleware',
                'middleware' => $middleware
            ];
        }
        
        // 按优先级排序
        usort($stack, function($a, $b) {
            $priorityA = $a['priority'] ?? $this->executionOrder[$a['type']] ?? 0;
            $priorityB = $b['priority'] ?? $this->executionOrder[$b['type']] ?? 0;
            return $priorityA <=> $priorityB;
        });
        
        return $stack;
    }

    /**
     * 获取路由中间件组
     */
    private function getRouteMiddlewareGroups(WebmanRequest $request): array
    {
        $groups = [];
        $uri = $request->uri();
        $method = $request->method();
        
        // 根据路径和方法确定中间件组
        if (strpos($uri, '/admin') === 0) {
            // 管理面板路由
            $groups[] = $this->middlewareGroups['web'] ?? [];
            $groups[] = $this->middlewareGroups['auth'] ?? [];
        } elseif (strpos($uri, '/api') === 0) {
            // API 路由
            $groups[] = $this->middlewareGroups['api'] ?? [];
        }
        
        return array_filter($groups);
    }

    /**
     * 获取特定路由中间件
     */
    private function getRouteSpecificMiddleware(WebmanRequest $request): array
    {
        $middleware = [];
        $uri = $request->uri();
        
        // 根据路径确定特定中间件
        if (strpos($uri, '/admin/login') !== false) {
            $middleware[] = $this->routeMiddleware['guest'] ?? null;
        } elseif (strpos($uri, '/admin') === 0 && strpos($uri, '/admin/login') === false) {
            $middleware[] = $this->routeMiddleware['auth'] ?? null;
        }
        
        return array_filter($middleware);
    }

    /**
     * 执行中间件栈
     */
    private function executeMiddlewareStack(array $middlewareStack, WebmanRequest $request, callable $next): WebmanResponse
    {
        $index = 0;
        $maxIndex = count($middlewareStack);
        
        // 创建洋葱模型的下一个调用函数
        $dispatcher = function($request) use (&$index, $maxIndex, $middlewareStack, $next) {
            if ($index >= $maxIndex) {
                // 执行控制器或最终处理函数
                return $next($request);
            }
            
            $middlewareInfo = $middlewareStack[$index];
            $index++;
            
            try {
                $middleware = $this->instantiateMiddleware($middlewareInfo['middleware']);
                
                // 执行中间件
                if (method_exists($middleware, 'handle')) {
                    return $middleware->handle($request, $dispatcher);
                } elseif (is_callable($middleware)) {
                    return $middleware($request, $dispatcher);
                } else {
                    throw new \InvalidArgumentException("Invalid middleware: {$middlewareInfo['middleware']}");
                }
            } catch (Throwable $e) {
                $this->log('error', 'Middleware execution failed: ' . $e->getMessage(), [
                    'middleware' => $middlewareInfo['middleware'],
                    'type' => $middlewareInfo['type']
                ]);
                throw $e;
            }
        };
        
        return $dispatcher($request);
    }

    /**
     * 实例化中间件
     */
    private function instantiateMiddleware(string $middlewareClass)
    {
        if (!class_exists($middlewareClass)) {
            throw new \InvalidArgumentException("Middleware class not found: {$middlewareClass}");
        }
        
        return new $middlewareClass();
    }

    /**
     * 处理中间件错误
     */
    private function handleMiddlewareError(Throwable $exception, WebmanRequest $request): WebmanResponse
    {
        $message = $exception->getMessage();
        $code = $exception->getCode();
        
        // 根据异常类型确定状态码
        $statusCode = 500;
        if ($exception instanceof \UnauthorizedException) {
            $statusCode = 401;
        } elseif ($exception instanceof \ForbiddenException) {
            $statusCode = 403;
        } elseif ($exception instanceof \NotFoundException) {
            $statusCode = 404;
        } elseif ($exception instanceof \InvalidArgumentException) {
            $statusCode = 400;
        }
        
        $errorResponse = [
            'error' => true,
            'message' => $message,
            'code' => $code,
            'status' => $statusCode,
            'timestamp' => time()
        ];
        
        $this->log('error', 'Middleware error handled', [
            'exception' => get_class($exception),
            'message' => $message,
            'status' => $statusCode
        ]);
        
        return new WebmanResponse($statusCode, [
            'Content-Type' => 'application/json'
        ], json_encode($errorResponse));
    }

    /**
     * 创建中间件组
     */
    public function middlewareGroup(string $name, array $middleware): self
    {
        $this->middlewareGroups[$name] = $middleware;
        return $this;
    }

    /**
     * 创建路由中间件别名
     */
    public function aliasMiddleware(string $name, string $middleware): self
    {
        $this->routeMiddleware[$name] = $middleware;
        return $this;
    }

    /**
     * 预检中间件
     */
    public function prependMiddleware(string $middleware): self
    {
        array_unshift($this->globalMiddleware, [
            'middleware' => $middleware,
            'priority' => -1
        ]);
        return $this;
    }

    /**
     * 追加中间件
     */
    public function pushMiddleware(string $middleware): self
    {
        $this->globalMiddleware[] = [
            'middleware' => $middleware,
            'priority' => 100
        ];
        return $this;
    }

    /**
     * 跳过中间件
     */
    public function skipMiddleware(string $middleware, callable $callback): self
    {
        // 实现中间件跳过逻辑
        return $this;
    }

    /**
     * 获取中间件统计信息
     */
    public function getMiddlewareStatistics(): array
    {
        return [
            'global_middleware_count' => count($this->globalMiddleware),
            'route_middleware_count' => count($this->routeMiddleware),
            'middleware_groups_count' => count($this->middlewareGroups),
            'middleware_groups' => array_keys($this->middlewareGroups),
            'route_middleware_aliases' => array_keys($this->routeMiddleware),
            'execution_order' => $this->executionOrder
        ];
    }

    /**
     * 验证中间件配置
     */
    public function validateMiddlewareConfiguration(): array
    {
        $errors = [];
        $warnings = [];
        
        // 检查全局中间件
        foreach ($this->globalMiddleware as $index => $middleware) {
            $middlewareClass = is_array($middleware) ? $middleware['middleware'] : $middleware;
            if (!class_exists($middlewareClass)) {
                $errors[] = "Global middleware class not found: {$middlewareClass}";
            }
        }
        
        // 检查路由中间件
        foreach ($this->routeMiddleware as $name => $middlewareClass) {
            if (!class_exists($middlewareClass)) {
                $errors[] = "Route middleware class not found: {$middlewareClass} (alias: {$name})";
            }
        }
        
        // 检查中间件组
        foreach ($this->middlewareGroups as $groupName => $middlewareList) {
            foreach ($middlewareList as $middlewareClass) {
                if (!class_exists($middlewareClass)) {
                    $errors[] = "Middleware group '{$groupName}' contains invalid class: {$middlewareClass}";
                }
            }
        }
        
        // 检查循环依赖
        $dependencies = $this->checkMiddlewareDependencies();
        if (!empty($dependencies)) {
            $warnings[] = "Potential middleware dependencies detected: " . implode(', ', $dependencies);
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'warnings' => $warnings
        ];
    }

    /**
     * 检查中间件依赖
     */
    private function checkMiddlewareDependencies(): array
    {
        // 简化的依赖检查实现
        return [];
    }

    /**
     * 清除中间件栈
     */
    public function clearMiddlewareStack(): void
    {
        $this->middlewareStack = [];
        $this->log('info', 'Middleware stack cleared');
    }

    /**
     * 重置中间件配置
     */
    public function resetMiddlewareConfiguration(): void
    {
        $this->initializeMiddlewareSystem();
        $this->log('info', 'Middleware configuration reset');
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