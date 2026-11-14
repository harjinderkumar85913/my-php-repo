<?php
// controller/ProductController.php

require_once __DIR__ . '/../models/product.php';
require_once __DIR__ . '/../models/category.php';

class ProductController {
    private $product;
    private $category;

    public function __construct($pdo) {
        $this->product  = new Product($pdo);
        $this->category = new Category($pdo);
    }

    // POST /api/product/getall
    public function getAll() {
        return ['success' => true, 'data' => $this->product->getAll()];
    }

    // POST /api/product/show   body: {"id":1}
    public function show($data) {
        $id = $data['id'] ?? null;
        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }

        $product = $this->product->getById($id);
        if (!$product) {
            return ['success' => false, 'status' => 404, 'message' => 'Product not found'];
        }

        return ['success' => true, 'data' => $product];
    }

    // POST /api/product/by-category   body: {"category_id":1}
    public function getByCategory($data) {
        $categoryId = $data['category_id'] ?? null;
        if (!$categoryId) {
            return ['success' => false, 'message' => 'Category ID is required'];
        }

        return ['success' => true, 'data' => $this->product->getByCategory($categoryId)];
    }

    // POST /api/product/create   body: {"product_name":"...","category_id":1,"price":99.99,"quantity":10,"description":"...","status":"Active"}
    public function create($data) {
        $name        = trim($data['product_name'] ?? '');
        $categoryId  = $data['category_id'] ?? null;
        $price       = $data['price'] ?? null;
        $quantity    = $data['quantity'] ?? 0;
        $description = trim($data['description'] ?? '');
        $status      = trim($data['status'] ?? 'Active');

        if ($name === '') {
            return ['success' => false, 'message' => 'Product name is required'];
        }
        if ($price === null || !is_numeric($price) || $price < 0) {
            return ['success' => false, 'message' => 'Valid price is required'];
        }
        if (!is_numeric($quantity) || $quantity < 0) {
            return ['success' => false, 'message' => 'Quantity must be 0 or greater'];
        }
        if (!in_array($status, ['Active', 'Inactive'])) {
            return ['success' => false, 'message' => 'Status must be Active or Inactive'];
        }
        if ($categoryId && !$this->category->getById($categoryId)) {
            return ['success' => false, 'status' => 404, 'message' => 'Category does not exist'];
        }
        if ($this->product->getByName($name)) {
            return ['success' => false, 'message' => 'Product already exists'];
        }

        $id = $this->product->create($name, $categoryId, (float)$price, (int)$quantity, $description, $status);

        return ['success' => true, 'status' => 201, 'message' => 'Product created', 'id' => $id];
    }

    // POST /api/product/update   body: {"id":1,"product_name":"...","category_id":1,"price":99.99,"quantity":10,"description":"...","status":"Active"}
    public function update($data) {
        $id          = $data['id'] ?? null;
        $name        = trim($data['product_name'] ?? '');
        $categoryId  = $data['category_id'] ?? null;
        $price       = $data['price'] ?? null;
        $quantity    = $data['quantity'] ?? 0;
        $description = trim($data['description'] ?? '');
        $status      = trim($data['status'] ?? 'Active');

        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }
        if (!$this->product->getById($id)) {
            return ['success' => false, 'status' => 404, 'message' => 'Product not found'];
        }
       
        
       
        if ($categoryId && !$this->category->getById($categoryId)) {
            return ['success' => false, 'status' => 404, 'message' => 'Category does not exist'];
        }

        // Name must not belong to a different product
        $existing = $this->product->getByName($name);
        if ($existing && $existing['id'] != $id) {
            return ['success' => false, 'message' => 'Product already exists'];
        }

        $this->product->update($id, $name, $categoryId, (float)$price, (int)$quantity, $description, $status);

        return ['success' => true, 'message' => 'Product updated'];
    }

    // POST /api/product/delete   body: {"id":1}
    public function delete($data) {
        $id = $data['id'] ?? null;
        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }
        if (!$this->product->getById($id)) {
            return ['success' => false, 'status' => 404, 'message' => 'Product not found'];
        }

        $this->product->delete($id);

        return ['success' => true, 'message' => 'Product deleted'];
    }
}

