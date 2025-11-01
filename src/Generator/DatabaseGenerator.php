<?php

namespace WebmanFilament\Generator;

/**
 * 数据库配置生成器
 * 
 * 负责生成数据库配置文件，支持多种数据库类型和交互式配置
 */
class DatabaseGenerator
{
    /**
     * 生成数据库配置
     * 
     * @param array $options 生成选项
     * @return array 生成结果
     */
    public function generate(array $options = []): array
    {
        $templatePath = __DIR__ . '/../../templates/config/database.php.template';
        $outputPath = config_path() . '/database.php';
        
        if (!file_exists($templatePath)) {
            return [
                'success' => false,
                'message' => '数据库配置模板文件不存在'
            ];
        }
        
        try {
            $template = file_get_contents($templatePath);
            
            // 获取默认配置
            $defaultConfig = $this->getDefaultDatabaseConfig();
            $userConfig = array_merge($defaultConfig, $options);
            
            // 验证配置
            $validation = $this->validate($userConfig);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => '数据库配置验证失败: ' . implode(', ', $validation['errors'])
                ];
            }
            
            // 替换模板变量
            $config = $this->replaceTemplateVariables($template, $userConfig);
            
            // 确保目录存在
            $dir = dirname($outputPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            
            // 写入文件
            file_put_contents($outputPath, $config);
            
            return [
                'success' => true,
                'message' => '数据库配置生成成功',
                'file' => $outputPath
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '数据库配置生成失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取默认数据库配置
     * 
     * @return array 默认配置
     */
    protected function getDefaultDatabaseConfig(): array
    {
        return [
            'default' => env('DB_CONNECTION', 'mysql'),
            'connections' => [
                'mysql' => [
                    'driver' => 'mysql',
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '3306'),
                    'database' => env('DB_DATABASE', 'webman_filament'),
                    'username' => env('DB_USERNAME', 'root'),
                    'password' => env('DB_PASSWORD', ''),
                    'charset' => 'utf8mb4',
                    'collation' => 'utf8mb4_unicode_ci',
                    'prefix' => '',
                    'strict' => true,
                    'engine' => null,
                    'options' => [],
                    'pool' => [
                        'min_connections' => 1,
                        'max_connections' => 10,
                        'acquire_timeout' => 60,
                        'idle_timeout' => 60,
                        'wait_timeout' => 3,
                    ],
                ],
                'pgsql' => [
                    'driver' => 'pgsql',
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', '5432'),
                    'database' => env('DB_DATABASE', 'webman_filament'),
                    'username' => env('DB_USERNAME', 'postgres'),
                    'password' => env('DB_PASSWORD', ''),
                    'charset' => 'utf8',
                    'prefix' => '',
                    'schema' => 'public',
                    'sslmode' => 'prefer',
                    'options' => [],
                    'pool' => [
                        'min_connections' => 1,
                        'max_connections' => 10,
                        'acquire_timeout' => 60,
                        'idle_timeout' => 60,
                        'wait_timeout' => 3,
                    ],
                ],
                'sqlite' => [
                    'driver' => 'sqlite',
                    'database' => env('DB_DATABASE', database_path('database.sqlite')),
                    'prefix' => '',
                    'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
                    'options' => [],
                ],
                'sqlsrv' => [
                    'driver' => 'sqlsrv',
                    'host' => env('DB_HOST', 'localhost'),
                    'port' => env('DB_PORT', '1433'),
                    'database' => env('DB_DATABASE', 'webman_filament'),
                    'username' => env('DB_USERNAME', 'sa'),
                    'password' => env('DB_PASSWORD', ''),
                    'charset' => 'utf8',
                    'prefix' => '',
                    'options' => [],
                    'pool' => [
                        'min_connections' => 1,
                        'max_connections' => 10,
                        'acquire_timeout' => 60,
                        'idle_timeout' => 60,
                        'wait_timeout' => 3,
                    ],
                ],
            ],
            'migrations' => [
                'table' => 'migrations',
                'path' => database_path('migrations'),
            ],
            'redis' => [
                'cluster' => false,
                'default' => [
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'password' => env('REDIS_PASSWORD', ''),
                    'port' => env('REDIS_PORT', 6379),
                    'database' => env('REDIS_DB', 0),
                    'timeout' => 5.0,
                ],
                'cache' => [
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'password' => env('REDIS_PASSWORD', ''),
                    'port' => env('REDIS_PORT', 6379),
                    'database' => env('REDIS_CACHE_DB', 1),
                    'timeout' => 5.0,
                ],
                'queue' => [
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'password' => env('REDIS_PASSWORD', ''),
                    'port' => env('REDIS_PORT', 6379),
                    'database' => env('REDIS_QUEUE_DB', 2),
                    'timeout' => 5.0,
                ],
                'session' => [
                    'host' => env('REDIS_HOST', '127.0.0.1'),
                    'password' => env('REDIS_PASSWORD', ''),
                    'port' => env('REDIS_PORT', 6379),
                    'database' => env('REDIS_SESSION_DB', 3),
                    'timeout' => 5.0,
                ],
            ],
        ];
    }

    /**
     * 验证数据库配置
     * 
     * @param array $config 配置数组
     * @return array 验证结果
     */
    public function validate(array $config): array
    {
        $errors = [];
        
        // 验证默认连接
        if (empty($config['default'])) {
            $errors[] = '缺少默认数据库连接配置';
        } else {
            $validDrivers = ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
            if (!in_array($config['default'], $validDrivers)) {
                $errors[] = '无效的数据库驱动: ' . $config['default'];
            }
        }
        
        // 验证连接配置
        if (isset($config['connections'])) {
            foreach ($config['connections'] as $driver => $connection) {
                $validation = $this->validateConnection($driver, $connection);
                if (!$validation['valid']) {
                    $errors = array_merge($errors, $validation['errors']);
                }
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 验证单个数据库连接配置
     * 
     * @param string $driver 数据库驱动
     * @param array $connection 连接配置
     * @return array 验证结果
     */
    protected function validateConnection(string $driver, array $connection): array
    {
        $errors = [];
        
        // SQLite特殊处理
        if ($driver === 'sqlite') {
            if (empty($connection['database'])) {
                $errors[] = 'SQLite数据库文件路径不能为空';
            }
            return [
                'valid' => empty($errors),
                'errors' => $errors
            ];
        }
        
        // 验证必需字段
        $requiredFields = ['host', 'database', 'username'];
        foreach ($requiredFields as $field) {
            if (empty($connection[$field])) {
                $errors[] = "{$driver}连接缺少必需字段: {$field}";
            }
        }
        
        // 验证端口
        if (!empty($connection['port']) && !is_numeric($connection['port'])) {
            $errors[] = "{$driver}连接端口必须是数字";
        }
        
        // 验证主机地址
        if (!empty($connection['host']) && !filter_var($connection['host'], FILTER_VALIDATE_IP) && 
            $connection['host'] !== 'localhost') {
            // 允许localhost，但验证其他主机名格式
            if (!preg_match('/^[a-zA-Z0-9.-]+$/', $connection['host'])) {
                $errors[] = "{$driver}连接主机格式无效";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 替换模板变量
     * 
     * @param string $template 模板内容
     * @param array $config 配置数组
     * @return string 替换后的内容
     */
    protected function replaceTemplateVariables(string $template, array $config): string
    {
        $replacements = [
            '{{DEFAULT_CONNECTION}}' => $config['default'],
            '{{MYSQL_HOST}}' => $config['connections']['mysql']['host'] ?? '',
            '{{MYSQL_PORT}}' => $config['connections']['mysql']['port'] ?? '',
            '{{MYSQL_DATABASE}}' => $config['connections']['mysql']['database'] ?? '',
            '{{MYSQL_USERNAME}}' => $config['connections']['mysql']['username'] ?? '',
            '{{MYSQL_PASSWORD}}' => $config['connections']['mysql']['password'] ?? '',
            '{{PGSQL_HOST}}' => $config['connections']['pgsql']['host'] ?? '',
            '{{PGSQL_PORT}}' => $config['connections']['pgsql']['port'] ?? '',
            '{{PGSQL_DATABASE}}' => $config['connections']['pgsql']['database'] ?? '',
            '{{PGSQL_USERNAME}}' => $config['connections']['pgsql']['username'] ?? '',
            '{{PGSQL_PASSWORD}}' => $config['connections']['pgsql']['password'] ?? '',
            '{{SQLITE_DATABASE}}' => $config['connections']['sqlite']['database'] ?? '',
            '{{REDIS_HOST}}' => $config['redis']['default']['host'] ?? '',
            '{{REDIS_PORT}}' => $config['redis']['default']['port'] ?? '',
            '{{REDIS_PASSWORD}}' => $config['redis']['default']['password'] ?? '',
            '{{REDIS_DATABASE}}' => $config['redis']['default']['database'] ?? '',
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * 交互式配置
     * 
     * @return array 配置选项
     */
    public function interactiveConfig(): array
    {
        $options = [];
        
        echo "=== 数据库配置 ===\n\n";
        
        // 选择数据库类型
        echo "选择数据库类型:\n";
        echo "1. MySQL\n";
        echo "2. PostgreSQL\n";
        echo "3. SQLite\n";
        echo "4. SQL Server\n";
        echo "请选择 [1]: ";
        
        $choice = trim(fgets(STDIN));
        if (empty($choice)) {
            $choice = '1';
        }
        
        $drivers = ['1' => 'mysql', '2' => 'pgsql', '3' => 'sqlite', '4' => 'sqlsrv'];
        $selectedDriver = $drivers[$choice] ?? 'mysql';
        
        $options['default'] = $selectedDriver;
        
        // 根据选择的数据库类型进行配置
        switch ($selectedDriver) {
            case 'mysql':
                $options['connections']['mysql'] = $this->configureMySQL();
                break;
            case 'pgsql':
                $options['connections']['pgsql'] = $this->configurePostgreSQL();
                break;
            case 'sqlite':
                $options['connections']['sqlite'] = $this->configureSQLite();
                break;
            case 'sqlsrv':
                $options['connections']['sqlsrv'] = $this->configureSQLServer();
                break;
        }
        
        // Redis配置
        echo "\n=== Redis配置 (可选) ===\n";
        echo "是否配置Redis? (y/n) [n]: ";
        $configureRedis = trim(fgets(STDIN));
        
        if (strtolower($configureRedis) === 'y') {
            $options['redis'] = $this->configureRedis();
        }
        
        return $options;
    }

    /**
     * 配置MySQL
     * 
     * @return array MySQL配置
     */
    protected function configureMySQL(): array
    {
        $config = [];
        
        echo "MySQL主机 [" . env('DB_HOST', '127.0.0.1') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['host'] = $input;
        }
        
        echo "MySQL端口 [" . env('DB_PORT', '3306') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['port'] = $input;
        }
        
        echo "数据库名 [" . env('DB_DATABASE', 'webman_filament') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['database'] = $input;
        }
        
        echo "用户名 [" . env('DB_USERNAME', 'root') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['username'] = $input;
        }
        
        echo "密码 [" . env('DB_PASSWORD', '') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['password'] = $input;
        }
        
        return array_merge([
            'driver' => 'mysql',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ], $config);
    }

    /**
     * 配置PostgreSQL
     * 
     * @return array PostgreSQL配置
     */
    protected function configurePostgreSQL(): array
    {
        $config = [];
        
        echo "PostgreSQL主机 [" . env('DB_HOST', '127.0.0.1') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['host'] = $input;
        }
        
        echo "PostgreSQL端口 [" . env('DB_PORT', '5432') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['port'] = $input;
        }
        
        echo "数据库名 [" . env('DB_DATABASE', 'webman_filament') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['database'] = $input;
        }
        
        echo "用户名 [" . env('DB_USERNAME', 'postgres') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['username'] = $input;
        }
        
        echo "密码 [" . env('DB_PASSWORD', '') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['password'] = $input;
        }
        
