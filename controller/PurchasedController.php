<?php
// controller/PurchasedController.php

require_once __DIR__ . '/../models/purchased.php';
require_once __DIR__ . '/../models/product.php';
require_once __DIR__ . '/../models/cart.php';

class PurchasedController {
    private $purchased;
    private $product;
    private $cart;

    public function __construct($pdo) {
        $this->purchased = new Purchased($pdo);
        $this->product   = new Product($pdo);
        $this->cart      = new Cart($pdo);
    }

    // POST /api/purchased/getall (Admin only)
    public function getAll($data, $currentUser) {
        return [
            'success' => true,
            'data'    => $this->purchased->getAll()
        ];
    }

    // POST /api/purchased/my-orders (Logged-in user's own purchases)
    public function getMyPurchases($data, $currentUser) {
        $userId = $currentUser['id'];
        return [
            'success' => true,
            'data'    => $this->purchased->getByUserId($userId)
        ];
    }

    // POST /api/purchased/getbyuser body: {"user_id": 1}
    public function getByUserId($data, $currentUser) {
        $targetUserId = $data['user_id'] ?? null;

        if (!$targetUserId) {
            return ['success' => false, 'message' => 'user_id is required'];
        }

        // Only admin can view another person's purchases
        if ($currentUser['role'] !== 'admin' && $currentUser['id'] != $targetUserId) {
            return [
                'success' => false,
                'status'  => 403,
                'message' => 'Access denied: You can only view your own purchases'
            ];
        }

        return [
            'success' => true,
            'data'    => $this->purchased->getByUserId($targetUserId)
        ];
    }

    // POST /api/purchased/show body: {"id": 1}
    public function show($data, $currentUser) {
        $id = $data['id'] ?? null;
        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }

        $userIdCheck = ($currentUser['role'] === 'admin') ? null : $currentUser['id'];
        $purchase    = $this->purchased->getById($id, $userIdCheck);

        if (!$purchase) {
            return ['success' => false, 'status' => 404, 'message' => 'Purchase record not found'];
        }

        return ['success' => true, 'data' => $purchase];
    }

    // POST /api/purchased/buy body: {"product_id": 1, "quantity": 2, "payment_status": "Completed"}
    public function buyNow($data, $currentUser) {
        $userId        = $currentUser['id'];
        $productId     = $data['product_id'] ?? null;
        $quantity      = $data['quantity'] ?? 1;
        $paymentStatus = trim($data['payment_status'] ?? 'Completed');

        if (!$productId) {
            return ['success' => false, 'message' => 'Product ID is required'];
        }
        if (!is_numeric($quantity) || (int)$quantity <= 0) {
            return ['success' => false, 'message' => 'Quantity must be at least 1'];
        }
        $quantity = (int)$quantity;

        // Verify product
        $product = $this->product->getById($productId);
        if (!$product) {
            return ['success' => false, 'status' => 404, 'message' => 'Product not found'];
        }
        if (isset($product['status']) && $product['status'] !== 'Active') {
            return ['success' => false, 'message' => 'This product is currently unavailable'];
        }

        // Check stock
        if ($quantity > (int)$product['quantity']) {
            return [
                'success' => false,
                'message' => "Insufficient stock. Only {$product['quantity']} available in stock."
            ];
        }

        $price      = (float)$product['price'];
        $totalPrice = $price * $quantity;
        $orderId    = 'ORD-' . strtoupper(uniqid());

        $result = $this->purchased->create(
            $userId,
            $productId,
            $quantity,
            $price,
            $totalPrice,
            $orderId,
            $paymentStatus,
            'Pending'
        );

        return [
            'success'     => true,
            'status'      => 201,
            'message'     => 'Purchase successful',
            'order_id'    => $result['order_id'],
            'purchase_id' => $result['id'],
            'total_price' => $totalPrice
        ];
    }

    // POST /api/purchased/checkout (Buy all items from cart)
    public function checkoutCart($data, $currentUser) {
        $userId = $currentUser['id'];
        $items  = $this->cart->getByUserId($userId);

        if (empty($items)) {
            return ['success' => false, 'message' => 'Your cart is empty'];
        }

        // Pre-validate stock for all items
        foreach ($items as $item) {
            $product = $this->product->getById($item['product_id']);
            if (!$product || $product['status'] !== 'Active') {
                return [
                    'success' => false,
                    'message' => "Product '{$item['product_name']}' is no longer available"
                ];
            }
            if ((int)$item['quantity'] > (int)$product['quantity']) {
                return [
                    'success' => false,
                    'message' => "Insufficient stock for '{$item['product_name']}'. Only {$product['quantity']} available."
                ];
            }
        }

        // Process purchase
        $orderId       = 'ORD-' . strtoupper(uniqid());
        $paymentStatus = trim($data['payment_status'] ?? 'Completed');
        $purchasedIds  = [];
        $grandTotal    = 0;

        foreach ($items as $item) {
            $price      = (float)$item['price'];
            $quantity   = (int)$item['quantity'];
            $totalPrice = $price * $quantity;
            $grandTotal += $totalPrice;

            $res = $this->purchased->create(
                $userId,
                $item['product_id'],
                $quantity,
                $price,
                $totalPrice,
                $orderId,
                $paymentStatus,
                'Pending'
            );
            $purchasedIds[] = $res['id'];
        }

        // Clear cart after checkout
        $this->cart->clearCart($userId);

        return [
            'success'     => true,
            'status'      => 201,
            'message'     => 'Checkout completed successfully',
            'order_id'    => $orderId,
            'total_items' => count($items),
            'grand_total' => $grandTotal
        ];
    }

    // POST /api/purchased/cancel body: {"id": 1}
    public function cancel($data, $currentUser) {
        $id = $data['id'] ?? null;
        if (!$id) {
            return ['success' => false, 'message' => 'Purchase ID is required'];
        }

        $userIdCheck = ($currentUser['role'] === 'admin') ? null : $currentUser['id'];
        $purchase    = $this->purchased->getById($id, $userIdCheck);

        if (!$purchase) {
            return ['success' => false, 'status' => 404, 'message' => 'Purchase record not found'];
        }

        if ($purchase['status'] === 'Cancelled') {
            return ['success' => false, 'message' => 'This order is already cancelled'];
        }

        if ($purchase['status'] === 'Delivered') {
            return ['success' => false, 'message' => 'Delivered orders cannot be cancelled'];
        }

        $this->purchased->cancel($id, $userIdCheck);

        return ['success' => true, 'message' => 'Order cancelled successfully and stock restored'];
    }

    // POST /api/purchased/update-status (Admin only) body: {"id": 1, "status": "Delivered"}
    public function updateStatus($data, $currentUser) {
        $id     = $data['id'] ?? null;
        $status = trim($data['status'] ?? '');

        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }

        $allowedStatuses = ['Pending', 'Processing', 'Delivered', 'Cancelled'];
        if (!in_array($status, $allowedStatuses)) {
            return [
                'success' => false,
                'message' => 'Status must be one of: ' . implode(', ', $allowedStatuses)
            ];
        }

        $purchase = $this->purchased->getById($id);
        if (!$purchase) {
            return ['success' => false, 'status' => 404, 'message' => 'Purchase record not found'];
        }

        if ($status === 'Cancelled' && $purchase['status'] !== 'Cancelled') {
            $this->purchased->cancel($id);
        } else {
            $this->purchased->updateStatus($id, $status);
        }

        return ['success' => true, 'message' => 'Purchase status updated to ' . $status];
    }
}

