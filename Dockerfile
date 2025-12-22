FROM php:8.2-apache

# 安装依赖
RUN apt-get update && apt-get install -y libsqlite3-dev && rm -rf /var/lib/apt/lists/*

# 安装 SQLite 扩展
RUN docker-php-ext-install sqlite3

# 使用生产环境配置
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

# 设置 Apache 根目录为 public
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 启用 Rewrite
RUN a2enmod rewrite

# 复制文件
WORKDIR /var/www/html
COPY . /var/www/html

# 设置权限
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 777 /var/www/html/config

# 备份配置用于初始化
RUN mkdir -p /usr/src/app_backup && cp -r /var/www/html/config /usr/src/app_backup/config

# 设置启动脚本
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

VOLUME /var/www/html/config
ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
