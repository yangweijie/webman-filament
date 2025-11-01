# Webman Filament 升级指南

## 概述

本指南详细说明了如何安全地升级 Webman Filament 到新版本，包括版本兼容性、升级步骤、回滚方案和注意事项。

## 版本兼容性

### 支持的升级路径

| 当前版本 | 目标版本 | 支持状态 | 升级复杂度 |
|----------|----------|----------|------------|
| 1.0.x | 1.1.x | ✅ 支持 | 简单 |
| 1.1.x | 1.2.x | ✅ 支持 | 中等 |
| 1.0.x | 1.2.x | ✅ 支持 | 复杂 |
| < 1.0 | 1.x.x | ⚠️ 需要特殊处理 | 困难 |

### 破坏性变更

#### 版本 1.2.x 主要变更

1. **配置格式变更**
   - 配置文件结构重新设计
   - 环境变量前缀变更：`FILAMENT_` → `WEBMAN_FILAMENT_`

2. **API 变更**
   - 某些方法签名变更
   - 类命名空间调整

3. **数据库变更**
   - 新增索引字段
   - 表结构优化

#### 版本 1.1.x 主要变更

1. **路由变更**
   - 路由前缀默认变更
   - 中间件重新组织

2. **依赖更新**
   - Filament 版本要求更新
   - PHP 版本要求提升

## 升级前准备

### 1. 备份数据

#### 数据库备份

```bash
# MySQL 备份
mysqldump -u username -p --single-transaction --routines --triggers database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# PostgreSQL 备份
pg_dump -U username -h localhost database_name > backup_$(date +%Y%m%d_%H%M%S).sql

# 或者使用 Laravel 备份命令
php webman backup:run
```

#### 文件备份

```bash
# 备份配置文件
cp -r config/filament config/filament.backup
cp .env .env.backup

# 备份上传文件
tar -czf uploads_backup_$(date +%Y%m%d_%H%M%S).tar.gz public/uploads/

# 备份自定义资源
cp -r src/Filament src/Filament.backup

# 备份自定义主题
cp -r resources/css/filament.css resources/css/filament.css.backup
```

#### 版本控制

```bash
# 创建升级分支
git checkout -b upgrade-to-v1.2.0

# 提交当前状态
git add .
git commit -m "升级前备份 - v1.1.0"
```

### 2. 检查当前版本

```bash
# 检查 Webman Filament 版本
php webman filament:version

# 检查依赖版本
composer show | grep -E "(webman|filament)"

# 检查 PHP 版本
php -v
```

### 3. 环境检查

运行升级前检查脚本：

```php
<?php
// scripts/upgrade-check.php

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== 升级前环境检查 ===\n\n";

// 检查当前版本
$currentVersion = \Composer\InstalledVersions::getVersion('webman/filament');
echo "当前版本: {$currentVersion}\n";

// 检查目标版本
echo "目标版本: 1.2.0\n";

// 检查 PHP 版本
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    echo "❌ PHP 版本过低: " . PHP_VERSION . "\n";
    echo "需要升级到 PHP 8.1 或更高版本\n";
} else {
    echo "✅ PHP 版本: " . PHP_VERSION . "\n";
}

// 检查磁盘空间
$freeSpace = disk_free_space('.') / (1024 * 1024 * 1024);
echo "可用磁盘空间: " . round($freeSpace, 2) . " GB\n";
if ($freeSpace < 1) {
    echo "⚠️ 磁盘空间不足，建议清理后继续\n";
}

// 检查数据库连接
try {
    $pdo = new PDO(
        "mysql:host=" . env('DB_HOST', '127.0.0.1') . ";dbname=" . env('DB_DATABASE'),
        env('DB_USERNAME'),
        env('DB_PASSWORD'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ 数据库连接正常\n";
} catch (PDOException $e) {
    echo "❌ 数据库连接失败: " . $e->getMessage() . "\n";
}

echo "\n=== 检查完成 ===\n";
```

## 升级步骤

### 方法一：自动升级（推荐）

#### 1. 运行升级命令

```bash
# 执行自动升级
php webman filament:upgrade

# 或者指定版本
php webman filament:upgrade --to=1.2.0

# 预览模式（不执行实际升级）
php webman filament:upgrade --dry-run
```

#### 2. 升级过程监控

升级命令会显示详细进度：

