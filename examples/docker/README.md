# Docker 部署指南

这个目录包含了 Webman Filament 的 Docker 容器化部署配置，支持开发、测试和生产环境。

## 📋 目录结构

```
examples/docker/
├── Dockerfile                 # 应用容器构建文件
├── docker-compose.yml         # 完整环境配置
├── docker-compose.simple.yml  # 简化环境配置
├── docker-compose.prod.yml    # 生产环境配置
├── mysql/
│   ├── master.cnf            # MySQL 主服务器配置
│   └── slave.cnf             # MySQL 从服务器配置
├── redis/
│   └── redis.conf            # Redis 集群配置
└── monitoring/
    ├── prometheus.yml        # 开发环境监控配置
    └── prometheus-production.yml  # 生产环境监控配置
```

## 🚀 快速开始

### 1. 基础部署

```bash
# 克隆项目
git clone https://github.com/your-org/webman-filament.git
cd webman-filament

# 复制环境配置
cp examples/docker/.env.docker .env

# 构建并启动服务
docker-compose -f examples/docker/docker-compose.simple.yml up -d

# 查看服务状态
docker-compose -f examples/docker/docker-compose.simple.yml ps
```

### 2. 完整环境部署

```bash
# 启动完整环境（包括监控、日志等）
docker-compose -f examples/docker/docker-compose.yml up -d

# 查看日志
docker-compose -f examples/docker/docker-compose.yml logs -f app
```

### 3. 生产环境部署

```bash
# 启动生产环境（多实例、负载均衡）
docker-compose -f examples/docker/docker-compose.prod.yml up -d

# 扩容应用实例
docker-compose -f examples/docker/docker-compose.prod.yml up -d --scale app-1=5
```

## 🔧 服务说明

### 核心服务

| 服务 | 端口 | 说明 |
|------|------|------|
| app | 9000 | Webman Filament 应用 |
| nginx | 80, 443 | Web 服务器 |
| database | 3306 | MySQL 数据库 |
| redis | 6379 | Redis 缓存 |

### 可选服务

| 服务 | 端口 | 说明 |
|------|------|------|
| queue | - | 队列处理器 |
| scheduler | - | 任务调度器 |
| monitoring | 9090 | Prometheus 监控 |
| grafana | 3000 | 监控面板 |
| elasticsearch | 9200 | 日志存储 |
| kibana | 5601 | 日志分析 |

## 📊 监控和日志

### Prometheus 监控

访问 http://localhost:9090 查看 Prometheus 监控界面。

### Grafana 面板

访问 http://localhost:3000 查看 Grafana 监控面板。
- 默认用户名: admin
- 默认密码: admin_password

### 日志查看

```bash
# 查看应用日志
docker-compose logs -f app

# 查看 Nginx 日志
docker-compose logs -f nginx

# 查看数据库日志
docker-compose logs -f database

# 查看所有日志
docker-compose logs -f
```

## 🛠️ 开发环境

### 本地开发

```bash
# 启动开发环境
docker-compose -f examples/docker/docker-compose.yml -f examples/docker/docker-compose.dev.yml up -d

# 进入应用容器
docker-compose exec app bash

# 运行命令
docker-compose exec app php artisan migrate
docker-compose exec app php artisan make:filament-resource User
```

### 热重载

开发环境支持代码热重载，修改代码后容器会自动重启。

## 🔒 安全配置

### SSL 证书

```bash
# 创建 SSL 目录
mkdir -p ssl

# 生成自签名证书（仅测试环境）
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
  -keyout ssl/private.key \
  -out ssl/certificate.crt

# 或使用 Let's Encrypt（生产环境）
certbot certonly --standalone -d your-domain.com
```

### 环境变量

生产环境请修改 `.env` 文件中的敏感信息：

```env
# 数据库密码
DB_PASSWORD=your_secure_password

# Redis 密码
REDIS_PASSWORD=your_redis_password

# 应用密钥
APP_KEY=base64:your_generated_key

# 管理员邮箱
AUTH_SUPER_ADMINS=admin@your-domain.com
```

## 📈 性能优化

### 资源限制

在 `docker-compose.prod.yml` 中配置资源限制：

```yaml
deploy:
  resources:
    limits:
      cpus: '1.0'
      memory: 1G
    reservations:
      cpus: '0.5'
      memory: 512M
```

### 缓存优化

启用 Redis 缓存：

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### 数据库优化

使用数据库连接池和读写分离。

## 🔄 备份和恢复

### 数据库备份

```bash
# 手动备份
docker-compose exec database mysqldump -u webman -p webman_filament > backup.sql

# 自动备份
docker-compose exec backup mysqldump -h database-master -u webman -psecure_password webman_filament > /backup/db-$(date +%Y%m%d_%H%M%S).sql
```

### 数据恢复

```bash
# 恢复数据库
docker-compose exec -T database mysql -u webman -p webman_filament < backup.sql
```

### 应用文件备份

```bash
# 备份存储文件
tar -czf app-backup.tar.gz storage/
```

## 🚨 故障排除

### 常见问题

1. **端口占用**
   ```bash
   # 检查端口占用
   netstat -tulpn | grep :80
   
   # 修改端口
   # 在 docker-compose.yml 中修改 ports 配置
   ```

2. **权限问题**
   ```bash
   # 设置正确的文件权限
   sudo chown -R $USER:$USER storage/
   sudo chmod -R 775 storage/
   ```

3. **内存不足**
   ```bash
   # 增加 Docker 内存限制
   # Docker Desktop: Settings > Resources > Memory
   ```

4. **数据库连接失败**
   ```bash
   # 检查数据库状态
   docker-compose exec database mysql -u webman -p -e "SELECT 1;"
   
   # 查看数据库日志
   docker-compose logs database
   ```

### 调试技巧

1. **进入容器调试**
   ```bash
   docker-compose exec app bash
   docker-compose exec nginx sh
   docker-compose exec database mysql -u root -p
   ```

2. **查看容器资源使用**
   ```bash
   docker stats
   ```

3. **网络诊断**
   ```bash
   docker-compose exec app ping database
   docker-compose exec app nslookup database
   ```

## 📋 维护任务

### 定期清理

```bash
# 清理未使用的镜像
docker image prune -a

# 清理未使用的容器
docker container prune

# 清理未使用的卷
docker volume prune

# 清理未使用的网络
docker network prune
```

### 更新服务

```bash
# 重新构建并启动
docker-compose -f examples/docker/docker-compose.yml up -d --build

# 滚动更新（生产环境）
docker-compose -f examples/docker/docker-compose.prod.yml up -d --no-deps app-1
```

### 健康检查

```bash
# 检查所有服务健康状态
docker-compose ps

# 检查应用健康
curl http://localhost/health

# 检查数据库连接
docker-compose exec app php artisan migrate:status
```

## 🔄 CI/CD 集成

### GitHub Actions 示例

```yaml
name: Deploy to Docker

on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
    - uses: actions/checkout@v2
    
    - name: Build and push Docker image
      run: |
        docker build -t webman-filament:${{ github.sha }} -f examples/docker/Dockerfile .
        docker tag webman-filament:${{ github.sha }} webman-filament:latest
        # 推送到你的镜像仓库
        # docker push webman-filament:${{ github.sha }}
    
    - name: Deploy to server
      run: |
        # 部署到服务器
        ssh user@server "cd /path/to/app && docker-compose pull && docker-compose up -d"
```

这个 Docker 配置提供了完整的容器化部署方案，支持从开发到生产的各个阶段。详细的配置和说明请参考各个配置文件中的注释。