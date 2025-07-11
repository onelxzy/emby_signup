# Emby Signup 自助注册页面

一个基于 PHP 的 Emby 媒体服务器自助注册系统，允许用户通过 Web 页面创建新账号，自动复制一个预设模板用户的配置和权限，并设置个人密码。

> ⚠️ 请注意：本程序直接操作 Emby 的用户创建和密码修改接口，**建议部署在局域网或仅向受信用户开放，谨防源码泄露和 Token 被盗用**。

---

## ✨ 功能特性

- 提供简洁美观的用户注册界面
- 通过 PHP 脚本调用 Emby API 完成用户创建
- 复制模板用户配置（权限、设定等）
- 支持密码强校验，包括长度、字母、数字等条件
- 提示性控件，或反馈注册成功/失败原因

---

## 🔧 部署步骤

1. 构建 PHP 环境，将 `index.php` 文件上传至相关目录
2. 编辑 `index.php`，根据注释修改以下行：

| 行号         | 字段名             | 说明 |
|--------------|-------------------|------|
| 20/40        | `【server】`      | Emby 服务器地址（推荐使用内网地址保障token安全） |
| 20/40        | `【port】`        | Emby 服务端口，通常为 8096 或 8920（https） |
| 20/40        | `【token】`       | Emby 管理员账号的 API Token（具备创建账号权限） |
| 21           | `【preset_userid】` | 模板用户 ID（作为新账号的复制模板） |
| 434          | 登录地址链接      | 修改为用户可访问的 Emby 登录页地址 |

📌 **模板账号权限必须事先设置好！** 新用户会完整继承该用户的 Emby 权限设置，请谨慎选择。

---

## 🌟 页面预览
> 简洁美观的响应式注册页面，支持 PC 与移动端

![image](https://github.com/user-attachments/assets/f715b9e7-a050-4c34-8748-b92f33a6713f)

---

## 🌍 项目地址

[https://github.com/onelxzy/emby_signup](https://github.com/onelxzy/emby_signup)

---

## 🚀 License

MIT License
