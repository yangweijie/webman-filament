# Webman Filament 安装指南

## 概述

本指南将详细介绍如何在不同环境中安装和配置 Webman Filament。Webman Filament 是一个为 Webman 框架提供 Filament 管理面板支持的扩展包。

## 系统要求

在开始安装之前，请确保您的系统满足以下要求：

- **PHP 版本**: 8.1 或更高版本
- **Webman 框架**: 1.4.0 或更高版本
- **Composer**: 2.0 或更高版本
- **数据库**: MySQL 5.7+、PostgreSQL 9.6+ 或 SQLite 3.8+
- **Web 服务器**: Nginx 1.18+ 或 Apache 2.4+

详细要求请参考 [系统要求文档](requirements.md)。

## 安装方法

### 方法一：通过 Composer 安装（推荐）

#### 1. 安装扩展包

```bash
# 进入您的 Webman 项目目录
cd /path/to/your/webman-project

# 安装 Webman Filament
composer require webman/filament

# 或者指定版本
composer require webman/filament:^1.0
```

#### 2. 发布资源文件

```bash
# 发布 Filament 资源
php webman filament:install

# 或者使用 publish 命令
php webman vendor:publish --provider="WebmanFilamentServiceProvider"
```

#### 3. 运行数据库迁移

```bash
# 运行迁移
php webman migrate

# 如果需要创建管理员用户
php webman filament:user
```

### 方法二：手动安装

#### 1. 下载并放置文件

```bash
# 创建目录结构
mkdir -p config/filament
mkdir -p src/Providers
mkdir -p resources/views/vendor/filament

# 复制配置文件
cp vendor/webman/filament/config/filament.php config/filament/

# 复制服务提供者
cp vendor/webman/filament/src/Providers/FilamentServiceProvider.php src/Providers/
```

#### 2. 注册服务提供者

在 `config/services.php` 中添加：

```php
return [
    // ... 其他配置
    
    'providers' => [
        // ... 其他服务提供者
        App\Providers\FilamentServiceProvider::class,
    ],
];
```

#### 3. 配置路由

在 `config/routes.php` 中添加 Filament 路由：

```php
use WebmanFilament\Http\Middleware\FilamentAuthMiddleware;

Route::group([
    'prefix' => 'admin',
    'middleware' => [FilamentAuthMiddleware::class],
], function () {
    require __DIR__ . '/vendor/webman/filament/routes/web.php';
});
```

## 配置步骤

### 1. 环境配置

创建或编辑 `.env` 文件：

```env
# 数据库配置
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Filament 配置
FILAMENT_AUTH_GUARD=web
FILAMENT_AUTH_PASSWORD_BROKER=users
FILAMENT_FILESYSTEM_DISK=local

# 文件上传配置
FILESYSTEM_DISK=local
```

### 2. 配置文件

#### 基本配置

编辑 `config/filament.php`：

```php
<?php

return [
    'domain' => env('FILAMENT_DOMAIN', null),
    'path' => env('FILAMENT_PATH', 'admin'),
    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
        'passwords' => env('FILAMENT_AUTH_PASSWORD_BROKER', 'users'),
    ],
    'pages' => [
        'dashboard' => \Filament\Pages\Dashboard::class,
    ],
    'resources' => [
        'namespace' => 'App\\Filament\\Resources',
        'path' => app_path('Filament/Resources'),
    ],
    'widgets' => [
        'namespace' => 'App\\Filament\\Widgets',
        'path' => app_path('Filament/Widgets'),
    ],
];
```

#### 身份验证配置

确保您的身份验证配置正确：

```php
// config/auth.php
return [
    'guards' => [
        'web' => [
            'driver' => 'session',
            'provider' => 'users',
        ],
    ],
    'providers' => [
        'users' => [
            'driver' => 'eloquent',
            'model' => App\Models\User::class,
        ],
    ],
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => 'password_reset_tokens',
            'expire' => 60,
            'throttle' => 60,
        ],
    ],
];
```

### 3. 创建管理员用户

#### 通过命令行创建

```bash
# 创建管理员用户
php webman filament:user

# 或者使用 Tinker
php webman tinker
```

在 Tinker 中执行：

