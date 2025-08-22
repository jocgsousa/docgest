<?php

class JWT {
    
    /**
     * Gera um token JWT
     */
    public static function encode($payload, $secret = null) {
        $secret = $secret ?: JWT_SECRET;
        
        $header = json_encode(['typ' => 'JWT', 'alg' => JWT_ALGORITHM]);
        $payload = json_encode($payload);
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64Header . "." . $base64Payload . "." . $base64Signature;
    }
    
    /**
     * Decodifica um token JWT
     */
    public static function decode($jwt, $secret = null) {
        $secret = $secret ?: JWT_SECRET;
        
        $tokenParts = explode('.', $jwt);
        
        if (count($tokenParts) !== 3) {
            throw new Exception('Token inválido');
        }
        
        $header = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[0]));
        $payload = base64_decode(str_replace(['-', '_'], ['+', '/'], $tokenParts[1]));
        $signatureProvided = $tokenParts[2];
        
        $base64Header = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64Payload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        
        $signature = hash_hmac('sha256', $base64Header . "." . $base64Payload, $secret, true);
        $base64Signature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        if (!hash_equals($base64Signature, $signatureProvided)) {
            throw new Exception('Assinatura do token inválida');
        }
        
        $decodedPayload = json_decode($payload, true);
        
        // Verificar expiração
        if (isset($decodedPayload['exp']) && $decodedPayload['exp'] < time()) {
            throw new Exception('Token expirado');
        }
        
        return $decodedPayload;
    }
    
    /**
     * Gera um token para um usuário
     */
    public static function generateUserToken($user) {
        $payload = [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'tipo_usuario' => $user['tipo_usuario'],
            'empresa_id' => $user['empresa_id'],
            'filial_id' => $user['filial_id'],
            'iat' => time(),
            'exp' => time() + JWT_EXPIRATION
        ];
        
        return self::encode($payload);
    }
    
    /**
     * Extrai o token do header Authorization
     */
    public static function getBearerToken() {
        $authHeader = null;
        
        // Tentar diferentes formas de obter o header Authorization
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
            $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? null;
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        } elseif (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
        }
        
        if ($authHeader && preg_match('/Bearer\s+(\S+)/', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Valida e retorna os dados do usuário do token
     */
    public static function validateToken() {
        try {
            $token = self::getBearerToken();
            
            if (!$token) {
                throw new Exception('Token não fornecido');
            }
            
            $payload = self::decode($token);
            
            return $payload;
            
        } catch (Exception $e) {
            throw new Exception('Token inválido: ' . $e->getMessage());
        }
    }
    
    /**
     * Middleware para verificar autenticação
     */
    public static function requireAuth() {
        try {
            return self::validateToken();
        } catch (Exception $e) {
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => 'Não autorizado: ' . $e->getMessage()
            ]);
            exit();
        }
    }
    
    /**
     * Middleware para verificar se é admin
     */
    public static function requireAdmin() {
        $user = self::requireAuth();
        
        if ($user['tipo_usuario'] != 1) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores podem acessar este recurso.'
            ]);
            exit();
        }
        
        return $user;
    }
    
    /**
     * Middleware para verificar se é admin ou admin da empresa
     */
    public static function requireAdminOrCompanyAdmin() {
        $user = self::requireAuth();
        
        if (!in_array($user['tipo_usuario'], [1, 2])) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado. Apenas administradores podem acessar este recurso.'
            ]);
            exit();
        }
        
        return $user;
    }
    
    /**
     * Middleware para verificar se é super admin (tipo 1)
     */
    public static function requireSuperAdmin() {
        $user = self::requireAuth();
        
        if ($user['tipo_usuario'] != 1) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Acesso negado. Apenas super administradores podem acessar este recurso.'
            ]);
            exit();
        }
        
        return $user;
    }
}

?>