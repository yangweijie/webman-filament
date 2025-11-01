<?php

namespace WebmanFilament\Middleware;

use Closure;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response as IlluminateResponse;
use Webman\Http\Request as WebmanRequest;
use Webman\Http\Response as WebmanResponse;
use WebmanFilament\Bridge\FilamentBridge;
use WebmanFilament\Translator\RequestTranslator;
use WebmanFilament\Translator\ResponseTranslator;
use WebmanFilament\Container\ContainerAdapter;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * Filament 中间件
 * 
 * 负责桥接 Laravel 中间件栈与 webman 洋葱模型
 * 处理请求/响应转换、认证授权、错误处理
 */
class FilamentMiddleware
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
     * Laravel 中间件栈映射
     * @var array
     */
    protected array $laravelMiddlewareStack = [
        'web' => ['web', 'session', 'csrf'],
        'auth' => ['auth'],
        'guest' => ['guest'],
        'throttle' => ['throttle:60,1'],
    ];

    /**
     * 中间件执行顺序
     * @var array
     */
    protected array $middlewareOrder = [
        'web',      // 全局基础中间件
        'auth',     // 认证检查
        'guest',    // 游客检查
        'throttle', // 限流
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
    }

    /**
     * 处理请求
     */
    public function handle(WebmanRequest $request, Closure $next): WebmanResponse
    {
        try {
            // 1. 转换 webman 请求为 Laravel 请求
            $illuminateRequest = $this->requestTranslator->toIlluminate($request);

            // 2. 执行 Laravel 中间件栈
            $this->executeLaravelMiddlewareStack($illuminateRequest);

            // 3. 路由匹配与处理
            $illuminateResponse = $this->handleFilamentRoute($illuminateRequest);

            // 4. 转换 Laravel 响应为 webman 响应
            return $this->responseTranslator->toWebman($illuminateResponse);

        } catch (Throwable $e) {
            return $this->handleException($request, $e);
        }
    }

    /**
     * 执行 Laravel 中间件栈
     */
    protected function executeLaravelMiddlewareStack(IlluminateRequest $request): void
    {
        foreach ($this->middlewareOrder as $middleware) {
            $this->executeMiddleware($middleware, $request);
        }
    }

    /**
     * 执行单个中间件
     */
    protected function executeMiddleware(string $middleware, IlluminateRequest $request): void
    {
        $middlewareMap = $this->laravelMiddlewareStack[$middleware] ?? [$middleware];
        
        foreach ($middlewareMap as $middlewareClass) {
            $this->runMiddleware($middlewareClass, $request);
        }
    }

    /**
     * 运行中间件
     */
    protected function runMiddleware(string $middlewareClass, IlluminateRequest $request): void
    {
        try {
            // 获取中间件实例
            $middleware = $this->containerAdapter->get($middlewareClass);
            
            // 执行中间件逻辑
            if (method_exists($middleware, 'handle')) {
                $middleware->handle($request, function ($request) {
                    return $request;
                });
            }
        } catch (Throwable $e) {
            // 中间件执行失败，记录日志但不中断请求
            error_log("FilamentMiddleware: Middleware {$middlewareClass} failed: " . $e->getMessage());
        }
    }

    /**
     * 处理 Filament 路由
     */
    protected function handleFilamentRoute(IlluminateRequest $request): IlluminateResponse
    {
        // 路由匹配
        $route = $this->bridge->matchRoute($request);
        
        if (!$route) {
            return new IlluminateResponse('Not Found', 404);
        }

        // 执行路由处理
        return $this->bridge->handleRoute($route, $request);
    }

    /**
     * 处理异常
     */
    protected function handleException(WebmanRequest $request, Throwable $e): WebmanResponse
    {
        // 记录异常日志
        error_log("FilamentMiddleware Exception: " . $e->getMessage());
        
        // 根据异常类型返回相应响应
        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return $this->responseTranslator->toWebman(
                new IlluminateResponse('Unauthorized', 401)
            );
        }

        if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
            return $this->responseTranslator->toWebman(
                new IlluminateResponse('Forbidden', 403)
            );
        }

        // 默认服务器错误
        return $this->responseTranslator->toWebman(
            new IlluminateResponse('Internal Server Error', 500)
        );
    }

    /**
     * 获取中间件配置
     */
    public function getMiddlewareConfig(): array
    {
        return [
            'stack' => $this->laravelMiddlewareStack,
            'order' => $this->middlewareOrder,
            'enabled' => true,
        ];
    }

    /**
     * 更新中间件配置
     */
    public function updateMiddlewareConfig(array $config): void
    {
        if (isset($config['stack'])) {
            $this->laravelMiddlewareStack = array_merge($this->laravelMiddlewareStack, $config['stack']);
        }
        
        if (isset($config['order'])) {
            $this->middlewareOrder = $config['order'];
        }
    }
}