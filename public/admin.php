<?php
// SQLite 版本管理后台
// ----------------------------------------------------
// 【重要】在任何操作前开始 Session，用于用户登录状态判断
// ----------------------------------------------------
session_start();

$config_path = __DIR__ . '/../config/config.php';
$db_core_file = __DIR__ . '/../config/database.php'; 

// 引入数据库核心类和初始化实例
if (!file_exists($db_core_file)) {
    die("错误: 数据库核心文件不存在，请检查 /config/database.php 路径。");
}
require_once $db_core_file;

// 默认设置
$error_message = '';
$base_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
$register_page_path = dirname($_SERVER['PHP_SELF']) . '/register.php';

// 检查配置文件
if (!file_exists($config_path)) {
    die("错误: 配置文件不存在，请检查 /config/config.php 路径。");
}
$config = require $config_path;


// ----------------------------------------------------
// 核心工具函数
// ----------------------------------------------------

// AJAX 渲染表格 HTML（局部刷新依赖）
function renderTableHtml($codes, $base_url, $register_page_path) {
    ob_start();
    ?>
    <?php if (!empty($codes)): ?>
        <?php $i = 1; foreach (array_values($codes) as $code): ?>
        <tr>
            <td><?= $i++; ?></td>
            <td><?= htmlspecialchars($code); ?></td>
            <td>
                <?php $invite_link = $base_url . $register_page_path . '?invite_code=' . urlencode($code); ?>
                <!-- 增加点击事件以复制链接 -->
                <span class="invite-link" 
                      title="<?= htmlspecialchars($invite_link); ?>" 
                      onclick="copyToClipboard('<?= htmlspecialchars($invite_link); ?>')">
                    <?= htmlspecialchars($invite_link); ?>
                </span>
            </td>
            <td>
                <button type="button" class="delete-btn" onclick="deleteCode('<?= $code ?>')">️ 删除</button>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="4" style="text-align:center;">暂无邀请码</td>
        </tr>
    <?php endif;
    return ob_get_clean();
}

// ----------------------------------------------------
// 【1. 登录/登出逻辑】
// ----------------------------------------------------
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: admin.php");
    exit;
}

if (isset($_POST['login_user'], $_POST['login_pass'])) {
    if (
        $_POST['login_user'] === $config['admin']['username']
        && $_POST['login_pass'] === $config['admin']['password']
    ) {
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin.php");
        exit;
    } else {
        $error_message = "用户名或密码错误";
    }
}

$is_authenticated = !empty($_SESSION['admin_logged_in']);


// ----------------------------------------------------
// 【2. AJAX 请求处理】
// ----------------------------------------------------
if ($is_authenticated && isset($_POST['ajax'])) {
    header("Content-Type: application/json; charset=utf-8");
    
    global $invite_db;

    $response = ['status' => 'error', 'message' => '未知操作', 'html' => ''];
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate') {
        // 生成随机码
        $new_code = InviteDB::generateRandomCode();
        // 插入数据库
        $success = $invite_db->insertCode($new_code);
        
        if ($success) {
            $response['status'] = 'success';
            $response['message'] = '✅ [成功] 邀请码新增成功！';
        } else {
            // 失败通常是数据库文件权限问题或极低的概率生成重复码
            $response['message'] = '❌ [错误] 新增邀请码失败，请检查数据库文件写入权限！';
        }

    } elseif ($action === 'delete' && isset($_POST['code'])) {
        $code_to_delete = $_POST['code'];
        // 删除数据库中的邀请码
        $success = $invite_db->deleteCode($code_to_delete);
        
        if ($success) {
            $response['status'] = 'success';
            $response['message'] = '✅ [删除] 邀请码删除成功！';
        } else {
            $response['status'] = 'warning';
            $response['message'] = '⚠️ [警告] 删除失败，邀请码不存在或已被使用。';
        }
    }
    
    // 重新查询最新的未使用的邀请码列表
    $codes = $invite_db->getAllUnusedCodes();
    $response['html'] = renderTableHtml($codes, $base_url, $register_page_path);
    
    echo json_encode($response);
    exit; 
}

