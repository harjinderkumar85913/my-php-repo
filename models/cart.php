<?php
// models/Cart.php

class Cart {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTable();
    }

    private function createTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS cart (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_product (user_id, product_id)
        )");
    }

    public function getByUserId($userId) {
        $sql = "SELECT 
                    c.id AS cart_id,
                    c.user_id,
                    c.product_id,
                    c.quantity,
                    p.product_name,
                    p.price,
                    p.quantity AS stock,
                    p.status AS product_status,
                    (c.quantity * p.price) AS subtotal,
                    c.created_at,
                    c.updated_at
                FROM cart c
                JOIN product p ON c.product_id = p.id
                WHERE c.user_id = ?
                ORDER BY c.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id, $userId = null) {
        $sql = "SELECT c.*, p.product_name, p.price, p.quantity AS stock 
                FROM cart c
                JOIN product p ON c.product_id = p.id
                WHERE c.id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND c.user_id = ?";
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getItem($userId, $productId) {
        $stmt = $this->pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ? LIMIT 1");
        $stmt->execute([$userId, $productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function addItem($userId, $productId, $quantity = 1) {
        $existing = $this->getItem($userId, $productId);

        if ($existing) {
            $newQuantity = $existing['quantity'] + $quantity;
            $stmt = $this->pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
            $stmt->execute([$newQuantity, $existing['id']]);
            return $existing['id'];
        }

        $stmt = $this->pdo->prepare(
            "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)"
        );
        $stmt->execute([$userId, $productId, $quantity]);
        return $this->pdo->lastInsertId();
    }

    public function updateQuantity($id, $userId, $quantity) {
        $stmt = $this->pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
        return $stmt->execute([$quantity, $id, $userId]);
    }

    public function removeItem($id, $userId) {
        $stmt = $this->pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
        return $stmt->execute([$id, $userId]);
    }

    public function clearCart($userId) {
        $stmt = $this->pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        return $stmt->execute([$userId]);
    }

    public function getSummary($userId) {
        $sql = "SELECT 
                    COALESCE(SUM(c.quantity), 0) AS total_items,
                    COALESCE(SUM(c.quantity * p.price), 0.00) AS total_price
                FROM cart c
                JOIN product p ON c.product_id = p.id
                WHERE c.user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

