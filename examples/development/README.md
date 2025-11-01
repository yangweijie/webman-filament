# Webman Filament 开发环境配置

这个目录包含了 Webman Filament 的开发环境配置示例，专注于开发效率和调试便利性。

## 🛠️ 开发环境特性

- **热重载**: 代码修改后自动重载
- **调试工具**: 详细的错误信息和调试面板
- **性能监控**: 实时性能指标监控
- **SQL 日志**: 数据库查询日志记录
- **文件监控**: 自动检测文件变化
- **开发工具**: 内置调试和开发工具

## 🚀 快速开始

### 1. 环境准备

确保你的系统已安装：
- PHP 8.1+ (推荐 8.2)
- MySQL 5.7+ 或 PostgreSQL 9.6+
- Redis 6+ (可选)
- Composer
- Node.js 16+ (用于前端资源编译)

### 2. 安装依赖

```bash
# 安装 PHP 依赖
composer install

# 安装前端依赖
npm install

# 安装开发工具（可选）
composer require --dev laravel/telescope
composer require --dev clockworkapp/clockwork
```

### 3. 配置环境

```bash
# 复制环境配置
cp .env.development .env

# 生成应用密钥
php artisan key:generate

# 安装 Telescope（可选）
php artisan telescope:install
php artisan migrate
```

### 4. 配置数据库

编辑 `.env` 文件，设置数据库连接信息：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webman_filament_dev
DB_USERNAME=root
DB_PASSWORD=password
```

### 5. 启动服务

```bash
# 启动 Webman 服务
php start.php start

# 或使用开发模式（带文件监控）
php start.php start -d
```

访问 http://localhost:8787 查看开发环境首页。

## 🔧 开发工具

### 内置开发工具

访问以下 URL 使用内置开发工具：

- **首页**: http://localhost:8787/
- **管理后台**: http://localhost:8787/admin
- **健康检查**: http://localhost:8787/api/health
- **系统状态**: http://localhost:8787/api/status
- **调试信息**: http://localhost:8787/api/debug
- **开发工具**: http://localhost:8787/dev/

### 开发工具详情

#### 调试信息 (/dev/debug)
- PHP 版本和配置
- 内存使用情况
- 已加载的文件和扩展
- 环境变量
- 服务器信息

#### 路由列表 (/dev/routes)
- 所有已注册路由
- 路由方法和路径
- 路由处理程序

#### 数据库状态 (/dev/database)
- 数据库连接状态
- 表列表
- 表记录数量

#### 缓存状态 (/dev/cache)
- 缓存驱动配置
- 缓存存储信息

#### 性能分析 (/dev/performance)
- 内存使用统计
- 执行时间分析
- 系统负载信息

### 外部开发工具

#### Laravel Telescope
```bash
# 安装 Telescope
composer require --dev laravel/telescope
php artisan telescope:install
php artisan migrate

# 访问 Telescope
# http://localhost:8787/telescope
```

#### Clockwork
```bash
# 安装 Clockwork
composer require --dev clockworkapp/clockwork

# 在浏览器中安装 Clockwork 扩展
# 访问任何页面，Clockwork 会自动显示调试信息
```

## 📝 开发工作流

### 1. 创建资源

```bash
# 创建 Filament 资源
php artisan make:filament-resource Post --generate

# 创建页面
php artisan make:filament-page Settings

# 创建组件
php artisan make:filament-widget StatsWidget

# 创建表单组件
php artisan make:filament-form-component ProductForm
```

### 2. 创建模型和迁移

```bash
# 创建模型
php artisan make:model Product -m

# 创建工厂
php artisan make:factory ProductFactory

# 创建Seeder
php artisan make:seeder ProductSeeder
```

### 3. 运行测试

```bash
# 运行所有测试
php artisan test

# 运行特定测试
php artisan test --filter=ProductTest

# 生成测试覆盖率报告
php artisan test --coverage
```

### 4. 代码质量检查

```bash
# PHP 代码规范检查
./vendor/bin/phpcs

# 自动修复代码规范
./vendor/bin/phpcbf