// ----------------------------------------------------
// 【3. 正常页面加载】
// ----------------------------------------------------
header("Content-Type: text/html; charset=utf-8");
global $invite_db;
$all_invite_codes = $invite_db->getAllUnusedCodes();
$initial_table_html = renderTableHtml($all_invite_codes, $base_url, $register_page_path);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emby 邀请码管理后台</title>
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
            max-width: <?php echo $is_authenticated ? '900px' : '420px'; ?>; 
            transition: max-width 0.4s ease;
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
            padding: 16px;
        }

        .form-group input[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .form-group input[type="submit"]:active {
            transform: translateY(0);
        }
        
        .login-form-container {
            max-width: 420px;
            margin: 0 auto;
        }
        
        .admin-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .action-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            cursor: pointer;
            font-weight: 600;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            transition: all 0.3s ease;
            display: flex; 
            align-items: center;
            text-align: center;
            text-decoration: none;
            font-size: 16px;
        }

        .action-button span.icon {
            color: white; 
            font-weight: 700;
            margin-right: 6px;
            font-size: 1.2em;
            line-height: 1;
        }
        
        .action-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }

        .logout-link {
            color: #ef4444;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.2s;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #ef4444;
        }
        .logout-link:hover {
            color: white;
            background-color: #ef4444;
        }
        
        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        th, td {
            padding: 15px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 14px;
        }

        th {
            background-color: #f9fafb;
            color: #374151;
            font-weight: 600;
            text-transform: uppercase;
            white-space: nowrap;
        }
        
        tr:last-child td {
            border-bottom: none;
        }

        .delete-btn {
        background: #ef4444;
        color: white;
        border: none;
        padding: 6px 10px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 500;
        transition: background-color 0.2s;
        font-size: 13px;
        white-space: nowrap; 
        display: inline-block; 
    }

        .delete-btn:hover {
            background: #dc2626;
        }

        .invite-link {
            font-family: monospace;
            font-size: 13px;
            background: #f1f5f9;
            padding: 4px 8px;
            border-radius: 4px;
            display: inline-block;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            cursor: pointer; 
            transition: background-color 0.1s;
        }
        
        .invite-link:hover {
            background: #e0e7f1;
        }

        #status-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            display: none;
            border: 1px solid;
            font-size: 14px;
        }
        #status-message.success {
            background-color: #d1fae5;
            color: #065f46;
            border-color: #34d399;
        }
        #status-message.error {
            background-color: #fee2e2;
            color: #991b1b;
            border-color: #f87171;
        }
        #status-message.warning {
            background-color: #fffde7;
            color: #856118;
            border-color: #fde047;
        }
        
        .copy-toast {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #4CAF50; 
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
            font-weight: 600;
            z-index: 1000;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            opacity: 0;
            transition: opacity 0.5s ease;
        }

        .footer {
            margin-top: 30px;
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
        @media (max-width: 950px) {
            .main-container {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>
    
    <div class="main-container">
        <div class="logo-section">
            <img src="https://emby.media/favicon-96x96.png" alt="Emby Logo">
            <h1>Emby 邀请码后台</h1>
            <p><?php echo $is_authenticated ? '管理邀请码列表' : '管理员登录'; ?></p>
        </div>

        <div class="form-container">
            <?php if (!$is_authenticated): ?>
            <!-- 登录表单 -->
            <div class="login-form-container">
                <?php if ($error_message): ?>
                    <p class="message-status error" id="login-error" style="display: block; padding: 10px; margin-bottom: 20px; border-radius: 8px; font-weight: 500; text-align: center; background-color: #fee2e2; color: #991b1b;"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>
                <form method="post" action="admin.php">
                    <div class="form-group">
                        <label for="login_user">用户名</label>
                        <input type="text" id="login_user" name="login_user" required>
                    </div>
                    <div class="form-group">
                        <label for="login_pass">密码</label>
                        <input type="password" id="login_pass" name="login_pass" required>
                    </div>
                    <div class="form-group">
                        <input type="submit" value="登录">
                    </div>
                </form>
            </div>
            <?php else: ?>
            <div class="admin-panel">
                <div id="status-message"></div>
                
                <div class="admin-header">
                    <!-- 新增邀请码按钮 -->
                    <button type="button" class="action-button" onclick="generateCode()">
                        <span class="icon">+</span> 新增邀请码
                    </button>
                    
                    <!-- 登出链接 -->
                    <a href="?action=logout" class="logout-link">退出登录</a>
                </div>

                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>序号</th>
                                <th>邀请码</th>
                                <th>邀请链接</th>
                                <th>操作</th>
                            </tr>
                        </thead>
                        <!-- 邀请码列表内容 -->
                        <tbody id="invite-code-list">
                            <?php echo $initial_table_html; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
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

    <?php if ($is_authenticated): ?>
    <script>
        /**
         * 显示状态消息
         */
        function displayStatus(message, type = 'success') {
            const statusDiv = document.getElementById('status-message');
            statusDiv.innerHTML = message;
            statusDiv.className = type;
            statusDiv.style.display = 'block';
            
            setTimeout(() => {
                statusDiv.style.display = 'none';
            }, 4000);
        }

        /**
         * 复制文本到剪贴板并显示提示
         * @param {string} text 要复制的文本内容（即邀请链接）
         */
        function copyToClipboard(text) {
            const tempInput = document.createElement('textarea');
            tempInput.value = text;
            tempInput.style.position = 'fixed'; 
            tempInput.style.left = '-9999px';
            document.body.appendChild(tempInput);
            
            tempInput.select();
            try {
                const successful = document.execCommand('copy');
                if (successful) {
                    showCopyToast(' 链接已复制成功！');
                } else {
                    showCopyToast('❌ 复制失败，请手动复制。', 'error');
                }
            } catch (err) {
                console.error('Copy command failed: ', err);
                showCopyToast('❌ 复制失败，请手动复制。', 'error');
            }
            
            document.body.removeChild(tempInput);
        }

        /**
         * 显示一个短暂的浮动提示框
         * @param {string} message 提示信息
         * @param {string} type 提示类型 
         */
        function showCopyToast(message, type = 'success') {
            let toast = document.getElementById('copy-toast');
            if (!toast) {
                toast = document.createElement('div');
                toast.id = 'copy-toast';
                toast.className = 'copy-toast';
                document.body.appendChild(toast);
            }

            toast.textContent = message;
            toast.style.opacity = '1';

            setTimeout(() => {
                toast.style.opacity = '0';
            }, 2500);
        }


        /**
         * 发送 AJAX 请求
         */
        async function sendAjaxRequest(body) {
            try {
                const actionButton = document.querySelector('.action-button');
                if(actionButton) actionButton.disabled = true;

                const response = await fetch("admin.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: "ajax=1&" + body 
                });

                if (!response.ok) {
                    throw new Error(`HTTP 错误！状态: ${response.status}`);
                }

                const data = await response.json();
                
                document.getElementById("invite-code-list").innerHTML = data.html;
                
                displayStatus(data.message, data.status);

            } catch (error) {
                console.error('AJAX 请求失败:', error);
                displayStatus('❌ [致命错误] AJAX 请求失败，请检查服务器日志或 admin.php 顶部是否存在意外输出！', 'error');
            } finally {
                const actionButton = document.querySelector('.action-button');
                if(actionButton) actionButton.disabled = false;
            }
        }

        /**
         * 处理新增邀请码操作
         */
        function generateCode() {
            sendAjaxRequest('action=generate');
        }

        /**
         * 处理删除邀请码操作
         */
        function deleteCode(code) {
            const confirmAction = window.confirm(`确定要删除邀请码 ${code} 吗？`);
            if (!confirmAction) {
                 return;
            }
            
            const body = `action=delete&code=${encodeURIComponent(code)}`;
            sendAjaxRequest(body);
        }
    </script>
    <?php endif; ?>

</body>
</html>