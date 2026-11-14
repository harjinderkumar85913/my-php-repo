<?php
// models/Product.php

class Product {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTable();
    }

    private function createTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS product (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_name VARCHAR(150) NOT NULL,
            category_id INT DEFAULT NULL,
            price DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            quantity INT NOT NULL DEFAULT 0,
            description TEXT,
            status VARCHAR(50) NOT NULL DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function getAll() {
        $sql = "SELECT p.*, c.category_name 
                FROM product p 
                LEFT JOIN category c ON p.category_id = c.id 
                ORDER BY p.id DESC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $sql = "SELECT p.*, c.category_name 
                FROM product p 
                LEFT JOIN category c ON p.category_id = c.id 
                WHERE p.id = ? 
                LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByName($name) {
        $stmt = $this->pdo->prepare("SELECT * FROM product WHERE product_name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByCategory($categoryId) {
        $stmt = $this->pdo->prepare("SELECT * FROM product WHERE category_id = ? ORDER BY id DESC");
        $stmt->execute([$categoryId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($name, $categoryId, $price, $quantity, $description, $status = 'Active') {
        $stmt = $this->pdo->prepare(
            "INSERT INTO product (product_name, category_id, price, quantity, description, status) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$name, $categoryId, $price, $quantity, $description, $status]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $name, $categoryId, $price, $quantity, $description, $status) {
        $stmt = $this->pdo->prepare(
            "UPDATE product 
             SET product_name = ?, category_id = ?, price = ?, quantity = ?, description = ?, status = ? 
             WHERE id = ?"
        );
        return $stmt->execute([$name, $categoryId, $price, $quantity, $description, $status, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM product WHERE id = ?");
        return $stmt->execute([$id]);
    }
}

