<?php

namespace App\Middleware;

use App\Adapter\AuthAdapter;
use App\Adapter\PermissionAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

/**
 * 认证中间件 - 桥接 Laravel 中间件与 webman 洋葱模型
 * 
 * 功能特性：
 * - 支持多种认证方式（Session、API Token、MFA）
 * - 集成权限检查
 * * 支持动态权限验证
 * - 提供认证状态缓存
 * - 支持自定义认证逻辑
 */
class AuthMiddleware
{
    /**
     * @var AuthAdapter
     */
    protected AuthAdapter $authAdapter;

    /**
     * @var PermissionAdapter
     */
    protected PermissionAdapter $permissionAdapter;

    /**
     * @var array 排除认证的路由
     */
    protected array $exceptRoutes = [
        'login',
        'logout',
        'register',
        'forgot-password',
        'reset-password',
        'verify-email',
        'mfa-setup',
        'mfa-verify'
    ];

    public function __construct(
        AuthAdapter $authAdapter,
        PermissionAdapter $permissionAdapter
    ) {
        $this->authAdapter = $authAdapter;
        $this->permissionAdapter = $permissionAdapter;
    }

    /**
     * 处理认证中间件
     *
     * @param Request $request
     * @param \Closure $next
     * @param string $guard
     * @param string|null $permission
     * @return mixed
     */
    public function handle(Request $request, \Closure $next, string $guard = 'web', ?string $permission = null)
    {
        try {
            // 检查是否需要跳过认证
            if ($this->shouldSkipAuth($request)) {
                return $next($request);
            }

            // 检查认证状态
            $authResult = $this->authAdapter->check($guard);
            
            if (!$authResult['success']) {
                return $this->handleUnauthenticated($request, $guard);
            }

            $user = $authResult['data']['user'];

            // 检查用户是否需要 MFA 验证
            if ($this->requiresMfaVerification($user, $request)) {
                return $this->handleMfaRequired($request, $guard);
            }

            // 检查权限
            if ($permission && !$this->checkPermission($user, $permission, $request)) {
                return $this->handleUnauthorized($request, $permission);
            }

            // 添加用户信息到请求对象
            $request->setUserResolver(function () use ($user, $guard) {
                return $user;
            });

            // 记录访问日志
            $this->logAccess($request, $user);

            return $next($request);

        } catch (\Exception $e) {
            Log::error('认证中间件异常', [
                'route' => Route::currentRouteName(),
                'uri' => $request->getUri(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('认证处理失败', [], 500);
        }
    }

    /**
     * 检查是否应该跳过认证
     */
    protected function shouldSkipAuth(Request $request): bool
    {
        $routeName = Route::currentRouteName();
        
        // 检查路由名称
        if (in_array($routeName, $this->exceptRoutes)) {
            return true;
        }

        // 检查路由 URI 模式
        $exceptPatterns = [
            '/^api\/auth\//',
            '/^auth\//',
            '/^public\//',
            '/^health$/',
            '/^ping$/'
        ];

        foreach ($exceptPatterns as $pattern) {
            if (preg_match($pattern, $request->path())) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否需要 MFA 验证
     */
    protected function requiresMfaVerification($user, Request $request): bool
    {
        // 如果用户启用了 MFA 但尚未验证
        if ($user->mfa_enabled && !$user->mfa_verified_at) {
            // 检查当前路由是否需要 MFA
            $mfaRequiredRoutes = config('auth.mfa_required_routes', []);
            $routeName = Route::currentRouteName();
            
            return in_array($routeName, $mfaRequiredRoutes);
        }

        return false;
    }

    /**
     * 检查权限
     */
    protected function checkPermission($user, string $permission, Request $request): bool
    {
        // 获取资源模型（如果适用）
        $model = $this->getResourceModel($request);
        
        return $this->permissionAdapter->can($permission, $model);
    }

    /**
     * 获取资源模型
     */
    protected function getResourceModel(Request $request): ?\Illuminate\Database\Eloquent\Model
    {
        // 根据路由参数获取模型实例
        $route = Route::current();
        $parameters = $route ? $route->parameters() : [];

        // 查找模型参数（通常是 ID 参数对应的模型）
        foreach ($parameters as $key => $value) {
            if (is_string($value) && class_exists($value)) {
                continue; // 跳过类名参数
            }
            
            // 尝试从参数名推断模型类型
            $modelClass = $this->inferModelClass($key);
            if ($modelClass && is_numeric($value)) {
                return $modelClass::find($value);
            }
        }

        return null;
    }

    /**
     * 从参数名推断模型类
     */
    protected function inferModelClass(string $parameterName): ?string
    {
        $modelMappings = config('auth.model_mappings', [
            'user' => \App\Models\User::class,
            'role' => \App\Models\Role::class,
            'permission' => \App\Models\Permission::class,
            'post' => \App\Models\Post::class,
            'category' => \App\Models\Category::class
        ]);

        return $modelMappings[$parameterName] ?? null;
    }

    /**
     * 处理未认证情况
     */
    protected function handleUnauthenticated(Request $request, string $guard): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => '未认证，请先登录',
                'code' => 401
            ], 401);
        }

        // 保存 intended URL
        $request->session()->put('url.intended', $request->fullUrl());

        // 重定向到登录页面
        $loginRoute = config('auth.login_route', 'login');
        
        return redirect()->route($loginRoute)->with('error', '请先登录');
    }

    /**
     * 处理需要 MFA 验证的情况
     */
    protected function handleMfaRequired(Request $request, string $guard): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => '需要多因素认证',
                'code' => 428,
                'requires_mfa' => true
            ], 428);
        }

        // 重定向到 MFA 验证页面
        $mfaRoute = config('auth.mfa_verify_route', 'mfa.verify');
        
        return redirect()->route($mfaRoute)->with('warning', '请完成多因素认证');
    }

    /**
     * 处理未授权情况
     */
    protected function handleUnauthorized(Request $request, string $permission): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => '权限不足',
                'required_permission' => $permission,
                'code' => 403
            ], 403);
        }

        // 重定向到权限不足页面
        $unauthorizedRoute = config('auth.unauthorized_route', 'unauthorized');
        
        return redirect()->route($unauthorizedRoute)->with('error', '权限不足');
    }

    /**
     * 记录访问日志
     */
    protected function logAccess(Request $request, $user): void
    {
        try {
            Log::channel('access')->info('用户访问', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'route' => Route::currentRouteName(),
                'uri' => $request->getUri(),
                'method' => $request->getMethod(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error('访问日志记录失败', [
                'error' => $e->getMessage(),
                'user_id' => $user->id
            ]);
        }
    }

    /**
     * 错误响应
     */
    protected function errorResponse(string $message, array $errors = [], int $code = 500): Response
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
            'timestamp' => now()->toISOString()
        ], $code);
    }

    /**
     * 认证后处理（可选的后置中间件）
     */
    public function after(Request $request, Response $response): void
    {
        // 可以在这里添加后置处理逻辑
        // 例如：更新最后活动时间、清理临时数据等
    }
}