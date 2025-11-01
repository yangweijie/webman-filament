# Webman Filament 使用示例和文档

本目录包含了 webman-filament 扩展的完整使用示例和详细文档，帮助您快速上手并深入了解各项功能。

## 📁 文档目录

### 核心文档

- **[基础使用指南](docs/basic-usage.md)** - 快速上手指南，包含安装、配置、创建第一个资源等基础内容
- **[高级功能指南](docs/advanced-features.md)** - 深入了解高级特性，包括自定义组件、复杂关系处理、性能优化等
- **[自定义开发指南](docs/customization.md)** - 深度定制开发指南，涵盖适配器、桥接器、中间件等核心组件开发
- **[API 参考文档](docs/api-reference.md)** - 完整的 API 参考，包含所有类、方法、参数和返回值说明
- **[最佳实践指南](docs/best-practices.md)** - 性能优化、安全配置、开发规范、部署建议等最佳实践

## 📂 示例代码目录

### 基础示例 (`basic/`)

适合快速上手和开发测试的基础配置：

```
basic/
├── app/
│   ├── Models/
│   │   ├── User.php              # 用户模型示例
│   │   ├── Article.php           # 文章模型示例
│   │   ├── Category.php          # 分类模型示例
│   │   └── Tag.php               # 标签模型示例
│   └── Filament/
│       └── Resources/
│           └── UserResource.php  # 用户资源完整示例
├── config/
│   ├── app.php                   # 应用基础配置
│   └── database.php              # 数据库配置
├── start.php                     # Webman 启动配置
└── README.md                     # 基础示例说明
```

**包含特性：**
- 用户管理系统（角色、权限、状态管理）
- 文章管理系统（状态、分类、标签、SEO）
- 完整的 Filament 资源示例
- 表单、表格、过滤器、批量操作
- 权限控制和行级安全

### 高级示例 (`advanced/`)

展示复杂业务场景和高级功能：

```
advanced/
├── app/
│   └── Filament/
│       └── Resources/
│           └── ArticleResource.php  # 高级文章资源示例
└── README.md                        # 高级示例说明
```

**包含特性：**
- 复杂的富文本编辑器
- 图片上传和处理
- 多对多关系管理
- 自定义操作和动作
- 高级过滤器和查询
- SEO 设置和元数据管理
- 性能优化和缓存策略

### 开发环境配置 (`development/`)

开发环境专用配置，包含调试工具和开发辅助功能。

### 生产环境配置 (`production/`)

生产环境优化配置，包含性能调优和安全设置。

### Docker 配置 (`docker/`)

容器化部署配置，支持开发、测试、生产环境。

### Nginx 配置 (`nginx/`)

Nginx 反向代理和负载均衡配置。

## 🚀 快速开始

### 1. 环境准备

确保您的系统已安装：
- PHP 8.1+
- MySQL 5.7+ 或 PostgreSQL 9.6+
- Composer
- Node.js 16+

### 2. 安装扩展

```bash
# 安装依赖
composer install

# 运行安装脚本
composer run-script install

# 配置环境
composer run-script configure

# 安装静态资源
composer run-script install-assets

# 运行数据库迁移
composer run-script migrate
```

### 3. 启动服务

```bash
# 启动 Webman 服务
php start.php start

# 开发模式下启动（支持热重载）
php start.php start -d
```

### 4. 访问管理面板

启动成功后，访问 `http://localhost:8787/admin` 即可进入 Filament 管理面板。

## 📖 学习路径

### 初学者路径

1. **阅读基础文档**
   - [基础使用指南](docs/basic-usage.md)
   - 查看 `basic/` 示例代码

2. **实践操作**
   - 创建简单的用户管理资源
   - 配置基础权限系统
   - 实现基本的 CRUD 操作

### 进阶路径

1. **深入了解高级功能**
   - [高级功能指南](docs/advanced-features.md)
   - 查看 `advanced/` 示例代码

