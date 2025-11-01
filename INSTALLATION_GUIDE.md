# Webman-Filament 安装和自动化脚本

本项目包含了一套完整的安装和自动化脚本，用于简化 Webman-Filament 的安装和配置过程。

## 📁 文件结构

```
bin/
├── install.sh          # Linux/macOS 安装脚本
├── install.bat         # Windows 安装脚本
└── setup.php           # 通用 PHP 安装脚本

src/Command/
├── SetupCommand.php    # Laravel 安装命令
└── ConfigureCommand.php # 配置命令

scripts/
└── environment-check.php # 环境检查脚本
```

## 🚀 快速开始

### 方法一：使用 Shell 脚本（Linux/macOS）

```bash
# 给脚本执行权限
chmod +x bin/install.sh

# 运行安装脚本
./bin/install.sh
```

### 方法二：使用批处理脚本（Windows）

```cmd
# 运行安装脚本
bin\install.bat
```

### 方法三：使用 PHP 脚本（跨平台）

```bash
# 运行 PHP 安装脚本
php bin/setup.php
```

### 方法四：使用 Laravel 命令

```bash
# 运行 Laravel 安装命令
php artisan webman-filament:setup

# 运行配置命令
php artisan webman-filament:configure show
php artisan webman-filament:configure database
php artisan webman-filament:configure theme
```

### 方法五：环境检查

```bash
# 检查环境是否满足要求
php scripts/environment-check.php
```

## 📋 功能特性

### 🛠️ 安装脚本功能

1. **环境检查**
   - PHP 版本检查（要求 8.1+）
   - Composer 检查
   - Node.js 和 npm 检查
   - PHP 扩展检查
   - 数据库连接检查

2. **依赖安装**
   - Composer 依赖安装
   - NPM 依赖安装（可选）
   - 前端资源构建（可选）

3. **应用配置**
   - 生成应用密钥
   - 创建 .env 文件
   - 配置数据库连接

4. **数据库设置**
   - 数据库连接测试
   - 运行数据库迁移
   - 创建默认数据

5. **Filament 配置**
   - 发布 Filament 资源
   - 配置认证系统
   - 设置主题选项

6. **权限设置**
   - 设置目录权限
   - 创建存储链接
   - 优化文件权限

7. **性能优化**
   - 清理应用缓存
   - 配置缓存优化
   - 路由缓存优化

### ⚙️ 配置命令功能

1. **显示配置**
   - 查看当前应用配置
   - 显示数据库配置
   - 显示 Filament 配置
   - 显示认证配置
   - 显示主题配置

2. **数据库配置**
   - 交互式数据库配置
   - 连接测试
   - 自动迁移

3. **认证配置**
   - 配置 Filament 认证
   - 创建管理员用户
   - 设置权限系统

4. **主题配置**
   - 品牌名称设置
   - 暗色模式切换
   - 主题颜色配置

5. **权限配置**
   - 安装权限包
   - 创建默认角色
   - 设置权限规则

6. **配置重置**
   - 重置所有配置
   - 清理缓存
   - 重新安装

### 🔍 环境检查功能

1. **系统检查**
   - PHP 版本检查
   - 操作系统检测
   - 服务器软件检测

2. **扩展检查**
   - 必需 PHP 扩展检查
   - 可选扩展检查
   - 扩展功能验证

3. **工具检查**
   - Composer 检查
   - Node.js 检查
   - NPM 检查

4. **目录检查**
   - 目录结构检查
   - 文件存在性检查
   - 权限检查

5. **资源检查**
   - 内存限制检查
   - 上传限制检查
   - 执行时间检查

6. **数据库检查**
   - 连接测试
   - 版本检查
   - 权限验证

## 📝 使用示例

### 基本安装

```bash
# 1. 检查环境
php scripts/environment-check.php

# 2. 运行安装
php bin/setup.php

# 3. 配置应用
php artisan webman-filament:configure database
php artisan webman-filament:configure theme

# 4. 创建管理员
php artisan make:filament-user
```

### 高级配置

```bash
# 显示所有配置
php artisan webman-filament:configure show

# 配置数据库（交互式）
php artisan webman-filament:configure database

# 配置主题（命令行）
php artisan webman-filament:configure theme --option=brand --value="我的应用"

# 配置权限
php artisan webman-filament:configure permissions

# 重置配置
php artisan webman-filament:configure reset
```

### 批量操作

```bash
# 强制重新安装
php artisan webman-filament:setup --force

# 跳过某些步骤
php artisan webman-filament:setup --skip-deps --skip-migrate

# 交互式安装
php bin/setup.php
```

## 🔧 自定义配置

### 环境变量

在 `.env` 文件中可以设置以下变量：

```env
# 应用配置
APP_NAME="Webman-Filament"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

# 数据库配置
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=webman_filament
DB_USERNAME=root
DB_PASSWORD=your_password

# Filament 配置
FILAMENT_BRAND="我的应用"
FILAMENT_DARK_MODE=false
FILAMENT_PRIMARY_COLOR="#4f46e5"
```

### 主题自定义

```php
// config/filament.php
return [
    'brand' => env('FILAMENT_BRAND', 'Filament'),
    'dark_mode' => env('FILAMENT_DARK_MODE', false),
    'theme' => [
        'colors' => [
            'primary' => env('FILAMENT_PRIMARY_COLOR', '#4f46e5'),
        ],
    ],
];
```

## 🐛 故障排除

### 常见问题

1. **权限问题**
   ```bash
   # Linux/macOS
   chmod -R 775 storage bootstrap/cache
   
   # Windows (以管理员身份运行)
   icacls storage /grant Users:F /T
   icacls bootstrap\cache /grant Users:F /T
   ```

2. **PHP 扩展缺失**
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php-mbstring php-xml php-zip php-gd
   
   # CentOS/RHEL
   sudo yum install php-mbstring php-xml php-zip php-gd
   ```

3. **Composer 依赖问题**
   ```bash
   # 清理缓存
   composer clear-cache
   
   # 重新安装
   composer install --no-dev --optimize-autoloader
   ```

4. **数据库连接问题**
   ```bash
   # 检查 .env 配置
   cat .env
   
   # 测试连接
   php artisan tinker
   >>> DB::connection()->getPdo();
   ```

### 日志查看

```bash
# 查看应用日志
tail -f storage/logs/laravel.log

# 查看 Webman 日志
tail -f storage/logs/webman.log

# 查看 PHP 错误日志
tail -f /var/log/php_errors.log
```

## 📚 最佳实践

1. **开发环境**
   - 使用 `php artisan serve` 启动开发服务器
   - 启用调试模式进行开发
   - 使用 SQLite 数据库进行快速测试

2. **生产环境**
   - 禁用调试模式
   - 使用 MySQL/PostgreSQL 数据库
   - 配置适当的内存限制
   - 启用 OPcache

3. **性能优化**
   - 定期清理缓存
   - 使用 Redis 进行缓存
   - 优化数据库查询
   - 启用资源压缩

4. **安全建议**
   - 定期更新依赖
   - 使用强密码
   - 配置 HTTPS
   - 设置适当的文件权限

## 🤝 贡献指南

1. Fork 项目
2. 创建特性分支
3. 提交更改
4. 推送到分支
5. 创建 Pull Request

## 📄 许可证

本项目基于 MIT 许可证开源。

## 📞 支持

如有问题或建议，请：

1. 查看文档
2. 搜索现有 Issue
3. 创建新的 Issue
4. 联系开发团队

---

**Happy Coding! 🎉**