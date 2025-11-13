<?php
// app/Product.php
require_once __DIR__ . '/../config/db.php';
class Product {
    public static function all(){
        $stmt = getPDO()->query('SELECT * FROM products ORDER BY category, name');
        return $stmt->fetchAll();
    }
    public static function find($id){
        $stmt = getPDO()->prepare('SELECT * FROM products WHERE id=?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public static function create($data){
        $stmt = getPDO()->prepare('INSERT INTO products (name,description,price,category,stock) VALUES (?,?,?,?,?)');
        return $stmt->execute([$data['name'],$data['description'] ?? null,$data['price'],$data['category'] ?? null,$data['stock'] ?? null]);
    }
}
?>