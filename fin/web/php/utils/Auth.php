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
     * Get token from Authorization header
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (empty($authHeader)) {
            return null;
        }

        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }

        return null;
    }

    /**
     * Get authenticated user
     */
    public static function user() {
        $token = self::getTokenFromHeader();
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
     * Get authenticated user ID
     */
    public static function userId() {
        $user = self::user();
        return $user ? $user['id'] : null;
    }

    /**
     * Require authentication
     */
    public static function requireAuth() {
        $user = self::user();
        if (!$user) {
            Response::error('غير مصرح', [], 401);
            exit;
        }
        return $user;
    }

    /**
     * Require admin role
     */
    public static function requireAdmin() {
        $user = self::requireAuth();
        if ($user['role'] !== 'admin') {
            Response::error('غير مصرح - صلاحيات المدير مطلوبة', [], 403);
            exit;
        }
        return $user;
    }

    /**
     * Require vendor or admin role
     */
    public static function requireVendor() {
        $user = self::requireAuth();
        if (!in_array($user['role'], ['admin', 'vendor'])) {
            Response::error('غير مصرح - صلاحيات البائع مطلوبة', [], 403);
            exit;
        }
        return $user;
    }

    /**
     * Check if user is authenticated
     */
    public static function check() {
        return self::user() !== null;
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
     * Generate random token
     */
    public static function generateRandomToken($length = 64) {
        return bin2hex(random_bytes($length / 2));
    }

    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
