<?php
/**
 * إعدادات Firebase للتحقق من التوكن
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use GuzzleHttp\Client;

class FirebaseAuth {
    private $projectId = 'YOUR_FIREBASE_PROJECT_ID';
    private $publicKeys = [];
    private $keysExpiresAt = 0;
    
    /**
     * جلب المفاتيح العامة من Google
     */
    private function fetchPublicKeys() {
        if (time() < $this->keysExpiresAt && !empty($this->publicKeys)) {
            return $this->publicKeys;
        }
        
        $client = new Client();
        $response = $client->get('https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com');
        
        // تحديد وقت انتهاء الصلاحية من الهيدر
        $cacheControl = $response->getHeader('Cache-Control')[0] ?? '';
        if (preg_match('/max-age=(\d+)/', $cacheControl, $matches)) {
            $this->keysExpiresAt = time() + (int)$matches[1];
        }
        
        $this->publicKeys = json_decode($response->getBody(), true);
        return $this->publicKeys;
    }
    
    /**
     * التحقق من صحة توكن Firebase
     */
    public function verifyIdToken($idToken) {
        try {
            $keys = $this->fetchPublicKeys();
            
            // استخراج الـ kid من التوكن
            $tokenParts = explode('.', $idToken);
            $header = json_decode(base64_decode($tokenParts[0]), true);
            $kid = $header['kid'] ?? null;
            
            if (!$kid || !isset($keys[$kid])) {
                throw new Exception('Invalid token key');
            }
            
            $publicKey = $keys[$kid];
            
            // فك تشفير التوكن
            $decoded = JWT::decode($idToken, new Key($publicKey, 'RS256'));
            
            // التحقق من الـ audience و issuer
            if ($decoded->aud !== $this->projectId) {
                throw new Exception('Invalid audience');
            }
            
            $expectedIssuer = 'https://securetoken.google.com/' . $this->projectId;
            if ($decoded->iss !== $expectedIssuer) {
                throw new Exception('Invalid issuer');
            }
            
            // التحقق من انتهاء الصلاحية
            if ($decoded->exp < time()) {
                throw new Exception('Token expired');
            }
            
            return [
                'success' => true,
                'user_id' => $decoded->sub,
                'email' => $decoded->email ?? null,
                'email_verified' => $decoded->email_verified ?? false,
                'name' => $decoded->name ?? null
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Invalid token: ' . $e->getMessage()
            ];
        }
    }
}
?>
