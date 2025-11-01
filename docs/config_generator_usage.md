# Webman Filament 配置生成器

## 概述

配置生成器是一个强大的工具，用于自动生成和管理 Webman Filament 项目的配置文件。它支持交互式配置向导、默认值设置、配置验证等功能。

## 文件结构

```
src/Generator/
├── ConfigGenerator.php       # 主配置生成器类
├── EnvironmentGenerator.php  # 环境变量生成器
└── DatabaseGenerator.php     # 数据库配置生成器

templates/
├── .env.template             # 环境变量模板
└── config/
    ├── filament.php.template # Filament 配置模板
    └── database.php.template # 数据库配置模板
```

## 功能特性

### 1. 主配置生成器 (ConfigGenerator)

- **统一接口**: 提供统一的配置生成接口
- **交互式向导**: 支持交互式配置向导
- **配置验证**: 内置配置验证功能
- **模板管理**: 支持多种配置模板

#### 主要方法

```php
// 生成所有配置
$generator = new ConfigGenerator();
$results = $generator->generateAll($options);

// 交互式配置向导
$results = $generator->interactiveWizard($options);

// 验证配置
$validation = $generator->validateConfig($config);

// 获取模板信息
$template = $generator->getTemplate('environment');
```

### 2. 环境变量生成器 (EnvironmentGenerator)

- **模板替换**: 支持模板变量替换
- **默认值**: 提供合理的默认值
- **交互式配置**: 支持交互式配置
- **文件管理**: 支持.env文件的加载和保存

#### 主要方法

```php
$envGenerator = new EnvironmentGenerator();

// 生成环境变量配置
$results = $envGenerator->generate($options);

// 交互式配置
$options = $envGenerator->interactiveConfig();

// 验证配置
$validation = $envGenerator->validate($config);

// 从文件加载配置
$config = $envGenerator->loadFromFile('.env');

// 保存配置到文件
$envGenerator->saveToFile($config, '.env');
```

### 3. 数据库配置生成器 (DatabaseGenerator)

- **多数据库支持**: 支持 MySQL、PostgreSQL、SQLite、SQL Server
- **连接测试**: 支持数据库连接测试
- **交互式配置**: 支持交互式数据库配置
- **连接池配置**: 支持连接池参数配置

#### 主要方法

```php
$dbGenerator = new DatabaseGenerator();

// 生成数据库配置
$results = $dbGenerator->generate($options);

// 交互式配置
$options = $dbGenerator->interactiveConfig();

// 测试数据库连接
$testResult = $dbGenerator->testConnection($config);

// 验证配置
$validation = $dbGenerator->validate($config);
```

## 使用方法

### 1. 基本使用

```php
use WebmanFilament\Generator\ConfigGenerator;

// 创建生成器实例
$generator = new ConfigGenerator();

// 生成所有配置
$results = $generator->generateAll();

// 检查生成结果
if ($results['success']) {
    echo "配置生成成功！\n";
    echo "环境变量: " . $results['environment']['file'] . "\n";
    echo "数据库配置: " . $results['database']['file'] . "\n";
    echo "Filament配置: " . $results['filament']['file'] . "\n";
} else {
    echo "配置生成失败: " . $results['message'] . "\n";
}
```

### 2. 交互式配置向导

```php
use WebmanFilament\Generator\ConfigGenerator;

$generator = new ConfigGenerator();
$results = $generator->interactiveWizard();
```

### 3. 自定义配置

```php
use WebmanFilament\Generator\ConfigGenerator;

$generator = new ConfigGenerator();

// 自定义配置选项
$options = [
    'environment' => [
        'APP_NAME' => 'My Webman App',
        'APP_URL' => 'https://myapp.com',
        'DB_HOST' => 'localhost',
        'DB_DATABASE' => 'myapp_db',
        'DB_USERNAME' => 'myapp_user',
        'DB_PASSWORD' => 'secure_password'
    ],
    'filament' => [
        'app_name' => 'My Webman App',
        'url' => 'https://myapp.com',
        'enable_dark_mode' => true,
        'enable_notifications' => true
    ]
];

$results = $generator->generateAll($options);
```

