# Webman Filament 卸载指南

## 概述

本指南详细说明了如何安全、完整地卸载 Webman Filament，包括数据备份、清理步骤、回滚方案和注意事项。

## 卸载前准备

### 1. 数据备份

在卸载前，强烈建议备份重要数据：

#### 数据库备份

```bash
# MySQL 备份
mysqldump -u username -p --single-transaction --routines --triggers database_name > filament_backup_$(date +%Y%m%d_%H%M%S).sql

# PostgreSQL 备份
pg_dump -U username -h localhost database_name > filament_backup_$(date +%Y%m%d_%H%M%S).sql

# 或者使用 Laravel 备份命令
php webman backup:run
```

#### 配置文件备份

```bash
# 备份 Filament 相关配置
cp -r config/filament config/filament_backup_$(date +%Y%m%d)
cp .env .env_backup_$(date +%Y%m%d)

# 备份自定义资源文件
cp -r src/Filament src/Filament_backup_$(date +%Y%m%d)

# 备份主题文件
cp -r resources/css/filament.css resources/css/filament.css_backup_$(date +%Y%m%d) 2>/dev/null || true
```

#### 上传文件备份

```bash
# 备份用户上传的文件
tar -czf uploads_backup_$(date +%Y%m%d_%H%M%S).tar.gz public/uploads/
```

### 2. 记录当前状态

创建卸载前状态记录：

```bash
# 创建状态记录文件
cat > uninstall_status_$(date +%Y%m%d_%H%M%S).txt << EOF
=== Webman Filament 卸载前状态记录 ===
日期: $(date)
用户: $(whoami)
主机: $(hostname)

=== 安装的版本信息 ===
Webman Filament 版本: $(php webman filament:version 2>/dev/null || echo "无法获取")
PHP 版本: $(php -v | head -n 1)
Composer 版本: $(composer --version)

=== 已安装的包 ===
$(composer show | grep -E "(webman|filament)")

=== 数据库表列表 ===
$(php webman tinker --execute="DB::select('SHOW TABLES')" 2>/dev/null | grep -v "^>" || echo "无法获取表信息")

=== 路由信息 ===
$(php webman route:list | grep filament || echo "无 Filament 路由")

=== 配置文件位置 ===
config/filament/: $(ls -la config/filament/ 2>/dev/null || echo "目录不存在")
src/Filament/: $(ls -la src/Filament/ 2>/dev/null || echo "目录不存在")

=== 磁盘使用情况 ===
$(du -sh . 2>/dev/null || echo "无法获取磁盘使用情况")

EOF
```

### 3. 停止相关服务

```bash
# 停止 Webman 服务
php webman stop

# 或者如果使用 systemctl
sudo systemctl stop webman
```

## 卸载方法

### 方法一：使用卸载命令（推荐）

#### 1. 运行自动卸载

```bash
# 执行自动卸载
php webman filament:uninstall

# 交互式卸载（会询问确认）
php webman filament:uninstall --interactive

# 完全卸载（包括数据）
php webman filament:uninstall --force --remove-data
```

#### 2. 卸载过程监控

卸载命令会显示详细进度：

```
=== Webman Filament 卸载工具 ===

[1/8] 检查权限... ✅
[2/8] 备份数据... ✅
[3/8] 停止服务... ✅
[4/8] 移除路由... ✅
[5/8] 清理文件... ✅
[6/8] 卸载依赖... ✅
[7/8] 清理缓存... ✅
[8/8] 验证清理... ✅

卸载完成！所有 Filament 相关文件已清理。
```

### 方法二：手动卸载

#### 1. 清理路由配置

```php
// config/routes.php - 移除 Filament 路由
// 找到并删除以下代码块：
/*
Route::group([
    'prefix' => env('FILAMENT_PATH', 'admin'),
    'middleware' => [\WebmanFilament\Http\Middleware\FilamentAuthMiddleware::class],
], function () {
    require __DIR__ . '/vendor/webman/filament/routes/web.php';
});
*/
```

#### 2. 清理配置文件

