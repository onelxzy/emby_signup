<?php

// 配置文件，请勿放置在 Web 根目录下

if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header("HTTP/1.0 403 Forbidden");
    exit("Access Denied");
}

return [
    // ------------------------------------------
    // Emby API 配置
    // ------------------------------------------
    'emby' => [
        // 您的 Emby 服务器地址，例如 'http://127.0.0.1:8096'
        'base_url' => 'http://YOUR_EMBY_IP:PORT', 
        // Emby 的 API Token
        'token' => 'YOUR_EMBY_API_TOKEN', 
        // 用于复制权限的模板用户 ID
        'template_user_id' => 'EMBY_TEMPLATE_USER_ID', 
    ],
    
    // ------------------------------------------
    // 管理员账户配置
    // ------------------------------------------
    'admin' => [
        // 邀请码管理后台用户名
        'username' => 'admin', 
        // 邀请码管理后台密码
        'password' => 'password', 
    ],

    // ------------------------------------------
    // 站点配置
    // ------------------------------------------
    'site' => [
        // 注册成功后跳转的 Emby 登录页
        'login_url' => 'http://YOUR_EMBY_IP:PORT', 
    ],
    
    // ------------------------------------------
    // 邮件发送配置 (SMTP)
    // ------------------------------------------
    'smtp' => [
        'host' => 'smtp.qq.com',       // SMTP 服务器地址 (如 smtp.gmail.com, smtp.126.com)
        'port' => 465,                 // SMTP 端口 (通常 SSL 使用 465)
        'secure' => 'ssl',             // 加密方式: 'ssl'
        'username' => 'your_email@qq.com', // 发件人邮箱账号
        'password' => 'your_auth_code',    // 邮箱密码或应用专用授权码
        'from_name' => 'Emby Admin',       // 发件人显示名称
    ],

    // ------------------------------------------
    // 邀请邮件配置
    // ------------------------------------------
    'email_template' => [
        'subject' => 'Emby 媒体服务器邀请函',       //邮件主题
        'template_path' => __DIR__ . '/email_template.txt', 
    ],
];
