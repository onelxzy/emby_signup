<?php
header("Content-Type: text/html; charset=utf-8");

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $passwd = $_POST['passwd'];
    $confirm_passwd = $_POST['confirm_passwd'];

    // 输入验证
    if (!preg_match("/^[a-zA-Z0-9]{4,}$/", $username)) {
        $message = '用户名只允许包含数字和字母且至少需要4位！';
    } else if ($passwd !== $confirm_passwd) {
        $message = '两次输入的密码不一致！';
    } else if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{8,}$/", $passwd)) {
        $message = '密码至少需要8位且必须包含数字和字母！';
    } else {
        // 请求新建账号接口（提前预设好账号模板并抓包获取其userid）
        $url1 = "http://【server】:【port】/emby/Users/New?X-Emby-Token=【token】";    // 中括号的部分替换为你自己的接口信息，建议使用内网服务地址和token并谨防php源码泄露，注意这里token是指有账号创建权限的Emby服务器管理员的token
        $data1 = array('Name' => $username, 'CopyFromUserId' => '【preset_userid】', 'UserCopyOptions' => 'UserPolicy,UserConfiguration');    // 中括号内容替换为你模板账号的userid，注意这里是模板账号，不是管理员
        $options1 = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data1)
            )
        );
        $context1  = stream_context_create($options1);
        $result1 = file_get_contents($url1, false, $context1);
        if ($result1 === FALSE) { /* Handle error */ }

        // 获取新建账号的Id字段值
        $response1 = json_decode($result1, true);
        $userid = $response1['Id'];
        if ($userid === NULL) {
        	$message = "用户名已存在！";
    	} else {
	        // 为新建的账号更换密码接口
            $url2 = "http://【server】:【ip】/emby/Users/{$userid}/Password?X-Emby-Token=【token】";         //同上替换中括号中内容，token为管理员账户token
            $data2 = array('NewPw' => $passwd);
	        $options2 = array(
	            'http' => array(
	                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
	                'method'  => 'POST',
	                'content' => http_build_query($data2)
	            )
	        );
	        $context2  = stream_context_create($options2);
	        $result2 = file_get_contents($url2, false, $context2);
	        if ($result2 === FALSE) { /* Handle error */ }

	        $message = '注册完成！';
    	}
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emby自助注册</title>
    <link rel="icon" type="image/png" href="https://emby.media/favicon-32x32.png" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #333;
        }

        .main-container {
            width: 100%;
            max-width: 420px;
            margin-bottom: 20px;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo-section img {
            width: 64px;
            height: 64px;
            margin-bottom: 16px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .logo-section h1 {
            font-size: 28px;
            font-weight: 700;
            color: white;
            margin-bottom: 8px;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo-section p {
            font-size: 16px;
            color: rgba(255, 255, 255, 0.8);
            font-weight: 400;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            padding: 40px 32px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .form-group {
            margin-bottom: 24px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 14px;
        }

        .form-group input {
            width: 100%;
            padding: 16px;
            border: 2px solid #e5e7eb;
            border-radius: 12px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f9fafb;
            font-family: inherit;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            background: white;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .form-group input[type="submit"] {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            font-weight: 600;
            border: none;
            margin-top: 8px;
            font-size: 16px;
            position: relative;
            overflow: hidden;
        }

        .form-group input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .form-group input[type="submit"]:active {
            transform: translateY(0);
        }

        .login-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 24px;
            border-top: 1px solid #e5e7eb;
        }

        .login-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .login-link a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        #message {
            display: none; 
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .message-content {
            background: white;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
            text-align: center;
            max-width: 400px;
            width: 100%;
            animation: messageSlideIn 0.3s ease-out;
        }

        @keyframes messageSlideIn {
            from {
                opacity: 0;
                transform: scale(0.9) translateY(-20px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .message-content p {
            margin-bottom: 24px;
            font-size: 16px;
            color: #374151;
            line-height: 1.5;
        }

        .message-content button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }

        .message-content button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .footer {
            margin-top: auto;
            padding: 20px;
            text-align: center;
        }

        .footer-content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            color: rgba(255, 255, 255, 0.7);
            font-size: 14px;
        }

        .footer-content a {
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .footer-content a:hover {
            color: white;
            transform: translateY(-1px);
        }

        .github-icon {
            width: 18px;
            height: 18px;
            fill: currentColor;
        }

        @media (max-width: 480px) {
            body {
                padding: 16px;
            }

            .main-container {
                max-width: 100%;
            }

            .form-container {
                padding: 32px 24px;
                border-radius: 16px;
            }

            .logo-section h1 {
                font-size: 24px;
            }

            .logo-section p {
                font-size: 14px;
            }

            .form-group input {
                padding: 14px;
                font-size: 16px; 
            }

            .form-group input[type="submit"] {
                padding: 10px 15px;
                font-size: 16px;
            }

            .message-content {
                padding: 24px;
                margin: 16px;
            }
        }

        @media (max-width: 360px) {
            .form-container {
                padding: 24px 20px;
            }

            .logo-section h1 {
                font-size: 22px;
            }
        }

        @media (prefers-color-scheme: dark) {
            .form-container {
                background: rgba(255, 255, 255, 0.95); 
            }
        }

        @media (prefers-contrast: high) {
            .form-group input {
                border: 2px solid #000;
            }

            .form-group input:focus {
                border-color: #000;
                box-shadow: 0 0 0 3px rgba(0, 0, 0, 0.1);
            }
        }

        @media (prefers-reduced-motion: reduce) {
            * {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
    <script>
        function showMessage() {
            var messageBox = document.getElementById('message');
            var messageContent = messageBox.querySelector('.message-content p');
            var messageButton = messageBox.querySelector('.message-content button');

            if (messageContent.textContent.trim() !== '') {
                messageBox.style.display = 'flex'; 

                setTimeout(function() {
                    messageBox.style.display = 'none';
                }, 3000);
            }
        }

        function hideMessage() {
            document.getElementById('message').style.display = 'none';
        }

        
        document.addEventListener('DOMContentLoaded', function() {
            var messageOverlay = document.getElementById('message');
            if (messageOverlay) {
                messageOverlay.addEventListener('click', function(e) {
                    if (e.target === messageOverlay) { 
                        hideMessage();
                    }
                });
            }

            
        });
    </script>
</head>
<body>
    <div class="main-container">
        <div class="logo-section">
            <img src="https://emby.media/favicon-96x96.png" alt="Emby Logo">
            <h1>Emby 注册</h1>
            <p>创建您的媒体账户</p>
        </div>

        <div class="form-container">
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required placeholder="请输入用户名" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="passwd">密码</label>
                    <input type="password" id="passwd" name="passwd" required placeholder="请输入密码">
                </div>
                <div class="form-group">
                    <label for="confirm_passwd">确认密码</label>
                    <input type="password" id="confirm_passwd" name="confirm_passwd" required placeholder="请再次输入密码">
                </div>
                <div class="form-group">
                    <input type="submit" value="创建账户">
                </div>
            </form>

            <div class="login-link">
                <a href="https://url.com">已有账户？点击登录</a> </div>
        </div>
    </div>

    <div class="footer">
        <div class="footer-content">
            <span>开源项目</span>
            <a href="https://github.com/onelxzy/emby_signup" target="_blank" rel="noopener noreferrer">
                <svg class="github-icon" viewBox="0 0 24 24">
                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                </svg>
                GitHub
            </a>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <div id="message">
            <div class="message-content">
                <p><?php echo htmlspecialchars($message); ?></p>
                <button onclick="hideMessage()">确定</button>
            </div>
        </div>
        <script>showMessage();</script>
    <?php endif; ?>

</body>
</html>
