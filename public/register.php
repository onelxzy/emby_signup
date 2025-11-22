<?php
header("Content-Type: text/html; charset=utf-8");

$config_path = __DIR__ . '/../config/config.php';
$db_file_path = __DIR__ . '/../config/database.php';

if (!file_exists($config_path) || !file_exists($db_file_path)) {
    die("配置文件或数据库文件丢失，请检查路径配置！");
}

$config = require $config_path;
require_once $db_file_path;

$message = '';

if (isset($_GET['invite_code'])) {
    $invite_code_from_url = htmlspecialchars($_GET['invite_code']);
} else {
    $invite_code_from_url = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $passwd = $_POST['passwd'];
    $confirm_passwd = $_POST['confirm_passwd'];
    $input_invite_code = trim($_POST['invite_code']); 

    $valid_codes = $invite_db->getAllUnusedCodes();

    if (!preg_match("/^[a-zA-Z0-9]{4,}$/", $username)) {
        $message = '用户名只允许包含数字和字母且至少需要4位！';
    } else if ($passwd !== $confirm_passwd) {
        $message = '两次输入的密码不一致！';
    } else if (!preg_match("/^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d@$!%*#?&]{8,}$/", $passwd)) {
        $message = '密码至少需要8位且必须包含数字和字母！';
    } else if (!in_array($input_invite_code, $valid_codes)) {
        $message = '邀请码无效或已被使用！ '; 
    } else {
        $emby_url = rtrim($config['emby']['base_url'], '/');
        $emby_token = $config['emby']['token'];
        $template_id = $config['emby']['template_user_id'];

        $url1 = "{$emby_url}/emby/Users/New?X-Emby-Token={$emby_token}";
        $data1 = array(
            'Name' => $username, 
            'CopyFromUserId' => $template_id, 
            'UserCopyOptions' => 'UserPolicy,UserConfiguration'
        );
        $options1 = array(
            'http' => array(
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data1)
            )
        );
        $context1  = stream_context_create($options1);
        $result1 = @file_get_contents($url1, false, $context1);

        if ($result1 === FALSE) { 
            $message = "连接 Emby 服务器失败，请联系管理员！";
        } else {
            $response1 = json_decode($result1, true);
            if (!isset($response1['Id']) || $response1['Id'] === NULL) {
                $message = "用户名已存在或创建失败！";
            } else {
                $userid = $response1['Id'];
                $url2 = "{$emby_url}/emby/Users/{$userid}/Password?X-Emby-Token={$emby_token}";
                $data2 = array('NewPw' => $passwd);
                $options2 = array(
                    'http' => array(
                        'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                        'method'  => 'POST',
                        'content' => http_build_query($data2)
                    )
                );
                $context2  = stream_context_create($options2);
                $result2 = @file_get_contents($url2, false, $context2);
                
                if ($result2 === FALSE) { 
                    $message = "密码设置失败，请联系管理员重置！";
                } else {
                    if ($invite_db->useCode($input_invite_code)) {
                        $message = '注册完成！';
                    } else {
                        $message = '注册完成，但邀请码核销异常。';
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emby 自助注册</title>
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
        
        .form-group input[readonly] {
            background-color: #e5e7eb; 
            color: #6b7280; 
            cursor: not-allowed; 
            border-color: #d1d5db;
        }
        .form-group input[readonly]:focus {
            box-shadow: none; 
            border-color: #d1d5db;
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

        /* 用户须知弹窗样式 */
        #userNotice {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .notice-content {
            background: white;
            padding: 40px 36px;
            border-radius: 20px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            text-align: left;
            max-width: 500px;
            width: 100%;
            animation: noticeSlideIn 0.4s ease-out;
            border: 2px solid #667eea;
        }

        @keyframes noticeSlideIn {
            from {
                opacity: 0;
                transform: scale(0.8) translateY(-30px);
            }
            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .notice-content h3 {
            color: #667eea;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .notice-content h3::before {
            content: "⚠️";
            font-size: 24px;
        }

        .notice-content .notice-text {
            font-size: 16px;
            color: #374151;
            line-height: 1.6;
            margin-bottom: 28px;
            white-space: pre-line;
        }

        .notice-content .important-text {
            color: #dc2626;
            font-weight: 600;
        }

        .notice-content button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 14px 28px;
            border-radius: 10px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
            margin-top: 8px;
        }

        .notice-content button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
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

            .notice-content {
                padding: 32px 24px;
                margin: 16px;
                border-radius: 16px;
            }

            .notice-content h3 {
                font-size: 18px;
            }

            .notice-content .notice-text {
                font-size: 15px;
            }
        }

        @media (max-width: 360px) {
            .form-container {
                padding: 24px 20px;
            }

            .logo-section h1 {
                font-size: 22px;
            }

            .notice-content {
                padding: 24px 20px;
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
        function checkFirstVisit() {
            if (!localStorage.getItem('emby_notice_shown')) {
                showUserNotice();
                localStorage.setItem('emby_notice_shown', 'true');
            }
        }

        function showUserNotice() {
            var noticeBox = document.getElementById('userNotice');
            noticeBox.style.display = 'flex';
        }

        function hideUserNotice() {
            var noticeBox = document.getElementById('userNotice');
            noticeBox.style.display = 'none';
        }

        function showMessage() {
            var messageBox = document.getElementById('message');
            var messageContent = messageBox.querySelector('.message-content p');
            var messageButton = messageBox.querySelector('.message-content button');

            if (messageContent.textContent.trim() !== '') {
                messageBox.style.display = 'flex'; 

                setTimeout(function() {
                    hideMessage();
                    if (messageContent.textContent.trim() === '注册完成！') {
                        window.location.href = '<?php echo $config["site"]["login_url"]; ?>';
                    }
                }, 3000);
            }
        }

        function hideMessage() {
            var messageBox = document.getElementById('message');
            var messageContent = messageBox.querySelector('.message-content p');
            messageBox.style.display = 'none';
            if (messageContent.textContent.trim() === '注册完成！') {
                window.location.href = '<?php echo $config["site"]["login_url"]; ?>';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            checkFirstVisit();

            var messageOverlay = document.getElementById('message');
            if (messageOverlay) {
                messageOverlay.addEventListener('click', function(e) {
                    if (e.target === messageOverlay) { 
                        hideMessage();
                    }
                });
            }

            var noticeOverlay = document.getElementById('userNotice');
            if (noticeOverlay) {
                noticeOverlay.addEventListener('click', function(e) {
                    e.preventDefault();
                });
            }
        });
    </script>
</head>
<body>
    <!-- 这是一段自定义公告
    <div id="userNotice">
        <div class="notice-content">
            <h3>用户使用须知</h3>
            <div class="notice-text">
<span class="important-text">注意：此处可以填写公告
            </div>
            <button onclick="hideUserNotice()">我已了解，继续注册</button>
        </div>
    </div>
    -->
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
                    <label for="invite_code">邀请码</label>
                    <input type="text" id="invite_code" name="invite_code" required placeholder="请输入邀请码" 
                           value="<?php echo htmlspecialchars(!empty($invite_code_from_url) ? $invite_code_from_url : (isset($_POST['invite_code']) ? $_POST['invite_code'] : '')); ?>"
                           <?php echo !empty($invite_code_from_url) ? 'readonly' : ''; ?>>
                </div>
                <div class="form-group">
                    <input type="submit" value="创建账户">
                </div>
            </form>

            <div class="login-link">
            <a href="./admin.php" style="color:darkgray;">邀请码管理</a> </div>
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