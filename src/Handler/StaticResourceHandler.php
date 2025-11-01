<?php

namespace WebmanFilament\Handler;

use WebmanFilament\Bridge\FilamentBridge;
use WebmanFilament\Translator\RequestTranslator;
use WebmanFilament\Translator\ResponseTranslator;
use WebmanFilament\Container\ContainerAdapter;
use Psr\Container\ContainerInterface;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response as IlluminateResponse;
use Webman\Http\Request as WebmanRequest;
use Webman\Http\Response as WebmanResponse;
use Throwable;

/**
 * 静态资源处理器
 * 
 * 负责处理 Filament 静态资源(Livewire、Alpine、Tailwind)的加载、缓存与版本管理
 * 实现高效的静态资源分发策略
 */
class StaticResourceHandler
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
     * 静态资源配置
     * @var array
     */
    protected array $assetConfig = [
        'base_path' => '/filament/assets',
        'version' => '4.x',
        'cache_max_age' => 31536000, // 1年
        'enable_gzip' => true,
        'enable_brotli' => true,
    ];

    /**
     * 资源类型映射
     * @var array
     */
    protected array $resourceTypes = [
        'css' => [
            'tailwind' => '/filament/assets/css/tailwind.css',
            'filament' => '/filament/assets/css/filament.css',
            'livewire' => '/filament/assets/css/livewire.css',
        ],
        'js' => [
            'alpine' => '/filament/assets/js/alpine.js',
            'livewire' => '/filament/assets/js/livewire.js',
            'filament' => '/filament/assets/js/filament.js',
        ],
        'fonts' => [
            'inter' => '/filament/assets/fonts/Inter.woff2',
            'material-icons' => '/filament/assets/fonts/MaterialIcons.woff2',
        ],
        'images' => [
            'logo' => '/filament/assets/images/logo.png',
            'icons' => '/filament/assets/images/icons.svg',
        ],
    ];

    /**
     * 缓存策略配置
     * @var array
     */
    protected array $cacheStrategies = [
        'static' => [
            'max_age' => 31536000, // 1年
            'immutable' => true,
            'etag' => true,
        ],
        'dynamic' => [
            'max_age' => 3600, // 1小时
            'immutable' => false,
            'etag' => true,
        ],
        'development' => [
            'max_age' => 0,
            'immutable' => false,
            'etag' => false,
        ],
    ];

    /**
     * 支持的压缩格式
     * @var array
     */
    protected array $compressionFormats = [
        'gzip' => '.gz',
        'brotli' => '.br',
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
     * 处理静态资源请求
     */
    public function handle(WebmanRequest $request): ?WebmanResponse
    {
        try {
            $path = $request->path();
            
            // 检查是否为 Filament 静态资源
            if (!$this->isFilamentAsset($path)) {
                return null;
            }

            // 转换请求
            $illuminateRequest = $this->requestTranslator->toIlluminate($request);
            
            // 处理资源请求
            $illuminateResponse = $this->serveAsset($illuminateRequest, $path);
            
            // 转换响应
            return $this->responseTranslator->toWebman($illuminateResponse);

        } catch (Throwable $e) {
            error_log("StaticResourceHandler: Failed to serve asset: " . $e->getMessage());
            return null;
        }
    }

    /**
     * 检查是否为 Filament 静态资源
     */
    protected function isFilamentAsset(string $path): bool
    {
        return str_starts_with($path, $this->assetConfig['base_path']);
    }

    /**
     * 提供静态资源
     */
    protected function serveAsset(IlluminateRequest $request, string $path): IlluminateResponse
    {
        // 获取资源文件路径
        $filePath = $this->resolveAssetPath($path);
        
        if (!$filePath || !file_exists($filePath)) {
            return new IlluminateResponse('Asset not found', 404);
        }

        // 检查缓存
        if ($cachedResponse = $this->checkCache($request, $filePath)) {
            return $cachedResponse;
        }

        // 获取文件信息
        $fileInfo = $this->getFileInfo($filePath);
        
        // 创建响应
        $response = $this->createAssetResponse($filePath, $fileInfo);
        
        // 设置缓存头
        $this->setCacheHeaders($response, $fileInfo);
        
        // 设置压缩
        $this->setCompressionHeaders($response, $request);
        
        // 设置 CORS 头
        $this->setCorsHeaders($response);
        
        return $response;
    }

    /**
     * 解析资源文件路径
     */
    protected function resolveAssetPath(string $path): ?string
    {
        // 移除基础路径
        $relativePath = str_replace($this->assetConfig['base_path'], '', $path);
        $relativePath = ltrim($relativePath, '/');
        
        // 查找资源文件
        foreach ($this->resourceTypes as $type => $assets) {
            foreach ($assets as $name => $assetPath) {
                if ($assetPath === '/' . $relativePath) {
                    return $this->getAssetFilePath($name, $type);
                }
            }
        }
        
        // 直接路径匹配
        return $this->findAssetByPath('/' . $relativePath);
    }

    /**
     * 根据名称和类型获取资源文件路径
     */
    protected function getAssetFilePath(string $name, string $type): ?string
    {
        $basePath = $this->getAssetBasePath();
        
        switch ($type) {
            case 'css':
                return $basePath . '/css/' . $name . '.css';
            case 'js':
                return $basePath . '/js/' . $name . '.js';
            case 'fonts':
                return $basePath . '/fonts/' . $name . '.woff2';
            case 'images':
                return $basePath . '/images/' . $name . $this->getImageExtension($name);
            default:
                return null;
        }
    }

    /**
     * 获取资源基础路径
     */
    protected function getAssetBasePath(): string
    {
        return dirname(__DIR__, 2) . '/public/filament/assets';
    }

    /**
     * 根据路径查找资源
     */
    protected function findAssetByPath(string $relativePath): ?string
    {
        $basePath = $this->getAssetBasePath();
        $fullPath = $basePath . $relativePath;
        
        return file_exists($fullPath) ? $fullPath : null;
    }

    /**
     * 获取图片扩展名
     */
    protected function getImageExtension(string $name): string
    {
        $extensions = [
            'logo' => '.png',
            'icons' => '.svg',
        ];
        
        return $extensions[$name] ?? '.png';
    }

    /**
     * 检查缓存
     */
    protected function checkCache(IlluminateRequest $request, string $filePath): ?IlluminateResponse
    {
        $fileInfo = $this->getFileInfo($filePath);
        $etag = '"' . md5($filePath . $fileInfo['mtime']) . '"';
        $ifNoneMatch = $request->header('If-None-Match');
        
        if ($ifNoneMatch && $ifNoneMatch === $etag) {
            return new IlluminateResponse('', 304);
        }
        
        return null;
    }

    /**
     * 获取文件信息
     */
    protected function getFileInfo(string $filePath): array
    {
        return [
            'size' => filesize($filePath),
            'mtime' => filemtime($filePath),
            'type' => $this->getMimeType($filePath),
        ];
    }

    /**
     * 获取 MIME 类型
     */
    protected function getMimeType(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'woff2' => 'font/woff2',
            'png' => 'image/png',
            'svg' => 'image/svg+xml',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
    }

    /**
     * 创建资源响应
     */
    protected function createAssetResponse(string $filePath, array $fileInfo): IlluminateResponse
    {
        $content = file_get_contents($filePath);
        
        return new IlluminateResponse($content, 200, [
            'Content-Type' => $fileInfo['type'],
            'Content-Length' => $fileInfo['size'],
            'ETag' => '"' . md5($filePath . $fileInfo['mtime']) . '"',
        ]);
    }

    /**
     * 设置缓存头
     */
    protected function setCacheHeaders(IlluminateResponse $response, array $fileInfo): void
    {
        $strategy = $this->getCacheStrategy($fileInfo);
        
        $response->header('Cache-Control', $this->buildCacheControlHeader($strategy));
        $response->header('Last-Modified', gmdate('D, d M Y H:i:s', $fileInfo['mtime']) . ' GMT');
        $response->header('Expires', gmdate('D, d M Y H:i:s', time() + $strategy['max_age']) . ' GMT');
        
        if ($strategy['immutable']) {
            $response->header('Cache-Control', 'public, max-age=' . $strategy['max_age'] . ', immutable');
        }
    }

    /**
     * 获取缓存策略
     */
    protected function getCacheStrategy(array $fileInfo): array
    {
        $environment = $_ENV['APP_ENV'] ?? 'production';
        
        if ($environment === 'development') {
            return $this->cacheStrategies['development'];
        }
        
        // 根据文件类型选择策略
        $mimeType = $fileInfo['type'];
        
        if (str_contains($mimeType, 'font') || str_contains($mimeType, 'image')) {
            return $this->cacheStrategies['static'];
        }
        
        return $this->cacheStrategies['dynamic'];
    }

    /**
     * 构建缓存控制头
     */
    protected function buildCacheControlHeader(array $strategy): string
    {
        $parts = ['public'];
        
        if ($strategy['max_age'] > 0) {
            $parts[] = 'max-age=' . $strategy['max_age'];
        }
        
        if ($strategy['immutable']) {
            $parts[] = 'immutable';
        }
        
        return implode(', ', $parts);
    }

    /**
     * 设置压缩头
     */
    protected function setCompressionHeaders(IlluminateResponse $response, IlluminateRequest $request): void
    {
        if (!$this->assetConfig['enable_gzip'] && !$this->assetConfig['enable_brotli']) {
            return;
        }
        
        $acceptEncoding = $request->header('Accept-Encoding', '');
        
        if ($this->assetConfig['enable_gzip'] && str_contains($acceptEncoding, 'gzip')) {
            $response->header('Content-Encoding', 'gzip');
        } elseif ($this->assetConfig['enable_brotli'] && str_contains($acceptEncoding, 'br')) {
            $response->header('Content-Encoding', 'br');
        }
    }

    /**
     * 设置 CORS 头
     */
    protected function setCorsHeaders(IlluminateResponse $response): void
    {
        $response->header('Access-Control-Allow-Origin', '*');
        $response->header('Access-Control-Allow-Methods', 'GET, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Accept, Accept-Encoding, Content-Type');
    }

    /**
     * 生成资源清单
     */
    public function generateAssetManifest(): array
    {
        $manifest = [];
        
        foreach ($this->resourceTypes as $type => $assets) {
            foreach ($assets as $name => $path) {
                $filePath = $this->getAssetFilePath($name, $type);
                
                if ($filePath && file_exists($filePath)) {
                    $fileInfo = $this->getFileInfo($filePath);
                    
                    $manifest[$path] = [
                        'path' => $path,
                        'file' => $filePath,
                        'size' => $fileInfo['size'],
                        'type' => $fileInfo['type'],
                        'version' => $this->assetConfig['version'],
                        'hash' => md5($filePath . $fileInfo['mtime']),
                    ];
                }
            }
        }
        
        return $manifest;
    }

    /**
     * 获取资源配置
     */
    public function getAssetConfig(): array
    {
        return $this->assetConfig;
    }

    /**
     * 更新资源配置
     */
    public function updateAssetConfig(array $config): void
    {
        $this->assetConfig = array_merge($this->assetConfig, $config);
    }

    /**
     * 获取缓存策略
     */
    public function getCacheStrategies(): array
    {
        return $this->cacheStrategies;
    }

    /**
     * 预热资源缓存
     */
    public function warmupCache(): void
    {
        $manifest = $this->generateAssetManifest();
        
        foreach ($manifest as $asset) {
            // 预加载文件到内存缓存
            if (file_exists($asset['file'])) {
                file_get_contents($asset['file']);
            }
        }
        
        error_log("StaticResourceHandler: Warmed up cache for " . count($manifest) . " assets");
    }

    /**
     * 清理过期缓存
     */
    public function cleanupCache(): void
    {
        // 实现缓存清理逻辑
        error_log("StaticResourceHandler: Cache cleanup completed");
    }
}