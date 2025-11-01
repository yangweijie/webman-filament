<?php

namespace App\Adapter;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use App\Models\Role;
use App\Models\Permission;
use App\Models\User;

/**
 * 权限适配器 - 桥接 Filament 权限系统与自定义权限管理
 * 
 * 功能特性：
 * - 支持基于角色的访问控制 (RBAC)
 * - 集成 Filament Shield 权限系统
 * - 支持权限缓存优化
 * - 提供权限检查中间件
 * - 支持动态权限分配
 */
class PermissionAdapter
{
    /**
     * @var string 权限缓存键前缀
     */
    protected string $cachePrefix = 'permissions:';

    /**
     * @var int 缓存过期时间（秒）
     */
    protected int $cacheTtl = 3600; // 1小时

    /**
     * @var array 内置权限类型
     */
    protected array $permissionTypes = [
        'view' => '查看',
        'create' => '创建',
        'edit' => '编辑',
        'delete' => '删除',
        'manage' => '管理',
        'export' => '导出',
        'import' => '导入'
    ];

    /**
     * 检查用户是否具有指定权限
     *
     * @param string $permission 权限名称
     * @param Model|null $model 模型实例（可选）
     * @param string $guard Guard 名称
     * @return bool
     */
    public function can(string $permission, ?Model $model = null, string $guard = 'web'): bool
    {
        try {
            $user = $this->getCurrentUser($guard);
            
            if (!$user) {
                return false;
            }

            // 超级管理员拥有所有权限
            if ($this->isSuperAdmin($user)) {
                return true;
            }

            // 检查直接权限
            if ($this->hasDirectPermission($user, $permission, $model)) {
                return true;
            }

            // 检查角色权限
            if ($this->hasRolePermission($user, $permission, $model)) {
                return true;
            }

            return false;

        } catch (\Exception $e) {
            Log::error('权限检查异常', [
                'permission' => $permission,
                'user_id' => $user?->id ?? 'guest',
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 检查用户是否具有任意一个权限
     *
     * @param array $permissions 权限数组
     * @param Model|null $model 模型实例
     * @param string $guard Guard 名称
     * @return bool
     */
    public function canAny(array $permissions, ?Model $model = null, string $guard = 'web'): bool
    {
        foreach ($permissions as $permission) {
            if ($this->can($permission, $model, $guard)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 检查用户是否具有所有权限
     *
     * @param array $permissions 权限数组
     * @param Model|null $model 模型实例
     * @param string $guard Guard 名称
     * @return bool
     */
    public function canAll(array $permissions, ?Model $model = null, string $guard = 'web'): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->can($permission, $model, $guard)) {
                return false;
            }
        }
        return true;
    }

    /**
     * 获取用户的所有权限
     *
     * @param string $guard Guard 名称
     * @return Collection
     */
    public function getUserPermissions(string $guard = 'web'): Collection
    {
        try {
            $user = $this->getCurrentUser($guard);
            
            if (!$user) {
                return collect();
            }

            $cacheKey = $this->cachePrefix . "user:{$user->id}:{$guard}";
            
            return Cache::remember($cacheKey, $this->cacheTtl, function () use ($user) {
                return $user->getAllPermissions();
            });

        } catch (\Exception $e) {
            Log::error('获取用户权限异常', [
                'user_id' => $user?->id ?? 'guest',
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * 获取用户的角色
     *
     * @param string $guard Guard 名称
     * @return Collection
     */
    public function getUserRoles(string $guard = 'web'): Collection
    {
        try {
            $user = $this->getCurrentUser($guard);
            
            if (!$user) {
                return collect();
            }

            return $user->getRoleNames();

        } catch (\Exception $e) {
            Log::error('获取用户角色异常', [
                'user_id' => $user?->id ?? 'guest',
                'error' => $e->getMessage()
            ]);

            return collect();
        }
    }

    /**
     * 为用户分配角色
     *
     * @param int $userId 用户ID
     * @param string|array $roles 角色名称或角色ID数组
     * @param string $guard Guard 名称
     * @return bool
     */
    public function assignRole(int $userId, $roles, string $guard = 'web'): bool
    {
        try {
            $user = $this->getUserById($userId, $guard);
            
            if (!$user) {
                return false;
            }

            if (is_string($roles)) {
                $roles = [$roles];
            }

            $user->assignRole($roles);

            // 清除权限缓存
            $this->clearUserPermissionCache($userId, $guard);

            Log::info('用户角色分配成功', [
                'user_id' => $userId,
                'roles' => $roles,
                'guard' => $guard
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('分配角色异常', [
                'user_id' => $userId,
                'roles' => $roles,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 移除用户角色
     *
     * @param int $userId 用户ID
     * @param string|array $roles 角色名称或角色ID数组
     * @param string $guard Guard 名称
     * @return bool
     */
    public function removeRole(int $userId, $roles, string $guard = 'web'): bool
    {
        try {
            $user = $this->getUserById($userId, $guard);
            
            if (!$user) {
                return false;
            }

            if (is_string($roles)) {
                $roles = [$roles];
            }

            $user->removeRole($roles);

            // 清除权限缓存
            $this->clearUserPermissionCache($userId, $guard);

            Log::info('用户角色移除成功', [
                'user_id' => $userId,
                'roles' => $roles,
                'guard' => $guard
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('移除角色异常', [
                'user_id' => $userId,
                'roles' => $roles,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 为角色分配权限
     *
     * @param string $roleName 角色名称
     * @param array $permissions 权限数组
     * @return bool
     */
    public function givePermissionToRole(string $roleName, array $permissions): bool
    {
        try {
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                return false;
            }

            $role->givePermissionTo($permissions);

            // 清除所有相关用户的权限缓存
            $this->clearRolePermissionCache($roleName);

            Log::info('角色权限分配成功', [
                'role' => $roleName,
                'permissions' => $permissions
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('分配角色权限异常', [
                'role' => $roleName,
                'permissions' => $permissions,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 从角色移除权限
     *
     * @param string $roleName 角色名称
     * @param array $permissions 权限数组
     * @return bool
     */
    public function revokePermissionFromRole(string $roleName, array $permissions): bool
    {
        try {
            $role = Role::where('name', $roleName)->first();
            
            if (!$role) {
                return false;
            }

            $role->revokePermissionTo($permissions);

            // 清除所有相关用户的权限缓存
            $this->clearRolePermissionCache($roleName);

            Log::info('角色权限移除成功', [
                'role' => $roleName,
                'permissions' => $permissions
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('移除角色权限异常', [
                'role' => $roleName,
                'permissions' => $permissions,
                'error' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * 获取所有权限
     *
     * @return Collection
     */
    public function getAllPermissions(): Collection
    {
        return Permission::all();
    }

    /**
     * 获取所有角色
     *
     * @return Collection
     */
    public function getAllRoles(): Collection
    {
        return Role::all();
    }

    /**
     * 创建权限
     *
     * @param string $name 权限名称
     * @param string $description 权限描述
     * @param string|null $group 权限分组
     * @return Permission|null
     */
    public function createPermission(string $name, string $description = '', ?string $group = null): ?Permission
    {
        try {
            return Permission::create([
                'name' => $name,
                'description' => $description,
                'group' => $group
            ]);

        } catch (\Exception $e) {
            Log::error('创建权限异常', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * 创建角色
     *
     * @param string $name 角色名称
     * @param string $description 角色描述
     * @param array $permissions 权限数组
     * @return Role|null
     */
    public function createRole(string $name, string $description = '', array $permissions = []): ?Role
    {
        try {
            $role = Role::create([
                'name' => $name,
                'description' => $description
            ]);

            if (!empty($permissions)) {
                $role->givePermissionTo($permissions);
            }

            return $role;

        } catch (\Exception $e) {
            Log::error('创建角色异常', [
                'name' => $name,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * 检查用户是否是超级管理员
     */
    protected function isSuperAdmin($user): bool
    {
        $superAdmins = config('auth.super_admins', []);
        return in_array($user->email, $superAdmins);
    }

    /**
     * 检查用户是否具有直接权限
     */
    protected function hasDirectPermission($user, string $permission, ?Model $model): bool
    {
        return $user->can($permission, $model);
    }

    /**
     * 检查用户是否具有角色权限
     */
    protected function hasRolePermission($user, string $permission, ?Model $model): bool
    {
        foreach ($user->getRoleNames() as $roleName) {
            if (Gate::forUser($user)->allows($permission, $model)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 获取当前用户
     */
    protected function getCurrentUser(string $guard)
    {
        return auth($guard)->user();
    }

    /**
     * 根据ID获取用户
     */
    protected function getUserById(int $userId, string $guard)
    {
        $userClass = config('auth.providers.users.model');
        return $userClass::find($userId);
    }

    /**
     * 清除用户权限缓存
     */
    protected function clearUserPermissionCache(int $userId, string $guard): void
    {
        $cacheKey = $this->cachePrefix . "user:{$userId}:{$guard}";
        Cache::forget($cacheKey);
    }

    /**
     * 清除角色权限缓存
     */
    protected function clearRolePermissionCache(string $roleName): void
    {
        $users = User::role($roleName)->get();
        foreach ($users as $user) {
            $this->clearUserPermissionCache($user->id, 'web');
        }
    }

    /**
     * 获取权限类型
     */
    public function getPermissionTypes(): array
    {
        return $this->permissionTypes;
    }

    /**
     * 生成权限名称
     *
     * @param string $resource 资源名称
     * @param string $action 动作类型
     * @return string
     */
    public function generatePermissionName(string $resource, string $action): string
    {
        return "{$resource}.{$action}";
    }

    /**
     * 获取资源的权限列表
     *
     * @param string $resource 资源名称
     * @return array
     */
    public function getResourcePermissions(string $resource): array
    {
        $permissions = [];
        
        foreach ($this->permissionTypes as $action => $description) {
            $permissions[] = [
                'name' => $this->generatePermissionName($resource, $action),
                'action' => $action,
                'description' => $description
            ];
        }
        
        return $permissions;
    }
}