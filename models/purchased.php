<?php
// models/Purchased.php

class Purchased {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTable();
    }

    private function createTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS purchased (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id VARCHAR(50) DEFAULT NULL,
            user_id INT NOT NULL,
            product_id INT NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10, 2) NOT NULL,
            total_price DECIMAL(10, 2) NOT NULL,
            payment_status VARCHAR(50) NOT NULL DEFAULT 'Completed',
            status VARCHAR(50) NOT NULL DEFAULT 'Pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    // Get all purchases (for Admin) with user, product, and category details
    public function getAll() {
        $sql = "SELECT 
                    pu.id,
                    pu.order_id,
                    pu.user_id,
                    u.name AS user_name,
                    u.email AS user_email,
                    pu.product_id,
                    pr.product_name,
                    c.category_name,
                    pu.quantity,
                    pu.price,
                    pu.total_price,
                    pu.payment_status,
                    pu.status,
                    pu.created_at
                FROM purchased pu
                LEFT JOIN users u ON pu.user_id = u.id
                LEFT JOIN product pr ON pu.product_id = pr.id
                LEFT JOIN category c ON pr.category_id = c.id
                ORDER BY pu.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get purchase by ID (optionally check user ownership)
    public function getById($id, $userId = null) {
        $sql = "SELECT 
                    pu.id,
                    pu.order_id,
                    pu.user_id,
                    u.name AS user_name,
                    u.email AS user_email,
                    pu.product_id,
                    pr.product_name,
                    c.category_name,
                    pu.quantity,
                    pu.price,
                    pu.total_price,
                    pu.payment_status,
                    pu.status,
                    pu.created_at
                FROM purchased pu
                LEFT JOIN users u ON pu.user_id = u.id
                LEFT JOIN product pr ON pu.product_id = pr.id
                LEFT JOIN category c ON pr.category_id = c.id
                WHERE pu.id = ?";
        $params = [$id];

        if ($userId !== null) {
            $sql .= " AND pu.user_id = ?";
            $params[] = $userId;
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    // Get all purchases for a specific user
    public function getByUserId($userId) {
        $sql = "SELECT 
                    pu.id,
                    pu.order_id,
                    pu.product_id,
                    pr.product_name,
                    c.category_name,
                    pu.quantity,
                    pu.price,
                    pu.total_price,
                    pu.payment_status,
                    pu.status,
                    pu.created_at
                FROM purchased pu
                LEFT JOIN product pr ON pu.product_id = pr.id
                LEFT JOIN category c ON pr.category_id = c.id
                WHERE pu.user_id = ?
                ORDER BY pu.id DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Get purchases grouped by order_id
    public function getByOrderId($orderId, $userId = null) {
        $sql = "SELECT 
                    pu.id,
                    pu.order_id,
                    pu.user_id,
                    pu.product_id,
                    pr.product_name,
                    pu.quantity,
                    pu.price,
                    pu.total_price,
                    pu.payment_status,
                    pu.status,
                    pu.created_at
                FROM purchased pu
                LEFT JOIN product pr ON pu.product_id = pr.id
                WHERE pu.order_id = ?";
        $params = [$orderId];

        if ($userId !== null) {
            $sql .= " AND pu.user_id = ?";
            $params[] = $userId;
        }

        $sql .= " ORDER BY pu.id ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Create a purchase record and reduce product stock
    public function create($userId, $productId, $quantity, $price, $totalPrice = null, $orderId = null, $paymentStatus = 'Completed', $status = 'Pending') {
        if ($totalPrice === null) {
            $totalPrice = $price * $quantity;
        }
        if ($orderId === null) {
            $orderId = 'ORD-' . strtoupper(uniqid());
        }

        // Deduct stock from product table
        $updateStock = $this->pdo->prepare("UPDATE product SET quantity = quantity - ? WHERE id = ? AND quantity >= ?");
        $updateStock->execute([$quantity, $productId, $quantity]);

        $stmt = $this->pdo->prepare(
            "INSERT INTO purchased (order_id, user_id, product_id, quantity, price, total_price, payment_status, status) 
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$orderId, $userId, $productId, $quantity, $price, $totalPrice, $paymentStatus, $status]);
        return [
            'id'       => $this->pdo->lastInsertId(),
            'order_id' => $orderId
        ];
    }

    // Update order fulfillment status (e.g. 'Pending', 'Processing', 'Delivered', 'Cancelled')
    public function updateStatus($id, $status) {
        $stmt = $this->pdo->prepare("UPDATE purchased SET status = ? WHERE id = ?" );
        return $stmt->execute([$status, $id]);
    }

    // Update payment status (e.g. 'Completed', 'Pending', 'Failed', 'Refunded')
    public function updatePaymentStatus($id, $paymentStatus) {
        $stmt = $this->pdo->prepare("UPDATE purchased SET payment_status = ? WHERE id = ?");
        return $stmt->execute([$paymentStatus, $id]);
    }

    // Cancel purchase and restore product quantity
    public function cancel($id, $userId = null) {
        $purchase = $this->getById($id, $userId);
        if (!$purchase || $purchase['status'] === 'Cancelled') {
            return false;
        }

        // Restore stock
        $restoreStock = $this->pdo->prepare("UPDATE product SET quantity = quantity + ? WHERE id = ?");
        $restoreStock->execute([$purchase['quantity'], $purchase['product_id']]);

        // Update status
        $stmt = $this->pdo->prepare("UPDATE purchased SET status = 'Cancelled' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    // Delete purchase record
    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM purchased WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

