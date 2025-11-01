# 性能问题排查指南

本文档提供系统性能问题的识别、分析和解决方案，帮助您优化系统性能。

## 目录

- [性能监控](#性能监控)
- [CPU性能问题](#cpu性能问题)
- [内存性能问题](#内存性能问题)
- [磁盘I/O问题](#磁盘io问题)
- [网络性能问题](#网络性能问题)
- [数据库性能问题](#数据库性能问题)
- [应用性能问题](#应用性能问题)
- [性能优化策略](#性能优化策略)

## 性能监控

### 1. 系统性能指标

**关键性能指标(KPI)**:
- CPU使用率
- 内存使用率
- 磁盘I/O使用率
- 网络吞吐量
- 响应时间
- 错误率

**监控工具**:
```bash
# 系统资源监控
htop
iotop
iftop
nethogs

# 性能基准测试
sysbench
dd if=/dev/zero of=testfile bs=1M count=1024
```

### 2. 实时监控脚本

```bash
#!/bin/bash
# performance_monitor.sh

echo "=== 系统性能监控报告 ==="
echo "时间: $(date)"
echo ""

# CPU使用率
echo "=== CPU使用率 ==="
top -bn1 | grep "Cpu(s)" | awk '{print $2 $3 $4 $5}'

# 内存使用
echo ""
echo "=== 内存使用 ==="
free -h

# 磁盘使用
echo ""
echo "=== 磁盘使用 ==="
df -h

# 负载平均值
echo ""
echo "=== 负载平均值 ==="
uptime

# 网络连接
echo ""
echo "=== 网络连接统计 ==="
ss -s

# 进程统计
echo ""
echo "=== 进程统计 ==="
ps aux | wc -l
echo "总进程数: $(ps aux | wc -l)"
```

### 3. 性能基线建立

**基线测量步骤**:
1. 在正常负载下收集性能数据
2. 建立性能基线值
3. 设置告警阈值
4. 定期更新基线

**基线数据记录**:
```bash
# 创建性能基线
echo "$(date),$(top -bn1 | grep 'Cpu(s)' | awk '{print $2}'),$(free | grep Mem | awk '{printf("%.2f", $3/$2 * 100.0)}'),$(uptime | awk '{print $10}')" >> performance_baseline.csv
```

## CPU性能问题

### 1. CPU使用率过高

**问题识别**:
- CPU使用率持续超过80%
- 系统响应缓慢
- 用户抱怨性能差

**排查步骤**:

1. **识别高CPU进程**
   ```bash
   # 查看CPU使用率排序的进程
   ps aux --sort=-%cpu | head -20
   
   # 实时监控CPU使用
   top
   htop
   ```

2. **分析CPU密集型进程**
   ```bash
   # 查看进程详细信息
   ps -ef | grep <PID>
   
   # 查看进程树
   pstree -p <PID>
   
   # 分析进程系统调用
   strace -c -p <PID>
   ```

3. **检查系统负载**
   ```bash
   # 查看负载平均值
   uptime
   
   # 查看CPU详细信息
   lscpu
   cat /proc/cpuinfo
   ```

**解决方案**:

1. **优化高CPU进程**
   ```bash
   # 调整进程优先级
   renice 10 <PID>
   
   # 终止不必要的进程
   kill <PID>
   ```

2. **代码优化**
   - 优化算法复杂度
   - 使用缓存减少计算
   - 实现异步处理
   - 减少循环嵌套

3. **硬件升级**
   - 增加CPU核心数
   - 升级到更快的CPU
   - 使用负载均衡

**预防措施**:
- 设置CPU使用率告警
- 定期审查进程列表
- 优化代码性能
- 使用性能分析工具

### 2. CPU负载不均衡

**问题描述**: 多核CPU负载分布不均

**排查步骤**:
```bash
# 查看每个CPU核心的使用情况
mpstat -P ALL 1 3

# 查看CPU亲和性
taskset -cp <PID>
```

**解决方案**:
```bash
# 设置进程CPU亲和性
taskset -c 0,1,2,3 <PID>

# 使用负载均衡工具
numactl --cpunodebind=0 --membind=0 <command>
```

## 内存性能问题

### 1. 内存使用率过高

**问题识别**:
- 内存使用率超过90%
- 出现OOM Killer
- 系统交换分区使用增加

**排查步骤**:

1. **分析内存使用**
   ```bash
   # 查看内存使用详情
   free -h
   cat /proc/meminfo
   
   # 查看内存使用排序的进程
   ps aux --sort=-%mem | head -20
   
   # 查看内存映射
   pmap -x <PID>
   ```

2. **检查内存泄漏**
   ```bash
   # 使用valgrind检测内存泄漏
   valgrind --tool=memcheck --leak-check=full ./program
   
   # 监控内存使用趋势
   watch -n 5 'free -h'
   ```

3. **分析内存分配**
   ```bash
   # 查看slab信息
   slabtop
   
   # 查看内存统计
   vmstat 5 10
   ```

**解决方案**:

1. **优化内存使用**
   ```php
   // 及时释放不需要的变量
   unset($large_array);
   
   // 使用unset释放对象
   $obj = new SomeClass();
   // ... 使用对象
   unset($obj);
   
   // 分批处理大数据
   $chunks = array_chunk($large_data, 1000);
   foreach ($chunks as $chunk) {
       process_chunk($chunk);
       unset($chunk); // 释放内存
   }
   ```

2. **调整系统参数**
   ```bash
   # 调整交换分区使用
   echo 10 > /proc/sys/vm/swappiness
   
   # 清理页面缓存
   echo 3 > /proc/sys/vm/drop_caches
   ```

3. **代码优化**
   - 使用生成器处理大数据
   - 优化数据结构
   - 实现对象池
   - 使用缓存机制

**预防措施**:
- 设置内存使用告警
- 定期检查内存使用趋势
- 使用内存分析工具
- 优化数据处理逻辑

### 2. 内存碎片化

**问题描述**: 内存碎片导致可用内存减少

**排查步骤**:
```bash
# 查看内存分配情况
cat /proc/buddyinfo

# 检查内存碎片
echo "=== 内存碎片分析 ==="
cat /proc/buddyinfo | while read line; do
    echo "$line"
done
```

**解决方案**:
- 重启内存密集型应用
- 使用内存整理工具
- 优化内存分配策略
- 考虑升级内存

## 磁盘I/O问题

### 1. 磁盘I/O瓶颈

**问题识别**:
- 磁盘I/O使用率持续100%
- 系统响应缓慢
- I/O等待时间过长

**排查步骤**:

1. **分析磁盘I/O**
   ```bash
   # 实时监控磁盘I/O
   iotop
   
   # 查看I/O统计
   iostat -x 1 5
   
   # 查看磁盘使用情况
   df -h
   du -sh /* | sort -hr
   ```

2. **识别高I/O进程**
   ```bash
   # 查看I/O使用排序的进程
   ps aux --sort=-%io | head -20
   
   # 查看进程I/O详情
   pidstat -d 1 5
   ```

3. **检查磁盘健康**
   ```bash
   # 检查磁盘SMART信息
   smartctl -a /dev/sda
   
   # 检查磁盘错误
   dmesg | grep -i "I/O error"
   ```

**解决方案**:

1. **优化I/O操作**
   ```bash
   # 调整I/O调度器
   echo noop > /sys/block/sda/queue/scheduler
   
   # 优化文件系统
   mount -o remount,noatime /dev/sda1
   ```

2. **代码优化**
   - 使用缓存减少磁盘访问
   - 批量写入操作
   - 异步I/O处理
   - 压缩数据减少存储

3. **硬件优化**
   - 使用SSD替代机械硬盘
   - 增加内存作为缓存
   - 使用RAID配置
   - 分离读写负载

**预防措施**:
- 设置I/O使用率告警
- 定期清理临时文件
- 监控磁盘空间使用
- 实施数据生命周期管理

### 2. 磁盘空间不足

**问题描述**: 磁盘空间耗尽导致系统异常

**排查步骤**:
```bash
# 查看磁盘使用情况
df -h

# 查找大文件
find / -type f -size +100M -exec ls -lh {} \; | sort -k5 -hr

# 查看目录大小
du -sh /* | sort -hr

# 检查inode使用
df -i
```

**解决方案**:
```bash
# 清理临时文件
find /tmp -type f -atime +7 -delete
find /var/log -type f -mtime +30 -delete

# 压缩旧日志
find /var/log -name "*.log" -mtime +7 -exec gzip {} \;

# 清理包缓存
apt-get clean  # Debian/Ubuntu
yum clean all  # CentOS/RHEL
```

## 网络性能问题

### 1. 网络延迟高

**问题识别**:
- 网络响应时间长
- 用户体验差
- API调用超时

**排查步骤**:

1. **网络连通性测试**
   ```bash
   # 测试网络延迟
   ping -c 10 target_host
   
   # 测试端口连通性
   telnet target_host port
   
   # 测试DNS解析
   nslookup target_host
   ```

2. **网络路径分析**
   ```bash
   # 跟踪网络路径
   traceroute target_host
   
   # 查看网络路由
   route -n
   netstat -rn
   ```

3. **网络流量分析**
   ```bash
   # 监控网络流量
   iftop
   nethogs
   
   # 查看网络统计
   ss -tuln
   netstat -i
   ```

**解决方案**:
- 优化网络路由
- 使用CDN加速
- 实现连接池
- 调整超时参数
- 使用缓存减少网络请求

### 2. 带宽不足

**问题描述**: 网络带宽无法满足需求

**排查步骤**:
```bash
# 测试网络带宽
iperf3 -c target_host

# 查看网络接口统计
cat /proc/net/dev

# 监控网络使用
iftop -i eth0
```

**解决方案**:
- 增加网络带宽
- 使用压缩技术
- 实现负载均衡
- 优化数据传输
- 使用缓存策略

## 数据库性能问题

### 1. 查询性能差

**问题识别**:
- 数据库响应缓慢
- 查询执行时间长
- 连接池耗尽

**排查步骤**:

1. **分析慢查询**
   ```sql
   -- 启用慢查询日志
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 2;
   
   -- 查看慢查询
   SELECT * FROM mysql.slow_log ORDER BY start_time DESC LIMIT 10;
   ```

2. **分析查询执行计划**
   ```sql
   EXPLAIN SELECT * FROM table WHERE condition;
   
   -- 详细执行计划
   EXPLAIN FORMAT=JSON SELECT * FROM table WHERE condition;
   ```

3. **监控数据库性能**
   ```bash
   # 查看数据库进程
   SHOW PROCESSLIST;
   
   # 查看数据库状态
   SHOW STATUS LIKE 'Threads_%';
   SHOW STATUS LIKE 'Slow_queries';
   ```

**解决方案**:

1. **索引优化**
   ```sql
   -- 创建索引
   CREATE INDEX idx_column ON table(column);
   
   -- 复合索引
   CREATE INDEX idx_columns ON table(col1, col2);
   
   -- 检查索引使用
   EXPLAIN SELECT * FROM table WHERE col1 = 'value';
   ```

2. **查询优化**
   ```sql
   -- 优化前
   SELECT * FROM users WHERE YEAR(created_at) = 2023;
   
   -- 优化后
   SELECT * FROM users WHERE created_at >= '2023-01-01' AND created_at < '2024-01-01';
   ```

3. **配置优化**
   ```ini
   # my.cnf
   innodb_buffer_pool_size = 1G
   query_cache_size = 64M
   max_connections = 200
   ```

**预防措施**:
- 定期审查慢查询
- 监控数据库性能
- 维护索引统计
- 实施查询缓存

### 2. 连接数过多

**问题描述**: 数据库连接数达到上限

**排查步骤**:
```sql
-- 查看当前连接数
SHOW STATUS LIKE 'Threads_connected';

-- 查看最大连接数
SHOW VARIABLES LIKE 'max_connections';

-- 查看连接详情
SELECT * FROM information_schema.PROCESSLIST;
```

**解决方案**:
- 增加最大连接数
- 实现连接池
- 优化连接生命周期
- 及时关闭不需要的连接

## 应用性能问题

### 1. 响应时间过长

**问题识别**:
- API响应时间超过预期
- 页面加载缓慢
- 用户体验差

**排查步骤**:

1. **分析响应时间**
   ```php
   // 添加性能监控代码
   $start_time = microtime(true);
   
   // 业务逻辑
   business_logic();
   
   $end_time = microtime(true);
   $execution_time = $end_time - $start_time;
   
   error_log("Execution time: " . $execution_time . " seconds");
   ```

2. **分析代码性能**
   ```bash
   # 使用Xdebug分析
   php -dxdebug.mode=profile script.php
   
   # 使用Blackfire分析
   blackfire curl https://example.com
   ```

3. **监控应用指标**
   ```bash
   # 使用APM工具
   newrelic-cli monitor start
   ```

**解决方案**:

1. **代码优化**
   ```php
   // 缓存频繁访问的数据
   $cache_key = 'user_data_' . $user_id;
   $user_data = cache()->get($cache_key);
   
   if (!$user_data) {
       $user_data = User::find($user_id);
       cache()->put($cache_key, $user_data, 3600);
   }
   
   // 使用批量处理
   $users = User::where('status', 'active')->chunk(100, function($chunk) {
       foreach ($chunk as $user) {
           process_user($user);
       }
   });
   ```

2. **缓存优化**
   - 使用Redis缓存
   - 实现页面缓存
   - 缓存数据库查询结果
   - 使用CDN缓存静态资源

3. **异步处理**
   ```php
   // 使用队列处理耗时任务
   dispatch(new ProcessDataJob($data));
   
   // 异步发送邮件
   Mail::to($user)->queue(new WelcomeEmail($user));
   ```

**预防措施**:
- 设置响应时间告警
- 定期进行性能测试
- 使用APM监控工具
- 优化代码质量

### 2. 内存泄漏

**问题描述**: 应用内存使用持续增长

**排查步骤**:
```bash
# 监控内存使用
watch -n 5 'ps aux | grep php | awk "{print \$6}"'

# 使用内存分析工具
valgrind --tool=massif ./php_script.php

# 分析内存快照
php -r "
\$start_memory = memory_get_usage();
for(\$i = 0; \$i < 1000; \$i++) {
    \$arr[] = str_repeat('x', 1000);
}
echo 'Peak memory: ' . memory_get_peak_usage() . PHP_EOL;
echo 'Current memory: ' . memory_get_usage() . PHP_EOL;
"
```

**解决方案**:
- 修复循环引用
- 及时释放资源
- 使用对象池
- 重构数据结构

## 性能优化策略

### 1. 系统级优化

**内核参数调优**:
```bash
# /etc/sysctl.conf
# 网络优化
net.core.rmem_max = 16777216
net.core.wmem_max = 16777216
net.ipv4.tcp_rmem = 4096 87380 16777216
net.ipv4.tcp_wmem = 4096 65536 16777216

# 文件描述符限制
fs.file-max = 2097152

# 内存管理
vm.swappiness = 10
vm.dirty_ratio = 15
vm.dirty_background_ratio = 5

# 应用设置
sysctl -p
```

**系统资源限制**:
```bash
# /etc/security/limits.conf
* soft nofile 65536
* hard nofile 65536
* soft nproc 32768
* hard nproc 32768
```

### 2. 应用级优化

**PHP优化**:
```ini
; php.ini
; 内存管理
memory_limit = 256M
max_execution_time = 30

; OPcache优化
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=2

; 性能优化
realpath_cache_size=4096k
realpath_cache_ttl=600
```

**Nginx优化**:
```nginx
# /etc/nginx/nginx.conf
worker_processes auto;
worker_connections 1024;

# 缓冲区优化
client_body_buffer_size 128k;
client_max_body_size 10m;
client_header_buffer_size 1k;
large_client_header_buffers 4 4k;

# 缓存优化
open_file_cache max=10000 inactive=20s;
open_file_cache_valid 30s;
open_file_cache_min_uses 2;
open_file_cache_errors on;
```

### 3. 数据库优化

**MySQL优化**:
```ini
# my.cnf
[mysqld]
# 内存优化
innodb_buffer_pool_size = 1G
innodb_log_file_size = 256M
innodb_log_buffer_size = 8M
query_cache_size = 64M
query_cache_type = 1

# 连接优化
max_connections = 200
connect_timeout = 5
wait_timeout = 600
interactive_timeout = 600

# InnoDB优化
innodb_flush_log_at_trx_commit = 2
innodb_flush_method = O_DIRECT
innodb_file_per_table = 1
```

### 4. 监控和告警

**性能监控脚本**:
```bash
#!/bin/bash
# performance_alert.sh

# CPU使用率告警
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | awk '{print $2}' | cut -d'%' -f1)
if (( $(echo "$CPU_USAGE > 80" | bc -l) )); then
    echo "ALERT: CPU使用率超过80%: $CPU_USAGE%" | mail -s "CPU使用率告警" admin@example.com
fi

# 内存使用率告警
MEM_USAGE=$(free | grep Mem | awk '{printf("%.2f", $3/$2 * 100.0)}')
if (( $(echo "$MEM_USAGE > 90" | bc -l) )); then
    echo "ALERT: 内存使用率超过90%: $MEM_USAGE%" | mail -s "内存使用率告警" admin@example.com
fi

# 磁盘使用率告警
DISK_USAGE=$(df -h / | awk 'NR==2 {print $5}' | cut -d'%' -f1)
if [ $DISK_USAGE -gt 90 ]; then
    echo "ALERT: 磁盘使用率超过90%: $DISK_USAGE%" | mail -s "磁盘使用率告警" admin@example.com
fi
```

**设置定时任务**:
```bash
# crontab -e
# 每5分钟检查性能
*/5 * * * * /path/to/performance_alert.sh

# 每天生成性能报告
0 2 * * * /path/to/generate_performance_report.sh
```

## 性能调优检查清单

### 1. 系统层面
- [ ] CPU使用率正常
- [ ] 内存使用率合理
- [ ] 磁盘I/O性能良好
- [ ] 网络延迟可接受
- [ ] 系统负载均衡

### 2. 应用层面
- [ ] 代码性能优化
- [ ] 缓存策略有效
- [ ] 数据库查询优化
- [ ] 异步处理实现
- [ ] 资源使用合理

### 3. 监控层面
- [ ] 性能指标收集
- [ ] 告警机制配置
- [ ] 日志记录完整
- [ ] 报告定期生成
- [ ] 问题及时响应

### 4. 优化层面
- [ ] 定期性能评估
- [ ] 瓶颈识别分析
- [ ] 优化方案实施
- [ ] 效果验证评估
- [ ] 经验总结分享

## 紧急联系信息

遇到严重的性能问题时，请联系：

- **性能团队**: performance@example.com
- **紧急热线**: +1-xxx-xxx-xxxx
- **在线支持**: https://support.example.com/performance

## 更新日志

- 2025-11-01: 初始版本创建
- 添加CPU性能问题分析
- 完善内存和磁盘I/O问题排查
- 增加数据库和应用性能优化
- 提供完整的性能监控方案

---

*本指南将持续更新，以反映最新的性能优化实践和工具。*