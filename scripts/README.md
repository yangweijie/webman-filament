# Webman-Filament 扩展脚本使用指南

本文档介绍了如何使用 Webman-Filament 扩展提供的安装、配置和验证脚本。

## 脚本概述

### 1. 安装脚本 (`scripts/install.php`)
自动化安装和配置 Webman-Filament 扩展的基本环境。

**功能：**
- 检查运行环境
- 验证依赖关系
- 创建必要目录
- 复制配置文件
- 安装资源文件
- 生成缓存
- 运行数据库迁移

**使用方法：**
```bash
# 基本安装
php scripts/install.php

# 强制重新安装
php scripts/install.php --force

# 详细输出模式
php scripts/install.php --verbose

# 模拟运行（不实际执行）
php scripts/install.php --dry-run

# 显示帮助
php scripts/install.php --help
```

### 2. 配置脚本 (`scripts/configure.php`)
配置和自定义 Webman-Filament 扩展的设置。

**功能：**
- 数据库连接配置
- 管理员账户设置
- 主题和外观配置
- 其他系统设置

**使用方法：**
```bash
# 基本配置
php scripts/configure.php

# 交互式配置模式
php scripts/configure.php --interactive

# 仅配置数据库
php scripts/configure.php --database

# 仅配置管理员账户
php scripts/configure.php --admin

# 仅配置主题
php scripts/configure.php --theme

# 重置所有配置
php scripts/configure.php --reset

# 显示帮助
php scripts/configure.php --help
```

### 3. 验证脚本 (`scripts/validate.php`)
验证 Webman-Filament 扩展的安装和配置是否正确。

**功能：**
- 系统环境检查
- 依赖包验证
- 文件和目录检查
- 配置验证
- 数据库连接测试
- 服务状态检查
- 性能检查
- 安全性检查

**使用方法：**
```bash
# 基本验证
php scripts/validate.php

# 详细验证模式
php scripts/validate.php --verbose

# 快速验证（跳过耗时检查）
php scripts/validate.php --quick

# 健康检查模式
php scripts/validate.php --health

# 生成验证报告
php scripts/validate.php --report

# 显示帮助
php scripts/validate.php --help
```

## Composer 脚本命令

以下是通过 Composer 执行的便捷命令：

### 安装和配置
```bash
# 完整安装流程（安装+配置+验证）
composer run setup

# 强制重新安装流程
composer run setup:force

# 仅安装
composer run install

# 仅配置
composer run configure

# 仅验证
composer run validate
```

### 开发相关
```bash
# 开发环境准备
composer run dev

# 构建生产环境
composer run build

# 清理缓存
composer run clean

# 优化性能
composer run optimize

# 清除优化缓存
composer run optimize:clear
```

### 测试相关
```bash
# 运行测试
composer test

# 生成测试覆盖率报告
composer run test-coverage
```

## 典型使用流程

### 1. 全新安装
```bash
# 1. 安装扩展
composer run install

# 2. 配置扩展
composer run configure

# 3. 验证安装
composer run validate

# 或者一键完成
composer run setup
```

### 2. 重新配置
```bash
# 重置配置并重新配置
composer run configure -- --reset
composer run configure -- --interactive
```

### 3. 故障排查
```bash
# 详细验证
composer run validate -- --verbose

# 生成验证报告
composer run validate -- --report

# 查看日志
tail -f storage/logs/scripts.log
```

## 配置文件

### 主要配置文件
- `config/filament.php` - Filament 主要配置
- `storage/logs/scripts.log` - 脚本执行日志

### 配置项说明

#### 数据库配置
```php
'database' => [
    'host' => '127.0.0.1',
    'port' => '3306',
    'database' => 'webman_filament',
    'username' => 'root',
    'password' => '',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
]
```

#### 管理员配置
```php
'admin' => [
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => '', // 需要设置
]
```

#### 主题配置
```php
'theme' => [
    'dark_mode' => false,
    'primary_color' => '#6366f1',
    'secondary_color' => '#64748b',
    'brand_name' => 'Webman Filament',
    'logo' => null,
    'favicon' => null,
]
```

## 故障排除

### 常见问题

1. **权限问题**
   ```bash
   # 确保 storage 目录可写
   chmod -R 775 storage/
   ```

2. **依赖问题**
   ```bash
   # 重新安装依赖
   composer install
   composer run install -- --force
   ```

3. **配置问题**
   ```bash
   # 重置配置
   composer run configure -- --reset
   ```

4. **验证失败**
   ```bash
   # 查看详细错误信息
   composer run validate -- --verbose
   ```

### 日志文件位置
- 脚本执行日志：`storage/logs/scripts.log`
- 验证报告：`storage/logs/validation-report.json`

### 获取帮助
每个脚本都支持 `--help` 参数显示详细帮助信息：
```bash
php scripts/install.php --help
php scripts/configure.php --help
php scripts/validate.php --help
```

## 最佳实践

1. **首次安装**：使用 `composer run setup` 一键完成
2. **开发环境**：使用 `composer run dev` 快速准备
3. **生产部署**：使用 `composer run build` 构建优化版本
4. **定期验证**：使用 `composer run validate` 检查系统状态
5. **问题排查**：使用 `--verbose` 和 `--report` 选项获取详细信息

## 注意事项

- 确保 PHP 版本 >= 8.1
- 确保 MySQL/MariaDB 数据库可用
- 确保必要的 PHP 扩展已安装
- 在生产环境中谨慎使用 `--force` 选项
- 定期备份配置文件和数据库