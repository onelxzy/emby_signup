<?php
header("Content-Type: text/html; charset=utf-8"); 

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = htmlspecialchars($_POST['username']);
    $passwd = $_POST['passwd'];
    $confirm_passwd = $_POST['confirm_passwd'];

    // 输入验证
    if (!preg_match("/^[a-zA-Z0-9]{4,}$/", $username)) {
        $message = '用户名不符合格式要求！';
    } else if ($passwd !== $confirm_passwd) {
        $message = '两次输入的密码不一致！';
    } else {
        // 请求新建账号接口（提前预设好账号模板并抓包获取其userid）
        $url1 = "http://【server】:【port】/emby/Users/New?X-Emby-Token=【token】";    // 中括号的部分替换为你自己的接口信息，建议使用内网服务地址和token并谨防php源码泄露
        $data1 = array('Name' => $username, 'CopyFromUserId' => '【preset_userid】', 'UserCopyOptions' => 'UserPolicy,UserConfiguration');    // 中括号内容替换为你模板账号的userid
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
	        $url2 = "http://【server】:【ip】/emby/Users/{$userid}/Password?X-Emby-Token=【token】";         //同上替换中括号中内容
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
<html>
<head>
    <meta charset="UTF-8">
    <title>Emby自助注册</title>
    <link rel="icon" type="image/png" href="https://emby.media/favicon-32x32.png" sizes="32x32">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 0;
        }
        .container {
            width: 300px;
            margin: 0 auto;
            padding-top: 100px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        .form-group input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
        }
        .form-group input[type="submit"]:hover {
            background-color: #45a049;
        }
        #message {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 50px;
            background-color: white;
            color: black;
            border-radius: 5px;
            border: 2px solid lightgray;
            z-index: 1000;
        }
        #message button {
            display: block;
            margin: 20px auto 0;
            padding: 10px 20px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        #message button:hover {
            background-color: #45a049;
        }
    </style>
    <script>
        function showMessage() {
            var message = document.getElementById('message');
            message.style.display = 'block';
            setTimeout(function() {
                message.style.display = 'none';
            }, 3000);
        }
    </script>
</head>
<body>
    <div class="container">
        <form method="post" action="">
            <div class="form-group">
                <label for="username">用户名：</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="passwd">密码：</label>
                <input type="password" id="passwd" name="passwd" required>
            </div>
            <div class="form-group">
                <label for="confirm_passwd">确认密码：</label>
                <input type="password" id="confirm_passwd" name="confirm_passwd" required>
            </div>
            <div class="form-group">
                <input type="submit" value="注册">
            </div>
        </form>
    </div>
    <div id="message">
        <?php echo $message; ?>
        <button onclick="document.getElementById('message').style.display='none'">OK</button>
    </div>
    <?php if ($message !== '') echo "<script>showMessage();</script>"; ?>
</body>
</html>
