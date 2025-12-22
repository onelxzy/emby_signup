FROM php:8.2-apache

# 1. 设置环境变量
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public

# 2. 安装依赖并配置 PHP 扩展
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-source extract \
    && docker-php-ext-install sqlite3 \
    && docker-php-source delete \
    && rm -rf /var/lib/apt/lists/*

# 3. 使用生产环境配置
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# 4. 配置 Apache 文档目录
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

# 5. 复制文件与设置工作目录
WORKDIR /var/www/html
COPY . /var/www/html

# 6. 设置权限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/config

# 7. 备份配置 (用于初始化)
RUN mkdir -p /usr/src/app_backup && cp -r /var/www/html/config /usr/src/app_backup/config

# 8. 入口脚本
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

VOLUME /var/www/html/config
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
