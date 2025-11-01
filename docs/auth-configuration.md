# 认证配置指南

本文档详细介绍Webman Filament Admin的认证系统配置和最佳实践。

## 目录

- [基础认证配置](#基础认证配置)
- [用户模型配置](#用户模型配置)
- [认证驱动](#认证驱动)
- [会话配置](#会话配置)
- [密码策略](#密码策略)
- [双因子认证](#双因子认证)
- [OAuth配置](#oauth配置)
- [权限系统](#权限系统)
- [安全配置](#安全配置)

## 基础认证配置

### 认证配置文件

```php
// config/auth.php
return [
    'defaults' => [
        'guard' => 'web',
        'passwords' => 'users',
    ],

    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
        'api' => [
            'driver' => 'sanctum',
            'provider' => 'users',
            'hash' => false,
        ],
        'admin' => [
            'driver' => 'session',
            'provider' => 'admins',
        ],
    ],

    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
        'admins' => [
            'driver' => 'eloquent',
            'model' => App\Models\Admin::class,
        ],
        'ldap' => [
            'driver' => 'ldap',
            'model' => App\Models\LdapUser::class,
            'database' => [
                'model' => App\Models\User::class,
                'table' => 'ldap_users',
            ],
        ],
    ],

    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
        'admins' => [
            'provider' => 'admins',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],

    'password_timeout' => 10800,
];
```

### 环境变量配置

```env
# 认证基础配置
AUTH_GUARD=web
AUTH_PROVIDER=users
AUTH_PASSWORD_TIMEOUT=10800

# 会话配置
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

# 密码配置
BCRYPT_ROUNDS=12
PASSWORD_MIN_LENGTH=8
PASSWORD_REQUIRE_UPPERCASE=true
PASSWORD_REQUIRE_LOWERCASE=true
PASSWORD_REQUIRE_NUMBERS=true
PASSWORD_REQUIRE_SYMBOLS=true

# 双因子认证
TWO_FACTOR_ENABLED=true
TWO_FACTOR_ISSUER="Webman Filament Admin"
TWO_FACTOR_WINDOW=1

# OAuth配置
OAUTH_CLIENT_ID=
OAUTH_CLIENT_SECRET=
OAUTH_REDIRECT_URI=
OAUTH_SCOPES=email,profile

# LDAP配置
LDAP_ENABLED=false
LDAP_HOST=ldap.example.com
LDAP_PORT=389
LDAP_BASE_DN=dc=example,dc=com
LDAP_USERNAME_ATTRIBUTE=uid
LDAP_EMAIL_ATTRIBUTE=mail
LDAP_NAME_ATTRIBUTE=givenName
```

## 用户模型配置

### User模型配置

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Casts\Attribute;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'status',
        'role',
        'last_login_at',
        'email_verified_at',
        'phone_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'last_login_at' => 'datetime',
        'password' => 'hashed',
        'two_factor_recovery_codes' => 'encrypted',
        'two_factor_secret' => 'encrypted',
        'is_active' => 'boolean',
        'email_verified' => 'boolean',
        'phone_verified' => 'boolean',
        'two_factor_enabled' => 'boolean',
    ];

    /**
     * 关联角色
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    /**
     * 关联权限
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'user_permissions');
    }

    /**
     * 检查用户是否有指定角色
     */
    public function hasRole($role)
    {
        if (is_string($role)) {
            return $this->roles->contains('name', $role);
        }

        return !! $role->intersect($this->roles)->count();
    }

    /**
     * 检查用户是否有指定权限
     */
    public function hasPermission($permission)
    {
        if ($this->roles->pluck('permissions')->flatten()->pluck('name')->contains($permission)) {
            return true;
        }

        return $this->permissions->pluck('name')->contains($permission);
    }

    /**
     * 密码修改器
     */
    protected function password(): Attribute
    {
        return Attribute::set(function ($value) {
            if (!is_string($value) || !preg_match('/^\$2y\$/', $value)) {
                return bcrypt($value);
            }
            return $value;
        });
    }

    /**
     * 获取头像URL
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->avatar) {
            return asset('storage/' . $this->avatar);
        }

        return asset('images/default-avatar.png');
    }

    /**
     * 检查用户是否活跃
     */
    public function isActive()
    {
        return $this->status === 'active';
    }

    /**
     * 检查用户是否被锁定
     */
    public function isLocked()
    {
        return $this->locked_until && $this->locked_until->isFuture();
    }

    /**
     * 锁定用户
     */
    public function lock($minutes = 30)
    {
        $this->update([
            'locked_until' => now()->addMinutes($minutes),
            'login_attempts' => 0,
        ]);
    }

    /**
     * 解锁用户
     */
    public function unlock()
    {
        $this->update([
            'locked_until' => null,
            'login_attempts' => 0,
        ]);
    }

    /**
     * 记录登录尝试
     */
    public function recordLoginAttempt($success = false)
    {
        if ($success) {
            $this->update([
                'last_login_at' => now(),
                'login_attempts' => 0,
                'locked_until' => null,
            ]);
        } else {
            $attempts = $this->login_attempts + 1;
            $lockedUntil = null;

            // 5次失败后锁定30分钟
            if ($attempts >= 5) {
                $lockedUntil = now()->addMinutes(30);
            }

            $this->update([
                'login_attempts' => $attempts,
                'locked_until' => $lockedUntil,
            ]);
        }
    }
}
```

## 认证驱动

### Session驱动配置

```php
// Session认证配置
'session' => [
    'driver' => 'session',
    'provider' => 'users',
    'timeout' => 120,
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'connection' => null,
    'table' => 'sessions',
    'store' => null,
    'lottery' => [2, 100],
    'cookie' => 'webman_filament_session',
    'path' => '/',
    'domain' => null,
    'secure' => env('SESSION_SECURE_COOKIE'),
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
],
```

### Sanctum API认证

```php
// Sanctum配置
'api' => [
    'driver' => 'sanctum',
    'provider' => 'users',
    'expiration' => null,
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
    'middleware' => [
        'auth:sanctum' => \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        'verify_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    ],
],

// Sanctum具体配置
'sanctum' => [
    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        env('APP_URL') ? ','.parse_url(env('APP_URL'), PHP_URL_HOST) : ''
    ))),
    'guard' => ['web'],
    'expiration' => null,
    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),
    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => App\Http\Middleware\EncryptCookies::class,
        'validate_csrf_token' => App\Http\Middleware\VerifyCsrfToken::class,
    ],
],
```

### LDAP认证驱动

```php
// LDAP认证配置
'ldap' => [
    'enabled' => env('LDAP_ENABLED', false),
    'connection' => [
        'hosts' => [env('LDAP_HOST', 'ldap.example.com')],
        'port' => env('LDAP_PORT', 389),
        'username' => env('LDAP_USERNAME'),
        'password' => env('LDAP_PASSWORD'),
        'base_dn' => env('LDAP_BASE_DN', 'dc=example,dc=com'),
        'timeout' => 5,
        'use_ssl' => env('LDAP_USE_SSL', false),
        'use_tls' => env('LDAP_USE_TLS', false),
    ],
    'user' => [
        'model' => App\Models\User::class,
        'database' => [
            'model' => App\Models\User::class,
            'table' => 'users',
            'guid_column' => 'objectguid',
            'username_column' => 'samaccountname',
        ],
        'scope' => 'App\Ldap\Scopes\UserScope::class',
    ],
    'logging' => [
        'enabled' => env('LDAP_LOGGING', true),
        'channel' => 'ldap',
    ],
],
```

## 会话配置

### Redis会话配置

```php
// config/session.php
return [
    'driver' => env('SESSION_DRIVER', 'file'),
    'lifetime' => env('SESSION_LIFETIME', 120),
    'expire_on_close' => false,
    'encrypt' => false,
    'files' => storage_path('framework/sessions'),
    'connection' => env('SESSION_CONNECTION'),
    'table' => 'sessions',
    'store' => env('SESSION_STORE'),
    'lottery' => [2, 100],
    'cookie' => env('SESSION_COOKIE', 'webman_filament_session'),
    'path' => '/',
    'domain' => env('SESSION_DOMAIN'),
    'secure' => env('SESSION_SECURE_COOKIE'),
    'http_only' => true,
    'same_site' => 'lax',
    'partitioned' => false,
    'redis' => [
        'connection' => 'session',
        'lock' => [
            'enabled' => true,
            'wait' => 10,
            'sleep' => 150,
        ],
    ],
];
```

### 会话安全配置

```php
// 会话安全配置
'session_security' => [
    'regenerate_on_login' => true,
    'invalidate_on_password_change' => true,
    'secure_cookies' => env('APP_ENV') === 'production',
    'httponly_cookies' => true,
    'samesite' => 'lax',
    'csrf_protection' => true,
    'session_fingerprinting' => true,
    'concurrent_sessions' => false,
    'max_concurrent_sessions' => 1,
],
```

## 密码策略

### 密码规则配置

```php
// config/password.php
return [
    'min_length' => env('PASSWORD_MIN_LENGTH', 8),
    'require_uppercase' => env('PASSWORD_REQUIRE_UPPERCASE', true),
    'require_lowercase' => env('PASSWORD_REQUIRE_LOWERCASE', true),
    'require_numbers' => env('PASSWORD_REQUIRE_NUMBERS', true),
    'require_symbols' => env('PASSWORD_REQUIRE_SYMBOLS', true),
    'max_attempts' => env('PASSWORD_MAX_ATTEMPTS', 5),
    'lockout_duration' => env('PASSWORD_LOCKOUT_DURATION', 900), // 15分钟
    'history_count' => env('PASSWORD_HISTORY_COUNT', 5),
    'expiration_days' => env('PASSWORD_EXPIRATION_DAYS', 90),
    'breach_detection' => [
        'enabled' => env('PASSWORD_BREACH_DETECTION', true),
        'api_key' => env('HIBP_API_KEY'),
        'cache_duration' => 86400, // 24小时
    ],
];
```

### 密码验证规则

```php
// Password validation rules
class PasswordValidator
{
    public static function rules(): array
    {
        return [
            'min_length' => 'min:' . config('password.min_length'),
            'require_uppercase' => 'regex:/[A-Z]/',
            'require_lowercase' => 'regex:/[a-z]/',
            'require_numbers' => 'regex:/[0-9]/',
            'require_symbols' => 'regex:/[!@#$%^&*(),.?":{}|<>]/',
        ];
    }

    public static function validate($attribute, $value, $fail)
    {
        $rules = self::rules();
        
        // 检查最小长度
        if (strlen($value) < config('password.min_length')) {
            $fail("密码长度至少需要" . config('password.min_length') . "个字符。");
        }
        
        // 检查大写字母
        if (config('password.require_uppercase') && !preg_match('/[A-Z]/', $value)) {
            $fail("密码必须包含至少一个大写字母。");
        }
        
        // 检查小写字母
        if (config('password.require_lowercase') && !preg_match('/[a-z]/', $value)) {
            $fail("密码必须包含至少一个小写字母。");
        }
        
        // 检查数字
        if (config('password.require_numbers') && !preg_match('/[0-9]/', $value)) {
            $fail("密码必须包含至少一个数字。");
        }
        
        // 检查特殊字符
        if (config('password.require_symbols') && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
            $fail("密码必须包含至少一个特殊字符。");
        }
        
        // 检查密码泄露
        if (config('password.breach_detection.enabled')) {
            if (self::isPasswordPwned($value)) {
                $fail("此密码已被泄露，请选择其他密码。");
            }
        }
    }

    private static function isPasswordPwned($password): bool
    {
        $hash = strtoupper(sha1($password));
        $prefix = substr($hash, 0, 5);
        $suffix = substr($hash, 5);
        
        $client = new \GuzzleHttp\Client();
        $response = $client->get("https://api.pwnedpasswords.com/range/{$prefix}");
        $hashes = explode("\n", $response->getBody());
        
        foreach ($hashes as $hash_line) {
            list($hash_suffix, $count) = explode(':', $hash_line);
            if ($hash_suffix === $suffix) {
                return (int) $count > 0;
            }
        }
        
        return false;
    }
}
```

## 双因子认证

### 2FA配置

```php
// config/two-factor.php
return [
    'enabled' => env('TWO_FACTOR_ENABLED', true),
    'issuer' => env('TWO_FACTOR_ISSUER', 'Webman Filament Admin'),
    'window' => env('TWO_FACTOR_WINDOW', 1),
    'digits' => env('TWO_FACTOR_DIGITS', 6),
    'algorithm' => env('TWO_FACTOR_ALGORITHM', 'sha1'),
    'period' => env('TWO_FACTOR_PERIOD', 30),
    'recovery_codes' => [
        'count' => 8,
        'length' => 10,
        'alphabet' => '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz',
    ],
    'backup_codes' => [
        'count' => 10,
        'length' => 8,
        'alphabet' => '0123456789',
    ],
];
```

### 2FA服务类

```php
<?php

namespace App\Services;

use App\Models\User;
use PragmaRX\Google2FA\Google2FA;
use BaconQrCode\Writer;
use BaconQrCode\WriterInterface;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Renderer\Image\Imagick;
use BaconQrCode\Renderer\Image\Svg;
use BaconQrCode\Writer as QrCodeWriter;

class TwoFactorService
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    /**
     * 生成2FA密钥
     */
    public function generateSecretKey(): string
    {
        return $this->google2fa->generateSecretKey();
    }

    /**
     * 生成QR码
     */
    public function generateQrCode(string $company, string $holder, string $secret): string
    {
        $g2fa = new Google2FA();
        $qrCodeUrl = $g2fa->getQRCodeUrl($company, $holder, $secret);
        
        $renderer = new Svg();
        $renderer->setWidth(200);
        $renderer->setHeight(200);
        
        $writer = new QrCodeWriter($renderer);
        $svgContent = $writer->writeString($qrCodeUrl);
        
        return $svgContent;
    }

    /**
     * 验证2FA代码
     */
    public function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        return $this->google2fa->verifyKey($secret, $code, $window);
    }

    /**
     * 启用2FA
     */
    public function enableTwoFactor(User $user, string $secret, array $recoveryCodes = []): bool
    {
        $user->update([
            'two_factor_secret' => encrypt($secret),
            'two_factor_enabled' => true,
            'two_factor_recovery_codes' => encrypt(json_encode($recoveryCodes)),
        ]);

        return true;
    }

    /**
     * 禁用2FA
     */
    public function disableTwoFactor(User $user): bool
    {
        $user->update([
            'two_factor_secret' => null,
            'two_factor_enabled' => false,
            'two_factor_recovery_codes' => null,
        ]);

        return true;
    }

    /**
     * 生成恢复代码
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        $alphabet = config('two-factor.recovery_codes.alphabet');
        
        for ($i = 0; $i < config('two-factor.recovery_codes.count'); $i++) {
            $code = '';
            for ($j = 0; $j < config('two-factor.recovery_codes.length'); $j++) {
                $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
            $codes[] = $code;
        }
        
        return $codes;
    }

    /**
     * 使用恢复代码
     */
    public function useRecoveryCode(User $user, string $code): bool
    {
        $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);
        
        $index = array_search($code, $recoveryCodes);
        
        if ($index !== false) {
            unset($recoveryCodes[$index]);
            
            $user->update([
                'two_factor_recovery_codes' => encrypt(json_encode(array_values($recoveryCodes))),
            ]);
            
            return true;
        }
        
        return false;
    }
}
```

## OAuth配置

### OAuth 2.0配置

```php
// config/oauth.php
return [
    'providers' => [
        'google' => [
            'client_id' => env('GOOGLE_CLIENT_ID'),
            'client_secret' => env('GOOGLE_CLIENT_SECRET'),
            'redirect' => env('APP_URL') . '/auth/google/callback',
            'scopes' => ['email', 'profile'],
        ],
        'github' => [
            'client_id' => env('GITHUB_CLIENT_ID'),
            'client_secret' => env('GITHUB_CLIENT_SECRET'),
            'redirect' => env('APP_URL') . '/auth/github/callback',
            'scopes' => ['user:email'],
        ],
        'facebook' => [
            'client_id' => env('FACEBOOK_CLIENT_ID'),
            'client_secret' => env('FACEBOOK_CLIENT_SECRET'),
            'redirect' => env('APP_URL') . '/auth/facebook/callback',
            'scopes' => ['email', 'public_profile'],
        ],
    ],
    'routes' => [
        'login' => '/auth/{provider}',
        'callback' => '/auth/{provider}/callback',
        'logout' => '/auth/logout',
    ],
    'middleware' => [
        'guest' => 'guest:oauth',
        'authenticated' => 'auth.oauth',
    ],
];
```

## 权限系统

### 权限配置

```php
// config/permissions.php
return [
    'models' => [
        'permission' => Spatie\Permission\Models\Permission::class,
        'role' => Spatie\Permission\Models\Role::class,
    ],
    'table_names' => [
        'permissions' => 'permissions',
        'roles' => 'roles',
        'role_has_permissions' => => 'role_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'model_has_permissions' => 'model_has_permissions',
    ],
    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'spatie.permission.cache',
        'cache_driver' => env('CACHE_DRIVER', 'file'),
        'store' => 'default',
    ],
];
```

### 权限中间件

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        if (!auth()->check()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        foreach ($permissions as $permission) {
            if (!$user->can($permission)) {
                abort(403, 'Insufficient permissions.');
            }
        }

        return $next($request);
    }
}
```