        return array_merge([
            'driver' => 'pgsql',
            'charset' => 'utf8',
            'prefix' => '',
            'schema' => 'public',
            'sslmode' => 'prefer',
        ], $config);
    }

    /**
     * 配置SQLite
     * 
     * @return array SQLite配置
     */
    protected function configureSQLite(): array
    {
        $config = [];
        
        echo "SQLite数据库文件路径 [" . database_path('database.sqlite') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['database'] = $input;
        }
        
        echo "启用外键约束 (y/n) [y]: ";
        $input = trim(fgets(STDIN));
        $config['foreign_key_constraints'] = empty($input) || strtolower($input) === 'y';
        
        return array_merge([
            'driver' => 'sqlite',
            'prefix' => '',
        ], $config);
    }

    /**
     * 配置SQL Server
     * 
     * @return array SQL Server配置
     */
    protected function configureSQLServer(): array
    {
        $config = [];
        
        echo "SQL Server主机 [" . env('DB_HOST', 'localhost') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['host'] = $input;
        }
        
        echo "SQL Server端口 [" . env('DB_PORT', '1433') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['port'] = $input;
        }
        
        echo "数据库名 [" . env('DB_DATABASE', 'webman_filament') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['database'] = $input;
        }
        
        echo "用户名 [" . env('DB_USERNAME', 'sa') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['username'] = $input;
        }
        
        echo "密码 [" . env('DB_PASSWORD', '') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['password'] = $input;
        }
        
        return array_merge([
            'driver' => 'sqlsrv',
            'charset' => 'utf8',
            'prefix' => '',
        ], $config);
    }

    /**
     * 配置Redis
     * 
     * @return array Redis配置
     */
    protected function configureRedis(): array
    {
        $config = [];
        
        echo "Redis主机 [" . env('REDIS_HOST', '127.0.0.1') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['default']['host'] = $input;
        }
        
        echo "Redis端口 [" . env('REDIS_PORT', '6379') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['default']['port'] = $input;
        }
        
        echo "Redis密码 [" . env('REDIS_PASSWORD', '') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['default']['password'] = $input;
        }
        
        echo "Redis数据库 [" . env('REDIS_DB', '0') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $config['default']['database'] = $input;
        }
        
        return array_merge([
            'cluster' => false,
            'default' => [
                'host' => '127.0.0.1',
                'password' => '',
                'port' => 6379,
                'database' => 0,
                'timeout' => 5.0,
            ],
        ], $config);
    }

    /**
     * 测试数据库连接
     * 
     * @param array $config 数据库配置
     * @return array 测试结果
     */
    public function testConnection(array $config): array
    {
        try {
            $driver = $config['driver'] ?? $config['default'] ?? 'mysql';
            
            switch ($driver) {
                case 'mysql':
                    return $this->testMySQLConnection($config);
                case 'pgsql':
                    return $this->testPostgreSQLConnection($config);
                case 'sqlite':
                    return $this->testSQLiteConnection($config);
                case 'sqlsrv':
                    return $this->testSQLServerConnection($config);
                default:
                    return [
                        'success' => false,
                        'message' => '不支持的数据库驱动: ' . $driver
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '连接测试失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 测试MySQL连接
     * 
     * @param array $config 配置
     * @return array 测试结果
     */
    protected function testMySQLConnection(array $config): array
    {
        try {
            $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset={$config['charset']}";
            $pdo = new \PDO($dsn, $config['username'], $config['password']);
            
            return [
                'success' => true,
                'message' => 'MySQL连接测试成功'
            ];
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'MySQL连接失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 测试PostgreSQL连接
     * 
     * @param array $config 配置
     * @return array 测试结果
     */
    protected function testPostgreSQLConnection(array $config): array
    {
        try {
            $dsn = "pgsql:host={$config['host']};port={$config['port']};dbname={$config['database']}";
            $pdo = new \PDO($dsn, $config['username'], $config['password']);
            
            return [
                'success' => true,
                'message' => 'PostgreSQL连接测试成功'
            ];
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'PostgreSQL连接失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 测试SQLite连接
     * 
     * @param array $config 配置
     * @return array 测试结果
     */
    protected function testSQLiteConnection(array $config): array
    {
        try {
            $dsn = "sqlite:{$config['database']}";
            $pdo = new \PDO($dsn);
            
            return [
                'success' => true,
                'message' => 'SQLite连接测试成功'
            ];
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'SQLite连接失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 测试SQL Server连接
     * 
     * @param array $config 配置
     * @return array 测试结果
     */
    protected function testSQLServerConnection(array $config): array
    {
        try {
            $dsn = "sqlsrv:Server={$config['host']},{$config['port']};Database={$config['database']}";
            $pdo = new \PDO($dsn, $config['username'], $config['password']);
            
            return [
                'success' => true,
                'message' => 'SQL Server连接测试成功'
            ];
        } catch (\PDOException $e) {
            return [
                'success' => false,
                'message' => 'SQL Server连接失败: ' . $e->getMessage()
            ];
        }
    }
}