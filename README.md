# webman-filament 扩展

基于适配器模式的 webman + Filament 集成扩展

## 概述

webman-filament 扩展实现了将 Filament 后台面板集成到 webman 框架中的完整解决方案。该扩展采用适配器模式，确保在常驻内存环境下保持 Filament 的完整功能。

## 核心特性

- **适配器模式设计**: 通过桥接适配器、请求响应适配器、服务容器适配层实现无缝集成
- **生命周期桥接**: 在 webman 启动、重载、停止事件中管理 Filament 面板
- **路由与中间件桥接**: 将 Laravel 中间件栈与 webman 洋葱模型对齐
- **性能优化**: 利用 webman 的常驻内存优势，提升后台管理性能
- **完整功能支持**: 支持面板、资源、表单、表格、动作、通知等所有 Filament 组件

## 文件结构

```
webman-filament/
├── composer.json                    # 扩展包配置
├── src/
│   ├── WebmanFilamentServiceProvider.php  # 核心服务提供者
│   ├── Bridge/
│   │   └── FilamentBridge.php       # Filament 桥接器主类
│   ├── Adapter/
│   │   └── RequestResponseAdapter.php     # 请求响应适配器
│   └── Support/                     # 支持类库
├── config/
│   └── filament.php                 # 扩展配置文件
└── public/                          # 静态资源目录
```

## 安装

1. 将扩展文件放置到项目中
2. 安装依赖：
   ```bash
   composer install
   ```
3. 发布配置文件：
   ```bash
   php webman install:filament
   ```
4. 运行迁移：
   ```bash
   php webman filament:migrate
   ```

## 配置

在 `config/filament.php` 中配置扩展：

```php
return [
    'auto_register_routes' => true,
    'panels' => [
        'admin' => [
            'id' => 'admin',
            'path' => 'admin',
            'title' => 'Admin Panel',
            'middleware' => ['web', 'auth'],
        ],
    ],
    // ... 更多配置选项
];
```

## 使用方法

### 1. 创建面板提供者

```php
<?php

namespace App\Filament;

use Filament\Panel;
use Filament\PanelProvider;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->title('Admin Panel')
            ->middleware(['web', 'auth']);
    }
}
```

### 2. 注册服务提供者

在 `config/filament.php` 中注册面板提供者：

```php
'panel_providers' => [
    'admin' => \App\Filament\AdminPanelProvider::class,
],
```

### 3. 创建资源

```php
<?php

namespace App\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Forms;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),
            ]);
    }
    
    public static function table(Tables\Table $table): Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('email'),
            ]);
    }
}
```

## 核心组件

### FilamentBridge (桥接器)

负责在 webman 生命周期与 Filament 之间建立桥接：

- `onStart()`: webman 启动时初始化 Filament
- `onReload()`: webman 重载时重新注册面板和插件
- `onStop()`: webman 停止时清理资源

### RequestResponseAdapter (请求响应适配器)

负责在 webman Request/Response 与 Laravel Illuminate Request/Response 之间转换：

- `convertWebmanRequestToIlluminate()`: 转换 webman 请求到 Laravel 请求
- `convertIlluminateResponseToWebman()`: 转换 Laravel 响应到 webman 响应
- `handleJsonRequestResponse()`: 处理 JSON 请求响应

### WebmanFilamentServiceProvider (服务提供者)

扩展的核心服务提供者，负责：

- 注册适配器服务
- 初始化 Filament 环境
- 管理生命周期事件
- 配置面板和插件

## 性能优化

### 1. 启用缓存

```php
'cache' => [
    'enabled' => true,
    'driver' => 'file',
    'ttl' => 3600,
],
```

### 2. 配置连接池

```php
'connection_pool' => [
    'enabled' => true,
    'min_connections' => 2,
    'max_connections' => 10,
],
```

### 3. 启用 OPcache

确保 PHP 配置中启用 OPcache 以获得最佳性能。

## 故障排除

### 1. 路由未找到

检查 `config/filament.php` 中的 `auto_register_routes` 设置。

### 2. 认证问题

确认中间件配置正确：

```php
'middleware' => ['web', 'auth'],
```

### 3. 静态资源加载失败

运行资产安装命令：

```bash
composer run-script install-assets
```

## 开发指南

### 添加新适配器

1. 在 `src/Adapter/` 目录下创建新适配器类
2. 实现相应的接口
3. 在服务提供者中注册适配器

### 扩展生命周期

在桥接器中添加新的生命周期事件：

```php
public function onCustomEvent(): void
{
    // 自定义事件处理逻辑
}
```

## 许可证

MIT License

## 贡献

欢迎提交 Issue 和 Pull Request！