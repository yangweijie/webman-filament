# Nginx 配置示例

这个目录包含了 Webman Filament 的 Nginx 配置示例，涵盖开发、测试和生产环境。

## 📋 文件说明

- `nginx.conf` - 基础 Nginx 配置
- `conf.d/webman-filament.conf` - 生产环境服务器配置
- `nginx-lb.conf` - 负载均衡器配置
- `conf.d/dev.conf` - 开发环境配置

## 🚀 快速开始

### 1. 基础配置

```bash
# 复制基础配置
sudo cp examples/nginx/nginx.conf /etc/nginx/nginx.conf

# 创建站点配置目录
sudo mkdir -p /etc/nginx/conf.d

# 复制站点配置
sudo cp examples/nginx/conf.d/webman-filament.conf /etc/nginx/conf.d/

# 测试配置
sudo nginx -t

# 重启 Nginx
sudo systemctl reload nginx
```

### 2. SSL 证书配置

```bash
# 创建 SSL 目录
sudo mkdir -p /etc/nginx/ssl

# 生成自签名证书（测试环境）
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout /etc/nginx/ssl/private.key \
  -out /etc/nginx/ssl/certificate.crt

# 或使用 Let's Encrypt（生产环境）
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d your-domain.com -d www.your-domain.com
```

### 3. 域名配置

编辑 `/etc/nginx/conf.d/webman-filament.conf`，修改域名：

```nginx
server_name your-domain.com www.your-domain.com;
```

## 🔧 配置详解

### 基础配置 (nginx.conf)

#### 性能优化
- `worker_processes auto` - 自动检测 CPU 核心数
- `use epoll` - 高效的事件模型
- `multi_accept on` - 一次接受多个连接
- `sendfile on` - 零拷贝文件传输
- `tcp_nopush on` - 优化 TCP 数据包
- `tcp_nodelay on` - 禁用 Nagle 算法

#### 压缩配置
```nginx
gzip on;
gzip_vary on;
gzip_min_length 1024;
gzip_proxied any;
gzip_comp_level 6;
```

#### 限流配置
```nginx
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;
limit_req_zone $binary_remote_addr zone=login:10m rate=1r/s;
limit_conn_zone $binary_remote_addr zone=addr:10m;
```

### 生产环境配置

#### 安全头
```nginx
add_header Strict-Transport-Security "max-age=63072000; includeSubDomains; preload" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-XSS-Protection "1; mode=block" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "no-referrer-when-downgrade" always;
add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'; frame-ancestors 'self';" always;
```

#### SSL 配置
```nginx
ssl_certificate /etc/nginx/ssl/certificate.crt;
ssl_certificate_key /etc/nginx/ssl/private.key;
ssl_protocols TLSv1.2 TLSv1.3;
ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
ssl_prefer_server_ciphers off;
ssl_session_cache shared:SSL:10m;
ssl_session_timeout 10m;
```

#### 上游服务器
```nginx
upstream webman_app {
    server app-1:9000 weight=1 max_fails=3 fail_timeout=30s;
    server app-2:9000 weight=1 max_fails=3 fail_timeout=30s backup;
    keepalive 32;
}
```

### 负载均衡配置

#### 算法选择
- `least_conn` - 最少连接
- `ip_hash` - IP 哈希
- `weight` - 权重分配

#### 健康检查
```nginx
location /health {
    access_log off;
    return 200 "healthy\n";
    add_header Content-Type text/plain;
}
```

### 开发环境配置

#### 禁用缓存
```nginx
add_header Cache-Control "no-cache, no-store, must-revalidate";
add_header Pragma "no-cache";
add_header Expires "0";
```

#### 详细日志
```nginx
access_log /var/log/nginx/dev.access.log;
error_log /var/log/nginx/dev.error.log debug;
```

## 🔒 安全配置

### 文件访问控制

```nginx
# 拒绝访问隐藏文件
location ~ /\. {
    deny all;
    access_log off;
    log_not_found off;
}

# 拒绝访问敏感文件
location ~* \.(env|config|log|sql|bak|backup|old|tmp|temp)$ {
    deny all;
    access_log off;
    log_not_found off;
}
```