```
=== Webman Filament 升级工具 ===
当前版本: 1.1.5
目标版本: 1.2.0

[1/6] 检查兼容性... ✅
[2/6] 备份数据... ✅
[3/6] 更新依赖... ✅
[4/6] 运行迁移... ✅
[5/6] 更新配置... ✅
[6/6] 清理缓存... ✅

升级完成！新版本: 1.2.0
```

### 方法二：手动升级

#### 1. 更新依赖

```bash
# 更新 composer 依赖
composer update webman/filament

# 或者指定版本
composer require webman/filament:^1.2.0

# 更新 Filament 核心
composer update filament/filament
```

#### 2. 运行迁移

```bash
# 检查待执行的迁移
php webman migrate:status

# 运行迁移
php webman migrate

# 如果需要强制执行
php webman migrate --force
```

#### 3. 更新配置文件

```bash
# 发布新配置文件
php webman vendor:publish --provider="WebmanFilamentServiceProvider" --tag="filament-config"

# 手动合并配置（如果有自定义配置）
```

#### 4. 更新路由

```bash
# 发布新路由文件
php webman vendor:publish --provider="WebmanFilamentServiceProvider" --tag="filament-routes"

# 检查路由是否正确
php webman route:list | grep filament
```

#### 5. 更新资源文件

```bash
# 发布静态资源
php webman filament:assets:publish

# 更新主题文件
php webman filament:theme:publish
```

#### 6. 清理缓存

```bash
# 清理所有缓存
php webman cache:clear
php webman config:clear
php webman route:clear
php webman view:clear

# 重新生成缓存
php webman config:cache
php webman route:cache
```

## 版本特定升级说明

### 升级到 1.2.0

#### 配置变更

**旧配置格式** (`config/filament.php`)：

```php
return [
    'auth' => [
        'guard' => env('FILAMENT_AUTH_GUARD', 'web'),
        'passwords' => env('FILAMENT_AUTH_PASSWORD_BROKER', 'users'),
    ],
    'path' => env('FILAMENT_PATH', 'admin'),
];
```

**新配置格式**：

```php
return [
    'auth' => [
        'guard' => env('WEBMAN_FILAMENT_AUTH_GUARD', 'web'),
        'passwords' => env('WEBMAN_FILAMENT_AUTH_PASSWORD_BROKER', 'users'),
    ],
    'path' => env('WEBMAN_FILAMENT_PATH', 'admin'),
    'domain' => env('WEBMAN_FILAMENT_DOMAIN', null),
    'ssl' => env('WEBMAN_FILAMENT_SSL', false),
];
```

#### 环境变量更新

在 `.env` 文件中更新前缀：

```env
# 旧版本
FILAMENT_AUTH_GUARD=web
FILAMENT_PATH=admin

# 新版本
WEBMAN_FILAMENT_AUTH_GUARD=web
WEBMAN_FILAMENT_PATH=admin
WEBMAN_FILAMENT_SSL=true
```

#### 数据库迁移

新增字段：

```sql
-- 为 articles 表添加新字段
ALTER TABLE articles ADD COLUMN featured_image VARCHAR(255) NULL;
ALTER TABLE articles ADD COLUMN view_count INT DEFAULT 0;
ALTER TABLE articles ADD INDEX idx_view_count (view_count);

-- 为 users 表添加字段
ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN login_count INT DEFAULT 0;
```

#### 代码迁移

**1. 更新命名空间**

```php
// 旧版本
use WebmanFilament\Resources\BaseResource;

// 新版本
use WebmanFilament\Resources\Resource;
```

**2. 更新方法调用**

```php
// 旧版本
public static function getNavigationLabel(): string
{
    return '文章管理';
}

// 新版本
protected static ?string $navigationLabel = '文章管理';
```

**3. 更新表单组件**

```php
// 旧版本
Forms\Components\TextInput::make('title')
    ->label('标题')
    ->required()
    ->maxLength(255);

// 新版本（添加了更多选项）
Forms\Components\TextInput::make('title')
    ->label('标题')
    ->required()
    ->maxLength(255)
    ->live(onBlur: true)
    ->afterStateUpdated(fn (string $operation, $state, Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),
```

### 升级到 1.1.0

#### 路由变更

**旧路由配置**：

```php
Route::group([
    'prefix' => 'admin',
    'middleware' => ['auth'],
], function () {
    require __DIR__ . '/vendor/webman/filament/routes/web.php';
});
```

**新路由配置**：