```bash
# 移除 Filament 配置目录
rm -rf config/filament/

# 恢复原始配置（如果有备份）
# cp config/filament_backup_*/.env config/
```

#### 3. 清理服务提供者

```php
// config/services.php - 移除 Filament 服务提供者
// 从 providers 数组中移除：
// App\Providers\FilamentServiceProvider::class,
```

#### 4. 删除自定义资源

```bash
# 删除 Filament 资源目录
rm -rf src/Filament/

# 删除自定义页面
rm -f src/Filament/Resources/Pages/*.php
rm -f src/Filament/Resources/*Resource.php
```

#### 5. 清理静态资源

```bash
# 删除 Filament 静态资源
rm -rf public/vendor/filament/

# 删除主题文件
rm -f resources/css/filament.css
rm -f resources/js/filament.js
```

#### 6. 清理数据库

```bash
# 回滚 Filament 迁移
php webman migrate:rollback --path=vendor/webman/filament/database/migrations

# 或者删除特定表
php webman tinker --execute="
DB::statement('DROP TABLE IF EXISTS filament_users');
DB::statement('DROP TABLE IF EXISTS filament_personal_access_tokens');
"
```

#### 7. 卸载 Composer 依赖

```bash
# 移除 Filament 相关包
composer remove webman/filament

# 移除 Filament 核心包（如果不再需要）
composer remove filament/filament

# 清理不需要的依赖
composer autoremove

# 重新生成自动加载文件
composer dump-autoload
```

#### 8. 清理缓存

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

## 深度清理

### 1. 清理环境变量

编辑 `.env` 文件，移除 Filament 相关配置：

```env
# 移除以下行：
# FILAMENT_AUTH_GUARD=web
# FILAMENT_AUTH_PASSWORD_BROKER=users
# FILAMENT_PATH=admin
# FILAMENT_DOMAIN=
# FILAMENT_FILESYSTEM_DISK=local
```

### 2. 清理日志文件

```bash
# 清理 Filament 相关日志
rm -f storage/logs/filament.log
rm -f storage/logs/filament-*.log

# 清理 Webman 日志中的 Filament 相关条目
sed -i '/filament/d' storage/logs/webman.log
```

### 3. 清理会话数据

```bash
# 清理 Filament 相关会话
rm -f storage/framework/sessions/filament_*
rm -f storage/framework/sessions/*filament*

# 清理缓存会话
php webman session:clear
```

### 4. 清理临时文件

```bash
# 清理临时上传文件
find storage/tmp -name "*filament*" -delete

# 清理编译文件
rm -f bootstrap/cache/filament_*.php
rm -f bootstrap/cache/packages.php
rm -f bootstrap/cache/services.php

# 重新生成缓存
php webman config:cache
php webman route:cache
```

### 5. 清理数据库残留

```sql
-- 检查并清理残留的 Filament 相关数据
DELETE FROM users WHERE email LIKE '%@filament.local';
DELETE FROM password_reset_tokens WHERE email LIKE '%@filament.local';

-- 清理权限相关数据
DELETE FROM permissions WHERE name LIKE 'filament%';
DELETE FROM roles WHERE name LIKE 'filament%';

-- 清理日志数据
DELETE FROM activity_log WHERE subject_type LIKE '%Filament%';
DELETE FROM logs WHERE context LIKE '%filament%';
```

## 验证卸载

### 1. 检查文件清理

