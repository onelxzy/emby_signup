<?php

// 配置文件，请勿放置在 Web 根目录下

if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header("HTTP/1.0 403 Forbidden");
    exit("Access Denied");
}

function env($key, $default = null) {
    $value = getenv($key);
    return $value === false ? $default : $value;
}

return [
    // ------------------------------------------
    // Emby API 配置
    // ------------------------------------------
    'emby' => [
        // 您的 Emby 服务器地址，例如 'http://127.0.0.1:8096'
        'base_url' => env('EMBY_BASE_URL', 'http://YOUR_EMBY_IP:PORT'), 
        // Emby 的 API Token
        'token' => env('EMBY_API_TOKEN', 'YOUR_EMBY_API_TOKEN'),
        // 用于复制权限的模板用户 ID
        'template_user_id' => env('EMBY_TEMPLATE_USER_ID', 'YOUR_EMBY_TEMPLATE_USER_ID'),
    ],
    
    // ------------------------------------------
    // 管理员账户配置
    // ------------------------------------------
    'admin' => [
        // 邀请码管理后台用户名
        'username' => env('ADMIN_USERNAME', 'admin'),
        // 邀请码管理后台密码
        'password' => env('ADMIN_PASSWORD', 'password'),
    ],

    // ------------------------------------------
    // 站点配置
    // ------------------------------------------
    'site' => [
        // 注册成功后跳转的 Emby 登录页
        'login_url' => env('SITE_LOGIN_URL', 'http://YOUR_EMBY_IP:PORT'),
    ],
    
    // ------------------------------------------
    // 邮件发送配置 (SMTP)
    // ------------------------------------------
    'smtp' => [
        'host' => env('SMTP_HOST', ''),                         // SMTP 服务器地址 (如 smtp.gmail.com, smtp.126.com)
        'port' => env('SMTP_PORT', 465),                        // SMTP 端口 (通常 SSL 使用 465)
        'secure' => env('SMTP_SECURE', 'ssl'),                  // 加密方式: 'ssl'
        'username' => env('SMTP_USERNAME', ''),                 // 发件人邮箱账号
        'password' => env('SMTP_PASSWORD', ''),                 // 邮箱密码或应用专用授权码
        'from_name' => env('SMTP_FROM_NAME', 'Emby Admin'),     // 发件人显示名称
    ],

    // ------------------------------------------
    // 邀请邮件配置
    // ------------------------------------------
    'email_template' => [
        'subject' => env('EMAIL_SUBJECT', 'Emby 媒体服务器邀请函'), //邮件主题
        'template_path' => __DIR__ . '/email_template.txt', 
    ],
];
