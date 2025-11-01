<?php

declare(strict_types=1);

namespace WebmanFilament\Adapter;

use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Workerman\Protocols\Http\Request as WorkermanRequest;
use Workerman\Protocols\Http\Response as WorkermanResponse;

/**
 * 请求响应适配器
 * 
 * 负责在 webman 的 Request/Response 与 Laravel 的 Illuminate Request/Response 之间进行双向转换
 * 
 * 功能包括：
 * - 请求构建与转换
 * - 响应转换与输出
 * - 错误与异常映射
 * - 头信息和状态码处理
 * - Cookie 和 Session 处理
 */
class RequestResponseAdapter
{
    /**
     * 请求转换配置
     */
    protected array $requestConfig = [
        'timeout' => 30,
        'max_redirects' => 5,
        'preserve_fragments' => false,
    ];

    /**
     * 响应转换配置
     */
    protected array $responseConfig = [
        'default_content_type' => 'text/html',
        'charset' => 'UTF-8',
        'compress_response' => false,
    ];

    /**
     * 构造函数
     */
    public function __construct(array $config = [])
    {
        $this->requestConfig = array_merge($this->requestConfig, $config['request'] ?? []);
        $this->responseConfig = array_merge($this->responseConfig, $config['response'] ?? []);
    }

    /**
     * 将 webman Request 转换为 Illuminate Request
     */
    public function convertWebmanRequestToIlluminate(WorkermanRequest $webmanRequest): IlluminateRequest
    {
        try {
            Log::debug('[RequestResponseAdapter] 开始转换 webman Request');

            // 构建 Symfony Request
            $symfonyRequest = $this->buildSymfonyRequest($webmanRequest);

            // 创建 Illuminate Request
            $illuminateRequest = IlluminateRequest::createFromBase($symfonyRequest);

            // 设置额外属性
            $this->setAdditionalRequestAttributes($illuminateRequest, $webmanRequest);

            Log::debug('[RequestResponseAdapter] webman Request 转换完成', [
                'method' => $illuminateRequest->method(),
                'uri' => $illuminateRequest->getUri(),
                'user_agent' => $illuminateRequest->userAgent(),
            ]);

            return $illuminateRequest;

        } catch (\Exception $e) {
            Log::error('[RequestResponseAdapter] webman Request 转换失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 将 Illuminate Response 转换为 webman Response
     */
    public function convertIlluminateResponseToWebman(IlluminateResponse $illuminateResponse): WorkermanResponse
    {
        try {
            Log::debug('[RequestResponseAdapter] 开始转换 Illuminate Response');

            // 获取响应内容
            $content = $illuminateResponse->getContent();

            // 获取状态码
            $statusCode = $illuminateResponse->getStatusCode();

            // 获取响应头
            $headers = $this->extractResponseHeaders($illuminateResponse);

            // 创建 webman Response
            $webmanResponse = new WorkermanResponse($statusCode, $headers, $content);

            // 设置额外属性
            $this->setAdditionalResponseAttributes($webmanResponse, $illuminateResponse);

            Log::debug('[RequestResponseAdapter] Illuminate Response 转换完成', [
                'status_code' => $statusCode,
                'content_length' => strlen($content),
                'headers_count' => count($headers),
            ]);

            return $webmanResponse;

        } catch (\Exception $e) {
            Log::error('[RequestResponseAdapter] Illuminate Response 转换失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * 处理 JSON 请求响应
     */
    public function handleJsonRequestResponse(WorkermanRequest $webmanRequest, callable $handler): WorkermanResponse
    {
        try {
            // 设置 JSON 请求头
            $this->setJsonRequestHeaders($webmanRequest);

            // 转换请求
            $illuminateRequest = $this->convertWebmanRequestToIlluminate($webmanRequest);

            // 设置 JSON 响应期望
            $illuminateRequest->headers->set('Accept', 'application/json');

            // 执行处理逻辑
            $result = $handler($illuminateRequest);

            // 如果结果是 Illuminate Response，直接转换
            if ($result instanceof IlluminateResponse) {
                return $this->convertIlluminateResponseToWebman($result);
            }

            // 如果结果是数组，创建 JSON 响应
            if (is_array($result)) {
                $jsonResponse = new JsonResponse($result);
                return $this->convertIlluminateResponseToWebman($jsonResponse);
            }

            // 其他类型响应
            $response = new IlluminateResponse($result);
            return $this->convertIlluminateResponseToWebman($response);

        } catch (\Exception $e) {
            Log::error('[RequestResponseAdapter] JSON 请求处理失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // 返回 JSON 错误响应
            return $this->createJsonErrorResponse($e);
        }
    }

    /**
     * 处理文件下载响应
     */
    public function handleFileDownloadResponse(IlluminateResponse $illuminateResponse): WorkermanResponse
    {
        try {
            Log::debug('[RequestResponseAdapter] 处理文件下载响应');

            // 转换响应
            $webmanResponse = $this->convertIlluminateResponseToWebman($illuminateResponse);

            // 设置下载相关头
            $this->setDownloadHeaders($webmanResponse, $illuminateResponse);

            return $webmanResponse;

        } catch (\Exception $e) {
            Log::error('[RequestResponseAdapter] 文件下载响应处理失败', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 处理流式响应
     */
    public function handleStreamingResponse(callable $streamingCallback): WorkermanResponse
    {
        try {
            Log::debug('[RequestResponseAdapter] 处理流式响应');

            // 创建 webman Response
            $webmanResponse = new WorkermanResponse(200, [], '');

            // 设置流式响应头
            $this->setStreamingHeaders($webmanResponse);

            // 执行流式回调
            $streamingCallback($webmanResponse);

            return $webmanResponse;

        } catch (\Exception $e) {
            Log::error('[RequestResponseAdapter] 流式响应处理失败', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 映射请求字段
     */
    public function mapRequestFields(array $webmanFields): array
    {
        $mappedFields = [];

        // 基础字段映射
        $fieldMapping = [
            'method' => 'method',
            'uri' => 'uri',
            'path' => 'path',
            'query_string' => 'query_string',
            'headers' => 'headers',
            'cookies' => 'cookies',
            'post' => 'post',
            'get' => 'get',
            'files' => 'files',
            'server' => 'server',
            'remote_addr' => 'remote_addr',
            'remote_port' => 'remote_port',
            'server_addr' => 'server_addr',
            'server_port' => 'server_port',
            'scheme' => 'scheme',
            'protocol' => 'protocol',
            'user_agent' => 'user_agent',
            'referer' => 'referer',
        ];

        foreach ($fieldMapping as $webmanField => $mappedField) {
            if (isset($webmanFields[$webmanField])) {
                $mappedFields[$mappedField] = $webmanFields[$webmanField];
            }
        }

        return $mappedFields;
    }

    /**
     * 映射响应字段
     */
    public function mapResponseFields(IlluminateResponse $illuminateResponse): array
    {
        $mappedFields = [
            'status_code' => $illuminateResponse->getStatusCode(),
            'content' => $illuminateResponse->getContent(),
            'headers' => $this->extractResponseHeaders($illuminateResponse),
            'charset' => $illuminateResponse->charset ?? $this->responseConfig['charset'],
            'version' => $illuminateResponse->getProtocolVersion(),
        ];

        return $mappedFields;
    }

    /**
     * 构建 Symfony Request
     */
    protected function buildSymfonyRequest(WorkermanRequest $webmanRequest): SymfonyRequest
    {
        // 获取请求方法
        $method = $webmanRequest->method();

        // 获取请求 URI
        $uri = $webmanRequest->uri();

        // 获取查询字符串
        $queryString = $webmanRequest->queryString();

        // 构建完整 URI
        $fullUri = $queryString ? $uri . '?' . $queryString : $uri;

        // 获取请求头
        $headers = $this->extractRequestHeaders($webmanRequest);

        // 获取服务器变量
        $server = $this->buildServerVariables($webmanRequest);

        // 获取 Cookie
        $cookies = $webmanRequest->cookie();

        // 获取 POST 数据
        $postData = $webmanRequest->post();

        // 获取文件上传
        $files = $webmanRequest->file();

        // 构建内容
        $content = $this->buildRequestContent($webmanRequest);

        // 创建 Symfony Request
        return new SymfonyRequest(
            $get = $webmanRequest->get(),
            $post = $postData,
            $attributes = [],
            $cookies = $cookies,
            $files = $files,
            $server = $server,
            $content = $content
        );
    }

    /**
     * 提取请求头
     */
    protected function extractRequestHeaders(WorkermanRequest $webmanRequest): array
    {
        $headers = [];
        $headerMap = [
            'Content-Type' => 'content_type',
            'Content-Length' => 'content_length',
            'Host' => 'host',
            'User-Agent' => 'user_agent',
            'Accept' => 'accept',
            'Accept-Language' => 'accept_language',
            'Accept-Encoding' => 'accept_encoding',
            'Connection' => 'connection',
            'Cookie' => 'cookie',
            'Referer' => 'referer',
            'X-Requested-With' => 'x_requested_with',
            'X-CSRF-TOKEN' => 'x_csrf_token',
            'Authorization' => 'authorization',
        ];

        foreach ($headerMap as $headerName => $webmanKey) {
            $value = $webmanRequest->header($webmanKey);
            if ($value !== null) {
                $headers[$headerName] = $value;
            }
        }

        return $headers;
    }

    /**
     * 提取响应头
     */
    protected function extractResponseHeaders(IlluminateResponse $illuminateResponse): array
    {
        $headers = [];

        foreach ($illuminateResponse->headers->all() as $name => $values) {
            foreach ($values as $value) {
                $headers[] = $name . ': ' . $value;
            }
        }

        return $headers;
    }

    /**
     * 构建服务器变量
     */
    protected function buildServerVariables(WorkermanRequest $webmanRequest): array
    {
        $server = [];

        // 基础服务器变量
        $server['REQUEST_METHOD'] = $webmanRequest->method();
        $server['REQUEST_URI'] = $webmanRequest->uri();
        $server['QUERY_STRING'] = $webmanRequest->queryString();
        $server['SERVER_PROTOCOL'] = $webmanRequest->protocol();
        $server['SERVER_NAME'] = $webmanRequest->host();
        $server['SERVER_PORT'] = (string) $webmanRequest->port();
        $server['REMOTE_ADDR'] = $webmanRequest->remoteAddress();
        $server['REMOTE_PORT'] = (string) $webmanRequest->remotePort();
        $server['REQUEST_TIME'] = time();
        $server['REQUEST_TIME_FLOAT'] = microtime(true);

        // 添加请求头到服务器变量
        foreach ($this->extractRequestHeaders($webmanRequest) as $name => $value) {
            $serverName = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
            $server[$serverName] = $value;
        }

        return $server;
    }

    /**
     * 构建请求内容
     */
    protected function buildRequestContent(WorkermanRequest $webmanRequest): string
    {
        $method = $webmanRequest->method();
        
        // 对于 GET/HEAD 请求，内容为空
        if (in_array($method, ['GET', 'HEAD'])) {
            return '';
        }

        // 对于 POST/PUT/PATCH 请求，返回原始内容
        $postData = $webmanRequest->rawBody();
        
        return $postData ?: '';
    }

    /**
     * 设置额外请求属性
     */
    protected function setAdditionalRequestAttributes(IlluminateRequest $illuminateRequest, WorkermanRequest $webmanRequest): void
    {
        // 设置原始请求对象
        $illuminateRequest->setRequestFormat('html');
        
        // 设置客户端 IP
        $clientIp = $webmanRequest->remoteAddress();
        if ($clientIp) {
            $illuminateRequest->setClientIp($clientIp);
        }

        // 设置其他属性
        $illuminateRequest['webman_request'] = $webmanRequest;
        $illuminateRequest['webman_adapter'] = $this;
    }

    /**
     * 设置额外响应属性
     */
    protected function setAdditionalResponseAttributes(WorkermanResponse $webmanResponse, IlluminateResponse $illuminateResponse): void
    {
        // 设置原始响应对象
        $webmanResponse->withHeaders([
            'X-Powered-By' => 'webman-filament',
            'X-Filament-Version' => '4.0',
        ]);

        // 设置缓存控制
        if ($illuminateResponse->headers->has('Cache-Control')) {
            $cacheControl = $illuminateResponse->headers->get('Cache-Control');
            $webmanResponse->header('Cache-Control', $cacheControl);
        }

        // 设置其他属性
        $webmanResponse['illuminate_response'] = $illuminateResponse;
        $webmanResponse['filament_adapter'] = $this;
    }

    /**
     * 设置 JSON 请求头
     */
    protected function setJsonRequestHeaders(WorkermanRequest $webmanRequest): void
    {
        // 设置 JSON 相关头
        // 这里可以添加 JSON 请求头设置逻辑
    }

    /**
     * 创建 JSON 错误响应
     */
    protected function createJsonErrorResponse(\Exception $e): WorkermanResponse
    {
        $errorData = [
            'error' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'type' => get_class($e),
            ],
            'timestamp' => now()->toISOString(),
        ];

        $jsonResponse = new JsonResponse($errorData, 500);
        return $this->convertIlluminateResponseToWebman($jsonResponse);
    }

    /**
     * 设置下载头
     */
    protected function setDownloadHeaders(WorkermanResponse $webmanResponse, IlluminateResponse $illuminateResponse): void
    {
        // 设置下载相关头
        $contentDisposition = $illuminateResponse->headers->get('Content-Disposition');
        if ($contentDisposition) {
            $webmanResponse->header('Content-Disposition', $contentDisposition);
        }

        // 设置内容类型
        $contentType = $illuminateResponse->headers->get('Content-Type');
        if ($contentType) {
            $webmanResponse->header('Content-Type', $contentType);
        }
    }

    /**
     * 设置流式响应头
     */
    protected function setStreamingHeaders(WorkermanResponse $webmanResponse): void
    {
        $webmanResponse->header('Content-Type', 'text/plain; charset=utf-8');
        $webmanResponse->header('Transfer-Encoding', 'chunked');
        $webmanResponse->header('Cache-Control', 'no-cache');
    }

    /**
     * 获取请求配置
     */
    public function getRequestConfig(): array
    {
        return $this->requestConfig;
    }

    /**
     * 获取响应配置
     */
    public function getResponseConfig(): array
    {
        return $this->responseConfig;
    }

    /**
     * 更新配置
     */
    public function updateConfig(array $config): void
    {
        $this->requestConfig = array_merge($this->requestConfig, $config['request'] ?? []);
        $this->responseConfig = array_merge($this->responseConfig, $config['response'] ?? []);
    }
}