```bash
# 创建验证脚本 scripts/uninstall-verify.php
<?php

echo "=== 卸载验证检查 ===\n\n";

$checks = [
    'config/filament/' => 'Filament 配置目录',
    'src/Filament/' => 'Filament 资源目录',
    'public/vendor/filament/' => 'Filament 静态资源',
    'resources/css/filament.css' => 'Filament 主题文件',
];

foreach ($checks as $path => $description) {
    if (file_exists(base_path($path))) {
        echo "❌ {$description} 仍然存在: {$path}\n";
    } else {
        echo "✅ {$description} 已清理\n";
    }
}

// 检查 Composer 包
$installed = \Composer\InstalledVersions::isInstalled('webman/filament');
if ($installed) {
    echo "❌ Webman Filament 包仍然安装\n";
} else {
    echo "✅ Webman Filament 包已卸载\n";
}

// 检查路由
$routes = shell_exec("php webman route:list 2>/dev/null");
if (strpos($routes, 'filament') !== false) {
    echo "❌ Filament 路由仍然存在\n";
} else {
    echo "✅ Filament 路由已清理\n";
}

// 检查数据库表
try {
    $pdo = new PDO(
        "mysql:host=" . env('DB_HOST') . ";dbname=" . env('DB_DATABASE'),
        env('DB_USERNAME'),
        env('DB_PASSWORD')
    );
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    $filamentTables = array_filter($tables, function($table) {
        return strpos($table, 'filament') !== false;
    });
    
    if (empty($filamentTables)) {
        echo "✅ Filament 数据表已清理\n";
    } else {
        echo "❌ 仍有 Filament 数据表存在: " . implode(', ', $filamentTables) . "\n";
    }
} catch (Exception $e) {
    echo "⚠️ 无法检查数据库表: " . $e->getMessage() . "\n";
}

echo "\n=== 验证完成 ===\n";
```

### 2. 功能测试

```bash
# 测试 Webman 服务启动
php webman start

# 检查服务状态
php webman status

# 测试路由访问
curl -I http://localhost:8787/admin 2>/dev/null | head -n 1

# 检查错误日志
tail -n 50 storage/logs/webman.log | grep -i error
```

### 3. 性能检查

```bash
# 检查内存使用
php -r "echo '内存使用: ' . round(memory_get_usage(true)/1024/1024, 2) . ' MB' . PHP_EOL;"

# 检查磁盘使用
du -sh . | cut -f1

# 检查进程
ps aux | grep webman | grep -v grep
```

## 回滚卸载

### 自动回滚

```bash
# 如果误卸载，可以使用回滚命令
php webman filament:rollback-uninstall

# 从备份恢复
php webman filament:restore --backup=backup_20231101_120000
```

### 手动回滚

#### 1. 恢复文件

```bash
# 恢复配置文件
cp -r config/filament_backup_*/ config/filament

# 恢复环境文件
cp .env_backup_* .env

# 恢复自定义资源
cp -r src/Filament_backup_* src/Filament

# 恢复上传文件
tar -xzf uploads_backup_*.tar.gz -C public/
```

#### 2. 恢复依赖

```bash
# 重新安装 Filament
composer require webman/filament

# 重新发布资源
php webman filament:install
```

#### 3. 恢复数据库

```bash
# 从备份恢复数据库
mysql -u username -p database_name < filament_backup_*.sql

# 重新运行迁移
php webman migrate
```

## 完全清理（可选）

如果需要完全清理系统中的所有痕迹：

### 1. 清理系统级文件

```bash
# 清理 Composer 全局包（如果使用）
composer global remove webman/filament

# 清理系统缓存
sudo composer clear-cache --global

# 清理系统临时文件
sudo rm -rf /tmp/filament_*
sudo rm -rf /var/tmp/filament_*
```

### 2. 清理系统服务

```bash
# 清理 systemd 服务（如果创建了）
sudo systemctl disable webman-filament.service 2>/dev/null || true
sudo rm -f /etc/systemd/system/webman-filament.service

# 重新加载 systemd
sudo systemctl daemon-reload
```

### 3. 清理定时任务

```bash
# 检查并清理定时任务
crontab -l | grep -v filament | crontab -

# 或者删除特定任务
sudo crontab -l | grep filament | sudo crontab -r
```

### 4. 清理日志轮转

```bash
# 清理 logrotate 配置
sudo rm -f /etc/logrotate.d/webman-filament

# 清理系统日志
sudo journalctl --vacuum-time=1d --unit=webman 2>/dev/null || true
```

## 常见问题解决

### 问题 1：卸载过程中权限错误

**症状**：Permission denied 错误

