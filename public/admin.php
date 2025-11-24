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
function renderTableHtml($codes, $base_url, $register_page_path) {
    ob_start();
    ?>
    <?php if (!empty($codes)): ?>
        <?php $i = 1; foreach (array_values($codes) as $code): ?>
        <tr>
            <td class="col-id"><?= $i++; ?></td>
            
            <td class="col-code">
                <span class="code-badge"><?= htmlspecialchars($code); ?></span>
            </td>
            
            <td class="col-link">
                <?php 
                $clean_path = ltrim($register_page_path, '/'); 
                $invite_link = rtrim($base_url, '/') . '/' . $clean_path . '?invite_code=' . urlencode($code); 
                ?>
                <div class="link-wrapper">
                    <span class="link-text" title="<?= htmlspecialchars($invite_link); ?>"><?= htmlspecialchars($invite_link); ?></span>
                    
                    <button type="button" class="copy-action-btn" onclick="copyInviteLink(this, '<?= htmlspecialchars(addslashes($invite_link)); ?>')">
                        <svg class="copy-icon" viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                    </button>
                </div>
            </td>
            
            <td class="col-action" style="text-align: right;">
                <button type="button" class="action-btn delete-btn" onclick="deleteCode('<?= $code ?>')">
                    <svg viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                    删除
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="4" class="empty-state">
                暂无可用邀请码，请点击右上角生成
            </td>
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

    $response = ['status' => 'error', 'message' => '未知操作', 'html' => '', 'count' => 0];
    $action = $_POST['action'] ?? '';
    
    if ($action === 'generate') {
        $new_code = InviteDB::generateRandomCode();
        if ($invite_db->insertCode($new_code)) {
            $response['status'] = 'success';
            $response['message'] = '✅ 邀请码已生成';
        } else {
            $response['message'] = '❌ 生成失败，请检查数据库权限';
        }

    } elseif ($action === 'delete' && isset($_POST['code'])) {
        $code_to_delete = $_POST['code'];
        if ($invite_db->deleteCode($code_to_delete)) { 
            $response['status'] = 'success';
            $response['message'] = '✅ 邀请码已删除';
        } else {
            $response['status'] = 'warning';
            $response['message'] = '🤔 删除失败，邀请码不存在或已被使用';
        }
    }
    
    // 重新查询最新的未使用的邀请码列表
    $codes = $invite_db->getAllUnusedCodes();
    $response['html'] = renderTableHtml($codes, $base_url, $register_page_path);
    $response['count'] = count($codes); 
    
    echo json_encode($response);
    exit; 
}

