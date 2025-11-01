<?php

namespace WebmanFilament\Generator;

/**
 * 环境变量生成器
 * 
 * 负责生成和管理.env文件配置，支持交互式配置和验证
 */
class EnvironmentGenerator
{
    /**
     * 生成环境变量配置
     * 
     * @param array $options 生成选项
     * @return array 生成结果
     */
    public function generate(array $options = []): array
    {
        $templatePath = __DIR__ . '/../../templates/.env.template';
        $outputPath = base_path() . '/.env';
        
        if (!file_exists($templatePath)) {
            return [
                'success' => false,
                'message' => '环境变量模板文件不存在'
            ];
        }
        
        try {
            $template = file_get_contents($templatePath);
            
            // 获取默认配置
            $defaultConfig = $this->getDefaultEnvironmentConfig();
            $userConfig = array_merge($defaultConfig, $options);
            
            // 验证配置
            $validation = $this->validate($userConfig);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => '环境变量配置验证失败: ' . implode(', ', $validation['errors'])
                ];
            }
            
            // 替换模板变量
            $envContent = $this->replaceTemplateVariables($template, $userConfig);
            
            // 备份现有.env文件
            if (file_exists($outputPath)) {
                $backupPath = $outputPath . '.backup.' . date('Y-m-d_H-i-s');
                copy($outputPath, $backupPath);
            }
            
            // 写入新文件
            file_put_contents($outputPath, $envContent);
            
            // 设置文件权限
            chmod($outputPath, 0600);
            