**解决方案**：
```bash
# 使用 sudo 权限
sudo php webman filament:uninstall --force

# 或者修复文件权限
sudo chown -R $USER:$USER .
chmod -R 755 .
```

### 问题 2：数据库连接错误

**症状**：无法连接到数据库进行清理

**解决方案**：
```bash
# 检查数据库服务
systemctl status mysql

# 手动连接测试
mysql -u username -p -e "SHOW DATABASES;"

# 使用强制清理选项
php webman filament:uninstall --skip-database
```

### 问题 3：文件占用错误

**症状**：文件正在使用中，无法删除

**解决方案**：
```bash
# 查找占用进程
lsof +D storage/

# 停止相关进程
pkill -f webman

# 等待进程结束
sleep 5

# 重新尝试卸载
php webman filament:uninstall --retry
```

### 问题 4：依赖冲突

**症状**：Composer 依赖冲突

**解决方案**：
```bash
# 强制卸载
composer remove webman/filament --force

# 清理冲突
composer update --no-scripts

# 重新生成自动加载
composer dump-autoload --no-scripts
```

### 问题 5：残留数据

**症状**：卸载后仍有残留数据

**解决方案**：
```bash
# 运行深度清理
php webman filament:uninstall --deep-clean

# 手动清理残留
rm -rf storage/filament_*
rm -rf bootstrap/cache/filament_*
rm -rf .filament_*

# 清理数据库残留
php webman tinker --execute="
DB::statement('DROP TABLE IF EXISTS filament_users');
DB::statement('DROP TABLE IF EXISTS filament_personal_access_tokens');
DB::statement('DELETE FROM users WHERE email LIKE \"%@filament.local\"');
"
```

## 卸载清单

### 卸载前

- [ ] 备份数据库
- [ ] 备份配置文件
- [ ] 备份上传文件
- [ ] 记录当前状态
- [ ] 停止 Webman 服务

### 卸载中

- [ ] 清理路由配置
- [ ] 删除配置文件
- [ ] 移除服务提供者
- [ ] 删除自定义资源
- [ ] 清理静态资源
- [ ] 清理数据库
- [ ] 卸载 Composer 依赖
- [ ] 清理缓存

### 卸载后

- [ ] 验证文件清理
- [ ] 验证数据库清理
- [ ] 测试服务启动
- [ ] 检查错误日志
- [ ] 确认功能正常

### 完全清理（可选）

- [ ] 清理系统级文件
- [ ] 清理系统服务
- [ ] 清理定时任务
- [ ] 清理日志轮转

## 注意事项

### ⚠️ 重要提醒

1. **数据不可恢复**：卸载操作会永久删除数据，请确保已备份
2. **依赖影响**：卸载可能影响其他依赖 Filament 的功能
3. **配置丢失**：自定义配置在卸载后会丢失
4. **路由失效**：依赖 Filament 路由的功能会失效

### 🔄 替代方案

如果不想完全卸载，可以考虑：

1. **禁用功能**
   ```php
   // config/filament.php
   return [
       'enabled' => false,
   ];
   ```

2. **隐藏管理面板**
   ```php
   // 设置无效路径
   'path' => null,
   ```

3. **移除权限**
   ```php
   // 移除用户权限
   public static function canView(): bool
   {
       return false;
   }
   ```

### 📞 获取帮助

如果在卸载过程中遇到问题：

1. **查看日志**
   ```bash
   tail -f storage/logs/webman.log
   ```

2. **运行诊断**
   ```bash
   php webman filament:diagnose
   ```

3. **联系支持**
   - 提交 Issue
   - 提供详细错误信息
   - 包含卸载日志

---

**卸载完成检查**：
- [ ] 所有 Filament 文件已删除
- [ ] 数据库表已清理
- [ ] 路由配置已移除
- [ ] Composer 依赖已卸载
- [ ] 缓存已清理
- [ ] 服务可正常启动
- [ ] 无错误日志

**更新时间**: 2025-11-01  
**版本**: 1.0.0