// ----------------------------------------------------
// 【3. 页面渲染】
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
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #0f172a;
            --card-bg: rgba(30, 41, 59, 0.7);
            --primary: #52B54B; 
            --primary-hover: #43943d;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --text-main: #f8fafc;
            --text-sub: #94a3b8;
            --input-bg: rgba(15, 23, 42, 0.6);
            --border-color: rgba(255, 255, 255, 0.1);
            --blur-amt: 16px;
            --transition-speed: 0.3s;
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
            padding: 40px 20px;
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
        .blob-2 { bottom: -10%; right: -10%; width: 400px; height: 400px; background: var(--primary); animation-delay: -5s; }

        @keyframes float {
            0%, 100% { transform: translate(0, 0); }
            50% { transform: translate(30px, 50px); }
        }

        .main-container {
            width: 100%;
            max-width: <?php echo $is_authenticated ? '1000px' : '400px'; ?>; 
            position: relative;
            z-index: 10;
            transition: max-width var(--transition-speed) cubic-bezier(0.16, 1, 0.3, 1);
        }

        .logo-section {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo-section img {
            width: 64px; height: 64px; margin-bottom: 16px; border-radius: 16px;
            box-shadow: 0 0 20px rgba(82, 181, 75, 0.3);
        }
        .logo-section h1 { font-size: 24px; font-weight: 700; margin-bottom: 8px; background: linear-gradient(to right, #fff, #cbd5e1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; }
        .logo-section p { font-size: 14px; color: var(--text-sub); }

        .form-container, .admin-panel {
            background: var(--card-bg);
            backdrop-filter: blur(var(--blur-amt));
            -webkit-backdrop-filter: blur(var(--blur-amt));
            border: 1px solid var(--border-color);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.6s ease-out;
        }

        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-size: 12px; font-weight: 600; text-transform: uppercase; color: var(--text-sub); }
        .form-group input { width: 100%; padding: 14px 16px; background: var(--input-bg); border: 1px solid var(--border-color); border-radius: 12px; color: white; font-family: inherit; font-size: 15px; transition: all var(--transition-speed) ease; }
        .form-group input:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(82, 181, 75, 0.15); background: rgba(15, 23, 42, 0.8); }
        .form-group input[type="submit"] { background: linear-gradient(135deg, var(--primary) 0%, #3d8c38 100%); color: white; cursor: pointer; font-weight: 600; border: none; margin-top: 12px; font-size: 16px; box-shadow: 0 10px 15px -3px rgba(82, 181, 75, 0.3); }
        .form-group input[type="submit"]:hover { transform: translateY(-2px); }

        .admin-header {
            display: flex; justify-content: space-between; align-items: center;
            margin-bottom: 24px; padding-bottom: 24px;
            border-bottom: 1px solid var(--border-color);
        }

        .admin-title { font-size: 18px; font-weight: 600; color: white; } 

        .action-button {
            background: var(--primary); color: white; border: none;
            padding: 10px 20px; border-radius: 10px; font-weight: 600;
            font-size: 14px; cursor: pointer; transition: all var(--transition-speed);
            display: inline-flex; align-items: center; gap: 6px;
            white-space: nowrap; 
        }
        .action-button:hover { background: var(--primary-hover); transform: translateY(-1px); }
        
        .logout-link {
            color: var(--text-sub); text-decoration: none; font-size: 14px;
            font-weight: 500; padding: 8px 16px; border-radius: 8px;
            transition: all var(--transition-speed); border: 1px solid transparent;
        }
        .logout-link:hover { color: var(--danger); background: rgba(239, 68, 68, 0.1); }
        .header-controls { display: flex; gap: 12px; }

        .table-wrapper::-webkit-scrollbar {
            height: 8px; 
            background-color: transparent;
        }

        .table-wrapper::-webkit-scrollbar-thumb {
            background-color: rgba(255, 255, 255, 0.15); 
            border-radius: 10px;
            transition: background-color 0.3s;
        }
        
        .table-wrapper::-webkit-scrollbar-thumb:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        .table-wrapper { 
            overflow-x: auto; 
            scrollbar-width: thin; 
            scrollbar-color: rgba(255, 255, 255, 0.15) transparent; 
        }

        table { width: 100%; border-collapse: separate; border-spacing: 0; }
        
        th { 
            text-align: left; 
            white-space: nowrap; 
            padding: 16px; 
            color: var(--text-sub); 
            font-size: 13px; 
            font-weight: 600; 
            text-transform: uppercase; 
            letter-spacing: 0.05em; 
            border-bottom: 1px solid var(--border-color); 
        }
        td { padding: 16px; border-bottom: 1px solid rgba(255,255,255,0.05); font-size: 14px; color: var(--text-main); vertical-align: middle; }
        tr:last-child td { border-bottom: none; }
        tr:hover td { background: rgba(255,255,255,0.02); }
        
        .col-id { width: 60px; }
        .col-code { width: 120px; }
        .code-badge { font-family: 'SF Mono', 'Roboto Mono', monospace; background: rgba(255,255,255,0.1); padding: 4px 8px; border-radius: 6px; color: #fff; font-size: 13px; }
        
        .col-link { width: auto; }
        .link-wrapper {
            display: inline-flex;
            align-items: center;
            background: var(--input-bg);
            padding: 6px 10px; border-radius: 8px;
            border: 1px solid transparent; transition: all var(--transition-speed);
        }
        .link-wrapper:hover { border-color: var(--primary); background: rgba(82, 181, 75, 0.1); }
        .link-text { 
            font-family: 'SF Mono', 'Roboto Mono', monospace; 
            white-space: nowrap; 
            color: var(--text-sub); 
            font-size: 12px;
        }
        
        .copy-action-btn {
            background: transparent; border: none; cursor: pointer; padding: 0; margin-left: 8px;
            display: flex; align-items: center; transition: color var(--transition-speed), transform 0.2s;
            color: var(--text-sub); flex-shrink: 0; 
        }
        .copy-action-btn:hover { color: var(--primary); }
        .copy-action-btn.success { color: var(--primary); transform: scale(1.1); }
        .copy-action-btn svg { width: 14px; height: 14px; stroke: currentColor; stroke-width: 2; fill: none; }

        .col-action { width: 100px; }
        .action-btn {
            background: transparent; border: 1px solid var(--border-color);
            color: var(--text-sub); padding: 6px 12px; border-radius: 8px;
            cursor: pointer; font-size: 12px; font-weight: 500;
            display: inline-flex; align-items: center; gap: 6px;
            transition: all var(--transition-speed); white-space: nowrap; 
        }
        .action-btn svg { width: 14px; height: 14px; stroke: currentColor; stroke-width: 2; fill: none; }
        .delete-btn:hover { border-color: var(--danger); background: rgba(239, 68, 68, 0.1); color: var(--danger); }
        
        .empty-state { text-align: center; padding: 40px; color: rgba(255,255,255,0.3); }

        #toast-container {
            position: fixed; top: 24px; left: 50%; 
            transform: translateX(-50%);
            z-index: 1000; 
            
            opacity: 0;
            transform: translate(-50%, -40px);
            
            transition: opacity var(--transition-speed) ease-out, transform var(--transition-speed) ease-out;
            pointer-events: none; 
        }
        
        #toast-container.show {
            opacity: 1;
            transform: translate(-50%, 0);
            pointer-events: auto;
        }
        
        .toast {
            background: #1e293b; color: white; padding: 12px 24px;
            border-radius: 50px; font-size: 14px; font-weight: 500;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.5);
            border: 1px solid rgba(255,255,255,0.1);
            display: flex; align-items: center; gap: 8px;
        }
        
        .toast.success { border-color: var(--primary); }
        .toast.error { border-color: var(--danger); }

        .login-error-msg {
            background: rgba(239, 68, 68, 0.15); color: #fca5a5;
            padding: 12px; border-radius: 10px; margin-bottom: 20px;
            font-size: 13px; text-align: center; border: 1px solid rgba(239, 68, 68, 0.3);
            animation: fadeIn 0.3s ease;
        }

        .footer { margin-top: auto; padding-top: 40px; }
        .footer-content {
            display: inline-flex; align-items: center; gap: 10px;
            padding: 8px 16px; background: rgba(255,255,255,0.05);
            border-radius: 50px; border: 1px solid rgba(255,255,255,0.05);
        }
        .footer-content a { color: var(--text-sub); text-decoration: none; font-size: 13px; display: flex; align-items: center; gap: 6px; transition: all 0.2s; }
        .footer-content a:hover { color: white; }
        .github-icon { width: 16px; height: 16px; fill: currentColor; } 

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        
        @media (max-width: 600px) {
            body { padding: 20px 16px; }
            .form-container, .admin-panel { padding: 24px 16px; }
            .link-text { max-width: 100%; }
            .admin-header { flex-direction: column-reverse; gap: 16px; align-items: stretch; }
            .logout-link { text-align: center; background: rgba(255,255,255,0.05); }
        }
    </style>
</head>
<body>
    
    <div class="bg-blob blob-1"></div>
    <div class="bg-blob blob-2"></div>

    <div id="toast-container">
        <div id="status-toast" class="toast">
            <span id="toast-icon"></span>
            <span id="toast-text"></span>
        </div>
    </div>

    <div class="main-container">
        <div class="logo-section">
            <img src="https://emby.media/favicon-96x96.png" alt="Emby Logo">
            <h1>邀请码管理后台</h1>
            <p><?php echo $is_authenticated ? '管理邀请码列表' : '请登录管理员账号'; ?></p>
        </div>

        <?php if (!$is_authenticated): ?>
        <div class="form-container">
            <?php if ($error_message): ?>
                <div class="login-error-msg">⚠️ <?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>
            <form method="post" action="admin.php">
                <div class="form-group">
                    <label for="login_user">账号</label>
                    <input type="text" id="login_user" name="login_user" required placeholder="管理员账号" autocomplete="off">
                </div>
                <div class="form-group">
                    <label for="login_pass">密码</label>
                    <input type="password" id="login_pass" name="login_pass" required placeholder="管理员密码">
                </div>
                <div class="form-group">
                    <input type="submit" value="立即登录">
                </div>
            </form>
        </div>
        
        <?php else: ?>
        
        <div class="admin-panel">
            <div class="admin-header">
                <div class="admin-title">邀请码 (<span id="code-count"><?php echo count($all_invite_codes); ?></span>)</div>
                <div class="header-controls">
                    <button type="button" id="generate-btn" class="action-button" onclick="generateCode()">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        生成
                    </button>
                    <a href="?action=logout" class="logout-link">退出</a>
                </div>
            </div>

            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th class="col-id">序号</th>
                            <th class="col-code">邀请码</th>
                            <th class="col-link">注册链接</th>
                            <th class="col-action">操作</th> 
                        </tr>
                    </thead>
                    <tbody id="invite-code-list">
                        <?php echo $initial_table_html; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
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

    <?php if ($is_authenticated): ?>
    <script>
        let toastTimeout;
        let generateBtnOriginalText = ''; 

        function copyInviteLink(buttonElement, text) {
            const originalContent = buttonElement.innerHTML;
            const tempInput = document.createElement('textarea');
            tempInput.value = text;
            tempInput.style.position = 'fixed'; 
            tempInput.style.left = '-9999px';
            document.body.appendChild(tempInput);
            
            tempInput.select();
            let successful = false;
            try {
                successful = document.execCommand('copy');
            } catch (err) {
                console.error('Copy failed: ', err);
            }
            document.body.removeChild(tempInput);

            if (successful) {
                buttonElement.innerHTML = '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"></polyline></svg>';
                buttonElement.classList.add('success');
                
                setTimeout(() => {
                    buttonElement.innerHTML = originalContent;
                    buttonElement.classList.remove('success');
                }, 1500); 

            } else {
                displayStatus('复制失败，请手动复制', 'error'); 
            }
        }

        function displayStatus(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container');
            const toast = document.getElementById('status-toast');
            const text = document.getElementById('toast-text');
            const icon = document.getElementById('toast-icon');

            if (!message) return;

            text.innerText = message.replace(/^(✅|❌|🤔)\s*/, '');
            toast.className = 'toast';
            if(type === 'error' || type === 'warning') { 
                toast.classList.add('error');
                icon.innerText = type === 'error' ? '❌' : '🤔';
            } else {
                toast.classList.add('success');
                icon.innerText = '✅';
            }

            clearTimeout(toastTimeout);
            toastContainer.classList.remove('show');

            void toastContainer.offsetWidth; 

            toastContainer.classList.add('show');
            
            toastTimeout = setTimeout(() => {
                toastContainer.classList.remove('show');
            }, 3000); 
        }

        async function sendAjaxRequest(body) {
            const isGenerateAction = body.includes('action=generate');
            const actionButton = isGenerateAction ? document.getElementById('generate-btn') : null;
            
            if (isGenerateAction && actionButton) {
                generateBtnOriginalText = actionButton.innerHTML; 
                
                actionButton.disabled = true;
                
                const styleId = 'spin-keyframes';
                if (!document.getElementById(styleId)) {
                    const style = document.createElement('style');
                    style.id = styleId;
                    style.innerHTML = `@keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }`;
                    document.head.appendChild(style);
                }

                actionButton.innerHTML = `
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" style="animation: spin 0.8s linear infinite;">
                        <path d="M12 2C6.477 2 2 6.477 2 12s4.477 10 10 10 10-4.477 10-10S17.523 2 12 2z" stroke-opacity=".3"/>
                        <path d="M22 12c0-5.523-4.477-10-10-10" stroke-linecap="round"/>
                    </svg>
                    处理中...
                `;
            }


            try {
                const response = await fetch("admin.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "ajax=1&" + body 
                });

                if (!response.ok) throw new Error(`HTTP Error: ${response.status}`);

                const data = await response.json();
                
                document.getElementById("invite-code-list").innerHTML = data.html;
                
                document.getElementById("code-count").innerText = data.count || 0;
                
                displayStatus(data.message, data.status);

            } catch (error) {
                console.error('AJAX Error:', error);
                displayStatus('❌ 网络请求失败', 'error');
            } finally {
                if (isGenerateAction && actionButton) {
                    actionButton.disabled = false;
                    actionButton.innerHTML = generateBtnOriginalText; 
                }
            }
        }

        function generateCode() {
            sendAjaxRequest('action=generate');
        }

        function deleteCode(code) {
            const confirmAction = window.confirm(`确定要删除邀请码 ${code} 吗？`);
            if (!confirmAction) return;
            
            sendAjaxRequest(`action=delete&code=${encodeURIComponent(code)}`);
        }
    </script>
    <?php endif; ?>

</body>
</html>
