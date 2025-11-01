# 环境变量参考

本文档提供Webman Filament Admin所有环境变量的详细说明和配置示例。

## 目录

- [应用基础配置](#应用基础配置)
- [数据库配置](#数据库配置)
- [缓存配置](#缓存配置)
- [会话配置](#会话配置)
- [邮件配置](#邮件配置)
- [文件存储配置](#文件存储配置)
- [队列配置](#队列配置)
- [认证配置](#认证配置)
- [安全配置](#安全配置)
- [监控配置](#监控配置)
- [第三方服务配置](#第三方服务配置)

## 应用基础配置

### APP_NAME
```env
APP_NAME="Webman Filament Admin"
```
- **描述**: 应用名称
- **默认值**: "Webman Filament Admin"
- **用途**: 用于邮件头部、日志等显示

### APP_ENV
```env
APP_ENV=production
```
- **描述**: 应用环境
- **可选值**: `local`, `development`, `testing`, `staging`, `production`
- **默认值**: `local`
- **用途**: 控制调试模式、缓存策略等

### APP_DEBUG
```env
APP_DEBUG=false
```
- **描述**: 调试模式开关
- **可选值**: `true`, `false`
- **默认值**: `true`
- **注意**: 生产环境必须设置为 `false`

### APP_URL
```env
APP_URL=https://your-domain.com
```
- **描述**: 应用基础URL
- **默认值**: `http://localhost`
- **用途**: 生成绝对链接、重定向等

### APP_KEY
```env
APP_KEY=base64:your-32-character-secret-key
```
- **描述**: 应用加密密钥
- **生成**: `php artisan key:generate`
- **长度**: 32字符
- **用途**: 加密、会话签名等

### APP_TIMEZONE
```env
APP_TIMEZONE=Asia/Shanghai
```
- **描述**: 应用时区
- **默认值**: `UTC`
- **用途**: 时间显示、调度任务等

### APP_LOCALE
```env
APP_LOCALE=zh_CN
```
- **描述**: 应用语言
- **默认值**: `en`
- **用途**: 本地化显示

### APP_FALLBACK_LOCALE
```env
APP_FALLBACK_LOCALE=en
```
- **描述**: 备用语言
- **默认值**: `en`
- **用途**: 当主语言文件缺失时使用

### APP_FAKER_LOCALE
```env
APP_FAKER_LOCALE=zh_CN
```
- **描述**: 假数据生成语言
- **默认值**: `en_US`
- **用途**: 测试数据生成

### APP_MAINTENANCE_DRIVER
```env
APP_MAINTENANCE_DRIVER=file
```
- **描述**: 维护模式驱动
- **可选值**: `file`, `cache`
- **默认值**: `file`
- **用途**: 维护模式存储方式

## 数据库配置

### DB_CONNECTION
```env
DB_CONNECTION=mysql
```
- **描述**: 数据库连接类型
- **可选值**: `mysql`, `pgsql`, `sqlite`, `sqlsrv`
- **默认值**: `mysql`
- **用途**: 指定使用的数据库驱动

### DB_HOST
```env
DB_HOST=127.0.0.1
```
- **描述**: 数据库主机地址
- **默认值**: `127.0.0.1`
- **用途**: 数据库连接主机

### DB_PORT
```env
DB_PORT=3306
```
- **描述**: 数据库端口
- **默认值**: `3306`
- **用途**: 数据库连接端口

### DB_DATABASE
```env
DB_DATABASE=webman_filament
```
- **描述**: 数据库名称
- **用途**: 指定要使用的数据库

### DB_USERNAME
```env
DB_USERNAME=root
```
- **描述**: 数据库用户名
- **用途**: 数据库认证用户名

### DB_PASSWORD
```env
DB_PASSWORD=your_secure_password
```
- **描述**: 数据库密码
- **用途**: 数据库认证密码

### DB_SOCKET
```env
DB_SOCKET=/var/run/mysqld/mysqld.sock
```
- **描述**: Unix套接字路径
- **用途**: 通过套接字连接数据库

### DB_CHARSET
```env
DB_CHARSET=utf8mb4
```
- **描述**: 数据库字符集
- **默认值**: `utf8mb4`
- **用途**: 数据编码格式

### DB_COLLATION
```env
DB_COLLATION=utf8mb4_unicode_ci
```
- **描述**: 数据库排序规则
- **默认值**: `utf8mb4_unicode_ci`
- **用途**: 字符串排序和比较

### DB_PREFIX
```env
DB_PREFIX=
```
- **描述**: 表前缀
- **用途**: 为所有表名添加前缀

### DB_STRICT
```env
DB_STRICT=true
```
- **描述**: 严格模式
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 启用MySQL严格模式

### DB_ENGINE
```env
DB_ENGINE=InnoDB
```
- **描述**: 数据库引擎
- **默认值**: `null`
- **用途**: 指定表引擎

### DB_POOL_MIN
```env
DB_POOL_MIN=5
```
- **描述**: 连接池最小连接数
- **默认值**: `5`
- **用途**: 数据库连接池配置

### DB_POOL_MAX
```env
DB_POOL_MAX=25
```
- **描述**: 连接池最大连接数
- **默认值**: `25`
- **用途**: 数据库连接池配置

### DB_SSL_MODE
```env
DB_SSL_MODE=disabled
```
- **描述**: SSL连接模式
- **可选值**: `disabled`, `required`, `verify_ca`, `verify_identity`
- **默认值**: `disabled`
- **用途**: 数据库SSL连接

### DB_SSL_CA
```env
DB_SSL_CA=/path/to/ca-cert.pem
```
- **描述**: SSL证书路径
- **用途**: SSL连接证书验证

## 缓存配置

### CACHE_DRIVER
```env
CACHE_DRIVER=redis
```
- **描述**: 缓存驱动类型
- **可选值**: `apc`, `array`, `database`, `file`, `memcached`, `redis`, `dynamodb`
- **默认值**: `file`
- **用途**: 指定缓存存储方式

### CACHE_PREFIX
```env
CACHE_PREFIX=webman_filament_cache
```
- **描述**: 缓存键前缀
- **用途**: 防止缓存键冲突

### REDIS_HOST
```env
REDIS_HOST=127.0.0.1
```
- **描述**: Redis主机地址
- **默认值**: `127.0.0.1`
- **用途**: Redis连接地址

### REDIS_PASSWORD
```env
REDIS_PASSWORD=null
```
- **描述**: Redis密码
- **默认值**: `null`
- **用途**: Redis认证密码

### REDIS_PORT
```env
REDIS_PORT=6379
```
- **描述**: Redis端口
- **默认值**: `6379`
- **用途**: Redis连接端口

### REDIS_DB
```env
REDIS_DB=0
```
- **描述**: Redis数据库编号
- **默认值**: `0`
- **用途**: 选择Redis数据库

### REDIS_CACHE_DB
```env
REDIS_CACHE_DB=1
```
- **描述**: 缓存专用数据库
- **默认值**: `1`
- **用途**: 缓存数据存储数据库

### REDIS_SESSION_DB
```env
REDIS_SESSION_DB=2
```
- **描述**: 会话专用数据库
- **默认值**: `2`
- **用途**: 会话数据存储数据库

### REDIS_QUEUE_DB
```env
REDIS_QUEUE_DB=3
```
- **描述**: 队列专用数据库
- **默认值**: `3`
- **用途**: 队列数据存储数据库

### REDIS_CLUSTER
```env
REDIS_CLUSTER=false
```
- **描述**: Redis集群模式
- **可选值**: `true`, `false`
- **默认值**: `false`
- **用途**: 启用Redis集群

### MEMCACHED_HOST
```env
MEMCACHED_HOST=127.0.0.1
```
- **描述**: Memcached主机
- **默认值**: `127.0.0.1`
- **用途**: Memcached连接地址

### MEMCACHED_PORT
```env
MEMCACHED_PORT=11211
```
- **描述**: Memcached端口
- **默认值**: `11211`
- **用途**: Memcached连接端口

## 会话配置

### SESSION_DRIVER
```env
SESSION_DRIVER=redis
```
- **描述**: 会话驱动类型
- **可选值**: `file`, `cookie`, `database`, `apc`, `memcached`, `redis`, `array`
- **默认值**: `file`
- **用途**: 指定会话存储方式

### SESSION_LIFETIME
```env
SESSION_LIFETIME=120
```
- **描述**: 会话生命周期（分钟）
- **默认值**: `120`
- **用途**: 会话过期时间

### SESSION_ENCRYPT
```env
SESSION_ENCRYPT=false
```
- **描述**: 会话数据加密
- **可选值**: `true`, `false`
- **默认值**: `false`
- **用途**: 加密会话数据

### SESSION_PATH
```env
SESSION_PATH=/
```
- **描述**: 会话Cookie路径
- **默认值**: `/`
- **用途**: Cookie有效路径

### SESSION_DOMAIN
```env
SESSION_DOMAIN=null
```
- **描述**: 会话Cookie域名
- **默认值**: `null`
- **用途**: Cookie有效域名

### SESSION_SECURE_COOKIE
```env
SESSION_SECURE_COOKIE=false
```
- **描述**: 安全Cookie
- **可选值**: `true`, `false`
- **默认值**: `false`
- **注意**: 生产环境建议设置为 `true`

### SESSION_HTTP_ONLY
```env
SESSION_HTTP_ONLY=true
```
- **描述**: HTTP Only Cookie
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 防止JavaScript访问Cookie

### SESSION_SAME_SITE
```env
SESSION_SAME_SITE=lax
```
- **描述**: SameSite属性
- **可选值**: `lax`, `strict`, `none`
- **默认值**: `lax`
- **用途**: CSRF保护

### SESSION_STORE
```env
SESSION_STORE=null
```
- **描述**: 会话存储名称
- **默认值**: `null`
- **用途**: 指定会话存储

### SESSION_CONNECTION
```env
SESSION_CONNECTION=null
```
- **描述**: 会话数据库连接
- **默认值**: `null`
- **用途**: 数据库会话连接

## 邮件配置

### MAIL_MAILER
```env
MAIL_MAILER=smtp
```
- **描述**: 邮件发送驱动
- **可选值**: `smtp`, `sendmail`, `mail`, `ses`, `mailgun`, `postmark`, `log`, `array`
- **默认值**: `smtp`
- **用途**: 指定邮件发送方式

### MAIL_HOST
```env
MAIL_HOST=smtp.gmail.com
```
- **描述**: SMTP服务器地址
- **用途**: 邮件发送服务器

### MAIL_PORT
```env
MAIL_PORT=587
```
- **描述**: SMTP端口
- **默认值**: `587`
- **用途**: 邮件服务器端口

### MAIL_USERNAME
```env
MAIL_USERNAME=your-email@gmail.com
```
- **描述**: SMTP用户名
- **用途**: 邮件认证用户名

### MAIL_PASSWORD
```env
MAIL_PASSWORD=your-app-password
```
- **描述**: SMTP密码
- **用途**: 邮件认证密码

### MAIL_ENCRYPTION
```env
MAIL_ENCRYPTION=tls
```
- **描述**: 邮件加密方式
- **可选值**: `tls`, `ssl`, `null`
- **默认值**: `null`
- **用途**: 邮件传输加密

### MAIL_FROM_ADDRESS
```env
MAIL_FROM_ADDRESS=noreply@your-domain.com
```
- **描述**: 发件人邮箱地址
- **用途**: 默认发件地址

### MAIL_FROM_NAME
```env
MAIL_FROM_NAME="Webman Filament Admin"
```
- **描述**: 发件人名称
- **用途**: 默认发件人名称

### MAIL_PRETEND
```env
MAIL_PRETEND=false
```
- **描述**: 模拟邮件发送
- **可选值**: `true`, `false`
- **默认值**: `false`
- **用途**: 开发环境记录邮件而不发送

## 文件存储配置

### FILESYSTEM_DISK
```env
FILESYSTEM_DISK=local
```
- **描述**: 默认文件系统驱动
- **可选值**: `local`, `public`, `s3`, `ftp`, `sftp`
- **默认值**: `local`
- **用途**: 指定文件存储方式

### AWS_ACCESS_KEY_ID
```env
AWS_ACCESS_KEY_ID=your-access-key
```
- **描述**: AWS访问密钥ID
- **用途**: AWS S3认证

### AWS_SECRET_ACCESS_KEY
```env
AWS_SECRET_ACCESS_KEY=your-secret-key
```
- **描述**: AWS秘密访问密钥
- **用途**: AWS S3认证

### AWS_DEFAULT_REGION
```env
AWS_DEFAULT_REGION=us-east-1
```
- **描述**: AWS默认区域
- **默认值**: `us-east-1`
- **用途**: AWS服务区域

### AWS_BUCKET
```env
AWS_BUCKET=your-bucket-name
```
- **描述**: S3存储桶名称
- **用途**: S3文件存储

### AWS_URL
```env
AWS_URL=https://your-bucket.s3.amazonaws.com
```
- **描述**: S3自定义端点URL
- **用途**: 自定义S3端点

### AWS_ENDPOINT
```env
AWS_ENDPOINT=null
```
- **描述**: S3服务终端节点
- **默认值**: `null`
- **用途**: 自定义S3兼容服务

### AWS_USE_PATH_STYLE_ENDPOINT
```env
AWS_USE_PATH_STYLE_ENDPOINT=false
```
- **描述**: 使用路径样式端点
- **可选值**: `true`, `false`
- **默认值**: `false`
- **用途**: S3兼容服务配置

## 队列配置

### QUEUE_CONNECTION
```env
QUEUE_CONNECTION=redis
```
- **描述**: 队列连接驱动
- **可选值**: `sync`, `database`, `beanstalkd`, `sqs`, `redis`, `null`
- **默认值**: `sync`
- **用途**: 指定队列处理方式

### QUEUE_FAILED_DRIVER
```env
QUEUE_FAILED_DRIVER=database
```
- **描述**: 失败队列驱动
- **可选值**: `database`, `dynamodb`, `null`
- **默认值**: `null`
- **用途**: 失败任务存储

### HORIZON_ENABLED
```env
HORIZON_ENABLED=true
```
- **描述**: Laravel Horizon启用
- **可选值**: `true`, `false`
- **默认值**: `false`
- **用途**: 队列监控面板

### HORIZON_REDIS_CONNECTION
```env
HORIZON_REDIS_CONNECTION=horizon
```
- **描述**: Horizon Redis连接
- **默认值**: `default`
- **用途**: Horizon数据存储

## 认证配置

### AUTH_GUARD
```env
AUTH_GUARD=web
```
- **描述**: 默认认证守卫
- **可选值**: `web`, `api`
- **默认值**: `web`
- **用途**: 指定认证方式

### AUTH_PROVIDER
```env
AUTH_PROVIDER=users
```
- **描述**: 认证用户提供器
- **默认值**: `users`
- **用途**: 指定用户模型

### SANCTUM_STATEFUL_DOMAINS
```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000
```
- **描述**: Sanctum有状态域名
- **用途**: API认证域名白名单

### SANCTUM_EXPIRATION
```env
SANCTUM_EXPIRATION=null
```
- **描述**: API令牌过期时间（分钟）
- **默认值**: `null`
- **用途**: API令牌有效期

### OAUTH_CLIENT_ID
```env
OAUTH_CLIENT_ID=your-client-id
```
- **描述**: OAuth客户端ID
- **用途**: 第三方登录认证

### OAUTH_CLIENT_SECRET
```env
OAUTH_CLIENT_SECRET=your-client-secret
```
- **描述**: OAuth客户端密钥
- **用途**: 第三方登录认证

### OAUTH_REDIRECT_URI
```env
OAUTH_REDIRECT_URI=https://your-domain.com/auth/callback
```
- **描述**: OAuth回调地址
- **用途**: 第三方登录回调

### GOOGLE_CLIENT_ID
```env
GOOGLE_CLIENT_ID=your-google-client-id
```
- **描述**: Google OAuth客户端ID
- **用途**: Google登录

### GOOGLE_CLIENT_SECRET
```env
GOOGLE_CLIENT_SECRET=your-google-client-secret
```
- **描述**: Google OAuth客户端密钥
- **用途**: Google登录

### GITHUB_CLIENT_ID
```env
GITHUB_CLIENT_ID=your-github-client-id
```
- **描述**: GitHub OAuth客户端ID
- **用途**: GitHub登录

### GITHUB_CLIENT_SECRET
```env
GITHUB_CLIENT_SECRET=your-github-client-secret
```
- **描述**: GitHub OAuth客户端密钥
- **用途**: GitHub登录

## 安全配置

### BCRYPT_ROUNDS
```env
BCRYPT_ROUNDS=12
```
- **描述**: 密码哈希强度
- **默认值**: `10`
- **范围**: `4-31`
- **用途**: 密码加密强度

### PASSWORD_MIN_LENGTH
```env
PASSWORD_MIN_LENGTH=8
```
- **描述**: 密码最小长度
- **默认值**: `8`
- **用途**: 密码策略

### PASSWORD_REQUIRE_UPPERCASE
```env
PASSWORD_REQUIRE_UPPERCASE=true
```
- **描述**: 密码必须包含大写字母
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 密码策略

### PASSWORD_REQUIRE_LOWERCASE
```env
PASSWORD_REQUIRE_LOWERCASE=true
```
- **描述**: 密码必须包含小写字母
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 密码策略

### PASSWORD_REQUIRE_NUMBERS
```env
PASSWORD_REQUIRE_NUMBERS=true
```
- **描述**: 密码必须包含数字
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 密码策略

### PASSWORD_REQUIRE_SYMBOLS
```env
PASSWORD_REQUIRE_SYMBOLS=true
```
- **描述**: 密码必须包含特殊字符
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 密码策略

### RATE_LIMIT_ENABLED
```env
RATE_LIMIT_ENABLED=true
```
- **描述**: 速率限制启用
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: API访问控制

### SECURITY_HEADERS_ENABLED
```env
SECURITY_HEADERS_ENABLED=true
```
- **描述**: 安全头部启用
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: HTTP安全头部

### CSP_ENABLED
```env
CSP_ENABLED=true
```
- **描述**: 内容安全策略启用
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: XSS防护

### HSTS_ENABLED
```env
HSTS_ENABLED=true
```
- **描述**: HSTS启用
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: HTTPS强制

### TWO_FACTOR_ENABLED
```env
TWO_FACTOR_ENABLED=true
```
- **描述**: 双因子认证启用
- **可选值**: `true`, `false`
- **默认值**: `false`
- **用途**: 增强认证安全

### AUDIT_ENABLED
```env
AUDIT_ENABLED=true
```
- **描述**: 审计日志启用
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 安全审计

## 监控配置

### MONITORING_ENABLED
```env
MONITORING_ENABLED=true
```
- **描述**: 监控启用
- **可选值**: `true`, `false`
- **默认值**: `false`
- **用途**: 系统监控

### LOG_CHANNEL
```env
LOG_CHANNEL=stack
```
- **描述**: 日志通道
- **可选值**: `stack`, `single`, `daily`, `slack`, `papertrail`, `stderr`, `syslog`, `errorlog`, `null`, `emergency`
- **默认值**: `stack`
- **用途**: 日志输出方式

### LOG_LEVEL
```env
LOG_LEVEL=debug
```
- **描述**: 日志级别
- **可选值**: `debug`, `info`, `notice`, `warning`, `error`, `critical`, `alert`, `emergency`
- **默认值**: `debug`
- **用途**: 日志详细程度

### LOG_SLACK_WEBHOOK_URL
```env
LOG_SLACK_WEBHOOK_URL=https://hooks.slack.com/services/...
```
- **描述**: Slack日志Webhook URL
- **用途**: Slack日志通知

### SENTRY_LARAVEL_DSN
```env
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io/project-id
```
- **描述**: Sentry错误追踪DSN
- **用途**: 错误监控和追踪

### NEW_RELIC_LICENSE_KEY
```env
NEW_RELIC_LICENSE_KEY=your-new-relic-key
```
- **描述**: New Relic监控密钥
- **用途**: 应用性能监控

## 第三方服务配置

### STRIPE_KEY
```env
STRIPE_KEY=pk_test_your_publishable_key
```
- **描述**: Stripe公钥
- **用途**: 支付处理

### STRIPE_SECRET
```env
STRIPE_SECRET=sk_test_your_secret_key
```
- **描述**: Stripe密钥
- **用途**: 支付处理

### STRIPE_WEBHOOK_SECRET
```env
STRIPE_WEBHOOK_SECRET=whsec_your_webhook_secret
```
- **描述**: Stripe Webhook密钥
- **用途**: 支付事件验证

### PAYPAL_CLIENT_ID
```env
PAYPAL_CLIENT_ID=your-paypal-client-id
```
- **描述**: PayPal客户端ID
- **用途**: PayPal支付

### PAYPAL_CLIENT_SECRET
```env
PAYPAL_CLIENT_SECRET=your-paypal-client-secret
```
- **描述**: PayPal客户端密钥
- **用途**: PayPal支付

### TWILIO_SID
```env
TWILIO_SID=your-twilio-account-sid
```
- **描述**: Twilio账户SID
- **用途**: 短信发送

### TWILIO_TOKEN
```env
TWILIO_TOKEN=your-twilio-auth-token
```
- **描述**: Twilio认证令牌
- **用途**: 短信发送

### TWILIO_FROM
```env
TWILIO_FROM=+1234567890
```
- **描述**: Twilio发送号码
- **用途**: 短信发送

### VONAGE_API_KEY
```env
VONAGE_API_KEY=your-vonage-api-key
```
- **描述**: Vonage API密钥
- **用途**: 短信发送

### VONAGE_API_SECRET
```env
VONAGE_API_SECRET=your-vonage-api-secret
```
- **描述**: Vonage API密钥
- **用途**: 短信发送

### PUSHER_APP_ID
```env
PUSHER_APP_ID=your-pusher-app-id
```
- **描述**: Pusher应用ID
- **用途**: 实时通知

### PUSHER_APP_KEY
```env
PUSHER_APP_KEY=your-pusher-app-key
```
- **描述**: Pusher应用密钥
- **用途**: 实时通知

### PUSHER_APP_SECRET
```env
PUSHER_APP_SECRET=your-pusher-app-secret
```
- **描述**: Pusher应用秘密
- **用途**: 实时通知

### PUSHER_APP_CLUSTER
```env
PUSHER_APP_CLUSTER=mt1
```
- **描述**: Pusher集群
- **默认值**: `mt1`
- **用途**: 实时通知

### PUSHER_BEAMS_INSTANCE_ID
```env
PUSHER_BEAMS_INSTANCE_ID=your-beams-instance-id
```
- **描述**: Pusher Beams实例ID
- **用途**: 推送通知

### PUSHER_BEAMS_SECRET_KEY
```env
PUSHER_BEAMS_SECRET_KEY=your-beams-secret-key
```
- **描述**: Pusher Beams密钥
- **用途**: 推送通知

## 开发和测试配置

### DEBUGBAR_ENABLED
```env
DEBUGBAR_ENABLED=false
```
- **描述**: DebugBar启用
- **可选值**: `true`, `false`
- **默认值**: `false`
- **用途**: 开发调试工具

### TELESCOPE_ENABLED
```env
TELESCOPE_ENABLED=false
```
- **描述**: Telescope启用
- **可选值**: `true`, `false`
- **默认值**: `false`
- **用途**: 开发监控工具

### DUSK_DRIVER
```env
DUSK_DRIVER=chrome
```
- **描述**: Dusk浏览器驱动
- **可选值**: `chrome`, `firefox`
- **默认值**: `chrome`
- **用途**: 浏览器测试

### DUSK_HEADLESS
```env
DUSK_HEADLESS=true
```
- **描述**: Dusk无头模式
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 自动化测试

### DUSK_WINDOW_SIZE
```env
DUSK_WINDOW_SIZE=1920,1080
```
- **描述**: Dusk窗口大小
- **默认值**: `1920,1080`
- **用途**: 自动化测试

## 性能优化配置

### ASSET_VERSIONING
```env
ASSET_VERSIONING=true
```
- **描述**: 资源版本控制
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 浏览器缓存控制

### CSS_COMPRESSION
```env
CSS_COMPRESSION=true
```
- **描述**: CSS压缩
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 前端性能优化

### JS_COMPRESSION
```env
JS_COMPRESSION=true
```
- **描述**: JavaScript压缩
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 前端性能优化

### IMAGE_COMPRESSION
```env
IMAGE_COMPRESSION=true
```
- **描述**: 图片压缩
- **可选值**: `true`, `false`
- **默认值**: `true`
- **用途**: 图片优化

### CDN_ENABLED
```env
CDN_ENABLED=false
```
- **描述**: CDN启用
- **可选值**: `true`, `false`
- **默认值**: `false`
- **用途**: 静态资源加速

### CDN_DOMAIN
```env
CDN_DOMAIN=cdn.your-domain.com
```
- **描述**: CDN域名
- **用途**: 静态资源CDN地址

## 配置示例

### 开发环境配置
```env
# .env.development
APP_NAME="Webman Filament Admin"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8787
APP_KEY=base64:development-key-32-chars

# 数据库
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webman_filament_dev
DB_USERNAME=root
DB_PASSWORD=

# 缓存
CACHE_DRIVER=file
SESSION_DRIVER=file

# 邮件
MAIL_MAILER=log

# 开发工具
DEBUGBAR_ENABLED=true
TELESCOPE_ENABLED=true
```

### 生产环境配置
```env
# .env.production
APP_NAME="Webman Filament Admin"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
APP_KEY=base64:production-key-32-chars

# 数据库
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=webman_filament
DB_USERNAME=webman_user
DB_PASSWORD=secure_password_here

# Redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=redis_password
REDIS_DB=0

# 邮件
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=noreply@your-domain.com
MAIL_PASSWORD=app_specific_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="Webman Filament Admin"

# 安全
BCRYPT_ROUNDS=12
PASSWORD_MIN_LENGTH=8
RATE_LIMIT_ENABLED=true
SECURITY_HEADERS_ENABLED=true

# 监控
LOG_LEVEL=info
SENTRY_LARAVEL_DSN=https://your-sentry-dsn@sentry.io/project-id

# CDN
CDN_ENABLED=true
CDN_DOMAIN=cdn.your-domain.com
```

### 测试环境配置
```env
# .env.testing
APP_NAME="Webman Filament Admin"
APP_ENV=testing
APP_DEBUG=true
APP_URL=http://localhost
APP_KEY=base64:testing-key-32-chars

# 测试数据库
DB_CONNECTION=sqlite
DB_DATABASE=:memory:

# 缓存
CACHE_DRIVER=array
SESSION_DRIVER=array
QUEUE_CONNECTION=sync

# 邮件
MAIL_MAILER=array

# 测试配置
DUSK_HEADLESS=true
TELESCOPE_ENABLED=false
```

## 配置验证

### 配置检查命令
```bash
# 检查应用配置
php artisan config:show

# 验证环境变量
php artisan env:show

# 检查数据库连接
php artisan migrate:status

# 测试邮件配置
php artisan tinker
Mail::raw('Test email', function($msg) {
    $msg->to('test@example.com')->subject('Test');
});
```

### 配置验证脚本
```php
<?php
// config/validate.php

return [
    'required' => [
        'APP_KEY',
        'APP_URL',
        'DB_CONNECTION',
        'DB_HOST',
        'DB_DATABASE',
        'DB_USERNAME',
    ],
    'optional' => [
        'DB_PASSWORD',
        'REDIS_HOST',
        'MAIL_HOST',
        'MAIL_USERNAME',
        'MAIL_PASSWORD',
    ],
    'validation' => [
        'APP_ENV' => ['local', 'development', 'testing', 'staging', 'production'],
        'DB_CONNECTION' => ['mysql', 'pgsql', 'sqlite', 'sqlsrv'],
        'CACHE_DRIVER' => ['apc', 'array', 'database', 'file', 'memcached', 'redis'],
        'SESSION_DRIVER' => ['file', 'cookie', 'database', 'apc', 'memcached', 'redis', 'array'],
        'MAIL_MAILER' => ['smtp', 'sendmail', 'mail', 'ses', 'mailgun', 'postmark', 'log', 'array'],
    ],
];
```

## 最佳实践

### 1. 环境隔离
- 不同环境使用不同的配置文件
- 敏感信息不硬编码在代码中
- 定期更新密钥和密码

### 2. 安全配置
- 生产环境关闭调试模式
- 使用强密码和复杂密钥
- 启用安全头部和HTTPS

### 3. 性能优化
- 生产环境启用缓存
- 使用Redis等高性能存储
- 配置CDN加速静态资源

### 4. 监控告警
- 配置适当的日志级别
- 启用错误监控服务
- 设置关键指标告警

### 5. 备份恢复
- 定期备份配置文件
- 测试配置恢复流程
- 文档化配置变更

## 故障排除

### 常见配置问题

1. **APP_KEY未设置**
   ```bash
   php artisan key:generate
   ```

2. **数据库连接失败**
   ```bash
   php artisan migrate:status
   # 检查数据库配置
   ```

3. **缓存配置错误**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

4. **邮件配置问题**
   ```bash
   php artisan tinker
   Mail::raw('Test', function($msg) {
       $msg->to('test@example.com')->subject('Test');
   });
   ```

5. **权限问题**
   ```bash
   chmod -R 755 storage
   chmod -R 755 bootstrap/cache
   ```

### 配置调试命令
```bash
# 查看所有配置
php artisan config:show

# 查看特定配置
php artisan config:show database.connections.mysql

# 验证配置
php artisan config:validate

# 清除配置缓存
php artisan config:clear

# 重新生成配置缓存
php artisan config:cache
```