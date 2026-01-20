<?php
/**
 * JWT Authentication Helper
 * مساعد المصادقة JWT
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

class Auth {
    /**
     * Generate JWT Token
     */
    public static function generateToken($userId, $additionalData = []) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode(array_merge([
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + JWT_EXPIRY
        ], $additionalData));

        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payload);

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, JWT_SECRET, true);
        $base64Signature = self::base64UrlEncode($signature);

        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }

    /**
     * Verify JWT Token
     */
    public static function verifyToken($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return false;
        }

        list($base64Header, $base64Payload, $base64Signature) = $parts;

        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, JWT_SECRET, true);
        $expectedSignature = self::base64UrlEncode($signature);

        if (!hash_equals($expectedSignature, $base64Signature)) {
            return false;
        }

        $payload = json_decode(self::base64UrlDecode($base64Payload), true);

        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return false;
        }

        return $payload;
    }

    /**
     * Get current user from request
     */
    public static function getUser() {
        $token = self::getBearerToken();
        if (!$token) {
            return null;
        }

        $payload = self::verifyToken($token);
        if (!$payload) {
            return null;
        }

        $userModel = new User();
        return $userModel->findById($payload['user_id']);
    }

    /**
     * Require authentication
     */
    public static function requireAuth() {
        $user = self::getUser();
        if (!$user) {
            require_once __DIR__ . '/Response.php';
            Response::unauthorized('يرجى تسجيل الدخول');
        }
        return $user;
    }

    /**
     * Require admin role
     */
    public static function requireAdmin() {
        $user = self::requireAuth();
        if ($user['role'] !== 'admin') {
            require_once __DIR__ . '/Response.php';
            Response::forbidden('غير مصرح لك بالوصول');
        }
        return $user;
    }

    /**
     * Require vendor role
     */
    public static function requireVendor() {
        $user = self::requireAuth();
        if (!in_array($user['role'], ['admin', 'vendor'])) {
            require_once __DIR__ . '/Response.php';
            Response::forbidden('غير مصرح لك بالوصول');
        }
        return $user;
    }

    /**
     * Get Bearer Token from header
     */
    private static function getBearerToken() {
        $headers = self::getAuthorizationHeader();
        if (!empty($headers)) {
            if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
                return $matches[1];
            }
        }
        return null;
    }

    /**
     * Get Authorization Header
     */
    private static function getAuthorizationHeader() {
        $headers = null;
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(
                array_map('ucwords', array_keys($requestHeaders)),
                array_values($requestHeaders)
            );
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        return $headers;
    }

    /**
     * Base64 URL Encode
     */
    private static function base64UrlEncode($data) {
        return str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));
    }

    /**
     * Base64 URL Decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    /**
     * Hash password
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }

    /**
     * Generate random token (for password reset, etc.)
     */
    public static function generateRandomToken($length = 64) {
        return bin2hex(random_bytes($length / 2));
    }
}