# 静态分析
./vendor/bin/phpstan analyse
```

## 🔄 热重载配置

### 文件监控

开发环境会自动监控以下文件变化：
- `app/Filament/**/*.php`
- `config/filament.php`
- `config/auth.php`
- `resources/views/filament/**/*.php`

### 自定义监控路径

在 `.env` 中配置：

```env
FILAMENT_HOT_RELOAD_WATCH_PATHS=app/Filament,config,resources/views/filament
```

### 监控间隔

```env
FILE_WATCHER_INTERVAL=1000  # 毫秒
```

## 📊 性能监控

### 实时性能指标

开发环境会显示以下性能指标：
- 内存使用情况
- 执行时间
- 数据库查询数量
- 缓存命中率
- 文件包含数量

### 慢查询监控

```env
DB_LOG_SLOW_QUERIES=true
DB_SLOW_QUERY_THRESHOLD=100  # 毫秒
```

### 性能分析

启用 XHProf 进行性能分析：

```bash
# 安装 XHProf 扩展
pecl install xhprof

# 在 php.ini 中启用
extension=xhprof.so
xhprof.output_dir=/tmp/xhprof
```

## 🐛 调试配置

### 错误显示

开发环境会显示详细的错误信息：
- 错误堆栈跟踪
- 相关代码片段
- 环境变量
- 服务器信息

### SQL 查询日志

启用 SQL 查询日志：

```env
FILAMENT_SHOW_SQL_QUERIES=true
```

### 调试面板

在页面底部会显示调试面板，包含：
- 执行时间
- 内存使用
- 数据库查询
- 缓存操作

## 📚 模拟数据

### 生成模拟数据

```bash
# 生成工厂文件
php artisan make:factory UserFactory

# 运行 Seeder
php artisan db:seed

# 填充特定表
php artisan db:seed --class=UserSeeder
```

### 批量生成数据

在 `.env` 中配置：

```env
MOCK_DATA_ENABLED=true
MOCK_DATA_COUNT=100
```

## 🔌 开发插件

### 可用的开发插件

- **Debug Plugin**: 调试信息显示
- **Routes Plugin**: 路由管理
- **Database Plugin**: 数据库管理
- **Performance Plugin**: 性能监控

### 启用插件

```env
DEVELOPMENT_PLUGINS_ENABLED=true
DEVELOPMENT_DEBUG_PLUGIN_ENABLED=true
DEVELOPMENT_ROUTES_PLUGIN_ENABLED=true
DEVELOPMENT_DATABASE_PLUGIN_ENABLED=true
```

## 📖 API 文档

### 自动生成 API 文档

```bash
# 安装 API 文档生成器
composer require --dev darkaonline/l5-swagger

# 生成文档
php artisan l5-swagger:generate

# 访问文档
# http://localhost:8787/api/documentation
```

### 手动创建文档

在控制器中添加注释：

```php
/**
 * @OA\Get(
 *     path="/api/users",
 *     summary="获取用户列表",
 *     tags={"Users"},
 *     @OA\Response(response=200, description="成功")
 * )
 */
public function index()
{
    //
}
```

## 🚨 故障排除

### 常见问题

1. **端口占用**
   ```bash
   # 检查端口占用
   lsof -i :8787
   
   # 修改端口
   php start.php start -p 8788
   ```

2. **权限问题**
   ```bash
   # 设置权限
   chmod -R 775 storage
   chmod -R 775 bootstrap/cache
   ```

3. **内存不足**
   ```bash
   # 增加内存限制
   ini_set('memory_limit', '2G');
   ```

4. **数据库连接失败**
   ```bash
   # 检查数据库服务
   sudo systemctl status mysql
   
   # 测试连接
   mysql -u root -p
   ```

### 调试技巧

1. **启用详细日志**
   ```env
   LOG_LEVEL=debug
   FILAMENT_LOG_LEVEL=debug
   ```

2. **查看日志文件**
   ```bash
   tail -f storage/logs/laravel.log
   tail -f storage/logs/php_errors.log
   ```

3. **使用 Xdebug**
   ```bash
   # 安装 Xdebug
   pecl install xdebug
   
   # 配置 php.ini
   zend_extension=xdebug.so
   xdebug.mode=debug
   xdebug.start_with_request=yes
   ```

## 📝 开发规范

### 代码规范

遵循以下代码规范：
- PSR-12 代码规范
- Laravel 编码约定
- Filament 最佳实践

### 提交规范

使用语义化提交信息：
- `feat:` 新功能
- `fix:` 修复 bug
- `docs:` 文档更新
- `style:` 代码格式
- `refactor:` 代码重构
- `test:` 测试相关
- `chore:` 构建过程或辅助工具的变动

### 分支管理

- `main`: 主分支
- `develop`: 开发分支
- `feature/*`: 功能分支
- `hotfix/*`: 修复分支
- `release/*`: 发布分支

这个开发环境配置提供了完整的开发工具和调试功能，帮助开发者高效地进行 Webman Filament 应用的开发工作。