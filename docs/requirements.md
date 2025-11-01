# 系统要求文档

## 概述

本文档详细说明了运行 Webman Filament 的系统要求，包括硬件、软件和依赖项要求。

## 核心要求

### PHP 要求

| 组件 | 最低版本 | 推荐版本 | 说明 |
|------|----------|----------|------|
| PHP | 8.1 | 8.2+ | Filament 需要 PHP 8.1 或更高版本 |
| Composer | 2.0 | 2.5+ | 包管理器 |
| PHP 扩展 | - | - | 必需的扩展列表 |

#### 必需 PHP 扩展

```bash
# 检查已安装的扩展
php -m | grep -E "(bcmath|ctype|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml|gd|zip|curl)"
```

| 扩展名 | 最低版本 | 说明 |
|--------|----------|------|
| bcmath | - | 数值计算 |
| ctype | - | 字符类型检查 |
| fileinfo | - | 文件信息检测 |
| json | - | JSON 处理 |
| mbstring | - | 多字节字符串处理 |
| openssl | - | 加密功能 |
| pdo | - | 数据库抽象层 |
| pdo_mysql | - | MySQL 驱动 |
| pdo_pgsql | - | PostgreSQL 驱动 |
| pdo_sqlite | - | SQLite 驱动 |
| tokenizer | - | PHP 标记器 |
| xml | - | XML 处理 |
| gd | 2.0+ | 图像处理 |
| zip | - | ZIP 压缩 |
| curl | - | HTTP 客户端 |
| intl | - | 国际化支持 |

#### PHP 配置要求

在 `php.ini` 中配置以下参数：

```ini
; 基础配置
memory_limit = 256M
max_execution_time = 60
max_input_time = 60
post_max_size = 50M
upload_max_filesize = 50M
max_file_uploads = 20

; 会话配置
session.gc_maxlifetime = 7200
session.cookie_httponly = On
session.cookie_secure = On

; OPcache 配置
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1

; 错误报告（生产环境）
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

### Webman 框架要求

| 组件 | 最低版本 | 推荐版本 | 说明 |
|------|----------|----------|------|
| Webman | 1.4.0 | 1.5.0+ | 核心框架 |
| Workerman | 4.0+ | 4.8+ | 底层网络库 |
| think-template | 2.0+ | 2.1+ | 模板引擎 |

#### 验证 Webman 版本

```bash
# 检查 Webman 版本
php webman --version

# 或者检查 composer.json
grep "workerman/webman-framework" composer.json
```

### 数据库要求

#### MySQL / MariaDB

| 组件 | 最低版本 | 推荐版本 | 说明 |
|------|----------|----------|------|
| MySQL | 5.7 | 8.0+ | 关系型数据库 |
| MariaDB | 10.2 | 10.6+ | MySQL 兼容数据库 |

**MySQL 配置建议**：

```ini
# my.cnf
[mysqld]
# 基础配置
max_connections = 200
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_flush_log_at_trx_commit = 2

# 查询缓存
query_cache_type = 1
query_cache_size = 128M
query_cache_limit = 2M

# 字符集
character-set-server = utf8mb4
collation-server = utf8mb4_unicode_ci

# 时区
default-time-zone = '+08:00'
```

#### PostgreSQL

| 组件 | 最低版本 | 推荐版本 | 说明 |
|------|----------|----------|------|
| PostgreSQL | 9.6 | 13+ | 关系型数据库 |

**PostgreSQL 配置建议**：

```ini
# postgresql.conf
# 基础配置
max_connections = 200
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB

# WAL 配置
wal_buffers = 16MB
checkpoint_completion_target = 0.9

# 字符编码
client_encoding = UTF8
server_encoding = UTF8

