# 使用多阶段构建的 Dockerfile
FROM node:18-alpine AS frontend-builder

WORKDIR /app

# 复制前端依赖文件
COPY package*.json ./
RUN npm ci --only=production

# 复制源代码并构建
COPY . .
RUN npm run build

# PHP 阶段
FROM php:8.2-fpm-alpine AS php-base

# 安装系统依赖
RUN apk add --no-cache \
    bash \
    curl \
    freetype-dev \
    g++ \
    gcc \
    git \
    icu-dev \
    icu-libs \
    icu \
    icu-dev \
    libc-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    make \
    mysql-client \
    oniguruma-dev \
    openssh-client \
    rsync \
    zlib-dev

# 安装 PHP 扩展
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        bcmath \
        gd \
        intl \
        mysqli \
        opcache \
        pdo_mysql \
        zip

# 安装 Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 安装 PHP 依赖
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# 复制构建的前端资源
COPY --from=frontend-builder /app/public ./public

# 复制应用代码
COPY . .

# 设置权限
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html
RUN chmod -R 775 /var/www/html/storage
RUN chmod -R 775 /var/www/html/bootstrap/cache

# 健康检查
HEALTHCHECK --interval=30s --timeout=3s --start-period=5s --retries=3 \
    CMD php-fpm -t && curl -f http://localhost:9000/health || exit 1

EXPOSE 9000

CMD ["php-fpm"]