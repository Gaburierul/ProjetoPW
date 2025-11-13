<?php
// app/Customer.php
require_once __DIR__ . '/../config/db.php';
class Customer {
    public static function all(){
        $stmt = getPDO()->query('SELECT * FROM customers ORDER BY name');
        return $stmt->fetchAll();
    }
    public static function find($id){
        $stmt = getPDO()->prepare('SELECT * FROM customers WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public static function create($data){
        $stmt = getPDO()->prepare('INSERT INTO customers (name,phone,email,notes) VALUES (?,?,?,?)');
        return $stmt->execute([$data['name'],$data['phone'] ?? null,$data['email'] ?? null,$data['notes'] ?? null]);
    }
}
?>