# 时区
timezone = 'Asia/Shanghai'
```

#### SQLite

| 组件 | 最低版本 | 推荐版本 | 说明 |
|------|----------|----------|------|
| SQLite | 3.8 | 3.35+ | 轻量级数据库 |

**注意**：SQLite 适用于开发和小规模应用，不推荐用于生产环境。

### Web 服务器要求

#### Nginx

| 组件 | 最低版本 | 推荐版本 | 说明 |
|------|----------|----------|------|
| Nginx | 1.18 | 1.20+ | 高性能 Web 服务器 |

**Nginx 配置示例**：

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/your/webman/public;
    index index.php index.html;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Gzip 压缩
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss application/javascript;

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|eot|svg)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    # PHP 处理
    location ~ \.php$ {
        fastcgi_pass unix:/tmp/php-cgi.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_param PATH_INFO $fastcgi_path_info;
        fastcgi_param PATH_TRANSLATED $document_root$fastcgi_path_info;
        fastcgi_read_timeout 300;
    }

    # Webman 路由处理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

#### Apache

| 组件 | 最低版本 | 推荐版本 | 说明 |
|------|----------|----------|------|
| Apache | 2.4 | 2.4.41+ | Web 服务器 |

**Apache 配置示例**：

```apache
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/your/webman/public

    # 安全配置
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"

    # 启用重写
    RewriteEngine On

    # 静态文件缓存
    <FilesMatch "\.(jpg|jpeg|png|gif|ico|css|js|woff|woff2|ttf|eot|svg)$">
        ExpiresActive On
        ExpiresDefault "access plus 1 year"
        Header append Cache-Control "public, immutable"
    </FilesMatch>

    # PHP 处理
    <FilesMatch "\.php$">
        SetHandler application/x-httpd-php
    </FilesMatch>

    # 路由处理
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php [QSA,L]

    # 错误日志
    ErrorLog ${APACHE_LOG_DIR}/webman_error.log
    CustomLog ${APACHE_LOG_DIR}/webman_access.log combined
</VirtualHost>
```

## 硬件要求

### 开发环境

| 资源 | 最低要求 | 推荐配置 | 说明 |
|------|----------|----------|------|
| CPU | 2 核 | 4 核+ | 并发处理 |
| 内存 | 4 GB | 8 GB+ | 运行环境和缓存 |
| 存储 | 20 GB | 50 GB+ | 代码、数据库、缓存 |
| 网络 | 10 Mbps | 100 Mbps+ | 静态资源传输 |

### 生产环境

| 资源 | 最低要求 | 推荐配置 | 说明 |
|------|----------|----------|------|
| CPU | 4 核 | 8 核+ | 高并发处理 |
| 内存 | 8 GB | 16 GB+ | 大量并发用户 |
| 存储 | 100 GB | 500 GB+ | 数据库和文件存储 |
| 网络 | 100 Mbps | 1 Gbps+ | 快速响应 |

### 云服务器推荐配置

#### 阿里云 ECS

```
推荐配置：
- 实例规格：ecs.c6.large (2 vCPU 4 GB)
- 操作系统：Alibaba Cloud Linux 3
- 系统盘：40 GB 高效云盘
- 数据盘：100 GB 高效云盘（可选）
```

#### 腾讯云 CVM

```
推荐配置：
- 实例规格：SA2.SMALL4 (2 vCPU 4 GB)
- 操作系统：TencentOS Server 3.1
- 系统盘：50 GB 高性能 SSD
- 数据盘：100 GB 高性能 SSD（可选）
```

#### AWS EC2

```
推荐配置：
- 实例类型：t3.medium (2 vCPU 4 GB)
- 操作系统：Amazon Linux 2
- 存储：30 GB gp2
- 额外存储：100 GB gp2（可选）
```

## 依赖包要求

### 核心依赖

在 `composer.json` 中检查以下依赖：

```json
{
    "require": {
        "php": "^8.1",
        "workerman/webman-framework": "^1.4.0",
        "webman/filament": "^1.0",
        "filament/filament": "^3.0",
        "laravel/framework": "^10.0"
    }
}
```

### 开发依赖

```json
{
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "squizlabs/php_codesniffer": "^3.6",
        "pestphp/pest": "^2.0"
    }
}
```

### 验证依赖安装

```bash
# 检查依赖版本
composer show | grep -E "(webman|filament|laravel)"

# 检查 PHP 扩展
php -m | sort

