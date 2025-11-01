# 日志和调试指南

本文档提供系统日志管理和调试的完整指南，帮助您有效排查和解决问题。

## 目录

- [日志系统概述](#日志系统概述)
- [日志配置](#日志配置)
- [日志分析](#日志分析)
- [调试工具](#调试工具)
- [错误处理](#错误处理)
- [性能分析](#性能分析)
- [监控和告警](#监控和告警)
- [最佳实践](#最佳实践)

## 日志系统概述

### 1. 日志类型

**系统日志**:
- `/var/log/syslog` - 系统级日志
- `/var/log/messages` - 通用系统消息
- `/var/log/auth.log` - 认证相关日志
- `/var/log/kern.log` - 内核日志

**应用日志**:
- `/var/log/nginx/access.log` - Nginx访问日志
- `/var/log/nginx/error.log` - Nginx错误日志
- `/var/log/php_errors.log` - PHP错误日志
- `/var/log/mysql/error.log` - MySQL错误日志

**自定义应用日志**:
- `storage/logs/laravel.log` - Laravel应用日志
- `var/log/app.log` - 自定义应用日志

### 2. 日志级别

**标准日志级别** (RFC 5424):
- **EMERGENCY** (0): 系统不可用
- **ALERT** (1): 必须立即采取行动
- **CRITICAL** (2): 严重情况
- **ERROR** (3): 错误情况
- **WARNING** (4): 警告情况
- **NOTICE** (5): 正常但重要的情况
- **INFO** (6): 信息性消息
- **DEBUG** (7): 调试级别消息

### 3. 日志格式

**结构化日志格式**:
```json
{
    "timestamp": "2025-11-01T11:08:24Z",
    "level": "ERROR",
    "message": "Database connection failed",
    "context": {
        "host": "db.example.com",
        "port": 3306,
        "error_code": 2002
    },
    "source": "app.database",
    "request_id": "req_123456"
}
```

**传统日志格式**:
```
2025-11-01 11:08:24 ERROR Database connection failed (host: db.example.com, port: 3306)
```

## 日志配置

### 1. 系统日志配置

**rsyslog配置** (`/etc/rsyslog.conf`):
```bash
# 启用UDP接收
$ModLoad imudp
$UDPServerRun 514

# 配置日志规则
*.info;mail.none;authpriv.none;cron.none /var/log/messages
authpriv.* /var/log/secure
mail.* /var/log/maillog
cron.* /var/log/cron
*.emerg :omusrmsg:*
```

**logrotate配置** (`/etc/logrotate.conf`):
```bash
# 全局配置
daily
rotate 7
compress
delaycompress
missingok
notifempty
create 0644 root adm

# 应用特定配置
/var/log/nginx/*.log {
    daily
    rotate 14
    compress
    delaycompress
    missingok
    notifempty
    sharedscripts
    postrotate
        /bin/kill -USR1 `cat /run/nginx.pid 2>/dev/null` 2>/dev/null || true
    endscript
}
```

### 2. PHP日志配置

**php.ini配置**:
```ini
; 错误日志配置
log_errors = On
error_log = /var/log/php_errors.log
error_reporting = E_ALL
display_errors = Off
display_startup_errors = Off

; 慢查询日志
slow_query_log = On
slow_query_log_file = /var/log/php_slow.log
long_query_time = 2.0
```

**Laravel日志配置** (`config/logging.php`):
```php
<?php

return [
    'default' => env('LOG_CHANNEL', 'stack'),
    
    'channels' => [
        'stack' => [
            'driver' => 'stack',
            'channels' => ['daily', 'slack'],
            'ignore_exceptions' => false,
        ],
        
        'daily' => [
            'driver' => 'daily',
            'path' => storage_path('logs/laravel.log'),
            'level' => 'debug',
            'days' => 14,
        ],
        
        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => 'Laravel Log',
            'emoji' => ':boom:',
            'level' => 'critical',
        ],
    ],
];
```

### 3. 应用日志配置

**自定义日志记录器** (`src/Support/Logger.php`):
```php
<?php

namespace App\Support;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;
use Monolog\Formatter\JsonFormatter;

class Logger
{
    protected static $loggers = [];

    public static function get($name = 'app')
    {
        if (!isset(self::$loggers[$name])) {
            self::$loggers[$name] = self::createLogger($name);
        }

        return self::$loggers[$name];
    }

    protected static function createLogger($name)
    {
        $logger = new MonologLogger($name);
        
        // 添加文件处理器
        $fileHandler = new RotatingFileHandler(
            storage_path("logs/{$name}.log"),
            30, // 保留30天
            MonologLogger::DEBUG
        );
        
        // 设置JSON格式
        $formatter = new JsonFormatter();
        $fileHandler->setFormatter($formatter);
        
        $logger->pushHandler($fileHandler);
        
        // 添加控制台处理器（开发环境）
        if (app()->environment('local')) {
            $consoleHandler = new StreamHandler('php://stdout', MonologLogger::DEBUG);
            $consoleHandler->setFormatter(new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context%\n"
            ));
            $logger->pushHandler($consoleHandler);
        }

        return $logger;
    }

    public static function info($message, array $context = [])
    {
        self::get()->info($message, $context);
    }

    public static function error($message, array $context = [])
    {
        self::get()->error($message, $context);
    }

    public static function warning($message, array $context = [])
    {
        self::get()->warning($message, $context);
    }

    public static function debug($message, array $context = [])
    {
        self::get()->debug($message, $context);
    }
}
```

## 日志分析

### 1. 日志查看命令

**基础查看命令**:
```bash
# 查看日志文件
cat /var/log/app.log

# 实时查看日志
tail -f /var/log/app.log

# 查看最后100行
tail -100 /var/log/app.log

# 查看日志文件头部
head -50 /var/log/app.log

# 分页查看
less /var/log/app.log
```

**搜索和过滤**:
```bash
# 搜索特定关键词
grep "ERROR" /var/log/app.log

# 搜索多个关键词
grep -E "(ERROR|WARN)" /var/log/app.log

# 搜索排除某些行
grep -v "DEBUG" /var/log/app.log

# 显示行号
grep -n "ERROR" /var/log/app.log

# 搜索特定时间范围
grep "2025-11-01 10:00" /var/log/app.log
```

**高级过滤**:
```bash
# 按时间范围过滤
awk '$1 >= "2025-11-01 10:00:00" && $1 <= "2025-11-01 11:00:00"' /var/log/app.log

# 按日志级别过滤
awk '$3 == "ERROR"' /var/log/app.log

# 统计错误数量
grep -c "ERROR" /var/log/app.log

# 提取错误信息
grep "ERROR" /var/log/app.log | awk '{print $4}'
```

### 2. 日志分析脚本

**日志统计分析** (`scripts/analyze_logs.sh`):
```bash
#!/bin/bash
# 日志分析脚本

LOG_FILE="${1:-/var/log/app.log}"
OUTPUT_FILE="${2:-/tmp/log_analysis.txt}"

echo "=== 日志分析报告 ===" > $OUTPUT_FILE
echo "分析时间: $(date)" >> $OUTPUT_FILE
echo "日志文件: $LOG_FILE" >> $OUTPUT_FILE
echo "" >> $OUTPUT_FILE

# 统计总行数
echo "=== 基本统计 ===" >> $OUTPUT_FILE
echo "总行数: $(wc -l < $LOG_FILE)" >> $OUTPUT_FILE

# 按日志级别统计
echo "" >> $OUTPUT_FILE
echo "=== 日志级别统计 ===" >> $OUTPUT_FILE
grep -oP '\[\K[^\]]+' $LOG_FILE | sort | uniq -c | sort -nr >> $OUTPUT_FILE

# 错误趋势分析
echo "" >> $OUTPUT_FILE
echo "=== 错误趋势分析 ===" >> $OUTPUT_FILE
echo "最近1小时错误数: $(grep "$(date +%Y-%m-%d\ %H):" $LOG_FILE | grep -c ERROR)" >> $OUTPUT_FILE
echo "最近24小时错误数: $(grep "$(date +%Y-%m-%d)" $LOG_FILE | grep -c ERROR)" >> $OUTPUT_FILE

# 常见错误
echo "" >> $OUTPUT_FILE
echo "=== 常见错误 ===" >> $OUTPUT_FILE
grep "ERROR" $LOG_FILE | awk '{print $4}' | sort | uniq -c | sort -nr | head -10 >> $OUTPUT_FILE

# IP访问统计
echo "" >> $OUTPUT_FILE
echo "=== IP访问统计 ===" >> $OUTPUT_FILE
grep -oP '\d+\.\d+\.\d+\.\d+' $LOG_FILE | sort | uniq -c | sort -nr | head -10 >> $OUTPUT_FILE

echo "分析完成，结果保存到: $OUTPUT_FILE"
```

**实时日志监控** (`scripts/monitor_logs.sh`):
```bash
#!/bin/bash
# 实时日志监控脚本

LOG_FILE="${1:-/var/log/app.log}"
ALERT_EMAIL="${2:-admin@example.com}"

# 创建临时文件
ERROR_COUNT_FILE="/tmp/error_count.txt"
ALERT_THRESHOLD=10

# 初始化错误计数
echo "0" > $ERROR_COUNT_FILE

# 监控函数
monitor_logs() {
    tail -f $LOG_FILE | while read line; do
        if echo "$line" | grep -q "ERROR"; then
            ERROR_COUNT=$(cat $ERROR_COUNT_FILE)
            ERROR_COUNT=$((ERROR_COUNT + 1))
            echo "$ERROR_COUNT" > $ERROR_COUNT_FILE
            
            # 检查是否超过阈值
            if [ $ERROR_COUNT -ge $ALERT_THRESHOLD ]; then
                echo "警告: 检测到大量错误日志" | mail -s "错误告警" $ALERT_EMAIL
                echo "0" > $ERROR_COUNT_FILE
            fi
        fi
        
        # 记录时间戳
        if echo "$line" | grep -q "WARNING"; then
            echo "$(date): WARNING detected - $line" >> /var/log/warning_monitor.log
        fi
    done
}

# 启动监控
monitor_logs
```

### 3. 日志聚合和分析

**使用ELK Stack**:
```yaml
# docker-compose.yml
version: '3'
services:
  elasticsearch:
    image: elasticsearch:7.14.0
    environment:
      - discovery.type=single-node
    ports:
      - "9200:9200"

  logstash:
    image: logstash:7.14.0
    volumes:
      - ./logstash.conf:/usr/share/logstash/pipeline/logstash.conf
    ports:
      - "5044:5044"

  kibana:
    image: kibana:7.14.0
    ports:
      - "5601:5601"
```

**Logstash配置** (`logstash.conf`):
```ruby
input {
  beats {
    port => 5044
  }
}

filter {
  if [fields][log_type] == "application" {
    grok {
      match => { 
        "message" => "%{TIMESTAMP_ISO8601:timestamp} %{LOGLEVEL:level} %{GREEDYDATA:message}" 
      }
    }
    
    date {
      match => [ "timestamp", "yyyy-MM-dd HH:mm:ss" ]
    }
    
    mutate {
      add_field => { "application" => "myapp" }
    }
  }
}

output {
  elasticsearch {
    hosts => ["elasticsearch:9200"]
    index => "logs-%{+YYYY.MM.dd}"
  }
}
```

## 调试工具

### 1. PHP调试工具

**Xdebug配置**:
```ini
; php.ini
[xdebug]
zend_extension=xdebug.so

; 基本调试配置
xdebug.mode=debug
xdebug.start_with_request=yes
xdebug.client_host=localhost
xdebug.client_port=9003

; 性能分析配置
xdebug.mode=profile
xdebug.output_dir=/tmp/xdebug_profiles
xdebug.profiler_output_name=cachegrind.out.%p

; 远程调试配置
xdebug.remote_enable=1
xdebug.remote_autostart=1
xdebug.remote_connect_back=1
```

**使用Xdebug**:
```bash
# 生成性能分析报告
php -dxdebug.mode=profile script.php

# 查看性能分析文件
kcachegrind /tmp/xdebug_profiles/cachegrind.out.*
```

**Blackfire配置**:
```bash
# 安装Blackfire
curl -L https://blackfire.io/installer | bash

# 配置凭据
blackfire config --id=YOUR_ID --token=YOUR_TOKEN

# 分析性能
blackfire curl https://example.com

# 生成性能报告
blackfire run php script.php
```

### 2. 数据库调试

**MySQL慢查询分析**:
```sql
-- 启用慢查询日志
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 2;
SET GLOBAL log_queries_not_using_indexes = 'ON';

-- 查看慢查询
SELECT * FROM mysql.slow_log 
ORDER BY start_time DESC 
LIMIT 10;

-- 分析查询执行计划
EXPLAIN SELECT * FROM users WHERE email = 'test@example.com';

-- 查看进程列表
SHOW PROCESSLIST;

-- 杀死长时间运行的查询
KILL QUERY <process_id>;
```

**PostgreSQL调试**:
```sql
-- 启用查询日志
ALTER SYSTEM SET log_statement = 'all';
ALTER SYSTEM SET log_min_duration_statement = 1000;
SELECT pg_reload_conf();

-- 查看慢查询
SELECT query, mean_time, calls 
FROM pg_stat_statements 
ORDER BY mean_time DESC 
LIMIT 10;

-- 分析查询计划
EXPLAIN (ANALYZE, BUFFERS) SELECT * FROM users WHERE email = 'test@example.com';
```

### 3. Web服务器调试

**Nginx调试**:
```nginx
# 启用详细错误日志
error_log /var/log/nginx/error.log debug;
access_log /var/log/nginx/access.log combined;

# 调试配置
server {
    listen 80;
    server_name example.com;
    
    # 调试变量
    add_header X-Debug-Info $uri always;
    add_header X-Debug-Method $request_method always;
    add_header X-Debug-IP $remote_addr always;
    
    location / {
        # 记录请求体
        access_log /var/log/nginx/request_body.log;
        
        # 调试重写
        rewrite_log on;
        
        proxy_pass http://backend;
    }
}
```

**Apache调试**:
```apache
# 启用详细日志
LogLevel info rewrite:trace3

# 自定义日志格式
LogFormat "%h %l %u %t \"%r\" %>s %b \"%{Referer}i\" \"%{User-Agent}i\" %D" combined
LogFormat "%h %l %u %t \"%r\" %>s %b" common

CustomLog logs/access_log combined
ErrorLog logs/error_log
```

### 4. 网络调试

**网络连接测试**:
```bash
# 测试端口连通性
telnet target_host port

# 使用nc测试
nc -zv target_host port

# 跟踪网络路径
traceroute target_host

# 查看网络连接
netstat -tulpn
ss -tulpn

# 抓包分析
tcpdump -i eth0 host target_host
tcpdump -i eth0 port 80
```

**DNS调试**:
```bash
# DNS解析测试
nslookup target_host
dig target_host

# 查看DNS缓存
systemd-resolve --status
```

## 错误处理

### 1. 全局错误处理

**PHP全局错误处理**:
```php
<?php
// 设置自定义错误处理器
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    $errorTypes = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_NOTICE => 'NOTICE',
        E_USER_ERROR => 'USER_ERROR',
        E_USER_WARNING => 'USER_WARNING',
        E_USER_NOTICE => 'USER_NOTICE',
    ];
    
    $errorType = $errorTypes[$errno] ?? 'UNKNOWN';
    
    $context = [
        'error_type' => $errorType,
        'error_file' => $errfile,
        'error_line' => $errline,
        'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),
    ];
    
    \App\Support\Logger::error($errstr, $context);
    
    // 返回false继续执行标准错误处理器
    return false;
});

// 设置异常处理器
set_exception_handler(function($exception) {
    $context = [
        'exception_class' => get_class($exception),
        'exception_message' => $exception->getMessage(),
        'exception_file' => $exception->getFile(),
        'exception_line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString(),
    ];
    
    \App\Support\Logger::error('Uncaught exception: ' . $exception->getMessage(), $context);
    
    // 发送错误报告
    if (app()->environment('production')) {
        report_exception($exception);
    }
});

// 设置致命错误处理器
register_shutdown_function(function() {
    $error = error_get_last();
    
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $context = [
            'error_type' => 'FATAL_ERROR',
            'error_message' => $error['message'],
            'error_file' => $error['file'],
            'error_line' => $error['line'],
        ];
        
        \App\Support\Logger::error('Fatal error: ' . $error['message'], $context);
    }
});
```

**Laravel异常处理** (`app/Exceptions/Handler.php`):
```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use App\Support\Logger;

class Handler extends ExceptionHandler
{
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
    ];

    public function report(Throwable $exception)
    {
        // 记录异常信息
        $context = [
            'exception_class' => get_class($exception),
            'exception_message' => $exception->getMessage(),
            'exception_file' => $exception->getFile(),
            'exception_line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'request_url' => request()->fullUrl(),
            'request_method' => request()->method(),
            'request_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ];
        
        Logger::error('Application exception: ' . $exception->getMessage(), $context);
        
        // 发送告警（严重异常）
        if ($this->shouldAlert($exception)) {
            $this->sendAlert($exception, $context);
        }
        
        parent::report($exception);
    }

    protected function shouldAlert(Throwable $exception): bool
    {
        return $exception instanceof \ErrorException ||
               $exception instanceof \PDOException ||
               $exception instanceof \Exception;
    }

    protected function sendAlert(Throwable $exception, array $context)
    {
        // 发送邮件或Slack通知
        // 这里实现具体的告警逻辑
    }
}
```

### 2. 错误报告和跟踪

**错误报告工具**:
```php
<?php

class ErrorReporter
{
    public static function reportError($error, $context = [])
    {
        // 收集环境信息
        $environment = [
            'php_version' => PHP_VERSION,
            'os' => php_uname(),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'],
        ];
        
        // 收集请求信息
        $request = [
            'url' => $_SERVER['REQUEST_URI'] ?? '',
            'method' => $_SERVER['REQUEST_METHOD'] ?? '',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'post_data' => $_POST,
            'get_data' => $_GET,
        ];
        
        $fullContext = array_merge($context, [
            'environment' => $environment,
            'request' => $request,
            'timestamp' => date('Y-m-d H:i:s'),
        ]);
        
        // 记录到日志
        Logger::error($error, $fullContext);
        
        // 发送到错误追踪服务
        self::sendToErrorTrackingService($error, $fullContext);
    }
    
    protected static function sendToErrorTrackingService($error, $context)
    {
        // 发送到Sentry、Bugsnag等服务
        // 这里实现具体的集成代码
    }
}
```

## 性能分析

### 1. 应用性能监控

**性能监控中间件** (`app/Http/Middleware/PerformanceMonitor.php`):
```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Support\Logger;

class PerformanceMonitor
{
    public function handle(Request $request, Closure $next)
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        $response = $next($request);
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = $endTime - $startTime;
        $memoryUsage = $endMemory - $startMemory;
        
        $context = [
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'execution_time' => round($executionTime, 4),
            'memory_usage' => $memoryUsage,
            'peak_memory' => memory_get_peak_usage(),
            'response_status' => $response->getStatusCode(),
            'user_id' => auth()->id(),
        ];
        
        // 记录慢请求
        if ($executionTime > 2.0) {
            Logger::warning('Slow request detected', $context);
        }
        
        // 记录内存使用高的请求
        if ($memoryUsage > 50 * 1024 * 1024) { // 50MB
            Logger::warning('High memory usage detected', $context);
        }
        
        return $response;
    }
}
```

**数据库查询监控**:
```php
<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueryMonitor
{
    public static function enable()
    {
        DB::listen(function ($query) {
            $sql = $query->sql;
            $bindings = $query->bindings;
            $time = $query->time;
            
            // 替换绑定参数
            $sql = str_replace(['%', '?'], ['%%', '%s'], $sql);
            $sql = vsprintf($sql, array_map(function ($binding) {
                return is_numeric($binding) ? $binding : "'{$binding}'";
            }, $bindings));
            
            $context = [
                'sql' => $sql,
                'time' => $time,
                'connection' => $query->connectionName,
            ];
            
            // 记录慢查询
            if ($time > 100) {
                Log::warning('Slow database query', $context);
            }
            
            // 记录所有查询（开发环境）
            if (app()->environment('local')) {
                Log::info('Database query', $context);
            }
        });
    }
}
```

### 2. 性能分析工具

**APM集成** (使用New Relic):
```php
<?php
// 在public/index.php中启用
if (extension_loaded('newrelic')) {
    newrelic_set_appname('My Application');
    newrelic_background_job(true);
}
```

**自定义性能分析**:
```php
<?php

class Profiler
{
    protected static $profiles = [];
    
    public static function start($name)
    {
        self::$profiles[$name] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
        ];
    }
    
    public static function end($name)
    {
        if (!isset(self::$profiles[$name])) {
            return;
        }
        
        $profile = self::$profiles[$name];
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $result = [
            'name' => $name,
            'execution_time' => $endTime - $profile['start_time'],
            'memory_usage' => $endMemory - $profile['start_memory'],
            'peak_memory' => memory_get_peak_usage(),
        ];
        
        Logger::info('Profile completed', $result);
        
        unset(self::$profiles[$name]);
        
        return $result;
    }
}

// 使用示例
Profiler::start('user_registration');
// ... 用户注册逻辑
$result = Profiler::end('user_registration');
```

## 监控和告警

### 1. 日志监控配置

**Prometheus + Grafana监控**:
```yaml
# prometheus.yml
global:
  scrape_interval: 15s

scrape_configs:
  - job_name: 'application'
    static_configs:
      - targets: ['localhost:8080']
    metrics_path: '/metrics'

  - job_name: 'nginx'
    static_configs:
      - targets: ['localhost:9113']
```

**Grafana仪表板配置**:
```json
{
  "dashboard": {
    "title": "Application Logs Dashboard",
    "panels": [
      {
        "title": "Error Rate",
        "type": "graph",
        "targets": [
          {
            "expr": "rate(application_errors_total[5m])",
            "legendFormat": "Errors/sec"
          }
        ]
      },
      {
        "title": "Response Time",
        "type": "graph",
        "targets": [
          {
            "expr": "histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m]))",
            "legendFormat": "95th percentile"
          }
        ]
      }
    ]
  }
}
```

### 2. 告警规则配置

**Prometheus告警规则** (`alerts.yml`):
```yaml
groups:
- name: application
  rules:
  - alert: HighErrorRate
    expr: rate(application_errors_total[5m]) > 0.1
    for: 5m
    labels:
      severity: warning
    annotations:
      summary: "High error rate detected"
      description: "Error rate is {{ $value }} errors per second"

  - alert: HighResponseTime
    expr: histogram_quantile(0.95, rate(http_request_duration_seconds_bucket[5m])) > 2
    for: 5m
    labels:
      severity: warning
    annotations:
      summary: "High response time detected"
      description: "95th percentile response time is {{ $value }}s"

  - alert: HighMemoryUsage
    expr: (node_memory_MemTotal_bytes - node_memory_MemAvailable_bytes) / node_memory_MemTotal_bytes > 0.9
    for: 5m
    labels:
      severity: critical
    annotations:
      summary: "High memory usage detected"
      description: "Memory usage is {{ $value | humanizePercentage }}"
```

### 3. 告警通知配置

**Slack通知** (`config/alerting.php`):
```php
<?php

return [
    'slack' => [
        'webhook_url' => env('SLACK_WEBHOOK_URL'),
        'channel' => '#alerts',
        'username' => 'AlertBot',
    ],
    
    'email' => [
        'smtp_host' => env('SMTP_HOST'),
        'smtp_port' => env('SMTP_PORT'),
        'to' => ['admin@example.com'],
    ],
];
```

**告警处理器**:
```php
<?php

class AlertHandler
{
    public static function sendAlert($severity, $message, $context = [])
    {
        // Slack通知
        if ($severity === 'critical') {
            self::sendSlackAlert($message, $context);
        }
        
        // 邮件通知
        if (in_array($severity, ['critical', 'warning'])) {
            self::sendEmailAlert($severity, $message, $context);
        }
        
        // 短信通知（严重级别）
        if ($severity === 'critical') {
            self::sendSMSAlert($message);
        }
    }
    
    protected static function sendSlackAlert($message, $context)
    {
        $webhook = config('alerting.slack.webhook_url');
        
        $payload = [
            'channel' => config('alerting.slack.channel'),
            'username' => config('alerting.slack.username'),
            'text' => $message,
            'attachments' => [
                [
                    'color' => 'danger',
                    'fields' => [
                        [
                            'title' => 'Context',
                            'value' => json_encode($context, JSON_PRETTY_PRINT),
                            'short' => false
                        ]
                    ]
                ]
            ]
        ];
        
        file_get_contents($webhook, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($payload)
            ]
        ]));
    }
}
```

## 最佳实践

### 1. 日志记录最佳实践

**结构化日志记录**:
```php
<?php

// 好的做法
Logger::info('User login', [
    'user_id' => $user->id,
    'email' => $user->email,
    'ip_address' => request()->ip(),
    'user_agent' => request()->userAgent(),
]);

// 避免的做法
Logger::info("User {$user->id} logged in from " . request()->ip());
```

**日志级别使用指南**:
```php
<?php

// DEBUG: 开发调试信息
Logger::debug('Processing user data', ['user_id' => $userId]);

// INFO: 正常业务流程信息
Logger::info('User registered', ['user_id' => $userId]);

// WARNING: 异常情况但不影响核心功能
Logger::warning('Payment failed, retrying', ['user_id' => $userId, 'attempt' => $attempt]);

// ERROR: 错误情况，影响某些功能
Logger::error('Database connection failed', ['host' => $dbHost, 'error' => $error]);

// CRITICAL: 严重错误，系统可能无法正常运行
Logger::critical('System out of memory', ['memory_usage' => memory_get_usage()]);
```

### 2. 调试最佳实践

**调试信息管理**:
```php
<?php

class DebugHelper
{
    public static function debugVariable($variable, $name = 'variable')
    {
        if (!app()->environment('local')) {
            return;
        }
        
        $output = [
            'name' => $name,
            'type' => gettype($variable),
            'value' => $variable,
            'size' => strlen(var_export($variable, true)),
            'timestamp' => microtime(true),
        ];
        
        Logger::debug('Debug variable', $output);
        
        // 同时输出到控制台
        if (PHP_SAPI === 'cli') {
            echo "\n=== DEBUG: {$name} ===\n";
            var_dump($variable);
            echo "=== END DEBUG ===\n\n";
        }
    }
    
    public static function debugFunctionCall($function, $args = [], $result = null)
    {
        if (!app()->environment('local')) {
            return;
        }
        
        $context = [
            'function' => $function,
            'arguments' => $args,
            'result' => $result,
            'memory_before' => memory_get_usage(),
            'memory_after' => memory_get_usage(),
            'execution_time' => microtime(true),
        ];
        
        Logger::debug('Function call', $context);
    }
}
```

### 3. 性能监控最佳实践

**性能指标收集**:
```php
<?php

class MetricsCollector
{
    protected static $metrics = [];
    
    public static function recordTiming($name, $duration)
    {
        if (!isset(self::$metrics[$name])) {
            self::$metrics[$name] = [];
        }
        
        self::$metrics[$name][] = $duration;
    }
    
    public static function getMetrics()
    {
        $results = [];
        
        foreach (self::$metrics as $name => $values) {
            sort($values);
            $count = count($values);
            
            $results[$name] = [
                'count' => $count,
                'min' => min($values),
                'max' => max($values),
                'avg' => array_sum($values) / $count,
                'p50' => $values[intval($count * 0.5)],
                'p95' => $values[intval($count * 0.95)],
                'p99' => $values[intval($count * 0.99)],
            ];
        }
        
        return $results;
    }
    
    public static function reportMetrics()
    {
        $metrics = self::getMetrics();
        
        foreach ($metrics as $name => $stats) {
            Logger::info("Metrics: {$name}", $stats);
        }
    }
}
```

### 4. 错误处理最佳实践

**优雅的错误处理**:
```php
<?php

class ErrorHandler
{
    public static function handleException($exception)
    {
        // 记录异常
        Logger::error('Exception occurred', [
            'exception_class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
        ]);
        
        // 根据环境返回不同响应
        if (app()->environment('local')) {
            return response()->json([
                'error' => 'Exception occurred',
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ], 500);
        }
        
        // 生产环境返回通用错误信息
        return response()->json([
            'error' => 'An error occurred. Please try again later.'
        ], 500);
    }
}
```

## 紧急联系信息

遇到严重的日志或调试问题时，请联系：

- **开发团队**: dev-team@example.com
- **运维团队**: ops-team@example.com
- **紧急热线**: +1-xxx-xxx-xxxx
- **在线支持**: https://support.example.com/logging

## 更新日志

- 2025-11-01: 初始版本创建
- 添加日志系统配置指南
- 完善调试工具使用方法
- 增加性能分析和监控方案
- 提供完整的错误处理最佳实践

---

*本指南将持续更新，以反映最新的日志管理和调试实践。*