```php
Route::group([
    'prefix' => env('FILAMENT_PATH', 'admin'),
    'middleware' => [\WebmanFilament\Http\Middleware\FilamentAuthMiddleware::class],
], function () {
    require __DIR__ . '/vendor/webman/filament/routes/web.php';
});
```

#### 中间件更新

更新中间件类名：

```php
// 旧版本
use WebmanFilament\Middleware\AuthMiddleware;

// 新版本
use WebmanFilament\Http\Middleware\FilamentAuthMiddleware;
```

## 升级后验证

### 1. 功能测试

```bash
# 检查升级后的版本
php webman filament:version

# 测试管理面板访问
curl -I http://localhost:8787/admin

# 检查路由
php webman route:list | grep filament
```

### 2. 功能验证清单

- [ ] 管理员登录功能正常
- [ ] 仪表板显示正确
- [ ] 资源列表页面可访问
- [ ] 创建/编辑资源功能正常
- [ ] 文件上传功能正常
- [ ] 搜索和筛选功能正常
- [ ] 权限控制正常工作
- [ ] 邮件通知功能正常

### 3. 性能测试

```bash
# 使用 Apache Bench 测试
ab -n 100 -c 10 http://localhost:8787/admin

# 使用 wrk 测试
wrk -t12 -c400 -d30s http://localhost:8787/admin
```

### 4. 数据库完整性检查

```sql
-- 检查表结构
DESCRIBE articles;
DESCRIBE users;

-- 检查索引
SHOW INDEX FROM articles;
SHOW INDEX FROM users;

-- 检查数据完整性
SELECT COUNT(*) FROM articles;
SELECT COUNT(*) FROM users;
```

## 回滚方案

### 自动回滚

```bash
# 如果升级失败，可以自动回滚
php webman filament:rollback

# 回滚到指定版本
php webman filament:rollback --to=1.1.0
```

### 手动回滚

#### 1. 恢复数据库

```bash
# 从备份恢复数据库
mysql -u username -p database_name < backup_20231101_120000.sql

# 或者使用 PostgreSQL
psql -U username -d database_name < backup_20231101_120000.sql
```

#### 2. 恢复文件

```bash
# 恢复配置文件
rm -rf config/filament
mv config/filament.backup config/filament

# 恢复环境文件
cp .env.backup .env

# 恢复自定义资源
rm -rf src/Filament
mv src/Filament.backup src/Filament

# 恢复上传文件
rm -rf public/uploads/*
tar -xzf uploads_backup_20231101_120000.tar.gz -C public/
```

#### 3. 恢复依赖版本

```bash
# 恢复 composer 依赖
composer require webman/filament:^1.1.0

# 清理并重新安装
composer install --no-dev --optimize-autoloader
```

#### 4. 清理缓存

```bash
php webman cache:clear
php webman config:clear
php webman route:clear
php webman view:clear
```

## 常见问题解决

### 问题 1：升级后白屏

**症状**：访问管理面板显示白屏

**解决方案**：
```bash
# 检查 PHP 错误日志
tail -f /var/log/php_errors.log

# 检查 Webman 日志
tail -f storage/logs/webman.log

# 清理缓存
php webman cache:clear
php webman config:clear

# 检查文件权限
chmod -R 755 storage/
chmod -R 755 bootstrap/cache/
```

### 问题 2：数据库连接错误

**症状**：升级后数据库连接失败

**解决方案**：
```bash
# 检查数据库服务状态
systemctl status mysql

# 重新运行迁移
php webman migrate:install
php webman migrate

# 检查数据库配置
php webman tinker
DB::connection()->getPdo();
```

### 问题 3：路由不工作

**症状**：404 Not Found 错误

**解决方案**：
```bash
# 重新发布路由
php webman vendor:publish --provider="WebmanFilamentServiceProvider" --tag="filament-routes"

# 清理路由缓存
php webman route:clear

# 重启 Webman
php webman restart
```

### 问题 4：样式丢失

**症状**：CSS/JS 文件加载失败

**解决方案**：
```bash
# 重新发布资源文件
php webman filament:assets:publish

# 检查文件权限
chmod -R 755 public/vendor/filament/

# 清理浏览器缓存
```

### 问题 5：权限错误

**症状**：403 Forbidden 错误

**解决方案**：
```bash
# 检查文件所有者
ls -la storage/

# 修复文件权限
chown -R www-data:www-data storage/
chmod -R 775 storage/

# 检查 SELinux 上下文（如果启用）
restorecon -R storage/
```