            return [
                'success' => true,
                'message' => '环境变量配置生成成功',
                'file' => $outputPath
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => '环境变量配置生成失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取默认环境变量配置
     * 
     * @return array 默认配置
     */
    protected function getDefaultEnvironmentConfig(): array
    {
        return [
            'APP_NAME' => env('APP_NAME', 'Webman Filament'),
            'APP_ENV' => env('APP_ENV', 'local'),
            'APP_KEY' => env('APP_KEY', $this->generateAppKey()),
            'APP_DEBUG' => env('APP_DEBUG', true),
            'APP_URL' => env('APP_URL', 'http://localhost'),
            'APP_TIMEZONE' => env('APP_TIMEZONE', 'Asia/Shanghai'),
            'APP_LOCALE' => env('APP_LOCALE', 'zh_CN'),
            'APP_FALLBACK_LOCALE' => env('APP_FALLBACK_LOCALE', 'en'),
            
            // 数据库配置
            'DB_CONNECTION' => env('DB_CONNECTION', 'mysql'),
            'DB_HOST' => env('DB_HOST', '127.0.0.1'),
            'DB_PORT' => env('DB_PORT', '3306'),
            'DB_DATABASE' => env('DB_DATABASE', 'webman_filament'),
            'DB_USERNAME' => env('DB_USERNAME', 'root'),
            'DB_PASSWORD' => env('DB_PASSWORD', ''),
            
            // Filament配置
            'FILAMENT_BRAND' => env('FILAMENT_BRAND', 'Webman Filament'),
            'FILAMENT_CACHE_PREFIX' => env('FILAMENT_CACHE_PREFIX', 'filament_cache'),
            'FILAMENT_DARK_MODE' => env('FILAMENT_DARK_MODE', true),
            'FILAMENT_NOTIFICATIONS' => env('FILAMENT_NOTIFICATIONS', true),
            'FILAMENT_WIDGETS' => env('FILAMENT_WIDGETS', true),
            
            // 缓存配置
            'CACHE_DRIVER' => env('CACHE_DRIVER', 'file'),
            'CACHE_PREFIX' => env('CACHE_PREFIX', 'webman_cache'),
            
            // 会话配置
            'SESSION_DRIVER' => env('SESSION_DRIVER', 'file'),
            'SESSION_LIFETIME' => env('SESSION_LIFETIME', '120'),
            
            // 队列配置
            'QUEUE_CONNECTION' => env('QUEUE_CONNECTION', 'sync'),
            
            // 邮件配置
            'MAIL_MAILER' => env('MAIL_MAILER', 'smtp'),
            'MAIL_HOST' => env('MAIL_HOST', 'smtp.mailtrap.io'),
            'MAIL_PORT' => env('MAIL_PORT', '2525'),
            'MAIL_USERNAME' => env('MAIL_USERNAME', ''),
            'MAIL_PASSWORD' => env('MAIL_PASSWORD', ''),
            'MAIL_ENCRYPTION' => env('MAIL_ENCRYPTION', 'tls'),
            'MAIL_FROM_ADDRESS' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
            'MAIL_FROM_NAME' => env('MAIL_FROM_NAME', '${APP_NAME}'),
            
            // 日志配置
            'LOG_CHANNEL' => env('LOG_CHANNEL', 'daily'),
            'LOG_LEVEL' => env('LOG_LEVEL', 'debug'),
            
            // Redis配置
            'REDIS_HOST' => env('REDIS_HOST', '127.0.0.1'),
            'REDIS_PASSWORD' => env('REDIS_PASSWORD', ''),
            'REDIS_PORT' => env('REDIS_PORT', '6379'),
            
            // 安全配置
            'BCRYPT_ROUNDS' => env('BCRYPT_ROUNDS', '10'),
            'JWT_SECRET' => env('JWT_SECRET', $this->generateJwtSecret()),
            
            // 文件存储配置
            'FILESYSTEM_DISK' => env('FILESYSTEM_DISK', 'local'),
            'AWS_ACCESS_KEY_ID' => env('AWS_ACCESS_KEY_ID', ''),
            'AWS_SECRET_ACCESS_KEY' => env('AWS_SECRET_ACCESS_KEY', ''),
            'AWS_DEFAULT_REGION' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'AWS_BUCKET' => env('AWS_BUCKET', ''),
            'AWS_USE_PATH_STYLE_ENDPOINT' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        ];
    }

    /**
     * 生成应用密钥
     * 
     * @return string 应用密钥
     */
    protected function generateAppKey(): string
    {
        return 'base64:' . base64_encode(random_bytes(32));
    }

    /**
     * 生成JWT密钥
     * 
     * @return string JWT密钥
     */
    protected function generateJwtSecret(): string
    {
        return base64_encode(random_bytes(64));
    }

    /**
     * 验证环境变量配置
     * 
     * @param array $config 配置数组
     * @return array 验证结果
     */
    public function validate(array $config): array
    {
        $errors = [];
        
        // 验证必需字段
        $requiredFields = ['APP_NAME', 'APP_URL'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $errors[] = "缺少必需字段: {$field}";
            }
        }
        
        // 验证URL格式
        if (!empty($config['APP_URL']) && !filter_var($config['APP_URL'], FILTER_VALIDATE_URL)) {
            $errors[] = 'APP_URL格式无效';
        }
        
        // 验证数据库端口
        if (!empty($config['DB_PORT']) && !is_numeric($config['DB_PORT'])) {
            $errors[] = 'DB_PORT必须是数字';
        }
        
        // 验证时区
        if (!empty($config['APP_TIMEZONE']) && !in_array($config['APP_TIMEZONE'], timezone_identifiers_list())) {
            $errors[] = 'APP_TIMEZONE时区格式无效';
        }
        
        // 验证环境变量
        $validEnvs = ['local', 'production', 'testing'];
        if (!empty($config['APP_ENV']) && !in_array($config['APP_ENV'], $validEnvs)) {
            $errors[] = 'APP_ENV必须是: ' . implode(', ', $validEnvs);
        }
        
        // 验证调试模式
        if (isset($config['APP_DEBUG']) && !is_bool($config['APP_DEBUG'])) {
            $errors[] = 'APP_DEBUG必须是布尔值';
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
        $replacements = [];
        
        foreach ($config as $key => $value) {
            $replacements['{{' . $key . '}}'] = $this->formatEnvValue($value);
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * 格式化环境变量值
     * 
     * @param mixed $value 值
     * @return string 格式化后的值
     */
    protected function formatEnvValue($value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        
        if (is_numeric($value)) {
            return (string) $value;
        }
        
        // 如果包含特殊字符，用引号包围
        if (strpos($value, ' ') !== false || strpos($value, '"') !== false || strpos($value, "'") !== false) {
            return '"' . addslashes($value) . '"';
        }
        
        return $value;
    }

    /**
     * 交互式配置
     * 
     * @return array 配置选项
     */
    public function interactiveConfig(): array
    {
        $options = [];
        
        echo "=== 环境变量配置 ===\n\n";
        
        // 应用基本配置
        echo "应用名称 [" . env('APP_NAME', 'Webman Filament') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['APP_NAME'] = $input;
        }
        
        echo "应用环境 (local/production/testing) [" . env('APP_ENV', 'local') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['APP_ENV'] = $input;
        }
        
        echo "应用URL [" . env('APP_URL', 'http://localhost') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['APP_URL'] = $input;
        }
        
        echo "应用时区 [" . env('APP_TIMEZONE', 'Asia/Shanghai') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['APP_TIMEZONE'] = $input;
        }
        
