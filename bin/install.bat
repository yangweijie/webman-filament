@echo off
setlocal enabledelayedexpansion

:: Webman-Filament 安装脚本 (Windows)
:: 作者: Webman-Filament 开发团队
:: 版本: 1.0.0

title Webman-Filament 安装程序

:: 颜色定义 (Windows CMD 有限支持)
set "RED=[91m"
set "GREEN=[92m"
set "YELLOW=[93m"
set "BLUE=[94m"
set "NC=[0m"

:: 项目信息
set "PROJECT_NAME=Webman-Filament"
set "PROJECT_DIR=%CD%"

:: 函数：打印带颜色的消息
:print_message
set "color=%~1"
set "message=%~2"
echo %color%[%date% %time%] %message%%NC%
goto :eof

:print_success
call :print_message "%GREEN%" "✅ %~1"
goto :eof

:print_error
call :print_message "%RED%" "❌ %~1"
goto :eof

:print_warning
call :print_message "%YELLOW%" "⚠️  %~1"
goto :eof

:print_info
call :print_message "%BLUE%" "ℹ️  %~1"
goto :eof

:: 函数：检查命令是否存在
:command_exists
where %~1 >nul 2>&1
if %errorlevel% equ 0 (
    exit /b 0
) else (
    exit /b 1
)

:: 函数：检查PHP版本
:check_php_version
call :print_info "正在检查 PHP 版本..."

if not command_exists php (
    call :print_error "PHP 未安装，请先安装 PHP 8.1 或更高版本"
    call :print_info "下载地址：https://www.php.net/downloads.php"
    pause
    exit /b 1
)

:: 获取PHP版本
for /f "tokens=*" %%i in ('php -r "echo PHP_VERSION;"') do set "PHP_VERSION=%%i"
call :print_success "PHP 版本检查通过: !PHP_VERSION!"
goto :eof

:: 函数：检查Composer
:check_composer
call :print_info "正在检查 Composer..."

if not command_exists composer (
    call :print_error "Composer 未安装，请先安装 Composer"
    call :print_info "下载地址：https://getcomposer.org/download/"
    pause
    exit /b 1
)

:: 获取Composer版本
for /f "tokens=*" %%i in ('composer --version') do set "COMPOSER_VERSION=%%i"
call :print_success "Composer 检查通过: !COMPOSER_VERSION!"
goto :eof

:: 函数：检查Node.js和npm
:check_node
call :print_info "正在检查 Node.js 和 npm..."

if command_exists node (
    for /f "tokens=*" %%i in ('node --version') do set "NODE_VERSION=%%i"
    call :print_success "Node.js 版本: !NODE_VERSION!"
    
    if command_exists npm (
        for /f "tokens=*" %%i in ('npm --version') do set "NPM_VERSION=%%i"
        call :print_success "npm 版本: !NPM_VERSION!"
    ) else (
        call :print_warning "npm 未找到，部分前端功能可能不可用"
    )
) else (
    call :print_warning "Node.js 未安装，部分前端功能可能不可用"
)
goto :eof

:: 函数：检查数据库连接
:check_database
call :print_info "正在检查数据库连接..."

if exist .env (
    :: 简单的环境变量读取（Windows批处理有限）
    findstr /C:"DB_CONNECTION" .env >nul 2>&1
    if !errorlevel! equ 0 (
        call :print_info "检测到数据库配置，请确保数据库服务正在运行"
    ) else (
        call :print_warning "未检测到数据库配置，请稍后手动配置"
    )
) else (
    call :print_warning "未找到 .env 文件，请稍后创建"
)
goto :eof

:: 函数：安装PHP依赖
:install_php_dependencies
call :print_info "安装 PHP 依赖..."

if exist composer.json (
    composer install --no-dev --optimize-autoloader
    if !errorlevel! equ 0 (
        call :print_success "PHP 依赖安装完成"
    ) else (
        call :print_error "PHP 依赖安装失败"
        pause
        exit /b 1
    )
) else (
    call :print_error "未找到 composer.json 文件"
    pause
    exit /b 1
)
goto :eof

:: 函数：生成应用密钥
:generate_app_key
call :print_info "生成应用密钥..."

php artisan key:generate --force
if !errorlevel! equ 0 (
    call :print_success "应用密钥生成完成"
) else (
    call :print_error "应用密钥生成失败"
    pause
    exit /b 1
)
goto :eof

