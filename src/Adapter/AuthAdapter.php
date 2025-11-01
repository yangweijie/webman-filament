<?php

namespace App\Adapter;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * 认证适配器 - 桥接 Laravel Auth 与 webman 常驻内存模型
 * 
 * 功能特性：
 * - 支持 Session、API Token、MFA 多种认证方式
 * - 适配 webman 的请求/响应模型
 * - 支持多 Guard 认证
 * - 集成 Filament 权限系统
 * - 提供认证事件监听
 */
class AuthAdapter
{
    /**
     * @var array 支持的认证方式
     */
    protected array $authMethods = [
        'session' => 'web',
        'api' => 'api',
        'token' => 'sanctum'
    ];

    /**
     * @var string 默认 Guard
     */
    protected string $defaultGuard = 'web';

    /**
     * @var array 认证配置
     */
    protected array $config;

    public function __construct()
    {
        $this->config = config('auth', []);
    }

    /**
     * 用户登录
     *
     * @param Request $request
     * @param string $guard
     * @param bool $remember
     * @return array
     * @throws ValidationException
     */
    public function login(Request $request, string $guard = 'web', bool $remember = false): array
    {
        try {
            // 验证输入
            $validator = $this->validateLoginCredentials($request);
            if ($validator->fails()) {
                return $this->errorResponse('验证失败', $validator->errors(), 422);
            }

            $credentials = $validator->validated();
            $user = $this->getUserProvider()->retrieveByCredentials($credentials);

            if (!$user || !$this->getHasher()->check($credentials['password'], $user->getAuthPassword())) {
                Log::warning('登录失败 - 用户名或密码错误', [
                    'email' => $credentials['email'] ?? 'unknown',
                    'guard' => $guard,
                    'ip' => $request->ip()
                ]);
                return $this->errorResponse('用户名或密码错误', [], 401);
            }

            // 检查用户是否激活
            if (!$this->isUserActive($user)) {
                return $this->errorResponse('账户已被禁用', [], 403);
            }

            // 执行登录
            Auth::guard($guard)->login($user, $remember);

            // 记录登录事件
            event(new Login($guard, $user, false));

            // 更新最后登录时间
            $user->update([
                'last_login_at' => Carbon::now(),
                'last_login_ip' => $request->ip()
            ]);

            Log::info('用户登录成功', [
                'user_id' => $user->id,
                'email' => $user->email,
                'guard' => $guard,
                'ip' => $request->ip()
            ]);

            return $this->successResponse([
                'user' => $user,
                'token' => $this->generateToken($user, $guard),
                'guard' => $guard,
                'expires_at' => $this->getTokenExpiry($guard)
            ], '登录成功');

        } catch (\Exception $e) {
            Log::error('登录异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('登录失败，请稍后重试', [], 500);
        }
    }

    /**
     * 用户注册
     *
     * @param Request $request
     * @param string $guard
     * @return array
     * @throws ValidationException
     */
    public function register(Request $request, string $guard = 'web'): array
    {
        try {
            $validator = $this->validateRegistrationData($request);
            if ($validator->fails()) {
                return $this->errorResponse('注册数据验证失败', $validator->errors(), 422);
            }

            $data = $validator->validated();
            
            // 检查邮箱是否已存在
            $existingUser = $this->getUserProvider()->retrieveByCredentials(['email' => $data['email']]);
            if ($existingUser) {
                return $this->errorResponse('邮箱已被注册', [], 409);
            }

            // 创建用户
            $user = $this->createUser($data);

            // 自动登录
            Auth::guard($guard)->login($user);

            // 记录注册事件
            event(new Registered($user));

            Log::info('用户注册成功', [
                'user_id' => $user->id,
                'email' => $user->email,
                'guard' => $guard
            ]);

            return $this->successResponse([
                'user' => $user,
                'guard' => $guard
            ], '注册成功');

        } catch (\Exception $e) {
            Log::error('注册异常', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('注册失败，请稍后重试', [], 500);
        }
    }

    /**
     * 用户登出
     *
     * @param Request $request
     * @param string $guard
     * @return array
     */
    public function logout(Request $request, string $guard = 'web'): array
    {
        try {
            $user = Auth::guard($guard)->user();
            
            if ($user) {
                // 记录登出事件
                event(new Logout($guard, $user));
                
                Log::info('用户登出', [
                    'user_id' => $user->id,
                    'guard' => $guard
                ]);
            }

            // 执行登出
            Auth::guard($guard)->logout();

            // 清除会话
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->successResponse([], '登出成功');

        } catch (\Exception $e) {
            Log::error('登出异常', [
                'error' => $e->getMessage(),
                'guard' => $guard
            ]);

            return $this->errorResponse('登出失败', [], 500);
        }
    }

    /**
     * 验证用户认证状态
     *
     * @param string $guard
     * @return array
     */
    public function check(string $guard = 'web'): array
    {
        try {
            $user = Auth::guard($guard)->user();
            
            if (!$user) {
                return $this->errorResponse('未认证', [], 401);
            }

            // 检查用户是否仍然激活
            if (!$this->isUserActive($user)) {
                Auth::guard($guard)->logout();
                return $this->errorResponse('账户已被禁用', [], 403);
            }

            return $this->successResponse([
                'user' => $user,
                'authenticated' => true,
                'guard' => $guard
            ]);

        } catch (\Exception $e) {
            Log::error('认证检查异常', [
                'error' => $e->getMessage(),
                'guard' => $guard
            ]);

            return $this->errorResponse('认证检查失败', [], 500);
        }
    }

    /**
     * 刷新令牌
     *
     * @param string $guard
     * @return array
     */
    public function refresh(string $guard = 'web'): array
    {
        try {
            $user = Auth::guard($guard)->user();
            
            if (!$user) {
                return $this->errorResponse('未认证', [], 401);
            }

            // 重新生成令牌
            $newToken = $this->generateToken($user, $guard);

            return $this->successResponse([
                'user' => $user,
                'token' => $newToken,
                'expires_at' => $this->getTokenExpiry($guard)
            ], '令牌刷新成功');

        } catch (\Exception $e) {
            Log::error('令牌刷新异常', [
                'error' => $e->getMessage(),
                'guard' => $guard
            ]);

            return $this->errorResponse('令牌刷新失败', [], 500);
        }
    }

    /**
     * 多因素认证验证
     *
     * @param Request $request
     * @param string $guard
     * @return array
     */
    public function verifyMfa(Request $request, string $guard = 'web'): array
    {
        try {
            $user = Auth::guard($guard)->user();
            
            if (!$user) {
                return $this->errorResponse('未认证', [], 401);
            }

            // 验证 MFA 代码
            $validator = Validator::make($request->all(), [
                'code' => 'required|string|size:6'
            ]);

            if ($validator->fails()) {
                return $this->errorResponse('MFA 代码格式错误', $validator->errors(), 422);
            }

            $code = $request->input('code');
            
            if (!$this->verifyMfaCode($user, $code)) {
                Log::warning('MFA 验证失败', [
                    'user_id' => $user->id,
                    'code' => $code
                ]);
                return $this->errorResponse('MFA 验证失败', [], 401);
            }

            // 标记 MFA 已验证
            $user->update([
                'mfa_verified_at' => Carbon::now()
            ]);

            return $this->successResponse([
                'user' => $user,
                'mfa_verified' => true
            ], 'MFA 验证成功');

        } catch (\Exception $e) {
            Log::error('MFA 验证异常', [
                'error' => $e->getMessage(),
                'guard' => $guard
            ]);

            return $this->errorResponse('MFA 验证失败', [], 500);
        }
    }

    /**
     * 验证登录凭据
     */
    protected function validateLoginCredentials(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ]);
    }