        echo "应用语言 [" . env('APP_LOCALE', 'zh_CN') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['APP_LOCALE'] = $input;
        }
        
        echo "启用调试模式 (y/n) [" . (env('APP_DEBUG', true) ? 'y' : 'n') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['APP_DEBUG'] = strtolower($input) === 'y';
        }
        
        // 数据库配置
        echo "\n=== 数据库配置 ===\n";
        echo "数据库驱动 (mysql/pgsql/sqlite) [" . env('DB_CONNECTION', 'mysql') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['DB_CONNECTION'] = $input;
        }
        
        echo "数据库主机 [" . env('DB_HOST', '127.0.0.1') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['DB_HOST'] = $input;
        }
        
        echo "数据库端口 [" . env('DB_PORT', '3306') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['DB_PORT'] = $input;
        }
        
        echo "数据库名 [" . env('DB_DATABASE', 'webman_filament') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['DB_DATABASE'] = $input;
        }
        
        echo "数据库用户名 [" . env('DB_USERNAME', 'root') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['DB_USERNAME'] = $input;
        }
        
        echo "数据库密码 [" . env('DB_PASSWORD', '') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['DB_PASSWORD'] = $input;
        }
        
        // Filament配置
        echo "\n=== Filament配置 ===\n";
        echo "Filament品牌名称 [" . env('FILAMENT_BRAND', 'Webman Filament') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['FILAMENT_BRAND'] = $input;
        }
        
        echo "启用暗黑模式 (y/n) [" . (env('FILAMENT_DARK_MODE', true) ? 'y' : 'n') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['FILAMENT_DARK_MODE'] = strtolower($input) === 'y';
        }
        
        return $options;
    }

    /**
     * 从现有.env文件加载配置
     * 
     * @param string $filePath .env文件路径
     * @return array 配置数组
     */
    public function loadFromFile(string $filePath = null): array
    {
        $filePath = $filePath ?? base_path() . '/.env';
        
        if (!file_exists($filePath)) {
            return [];
        }
        
        $config = [];
        $lines = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        foreach ($lines as $line) {
            // 跳过注释行
            if (strpos($line, '#') === 0) {
                continue;
            }
            
            // 解析键值对
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // 移除引号
                if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                    (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                $config[$key] = $value;
            }
        }
        
        return $config;
    }

    /**
     * 保存配置到.env文件
     * 
     * @param array $config 配置数组
     * @param string $filePath 输出文件路径
     * @return bool 是否成功
     */
    public function saveToFile(array $config, string $filePath = null): bool
    {
        $filePath = $filePath ?? base_path() . '/.env';
        
        $content = "# Webman Filament 环境变量配置\n";
        $content .= "# 生成时间: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($config as $key => $value) {
            $content .= $key . '=' . $this->formatEnvValue($value) . "\n";
        }
        
        return file_put_contents($filePath, $content) !== false;
    }
}