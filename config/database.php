<?php

if (__FILE__ === realpath($_SERVER['SCRIPT_FILENAME'])) {
    header("HTTP/1.0 403 Forbidden");
    exit("Access Denied");
}

/**
 * InviteDB 类：用于管理邀请码的 SQLite 数据库操作
 */
class InviteDB
{
    private SQLite3 $db;
    
    /**
     * 构造函数：连接或创建数据库，并确保表结构存在。
     * @param string $db_path 数据库文件的绝对路径
     */
    public function __construct(string $db_path)
    {
        // 自动创建数据库文件（如果不存在）
        try {
            // SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE 是默认值
            $this->db = new SQLite3($db_path);
        } catch (Exception $e) {
            // 捕获权限错误或PHP扩展未安装错误
            die("❌ 数据库连接失败，请检查 PHP 的 sqlite3 扩展是否安装，或目录权限是否允许写入！错误: " . $e->getMessage());
        }

        $this->initTable();
    }

    /**
     * 初始化表结构
     */
    private function initTable(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS invite_codes (
            code TEXT PRIMARY KEY NOT NULL,
            used INTEGER DEFAULT 0 NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )";

        $this->db->exec($sql);
        
        // 启用 WAL 模式以提高并发写入性能
        $this->db->exec('PRAGMA journal_mode = wal;');
    }
    
    /**
     * 生成随机邀请码
     */
    public static function generateRandomCode($length = 16): string
    {
        $chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $out = '';
        for ($i = 0; $i < $length; $i++) {
            $out .= $chars[random_int(0, strlen($chars) - 1)]; 
        }
        return $out;
    }

    /**
     * 新增邀请码
     * @param string $code
     * @return bool 成功返回 true，如果邀请码已存在或失败返回 false
     */
    public function insertCode(string $code): bool
    {
        $stmt = $this->db->prepare('INSERT INTO invite_codes (code) VALUES (:code)');
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        
        $this->db->exec('BEGIN TRANSACTION');
        $result = $stmt->execute();
        $this->db->exec('COMMIT');

        return $result !== false && $this->db->changes() > 0;
    }

    /**
     * 查询所有未使用的邀请码
     * @return array 包含所有未使用的邀请码字符串的数组
     */
    public function getAllUnusedCodes(): array
    {
        $codes = [];
        // 查询未使用的 (used = 0)，并按创建时间倒序排列
        $result = $this->db->query("SELECT code FROM invite_codes WHERE used = 0 ORDER BY created_at DESC");

        if ($result) {
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $codes[] = $row['code'];
            }
        }
        return $codes;
    }

    /**
     * 删除指定的邀请码 (只能删除未使用的)
     * @param string $code
     * @return bool 成功删除返回 true，否则返回 false
     */
    public function deleteCode(string $code): bool
    {
        $stmt = $this->db->prepare('DELETE FROM invite_codes WHERE code = :code AND used = 0');
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        
        $this->db->exec('BEGIN TRANSACTION');
        $stmt->execute();
        $this->db->exec('COMMIT');
        
        // 检查是否有行被删除
        return $this->db->changes() > 0;
    }

    /**
     * 验证并使用邀请码 (原子性操作)
     * @param string $code 用户输入的邀请码
     * @return bool 验证成功并标记为已使用返回 true，否则返回 false
     */
    public function useCode(string $code): bool
    {
        $this->db->exec('BEGIN TRANSACTION');
        
        // 1. 尝试更新邀请码：从 used=0 状态改为 used=1
        // 只有当邀请码存在且状态为未使用(0)时，更新才会成功。
        $stmt = $this->db->prepare('UPDATE invite_codes SET used = 1 WHERE code = :code AND used = 0');
        $stmt->bindValue(':code', $code, SQLITE3_TEXT);
        $stmt->execute();
        
        // 2. 检查更新是否成功：如果影响行数 > 0，说明更新成功，邀请码有效。
        $success = $this->db->changes() > 0;
        
        // 提交或回滚事务
        if ($success) {
            $this->db->exec('COMMIT');
            return true;
        } else {
            $this->db->exec('ROLLBACK');
            return false; // 邀请码不存在或已被使用
        }
    }
}

// ----------------------------------------------------
// 全局初始化
// ----------------------------------------------------

// 数据库文件路径
$db_path = __DIR__ . '/invite_codes.sqlite'; 

// 创建全局数据库实例
$invite_db = new InviteDB($db_path);
?>