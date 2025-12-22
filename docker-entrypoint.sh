#!/bin/bash
set -e
CONFIG_DIR="/var/www/html/config"
BACKUP_DIR="/usr/src/app_backup/config"

if [ ! -f "$CONFIG_DIR/config.php" ]; then
    cp "$BACKUP_DIR/config.php" "$CONFIG_DIR/"
fi
if [ ! -f "$CONFIG_DIR/database.php" ]; then
    cp "$BACKUP_DIR/database.php" "$CONFIG_DIR/"
fi
if [ ! -f "$CONFIG_DIR/email_template.txt" ]; then
    if [ -f "$BACKUP_DIR/email_template.txt" ]; then
        cp "$BACKUP_DIR/email_template.txt" "$CONFIG_DIR/"
    else
        touch "$CONFIG_DIR/email_template.txt"
    fi
fi

# 确保权限正确
chown -R www-data:www-data "$CONFIG_DIR"

exec "$@"
