# 数据库迁移适配系统

这是一个基于 Laravel Eloquent ORM 的数据库迁移适配系统，提供了完整的数据库迁移、模型操作和连接池管理功能。

## 系统架构

```
src/
├── Adapter/
│   ├── MigrationAdapter.php    # 迁移适配器
│   └── ModelAdapter.php        # 模型适配器
├── Handler/
│   └── DatabaseHandler.php     # 数据库处理器
└── Command/
    └── MigrateCommand.php      # 迁移命令

database/
└── migrations/                 # 迁移文件目录
```

## 核心组件

### 1. MigrationAdapter (迁移适配器)
- 数据库连接检查
- 迁移表管理
- 迁移执行控制
- 迁移状态查询
- 数据库驱动兼容性检查

### 2. ModelAdapter (模型适配器)
- Eloquent ORM 封装
- 查询优化和缓存
- 批量操作支持
- 软删除处理
- 关系查询优化

### 3. DatabaseHandler (数据库处理器)
- 连接池管理
- 事务处理
- 健康检查
- 数据库信息查询
- 连接优化

### 4. MigrateCommand (迁移命令)
- 命令行迁移操作
- 多种迁移选项支持
- 迁移状态管理
- 错误处理和日志

## 主要功能

### 迁移管理
- ✅ 迁移文件创建和管理
- ✅ 迁移执行和回滚
- ✅ 迁移状态查询
- ✅ 批量迁移操作
- ✅ 迁移依赖处理

### 模型操作
- ✅ 基础 CRUD 操作
- ✅ 查询优化和缓存
- ✅ 批量插入/更新
- ✅ 分页查询
- ✅ 软删除支持
- ✅ 关系查询

### 连接池管理
- ✅ 连接池配置
- ✅ 健康检查
- ✅ 连接优化
- ✅ 错误恢复
- ✅ 性能监控

### 数据库兼容性
- ✅ MySQL 支持
- ✅ PostgreSQL 支持
- ✅ SQLite 支持
- ✅ SQL Server 支持
- ✅ 连接池配置

## 使用方法

### 1. 基本迁移操作

```bash
# 执行所有待执行迁移
php artisan db:migrate

# 逐步执行迁移
php artisan db:migrate --step

# 模拟执行迁移（不实际执行）
php artisan db:migrate --pretend

# 指定迁移路径
php artisan db:migrate --path=database/migrations/custom

# 迁移后执行数据库填充
php artisan db:migrate --seed

# 迁移后执行指定的填充类
php artisan db:migrate --seed --seeder=UserSeeder
```

### 2. 迁移状态管理

```bash
# 检查迁移状态
php artisan db:migrate --check

# 显示详细迁移状态
php artisan db:migrate --status

# 回滚最后一个迁移批次
php artisan db:migrate --rollback

# 回滚所有迁移
php artisan db:migrate --reset

# 清空数据库后重新迁移
php artisan db:migrate --fresh
```

### 3. 创建迁移文件

```bash
# 创建新表迁移
php artisan db:migrate --create=posts

# 修改现有表迁移
php artisan db:migrate --table=users
```

### 4. 编程方式使用

```php
use App\Adapter\MigrationAdapter;
use App\Adapter\ModelAdapter;
use App\Handler\DatabaseHandler;

// 注入依赖
class MigrationService
{
    public function __construct(
        private MigrationAdapter $migrationAdapter,
        private ModelAdapter $modelAdapter,
        private DatabaseHandler $databaseHandler
    ) {}

    public function runMigration(): array
    {
        // 检查连接
        if (!$this->migrationAdapter->checkConnection()) {
            throw new \Exception('数据库连接失败');
        }

        // 执行迁移
        return $this->migrationAdapter->runMigrations();
    }

    public function getMigrationStatus(): array
    {
        return $this->migrationAdapter->getMigrationStatus();
    }

    public function findUser(int $id): ?User
    {
        $user = new User();
        return $this->modelAdapter->find($user, $id);
    }

    public function testConnection(): array
    {
        return $this->databaseHandler->testConnection();
    }
}
```

## 配置要求

### 数据库配置
确保在 `config/database.php` 中正确配置数据库连接：

```php
'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'forge'),
        'username' => env('DB_USERNAME', 'forge'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
        'engine' => null,
        'options' => extension_loaded('pdo_mysql') ? array_filter([
            PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        ]) : [],
        // 连接池配置
        'pool_size' => 10,
        'max_connections' => 20,
        'connection_timeout' => 60,
        'idle_timeout' => 600,
    ],
    
    // 其他数据库配置...
],
```

### 环境变量
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

## 最佳实践

### 1. 迁移文件命名
- 使用时间戳格式：`YYYY_MM_D_HHMMSS_description.php`
- 描述性名称：`create_users_table.php`
- 避免特殊字符和空格

### 2. 迁移编写规范
```php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('table_name', function (Blueprint $table) {
            $table->id();
            $table->string('column_name');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('table_name');
    }
};
```

### 3. 模型操作优化
- 使用查询缓存减少数据库负载
- 合理使用批量操作
- 避免 N+1 查询问题
- 使用索引优化查询性能

### 4. 连接池管理
- 根据应用负载调整连接池大小
- 定期执行健康检查
- 监控连接使用情况
- 及时清理无效连接

## 故障排除

### 常见问题

1. **数据库连接失败**
   - 检查数据库服务是否运行
   - 验证连接参数
   - 检查网络连接

2. **迁移文件格式错误**
   - 确保文件名符合规范
   - 检查迁移类结构
   - 验证 up/down 方法

3. **权限不足**
   - 确保应用有数据库操作权限
   - 检查文件读写权限
   - 验证用户权限

4. **连接池耗尽**
   - 增加连接池大小
   - 优化查询性能
   - 检查长时间运行的事务

### 日志和调试
系统会记录详细的操作日志，包括：
- 迁移执行过程
- 数据库连接状态
- 错误信息和堆栈跟踪
- 性能指标

## 性能优化

### 1. 查询优化
- 使用索引
- 避免全表扫描
- 合理使用缓存
- 批量操作

### 2. 连接优化
- 配置合适的连接池大小
- 设置合适的超时时间
- 启用连接复用
- 定期清理空闲连接

### 3. 迁移优化
- 按批次执行大量迁移
- 使用事务包装迁移
- 避免长时间锁表
- 合理安排迁移顺序

## 扩展功能

系统支持以下扩展：
- 自定义数据库驱动
- 迁移钩子函数
- 迁移验证器
- 性能监控
- 备份和恢复

## 技术支持

如有问题或建议，请：
1. 查看日志文件
2. 检查配置参数
3. 验证数据库状态
4. 参考最佳实践

---

**注意**: 本系统基于 Laravel 框架构建，需要 PHP 8.0+ 和相应的数据库扩展支持。