# 检查 Composer 插件
composer plugin:list
```

## 操作系统兼容性

### Linux 发行版

| 发行版 | 最低版本 | 推荐版本 | 说明 |
|--------|----------|----------|------|
| Ubuntu | 20.04 LTS | 22.04 LTS | 长期支持版本 |
| CentOS | 7 | 8/9 | 企业级发行版 |
| Debian | 10 | 11/12 | 稳定版本 |
| RHEL | 7 | 8/9 | 红帽企业版 |
| Alibaba Cloud Linux | 2 | 3 | 阿里云官方发行版 |
| TencentOS Server | 2 | 3 | 腾讯云官方发行版 |

### macOS

| 版本 | 最低要求 | 推荐版本 | 说明 |
|------|----------|----------|------|
| macOS | 10.15 | 12.0+ | Catalina 或更高版本 |
| Xcode Command Line Tools | - | 最新版 | 编译工具 |
| Homebrew | - | 最新版 | 包管理器 |

### Windows

| 组件 | 最低版本 | 推荐版本 | 说明 |
|------|----------|----------|------|
| Windows | 10 | 11 | 操作系统 |
| PHP | 8.1 | 8.2+ | PHP 运行时 |
| Composer | 2.0 | 2.5+ | 包管理器 |
| Git | 2.20 | 2.40+ | 版本控制 |

**Windows 注意事项**：
- 建议使用 WSL2 进行开发
- 或者使用 XAMPP/WAMP 集成环境
- 确保路径不包含中文字符

## 网络要求

### 防火墙配置

#### 入站规则

```bash
# 允许 HTTP 流量
iptables -A INPUT -p tcp --dport 80 -j ACCEPT

# 允许 HTTPS 流量
iptables -A INPUT -p tcp --dport 443 -j ACCEPT

# 允许 SSH 远程管理
iptables -A INPUT -p tcp --dport 22 -j ACCEPT

# 允许本地回环
iptables -A INPUT -i lo -j ACCEPT
```

#### 出站规则

```bash
# 允许 DNS 查询
iptables -A OUTPUT -p udp --dport 53 -j ACCEPT

# 允许 HTTP/HTTPS 请求
iptables -A OUTPUT -p tcp --dport 80 -j ACCEPT
iptables -A OUTPUT -p tcp --dport 443 -j ACCEPT

# 允许数据库连接（如果远程）
iptables -A OUTPUT -p tcp --dport 3306 -j ACCEPT
iptables -A OUTPUT -p tcp --dport 5432 -j ACCEPT
```

### 代理和缓存

#### 代理服务器配置

```nginx
# Nginx 代理配置
upstream webman_backend {
    server 127.0.0.1:8787;
    keepalive 32;
}

server {
    listen 80;
    server_name your-domain.com;

    location / {
        proxy_pass http://webman_backend;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        
        # WebSocket 支持
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
    }
}
```

#### CDN 配置

```javascript
// 静态资源 CDN 配置
const cdnConfig = {
    js: 'https://cdn.your-domain.com/js/',
    css: 'https://cdn.your-domain.com/css/',
    images: 'https://cdn.your-domain.com/images/',
    fonts: 'https://cdn.your-domain.com/fonts/'
};
```

## 性能要求

### 响应时间

| 场景 | 目标响应时间 | 最大响应时间 | 说明 |
|------|--------------|--------------|------|
| 页面加载 | < 2秒 | 5秒 | 首次访问 |
| API 调用 | < 500ms | 2秒 | 数据交互 |
| 文件上传 | < 10秒 | 30秒 | 大文件上传 |
| 数据库查询 | < 100ms | 500ms | 单次查询 |

### 并发处理

| 环境 | 并发用户数 | 每秒请求数 | 说明 |
|------|------------|------------|------|
| 开发环境 | 10-50 | 10-50 | 单用户测试 |
| 测试环境 | 100-500 | 50-200 | 功能测试 |
| 生产环境 | 1000+ | 500+ | 真实用户访问 |

### 内存使用

| 进程类型 | 基础内存 | 峰值内存 | 说明 |
|----------|----------|----------|------|
| Webman 主进程 | 32 MB | 64 MB | 核心进程 |
| Webman Worker | 16 MB | 32 MB | 工作进程 |
| PHP-FPM | 64 MB | 128 MB | PHP 进程池 |
| MySQL | 512 MB | 2 GB | 数据库服务 |

## 安全要求

### SSL/TLS 证书

```bash
# 使用 Let's Encrypt 免费证书
certbot --nginx -d your-domain.com

