<?php

namespace FilamentWebmanAdapter\Adapter;

use Illuminate\Container\Container;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Webman\Container\Container as WebmanContainer;

/**
 * 服务容器适配器
 * 
 * 桥接 Laravel Container 与可选 php-di，提供统一容器接口
 * 支持接口绑定、生命周期管理、依赖注入
 */
class ServiceContainerAdapter implements ContainerInterface
{
    private ContainerInterface $laravelContainer;
    private ContainerInterface $webmanContainer;
    private LoggerInterface $logger;
    private array $bindings = [];
    private array $singletons = [];
    private array $interfaces = [];

    public function __construct(
        ?ContainerInterface $laravelContainer = null,
        ?ContainerInterface $webmanContainer = null,
        LoggerInterface $logger = null
    ) {
        $this->laravelContainer = $laravelContainer ?? new Container();
        $this->webmanContainer = $webmanContainer ?? new WebmanContainer();
        $this->logger = $logger;
        
        $this->initializeDefaultBindings();
        $this->log('info', 'ServiceContainerAdapter initialized');
    }

    /**
     * 初始化默认绑定
     */
    private function initializeDefaultBindings(): void
    {
        // 默认绑定接口与实现
        $this->bind('AuthManagerInterface', \Illuminate\Auth\AuthManager::class);
        $this->bind('PolicyRegistryInterface', PolicyRegistry::class);
        $this->bind('TranslatorInterface', RequestResponseTranslator::class);
        $this->bind('ConnectionPoolInterface', ConnectionPool::class);
    }

    /**
     * 绑定接口到实现
     */
    public function bind(string $interface, string $implementation, array $parameters = []): void
    {
        try {
            $this->interfaces[$interface] = $implementation;
            $this->bindings[$interface] = [
                'implementation' => $implementation,
                'parameters' => $parameters,
                'singleton' => false
            ];
            
            // 同时绑定到 Laravel Container
            if ($this->laravelContainer instanceof Container) {
                $this->laravelContainer->bind($interface, function() use ($implementation, $parameters) {
                    return $this->resolve($implementation, $parameters);
                });
            }
            
            // 同时绑定到 webman Container
            if ($this->webmanContainer instanceof WebmanContainer) {
                $this->webmanContainer->bind($interface, $implementation);
            }
            
            $this->log('debug', "Interface bound: {$interface} -> {$implementation}");
        } catch (\Exception $e) {
            $this->log('error', "Failed to bind interface {$interface}: " . $e->getMessage());
            throw new \RuntimeException("Failed to bind interface {$interface}", 0, $e);
        }
    }

    /**
     * 绑定单例
     */
    public function singleton(string $interface, string $implementation, array $parameters = []): void
    {
        try {
            $this->interfaces[$interface] = $implementation;
            $this->singletons[$interface] = [
                'implementation' => $implementation,
                'parameters' => $parameters,
                'instance' => null
            ];
            
            // 同时绑定到 Laravel Container
            if ($this->laravelContainer instanceof Container) {
                $this->laravelContainer->singleton($interface, function() use ($implementation, $parameters) {
                    return $this->resolve($implementation, $parameters);
                });
            }
            
            // 同时绑定到 webman Container
            if ($this->webmanContainer instanceof WebmanContainer) {
                $this->webmanContainer->singleton($interface, $implementation);
            }
            
            $this->log('debug', "Singleton bound: {$interface} -> {$implementation}");
        } catch (\Exception $e) {
            $this->log('error', "Failed to bind singleton {$interface}: " . $e->getMessage());
            throw new \RuntimeException("Failed to bind singleton {$interface}", 0, $e);
        }
    }

    /**
     * 获取服务实例
     */
    public function get(string $id)
    {
        try {
            // 首先检查单例
            if (isset($this->singletons[$id])) {
                $singleton = $this->singletons[$id];
                if ($singleton['instance'] === null) {
                    $singleton['instance'] = $this->resolve($id);
                    $this->singletons[$id] = $singleton;
                }
                return $singleton['instance'];
            }
            
            // 尝试从 Laravel Container 获取
            if ($this->laravelContainer instanceof Container && $this->laravelContainer->bound($id)) {
                return $this->laravelContainer->make($id);
            }
            
            // 尝试从 webman Container 获取
            if ($this->webmanContainer instanceof WebmanContainer && $this->webmanContainer->has($id)) {
                return $this->webmanContainer->get($id);
            }
            
            // 如果有接口绑定，解析实现
            if (isset($this->interfaces[$id])) {
                return $this->resolve($this->interfaces[$id]);
            }
            
            throw new \RuntimeException("Service not found: {$id}");
        } catch (\Exception $e) {
            $this->log('error', "Failed to get service {$id}: " . $e->getMessage());
            throw new \RuntimeException("Failed to get service {$id}", 0, $e);
        }
    }