### 限流配置

```nginx
# API 限流
location /api/ {
    limit_req zone=api burst=20 nodelay;
}

# 登录限流
location ~ ^/(admin/login|login) {
    limit_req zone=login burst=5 nodelay;
}
```

### 安全头

```nginx
# XSS 保护
add_header X-XSS-Protection "1; mode=block" always;

# 内容类型保护
add_header X-Content-Type-Options "nosniff" always;

# 框架保护
add_header X-Frame-Options "SAMEORIGIN" always;
```

## 📊 性能优化

### 静态文件缓存

```nginx
location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
    add_header Vary Accept-Encoding;
    access_log off;
}
```

### Gzip 压缩

```nginx
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
    application/xml+rss;
```

### FastCGI 优化

```nginx
fastcgi_connect_timeout 60s;
fastcgi_send_timeout 60s;
fastcgi_read_timeout 60s;
fastcgi_buffer_size 64k;
fastcgi_buffers 4 64k;
fastcgi_busy_buffers_size 128k;
fastcgi_keep_conn on;
```

## 🚨 故障排除

### 常见问题

1. **403 Forbidden**
   ```bash
   # 检查文件权限
   sudo chown -R www-data:www-data /var/www/html
   sudo chmod -R 755 /var/www/html
   ```

2. **502 Bad Gateway**
   ```bash
   # 检查上游服务器状态
   curl http://localhost:9000/health
   
   # 检查 Nginx 配置
   sudo nginx -t
   ```

3. **504 Gateway Timeout**
   ```bash
   # 增加超时时间
   fastcgi_read_timeout 300s;
   ```

4. **413 Request Entity Too Large**
   ```bash
   # 增加客户端请求大小限制
   client_max_body_size 100M;
   ```

### 调试技巧

1. **查看错误日志**
   ```bash
   sudo tail -f /var/log/nginx/error.log
   ```

2. **查看访问日志**
   ```bash
   sudo tail -f /var/log/nginx/access.log
   ```

3. **测试配置**
   ```bash
   sudo nginx -t
   sudo nginx -T  # 显示完整配置
   ```

4. **检查连接状态**
   ```bash
   sudo netstat -tlnp | grep nginx
   ```

## 📈 监控和统计

### 状态页面

```nginx
location /nginx_status {
    stub_status on;
    access_log off;
    allow 127.0.0.1;
    allow 10.0.0.0/8;
    allow 172.16.0.0/12;
    allow 192.168.0.0/16;
    deny all;
}
```

### 日志分析

```bash
# 访问量统计
awk '{print $1}' /var/log/nginx/access.log | sort | uniq -c | sort -nr | head -10

# 错误统计
grep " 5[0-9][0-9] " /var/log/nginx/access.log | wc -l

# 响应时间统计
awk '{print $NF}' /var/log/nginx/access.log | sort -n
```

## 🔄 负载均衡配置

### 多实例部署

```bash
# 启动多个应用实例
docker-compose up -d --scale app-1=3

# Nginx 自动负载均衡
upstream webman_backend {
    server app-1:9000 weight=1 max_fails=3 fail_timeout=30s;
    server app-2:9000 weight=1 max_fails=3 fail_timeout=30s;
    server app-3:9000 weight=1 max_fails=3 fail_timeout=30s;
    keepalive 32;
}
```

### 健康检查

```bash
# 定期检查后端服务器状态
# 可以使用第三方工具如 nginx-upstream-check-module
```

## 📝 维护任务

### 定期清理日志

```bash
# 创建日志轮转配置
sudo tee /etc/logrotate.d/nginx <<EOF
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
EOF
```

### 性能调优

```bash
# 优化 worker 连接数
worker_connections 4096;

# 优化文件描述符限制
worker_rlimit_nofile 65535;
```

这个 Nginx 配置提供了完整的 Web 服务器解决方案，支持从开发到生产的各个阶段。详细的配置说明请参考各个配置文件中的注释。