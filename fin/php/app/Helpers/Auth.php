<?php

namespace App\Helpers;

use App\Core\Config;
use App\Core\ApiResponse;
use App\Models\User;

/**
 * JWT Authentication Helper
 * مساعد المصادقة JWT
 */
class Auth
{
    /**
     * Generate JWT Token
     */
    public static function generateToken(int $userId, array $additionalData = []): string
    {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        
        $payload = json_encode(array_merge([
            'user_id' => $userId,
            'iat' => time(),
            'exp' => time() + Config::get('app.jwt.expiry', 604800)
        ], $additionalData));
        
        $base64Header = self::base64UrlEncode($header);
        $base64Payload = self::base64UrlEncode($payload);
        
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, Config::get('app.jwt.secret'), true);
        $base64Signature = self::base64UrlEncode($signature);
        
        return $base64Header . '.' . $base64Payload . '.' . $base64Signature;
    }
    
    /**
     * Verify JWT Token
     */
    public static function verifyToken(string $token): ?array
    {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        list($base64Header, $base64Payload, $base64Signature) = $parts;
        
        $signature = hash_hmac('sha256', $base64Header . '.' . $base64Payload, Config::get('app.jwt.secret'), true);
        $expectedSignature = self::base64UrlEncode($signature);
        
        if (!hash_equals($expectedSignature, $base64Signature)) {
            return null;
        }
        
        $payload = json_decode(self::base64UrlDecode($base64Payload), true);
        
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) {
            return null;
        }
        
        return $payload;
    }
    
    /**
     * Get current user from request
     */
    public static function getUser(): ?array
    {
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
    public static function requireAuth(): array
    {
        $user = self::getUser();
        
        if (!$user) {
            ApiResponse::unauthorized('يرجى تسجيل الدخول');
        }
        
        return $user;
    }
    
    /**
     * Require admin role
     */
    public static function requireAdmin(): array
    {
        $user = self::requireAuth();
        
        if ($user['role'] !== 'admin') {
            ApiResponse::forbidden('غير مصرح لك بالوصول');
        }
        
        return $user;
    }
    
    /**
     * Require vendor role
     */
    public static function requireVendor(): array
    {
        $user = self::requireAuth();
        
        if (!in_array($user['role'], ['admin', 'vendor'])) {
            ApiResponse::forbidden('غير مصرح لك بالوصول');
        }
        
        return $user;
    }
    
    /**
     * Get Bearer Token from header
     */
    private static function getBearerToken(): ?string
    {
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
    private static function getAuthorizationHeader(): ?string
    {
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
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * Base64 URL Decode
     */
    private static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}
