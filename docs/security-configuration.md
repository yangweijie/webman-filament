# 安全配置指南

本文档详细介绍Webman Filament Admin的安全配置策略和最佳实践。

## 目录

- [基础安全配置](#基础安全配置)
- [认证安全](#认证安全)
- [授权控制](#授权控制)
- [数据安全](#数据安全)
- [网络安全](#网络安全)
- [应用安全](#应用安全)
- [安全监控](#安全监控)
- [安全审计](#安全审计)
- [应急响应](#应急响应)

## 基础安全配置

### 安全配置文件

```php
// config/security.php
return [
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self' https:; frame-ancestors 'none';",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        'Cross-Origin-Embedder-Policy' => 'require-corp',
        'Cross-Origin-Opener-Policy' => 'same-origin',
    ],
    
    'csrf' => [
        'enabled' => true,
        'token_lifetime' => 3600,
        'same_site' => 'lax',
        'secure' => env('APP_ENV') === 'production',
        'http_only' => true,
        'methods' => ['POST', 'PUT', 'PATCH', 'DELETE'],
        'exempt_routes' => [
            'webhook/*',
            'api/webhook/*',
        ],
    ],
    
    'rate_limiting' => [
        'enabled' => true,
        'limits' => [
            'login' => [
                'max_attempts' => 5,
                'decay_minutes' => 15,
                'strategy' => 'sliding_window',
            ],
            'password_reset' => [
                'max_attempts' => 3,
                'decay_minutes' => 60,
            ],
            'registration' => [
                'max_attempts' => 3,
                'decay_minutes' => 60,
            ],
            'api' => [
                'max_attempts' => 60,
                'decay_minutes' => 1,
            ],
            'general' => [
                'max_attempts' => 100,
                'decay_minutes' => 1,
            ],
        ],
    ],
    
    'session' => [
        'regenerate_on_login' => true,
        'invalidate_on_password_change' => true,
        'secure_cookies' => env('APP_ENV') === 'production',
        'httponly_cookies' => true,
        'samesite' => 'lax',
        'concurrent_sessions' => false,
        'timeout' => 120,
        'fingerprinting' => true,
    ],
    
    'encryption' => [
        'key' => env('APP_KEY'),
        'cipher' => 'AES-256-CBC',
        'algorithm' => 'aes-256-gcm',
        'key_length' => 32,
    ],
    
    'password' => [
        'min_length' => 8,
        'require_uppercase' => true,
        'require_lowercase' => true,
        'require_numbers' => true,
        'require_symbols' => true,
        'max_age' => 90, // 天
        'history_count' => 5,
        'breach_detection' => true,
    ],
    
    'audit' => [
        'enabled' => true,
        'events' => [
            'login' => true,
            'logout' => true,
            'failed_login' => true,
            'password_change' => true,
            'permission_change' => true,
            'data_access' => true,
            'data_modification' => true,
            'admin_actions' => true,
        ],
        'retention_days' => 365,
        'storage' => 'database',
    ],
];
```

### 环境安全配置

```env
# 安全基础配置
APP_KEY=base64:your-32-character-secret-key
APP_DEBUG=false
APP_ENV=production
APP_URL=https://your-domain.com

# SSL/TLS配置
SSL_CERT_PATH=/path/to/certificate.pem
SSL_KEY_PATH=/path/to/private-key.pem
SSL_CA_PATH=/path/to/ca-bundle.pem

# 安全头部配置
SECURITY_HEADERS_ENABLED=true
CSP_ENABLED=true
HSTS_ENABLED=true

# 认证配置
AUTH_SESSION_TIMEOUT=120
AUTH_MAX_LOGIN_ATTEMPTS=5
AUTH_LOCKOUT_DURATION=900
AUTH_PASSWORD_MIN_LENGTH=8

# 速率限制配置
RATE_LIMIT_ENABLED=true
RATE_LIMIT_LOGIN=5,15
RATE_LIMIT_API=60,1
RATE_LIMIT_GENERAL=100,1

# 审计配置
AUDIT_ENABLED=true
AUDIT_RETENTION_DAYS=365

# 监控配置
SECURITY_MONITORING_ENABLED=true
SECURITY_ALERT_EMAIL=admin@your-domain.com
SECURITY_ALERT_SLACK_WEBHOOK=https://hooks.slack.com/...

# 加密配置
ENCRYPTION_ALGORITHM=aes-256-gcm
HASH_ALGORITHM=sha256
```

### 安全中间件

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // 获取配置的安全头部
        $headers = config('security.headers');
        
        foreach ($headers as $header => $value) {
            // 动态生成CSP头部
            if ($header === 'Content-Security-Policy') {
                $value = $this->generateCSP($request);
            }
            
            $response->headers->set($header, $value);
        }
        
        // 添加自定义安全头部
        $response->headers->set('X-Powered-By', 'Webman Filament');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        
        return $response;
    }
    
    protected function generateCSP(Request $request): string
    {
        $csp = "default-src 'self'";
        
        // 根据环境调整CSP策略
        if (app()->environment('local')) {
            $csp .= " 'unsafe-inline' 'unsafe-eval'";
        }
        
        // 添加动态域名
        $allowedDomains = config('security.csp.allowed_domains', []);
        foreach ($allowedDomains as $domain) {
            $csp .= " https://{$domain}";
        }
        
        return $csp;
    }
}
```

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\RateLimiter;

class RateLimitMiddleware
{
    public function handle(Request $request, Closure $next, string $limit = 'general'): Response
    {
        $key = $this->getRateLimitKey($request, $limit);
        $limitConfig = config("security.rate_limiting.limits.{$limit}");
        
        if (!$limitConfig) {
            return $next($request);
        }
        
        $maxAttempts = $limitConfig['max_attempts'];
        $decayMinutes = $limitConfig['decay_minutes'];
        
        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'message' => 'Too many requests',
                'retry_after' => $seconds,
            ], 429, [
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
                'X-RateLimit-Reset' => now()->addSeconds($seconds)->timestamp,
            ]);
        }
        
        RateLimiter::hit($key, $decayMinutes * 60);
        
        $response = $next($request);
        
        // 添加速率限制头部
        $remaining = $maxAttempts - RateLimiter::attempts($key);
        $response->headers->set('X-RateLimit-Limit', $maxAttempts);
        $response->headers->set('X-RateLimit-Remaining', max(0, $remaining));
        $response->headers->set('X-RateLimit-Reset', now()->addMinutes($decayMinutes)->timestamp);
        
        return $response;
    }
    
    protected function getRateLimitKey(Request $request, string $limit): string
    {
        // 根据用户IP和限制类型生成key
        $ip = $request->ip();
        $userId = auth()->id();
        
        if ($userId) {
            return "{$limit}:user:{$userId}";
        }
        
        return "{$limit}:ip:{$ip}";
    }
}
```

## 认证安全

### 强密码策略

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PasswordSecurityService
{
    /**
     * 验证密码强度
     */
    public function validatePasswordStrength(string $password): array
    {
        $errors = [];
        $config = config('security.password');
        
        // 检查最小长度
        if (strlen($password) < $config['min_length']) {
            $errors[] = "密码长度至少需要{$config['min_length']}个字符";
        }
        
        // 检查大写字母
        if ($config['require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = "密码必须包含至少一个大写字母";
        }
        
        // 检查小写字母
        if ($config['require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            $errors[] = "密码必须包含至少一个小写字母";
        }
        
        // 检查数字
        if ($config['require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = "密码必须包含至少一个数字";
        }
        
        // 检查特殊字符
        if ($config['require_symbols'] && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = "密码必须包含至少一个特殊字符";
        }
        
        // 检查常见密码
        if ($this->isCommonPassword($password)) {
            $errors[] = "密码过于常见，请选择更安全的密码";
        }
        
        // 检查密码泄露
        if ($config['breach_detection'] && $this->isPasswordPwned($password)) {
            $errors[] = "此密码已被泄露，请选择其他密码";
        }
        
        return $errors;
    }
    
    /**
     * 检查密码历史
     */
    public function checkPasswordHistory(int $userId, string $password): bool
    {
        $historyCount = config('security.password.history_count');
        
        $historicalPasswords = DB::table('password_history')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($historyCount)
            ->pluck('password_hash')
            ->toArray();
        
        foreach ($historicalPasswords as $hash) {
            if (Hash::check($password, $hash)) {
                return false; // 密码在历史记录中找到
            }
        }
        
        return true;
    }
    
    /**
     * 保存密码历史
     */
    public function savePasswordHistory(int $userId, string $password): void
    {
        DB::table('password_history')->insert([
            'user_id' => $userId,
            'password_hash' => Hash::make($password),
            'created_at' => now(),
        ]);
        
        // 清理旧的历史记录
        $this->cleanOldPasswordHistory($userId);
    }
    
    /**
     * 清理旧密码历史
     */
    protected function cleanOldPasswordHistory(int $userId): void
    {
        $historyCount = config('security.password.history_count');
        
        $oldRecords = DB::table('password_history')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->offset($historyCount)
            ->pluck('id');
        
        if ($oldRecords->isNotEmpty()) {
            DB::table('password_history')
                ->whereIn('id', $oldRecords)
                ->delete();
        }
    }
    
    /**
     * 检查常见密码
     */
    protected function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            '123456', 'password', '123456789', '12345678', '12345',
            'qwerty', 'abc123', '111111', '123123', 'password123',
            'admin', 'letmein', 'welcome', 'monkey', 'dragon',
        ];
        
        return in_array(strtolower($password), $commonPasswords);
    }
    
    /**
     * 检查密码是否被泄露
     */
    protected function isPasswordPwned(string $password): bool
    {
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);
        
        try {
            $client = new \GuzzleHttp\Client();
            $response = $client->get("https://api.pwnedpasswords.com/range/{$prefix}", [
                'headers' => [
                    'User-Agent' => 'Webman-Filament-Security',
                ],
            ]);
            
            $hashes = explode("\n", $response->getBody());
            
            foreach ($hashes as $hashLine) {
                list($hashSuffix, $count) = explode(':', trim($hashLine));
                if ($hashSuffix === $suffix && (int) $count > 0) {
                    return true;
                }
            }
        } catch (\Exception $e) {
            \Log::warning('Password breach check failed: ' . $e->getMessage());
        }
        
        return false;
    }
    
    /**
     * 生成安全密码
     */
    public function generateSecurePassword(int $length = 16): string
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $symbols = '!@#$%^&*(),.?":{}|<>';
        
        $allChars = $uppercase . $lowercase . $numbers . $symbols;
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $symbols[random_int(0, strlen($symbols) - 1)];
        
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }
}
```

### 账户锁定机制

```php
<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AccountLockService
{
    protected $maxAttempts;
    protected $lockoutDuration;
    
    public function __construct()
    {
        $this->maxAttempts = config('security.max_login_attempts', 5);
        $this->lockoutDuration = config('security.lockout_duration', 900); // 15分钟
    }
    
    /**
     * 记录登录尝试
     */
    public function recordLoginAttempt(string $identifier, bool $success = true): array
    {
        $key = "login_attempts:{$identifier}";
        $attempts = Cache::get($key, []);
        
        if ($success) {
            // 成功登录，清除失败记录
            Cache::forget($key);
            $this->logSecurityEvent('login_success', $identifier);
            
            return ['success' => true, 'locked' => false];
        } else {
            // 失败登录，记录尝试
            $attempts[] = now()->timestamp;
            
            // 保留最近24小时的记录
            $attempts = array_filter($attempts, function ($timestamp) {
                return $timestamp > (now()->timestamp - 86400);
            });
            
            Cache::put($key, $attempts, 86400);
            
            $this->logSecurityEvent('login_failed', $identifier, ['attempts' => count($attempts)]);
            
            // 检查是否需要锁定
            if (count($attempts) >= $this->maxAttempts) {
                $this->lockAccount($identifier);
                return ['success' => false, 'locked' => true];
            }
            
            return ['success' => false, 'locked' => false];
        }
    }
    
    /**
     * 检查账户是否被锁定
     */
    public function isAccountLocked(string $identifier): bool
    {
        $lockKey = "account_lock:{$identifier}";
        $lockUntil = Cache::get($lockKey);
        
        if ($lockUntil && $lockUntil > now()) {
            return true;
        }
        
        return false;
    }
    
    /**
     * 锁定账户
     */
    protected function lockAccount(string $identifier): void
    {
        $lockKey = "account_lock:{$identifier}";
        $lockUntil = now()->addSeconds($this->lockoutDuration);
        
        Cache::put($lockKey, $lockUntil, $this->lockoutDuration);
        
        $this->logSecurityEvent('account_locked', $identifier, [
            'lock_until' => $lockUntil,
        ]);
    }
    
    /**
     * 解锁账户
     */
    public function unlockAccount(string $identifier): void
    {
        $lockKey = "account_lock:{$identifier}";
        $attemptKey = "login_attempts:{$identifier}";
        
        Cache::forget($lockKey);
        Cache::forget($attemptKey);
        
        $this->logSecurityEvent('account_unlocked', $identifier);
    }
    
    /**
     * 获取锁定剩余时间
     */
    public function getLockRemainingTime(string $identifier): int
    {
        $lockKey = "account_lock:{$identifier}";
        $lockUntil = Cache::get($lockKey);
        
        if ($lockUntil && $lockUntil > now()) {
            return $lockUntil->diffInSeconds(now());
        }
        
        return 0;
    }
    
    /**
     * 记录安全事件
     */
    protected function logSecurityEvent(string $event, string $identifier, array $context = []): void
    {
        Log::channel('security')->info($event, [
            'identifier' => $identifier,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'context' => $context,
        ]);
    }
}
```

## 授权控制

### 权限管理服务

```php
<?php

namespace App\Services;

use App\Models\User;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PermissionService
{
    protected $cachePrefix = 'permissions:';
    protected $cacheTtl = 3600; // 1小时
    
    /**
     * 检查用户权限
     */
    public function hasPermission(User $user, string $permission): bool
    {
        // 超级管理员拥有所有权限
        if ($user->hasRole('super_admin')) {
            return true;
        }
        
        $cacheKey = $this->cachePrefix . "user:{$user->id}:{$permission}";
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user, $permission) {
            return $user->hasPermissionTo($permission);
        });
    }
    
    /**
     * 检查用户角色
     */
    public function hasRole(User $user, string $role): bool
    {
        return $user->hasRole($role);
    }
    
    /**
     * 批量检查权限
     */
    public function hasAnyPermission(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($user, $permission)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * 检查所有权限
     */
    public function hasAllPermissions(User $user, array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($user, $permission)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 授予权限
     */
    public function grantPermission(User $user, string $permission): bool
    {
        try {
            $user->givePermissionTo($permission);
            $this->clearPermissionCache($user);
            $this->logPermissionChange($user, 'permission_granted', $permission);
            return true;
        } catch (\Exception $e) {
            \Log::error('Permission grant failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 撤销权限
     */
    public function revokePermission(User $user, string $permission): bool
    {
        try {
            $user->revokePermissionTo($permission);
            $this->clearPermissionCache($user);
            $this->logPermissionChange($user, 'permission_revoked', $permission);
            return true;
        } catch (\Exception $e) {
            \Log::error('Permission revoke failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 分配角色
     */
    public function assignRole(User $user, string $role): bool
    {
        try {
            $user->assignRole($role);
            $this->clearPermissionCache($user);
            $this->logPermissionChange($user, 'role_assigned', $role);
            return true;
        } catch (\Exception $e) {
            \Log::error('Role assignment failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 移除角色
     */
    public function removeRole(User $user, string $role): bool
    {
        try {
            $user->removeRole($role);
            $this->clearPermissionCache($user);
            $this->logPermissionChange($user, 'role_removed', $role);
            return true;
        } catch (\Exception $e) {
            \Log::error('Role removal failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 获取用户所有权限
     */
    public function getUserPermissions(User $user): array
    {
        return $user->getAllPermissions()->pluck('name')->toArray();
    }
    
    /**
     * 获取用户所有角色
     */
    public function getUserRoles(User $user): array
    {
        return $user->getRoleNames()->toArray();
    }
    
    /**
     * 清除权限缓存
     */
    protected function clearPermissionCache(User $user): void
    {
        $pattern = $this->cachePrefix . "user:{$user->id}:*";
        $this->clearCacheByPattern($pattern);
    }
    
    /**
     * 按模式清除缓存
     */
    protected function clearCacheByPattern(string $pattern): void
    {
        $redis = Cache::getStore()->getRedis();
        $keys = $redis->keys($pattern);
        
        if (!empty($keys)) {
            $redis->del($keys);
        }
    }
    
    /**
     * 记录权限变更
     */
    protected function logPermissionChange(User $user, string $action, string $resource): void
    {
        DB::table('permission_audit_log')->insert([
            'user_id' => $user->id,
            'action' => $action,
            'resource' => $resource,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);
    }
    
    /**
     * 权限审计
     */
    public function auditPermissions(User $user): array
    {
        $auditLog = DB::table('permission_audit_log')
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        return [
            'current_permissions' => $this->getUserPermissions($user),
            'current_roles' => $this->getUserRoles($user),
            'audit_log' => $auditLog,
        ];
    }
}
```

### RBAC权限中间件

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\PermissionService;

class RoleBasedAccessControl
{
    protected $permissionService;
    
    public function __construct(PermissionService $permissionService)
    {
        $this->permissionService = $permissionService;
    }
    
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }
        
        $user = auth()->user();
        $route = $request->route();
        $action = $route->getActionMethod();
        
        // 检查角色权限
        if (!$this->checkRolePermissions($user, $permissions)) {
            abort(403, 'Insufficient permissions');
        }
        
        // 记录访问
        $this->logAccess($user, $request);
        
        return $next($request);
    }
    
    protected function checkRolePermissions($user, array $permissions): bool
    {
        // 超级管理员拥有所有权限
        if ($this->permissionService->hasRole($user, 'super_admin')) {
            return true;
        }
        
        // 检查是否有任何权限
        foreach ($permissions as $permission) {
            if ($this->permissionService->hasPermission($user, $permission)) {
                return true;
            }
        }
        
        return false;
    }
    
    protected function logAccess($user, Request $request): void
    {
        DB::table('access_log')->insert([
            'user_id' => $user->id,
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'created_at' => now(),
        ]);
    }
}
```

## 数据安全

### 数据加密服务

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DataEncryptionService
{
    protected $algorithm = 'aes-256-gcm';
    protected $key;
    
    public function __construct()
    {
        $this->key = config('app.key');
    }
    
    /**
     * 加密敏感数据
     */
    public function encrypt(string $data): string
    {
        return Crypt::encryptString($data);
    }
    
    /**
     * 解密敏感数据
     */
    public function decrypt(string $encryptedData): string
    {
        return Crypt::decryptString($encryptedData);
    }
    
    /**
     * 加密文件
     */
    public function encryptFile(string $filePath, string $outputPath): bool
    {
        try {
            $content = file_get_contents($filePath);
            $encrypted = $this->encrypt($content);
            
            return file_put_contents($outputPath, $encrypted) !== false;
        } catch (\Exception $e) {
            \Log::error('File encryption failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 解密文件
     */
    public function decryptFile(string $encryptedFilePath, string $outputPath): bool
    {
        try {
            $encrypted = file_get_contents($encryptedFilePath);
            $decrypted = $this->decrypt($encrypted);
            
            return file_put_contents($outputPath, $decrypted) !== false;
        } catch (\Exception $e) {
            \Log::error('File decryption failed: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * 生成安全的随机字符串
     */
    public function generateSecureToken(int $length = 32): string
    {
        return Str::random($length);
    }
    
    /**
     * 生成哈希值
     */
    public function hash(string $data): string
    {
        return hash('sha256', $data);
    }
    
    /**
     * 验证哈希值
     */
    public function verifyHash(string $data, string $hash): bool
    {
        return hash_equals($this->hash($data), $hash);
    }
    
    /**
     * 加密数据库字段
     */
    public function encryptDatabaseField(string $value): string
    {
        return $this->encrypt($value);
    }
    
    /**
     * 解密数据库字段
     */
    public function decryptDatabaseField(string $encryptedValue): string
    {
        return $this->decrypt($encryptedValue);
    }
}
```

### 数据脱敏服务

```php
<?php

namespace App\Services;

class DataMaskingService
{
    /**
     * 脱敏邮箱地址
     */
    public function maskEmail(string $email): string
    {
        $parts = explode('@', $email);
        
        if (count($parts) !== 2) {
            return $email;
        }
        
        $username = $parts[0];
        $domain = $parts[1];
        
        if (strlen($username) <= 2) {
            $maskedUsername = str_repeat('*', strlen($username));
        } else {
            $maskedUsername = substr($username, 0, 2) . str_repeat('*', strlen($username) - 2);
        }
        
        return $maskedUsername . '@' . $domain;
    }
    
    /**
     * 脱敏手机号码
     */
    public function maskPhone(string $phone): string
    {
        if (strlen($phone) < 11) {
            return str_repeat('*', strlen($phone));
        }
        
        return substr($phone, 0, 3) . str_repeat('*', 4) . substr($phone, -4);
    }
    
    /**
     * 脱敏身份证号
     */
    public function maskIdCard(string $idCard): string
    {
        if (strlen($idCard) < 8) {
            return str_repeat('*', strlen($idCard));
        }
        
        return substr($idCard, 0, 4) . str_repeat('*', strlen($idCard) - 8) . substr($idCard, -4);
    }
    
    /**
     * 脱敏银行卡号
     */
    public function maskBankCard(string $bankCard): string
    {
        if (strlen($bankCard) < 8) {
            return str_repeat('*', strlen($bankCard));
        }
        
        return substr($bankCard, 0, 4) . str_repeat('*', strlen($bankCard) - 8) . substr($bankCard, -4);
    }
    
    /**
     * 脱敏IP地址
     */
    public function maskIpAddress(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $ip);
            return $parts[0] . '.' . $parts[1] . '.xxx.xxx';
        } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return substr($ip, 0, 4) . 'xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx:xxxx';
        }
        
        return 'xxx.xxx.xxx.xxx';
    }
    
    /**
     * 批量脱敏数据
     */
    public function maskData(array $data, array $fields): array
    {
        foreach ($fields as $field => $maskType) {
            if (isset($data[$field])) {
                $data[$field] = $this->applyMasking($data[$field], $maskType);
            }
        }
        
        return $data;
    }
    
    protected function applyMasking(string $value, string $maskType): string
    {
        return match ($maskType) {
            'email' => $this->maskEmail($value),
            'phone' => $this->maskPhone($value),
            'id_card' => $this->maskIdCard($value),
            'bank_card' => $this->maskBankCard($value),
            'ip' => $this->maskIpAddress($value),
            'partial' => $this->maskPartial($value),
            'full' => str_repeat('*', strlen($value)),
            default => $value,
        };
    }
    
    protected function maskPartial(string $value): string
    {
        $length = strlen($value);
        
        if ($length <= 2) {
            return str_repeat('*', $length);
        }
        
        return substr($value, 0, 1) . str_repeat('*', $length - 2) . substr($value, -1);
    }
}
```

## 网络安全

### SSL/TLS配置

```nginx
# Nginx SSL配置
server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com;
    
    # SSL证书配置
    ssl_certificate /path/to/certificate.pem;
    ssl_certificate_key /path/to/private-key.pem;
    ssl_trusted_certificate /path/to/ca-bundle.pem;
    
    # SSL安全配置
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    ssl_session_tickets off;
    
    # OCSP装订
    ssl_stapling on;
    ssl_stapling_verify on;
    resolver 8.8.8.8 8.8.4.4 valid=300s;
    resolver_timeout 5s;
    
    # HSTS
    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;
    
    # 其他安全头部
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header Referrer-Policy "strict-origin-when-cross-origin" always;
    
    # 证书透明度
    add_header Expect-CT "max-age=86400, enforce" always;
    
    # 其他配置...
}

# HTTP重定向到HTTPS
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}
```

### 防火墙配置

```bash
#!/bin/bash
# firewall-setup.sh

# 清理现有规则
iptables -F
iptables -X
iptables -t nat -F
iptables -t nat -X
iptables -t mangle -F
iptables -t mangle -X

# 设置默认策略
iptables -P INPUT DROP
iptables -P FORWARD DROP
iptables -P OUTPUT ACCEPT

# 允许本地回环
iptables -A INPUT -i lo -j ACCEPT
iptables -A OUTPUT -o lo -j ACCEPT

# 允许已建立的连接
iptables -A INPUT -m state --state ESTABLISHED,RELATED -j ACCEPT

# 允许SSH（端口22）
iptables -A INPUT -p tcp --dport 22 -j ACCEPT

# 允许HTTP（端口80）
iptables -A INPUT -p tcp --dport 80 -j ACCEPT

# 允许HTTPS（端口443）
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# 允许Ping
iptables -A INPUT -p icmp --icmp-type echo-request -j ACCEPT

# 限制连接频率
iptables -A INPUT -p tcp --dport 22 -m limit --limit 3/min --limit-burst 3 -j ACCEPT
iptables -A INPUT -p tcp --dport 80 -m limit --limit 25/min --limit-burst 100 -j ACCEPT
iptables -A INPUT -p tcp --dport 443 -m limit --limit 25/min --limit-burst 100 -j ACCEPT

# 防止端口扫描
iptables -A INPUT -m state --state NEW -m recent --set
iptables -A INPUT -m state --state NEW -m recent --update --seconds 60 --hitcount 10 -j DROP

# 防止SYN洪水攻击
iptables -A INPUT -p tcp --syn -m limit --limit 2/s --limit-burst 30 -j ACCEPT

# 防止ping洪水
iptables -A INPUT -p icmp --icmp-type echo-request -m limit --limit 1/s --limit-burst 2 -j ACCEPT

# 保存规则
iptables-save > /etc/iptables/rules.v4
```

## 应用安全

### 输入验证

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class InputValidationService
{
    /**
     * 验证用户输入
     */
    public function validateInput(array $data, array $rules): array
    {
        $validator = Validator::make($data, $rules);
        
        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => $validator->errors(),
            ];
        }
        
        return [
            'valid' => true,
            'data' => $validator->validated(),
        ];
    }
    
    /**
     * SQL注入防护
     */
    public function sanitizeSqlInput(string $input): string
    {
        // 移除危险字符
        $dangerous = [';', '--', '/*', '*/', 'xp_', 'sp_'];
        
        foreach ($dangerous as $pattern) {
            $input = str_ireplace($pattern, '', $input);
        }
        
        return trim($input);
    }
    
    /**
     * XSS防护
     */
    public function sanitizeXssInput(string $input): string
    {
        // 移除脚本标签
        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi', '', $input);
        
        // 移除事件处理器
        $input = preg_replace('/\s+on\w+\s*=/i', '', $input);
        
        // 移除javascript:协议
        $input = preg_replace('/javascript:/i', '', $input);
        
        // HTML实体编码
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * 文件上传验证
     */
    public function validateFileUpload($file, array $allowedTypes = [], int $maxSize = 5120): array
    {
        $errors = [];
        
        // 检查文件是否上传
        if (!$file || !$file->isValid()) {
            $errors[] = '文件上传失败';
            return ['valid' => false, 'errors' => $errors];
        }
        
        // 检查文件大小
        if ($file->getSize() > $maxSize * 1024) {
            $errors[] = "文件大小不能超过{$maxSize}KB";
        }
        
        // 检查文件类型
        if (!empty($allowedTypes)) {
            $mimeType = $file->getMimeType();
            if (!in_array($mimeType, $allowedTypes)) {
                $errors[] = '不允许的文件类型';
            }
        }
        
        // 检查文件扩展名
        $extension = $file->getClientOriginalExtension();
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx'];
        
        if (!in_array(strtolower($extension), $allowedExtensions)) {
            $errors[] = '不允许的文件扩展名';
        }
        
        // 检查文件名
        $filename = $file->getClientOriginalName();
        if (preg_match('/[^a-zA-Z0-9._-]/', $filename)) {
            $errors[] = '文件名包含非法字符';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    /**
     * CSRF令牌验证
     */
    public function validateCsrfToken(string $token): bool
    {
        $sessionToken = session('_token');
        
        return hash_equals($sessionToken, $token);
    }
    
    /**
     * 验证邮箱地址
     */
    public function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * 验证URL
     */
    public function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * 验证IP地址
     */
    public function validateIpAddress(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP) !== false;
    }
}
```

### 文件上传安全

```php
<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SecureFileUploadService
{
    protected $allowedMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ];
    
    protected $maxFileSize = 5120; // 5MB
    
    /**
     * 安全文件上传
     */
    public function uploadSecureFile(UploadedFile $file, string $directory = 'uploads'): array
    {
        // 验证文件
        $validation = $this->validateFile($file);
        if (!$validation['valid']) {
            return $validation;
        }
        
        // 生成安全文件名
        $filename = $this->generateSecureFilename($file);
        
        // 检查目录是否存在
        $this->ensureDirectoryExists($directory);
        
        // 上传文件
        $path = $file->storeAs($directory, $filename, 'public');
        
        // 记录上传日志
        $this->logFileUpload($filename, $file->getSize(), $file->getMimeType());
        
        return [
            'valid' => true,
            'path' => $path,
            'filename' => $filename,
            'url' => Storage::url($path),
        ];
    }
    
    /**
     * 验证文件
     */
    protected function validateFile(UploadedFile $file): array
    {
        $errors = [];
        
        // 检查文件大小
        if ($file->getSize() > $this->maxFileSize * 1024) {
            $errors[] = "文件大小不能超过{$this->maxFileSize}KB";
        }
        
        // 检查MIME类型
        if (!in_array($file->getMimeType(), $this->allowedMimeTypes)) {
            $errors[] = '不允许的文件类型';
        }
        
        // 检查文件内容
        if (!$this->validateFileContent($file)) {
            $errors[] = '文件内容不合法';
        }
        
        // 检查文件名
        if (!$this->validateFilename($file->getClientOriginalName())) {
            $errors[] = '文件名包含非法字符';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    /**
     * 验证文件内容
     */
    protected function validateFileContent(UploadedFile $file): bool
    {
        $handle = fopen($file->getPathname(), 'r');
        $chunk = fread($handle, 1024);
        fclose($handle);
        
        // 检查是否包含恶意代码
        $maliciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
        ];
        
        foreach ($maliciousPatterns as $pattern) {
            if (preg_match($pattern, $chunk)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * 验证文件名
     */
    protected function validateFilename(string $filename): bool
    {
        // 检查是否包含路径遍历字符
        if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
            return false;
        }
        
        // 检查是否包含特殊字符
        if (preg_match('/[^a-zA-Z0-9._-]/', $filename)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * 生成安全文件名
     */
    protected function generateSecureFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $randomName = Str::random(32);
        
        return $randomName . '.' . $extension;
    }
    
    /**
     * 确保目录存在
     */
    protected function ensureDirectoryExists(string $directory): void
    {
        if (!Storage::exists($directory)) {
            Storage::makeDirectory($directory);
        }
    }
    
    /**
     * 记录文件上传日志
     */
    protected function logFileUpload(string $filename, int $size, string $mimeType): void
    {
        \Log::channel('security')->info('file_upload', [
            'filename' => $filename,
            'size' => $size,
            'mime_type' => $mimeType,
            'user_id' => auth()->id(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }
}
```

## 安全监控

### 安全监控服务

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class SecurityMonitoringService
{
    protected $alertThresholds = [
        'failed_logins' => 10, // 每小时
        'suspicious_requests' => 50, // 每小时
        'file_uploads' => 100, // 每小时
        'admin_actions' => 20, // 每小时
    ];
    
    /**
     * 监控安全事件
     */
    public function monitorSecurityEvents(): void
    {
        $this->checkFailedLogins();
        $this->checkSuspiciousRequests();
        $this->checkFileUploads();
        $this->checkAdminActions();
    }
    
    /**
     * 检查失败登录
     */
    protected function checkFailedLogins(): void
    {
        $failedLogins = DB::table('login_attempts')
            ->where('success', false)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($failedLogins > $this->alertThresholds['failed_logins']) {
            $this->sendAlert('high_failed_logins', [
                'count' => $failedLogins,
                'threshold' => $this->alertThresholds['failed_logins'],
            ]);
        }
    }
    
    /**
     * 检查可疑请求
     */
    protected function checkSuspiciousRequests(): void
    {
        $suspiciousRequests = DB::table('access_log')
            ->where('suspicious', true)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($suspiciousRequests > $this->alertThresholds['suspicious_requests']) {
            $this->sendAlert('high_suspicious_requests', [
                'count' => $suspiciousRequests,
                'threshold' => $this->alertThresholds['suspicious_requests'],
            ]);
        }
    }
    
    /**
     * 检查文件上传
     */
    protected function checkFileUploads(): void
    {
        $fileUploads = DB::table('file_uploads')
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($fileUploads > $this->alertThresholds['file_uploads']) {
            $this->sendAlert('high_file_uploads', [
                'count' => $fileUploads,
                'threshold' => $this->alertThresholds['file_uploads'],
            ]);
        }
    }
    
    /**
     * 检查管理员操作
     */
    protected function checkAdminActions(): void
    {
        $adminActions = DB::table('admin_actions')
            ->where('created_at', '>=', now()->subHour())
            ->count();
        
        if ($adminActions > $this->alertThresholds['admin_actions']) {
            $this->sendAlert('high_admin_actions', [
                'count' => $adminActions,
                'threshold' => $this->alertThresholds['admin_actions'],
            ]);
        }
    }
    
    /**
     * 记录安全事件
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        DB::table('security_events')->insert([
            'event' => $event,
            'context' => json_encode($context),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
        
        // 记录到日志
        Log::channel('security')->info($event, $context);
    }
    
    /**
     * 发送告警
     */
    protected function sendAlert(string $alertType, array $data): void
    {
        // 记录告警
        DB::table('security_alerts')->insert([
            'type' => $alertType,
            'data' => json_encode($data),
            'sent_at' => now(),
        ]);
        
        // 发送邮件告警
        $this->sendEmailAlert($alertType, $data);
        
        // 发送Slack告警
        $this->sendSlackAlert($alertType, $data);
        
        Log::channel('security')->warning("Security alert: {$alertType}", $data);
    }
    
    /**
     * 发送邮件告警
     */
    protected function sendEmailAlert(string $alertType, array $data): void
    {
        $email = config('security.alerts.email');
        
        if (!$email) {
            return;
        }
        
        // 实现邮件发送逻辑
        // 可以使用Laravel的Mail功能
    }
    
    /**
     * 发送Slack告警
     */
    protected function sendSlackAlert(string $alertType, array $data): void
    {
        $webhook = config('security.alerts.slack_webhook');
        
        if (!$webhook) {
            return;
        }
        
        $message = [
            'text' => "Security Alert: {$alertType}",
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => [
                        [
                            'title' => 'Alert Type',
                            'value' => $alertType,
                            'short' => true,
                        ],
                        [
                            'title' => 'Count',
                            'value' => $data['count'] ?? 'N/A',
                            'short' => true,
                        ],
                        [
                            'title' => 'Threshold',
                            'value' => $data['threshold'] ?? 'N/A',
                            'short' => true,
                        ],
                        [
                            'title' => 'Time',
                            'value' => now()->toDateTimeString(),
                            'short' => true,
                        ],
                    ],
                ],
            ],
        ];
        
        Http::post($webhook, $message);
    }
    
    /**
     * 获取安全统计
     */
    public function getSecurityStats(): array
    {
        return [
            'failed_logins_24h' => DB::table('login_attempts')
                ->where('success', false)
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            
            'suspicious_requests_24h' => DB::table('access_log')
                ->where('suspicious', true)
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            
            'file_uploads_24h' => DB::table('file_uploads')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            
            'security_events_24h' => DB::table('security_events')
                ->where('created_at', '>=', now()->subDay())
                ->count(),
            
            'active_alerts' => DB::table('security_alerts')
                ->where('sent_at', '>=', now()->subDay())
                ->count(),
        ];
    }
}
```

## 安全审计

### 审计日志服务

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class AuditLogService
{
    /**
     * 记录用户操作
     */
    public function logUserAction(string $action, array $context = []): void
    {
        DB::table('audit_logs')->insert([
            'user_id' => Auth::id(),
            'action' => $action,
            'context' => json_encode($context),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'url' => request()->fullUrl(),
            'method' => request()->method(),
            'created_at' => now(),
        ]);
    }
    
    /**
     * 记录数据访问
     */
    public function logDataAccess(string $resource, string $operation, array $data = []): void
    {
        DB::table('data_access_logs')->insert([
            'user_id' => Auth::id(),
            'resource' => $resource,
            'operation' => $operation,
            'data' => json_encode($data),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
    
    /**
     * 记录权限变更
     */
    public function logPermissionChange(string $action, string $resource, $targetUserId = null): void
    {
        DB::table('permission_audit_logs')->insert([
            'user_id' => Auth::id(),
            'target_user_id' => $targetUserId,
            'action' => $action,
            'resource' => $resource,
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }
    
    /**
     * 记录系统事件
     */
    public function logSystemEvent(string $event, array $context = []): void
    {
        DB::table('system_audit_logs')->insert([
            'event' => $event,
            'context' => json_encode($context),
            'created_at' => now(),
        ]);
    }
    
    /**
     * 获取审计报告
     */
    public function getAuditReport(array $filters = []): array
    {
        $query = DB::table('audit_logs')->orderBy('created_at', 'desc');
        
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        
        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        
        if (isset($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }
        
        return $query->limit(1000)->get()->toArray();
    }
}
```

## 应急响应

### 应急响应计划

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IncidentResponseService
{
    /**
     * 处理安全事件
     */
    public function handleSecurityIncident(string $type, array $data): void
    {
        // 创建事件记录
        $incidentId = DB::table('security_incidents')->insertGetId([
            'type' => $type,
            'status' => 'open',
            'data' => json_encode($data),
            'created_at' => now(),
        ]);
        
        // 执行响应措施
        $this->executeResponseActions($type, $data);
        
        // 通知相关人员
        $this->notifyIncident($incidentId, $type, $data);
        
        Log::channel('security')->critical("Security incident: {$type}", [
            'incident_id' => $incidentId,
            'data' => $data,
        ]);
    }
    
    /**
     * 执行响应措施
     */
    protected function executeResponseActions(string $type, array $data): void
    {
        switch ($type) {
            case 'brute_force':
                $this->handleBruteForce($data);
                break;
            case 'suspicious_activity':
                $this->handleSuspiciousActivity($data);
                break;
            case 'data_breach':
                $this->handleDataBreach($data);
                break;
            case 'malware_detection':
                $this->handleMalwareDetection($data);
                break;
        }
    }
    
    /**
     * 处理暴力破解
     */
    protected function handleBruteForce(array $data): void
    {
        // 封禁IP地址
        $this->banIpAddress($data['ip_address']);
        
        // 锁定相关账户
        if (isset($data['user_id'])) {
            $this->lockUserAccount($data['user_id']);
        }
        
        // 增强监控
        $this->enhanceMonitoring($data['ip_address']);
    }
    
    /**
     * 处理可疑活动
     */
    protected function handleSuspiciousActivity(array $data): void
    {
        // 标记可疑用户
        if (isset($data['user_id'])) {
            DB::table('users')
                ->where('id', $data['user_id'])
                ->update(['suspicious' => true]);
        }
        
        // 启用额外验证
        $this->enableAdditionalVerification($data['user_id'] ?? null);
    }
    
    /**
     * 处理数据泄露
     */
    protected function handleDataBreach(array $data): void
    {
        // 立即通知
        $this->sendImmediateNotification('data_breach', $data);
        
        // 记录泄露详情
        $this->logDataBreach($data);
        
        // 启动恢复程序
        $this->initiateRecoveryProcedure($data);
    }
    
    /**
     * 封禁IP地址
     */
    protected function banIpAddress(string $ipAddress): void
    {
        DB::table('banned_ips')->insert([
            'ip_address' => $ipAddress,
            'reason' => 'security_incident',
            'banned_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);
    }
    
    /**
     * 锁定用户账户
     */
    protected function lockUserAccount(int $userId): void
    {
        DB::table('users')
            ->where('id', $userId)
            ->update([
                'locked_until' => now()->addDays(7),
                'locked_reason' => 'security_incident',
            ]);
    }
    
    /**
     * 发送事件通知
     */
    protected function notifyIncident(int $incidentId, string $type, array $data): void
    {
        // 发送邮件通知
        $this->sendEmailNotification($incidentId, $type, $data);
        
        // 发送Slack通知
        $this->sendSlackNotification($incidentId, $type, $data);
        
        // 记录通知日志
        DB::table('incident_notifications')->insert([
            'incident_id' => $incidentId,
            'type' => $type,
            'sent_at' => now(),
        ]);
    }
}
```

## 最佳实践

### 1. 安全开发

- 实施安全编码标准
- 进行代码审查
- 使用安全开发工具
- 定期安全测试

### 2. 访问控制

- 最小权限原则
- 多因素认证
- 定期权限审查
- 及时撤销权限

### 3. 数据保护

- 加密敏感数据
- 安全传输协议
- 定期数据备份
- 访问日志记录

### 4. 监控告警

- 实时安全监控
- 异常行为检测
- 及时告警响应
- 定期安全评估

### 5. 应急响应

- 制定响应计划
- 定期演练
- 快速响应机制
- 事后分析改进

## 安全检查清单

### 部署前检查

- [ ] 更新所有依赖包
- [ ] 配置SSL证书
- [ ] 设置强密码策略
- [ ] 配置防火墙规则
- [ ] 启用安全头部
- [ ] 配置日志记录
- [ ] 设置监控告警
- [ ] 备份重要数据

### 定期检查

- [ ] 更新安全补丁
- [ ] 检查访问日志
- [ ] 审查用户权限
- [ ] 测试备份恢复
- [ ] 扫描安全漏洞
- [ ] 更新安全策略
- [ ] 培训安全意识
- [ ] 评估安全风险

### 事件响应

- [ ] 立即隔离影响
- [ ] 评估影响范围
- [ ] 收集相关证据
- [ ] 通知相关人员
- [ ] 修复安全漏洞
- [ ] 恢复系统服务
- [ ] 记录处理过程
- [ ] 总结经验教训