<?php

class Response {
    
    /**
     * Envia uma resposta de sucesso
     */
    public static function success($data = null, $message = 'Sucesso', $code = 200) {
        http_response_code($code);
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Envia uma resposta de erro
     */
    public static function error($message = 'Erro interno do servidor', $code = 500, $details = null) {
        http_response_code($code);
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($details !== null && APP_ENV === 'development') {
            $response['details'] = $details;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Envia uma resposta de validação
     */
    public static function validation($errors, $message = 'Dados inválidos') {
        http_response_code(422);
        
        echo json_encode([
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    /**
     * Envia uma resposta de não autorizado
     */
    public static function unauthorized($message = 'Não autorizado') {
        self::error($message, 401);
    }
    
    /**
     * Envia uma resposta de acesso negado
     */
    public static function forbidden($message = 'Acesso negado') {
        self::error($message, 403);
    }
    
    /**
     * Envia uma resposta de não encontrado
     */
    public static function notFound($message = 'Recurso não encontrado') {
        self::error($message, 404);
    }
    
    /**
     * Envia uma resposta paginada
     */
    public static function paginated($data, $total, $page, $pageSize, $message = 'Dados recuperados com sucesso') {
        $totalPages = ceil($total / $pageSize);
        
        self::success([
            'items' => $data,
            'pagination' => [
                'current_page' => (int)$page,
                'per_page' => (int)$pageSize,
                'total' => (int)$total,
                'total_pages' => (int)$totalPages,
                'has_next' => $page < $totalPages,
                'has_prev' => $page > 1
            ]
        ], $message);
    }
    
    /**
     * Envia uma resposta de criação
     */
    public static function created($data = null, $message = 'Recurso criado com sucesso') {
        self::success($data, $message, 201);
    }
    
    /**
     * Envia uma resposta de atualização
     */
    public static function updated($data = null, $message = 'Recurso atualizado com sucesso') {
        self::success($data, $message, 200);
    }
    
    /**
     * Envia uma resposta de exclusão
     */
    public static function deleted($message = 'Recurso excluído com sucesso') {
        self::success(null, $message, 200);
    }
    
    /**
     * Envia uma resposta sem conteúdo
     */
    public static function noContent() {
        http_response_code(204);
        exit();
    }
    
    /**
     * Trata exceções e envia resposta apropriada
     */
    public static function handleException($e) {
        // Log do erro
        error_log('API Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' on line ' . $e->getLine());
        
        // Determinar código de status baseado no tipo de exceção
        $code = 500;
        $message = 'Erro interno do servidor';
        
        if ($e instanceof InvalidArgumentException) {
            $code = 400;
            $message = $e->getMessage();
        } elseif ($e instanceof UnauthorizedException) {
            $code = 401;
            $message = $e->getMessage();
        } elseif ($e instanceof ForbiddenException) {
            $code = 403;
            $message = $e->getMessage();
        } elseif ($e instanceof NotFoundException) {
            $code = 404;
            $message = $e->getMessage();
        } elseif (APP_ENV === 'development') {
            $message = $e->getMessage();
        }
        
        self::error($message, $code, APP_ENV === 'development' ? [
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ] : null);
    }
}

// Exceções customizadas
class UnauthorizedException extends Exception {}
class ForbiddenException extends Exception {}
class NotFoundException extends Exception {}

?>