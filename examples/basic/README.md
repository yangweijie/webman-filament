# Webman Filament 基础配置示例

这个目录包含了 Webman Filament 集成的基础配置示例，适合快速上手和开发测试。

## 文件说明

- `.env.example` - 环境变量配置模板
- `config/app.php` - 应用基础配置
- `config/database.php` - 数据库配置
- `start.php` - Webman 启动配置

## 快速开始

### 1. 环境准备

确保你的系统已安装：
- PHP 8.1+
- MySQL 5.7+ 或 PostgreSQL 9.6+
- Composer
- Node.js 16+ (用于前端资源编译)

### 2. 安装依赖

```bash
# 安装 PHP 依赖
composer install

# 安装前端依赖
npm install
```

### 3. 配置环境

```bash
# 复制环境配置
cp .env.example .env

# 生成应用密钥
php artisan key:generate
```

### 4. 配置数据库

编辑 `.env` 文件，设置数据库连接信息：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webman_filament
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. 运行迁移

```bash
# 运行数据库迁移
php artisan migrate

# 可选：填充示例数据
php artisan db:seed
```

### 6. 安装 Filament 资源

```bash
# 发布 Filament 资源
php artisan filament:install

# 构建前端资源
npm run build
```

### 7. 启动服务

```bash
# 启动 Webman 服务
php start.php start
```

访问 http://localhost:8787/admin 查看管理后台。

## 配置说明

### 应用配置

- `APP_NAME` - 应用名称
- `APP_ENV` - 应用环境 (local/development/production)
- `APP_DEBUG` - 调试模式
- `APP_URL` - 应用 URL

### Filament 配置

- `FILAMENT_PANEL_ID` - 面板 ID
- `FILAMENT_PANEL_PATH` - 面板路径
- `FILAMENT_AUTH_GUARD` - 认证守护者

### 数据库配置

支持 MySQL、PostgreSQL、SQLite 和 SQL Server。

## 开发指南

### 创建资源

```bash
# 创建 Filament 资源
php artisan make:filament-resource User

# 创建页面
php artisan make:filament-page Settings

# 创建组件
php artisan make:filament-widget StatsWidget
```

### 自定义配置

你可以根据需要修改 `config/filament.php` 和 `config/auth.php` 配置文件。

### 路由自定义

在 `start.php` 中可以添加自定义路由：

```php
Route::get('/custom', function() {
    return 'Custom Route';
});
```

## 故障排除

### 常见问题

1. **端口占用**：默认端口 8787，如被占用请修改 `start.php` 中的配置
2. **权限问题**：确保 storage 和 bootstrap/cache 目录有写入权限
3. **数据库连接**：检查数据库服务是否运行，连接信息是否正确

### 日志查看

- Webman 日志：`storage/logs/webman.log`
- Laravel 日志：`storage/logs/laravel.log`

### 性能优化

- 启用 OPcache：`opcache.enable=1`
- 使用 Redis 缓存
- 启用静态资源缓存

## 下一步

- 查看 [开发环境配置](../development/) 了解开发环境设置
- 查看 [生产环境配置](../production/) 了解生产环境部署
- 查看 [Docker 配置](../docker/) 了解容器化部署