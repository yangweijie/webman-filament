#!/bin/bash

# Webman-Filament 安装脚本 (Linux/macOS)
# 作者: Webman-Filament 开发团队
# 版本: 1.0.0

set -e  # 遇到错误立即退出

# 颜色定义
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# 项目信息
PROJECT_NAME="Webman-Filament"
PROJECT_DIR=$(pwd)

# 函数：打印带颜色的消息
print_message() {
    local color=$1
    local message=$2
    echo -e "${color}[$(date '+%Y-%m-%d %H:%M:%S')] ${message}${NC}"
}

print_success() {
    print_message "$GREEN" "✅ $1"
}

print_error() {
    print_message "$RED" "❌ $1"
}

print_warning() {
    print_message "$YELLOW" "⚠️  $1"
}

print_info() {
    print_message "$BLUE" "ℹ️  $1"
}

# 函数：检查命令是否存在
command_exists() {
    command -v "$1" >/dev/null 2>&1
}

# 函数：检查PHP版本
check_php_version() {
    if ! command_exists php; then
        print_error "PHP 未安装，请先安装 PHP 8.1 或更高版本"
        exit 1
    fi
    
    PHP_VERSION=$(php -r "echo PHP_VERSION;")
    PHP_MAJOR=$(echo $PHP_VERSION | cut -d. -f1)
    PHP_MINOR=$(echo $PHP_VERSION | cut -d. -f2)
    
    if [ "$PHP_MAJOR" -lt 8 ] || ([ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -lt 1 ]); then
        print_error "PHP 版本过低: $PHP_VERSION，需要 8.1 或更高版本"
        exit 1
    fi
    
    print_success "PHP 版本检查通过: $PHP_VERSION"
}

# 函数：检查Composer
check_composer() {
    if ! command_exists composer; then
        print_error "Composer 未安装，请先安装 Composer"
        print_info "安装命令: curl -sS https://getcomposer.org/installer | php"
        exit 1
    fi
    
    COMPOSER_VERSION=$(composer --version | head -n1)
    print_success "Composer 检查通过: $COMPOSER_VERSION"
}

# 函数：检查Node.js和npm
check_node() {
    if command_exists node; then
        NODE_VERSION=$(node --version)
        NPM_VERSION=$(npm --version)
        print_success "Node.js 版本: $NODE_VERSION"
        print_success "npm 版本: $NPM_VERSION"
    else
        print_warning "Node.js 未安装，部分前端功能可能不可用"
    fi
}

# 函数：检查数据库连接
check_database() {
    if [ -f .env ]; then
        source .env
        
        if [ ! -z "$DB_CONNECTION" ] && [ "$DB_CONNECTION" != "sqlite" ]; then
            case $DB_CONNECTION in
                mysql)
                    if command_exists mysql; then
                        mysql -h"${DB_HOST:-localhost}" -u"${DB_USERNAME:-root}" -p"${DB_PASSWORD:-}" -e "SELECT 1;" 2>/dev/null
                        if [ $? -eq 0 ]; then
                            print_success "MySQL 数据库连接正常"
                        else
                            print_warning "MySQL 数据库连接失败，请检查配置"
                        fi
                    fi
                    ;;
                pgsql)
                    if command_exists psql; then
                        PGPASSWORD="$DB_PASSWORD" psql -h"${DB_HOST:-localhost}" -U"${DB_USERNAME:-postgres}" -c "SELECT 1;" 2>/dev/null
                        if [ $? -eq 0 ]; then
                            print_success "PostgreSQL 数据库连接正常"
                        else
                            print_warning "PostgreSQL 数据库连接失败，请检查配置"
                        fi
                    fi
                    ;;
            esac
        fi
    fi
}

# 函数：安装PHP依赖
install_php_dependencies() {
    print_info "安装 PHP 依赖..."
    
    if [ -f composer.json ]; then
        composer install --no-dev --optimize-autoloader
        print_success "PHP 依赖安装完成"
    else
        print_error "未找到 composer.json 文件"
        exit 1
    fi
}

# 函数：生成应用密钥
generate_app_key() {
    print_info "生成应用密钥..."
    
    if command_exists php; then
        php artisan key:generate --force
        print_success "应用密钥生成完成"
    else
        print_error "PHP 不可用，无法生成应用密钥"
        exit 1
    fi
}

