# Webman Filament 部署指南

本指南详细介绍了如何在不同环境中部署 Webman Filament 应用，包括开发、测试和生产环境。

## 📋 目录

- [环境要求](#环境要求)
- [快速部署](#快速部署)
- [开发环境部署](#开发环境部署)
- [生产环境部署](#生产环境部署)
- [Docker 部署](#docker-部署)
- [Nginx 配置](#nginx-配置)
- [数据库配置](#数据库配置)
- [SSL 配置](#ssl-配置)
- [监控和日志](#监控和日志)
- [备份和恢复](#备份和恢复)
- [故障排除](#故障排除)
- [性能优化](#性能优化)
- [安全配置](#安全配置)

## 🖥️ 环境要求

### 最低要求

- **操作系统**: Linux (Ubuntu 20.04+ / CentOS 7+ / RHEL 8+)
- **PHP**: 8.1+ (推荐 8.2)
- **数据库**: MySQL 5.7+ / PostgreSQL 9.6+
- **Web 服务器**: Nginx 1.18+ / Apache 2.4+
- **内存**: 最少 1GB RAM
- **磁盘**: 最少 10GB 可用空间

### 推荐配置

- **操作系统**: Ubuntu 22.04 LTS
- **PHP**: 8.2
- **数据库**: MySQL 8.0 / PostgreSQL 13+
- **Web 服务器**: Nginx 1.20+
- **内存**: 4GB+ RAM
- **磁盘**: 50GB+ SSD

### PHP 扩展要求

```bash
# 必需扩展
php8.2-cli
php8.2-fpm
php8.2-mysql
php8.2-xml
php8.2-curl
php8.2-zip
php8.2-mbstring
php8.2-bcmath
php8.2-gd
php8.2-intl
php8.2-opcache

# 可选扩展
php8.2-redis
php8.2-imagick
php8.2-swoole
php8.2-xdebug
```

## 🚀 快速部署

### 1. 克隆项目

```bash
git clone https://github.com/your-org/webman-filament.git
cd webman-filament
```

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

```bash
# 编辑 .env 文件，设置数据库连接
vim .env

# 运行迁移
php artisan migrate
```

### 5. 启动服务

```bash
# 启动 Webman 服务
php start.php start

# 或使用开发模式
php start.php start -d
```

访问 http://localhost:8787 查看应用。

## 🛠️ 开发环境部署

### 1. 系统准备

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.2-fpm php8.2-mysql php8.2-redis \
    php8.2-xml php8.2-curl php8.2-zip php8.2-mbstring \
    php8.2-bcmath php8.2-gd php8.2-intl php8.2-opcache \
    mysql-server redis-server nginx

# CentOS/RHEL
sudo yum install php82 php82-php-fpm php82-php-mysqlnd \
    php82-php-redis php82-php-xml php82-php-curl \
    php82-php-zip php82-php-mbstring php82-php-bcmath \
    php82-php-gd php82-php-intl php82-php-opcache \
    mysql-server redis nginx
```

### 2. 配置开发环境

```bash
# 复制开发环境配置
cp examples/development/.env.development .env

# 安装开发工具
composer require --dev laravel/telescope
composer require --dev clockworkapp/clockwork

# 安装 Telescope
php artisan telescope:install
php artisan migrate
```

### 3. 配置数据库

```bash
# 启动服务
sudo systemctl start mysql
sudo systemctl start redis
sudo systemctl start nginx

# 创建数据库
mysql -u root -p
CREATE DATABASE webman_filament_dev CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'webman_dev'@'localhost' IDENTIFIED BY 'dev_password';
GRANT ALL PRIVILEGES ON webman_filament_dev.* TO 'webman_dev'@'localhost';
FLUSH PRIVILEGES;

# 更新 .env 文件
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=webman_filament_dev
DB_USERNAME=webman_dev
DB_PASSWORD=dev_password
```

### 4. 启动开发服务

```bash
# 启动 Webman
php start.php start

# 或使用 Docker 开发环境
docker-compose -f examples/docker/docker-compose.yml up -d
```

## 🏭 生产环境部署

### 1. 系统准备

```bash
# 更新系统
sudo apt update && sudo apt upgrade -y

# 安装必需软件
sudo apt install -y software-properties-common apt-transport-https \
    ca-certificates curl gnupg lsb-release

# 添加 PHP 仓库
sudo add-apt-repository ppa:ondrej/php
sudo apt update

# 安装 PHP 和扩展
sudo apt install -y php8.2-fpm php8.2-mysql php8.2-redis \
    php8.2-xml php8.2-curl php8.2-zip php8.2-mbstring \
    php8.2-bcmath php8.2-gd php8.2-intl php8.2-opcache \
    php8.2-imagick php8.2-swoole

# 安装数据库
sudo apt install -y mysql-server redis-server

# 安装 Nginx
sudo apt install -y nginx
```

### 2. PHP 配置优化

编辑 `/etc/php/8.2/fpm/php.ini`:

```ini
# 内存限制
memory_limit = 512M

# 执行时间限制
max_execution_time = 300
max_input_time = 300

# 文件上传限制
upload_max_filesize = 50M
post_max_size = 50M
max_file_uploads = 20

# OPcache 配置
opcache.enable = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1
opcache.save_comments = 0
opcache.validate_timestamps = 0

# 会话配置
session.save_handler = redis
session.save_path = "tcp://127.0.0.1:6379?database=2"
session.gc_maxlifetime = 7200

# 错误报告
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
```

### 3. 应用部署

```bash
# 克隆代码
git clone https://github.com/your-org/webman-filament.git /var/www/webman-filament
cd /var/www/webman-filament

# 安装依赖
composer install --no-dev --optimize-autoloader

# 安装前端依赖并构建
npm install
npm run build

# 复制生产环境配置
cp examples/production/.env.production .env

# 编辑环境配置
vim .env
```

### 4. 数据库配置

```bash
# 安全安装 MySQL
sudo mysql_secure_installation

# 创建数据库和用户
mysql -u root -p
CREATE DATABASE webman_filament_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'webman_prod'@'localhost' IDENTIFIED BY 'secure_production_password';
GRANT ALL PRIVILEGES ON webman_filament_prod.* TO 'webman_prod'@'localhost';
FLUSH PRIVILEGES;

# 运行迁移
php artisan migrate --force

# 可选：填充数据
php artisan db:seed --force
```

### 5. 权限设置

```bash
# 设置目录权限
sudo chown -R www-data:www-data /var/www/webman-filament
sudo chmod -R 755 /var/www/webman-filament
sudo chmod -R 775 /var/www/webman-filament/storage
sudo chmod -R 775 /var/www/webman-filament/bootstrap/cache

# 创建日志目录
sudo mkdir -p /var/log/webman-filament
sudo chown www-data:www-data /var/log/webman-filament
```

### 6. Nginx 配置

```bash
# 复制 Nginx 配置
sudo cp examples/nginx/nginx.conf /etc/nginx/nginx.conf
sudo cp examples/nginx/conf.d/webman-filament.conf /etc/nginx/conf.d/

# 编辑域名配置
sudo vim /etc/nginx/conf.d/webman-filament.conf
# 修改 server_name 为你的域名

# 测试配置
sudo nginx -t

# 重启 Nginx
sudo systemctl reload nginx
```

### 7. SSL 证书配置

```bash
# 安装 Certbot
sudo apt install certbot python3-certbot-nginx

# 获取 SSL 证书
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 设置自动续期
sudo crontab -e
# 添加以下行：
0 12 * * * /usr/bin/certbot renew --quiet
```

### 8. 启动服务

```bash
# 启动并启用服务
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm
sudo systemctl start mysql
sudo systemctl enable mysql
sudo systemctl start redis
sudo systemctl enable redis
sudo systemctl start nginx
sudo systemctl enable nginx

# 创建 Webman 系统服务
sudo cp examples/production/webman-filament.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable webman-filament
sudo systemctl start webman-filament
```

## 🐳 Docker 部署

### 1. 基础部署

```bash
# 克隆项目
git clone https://github.com/your-org/webman-filament.git
cd webman-filament

# 复制环境配置
cp examples/docker/.env.docker .env

# 启动服务
docker-compose -f examples/docker/docker-compose.simple.yml up -d

# 查看状态
docker-compose -f examples/docker/docker-compose.simple.yml ps
```

### 2. 完整环境部署

```bash
# 启动完整环境（包括监控、日志等）
docker-compose -f examples/docker/docker-compose.yml up -d

# 查看日志
docker-compose logs -f app
```

### 3. 生产环境部署

```bash
# 启动生产环境（多实例、负载均衡）
docker-compose -f examples/docker/docker-compose.prod.yml up -d

# 扩容应用实例
docker-compose -f examples/docker/docker-compose.prod.yml up -d --scale app-1=5
```

### 4. 开发环境部署

```bash
# 启动开发环境
docker-compose -f examples/docker/docker-compose.yml -f examples/docker/docker-compose.dev.yml up -d

# 进入应用容器
docker-compose exec app bash

# 运行命令
docker-compose exec app php artisan migrate
```

## 🌐 Nginx 配置

### 基础配置

```nginx
# /etc/nginx/nginx.conf
user nginx;
worker_processes auto;
error_log /var/log/nginx/error.log warn;
pid /var/run/nginx.pid;

events {
    worker_connections 1024;
    use epoll;
    multi_accept on;
}

http {
    include /etc/nginx/mime.types;
    default_type application/octet-stream;

    # 日志格式
    log_format main '$remote_addr - $remote_user [$time_local] "$request" '
                    '$status $body_bytes_sent "$http_referer" '
                    '"$http_user_agent" "$http_x_forwarded_for" '
                    'rt=$request_time uct="$upstream_connect_time" '
                    'uht="$upstream_header_time" urt="$upstream_response_time"';

    access_log /var/log/nginx/access.log main;

    # 性能配置
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    types_hash_max_size 2048;
    server_tokens off;

    # Gzip 压缩
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;

    # 上游服务器配置
    upstream webman_app {
        server 127.0.0.1:9000 weight=1 max_fails=3 fail_timeout=30s;
        keepalive 32;
    }

    # 包含站点配置
    include /etc/nginx/conf.d/*.conf;
}
```

### 站点配置

```nginx
# /etc/nginx/conf.d/webman-filament.conf
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com www.your-domain.com;
    
    # 重定向到 HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name your-domain.com www.your-domain.com;
    
    root /var/www/webman-filament/public;
    index index.php index.html;
    
    # SSL 配置
    ssl_certificate /etc/nginx/ssl/certificate.crt;
    ssl_certificate_key /etc/nginx/ssl/private.key;
    
    # SSL 优化
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    ssl_prefer_server_ciphers off;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    
    # 安全头
    add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    
    # 静态文件缓存
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
    }
    
    # Filament 静态资源
    location /filament/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }
    
    # PHP 处理
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass webman_app:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # 超时配置
        fastcgi_connect_timeout 60s;
        fastcgi_send_timeout 60s;
        fastcgi_read_timeout 60s;
    }
    
    # 主入口
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    # 拒绝访问敏感文件
    location ~ /\. {
        deny all;
    }
    
    location ~* \.(env|config|log)$ {
        deny all;
    }
}
```

## 🗄️ 数据库配置

### MySQL 配置

#### 安装和配置

```bash
# 安装 MySQL
sudo apt install mysql-server

# 安全配置
sudo mysql_secure_installation

# 创建数据库
mysql -u root -p
CREATE DATABASE webman_filament CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'webman'@'localhost' IDENTIFIED BY 'secure_password';
GRANT ALL PRIVILEGES ON webman_filament.* TO 'webman'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 性能优化

编辑 `/etc/mysql/mysql.conf.d/mysqld.cnf`:

```ini
[mysqld]
# 字符集配置
character-set-server=utf8mb4
collation-server=utf8mb4_unicode_ci

# 性能配置
innodb_buffer_pool_size=1G
innodb_log_file_size=256M
innodb_flush_log_at_trx_commit=2
innodb_flush_method=O_DIRECT
innodb_file_per_table=1

# 连接配置
max_connections=200
max_connect_errors=1000
wait_timeout=28800
interactive_timeout=28800

# 查询缓存
query_cache_type=1
query_cache_size=128M
query_cache_limit=2M

# 二进制日志
expire_logs_days=7
max_binlog_size=100M

# 安全配置
sql_mode=STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE,ERROR_FOR_DIVISION_BY_ZERO

# 慢查询日志
slow_query_log=1
slow_query_log_file=/var/log/mysql/mysql-slow.log
long_query_time=2
```

#### 备份和恢复

```bash
# 备份数据库
mysqldump -u webman -p webman_filament > backup_$(date +%Y%m%d_%H%M%S).sql

# 恢复数据库
mysql -u webman -p webman_filament < backup_20231201_120000.sql

# 自动备份脚本
#!/bin/bash
BACKUP_DIR="/var/backups/mysql"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

mysqldump -u webman -p webman_filament > $BACKUP_DIR/webman_filament_$DATE.sql

# 清理旧备份（保留7天）
find $BACKUP_DIR -name "*.sql" -mtime +7 -delete
```

### PostgreSQL 配置

#### 安装和配置

```bash
# 安装 PostgreSQL
sudo apt install postgresql postgresql-contrib

# 创建数据库和用户
sudo -u postgres psql
CREATE DATABASE webman_filament;
CREATE USER webman WITH ENCRYPTED PASSWORD 'secure_password';
GRANT ALL PRIVILEGES ON DATABASE webman_filament TO webman;
\q
```

#### 性能优化

编辑 `/etc/postgresql/14/main/postgresql.conf`:

```ini
# 内存配置
shared_buffers = 256MB
effective_cache_size = 1GB
work_mem = 4MB
maintenance_work_mem = 64MB

# WAL 配置
wal_level = replica
max_wal_size = 1GB
min_wal_size = 80MB

# 连接配置
max_connections = 100

# 日志配置
log_destination = 'stderr'
logging_collector = on
log_directory = 'log'
log_filename = 'postgresql-%Y-%m-%d_%H%M%S.log'
log_statement = 'all'
log_min_duration_statement = 1000
```

## 🔒 SSL 配置

### Let's Encrypt 证书

```bash
# 安装 Certbot
sudo apt install certbot python3-certbot-nginx

# 获取证书
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 测试自动续期
sudo certbot renew --dry-run

# 设置定时任务
sudo crontab -e
# 添加：
0 12 * * * /usr/bin/certbot renew --quiet
```

### 自签名证书（测试环境）

```bash
# 创建证书目录
sudo mkdir -p /etc/nginx/ssl

# 生成私钥
sudo openssl genrsa -out /etc/nginx/ssl/private.key 2048

# 生成证书签名请求
sudo openssl req -new -key /etc/nginx/ssl/private.key -out /etc/nginx/ssl/cert.csr

# 生成自签名证书
sudo openssl x509 -req -days 365 -in /etc/nginx/ssl/cert.csr \
    -signkey /etc/nginx/ssl/private.key -out /etc/nginx/ssl/certificate.crt

# 设置权限
sudo chmod 600 /etc/nginx/ssl/private.key
sudo chmod 644 /etc/nginx/ssl/certificate.crt
```

### SSL 配置优化

```nginx
# SSL 配置
ssl_certificate /etc/nginx/ssl/certificate.crt;
ssl_certificate_key /etc/nginx/ssl/private.key;

# SSL 协议和加密套件
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512:ECDHE-RSA-AES256-GCM-SHA384:DHE-RSA-AES256-GCM-SHA384;
ssl_prefer_server_ciphers off;

# 会话缓存
ssl_session_cache shared:SSL:50m;
ssl_session_timeout 1d;
ssl_session_tickets off;

# OCSP Stapling
ssl_stapling on;
ssl_stapling_verify on;
ssl_trusted_certificate /etc/nginx/ssl/ca.crt;
resolver 8.8.8.8 8.8.4.4 valid=300s;
resolver_timeout 5s;

# HSTS
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
```

## 📊 监控和日志

### 应用日志

```bash
# 查看应用日志
tail -f storage/logs/laravel.log

# 查看 Webman 日志
tail -f storage/logs/webman.log

# 查看 Nginx 访问日志
tail -f /var/log/nginx/access.log

# 查看 Nginx 错误日志
tail -f /var/log/nginx/error.log

# 查看 MySQL 日志
tail -f /var/log/mysql/error.log

# 查看 Redis 日志
tail -f /var/log/redis/redis-server.log
```

### 系统监控

```bash
# 安装监控工具
sudo apt install htop iotop nethogs

# 查看系统资源
htop
iotop
nethogs

# 查看磁盘空间
df -h

# 查看内存使用
free -h

# 查看进程
ps aux | grep webman
ps aux | grep nginx
ps aux | grep mysql
```

### 日志轮转

创建 `/etc/logrotate.d/webman-filament`:

```bash
/var/www/webman-filament/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 www-data www-data
    postrotate
        systemctl reload webman-filament
    endscript
}

/var/log/nginx/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 nginx adm
    postrotate
        systemctl reload nginx
    endscript
}

/var/log/mysql/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0644 mysql mysql
    postrotate
        systemctl reload mysql
    endscript
}
```

### 性能监控

#### Prometheus + Grafana

```bash
# 安装 Prometheus
wget https://github.com/prometheus/prometheus/releases/download/v2.40.0/prometheus-2.40.0.linux-amd64.tar.gz
tar xvfz prometheus-*.tar.gz
sudo cp prometheus-*/prometheus /usr/local/bin/

# 安装 Grafana
sudo apt install grafana

# 启动服务
sudo systemctl start prometheus
sudo systemctl start grafana-server
sudo systemctl enable prometheus
sudo systemctl enable grafana-server
```

#### 自定义监控脚本

创建 `/usr/local/bin/webman-health-check.sh`:

```bash
#!/bin/bash

# 健康检查脚本
LOG_FILE="/var/log/webman-health-check.log"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

# 检查 Webman 服务
if ! systemctl is-active --quiet webman-filament; then
    echo "[$TIMESTAMP] ERROR: Webman service is not running" >> $LOG_FILE
    systemctl restart webman-filament
fi

# 检查数据库连接
if ! mysql -u webman -p'password' -e "SELECT 1" webman_filament > /dev/null 2>&1; then
    echo "[$TIMESTAMP] ERROR: Database connection failed" >> $LOG_FILE
fi

# 检查磁盘空间
DISK_USAGE=$(df / | awk 'NR==2 {print $5}' | sed 's/%//')
if [ $DISK_USAGE -gt 90 ]; then
    echo "[$TIMESTAMP] WARNING: Disk usage is ${DISK_USAGE}%" >> $LOG_FILE
fi

# 检查内存使用
MEMORY_USAGE=$(free | awk 'NR==2{printf "%.0f", $3*100/$2}')
if [ $MEMORY_USAGE -gt 90 ]; then
    echo "[$TIMESTAMP] WARNING: Memory usage is ${MEMORY_USAGE}%" >> $LOG_FILE
fi
```

设置定时任务：

```bash
# 添加到 crontab
sudo crontab -e

# 每5分钟执行一次健康检查
*/5 * * * * /usr/local/bin/webman-health-check.sh
```

## 💾 备份和恢复

### 数据库备份

```bash
#!/bin/bash
# 数据库备份脚本

BACKUP_DIR="/var/backups/database"
DATE=$(date +%Y%m%d_%H%M%S)
DB_NAME="webman_filament"
DB_USER="webman"
DB_PASS="secure_password"

mkdir -p $BACKUP_DIR

# 备份数据库
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME | gzip > $BACKUP_DIR/db_${DATE}.sql.gz

# 清理旧备份（保留30天）
find $BACKUP_DIR -name "db_*.sql.gz" -mtime +30 -delete

# 上传到远程存储（可选）
# aws s3 cp $BACKUP_DIR/db_${DATE}.sql.gz s3://your-backup-bucket/database/

echo "Database backup completed: db_${DATE}.sql.gz"
```

### 应用文件备份

```bash
#!/bin/bash
# 应用文件备份脚本

BACKUP_DIR="/var/backups/application"
DATE=$(date +%Y%m%d_%H%M%S)
APP_DIR="/var/www/webman-filament"

mkdir -p $BACKUP_DIR

# 备份应用文件（排除不必要的目录）
tar -czf $BACKUP_DIR/app_${DATE}.tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    --exclude='storage/framework/cache' \
    --exclude='bootstrap/cache' \
    $APP_DIR

# 清理旧备份（保留7天）
find $BACKUP_DIR -name "app_*.tar.gz" -mtime +7 -delete

echo "Application backup completed: app_${DATE}.tar.gz"
```

### 完整备份脚本

```bash
#!/bin/bash
# 完整备份脚本

BACKUP_DIR="/var/backups/full"
DATE=$(date +%Y%m%d_%H%M%S)
LOG_FILE="$BACKUP_DIR/backup_${DATE}.log"

mkdir -p $BACKUP_DIR

echo "[$DATE] Starting full backup" >> $LOG_FILE

# 备份数据库
echo "[$DATE] Backing up database" >> $LOG_FILE
mysqldump -u webman -p'secure_password' webman_filament | gzip > $BACKUP_DIR/database_${DATE}.sql.gz

# 备份应用文件
echo "[$DATE] Backing up application files" >> $LOG_FILE
tar -czf $BACKUP_DIR/application_${DATE}.tar.gz \
    --exclude='node_modules' \
    --exclude='vendor' \
    --exclude='storage/logs' \
    /var/www/webman-filament

# 备份 Nginx 配置
echo "[$DATE] Backing up Nginx configuration" >> $LOG_FILE
tar -czf $BACKUP_DIR/nginx_${DATE}.tar.gz /etc/nginx

# 备份 SSL 证书
echo "[$DATE] Backing up SSL certificates" >> $LOG_FILE
tar -czf $BACKUP_DIR/ssl_${DATE}.tar.gz /etc/nginx/ssl

# 清理旧备份（保留30天）
find $BACKUP_DIR -name "*_${DATE}.*" -mtime +30 -delete

echo "[$DATE] Full backup completed" >> $LOG_FILE
```

### 恢复流程

```bash
#!/bin/bash
# 恢复脚本

BACKUP_DATE=$1
BACKUP_DIR="/var/backups/full"

if [ -z "$BACKUP_DATE" ]; then
    echo "Usage: $0 YYYYMMDD_HHMMSS"
    exit 1
fi

echo "Starting restoration from backup: $BACKUP_DATE"

# 停止服务
sudo systemctl stop webman-filament
sudo systemctl stop nginx

# 恢复数据库
echo "Restoring database..."
gunzip -c $BACKUP_DIR/database_${BACKUP_DATE}.sql.gz | mysql -u webman -p webman_filament

# 恢复应用文件
echo "Restoring application files..."
sudo tar -xzf $BACKUP_DIR/application_${BACKUP_DATE}.tar.gz -C /

# 恢复 Nginx 配置
echo "Restoring Nginx configuration..."
sudo tar -xzf $BACKUP_DIR/nginx_${BACKUP_DATE}.tar.gz -C /

# 恢复 SSL 证书
echo "Restoring SSL certificates..."
sudo tar -xzf $BACKUP_DIR/ssl_${BACKUP_DATE}.tar.gz -C /

# 设置权限
sudo chown -R www-data:www-data /var/www/webman-filament
sudo chmod -R 755 /var/www/webman-filament
sudo chmod -R 775 /var/www/webman-filament/storage
sudo chmod -R 775 /var/www/webman-filament/bootstrap/cache

# 启动服务
sudo systemctl start nginx
sudo systemctl start webman-filament

echo "Restoration completed"
```

## 🚨 故障排除

### 常见问题

#### 1. 服务启动失败

```bash
# 检查服务状态
sudo systemctl status webman-filament
sudo systemctl status nginx
sudo systemctl status mysql

# 查看日志
sudo journalctl -u webman-filament -f
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/mysql/error.log

# 检查端口占用
sudo netstat -tulpn | grep :80
sudo netstat -tulpn | grep :443
sudo netstat -tulpn | grep :3306
```

#### 2. 数据库连接失败

```bash
# 检查 MySQL 服务
sudo systemctl status mysql

# 测试数据库连接
mysql -u webman -p webman_filament

# 检查数据库配置
cat .env | grep DB_

# 检查 MySQL 错误日志
sudo tail -f /var/log/mysql/error.log
```

#### 3. 权限问题

```bash
# 设置正确的文件权限
sudo chown -R www-data:www-data /var/www/webman-filament
sudo chmod -R 755 /var/www/webman-filament
sudo chmod -R 775 /var/www/webman-filament/storage
sudo chmod -R 775 /var/www/webman-filament/bootstrap/cache

# 检查 SELinux 状态（CentOS/RHEL）
getenforce
# 如果是 Enforcing，可能需要调整策略
```

#### 4. 内存不足

```bash
# 查看内存使用
free -h
cat /proc/meminfo

# 查看内存使用最多的进程
ps aux --sort=-%mem | head

# 调整 PHP 内存限制
# 编辑 /etc/php/8.2/fpm/php.ini
memory_limit = 512M
```

#### 5. 磁盘空间不足

```bash
# 查看磁盘使用
df -h

# 查看大文件
sudo find / -type f -size +100M -exec ls -lh {} \;

# 清理日志文件
sudo journalctl --vacuum-time=7d

# 清理临时文件
sudo apt autoremove
sudo apt autoclean
```

### 调试技巧

#### 1. 启用调试模式

```bash
# 临时启用调试模式
export APP_DEBUG=true
php start.php start

# 或修改 .env 文件
APP_DEBUG=true
LOG_LEVEL=debug
```

#### 2. 查看详细错误信息

```bash
# 查看 PHP 错误日志
sudo tail -f /var/log/php_errors.log

# 查看 Nginx 错误日志
sudo tail -f /var/log/nginx/error.log

# 查看应用日志
tail -f storage/logs/laravel.log
```

#### 3. 数据库调试

```bash
# 启用 MySQL 慢查询日志
# 编辑 /etc/mysql/mysql.conf.d/mysqld.cnf
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 2

# 重启 MySQL
sudo systemctl restart mysql

# 查看慢查询
sudo mysqldumpslow /var/log/mysql/mysql-slow.log
```

#### 4. 性能分析

```bash
# 安装性能分析工具
sudo apt install strace htop iotop

# 跟踪系统调用
sudo strace -p $(pgrep -f webman) -c

# 监控系统资源
htop
iotop
```

## ⚡ 性能优化

### PHP 优化

#### OPcache 配置

```ini
# /etc/php/8.2/fpm/php.ini
opcache.enable = 1
opcache.enable_cli = 1
opcache.memory_consumption = 256
opcache.interned_strings_buffer = 16
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1
opcache.save_comments = 0
opcache.validate_timestamps = 0
opcache.preload_user = www-data
opcache.preload = /var/www/webman-filament/bootstrap/cache/preload.php
```

#### PHP-FPM 优化

```ini
# /etc/php/8.2/fpm/pool.d/www.conf
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

# 慢请求处理
request_slowlog_timeout = 5s
slowlog = /var/log/php8.2-fpm-slow.log

# 进程管理
process.max = 50
rlimit_files = 131072
rlimit_core = 0
```

### 数据库优化

#### MySQL 优化

```ini
# /etc/mysql/mysql.conf.d/mysqld.cnf
[mysqld]
# 内存配置
innodb_buffer_pool_size = 2G
innodb_log_file_size = 512M
innodb_log_buffer_size = 16M
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1

# 连接配置
max_connections = 200
max_connect_errors = 1000
wait_timeout = 28800
interactive_timeout = 28800

# 查询缓存
query_cache_type = 1
query_cache_size = 256M
query_cache_limit = 2M

# 临时表配置
tmp_table_size = 256M
max_heap_table_size = 256M

# 二进制日志
log_bin = /var/log/mysql/mysql-bin.log
expire_logs_days = 7
max_binlog_size = 100M
binlog_format = ROW

# 慢查询日志
slow_query_log = 1
slow_query_log_file = /var/log/mysql/mysql-slow.log
long_query_time = 2
```

#### 查询优化

```sql
-- 添加索引
ALTER TABLE users ADD INDEX idx_email (email);
ALTER TABLE posts ADD INDEX idx_user_id (user_id);
ALTER TABLE posts ADD INDEX idx_created_at (created_at);

-- 优化查询
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';
EXPLAIN SELECT * FROM posts WHERE user_id = 1 ORDER BY created_at DESC;
```

### Redis 优化

```conf
# /etc/redis/redis.conf
# 内存配置
maxmemory 1gb
maxmemory-policy allkeys-lru

# 持久化配置
save 900 1
save 300 10
save 60 10000
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes

# AOF 配置
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec

# 性能优化
tcp-keepalive 300
timeout 0
tcp-backlog 511

# 慢查询日志
slowlog-log-slower-than 10000
slowlog-max-len 128
```

### Nginx 优化

```nginx
# /etc/nginx/nginx.conf
worker_processes auto;
worker_connections 4096;
worker_rlimit_nofile 65535;

events {
    worker_connections 4096;
    use epoll;
    multi_accept on;
}

http {
    # 性能配置
    sendfile on;
    tcp_nopush on;
    tcp_nodelay on;
    keepalive_timeout 65;
    keepalive_requests 1000;
    
    # 缓冲配置
    client_body_buffer_size 128k;
    client_max_body_size 50m;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 4k;
    output_buffers 1 32k;
    postpone_output 1460;
    
    # 超时配置
    client_body_timeout 12;
    client_header_timeout 12;
    keepalive_timeout 15;
    send_timeout 10;
    
    # Gzip 压缩
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied any;
    gzip_comp_level 6;
    gzip_types
        text/plain
        text/css
        text/xml
        text/javascript
        application/json
        application/javascript
        application/xml+rss
        application/atom+xml
        image/svg+xml;
}
```

## 🔐 安全配置

### 系统安全

#### 防火墙配置

```bash
# Ubuntu/Debian (UFW)
sudo ufw default deny incoming
sudo ufw default allow outgoing
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable

# CentOS/RHEL (firewalld)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

#### Fail2ban 配置

```bash
# 安装 Fail2ban
sudo apt install fail2ban

# 创建配置文件
sudo tee /etc/fail2ban/jail.local <<EOF
[DEFAULT]
bantime = 3600
findtime = 600
maxretry = 5
ignoreip = 127.0.0.1/8 ::1

[nginx-http-auth]
enabled = true
filter = nginx-http-auth
logpath = /var/log/nginx/error.log
maxretry = 3

[nginx-limit-req]
enabled = true
filter = nginx-limit-req
logpath = /var/log/nginx/error.log
maxretry = 10

[sshd]
enabled = true
filter = sshd
logpath = /var/log/auth.log
maxretry = 3
EOF

# 启动 Fail2ban
sudo systemctl enable fail2ban
sudo systemctl start fail2ban
```

#### 系统更新

```bash
# 设置自动安全更新
sudo apt install unattended-upgrades
sudo dpkg-reconfigure -plow unattended-upgrades

# 创建更新脚本
sudo tee /usr/local/bin/security-update.sh <<EOF
#!/bin/bash
apt update && apt list --upgradable
apt upgrade -y
apt autoremove -y
apt autoclean
EOF

sudo chmod +x /usr/local/bin/security-update.sh

# 添加到定时任务
sudo crontab -e
# 添加：每周日凌晨2点执行安全更新
0 2 * * 0 /usr/local/bin/security-update.sh
```

### 应用安全

#### 环境变量安全

```bash
# 设置 .env 文件权限
chmod 600 .env

# 确保 .env 文件不被版本控制
echo ".env" >> .gitignore

# 使用强密码
# 生成随机密码
openssl rand -base64 32
```

#### 文件权限

```bash
# 设置严格的文件权限
find /var/www/webman-filament -type f -exec chmod 644 {} \;
find /var/www/webman-filament -type d -exec chmod 755 {} \;

# 保护敏感文件
chmod 600 /var/www/webman-filament/.env
chmod 600 /var/www/webman-filament/config/database.php
chmod 600 /var/www/webman-filament/storage/logs/*.log

# 设置所有者
chown -R www-data:www-data /var/www/webman-filament
```

#### 安全头配置

```nginx
# Nginx 安全头配置
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'; frame-ancestors 'self';" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
```

#### 访问控制

```nginx
# 限制访问敏感路径
location ~ ^/(admin|api)/ {
    # IP 白名单（可选）
    allow 192.168.1.0/24;
    allow 10.0.0.0/8;
    deny all;
    
    # 基本认证（可选）
    # auth_basic "Restricted Area";
    # auth_basic_user_file /etc/nginx/.htpasswd;
    
    try_files $uri $uri/ /index.php?$query_string;
}

# 禁止访问敏感文件
location ~ /\. {
    deny all;
}

location ~* \.(env|config|log|sql|bak|backup|old|tmp|temp)$ {
    deny all;
}

location ~ ^/(.git|svn|hg)/ {
    deny all;
}
```

### 数据库安全

#### MySQL 安全配置

```sql
-- 删除测试数据库
DROP DATABASE IF EXISTS test;

-- 删除匿名用户
DELETE FROM mysql.user WHERE User='';

-- 禁止 root 用户远程登录
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

-- 刷新权限
FLUSH PRIVILEGES;

-- 创建应用专用用户
CREATE USER 'webman_app'@'localhost' IDENTIFIED BY 'secure_password';
GRANT SELECT, INSERT, UPDATE, DELETE ON webman_filament.* TO 'webman_app'@'localhost';
FLUSH PRIVILEGES;
```

#### 备份加密

```bash
#!/bin/bash
# 加密备份脚本

BACKUP_DIR="/var/backups/encrypted"
DATE=$(date +%Y%m%d_%H%M%S)
PASSWORD_FILE="/etc/backup/.backup_password"

# 生成随机密码
openssl rand -base64 32 > $PASSWORD_FILE
chmod 600 $PASSWORD_FILE

# 加密备份
mysqldump -u webman -p webman_filament | gzip | gpg --cipher-algo AES256 --compress-algo 1 \
    --s2k-mode 3 --s2k-digest-algo SHA512 --s2k-count 65536 \
    --passphrase-file $PASSWORD_FILE --symmetric \
    --output $BACKUP_DIR/backup_${DATE}.sql.gz.gpg

# 清理明文备份
rm -f $BACKUP_DIR/backup_${DATE}.sql.gz

echo "Encrypted backup created: backup_${DATE}.sql.gz.gpg"
```

这个部署指南提供了完整的 Webman Filament 应用部署方案，涵盖了从开发到生产的各个阶段。详细的配置说明和最佳实践可以帮助你构建稳定、安全、高性能的应用服务。