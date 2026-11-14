<?php
// config/jwt.php

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Change this to a long random string and keep it private
define('JWT_SECRET', 'change_this_to_a_long_random_secret_key_123456789');

// Like jwt.sign(payload, secret, { expiresIn })
function jwt_sign(array $payload, $expiresInSeconds = 86400)
{
    $payload['iat'] = time();
    $payload['exp'] = time() + $expiresInSeconds;

    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

// Like jwt.verify(token, secret). Returns payload array, or null if invalid/expired
function jwt_verify($token)
{
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        return null;   // bad signature, expired, malformed, etc.
    }
}