## 安全配置

### 安全头部配置

```php
// config/security.php
return [
    'headers' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'DENY',
        'X-XSS-Protection' => '1; mode=block',
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self' https:;",
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
    ],
    'csrf' => [
        'enabled' => true,
        'token_lifetime' => 3600,
        'same_site' => 'lax',
        'secure' => env('APP_ENV') === 'production',
    ],
    'rate_limiting' => [
        'login' => [
            'max_attempts' => 5,
            'decay_minutes' => 15,
        ],
        'password_reset' => [
            'max_attempts' => 3,
            'decay_minutes' => 60,
        ],
        'api' => [
            'max_attempts' => 60,
            'decay_minutes' => 1,
        ],
    ],
    'session' => [
        'regenerate_on_login' => true,
        'invalidate_on_password_change' => true,
        'secure_cookies' => env('APP_ENV') === 'production',
        'httponly_cookies' => true,
        'samesite' => 'lax',
    ],
];
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
        
        $headers = config('security.headers');
        
        foreach ($headers as $header => $value) {
            $response->headers->set($header, $value);
        }
        
        return $response;
    }
}
```

## 最佳实践

### 1. 密码安全

- 使用强密码策略
- 启用密码历史检查
- 定期更新密码
- 检测密码泄露

### 2. 会话安全

- 使用安全的会话配置
- 启用会话固定保护
- 设置合理的会话超时
- 加密敏感会话数据

### 3. 访问控制

- 实施最小权限原则
- 使用基于角色的访问控制
- 定期审查用户权限
- 禁用不必要的账户

### 4. 监控和日志

- 记录所有认证事件
- 监控异常登录行为
- 设置告警机制
- 定期审计日志

## 故障排除

### 常见问题

1. **会话丢失**
   ```php
   // 检查会话配置
   config('session.driver');
   config('session.lifetime');
   ```

2. **2FA验证失败**
   ```php
   // 检查时间同步
   date_default_timezone_set('UTC');
   ```

3. **权限不生效**
   ```php
   // 清除权限缓存
   app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
   ```

### 调试命令

```bash
# 查看用户权限
php artisan tinker
$user = User::find(1);
$user->getAllPermissions();

# 清除缓存
php artisan permission:cache-reset
php artisan config:clear
php artisan cache:clear

# 查看会话信息
php artisan session:table
php artisan migrate:status
```