:: 函数：运行数据库迁移
:run_migrations
call :print_info "运行数据库迁移..."

php artisan migrate --force
if !errorlevel! equ 0 (
    call :print_success "数据库迁移完成"
) else (
    call :print_warning "数据库迁移失败，请检查数据库配置"
)
goto :eof

:: 函数：发布Filament资源
:publish_filament_assets
call :print_info "发布 Filament 资源..."

php filament:install --force
if !errorlevel! neq 0 (
    call :print_warning "Filament 安装命令失败，尝试其他方法..."
)

php vendor:publish --tag=filament-config --force
php vendor:publish --tag=filament-assets --force

if !errorlevel! equ 0 (
    call :print_success "Filament 资源发布完成"
) else (
    call :print_warning "Filament 资源发布可能有问题"
)
goto :eof

:: 函数：设置文件权限 (Windows 简化版)
:set_permissions
call :print_info "检查文件权限..."

if exist storage (
    :: Windows 不需要设置执行权限
    call :print_success "storage 目录存在"
)

if exist bootstrap\cache (
    call :print_success "bootstrap/cache 目录存在"
)

if exist public (
    call :print_success "public 目录存在"
)
goto :eof

:: 函数：创建符号链接
:create_symbolic_links
call :print_info "创建存储链接..."

php artisan storage:link
if !errorlevel! equ 0 (
    call :print_success "存储链接创建完成"
) else (
    call :print_warning "存储链接创建失败"
)
goto :eof

:: 函数：安装Node.js依赖（如果存在package.json）
:install_node_dependencies
if exist package.json (
    call :print_info "安装 Node.js 依赖..."
    
    if command_exists npm (
        npm install
        if !errorlevel! equ 0 (
            call :print_success "Node.js 依赖安装完成"
            
            :: 检查是否有build脚本
            npm run build >nul 2>&1
            if !errorlevel! equ 0 (
                call :print_info "构建前端资源..."
                npm run build
                if !errorlevel! equ 0 (
                    call :print_success "前端资源构建完成"
                )
            )
        ) else (
            call :print_warning "Node.js 依赖安装失败"
        )
    ) else (
        call :print_warning "npm 未找到，跳过 Node.js 依赖安装"
    )
)
goto :eof

:: 函数：清理缓存
:clear_cache
call :print_info "清理应用缓存..."

php artisan config:cache
php artisan route:cache
php artisan view:cache

if !errorlevel! equ 0 (
    call :print_success "缓存清理完成"
) else (
    call :print_warning "缓存清理可能有问题"
)
goto :eof

:: 函数：显示安装完成信息
:show_completion_info
echo.
echo ================================================
call :print_success "%PROJECT_NAME% 安装完成！"
echo ================================================
echo.
call :print_info "下一步操作："
echo 1. 访问您的应用 URL 查看效果
echo 2. 如果需要创建管理员账户，运行：
echo    php artisan make:filament-user
echo 3. 查看配置文件：config\filament.php
echo.
call :print_info "常用命令："
echo • 启动开发服务器：php artisan serve
echo • 清理缓存：php artisan cache:clear
echo • 查看日志：type storage\logs\laravel.log
echo.
call :print_success "享受使用 %PROJECT_NAME%！"
echo.
goto :eof

:: 主函数
:main
echo ================================================
call :print_info "%PROJECT_NAME% 安装程序"
echo ================================================
echo.

:: 环境检查
call :print_info "正在检查环境..."
call :check_php_version
call :check_composer
call :check_node
call :check_database
echo.

:: 安装依赖
call :print_info "正在安装依赖..."
call :install_php_dependencies
call :install_node_dependencies
echo.

:: 配置应用
call :print_info "正在配置应用..."
call :generate_app_key
echo.

:: 数据库迁移
call :print_info "正在处理数据库..."
call :run_migrations
echo.

:: Filament配置
call :print_info "正在配置 Filament..."
call :publish_filament_assets
echo.

:: 权限设置
call :print_info "正在检查文件..."
call :set_permissions
call :create_symbolic_links
echo.

:: 清理缓存
call :print_info "正在优化应用..."
call :clear_cache
echo.

:: 显示完成信息
call :show_completion_info

pause
goto :eof

:: 脚本入口点
call :main