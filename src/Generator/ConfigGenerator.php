<?php

namespace WebmanFilament\Generator;

use WebmanFilament\Generator\EnvironmentGenerator;
use WebmanFilament\Generator\DatabaseGenerator;

/**
 * 配置生成器类
 * 
 * 提供统一的配置生成接口，支持交互式配置、默认值设置、配置验证等功能
 */
class ConfigGenerator
{
    protected EnvironmentGenerator $environmentGenerator;
    protected DatabaseGenerator $databaseGenerator;

    public function __construct()
    {
        $this->environmentGenerator = new EnvironmentGenerator();
        $this->databaseGenerator = new DatabaseGenerator();
    }

    /**
     * 生成所有配置
     * 
     * @param array $options 生成选项
     * @return array 生成结果
     */
    public function generateAll(array $options = []): array
    {
        $results = [];
        
        try {
            // 生成环境变量配置
            $results['environment'] = $this->generateEnvironment($options);
            
            // 生成数据库配置
            $results['database'] = $this->generateDatabase($options);
            
            // 生成Filament配置
            $results['filament'] = $this->generateFilament($options);
            
            $results['success'] = true;
            $results['message'] = '所有配置生成成功';
            
        } catch (\Exception $e) {
            $results['success'] = false;
            $results['message'] = '配置生成失败: ' . $e->getMessage();
        }
        
        return $results;
    }

    /**
     * 生成环境变量配置
     * 
     * @param array $options 生成选项
     * @return array 生成结果
     */
    public function generateEnvironment(array $options = []): array
    {
        return $this->environmentGenerator->generate($options);
    }

    /**
     * 生成数据库配置
     * 
     * @param array $options 生成选项
     * @return array 生成结果
     */
    public function generateDatabase(array $options = []): array
    {
        return $this->databaseGenerator->generate($options);
    }