## 性能优化升级

### 1. 启用 OPcache

升级后建议启用 OPcache：

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2
```

### 2. 优化数据库

```sql
-- 为新字段添加索引
ALTER TABLE articles ADD INDEX idx_featured_image (featured_image);
ALTER TABLE users ADD INDEX idx_last_login (last_login_at);

-- 分析表结构
ANALYZE TABLE articles, users;
```

### 3. 配置缓存

```php
// config/filament.php
return [
    'cache' => [
        'enabled' => env('WEBMAN_FILAMENT_CACHE_ENABLED', true),
        'ttl' => env('WEBMAN_FILAMENT_CACHE_TTL', 3600),
    ],
];
```

## 监控和维护

### 1. 升级后监控

```bash
# 创建监控脚本 scripts/post-upgrade-monitor.php
<?php

echo "=== 升级后监控 ===\n\n";

// 检查服务状态
$services = ['mysql', 'nginx', 'webman'];
foreach ($services as $service) {
    $status = shell_exec("systemctl is-active $service");
    echo $service . ": " . trim($status) . "\n";
}

// 检查磁盘使用
$diskUsage = disk_free_space('.') / (1024 * 1024 * 1024);
echo "可用磁盘空间: " . round($diskUsage, 2) . " GB\n";

// 检查内存使用
$memoryUsage = memory_get_usage(true) / (1024 * 1024);
echo "内存使用: " . round($memoryUsage, 2) . " MB\n";

// 检查错误日志
$errorLog = storage_path('logs/webman.log');
if (file_exists($errorLog)) {
    $recentErrors = shell_exec("tail -n 50 $errorLog | grep -i error");
    echo "最近错误:\n" . $recentErrors;
}

echo "\n=== 监控完成 ===\n";
```

### 2. 定期维护

```bash
# 创建维护脚本 scripts/maintenance.php
<?php

echo "=== 系统维护 ===\n\n";

// 清理日志文件
$logFiles = glob(storage_path('logs/*.log'));
foreach ($logFiles as $file) {
    if (filemtime($file) < strtotime('-30 days')) {
        unlink($file);
        echo "删除旧日志: " . basename($file) . "\n";
    }
}

// 优化数据库
try {
    $pdo = new PDO(
        "mysql:host=" . env('DB_HOST') . ";dbname=" . env('DB_DATABASE'),
        env('DB_USERNAME'),
        env('DB_PASSWORD')
    );
    $pdo->exec("OPTIMIZE TABLE articles, users");
    echo "数据库优化完成\n";
} catch (PDOException $e) {
    echo "数据库优化失败: " . $e->getMessage() . "\n";
}

// 清理缓存
shell_exec("php " . base_path('webman') . " cache:clear");
echo "缓存清理完成\n";

echo "\n=== 维护完成 ===\n";
```

## 升级检查清单

### 升级前

- [ ] 备份数据库
- [ ] 备份配置文件
- [ ] 备份上传文件
- [ ] 检查磁盘空间
- [ ] 检查 PHP 版本
- [ ] 检查依赖兼容性
- [ ] 创建升级分支

### 升级中

- [ ] 运行升级命令
- [ ] 执行数据库迁移
- [ ] 更新配置文件
- [ ] 更新路由配置
- [ ] 发布静态资源
- [ ] 清理缓存

### 升级后

- [ ] 验证版本号
- [ ] 测试登录功能
- [ ] 测试核心功能
- [ ] 检查错误日志
- [ ] 性能测试
- [ ] 监控服务状态

### 回滚准备

- [ ] 保留备份文件
- [ ] 记录回滚步骤
- [ ] 测试回滚流程
- [ ] 准备紧急联系方式

## 支持与帮助

如果在升级过程中遇到问题：

1. **查看错误日志**
   ```bash
   tail -f storage/logs/webman.log
   ```

2. **运行诊断命令**
   ```bash
   php webman filament:diagnose
   ```

3. **联系技术支持**
   - 提交 Issue 到项目仓库
   - 提供详细的错误信息
   - 包含系统环境信息

4. **社区支持**
   - 访问官方论坛
   - 加入用户群组
   - 查看升级经验分享

---

**重要提醒**：
- ⚠️ 升级前务必备份数据
- ⚠️ 在测试环境先验证升级流程
- ⚠️ 保留回滚方案
- ⚠️ 升级后进行充分测试

**更新时间**: 2025-11-01  
**版本**: 1.2.0