2. **性能优化**
   - [最佳实践指南 - 性能优化部分](docs/best-practices.md#性能优化最佳实践)

3. **复杂业务场景**
   - 多对多关系处理
   - 自定义组件开发
   - 实时功能集成

### 开发者路径

1. **深度定制开发**
   - [自定义开发指南](docs/customization.md)
   - [API 参考文档](docs/api-reference.md)

2. **架构理解**
   - 适配器模式实现
   - 桥接器生命周期管理
   - 中间件开发

3. **生产部署**
   - [最佳实践指南 - 部署部分](docs/best-practices.md#部署最佳实践)
   - 查看 `production/` 和 `docker/` 配置

## 🔧 常用命令

### 开发命令

```bash
# 安装和配置
composer run-script install
composer run-script configure

# 清理和优化
composer run-script clean
composer run-script optimize

# 开发环境
composer run-script dev

# 构建生产版本
composer run-script build
```

### Filament 特定命令

```bash
# 创建资源
php artisan make:filament-resource User

# 创建页面
php artisan make:filament-page Dashboard

# 创建组件
php artisan make:filament-widget StatsWidget

# 安装资源
php artisan filament:install

# 清理缓存
php artisan filament:clear-cache
```

## 📋 示例代码说明

### UserResource 特性

- **完整的用户管理功能**
  - 用户创建、编辑、删除
  - 角色权限管理
  - 状态控制（激活/停用）
  - 头像上传

- **高级表格功能**
  - 自定义列显示
  - 多种过滤器
  - 批量操作
  - 导出功能

- **安全控制**
  - 权限检查
  - 行级安全
  - 操作确认

### ArticleResource 特性

- **内容管理系统**
  - 富文本编辑
  - 图片上传
  - 分类和标签
  - SEO 设置

- **发布管理**
  - 草稿/发布状态
  - 定时发布
  - 浏览量统计
  - 阅读时间计算

- **高级功能**
  - 文章复制
  - 批量操作
  - 权限控制
  - 性能优化

## 🛠️ 自定义开发

### 创建自定义资源

```bash
php artisan make:filament-resource YourResource
```

### 创建自定义组件

```php
// 自定义表单组件
class CustomComponent extends ViewComponent
{
    public function render()
    {
        return view('filament.components.custom');
    }
}
```

### 添加自定义中间件

```php
// 自定义认证中间件
class CustomAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 认证逻辑
        return $next($request);
    }
}
```

## 🔍 调试和故障排除

### 启用调试模式

```php
// .env
APP_DEBUG=true
FILAMENT_DEBUG=true
```

### 查看日志

- Webman 日志：`storage/logs/webman.log`
- Laravel 日志：`storage/logs/laravel.log`
- Filament 日志：`storage/logs/filament.log`

### 性能监控

```php
// 启用性能监控
'monitoring' => [
    'enabled' => true,
    'log_slow_queries' => true,
    'slow_query_threshold' => 1000,
],
```

## 📊 性能优化建议

### 1. 数据库优化

- 使用适当的索引
- 启用查询缓存
- 使用分页而非一次性加载
- 预加载关联数据

### 2. 缓存策略

- 启用配置缓存
- 使用 Redis 缓存
- 缓存静态资源
- 启用 OPcache

### 3. 内存优化

- 使用批量操作
- 及时释放大对象
- 监控内存使用
- 优化图片资源

## 🔒 安全最佳实践

### 1. 认证授权

- 实施行级安全
- 验证用户权限
- 使用 CSRF 保护
- 限制文件上传

### 2. 数据验证

- 严格验证输入
- 使用参数化查询
- 防止 SQL 注入
- 验证文件类型

### 3. 监控日志

- 记录关键操作
- 监控异常访问
- 设置告警机制
- 定期安全审计

## 🤝 贡献指南

欢迎提交 Issue 和 Pull Request！

### 提交规范

1. 提交前请测试代码
2. 遵循现有的代码风格
3. 添加必要的文档和注释
4. 确保向后兼容性

### 报告问题

请在 GitHub Issues 中报告问题，包含：
- 详细的错误描述
- 重现步骤
- 环境信息
- 相关日志

## 📄 许可证

MIT License

## 📞 支持

- **文档**: 查看本目录下的文档文件
- **示例**: 参考 `basic/` 和 `advanced/` 目录的示例代码
- **API**: 参考 [API 参考文档](docs/api-reference.md)
- **社区**: 参与 GitHub 讨论

---

**祝您使用愉快！** 🎉