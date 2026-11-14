<?php
// controller/UserController.php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../config/jwt.php';

class UserController
{
    private $user;

    public function __construct($pdo)
    {
        $this->user = new User($pdo);
    }

    // POST /api/register (always role "user")
    public function register($data)
    {
        $name     = trim($data['name'] ?? '');
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($name === '' || $email === '' || $password === '') {
            return ['success' => false, 'message' => 'Name, email and password are required'];
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email address'];
        }
        if (strlen($password) < 6) {
            return ['success' => false, 'message' => 'Password must be at least 6 characters'];
        }
        if ($this->user->getByEmail($email)) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $id = $this->user->create($name, $email, $password, 'user');

        return ['success' => true, 'status' => 201, 'message' => 'User registered', 'id' => $id];
    }

    // POST /api/login
    public function login($data)
    {
        $email    = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if ($email === '' || $password === '') {
            return ['success' => false, 'message' => 'Email and password are required'];
        }

        $user = $this->user->getByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            return ['success' => false, 'status' => 401, 'message' => 'Invalid email or password'];
        }

        $token = jwt_sign([
            'id'    => $user['id'],
            'email' => $user['email'],
            'role'  => $user['role']
        ], 86400);

        return [
            'success' => true,
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => [
                'id'    => $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role']
            ]
        ];
    }

    // POST /api/getall
    public function getUsers()
    {
        return ['success' => true, 'data' => $this->user->getAll()];
    }

    // POST /api/show   body: {"id":1}
    public function show($data)
    {
        $id = $data['id'] ?? null;
        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }

        $user = $this->user->getById($id);
        if (!$user) {
            return ['success' => false, 'status' => 404, 'message' => 'User not found'];
        }

        return ['success' => true, 'data' => $user];
    }

    // POST /api/update   body: {"id":1,"name":"...","email":"..."}
    public function update($data)
    {
        $id    = $data['id'] ?? null;
        $name  = trim($data['name'] ?? '');
        $email = trim($data['email'] ?? '');

        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }
        if (!$this->user->getById($id)) {
            return ['success' => false, 'status' => 404, 'message' => 'User not found'];
        }
        
        
        $existing = $this->user->getByEmail($email);
        if ($existing && $existing['id'] != $id) {
            return ['success' => false, 'message' => 'Email already exists'];
        }

        $this->user->update($id, $name, $email);

        return ['success' => true, 'message' => 'User updated'];
    }

    // POST /api/delete   body: {"id":1}
    public function delete($data, $currentUser)
    {
        $id = $data['id'] ?? null;
        if (!$id) {
            return ['success' => false, 'message' => 'ID is required'];
        }
        if ($id == $currentUser['id']) {
            return ['success' => false, 'message' => 'You cannot delete your own account'];
        }
        if (!$this->user->getById($id)) {
            return ['success' => false, 'status' => 404, 'message' => 'User not found'];
        }

        $this->user->delete($id);

        return ['success' => true, 'message' => 'User deleted'];
    }

    // GET / (Browser health check)
    public function ping()
    {
        return [
            'success' => true,
            'message' => 'API is running successfully!',
            'routes' => [
                'POST /api/register' => 'Public',
                'POST /api/login'    => 'Public',
                'POST /api/getall'   => 'Requires Token',
                'POST /api/show'     => 'Requires Token',
                'POST /api/update'   => 'Admin only',
                'POST /api/delete'   => 'Admin only'
            ]
        ];
    }
}