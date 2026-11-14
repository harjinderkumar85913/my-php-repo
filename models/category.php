<?php
// models/Category.php

class Category {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
        $this->createTable();
    }

    private function createTable() {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS category (
            id INT AUTO_INCREMENT PRIMARY KEY,
            category_name VARCHAR(100) NOT NULL,
            description VARCHAR(150) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'Active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function getAll() {
        $stmt = $this->pdo->query("SELECT * FROM category ORDER BY id DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM category WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getByName($name) {
        $stmt = $this->pdo->prepare("SELECT * FROM category WHERE category_name = ? LIMIT 1");
        $stmt->execute([$name]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($name, $description, $status = 'Active') {
        $stmt = $this->pdo->prepare(
            "INSERT INTO category (category_name, description, status) VALUES (?, ?, ?)"
        );
        $stmt->execute([$name, $description, $status]);
        return $this->pdo->lastInsertId();
    }

    public function update($id, $name, $description, $status) {
        $stmt = $this->pdo->prepare(
            "UPDATE category SET category_name = ?, description = ?, status = ? WHERE id = ?"
        );
        return $stmt->execute([$name, $description, $status, $id]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM category WHERE id = ?");
        return $stmt->execute([$id]);
    }
}