<?php

/**
 * 配置生成器测试脚本
 */

require_once __DIR__ . '/vendor/autoload.php';

use WebmanFilament\Generator\ConfigGenerator;

echo "=== Webman Filament 配置生成器测试 ===\n\n";

// 创建配置生成器实例
$generator = new ConfigGenerator();

echo "1. 测试获取模板信息\n";
$templates = [
    'environment' => $generator->getTemplate('environment'),
    'database' => $generator->getTemplate('database'),
    'filament' => $generator->getTemplate('filament')
];

foreach ($templates as $type => $template) {
    echo "   {$type}: " . ($template['description'] ?? 'N/A') . "\n";
    echo "   文件: " . ($template['file'] ?? 'N/A') . "\n";
    echo "   存在: " . (file_exists($template['file'] ?? '') ? '是' : '否') . "\n\n";
}

echo "2. 测试配置验证\n";
$testConfig = [
    'environment' => [
        'APP_NAME' => 'Test App',
        'APP_URL' => 'http://localhost'
    ],
    'filament' => [
        'app_name' => 'Test App',
        'url' => 'http://localhost',
        'timezone' => 'Asia/Shanghai'
    ]
];

$validation = $generator->validateConfig($testConfig);
echo "   验证结果: " . ($validation['valid'] ? '通过' : '失败') . "\n";
if (!$validation['valid']) {
    echo "   错误: " . implode(', ', $validation['errors']) . "\n";
}

echo "\n3. 测试生成配置（模拟）\n";
echo "   环境变量生成: " . ($generator->generateEnvironment() ? '成功' : '失败') . "\n";
echo "   数据库配置生成: " . ($generator->generateDatabase() ? '成功' : '失败') . "\n";
echo "   Filament配置生成: " . ($generator->generateFilament() ? '成功' : '失败') . "\n";

echo "\n=== 测试完成 ===\n";
echo "\n要使用交互式配置向导，请运行:\n";
echo "php -r \"require 'vendor/autoload.php'; use WebmanFilament\\Generator\\ConfigGenerator; (new ConfigGenerator())->interactiveWizard();\"\n";