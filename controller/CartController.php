<?php
// controller/CartController.php

require_once __DIR__ . '/../models/cart.php';
require_once __DIR__ . '/../models/product.php';

class CartController {
    private $cart;
    private $product;

    public function __construct($pdo) {
        $this->cart    = new Cart($pdo);
        $this->product = new Product($pdo);
    }

    // POST /api/cart/get
    public function getCart($data, $currentUser) {
        $userId  = $currentUser['id'];
        $items   = $this->cart->getByUserId($userId);
        $summary = $this->cart->getSummary($userId);

        return [
            'success' => true,
            'data'    => [
                'items'       => $items,
                'total_items' => (int) $summary['total_items'],
                'total_price' => (float) $summary['total_price']
            ]
        ];
    }

    // POST /api/cart/getbyuser   body: {"user_id": 1}
    public function getByUserId($data, $currentUser) {
        $targetUserId = $data['user_id'] ?? null;

        if (!$targetUserId) {
            return ['success' => false, 'message' => 'user_id is required'];
        }

        // Only admin can view another person's cart. Regular users can only view their own cart.
        if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $targetUserId) {
            return [
                'success' => false,
                'status'  => 403,
                'message' => 'Access denied: You can only view your own cart'
            ];
        }

        $items   = $this->cart->getByUserId($targetUserId);
        $summary = $this->cart->getSummary($targetUserId);

        return [
            'success' => true,
            'data'    => [
                'user_id'     => (int) $targetUserId,
                'items'       => $items,
                'total_items' => (int) $summary['total_items'],
                'total_price' => (float) $summary['total_price']
            ]
        ];
    }

    // POST /api/cart/add   body: {"product_id": 1, "quantity": 2}
    public function add($data, $currentUser) {
        $userId    = $currentUser['id'];
        $productId = $data['product_id'] ?? null;
        $quantity  = $data['quantity'] ?? 1;

        if (!$productId) {
            return ['success' => false, 'message' => 'Product ID is required'];
        }
        if (!is_numeric($quantity) || (int)$quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be at least 1'];
        }
        $quantity = (int)$quantity;

        // Verify product exists and is active
        $product = $this->product->getById($productId);
        if (!$product) {
            return ['success' => false, 'status' => 404, 'message' => 'Product not found'];
        }
        if (isset($product['status']) && $product['status'] !== 'Active') {
            return ['success' => false, 'message' => 'This product is currently unavailable'];
        }

        // Check stock availability
        $existingItem   = $this->cart->getItem($userId, $productId);
        $currentInCart  = $existingItem ? (int)$existingItem['quantity'] : 0;
        $requestedTotal = $currentInCart + $quantity;

        if ($requestedTotal > (int)$product['quantity']) {
            return [
                'success' => false,
                'message' => "Insufficient stock. Only {$product['quantity']} available in stock."
            ];
        }

        $cartId = $this->cart->addItem($userId, $productId, $quantity);

        return [
            'success' => true,
            'status'  => 201,
            'message' => 'Product added to cart',
            'cart_id' => $cartId
        ];
    }

    // POST /api/cart/update   body: {"cart_id": 1, "quantity": 3}
    public function update($data, $currentUser) {
        $userId   = $currentUser['id'];
        $cartId   = $data['cart_id'] ?? $data['id'] ?? null;
        $quantity = $data['quantity'] ?? null;

        if (!$cartId) {
            return ['success' => false, 'message' => 'Cart ID is required'];
        }
        if ($quantity === null || !is_numeric($quantity) || (int)$quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be at least 1'];
        }
        $quantity = (int)$quantity;

        $item = $this->cart->getById($cartId, $userId);
        if (!$item) {
            return ['success' => false, 'status' => 404, 'message' => 'Cart item not found'];
        }

        // Check product stock
        $product = $this->product->getById($item['product_id']);
        if ($product && $quantity > (int)$product['quantity']) {
            return [
                'success' => false,
                'message' => "Insufficient stock. Only {$product['quantity']} available in stock."
            ];
        }

        $this->cart->updateQuantity($cartId, $userId, $quantity);

        return ['success' => true, 'message' => 'Cart updated'];
    }

    // POST /api/cart/remove   body: {"cart_id": 1}
    public function remove($data, $currentUser) {
        $userId = $currentUser['id'];
        $cartId = $data['cart_id'] ?? $data['id'] ?? null;

        if (!$cartId) {
            return ['success' => false, 'message' => 'Cart ID is required'];
        }

        $item = $this->cart->getById($cartId, $userId);
        if (!$item) {
            return ['success' => false, 'status' => 404, 'message' => 'Cart item not found'];
        }

        $this->cart->removeItem($cartId, $userId);

        return ['success' => true, 'message' => 'Item removed from cart'];
    }

    // POST /api/cart/clear
    public function clear($data, $currentUser) {
        $userId = $currentUser['id'];
        $this->cart->clearCart($userId);

        return ['success' => true, 'message' => 'Cart cleared successfully'];
    }
}

