<?php

namespace FilamentWebmanAdapter\Adapter;

use Illuminate\Support\Facades\Config;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * 配置适配器
 * 
 * 统一管理面板、插件与主题配置
 * 支持环境变量处理、配置缓存、版本管理
 */
class ConfigAdapter
{
    private array $configurations = [];
    private array $environments = [];
    private array $pluginConfigs = [];
    private array $themeConfigs = [];
    private LoggerInterface $logger;
    private bool $cacheEnabled = true;
    private array $configCache = [];

    public function __construct(LoggerInterface $logger, bool $cacheEnabled = true)
    {
        $this->logger = $logger;
        $this->cacheEnabled = $cacheEnabled;
        $this->initializeDefaultConfigs();
        $this->log('info', 'ConfigAdapter initialized');
    }

    /**
     * 初始化默认配置
     */
    private function initializeDefaultConfigs(): void
    {
        // 面板默认配置
        $this->setConfig('filament.panel.default', [
            'id' => 'default',
            'path' => '/admin',
            'url' => null,
            'title' => 'Filament Admin',
            'brand' => null,
            'dark_mode' => false,
            'auth' => [
                'guard' => 'web',
                'login_route' => 'filament.admin.auth.login',
                'password_reset_route' => 'filament.admin.auth.passwords.email'
            ],
            'pages' => [],
            'resources' => [],
            'widgets' => [],
            'plugins' => [],
            'theme' => 'default'
        ]);

        // 认证默认配置
        $this->setConfig('filament.auth', [
            'guard' => 'web',
            'provider' => 'users',
            'password_broker' => 'users',
            'login_route' => 'filament.admin.auth.login',
            'logout_route' => 'filament.admin.auth.logout',
            'password_reset_route' => 'filament.admin.auth.passwords.email'
        ]);

        // 插件默认配置
        $this->setConfig('filament.plugins', [
            'enabled' => [],
            'disabled' => [],
            'settings' => []
        ]);

        // 主题默认配置
        $this->setConfig('filament.theme', [
            'name' => 'default',
            'colors' => [
                'primary' => '#2563eb',
                'secondary' => '#64748b',
                'success' => '#10b981',
                'warning' => '#f59e0b',
                'danger' => '#ef4444'
            ],
            'font_family' => 'Inter, sans-serif',
            'custom_css' => null,
            'custom_js' => null
        ]);
    }

    /**
     * 设置配置值
     */
    public function setConfig(string $key, $value, string $scope = 'global'): void
    {
        try {
            $this->configurations[$scope][$key] = $value;
            
            // 清除缓存
            if ($this->cacheEnabled) {
                unset($this->configCache[$key]);
            }
            
            // 同步到 Laravel Config
            Config::set($key, $value);
            
            $this->log('debug', "Config set: {$key}", ['scope' => $scope, 'value' => $value]);
        } catch (Throwable $e) {
            $this->log('error', "Failed to set config {$key}: " . $e->getMessage());
            throw new \RuntimeException("Failed to set config {$key}", 0, $e);
        }
    }

