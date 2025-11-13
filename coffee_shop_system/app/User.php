<?php
// app/User.php
require_once __DIR__ . '/../config/db.php';
class User {
    public static function all(){
        $stmt = getPDO()->query('SELECT id,username,email,role,created_at FROM users');
        return $stmt->fetchAll();
    }
    public static function create($username, $password, $email, $role='barista'){
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = getPDO()->prepare('INSERT INTO users (username,password,email,role) VALUES (?,?,?,?)');
        return $stmt->execute([$username,$hash,$email,$role]);
    }
    public static function ensureAdminExists(){
        $pdo = getPDO();
        $stmt = $pdo->query('SELECT COUNT(*) as cnt FROM users');
        $c = $stmt->fetch()['cnt'];
        if ($c == 0){
            self::create('admin','admin123','admin@coffee.local','admin');
        }
    }
}
?>