<?php

namespace WebmanFilament\Support;

/**
 * 日志记录器类
 * 
 * 用于在安装、配置和验证脚本中记录日志信息
 */
class Logger
{
    private string $logDir;
    private string $logFile;
    
    public function __construct(string $logDir = null)
    {
        $this->logDir = $logDir ?? dirname(__DIR__) . '/storage/logs';
        $this->logFile = $this->logDir . '/scripts.log';
        $this->ensureLogDirectory();
    }
    
    /**
     * 确保日志目录存在
     */
    private function ensureLogDirectory(): void
    {
        if (!is_dir($this->logDir)) {
            mkdir($this->logDir, 0755, true);
        }
    }
    
    /**
     * 记录日志
     */
    public function log(string $level, string $message, array $context = []): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = sprintf(
            "[%s] %s: %s %s\n",
            $timestamp,
            strtoupper($level),
            $message,
            !empty($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : ''
        );
        
        // 写入日志文件
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * 记录信息级别日志
     */
    public function info(string $message, array $context = []): void
    {
        $this->log('info', $message, $context);
    }
    
    /**
     * 记录警告级别日志
     */
    public function warning(string $message, array $context = []): void
    {
        $this->log('warning', $message, $context);
    }
    
    /**
     * 记录错误级别日志
     */
    public function error(string $message, array $context = []): void
    {
        $this->log('error', $message, $context);
    }
    
    /**
     * 记录调试级别日志
     */
    public function debug(string $message, array $context = []): void
    {
        $this->log('debug', $message, $context);
    }
    
    /**
     * 清理旧日志文件
     */
    public function cleanup(int $daysToKeep = 30): void
    {
        $files = glob($this->logDir . '/*.log');
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);
        
        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
    
    /**
     * 获取日志文件路径
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }
    
    /**
     * 读取日志内容
     */
    public function getLogs(int $lines = 100): array
    {
        if (!file_exists($this->logFile)) {
            return [];
        }
        
        $logs = [];
        $file = new \SplFileObject($this->logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $startLine = max(0, $totalLines - $lines);
        $file->seek($startLine);
        
        while (!$file->eof()) {
            $logs[] = trim($file->fgets());
        }
        
        return array_filter($logs);
    }
}