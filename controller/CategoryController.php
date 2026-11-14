<?php
// controller/CategoryController.php

require_once __DIR__ . '/../models/Category.php';

class CategoryController {
    private $category;

    public function __construct($pdo) {
        $this->category = new Category($pdo);
    }

    // POST /api/category/getall
    public function getAll() {
        return ['success' => true, 'data' => $this->category->getAll()];
    }

    // POST /api/category/show   body: {"id":1}
    public function show($data) {
        $id = $data['id'] ?? null;
        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }

        $category = $this->category->getById($id);
        if (!$category) {
            return ['success' => false, 'status' => 404, 'message' => 'Category not found'];
        }

        return ['success' => true, 'data' => $category];
    }

    // POST /api/category/create   body: {"category_name":"...","description":"...","status":"Active"}
    public function create($data) {
        $name        = trim($data['category_name'] ?? '');
        $description = trim($data['description'] ?? '');
        $status      = trim($data['status'] ?? 'Active');

        if ($name === '') {
            return ['success' => false, 'message' => 'Category name is required'];
        }
        if (!in_array($status, ['Active', 'Inactive'])) {
            return ['success' => false, 'message' => 'Status must be Active or Inactive'];
        }
        if ($this->category->getByName($name)) {
            return ['success' => false, 'message' => 'Category already exists'];
        }

        $id = $this->category->create($name, $description, $status);

        return ['success' => true, 'status' => 201, 'message' => 'Category created', 'id' => $id];
    }

    // POST /api/category/update   body: {"id":1,"category_name":"...","description":"...","status":"Active"}
    public function update($data) {
        $id          = $data['id'] ?? null;
        $name        = trim($data['category_name'] ?? '');
        $description = trim($data['description'] ?? '');
        $status      = trim($data['status'] ?? 'Active');

        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }
        if (!$this->category->getById($id)) {
            return ['success' => false, 'status' => 404, 'message' => 'Category not found'];
        }
        if ($name === '') {
            return ['success' => false, 'message' => 'Category name is required'];
        }
        if (!in_array($status, ['Active', 'Inactive'])) {
            return ['success' => false, 'message' => 'Status must be Active or Inactive'];
        }

        // Name must not belong to a different category
        $existing = $this->category->getByName($name);
        if ($existing && $existing['id'] != $id) {
            return ['success' => false, 'message' => 'Category already exists'];
        }

        $this->category->update($id, $name, $description, $status);

        return ['success' => true, 'message' => 'Category updated'];
    }

    // POST /api/category/delete   body: {"id":1}
    public function delete($data) {
        $id = $data['id'] ?? null;
        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }
        if (!$this->category->getById($id)) {
            return ['success' => false, 'status' => 404, 'message' => 'Category not found'];
        }

        $this->category->delete($id);

        return ['success' => true, 'message' => 'Category deleted'];
    }
}