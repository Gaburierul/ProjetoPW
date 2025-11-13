<?php
// app/Order.php
require_once __DIR__ . '/../config/db.php';
class Order {
    public static function all(){
        $sql = 'SELECT o.*, c.name as customer_name
                FROM orders o
                LEFT JOIN customers c ON o.customer_id = c.id
                ORDER BY o.created_at DESC';
        return getPDO()->query($sql)->fetchAll();
    }
    public static function find($id){
        $stmt = getPDO()->prepare('SELECT * FROM orders WHERE id=?');
        $stmt->execute([$id]);
        $order = $stmt->fetch();
        if ($order){
            $stmt2 = getPDO()->prepare('SELECT oi.*, p.name as product_name FROM order_items oi JOIN products p ON oi.product_id=p.id WHERE oi.order_id=?');
            $stmt2->execute([$id]);
            $order['items'] = $stmt2->fetchAll();
        }
        return $order;
    }
    public static function create($data){
        $pdo = getPDO();
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO orders (customer_id,table_number,total,status) VALUES (?,?,?,?)');
            $stmt->execute([$data['customer_id'] ?? null, $data['table_number'] ?? null, 0, $data['status'] ?? 'pending']);
            $order_id = $pdo->lastInsertId();
            $total = 0;
            $ins = $pdo->prepare('INSERT INTO order_items (order_id,product_id,quantity,unit_price,subtotal) VALUES (?,?,?,?,?)');
            foreach ($data['items'] as $it){
                $product = $pdo->prepare('SELECT * FROM products WHERE id=?');
                $product->execute([$it['product_id']]);
                $p = $product->fetch();
                if (!$p) throw new Exception('Produto não encontrado: ' . $it['product_id']);
                $subtotal = $p['price'] * $it['quantity'];
                $ins->execute([$order_id,$it['product_id'],$it['quantity'],$p['price'],$subtotal]);
                $total += $subtotal;
                // optionally update stock if available
                if ($p['stock'] !== null){
                    $u = $pdo->prepare('UPDATE products SET stock = stock - ? WHERE id = ?');
                    $u->execute([$it['quantity'],$it['product_id']]);
                }
            }
            $u2 = $pdo->prepare('UPDATE orders SET total = ? WHERE id = ?');
            $u2->execute([$total,$order_id]);
            $pdo->commit();
            return $order_id;
        } catch (Exception $e){
            $pdo->rollBack();
            throw $e;
        }
    }
}
?>