    /**
     * 生成Filament配置
     * 
     * @param array $options 生成选项
     * @return array 生成结果
     */
    public function generateFilament(array $options = []): array
    {
        $templatePath = __DIR__ . '/../../templates/config/filament.php.template';
        $outputPath = config_path() . '/filament.php';
        
        if (!file_exists($templatePath)) {
            return [
                'success' => false,
                'message' => 'Filament配置模板文件不存在'
            ];
        }
        
        try {
            $template = file_get_contents($templatePath);
            
            // 获取默认配置
            $defaultConfig = $this->getDefaultFilamentConfig();
            $userConfig = $options['filament'] ?? $defaultConfig;
            
            // 验证配置
            $validation = $this->validateFilamentConfig($userConfig);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => 'Filament配置验证失败: ' . implode(', ', $validation['errors'])
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
                'message' => 'Filament配置生成成功',
                'file' => $outputPath
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Filament配置生成失败: ' . $e->getMessage()
            ];
        }
    }

    /**
     * 获取默认Filament配置
     * 
     * @return array 默认配置
     */
    protected function getDefaultFilamentConfig(): array
    {
        return [
            'app_name' => env('APP_NAME', 'Webman Filament'),
            'url' => env('APP_URL', 'http://localhost'),
            'timezone' => env('APP_TIMEZONE', 'Asia/Shanghai'),
            'locale' => env('APP_LOCALE', 'zh_CN'),
            'brand' => env('FILAMENT_BRAND', 'Webman Filament'),
            'database_connection' => env('DB_CONNECTION', 'mysql'),
            'cache_prefix' => env('FILAMENT_CACHE_PREFIX', 'filament_cache'),
            'enable_dark_mode' => env('FILAMENT_DARK_MODE', true),
            'enable_notifications' => env('FILAMENT_NOTIFICATIONS', true),
            'enable_widgets' => env('FILAMENT_WIDGETS', true),
        ];
    }

    /**
     * 验证Filament配置
     * 
     * @param array $config 配置数组
     * @return array 验证结果
     */
    protected function validateFilamentConfig(array $config): array
    {
        $errors = [];
        
        // 验证必需字段
        $requiredFields = ['app_name', 'url'];
        foreach ($requiredFields as $field) {
            if (empty($config[$field])) {
                $errors[] = "缺少必需字段: {$field}";
            }
        }
        
        // 验证URL格式
        if (!empty($config['url']) && !filter_var($config['url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'URL格式无效';
        }
        
        // 验证时区
        if (!empty($config['timezone']) && !in_array($config['timezone'], timezone_identifiers_list())) {
            $errors[] = '时区格式无效';
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
            '{{APP_NAME}}' => $config['app_name'],
            '{{APP_URL}}' => $config['url'],
            '{{APP_TIMEZONE}}' => $config['timezone'],
            '{{APP_LOCALE}}' => $config['locale'],
            '{{FILAMENT_BRAND}}' => $config['brand'],
            '{{DB_CONNECTION}}' => $config['database_connection'],
            '{{CACHE_PREFIX}}' => $config['cache_prefix'],
            '{{DARK_MODE}}' => $config['enable_dark_mode'] ? 'true' : 'false',
            '{{NOTIFICATIONS}}' => $config['enable_notifications'] ? 'true' : 'false',
            '{{WIDGETS}}' => $config['enable_widgets'] ? 'true' : 'false',
        ];
        
        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * 交互式配置向导
     * 
     * @param array $options 初始选项
     * @return array 配置结果
     */
    public function interactiveWizard(array $options = []): array
    {
        echo "\n=== Webman Filament 配置生成向导 ===\n\n";
        
        // 环境变量配置
        echo "1. 配置环境变量\n";
        $envOptions = $this->interactiveEnvironmentConfig();
        
        // 数据库配置
        echo "\n2. 配置数据库\n";
        $dbOptions = $this->interactiveDatabaseConfig();
        
        // Filament配置
        echo "\n3. 配置Filament\n";
        $filamentOptions = $this->interactiveFilamentConfig();
        
        $options = array_merge($options, [
            'environment' => $envOptions,
            'database' => $dbOptions,
            'filament' => $filamentOptions,
        ]);
        
        echo "\n正在生成配置...\n";
        
        return $this->generateAll($options);
    }

    /**
     * 交互式环境变量配置
     * 
     * @return array 配置选项
     */
    protected function interactiveEnvironmentConfig(): array
    {
        $options = [];
        
        echo "应用名称 [" . env('APP_NAME', 'Webman Filament') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['APP_NAME'] = $input;
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
        
        return $options;
    }

    /**
     * 交互式数据库配置
     * 
     * @return array 配置选项
     */
    protected function interactiveDatabaseConfig(): array
    {
        return $this->databaseGenerator->interactiveConfig();
    }

    /**
     * 交互式Filament配置
     * 
     * @return array 配置选项
     */
    protected function interactiveFilamentConfig(): array
    {
        $options = [];
        
        echo "Filament品牌名称 [" . env('FILAMENT_BRAND', 'Webman Filament') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['brand'] = $input;
        }
        
        echo "启用暗黑模式 (y/n) [" . (env('FILAMENT_DARK_MODE', true) ? 'y' : 'n') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['enable_dark_mode'] = strtolower($input) === 'y';
        }
        
        echo "启用通知 (y/n) [" . (env('FILAMENT_NOTIFICATIONS', true) ? 'y' : 'n') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['enable_notifications'] = strtolower($input) === 'y';
        }
        
        echo "启用小组件 (y/n) [" . (env('FILAMENT_WIDGETS', true) ? 'y' : 'n') . "]: ";
        $input = trim(fgets(STDIN));
        if (!empty($input)) {
            $options['enable_widgets'] = strtolower($input) === 'y';
        }
        
        return $options;
    }

    /**
     * 验证配置
     * 
     * @param array $config 配置数组
     * @return array 验证结果
     */
    public function validateConfig(array $config): array
    {
        $errors = [];
        
        // 验证环境变量配置
        if (isset($config['environment'])) {
            $envValidation = $this->environmentGenerator->validate($config['environment']);
            if (!$envValidation['valid']) {
                $errors = array_merge($errors, $envValidation['errors']);
            }
        }
        
        // 验证数据库配置
        if (isset($config['database'])) {
            $dbValidation = $this->databaseGenerator->validate($config['database']);
            if (!$dbValidation['valid']) {
                $errors = array_merge($errors, $dbValidation['errors']);
            }
        }
        
        // 验证Filament配置
        if (isset($config['filament'])) {
            $filamentValidation = $this->validateFilamentConfig($config['filament']);
            if (!$filamentValidation['valid']) {
                $errors = array_merge($errors, $filamentValidation['errors']);
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * 获取配置模板
     * 
     * @param string $type 模板类型
     * @return array 模板信息
     */
    public function getTemplate(string $type): array
    {
        $templates = [
            'environment' => [
                'file' => __DIR__ . '/../../templates/.env.template',
                'description' => '环境变量配置模板'
            ],
            'database' => [
                'file' => __DIR__ . '/../../templates/config/database.php.template',
                'description' => '数据库配置模板'
            ],
            'filament' => [
                'file' => __DIR__ . '/../../templates/config/filament.php.template',
                'description' => 'Filament配置模板'
            ]
        ];
        
        return $templates[$type] ?? [];
    }
}