    /**
     * 获取配置值
     */
    public function getConfig(string $key, $default = null, string $scope = 'global')
    {
        try {
            // 检查缓存
            if ($this->cacheEnabled && isset($this->configCache[$key])) {
                return $this->configCache[$key];
            }

            // 从作用域中获取
            $value = $this->configurations[$scope][$key] ?? null;
            
            // 如果作用域中没有，尝试全局作用域
            if ($value === null && $scope !== 'global') {
                $value = $this->configurations['global'][$key] ?? null;
            }
            
            // 如果仍然没有，尝试从 Laravel Config 获取
            if ($value === null) {
                $value = Config::get($key, $default);
            }
            
            // 如果还是没有，使用默认值
            if ($value === null) {
                $value = $default;
            }
            
            // 缓存结果
            if ($this->cacheEnabled) {
                $this->configCache[$key] = $value;
            }
            
            return $value;
        } catch (Throwable $e) {
            $this->log('error', "Failed to get config {$key}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * 检查配置是否存在
     */
    public function hasConfig(string $key, string $scope = 'global'): bool
    {
        try {
            return isset($this->configurations[$scope][$key]) || 
                   isset($this->configurations['global'][$key]) || 
                   Config::has($key);
        } catch (Throwable $e) {
            $this->log('error', "Failed to check config existence {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 删除配置
     */
    public function removeConfig(string $key, string $scope = 'global'): bool
    {
        try {
            $removed = false;
            
            if (isset($this->configurations[$scope][$key])) {
                unset($this->configurations[$scope][$key]);
                $removed = true;
            }
            
            if ($scope !== 'global' && isset($this->configurations['global'][$key])) {
                unset($this->configurations['global'][$key]);
                $removed = true;
            }
            
            // 清除缓存
            if ($this->cacheEnabled) {
                unset($this->configCache[$key]);
            }
            
            if ($removed) {
                $this->log('debug', "Config removed: {$key}", ['scope' => $scope]);
            }
            
            return $removed;
        } catch (Throwable $e) {
            $this->log('error', "Failed to remove config {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 获取所有配置
     */
    public function getAllConfigs(string $scope = 'global'): array
    {
        try {
            $globalConfigs = $this->configurations['global'] ?? [];
            $scopeConfigs = $this->configurations[$scope] ?? [];
            
            return array_merge($globalConfigs, $scopeConfigs);
        } catch (Throwable $e) {
            $this->log('error', "Failed to get all configs for scope {$scope}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * 配置面板设置
     */
    public function configurePanel(string $panelId, array $config): void
    {
        try {
            $panelConfig = $this->getConfig("filament.panel.{$panelId}", []);
            $mergedConfig = array_merge($panelConfig, $config);
            
            $this->setConfig("filament.panel.{$panelId}", $mergedConfig);
            $this->log('info', "Panel configured: {$panelId}", ['config' => $config]);
        } catch (Throwable $e) {
            $this->log('error', "Failed to configure panel {$panelId}: " . $e->getMessage());
            throw new \RuntimeException("Failed to configure panel {$panelId}", 0, $e);
        }
    }

    /**
     * 获取面板配置
     */
    public function getPanelConfig(string $panelId = 'default'): array
    {
        return $this->getConfig("filament.panel.{$panelId}", []);
    }

    /**
     * 配置插件
     */
    public function configurePlugin(string $pluginName, array $config): void
    {
        try {
            $pluginConfigs = $this->getConfig('filament.plugins.settings', []);
            $pluginConfigs[$pluginName] = array_merge($pluginConfigs[$pluginName] ?? [], $config);
            
            $this->setConfig('filament.plugins.settings', $pluginConfigs);
            $this->log('info', "Plugin configured: {$pluginName}", ['config' => $config]);
        } catch (Throwable $e) {
            $this->log('error', "Failed to configure plugin {$pluginName}: " . $e->getMessage());
            throw new \RuntimeException("Failed to configure plugin {$pluginName}", 0, $e);
        }
    }

    /**
     * 获取插件配置
     */
    public function getPluginConfig(string $pluginName): array
    {
        $pluginConfigs = $this->getConfig('filament.plugins.settings', []);
        return $pluginConfigs[$pluginName] ?? [];
    }

    /**
     * 启用插件
     */
    public function enablePlugin(string $pluginName): void
    {
        try {
            $enabledPlugins = $this->getConfig('filament.plugins.enabled', []);
            $disabledPlugins = $this->getConfig('filament.plugins.disabled', []);
            
            if (!in_array($pluginName, $enabledPlugins)) {
                $enabledPlugins[] = $pluginName;
                $this->setConfig('filament.plugins.enabled', $enabledPlugins);
            }
            
            // 从禁用列表中移除
            $disabledPlugins = array_filter($disabledPlugins, fn($p) => $p !== $pluginName);
            $this->setConfig('filament.plugins.disabled', $disabledPlugins);
            
            $this->log('info', "Plugin enabled: {$pluginName}");
        } catch (Throwable $e) {
            $this->log('error', "Failed to enable plugin {$pluginName}: " . $e->getMessage());
            throw new \RuntimeException("Failed to enable plugin {$pluginName}", 0, $e);
        }
    }

    /**
     * 禁用插件
     */
    public function disablePlugin(string $pluginName): void
    {
        try {
            $disabledPlugins = $this->getConfig('filament.plugins.disabled', []);
            $enabledPlugins = $this->getConfig('filament.plugins.enabled', []);
            
            if (!in_array($pluginName, $disabledPlugins)) {
                $disabledPlugins[] = $pluginName;
                $this->setConfig('filament.plugins.disabled', $disabledPlugins);
            }
            
            // 从启用列表中移除
            $enabledPlugins = array_filter($enabledPlugins, fn($p) => $p !== $pluginName);
            $this->setConfig('filament.plugins.enabled', $enabledPlugins);
            
            $this->log('info', "Plugin disabled: {$pluginName}");
        } catch (Throwable $e) {
            $this->log('error', "Failed to disable plugin {$pluginName}: " . $e->getMessage());
            throw new \RuntimeException("Failed to disable plugin {$pluginName}", 0, $e);
        }
    }

    /**
     * 检查插件是否启用
     */
    public function isPluginEnabled(string $pluginName): bool
    {
        $enabledPlugins = $this->getConfig('filament.plugins.enabled', []);
        $disabledPlugins = $this->getConfig('filament.plugins.disabled', []);
        
        return in_array($pluginName, $enabledPlugins) && !in_array($pluginName, $disabledPlugins);
    }

    /**
     * 配置主题
     */
    public function configureTheme(array $themeConfig): void
    {
        try {
            $currentTheme = $this->getConfig('filament.theme', []);
            $mergedTheme = array_merge($currentTheme, $themeConfig);
            
            $this->setConfig('filament.theme', $mergedTheme);
            $this->log('info', "Theme configured", ['config' => $themeConfig]);
        } catch (Throwable $e) {
            $this->log('error', "Failed to configure theme: " . $e->getMessage());
            throw new \RuntimeException("Failed to configure theme", 0, $e);
        }
    }

    /**
     * 获取主题配置
     */
    public function getThemeConfig(): array
    {
        return $this->getConfig('filament.theme', []);
    }

    /**
     * 设置环境变量
     */
    public function setEnvironment(string $key, $value, string $scope = 'global'): void
    {
        try {
            $this->environments[$scope][$key] = $value;
            putenv("{$key}={$value}");
            
            $this->log('debug', "Environment variable set: {$key}", ['scope' => $scope, 'value' => $value]);
        } catch (Throwable $e) {
            $this->log('error', "Failed to set environment variable {$key}: " . $e->getMessage());
            throw new \RuntimeException("Failed to set environment variable {$key}", 0, $e);
        }
    }

    /**
     * 获取环境变量
     */
    public function getEnvironment(string $key, $default = null, string $scope = 'global')
    {
        try {
            // 首先从配置的环境变量中获取
            if (isset($this->environments[$scope][$key])) {
                return $this->environments[$scope][$key];
            }
            
            // 然后从全局作用域中获取
            if ($scope !== 'global' && isset($this->environments['global'][$key])) {
                return $this->environments['global'][$key];
            }
            
            // 最后从系统环境变量中获取
            $value = getenv($key);
            return $value !== false ? $value : $default;
        } catch (Throwable $e) {
            $this->log('error', "Failed to get environment variable {$key}: " . $e->getMessage());
            return $default;
        }
    }

    /**
     * 批量加载配置
     */
    public function loadConfigs(array $configs, string $scope = 'global'): void
    {
        try {
            foreach ($configs as $key => $value) {
                $this->setConfig($key, $value, $scope);
            }
            
            $this->log('info', "Batch configs loaded", ['count' => count($configs), 'scope' => $scope]);
        } catch (Throwable $e) {
            $this->log('error', "Failed to load batch configs: " . $e->getMessage());
            throw new \RuntimeException("Failed to load batch configs", 0, $e);
        }
    }

    /**
     * 验证配置
     */
    public function validateConfig(string $key, array $rules): bool
    {
        try {
            $value = $this->getConfig($key);
            
            foreach ($rules as $rule => $ruleValue) {
                switch ($rule) {
                    case 'required':
                        if ($ruleValue && empty($value)) {
                            throw new \InvalidArgumentException("Config {$key} is required");
                        }
                        break;
                    
                    case 'type':
                        $expectedType = $ruleValue;
                        $actualType = gettype($value);
                        if ($expectedType !== $actualType) {
                            throw new \InvalidArgumentException("Config {$key} must be of type {$expectedType}, {$actualType} given");
                        }
                        break;
                    
                    case 'in':
                        if (!in_array($value, $ruleValue)) {
                            throw new \InvalidArgumentException("Config {$key} must be one of: " . implode(', ', $ruleValue));
                        }
                        break;
                    
                    case 'min':
                        if (is_numeric($value) && $value < $ruleValue) {
                            throw new \InvalidArgumentException("Config {$key} must be at least {$ruleValue}");
                        }
                        break;
                    
                    case 'max':
                        if (is_numeric($value) && $value > $ruleValue) {
                            throw new \InvalidArgumentException("Config {$key} must not exceed {$ruleValue}");
                        }
                        break;
                }
            }
            
            return true;
        } catch (Throwable $e) {
            $this->log('error', "Config validation failed for {$key}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * 清除配置缓存
     */
    public function clearConfigCache(): void
    {
        $this->configCache = [];
        $this->log('info', 'Config cache cleared');
    }

    /**
     * 获取配置统计信息
     */
    public function getConfigStatistics(): array
    {
        return [
            'total_configs' => count($this->configurations),
            'cache_enabled' => $this->cacheEnabled,
            'cache_size' => count($this->configCache),
            'environments' => array_keys($this->environments),
            'scopes' => array_keys($this->configurations)
        ];
    }

    /**
     * 日志记录
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if ($this->logger) {
            $this->logger->log($level, $message, $context);
        }
    }
}