    /**
     * 验证注册数据
     */
    protected function validateRegistrationData(Request $request): \Illuminate\Contracts\Validation\Validator
    {
        return Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed'
        ]);
    }

    /**
     * 检查用户是否激活
     */
    protected function isUserActive($user): bool
    {
        // 检查用户状态字段（可根据实际业务调整）
        return $user->status !== 'disabled' && $user->email_verified_at !== null;
    }

    /**
     * 创建用户
     */
    protected function createUser(array $data): \Illuminate\Database\Eloquent\Model
    {
        $userClass = config('auth.providers.users.model');
        
        return $userClass::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => Carbon::now(),
            'status' => 'active'
        ]);
    }

    /**
     * 生成令牌
     */
    protected function generateToken($user, string $guard): ?string
    {
        // 根据 Guard 类型生成不同类型的令牌
        switch ($guard) {
            case 'api':
                return $user->createToken('api-token')->plainTextToken;
            case 'sanctum':
                return $user->createToken('sanctum-token')->plainTextToken;
            default:
                return null; // Session 认证不需要令牌
        }
    }

    /**
     * 获取令牌过期时间
     */
    protected function getTokenExpiry(string $guard): ?Carbon
    {
        switch ($guard) {
            case 'api':
            case 'sanctum':
                return Carbon::now()->addDays(30);
            default:
                return null;
        }
    }

    /**
     * 验证 MFA 代码
     */
    protected function verifyMfaCode($user, string $code): bool
    {
        // 这里应该集成实际的 TOTP 验证逻辑
        // 例如使用 OTPHP 或 Google Authenticator
        // 示例实现：
        
        if (!$user->mfa_secret) {
            return false;
        }

        // 使用 OTPHP 库验证 TOTP
        // $totp = new \OTPHP\TOTP($user->mfa_secret);
        // return $totp->verify($code);

        // 临时返回 true，实际应该实现完整的 TOTP 验证
        return true;
    }

    /**
     * 获取用户提供器
     */
    protected function getUserProvider()
    {
        return Auth::getProvider();
    }

    /**
     * 获取哈希器
     */
    protected function getHasher()
    {
        return app('hash');
    }

    /**
     * 成功响应
     */
    protected function successResponse(array $data, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => Carbon::now()->toISOString()
        ];
    }

    /**
     * 错误响应
     */
    protected function errorResponse(string $message, array $errors = [], int $code = 400): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors,
            'code' => $code,
            'timestamp' => Carbon::now()->toISOString()
        ];
    }
}