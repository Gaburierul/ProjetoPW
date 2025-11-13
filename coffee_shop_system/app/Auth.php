<?php
// app/Auth.php
require_once __DIR__ . '/../config/db.php';
session_start();

class Auth {
    public static function check(){
        return isset($_SESSION['user']);
    }
    public static function user(){
        return $_SESSION['user'] ?? null;
    }
    public static function requireAuth(){
        if (!self::check()){
            header('Location: /login.php');
            exit;
        }
    }
    public static function login($username, $password){
        $pdo = getPDO();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user && password_verify($password, $user['password'])){
            unset($user['password']);
            $_SESSION['user'] = $user;
            return true;
        }
        return false;
    }
    public static function logout(){
        session_destroy();
    }
}
?>