### 4. 仅生成特定配置

```php
use WebmanFilament\Generator\ConfigGenerator;

$generator = new ConfigGenerator();

// 仅生成环境变量
$envResult = $generator->generateEnvironment([
    'APP_NAME' => 'My App',
    'APP_URL' => 'https://myapp.com'
]);

// 仅生成数据库配置
$dbResult = $generator->generateDatabase([
    'default' => 'mysql',
    'connections' => [
        'mysql' => [
            'host' => 'localhost',
            'database' => 'myapp_db',
            'username' => 'root',
            'password' => 'password'
        ]
    ]
]);

// 仅生成Filament配置
$filamentResult = $generator->generateFilament([
    'app_name' => 'My App',
    'url' => 'https://myapp.com'
]);
```

## 配置模板

### 环境变量模板 (.env.template)

包含完整的应用环境变量配置，包括：

- 应用基本配置 (APP_NAME, APP_ENV, APP_URL 等)
- 数据库配置 (MySQL, PostgreSQL, SQLite, SQL Server)
- Filament 配置选项
- 缓存和会话配置
- 邮件和日志配置
- Redis 配置
- 安全配置
- 第三方服务配置

### 数据库配置模板 (database.php.template)

包含完整的数据库配置文件，包括：

- 默认连接配置
- 多种数据库驱动支持
- 连接池配置
- Redis 配置
- 缓存配置
- 会话配置
- 队列配置
- 广播配置
- 文件存储配置
- 邮件配置
- 日志配置

### Filament 配置模板 (filament.php.template)

包含完整的 Filament 配置文件，包括：

- 应用信息配置
- 品牌和主题配置
- 功能开关配置
- 页面和资源配置
- 通知和小组件配置
- 认证和权限配置
- 文件上传配置
- 性能优化配置

## 配置验证

所有生成器都内置了配置验证功能：

```php
$generator = new ConfigGenerator();
$config = [
    'environment' => [...],
    'database' => [...],
    'filament' => [...]
];

$validation = $generator->validateConfig($config);

if ($validation['valid']) {
    echo "配置验证通过";
} else {
    echo "配置验证失败:";
    foreach ($validation['errors'] as $error) {
        echo "- $error\n";
    }
}
```

## 错误处理

所有方法都返回标准的结果格式：

```php
[
    'success' => true/false,
    'message' => '成功或错误消息',
    'file' => '生成的文件路径（可选）',
    'errors' => ['错误列表（验证失败时）']
]
```

## 最佳实践

1. **使用交互式向导**: 对于首次配置，建议使用交互式向导
2. **备份现有配置**: 生成器会自动备份现有的配置文件
3. **验证配置**: 生成配置后务必验证配置的正确性
4. **测试连接**: 对于数据库配置，建议测试连接
5. **安全考虑**: 生产环境中确保敏感信息的安全

## 故障排除

### 常见问题

1. **模板文件不存在**
   - 检查模板文件路径是否正确
   - 确保模板文件有读取权限

2. **配置验证失败**
   - 检查必需字段是否填写
   - 验证数据格式是否正确
   - 查看错误消息详情

3. **文件写入失败**
   - 检查目标目录是否存在
   - 确保有写入权限
   - 检查磁盘空间

4. **数据库连接失败**
   - 验证数据库服务是否运行
   - 检查连接参数是否正确
   - 确认防火墙设置

### 调试模式

启用调试模式以获取详细错误信息：

```php
$generator = new ConfigGenerator();
$generator->setDebug(true);
$results = $generator->generateAll();
```

## 扩展开发

配置生成器采用模块化设计，可以轻松扩展：

1. **添加新的生成器**: 继承基础生成器类
2. **添加新的模板**: 创建新的模板文件
3. **添加新的验证规则**: 扩展验证方法
4. **添加新的交互方式**: 扩展交互配置方法

## 贡献

欢迎提交问题和改进建议！

## 许可证

本项目遵循 MIT 许可证。