```php
$user = new App\Models\User();
$user->name = 'Admin User';
$user->email = 'admin@example.com';
$user->password = Hash::make('password');
$user->email_verified_at = now();
$user->save();
```

#### 通过数据库直接创建

```sql
INSERT INTO users (name, email, password, email_verified_at, created_at, updated_at)
VALUES ('Admin User', 'admin@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', now(), now(), now());
```

## 验证安装

### 1. 检查服务状态

```bash
# 检查 Webman 服务
php webman status

# 检查路由
php webman route:list | grep filament
```

### 2. 访问管理面板

在浏览器中访问：`http://your-domain.com/admin`

默认登录凭据：
- 邮箱：admin@example.com
- 密码：password

### 3. 验证功能

- [ ] 登录功能正常
- [ ] 仪表板显示正常
- [ ] 资源管理页面可访问
- [ ] 文件上传功能正常
- [ ] 用户权限管理正常

## 常见问题解决

### 问题 1：权限错误

**症状**: 403 Forbidden 错误

**解决方案**:
```bash
# 设置正确的文件权限
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
chown -R www-data:www-data storage/
```

### 问题 2：数据库连接错误

**症状**: 数据库连接失败

**解决方案**:
1. 检查 `.env` 文件中的数据库配置
2. 确认数据库服务正在运行
3. 验证数据库用户权限

```bash
# 测试数据库连接
php webman tinker
DB::connection()->getPdo();
```

### 问题 3：路由不工作

**症状**: 404 Not Found 错误

**解决方案**:
1. 检查路由配置是否正确
2. 重启 Webman 服务
3. 清除路由缓存

```bash
# 清除缓存
php webman route:clear
php webman config:clear
php webman cache:clear

# 重启服务
php webman restart
```

### 问题 4：静态资源加载失败

**症状**: CSS/JS 文件 404 错误

**解决方案**:
```bash
# 发布静态资源
php webman filament:assets

# 或者手动复制
cp -r vendor/filament/filament/dist public/vendor/filament/
```

## 性能优化

### 1. 启用缓存

```php
// config/filament.php
return [
    'cache' => [
        'enabled' => env('FILAMENT_CACHE_ENABLED', true),
    ],
];
```

### 2. 优化数据库

```sql
-- 为常用字段添加索引
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_created_at ON users(created_at);
```

### 3. 启用 OPcache

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
```

## 安全配置

### 1. 设置强密码策略

```php
// config/filament.php
return [
    'auth' => [
        'passwords' => [
            'users' => [
                'provider' => 'users',
                'table' => 'password_reset_tokens',
                'expire' => 60,
                'throttle' => 3, // 3次失败后锁定60秒
            ],
        ],
    ],
];
```

### 2. 启用 HTTPS

确保在生产环境中使用 HTTPS：

```nginx
# Nginx 配置
server {
    listen 443 ssl http2;
    server_name your-domain.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # 其他配置...
}

server {
    listen 80;
    server_name your-domain.com;
    return 301 https://$server_name$request_uri;
}
```

### 3. 设置防火墙规则

```bash
# 限制管理面板访问
iptables -A INPUT -p tcp --dport 80 -s 192.168.1.0/24 -j ACCEPT
iptables -A INPUT -p tcp --dport 80 -j DROP
```

## 备份与恢复

### 1. 数据备份

```bash
# 备份数据库
mysqldump -u username -p database_name > backup.sql

# 备份文件
tar -czf files_backup.tar.gz storage/ public/uploads/
```

### 2. 配置备份

```bash
# 备份配置文件
cp config/filament.php config/filament.php.backup
cp .env .env.backup
```

### 3. 恢复数据

```bash
# 恢复数据库
mysql -u username -p database_name < backup.sql

# 恢复文件
tar -xzf files_backup.tar.gz
```

## 支持与帮助

如果遇到安装问题，请：

1. 查看 [常见问题解答](installation-guide.md#常见问题解决)
2. 检查 [系统要求](requirements.md)
3. 访问项目仓库提交 Issue
4. 查看官方文档

## 下一步

安装完成后，建议您：

1. 阅读 [快速开始指南](quick-start.md)
2. 配置自定义资源
3. 设置用户权限
4. 定制主题样式
5. 配置邮件通知

---

**更新时间**: 2025-11-01  
**版本**: 1.0.0