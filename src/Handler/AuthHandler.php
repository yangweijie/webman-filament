<?php

namespace App\Handler;

use App\Adapter\AuthAdapter;
use App\Adapter\PermissionAdapter;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

/**
 * 认证处理器 - 处理认证相关的业务逻辑
 * 
 * 功能特性：
 * - 密码重置处理
 * - 邮箱验证处理
 * - MFA 设置和管理
 * - 账户锁定/解锁
 * - 认证事件监听
 * - 安全日志记录
 */
class AuthHandler
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
     * @var int 密码重置令牌过期时间（分钟）
     */
    protected int $resetTokenExpiry = 60;

    /**
     * @var int 最大登录失败次数
     */
    protected int $maxLoginAttempts = 5;

    /**
     * @var int 账户锁定时间（分钟）
     */
    protected int $lockoutDuration = 30;

    public function __construct(
        AuthAdapter $authAdapter,
        PermissionAdapter $permissionAdapter
    ) {
        $this->authAdapter = $authAdapter;
        $this->permissionAdapter = $permissionAdapter;
    }

    /**
     * 处理密码重置请求
     *
     * @param Request $request
     * @return array
     */
    public function forgotPassword(Request $request): array
    {
        try {
            $request->validate([
                'email' => 'required|email|exists:users,email'
            ]);

            $status = Password::sendResetLink(
                $request->only('email')
            );

            if ($status === Password::RESET_LINK_SENT) {
                Log::info('密码重置链接发送成功', [
                    'email' => $request->email,
                    'ip' => $request->ip()
                ]);

                return [
                    'success' => true,
                    'message' => '密码重置链接已发送到您的邮箱',
                    'status' => $status
                ];
            }

            return [
                'success' => false,
                'message' => '密码重置链接发送失败',
                'status' => $status
            ];

        } catch (\Exception $e) {
            Log::error('密码重置请求异常', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '密码重置请求失败，请稍后重试'
            ];
        }
    }

    /**
     * 处理密码重置
     *
     * @param Request $request
     * @return array
     */
    public function resetPassword(Request $request): array
    {
        try {
            $request->validate([
                'token' => 'required',
                'email' => 'required|email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            $status = Password::reset(
                $request->only('email', 'password', 'password_confirmation', 'token'),
                function ($user, $password) {
                    $user->forceFill([
                        'password' => Hash::make($password),
                        'remember_token' => Str::random(60),
                    ])->save();

                    event(new PasswordReset($user));
                }
            );

            if ($status === Password::PASSWORD_RESET) {
                Log::info('密码重置成功', [
                    'email' => $request->email,
                    'ip' => $request->ip()
                ]);

                return [
                    'success' => true,
                    'message' => '密码重置成功，请使用新密码登录',
                    'status' => $status
                ];
            }

            return [
                'success' => false,
                'message' => '密码重置失败',
                'status' => $status
            ];

        } catch (\Exception $e) {
            Log::error('密码重置异常', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '密码重置失败，请稍后重试'
            ];
        }
    }

    /**
     * 处理邮箱验证
     *
     * @param Request $request
     * @return array
     */
    public function verifyEmail(Request $request): array
    {
        try {
            $user = auth('web')->user();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => '用户未认证'
                ];
            }

            if ($user->hasVerifiedEmail()) {
                return [
                    'success' => false,
                    'message' => '邮箱已经验证'
                ];
            }

            $user->sendEmailVerificationNotification();

            return [
                'success' => true,
                'message' => '验证邮件已发送'
            ];

        } catch (\Exception $e) {
            Log::error('邮箱验证异常', [
                'user_id' => auth('web')->id(),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => '邮箱验证失败，请稍后重试'
            ];
        }
    }

    /**
     * 设置 MFA
     *
     * @param Request $request
     * @return array
     */
    public function setupMfa(Request $request): array
    {
        try {
            $user = auth('web')->user();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => '用户未认证'
                ];
            }

            // 生成 MFA 密钥和二维码
            $secret = $this->generateMfaSecret();
            $qrCodeUrl = $this->generateQrCodeUrl($user, $secret);

            // 临时保存密钥（实际应该加密存储）
            $user->update([
                'mfa_secret_temp' => $secret
            ]);

            return [
                'success' => true,
                'message' => 'MFA 设置信息生成成功',
                'data' => [
                    'secret' => $secret,
                    'qr_code_url' => $qrCodeUrl,
                    'backup_codes' => $this->generateBackupCodes()
                ]
            ];

        } catch (\Exception $e) {
            Log::error('MFA 设置异常', [
                'user_id' => auth('web')->id(),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'MFA 设置失败，请稍后重试'
            ];
        }
    }

    /**
     * 启用 MFA
     *
     * @param Request $request
     * @return array
     */
    public function enableMfa(Request $request): array
    {
        try {
            $user = auth('web')->user();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => '用户未认证'
                ];
            }

            $request->validate([
                'code' => 'required|string|size:6',
                'backup_codes' => 'required|array'
            ]);

            // 验证 MFA 代码
            if (!$this->verifyMfaCode($user, $request->code)) {
                return [
                    'success' => false,
                    'message' => 'MFA 验证失败'
                ];
            }

            // 启用 MFA
            $user->update([
                'mfa_enabled' => true,
                'mfa_secret' => $user->mfa_secret_temp,
                'mfa_backup_codes' => json_encode($request->backup_codes),
                'mfa_enabled_at' => Carbon::now()
            ]);

            // 清除临时密钥
            $user->update(['mfa_secret_temp' => null]);

            Log::info('用户启用 MFA', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'MFA 启用成功'
            ];

        } catch (\Exception $e) {
            Log::error('启用 MFA 异常', [
                'user_id' => auth('web')->id(),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'MFA 启用失败，请稍后重试'
            ];
        }
    }

    /**
     * 禁用 MFA
     *
     * @param Request $request
     * @return array
     */
    public function disableMfa(Request $request): array
    {
        try {
            $user = auth('web')->user();

            if (!$user) {
                return [
                    'success' => false,
                    'message' => '用户未认证'
                ];
            }

            // 验证密码确认
            if (!Hash::check($request->password, $user->password)) {
                return [
                    'success' => false,
                    'message' => '密码错误'
                ];
            }

            // 禁用 MFA
            $user->update([
                'mfa_enabled' => false,
                'mfa_secret' => null,
                'mfa_backup_codes' => null,
                'mfa_enabled_at' => null,
                'mfa_verified_at' => null
            ]);

            Log::info('用户禁用 MFA', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            return [
                'success' => true,
                'message' => 'MFA 禁用成功'
            ];

        } catch (\Exception $e) {
            Log::error('禁用 MFA 异常', [
                'user_id' => auth('web')->id(),
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => 'MFA 禁用失败，请稍后重试'
            ];
        }
    }

    /**
     * 检查登录失败次数并处理账户锁定
     *
     * @param Request $request
     * @return array
     */
    public function checkLoginAttempts(Request $request): array
    {
        try {
            $email = $request->email;
            $ip = $request->ip();
            
            $cacheKey = "login_attempts:{$email}:{$ip}";
            $attempts = cache()->get($cacheKey, 0);

            if ($attempts >= $this->maxLoginAttempts) {
                $lockoutTime = cache()->get("lockout:{$email}:{$ip}");
                
                if ($lockoutTime && now()->lt($lockoutTime)) {
                    $remainingTime = now()->diffInMinutes($lockoutTime);
                    
                    return [
                        'success' => false,
                        'locked' => true,
                        'message' => "账户已锁定，请 {$remainingTime} 分钟后再试",
                        'remaining_time' => $remainingTime
                    ];
                } else {
                    // 锁定时间已过，清除记录
                    cache()->forget($cacheKey);
                    cache()->forget("lockout:{$email}:{$ip}");
                }
            }

            return [
                'success' => true,
                'attempts' => $attempts,
                'remaining_attempts' => $this->maxLoginAttempts - $attempts
            ];

        } catch (\Exception $e) {
            Log::error('检查登录尝试异常', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);

            return [
                'success' => true,
                'message' => '检查登录状态失败'
            ];
        }
    }

    /**
     * 记录登录失败
     *
     * @param Request $request
     * @return void
     */
    public function recordLoginAttempt(Request $request): void
    {
        try {
            $email = $request->email;
            $ip = $request->ip();
            
            $cacheKey = "login_attempts:{$email}:{$ip}";
            $attempts = cache()->get($cacheKey, 0) + 1;
            
            cache()->put($cacheKey, $attempts, now()->addHours(1));

            if ($attempts >= $this->maxLoginAttempts) {
                $lockoutTime = now()->addMinutes($this->lockoutDuration);
                cache()->put("lockout:{$email}:{$ip}", $lockoutTime, $lockoutTime);
                
                Log::warning('账户被锁定', [
                    'email' => $email,
                    'ip' => $ip,
                    'attempts' => $attempts
                ]);
            }

        } catch (\Exception $e) {
            Log::error('记录登录尝试异常', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 清除登录失败记录
     *
     * @param Request $request
     * @return void
     */
    public function clearLoginAttempts(Request $request): void
    {
        try {
            $email = $request->email;
            $ip = $request->ip();
            
            cache()->forget("login_attempts:{$email}:{$ip}");
            cache()->forget("lockout:{$email}:{$ip}");

        } catch (\Exception $e) {
            Log::error('清除登录尝试记录异常', [
                'email' => $request->email ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * 锁定用户账户
     *
     * @param int $userId
     * @param string $reason
     * @return bool
     */
    public function lockUser(int $userId, string $reason = '管理员锁定'): bool
    {
        try {
            $userClass = config('auth.providers.users.model');
            $user = $userClass::find($userId);

            if (!$user) {
                return false;
            }

            $user->update([
                'status' => 'locked',
                'locked_reason' => $reason,
                'locked_at' => Carbon::now()
            ]);

            Log::info('用户账户被锁定', [
                'user_id' => $userId,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('锁定用户账户异常', [
                'user_id' => $userId,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 解锁用户账户
     *
     * @param int $userId
     * @param string $reason
     * @return bool
     */
    public function unlockUser(int $userId, string $reason = '管理员解锁'): bool
    {
        try {
            $userClass = config('auth.providers.users.model');
            $user = $userClass::find($userId);

            if (!$user) {
                return false;
            }

            $user->update([
                'status' => 'active',
                'locked_reason' => null,
                'locked_at' => null
            ]);

            Log::info('用户账户被解锁', [
                'user_id' => $userId,
                'reason' => $reason
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('解锁用户账户异常', [
                'user_id' => $userId,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 生成 MFA 密钥
     */
    protected function generateMfaSecret(): string
    {
        return strtoupper(Str::random(16));
    }

    /**
     * 生成二维码 URL
     */
    protected function generateQrCodeUrl($user, string $secret): string
    {
        $issuer = config('app.name', 'Laravel');
        $label = urlencode("{$issuer}:{$user->email}");
        
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}";
    }

    /**
     * 生成备用代码
     */
    protected function generateBackupCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 10; $i++) {
            $codes[] = strtoupper(Str::random(8));
        }
        return $codes;
    }

    /**
     * 验证 MFA 代码
     */
    protected function verifyMfaCode($user, string $code): bool
    {
        // 这里应该实现完整的 TOTP 验证逻辑
        // 临时返回 true，实际应该验证 TOTP
        return true;
    }
}