    /**
     * 检查服务是否存在
     */
    public function has(string $id): bool
    {
        try {
            return $this->laravelContainer->has($id) || 
                   $this->webmanContainer->has($id) || 
                   isset($this->interfaces[$id]) ||
                   isset($this->singletons[$id]);
        } catch (\Exception $e) {
            $this->log('error', "Failed to check service existence {$id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 解析类实例
     */
    private function resolve(string $class, array $parameters = [])
    {
        try {
            // 如果有预定义绑定，使用绑定配置
            if (isset($this->bindings[$class])) {
                $binding = $this->bindings[$class];
                $parameters = array_merge($binding['parameters'], $parameters);
                return new $binding['implementation'](...$parameters);
            }
            
            if (isset($this->singletons[$class])) {
                $singleton = $this->singletons[$class];
                $parameters = array_merge($singleton['parameters'], $parameters);
                return new $singleton['implementation'](...$parameters);
            }
            
            // 检查是否有接口绑定
            if (isset($this->interfaces[$class])) {
                $implementation = $this->interfaces[$class];
                return new $implementation(...$parameters);
            }
            
            // 直接实例化
            return new $class(...$parameters);
        } catch (\Exception $e) {
            $this->log('error', "Failed to resolve class {$class}: " . $e->getMessage());
            throw new \RuntimeException("Failed to resolve class {$class}", 0, $e);
        }
    }

    /**
     * 获取所有绑定
     */
    public function getBindings(): array
    {
        return $this->bindings;
    }

    /**
     * 获取所有单例
     */
    public function getSingletons(): array
    {
        return $this->singletons;
    }

    /**
     * 清理所有实例（用于重载）
     */
    public function clearInstances(): void
    {
        foreach ($this->singletons as &$singleton) {
            $singleton['instance'] = null;
        }
        $this->log('info', 'All singleton instances cleared');
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

/**
 * 策略注册表接口
 */
interface PolicyRegistryInterface
{
    public function register(string $model, string $policy): void;
    public function getPolicy(string $model): ?string;
    public function hasPolicy(string $model): bool;
}

/**
 * 请求响应转换器接口
 */
interface TranslatorInterface
{
    public function toLaravelRequest($webmanRequest): \Illuminate\Http\Request;
    public function fromLaravelResponse($laravelResponse): array;
    public function handleException(\Throwable $exception): array;
}

/**
 * 连接池接口
 */
interface ConnectionPoolInterface
{
    public function getConnection(string $name = 'default');
    public function releaseConnection(string $name): void;
    public function getStatistics(): array;
}

/**
 * 简单策略注册表实现
 */
class PolicyRegistry implements PolicyRegistryInterface
{
    private array $policies = [];

    public function register(string $model, string $policy): void
    {
        $this->policies[$model] = $policy;
    }

    public function getPolicy(string $model): ?string
    {
        return $this->policies[$model] ?? null;
    }

    public function hasPolicy(string $model): bool
    {
        return isset($this->policies[$model]);
    }
}

/**
 * 简单请求响应转换器实现
 */
class RequestResponseTranslator implements TranslatorInterface
{
    public function toLaravelRequest($webmanRequest): \Illuminate\Http\Request
    {
        // 简化的转换实现
        return \Illuminate\Http\Request::create(
            $webmanRequest->uri(),
            $webmanRequest->method(),
            $webmanRequest->all(),
            [],
            [],
            [],
            json_encode($webmanRequest->all())
        );
    }

    public function fromLaravelResponse($laravelResponse): array
    {
        // 简化的转换实现
        return [
            'status' => $laravelResponse->getStatusCode(),
            'headers' => $laravelResponse->headers->all(),
            'content' => $laravelResponse->getContent()
        ];
    }

    public function handleException(\Throwable $exception): array
    {
        return [
            'error' => true,
            'message' => $exception->getMessage(),
            'code' => $exception->getCode()
        ];
    }
}

/**
 * 简单连接池实现
 */
class ConnectionPool implements ConnectionPoolInterface
{
    private array $connections = [];
    private array $statistics = [];

    public function getConnection(string $name = 'default')
    {
        if (!isset($this->connections[$name])) {
            $this->connections[$name] = new \PDO("sqlite::memory:");
            $this->statistics[$name] = 0;
        }
        $this->statistics[$name]++;
        return $this->connections[$name];
    }

    public function releaseConnection(string $name): void
    {
        // 在常驻内存模式下，连接保持打开状态
    }

    public function getStatistics(): array
    {
        return $this->statistics;
    }
}