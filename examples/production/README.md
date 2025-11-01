# Webman Filament 生产环境配置

这个目录包含了 Webman Filament 的生产环境配置示例，针对性能、安全性和稳定性进行了优化。

## 🚀 生产环境特性

- **高性能**: 连接池、OPcache、Redis 缓存
- **高安全**: HTTPS、CSRF、CSP、安全头
- **高可用**: 健康检查、监控告警、优雅关闭
- **高稳定**: 错误处理、重启机制、资源清理

## 📋 部署前准备

### 系统要求

- **操作系统**: Linux (Ubuntu 20.04+ / CentOS 7+ / RHEL 8+)
- **PHP**: 8.1+ (推荐 8.2)
- **数据库**: MySQL 8.0+ / PostgreSQL 13+ / Redis 6+
- **Web 服务器**: Nginx 1.18+ / Apache 2.4+
- **内存**: 最少 2GB RAM
- **磁盘**: 最少 20GB SSD

### PHP 扩展

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install php8.2-fpm php8.2-mysql php8.2-redis php8.2-xml \
    php8.2-curl php8.2-zip php8.2-mbstring php8.2-bcmath \
    php8.2-gd php8.2-intl php8.2-opcache

# CentOS/RHEL
sudo yum install php82 php82-php-fpm php82-php-mysqlnd php82-php-redis \
    php82-php-xml php82-php-curl php82-php-zip php82-php-mbstring \
    php82-php-bcmath php82-php-gd php82-php-intl php82-php-opcache
```

### PHP 配置优化

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

## 🔧 部署步骤

### 1. 代码部署

```bash
# 克隆代码库
git clone https://github.com/your-org/webman-filament.git
cd webman-filament

# 安装依赖
composer install --no-dev --optimize-autoloader

# 安装前端依赖
npm install
npm run build

# 复制环境配置
cp examples/production/.env.production .env

# 编辑环境配置
vim .env
```

### 2. 数据库配置

```bash
# 创建数据库
mysql -u root -p
CREATE DATABASE webman_filament_prod CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'webman_user'@'localhost' IDENTIFIED BY 'secure_password_here';
GRANT ALL PRIVILEGES ON webman_filament_prod.* TO 'webman_user'@'localhost';
FLUSH PRIVILEGES;

# 运行迁移
php artisan migrate --force

# 填充数据（可选）
php artisan db:seed --force
```

### 3. 权限设置

```bash
# 设置目录权限
sudo chown -R www-data:www-data /path/to/webman-filament
sudo chmod -R 755 /path/to/webman-filament
sudo chmod -R 775 /path/to/webman-filament/storage
sudo chmod -R 775 /path/to/webman-filament/bootstrap/cache
sudo chmod -R 775 /path/to/webman-filament/public

# 创建日志目录
sudo mkdir -p /var/log/webman-filament
sudo chown www-data:www-data /var/log/webman-filament
```

### 4. SSL 证书配置

```bash
# 使用 Let's Encrypt
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com

# 或使用自签名证书（仅测试环境）
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/webman-filament.key \
    -out /etc/ssl/certs/webman-filament.crt
```

### 5. Nginx 配置

```bash
# 复制 Nginx 配置
sudo cp examples/nginx/nginx.conf /etc/nginx/sites-available/webman-filament
sudo ln -s /etc/nginx/sites-available/webman-filament /etc/nginx/sites-enabled/

# 测试配置
sudo nginx -t

# 重启 Nginx
sudo systemctl reload nginx
```

### 6. 启动服务

```bash
# 使用 systemd 服务
sudo cp examples/production/webman-filament.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable webman-filament
sudo systemctl start webman-filament

# 检查服务状态
sudo systemctl status webman-filament
sudo systemctl status nginx
sudo systemctl status mysql
sudo systemctl status redis
```

## 🔒 安全配置

### 防火墙设置

```bash
# Ubuntu/Debian (UFW)
sudo ufw allow ssh
sudo ufw allow 'Nginx Full'
sudo ufw enable

# CentOS/RHEL (firewalld)
sudo firewall-cmd --permanent --add-service=ssh
sudo firewall-cmd --permanent --add-service=http
sudo firewall-cmd --permanent --add-service=https
sudo firewall-cmd --reload
```

### 安全加固

```bash
# 禁用不必要的服务
sudo systemctl disable apache2  # 如果不使用 Apache

# 设置 fail2ban
sudo apt install fail2ban
sudo cp examples/production/fail2ban.conf /etc/fail2ban/jail.local
sudo systemctl restart fail2ban

