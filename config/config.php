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
];