# 函数：运行数据库迁移
run_migrations() {
    print_info "运行数据库迁移..."
    
    if command_exists php; then
        php artisan migrate --force
        print_success "数据库迁移完成"
    else
        print_error "PHP 不可用，无法运行数据库迁移"
        exit 1
    fi
}

# 函数：发布Filament资源
publish_filament_assets() {
    print_info "发布 Filament 资源..."
    
    if command_exists php; then
        php artisan filament:install --force
        php artisan vendor:publish --tag=filament-config --force
        php artisan vendor:publish --tag=filament-assets --force
        print_success "Filament 资源发布完成"
    else
        print_error "PHP 不可用，无法发布 Filament 资源"
        exit 1
    fi
}

# 函数：设置文件权限
set_permissions() {
    print_info "设置文件权限..."
    
    # 设置存储和缓存目录权限
    if [ -d storage ]; then
        chmod -R 775 storage
        print_success "storage 目录权限设置完成"
    fi
    
    if [ -d bootstrap/cache ]; then
        chmod -R 775 bootstrap/cache
        print_success "bootstrap/cache 目录权限设置完成"
    fi
    
    # 设置公共目录权限
    if [ -d public ]; then
        chmod -R 755 public
        print_success "public 目录权限设置完成"
    fi
}

# 函数：创建符号链接
create_symbolic_links() {
    print_info "创建符号链接..."
    
    if command_exists php; then
        php artisan storage:link
        print_success "存储链接创建完成"
    fi
}

# 函数：安装Node.js依赖（如果存在package.json）
install_node_dependencies() {
    if [ -f package.json ]; then
        print_info "安装 Node.js 依赖..."
        
        if command_exists npm; then
            npm install
            print_success "Node.js 依赖安装完成"
            
            # 构建前端资源
            if npm run | grep -q "build"; then
                print_info "构建前端资源..."
                npm run build
                print_success "前端资源构建完成"
            fi
        else
            print_warning "npm 未找到，跳过 Node.js 依赖安装"
        fi
    fi
}

# 函数：清理缓存
clear_cache() {
    print_info "清理应用缓存..."
    
    if command_exists php; then
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        print_success "缓存清理完成"
    fi
}

# 函数：显示安装完成信息
show_completion_info() {
    echo ""
    echo "================================================"
    print_success "$PROJECT_NAME 安装完成！"
    echo "================================================"
    echo ""
    print_info "下一步操作："
    echo "1. 访问您的应用 URL 查看效果"
    echo "2. 如果需要创建管理员账户，运行："
    echo "   php artisan make:filament-user"
    echo "3. 查看配置文件：config/filament.php"
    echo ""
    print_info "常用命令："
    echo "• 启动开发服务器：php artisan serve"
    echo "• 清理缓存：php artisan cache:clear"
    echo "• 查看日志：tail -f storage/logs/laravel.log"
    echo ""
    print_success "享受使用 $PROJECT_NAME！"
}

# 主函数
main() {
    echo "================================================"
    print_info "$PROJECT_NAME 安装程序"
    echo "================================================"
    echo ""
    
    # 环境检查
    print_info "正在检查环境..."
    check_php_version
    check_composer
    check_node
    check_database
    echo ""
    
    # 安装依赖
    print_info "正在安装依赖..."
    install_php_dependencies
    install_node_dependencies
    echo ""
    
    # 配置应用
    print_info "正在配置应用..."
    generate_app_key
    echo ""
    
    # 数据库迁移
    print_info "正在处理数据库..."
    run_migrations
    echo ""
    
    # Filament配置
    print_info "正在配置 Filament..."
    publish_filament_assets
    echo ""
    
    # 权限设置
    print_info "正在设置权限..."
    set_permissions
    create_symbolic_links
    echo ""
    
    # 清理缓存
    print_info "正在优化应用..."
    clear_cache
    echo ""
    
    # 显示完成信息
    show_completion_info
}

# 脚本入口点
if [[ "${BASH_SOURCE[0]}" == "${0}" ]]; then
    main "$@"
fi