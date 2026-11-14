<?php
require_once __DIR__ . '/../controller/UserController.php';
require_once __DIR__ . '/../controller/CategoryController.php';   // add
require_once __DIR__ . '/../controller/ProductController.php';
require_once __DIR__ . '/../controller/CartController.php';
require_once __DIR__ . '/../controller/PurchasedController.php';

return [
    'POST' => [
        // users
        '/api/register' => ['UserController', 'register', 'public'],
        '/api/login'    => ['UserController', 'login',    'public'],
        '/api/getall'   => ['UserController', 'getUsers', 'auth'],
        '/api/show'     => ['UserController', 'show',     'auth'],
        '/api/update'   => ['UserController', 'update',   'admin'],
        '/api/delete'   => ['UserController', 'delete',   'admin'],

        // category
        '/api/category/getall' => ['CategoryController', 'getAll', 'auth'],
        '/api/category/show'   => ['CategoryController', 'show',   'auth'],
        '/api/category/create' => ['CategoryController', 'create', 'admin'],
        '/api/category/update' => ['CategoryController', 'update', 'admin'],
        '/api/category/delete' => ['CategoryController', 'delete', 'admin'],

        // product
        '/api/product/getall'      => ['ProductController', 'getAll',      'auth'],
        '/api/product/show'        => ['ProductController', 'show',        'auth'],
        '/api/product/by-category' => ['ProductController', 'getByCategory','auth'],
        '/api/product/create'      => ['ProductController', 'create',      'admin'],
        '/api/product/update'      => ['ProductController', 'update',      'admin'],
        '/api/product/delete'      => ['ProductController', 'delete',      'admin'],

        // cart
        '/api/cart/get'       => ['CartController', 'getCart',     'auth'],
        '/api/cart/getbyuser' => ['CartController', 'getByUserId', 'auth'],
        '/api/cart/add'       => ['CartController', 'add',         'auth'],
        '/api/cart/update'    => ['CartController', 'update',  'auth'],
        '/api/cart/remove'    => ['CartController', 'remove',  'auth'],
        '/api/cart/clear'     => ['CartController', 'clear',   'auth'],

        // purchased
        '/api/purchased/getall'    => ['PurchasedController', 'getAll',         'admin'],
        '/api/purchased/my-orders'    => ['PurchasedController', 'getMyPurchases',  'auth'],
        '/api/purchased/getbyuser'    => ['PurchasedController', 'getByUserId',     'auth'],
        '/api/purchased/show'         => ['PurchasedController', 'show',           'auth'],
        '/api/purchased/buy'          => ['PurchasedController', 'buyNow',         'auth'],
        '/api/purchased/checkout'     => ['PurchasedController', 'checkoutCart',   'auth'],
        '/api/purchased/cancel'       => ['PurchasedController', 'cancel',         'auth'],
        '/api/purchased/update-status'=> ['PurchasedController', 'updateStatus',   'admin'],
    ],
    'GET' => [],
];