# 或者使用商业证书
openssl req -new -newkey rsa:2048 -nodes -keyout your-domain.com.key -out your-domain.com.csr
```

### 安全头配置

```nginx
# 安全头设置
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:;" always;
```

### 文件权限

```bash
# 设置正确的文件权限
find /path/to/webman -type f -exec chmod 644 {} \;
find /path/to/webman -type d -exec chmod 755 {} \;

# 特殊目录权限
chmod -R 775 storage/
chmod -R 775 bootstrap/cache/
chmod 600 .env

# 设置所有者
chown -R www-data:www-data /path/to/webman
```

## 环境检查脚本

创建环境检查脚本 `scripts/environment-check.php`：

```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

echo "=== Webman Filament 环境检查 ===\n\n";

// 检查 PHP 版本
echo "PHP 版本: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '8.1.0', '<')) {
    echo "❌ PHP 版本过低，需要 8.1 或更高版本\n";
} else {
    echo "✅ PHP 版本满足要求\n";
}

// 检查必需的扩展
$required_extensions = [
    'bcmath', 'ctype', 'fileinfo', 'json', 'mbstring',
    'openssl', 'pdo', 'pdo_mysql', 'tokenizer', 'xml',
    'gd', 'zip', 'curl', 'intl'
];

echo "\nPHP 扩展检查:\n";
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ {$ext}\n";
    } else {
        echo "❌ {$ext} - 缺失\n";
    }
}

// 检查 Composer 依赖
echo "\nComposer 依赖检查:\n";
$required_packages = [
    'workerman/webman-framework' => '^1.4.0',
    'filament/filament' => '^3.0',
    'laravel/framework' => '^10.0'
];

foreach ($required_packages as $package => $version) {
    $installed = \Composer\InstalledVersions::isInstalled($package);
    if ($installed) {
        $installed_version = \Composer\InstalledVersions::getVersion($package);
        echo "✅ {$package} ({$installed_version})\n";
    } else {
        echo "❌ {$package} - 未安装\n";
    }
}

// 检查文件权限
echo "\n文件权限检查:\n";
$directories = [
    'storage' => 'storage/',
    'bootstrap/cache' => 'bootstrap/cache/',
    'public' => 'public/'
];

foreach ($directories as $name => $path) {
    if (is_writable($path)) {
        echo "✅ {$name} - 可写\n";
    } else {
        echo "❌ {$name} - 不可写\n";
    }
}

// 检查数据库连接
echo "\n数据库连接检查:\n";
try {
    $pdo = new PDO(
        "mysql:host=127.0.0.1;dbname=test",
        "root",
        "",
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    echo "✅ MySQL 连接正常\n";
} catch (PDOException $e) {
    echo "❌ MySQL 连接失败: " . $e->getMessage() . "\n";
}

echo "\n=== 检查完成 ===\n";
```

## 故障排除

### 常见问题

1. **内存不足**
   ```bash
   # 增加 PHP 内存限制
   php -d memory_limit=512M script.php
   ```

2. **权限错误**
   ```bash
   # 修复权限
   sudo chown -R $USER:$USER /path/to/project
   chmod -R 755 /path/to/project
   ```

3. **扩展缺失**
   ```bash
   # Ubuntu/Debian
   sudo apt-get install php8.2-mysql php8.2-gd php8.2-zip

   # CentOS/RHEL
   sudo yum install php-mysql php-gd php-zip
   ```

4. **端口占用**
   ```bash
   # 检查端口占用
   netstat -tulpn | grep :8787

   # 杀死占用进程
   sudo kill -9 <PID>
   ```

### 性能监控

```bash
# 安装性能监控工具
composer require --dev blackfire/blackfire-symfony-meta

# 性能分析
blackfire curl http://localhost:8787/admin
```

---

**检查清单**：
- [ ] PHP 版本 ≥ 8.1
- [ ] 所有必需扩展已安装
- [ ] Composer 依赖已安装
- [ ] 数据库服务运行正常
- [ ] Web 服务器配置正确
- [ ] 文件权限设置正确
- [ ] 防火墙规则配置
- [ ] SSL 证书已配置

**更新时间**: 2025-11-01  
**版本**: 1.0.0