# 定期更新系统
sudo apt update && sudo apt upgrade -y
```

## 📊 监控和维护

### 日志监控

```bash
# 应用日志
tail -f storage/logs/laravel.log

# Webman 日志
tail -f /var/log/webman-filament/webman.log

# Nginx 日志
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log

# 系统日志
tail -f /var/log/syslog
```

### 性能监控

```bash
# 系统资源监控
htop
iotop
netstat -tulnp

# 数据库监控
mysql -e "SHOW PROCESSLIST;"
mysql -e "SHOW STATUS LIKE 'Slow_queries';"

# Redis 监控
redis-cli info
redis-cli monitor
```

### 备份策略

```bash
# 数据库备份脚本
#!/bin/bash
BACKUP_DIR="/var/backups/webman-filament"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# 备份数据库
mysqldump -u webman_user -p webman_filament_prod > $BACKUP_DIR/db_$DATE.sql

# 备份应用文件
tar -czf $BACKUP_DIR/app_$DATE.tar.gz /path/to/webman-filament \
    --exclude=node_modules --exclude=vendor --exclude=storage/logs

# 清理旧备份（保留30天）
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
```

### 自动化维护

创建 `/etc/cron.d/webman-filament`:

```bash
# 每天凌晨2点备份
0 2 * * * www-data /path/to/backup-script.sh

# 每天清理日志
0 1 * * * www-data find /path/to/webman-filament/storage/logs -name "*.log" -mtime +7 -delete

# 每周优化数据库
0 3 * * 0 www-data mysql -u root -p -e "OPTIMIZE TABLE webman_filament_prod.users;"

# 每月更新依赖
0 4 1 * * www-data cd /path/to/webman-filament && composer install --no-dev --optimize-autoloader
```

## 🚨 故障排除

### 常见问题

1. **服务启动失败**
   ```bash
   # 检查日志
   sudo journalctl -u webman-filament -f
   
   # 检查端口占用
   sudo netstat -tulnp | grep :8787
   
   # 检查权限
   sudo chown -R www-data:www-data /path/to/webman-filament
   ```

2. **数据库连接失败**
   ```bash
   # 检查数据库服务
   sudo systemctl status mysql
   
   # 测试连接
   mysql -u webman_user -p webman_filament_prod
   
   # 检查配置
   cat .env | grep DB_
   ```

3. **性能问题**
   ```bash
   # 检查内存使用
   free -h
   
   # 检查磁盘空间
   df -h
   
   # 检查 PHP-FPM 状态
   sudo systemctl status php8.2-fpm
   
   # 检查 OPcache 状态
   php -r "phpinfo();" | grep -i opcache
   ```

### 紧急恢复

```bash
# 快速重启服务
sudo systemctl restart webman-filament
sudo systemctl restart nginx
sudo systemctl restart mysql
sudo systemctl restart redis

# 回滚到上一个版本
git checkout HEAD~1
composer install --no-dev --optimize-autoloader
sudo systemctl restart webman-filament

# 恢复数据库备份
mysql -u webman_user -p webman_filament_prod < /var/backups/webman-filament/db_YYYYMMDD_HHMMSS.sql
```

## 📈 性能优化

### 应用层优化

- 启用 Redis 缓存
- 配置数据库连接池
- 启用 OPcache
- 使用 CDN 加速静态资源
- 启用 Gzip 压缩

### 数据库优化

- 配置 MySQL 查询缓存
- 优化慢查询
- 定期分析表结构
- 使用数据库连接池

### 服务器优化

- 配置 Nginx 反向代理
- 启用 HTTP/2
- 配置静态资源缓存
- 使用负载均衡（多实例部署）

## 🔄 持续集成/部署

### GitHub Actions 示例

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    
    - name: Setup PHP
      uses: shivammathur/setup-php@v2
      with:
        php-version: '8.2'
        
    - name: Install dependencies
      run: composer install --no-dev --optimize-autoloader
      
    - name: Run tests
      run: ./vendor/bin/phpunit
      
    - name: Deploy to server
      uses: appleboy/ssh-action@v0.1.2
      with:
        host: ${{ secrets.HOST }}
        username: ${{ secrets.USERNAME }}
        key: ${{ secrets.KEY }}
        script: |
          cd /path/to/webman-filament
          git pull origin main
          composer install --no-dev --optimize-autoloader
          php artisan migrate --force
          php artisan config:cache
          php artisan route:cache
          php artisan view:cache
          sudo systemctl restart webman-filament
```

这个生产环境配置提供了完整的部署指南，确保你的 Webman Filament 应用在生产环境中稳定、安全、高效地运行。