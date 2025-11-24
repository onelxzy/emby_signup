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
        $message = '邀请码无效！ '; 
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
                $message = "用户创建失败，请联系管理员！";
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
                        $message = '邀请码核销异常。';
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
    <title>Emby 媒体库注册</title>
    <link rel="icon" type="image/png" href="https://emby.media/favicon-32x32.png" sizes="32x32">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --primary: #52B54B; /* Emby Greenish */
            --primary-hover: #43943d;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --input-bg: rgba(15, 23, 42, 0.6);
            --border-color: rgba(255, 255, 255, 0.1);
            --blur-amt: 16px;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 20px;
            position: relative;
            overflow-x: hidden;
        }

        .bg-blob {
            position: fixed;
            border-radius: 50%;
            filter: blur(80px);
            z-index: -1;
            opacity: 0.4;
            animation: float 10s infinite ease-in-out;
        }
        .blob-1 { top: -10%; left: -10%; width: 500px; height: 500px; background: #4f46e5; animation-delay: 0s; }
        .blob-2 { bottom: -10%; right: -10%; width: 400px; height: 400px; background: #52B54B; animation-delay: -5s; }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, 50px); }
        }

        .main-container {
            width: 100%;
            max-width: 400px;
            position: relative;
            z-index: 10;
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
            animation: fadeInDown 0.8s ease-out;
        }

        .logo-section img {
            width: 72px;
            height: 72px;
            margin-bottom: 16px;
            border-radius: 18px;
            box-shadow: 0 0 20px rgba(82, 181, 75, 0.3);
        }

        .logo-section h1 {
            font-size: 24px;
            font-weight: 700;
            letter-spacing: -0.5px;
            margin-bottom: 8px;
            background: linear-gradient(to right, #fff, #cbd5e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .logo-section p {
            font-size: 14px;
            color: var(--text-sub);
        }

        .form-container {
            background: var(--card-bg);
            backdrop-filter: blur(var(--blur-amt));
            -webkit-backdrop-filter: blur(var(--blur-amt));
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 40px 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeInUp 0.8s ease-out;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-sub);
        }

        .form-group input {
            width: 100%;
            padding: 14px 16px;
            background: var(--input-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            color: white;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.3s ease;
        }

        .form-group input::placeholder {
            color: rgba(148, 163, 184, 0.4);
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(82, 181, 75, 0.15);
            background: rgba(15, 23, 42, 0.8);
        }
        
        .form-group input[readonly] {
            opacity: 0.6;
            cursor: not-allowed;
            border-style: dashed;
        }

        .form-group input[type="submit"] {
            background: linear-gradient(135deg, var(--primary) 0%, #3d8c38 100%);
            color: white;
            cursor: pointer;
            font-weight: 600;
            border: none;
            margin-top: 12px;
            font-size: 16px;
            letter-spacing: 0.5px;
            box-shadow: 0 10px 15px -3px rgba(82, 181, 75, 0.3);
        }

        .form-group input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 14px 20px -3px rgba(82, 181, 75, 0.4);
        }

        .form-group input[type="submit"]:active {
            transform: translateY(0);
        }

        .status-link {
            text-align: center;
            margin-top: 24px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }

        .status-link a {
            color: var(--text-sub);
            text-decoration: none;
            font-size: 13px;
            transition: color 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        
        .status-link a::before {
            content: '';
            display: block;
            width: 8px;
            height: 8px;
            background-color: #10b981;
            border-radius: 50%;
            box-shadow: 0 0 8px #10b981;
        }

        .status-link a:hover {
            color: white;
        }

        /* Footer */
        .footer {
            margin-top: 40px;
            text-align: center;
            animation: fadeIn 1s ease-out 0.5s both;
        }

        .footer-content {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: rgba(255,255,255,0.05);
            border-radius: 50px;
            border: 1px solid rgba(255,255,255,0.05);
        }

        .footer-content a {
            color: var(--text-sub);
            text-decoration: none;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s;
        }

        .footer-content a:hover {
            color: white;
        }

        .github-icon {
            width: 16px;
            height: 16px;
            fill: currentColor;
        }

        /* Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .modal-content {
            background: #1e293b;
            padding: 32px;
            border-radius: 20px;
            border: 1px solid rgba(255,255,255,0.1);
            max-width: 400px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
            animation: zoomIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-title {
            font-size: 18px;
            font-weight: 700;
            color: white;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .modal-text {
            font-size: 15px;
            color: var(--text-sub);
            line-height: 1.6;
            margin-bottom: 24px;
            text-align: left;
            background: rgba(0,0,0,0.2);
            padding: 16px;
            border-radius: 12px;
        }
        
        .modal-text .highlight {
            color: #ef4444;
            font-weight: 600;
            display: block;
            margin-bottom: 8px;
        }

        .modal-btn {
            background: white;
            color: #0f172a;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: all 0.2s;
        }

        .modal-btn:hover {
            background: #f1f5f9;
            transform: scale(1.02);
        }

        @keyframes fadeInDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        @keyframes zoomIn { from { opacity: 0; transform: scale(0.9); } to { opacity: 1; transform: scale(1); } }
    </style>
    <script>
        function checkFirstVisit() {
            if (!localStorage.getItem('emby_notice_shown')) {
                showUserNotice();
                localStorage.setItem('emby_notice_shown', 'true');
            }
        }

        function showUserNotice() {
            document.getElementById('userNotice').style.display = 'flex';
        }

        function hideUserNotice() {
            const notice = document.getElementById('userNotice');
            notice.style.opacity = '0';
            setTimeout(() => notice.style.display = 'none', 300);
        }

        function showMessage() {
            var messageBox = document.getElementById('message');
            var messageContent = document.getElementById('msg-text');
            
            if (messageContent.innerText.trim() !== '') {
                messageBox.style.display = 'flex'; 
                
                setTimeout(function() {
                    hideMessage();
                    if (messageContent.innerText.trim() === '注册完成！') {
                        window.location.href = '<?php echo $config["site"]["login_url"]; ?>';
                    }
                }, 3000);
            }
        }

        function hideMessage() {
            var messageBox = document.getElementById('message');
            var messageContent = document.getElementById('msg-text');
            
            messageBox.style.opacity = '0';
            setTimeout(() => {
                messageBox.style.display = 'none';
                messageBox.style.opacity = '1';
            }, 300);

            if (messageContent.innerText.trim() === '注册完成！') {
                window.location.href = '<?php echo $config["site"]["login_url"]; ?>';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            checkFirstVisit();

            document.querySelectorAll('.modal-overlay').forEach(overlay => {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        if(overlay.id === 'message') hideMessage();
                    }
                });
            });
        });
    </script>
</head>
<body>
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>
    <!-- 这是一段自定义公告
    <div id="userNotice" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-title">⚠️ 用户须知</div>
            <div class="modal-text">
                此处可以填写公告
            </div>
            <button class="modal-btn" onclick="hideUserNotice()">我已了解，继续</button>
        </div>
    </div>
    -->
    <div class="main-container">
        <div class="logo-section">
            <img src="https://emby.media/favicon-96x96.png" alt="Emby Logo">
            <h1>加入媒体库</h1>
            <p>创建您的 Emby 账户</p>
        </div>

        <div class="form-container">
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">用户名</label>
                    <input type="text" id="username" name="username" required placeholder="请输入用户名" autocomplete="off" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>">
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
                    <input type="submit" value="立即注册">
                </div>
            </form>

            <div class="status-link">
                <a href="./admin.php" target="_blank">邀请码管理</a>
            </div>
        </div>

        <div class="footer">
            <div class="footer-content">
                <a href="https://github.com/onelxzy/emby_signup" target="_blank" rel="noopener noreferrer">
                    <svg class="github-icon" viewBox="0 0 24 24">
                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    Open Source
                </a>
            </div>
        </div>
    </div>

    <?php if ($message !== ''): ?>
        <?php 
            $is_success = ($message === '注册完成！');
            $modal_icon = $is_success ? '✅' : '⚠️';
            $modal_style_class = $is_success ? 'modal-success' : 'modal-warning';
        ?>
        <div id="message" class="modal-overlay">
            <div class="modal-content <?php echo $modal_style_class; ?>" style="max-width: 320px;">
                <div style="font-size: 32px; margin-bottom: 10px;"><?php echo $modal_icon; ?></div>
                <p id="msg-text" style="color: white; margin-bottom: 20px; font-size: 16px;"><?php echo htmlspecialchars($message); ?></p>
                <button class="modal-btn" onclick="hideMessage()">确定</button>
            </div>
        </div>
        <script>showMessage();</script>
    <?php endif; ?>

</body>
</html>
