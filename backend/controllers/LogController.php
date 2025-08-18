<?php

require_once __DIR__ . '/../models/Log.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class LogController {
    private $logModel;
    
    public function __construct() {
        $this->logModel = new Log();
    }
    
    /**
     * Lista logs com filtros e paginação
     */
    public function index() {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissões (apenas Super Admin e Admin da Empresa)
            if ($user['tipo_usuario'] != 1 && $user['tipo_usuario'] != 2) {
                Response::forbidden('Acesso negado: Apenas administradores podem visualizar logs');
                return;
            }
            
            // Parâmetros de filtro
            $filters = [];
            if (isset($_GET['level'])) {
                $filters['level'] = $_GET['level'];
            }
            if (isset($_GET['user_id'])) {
                $filters['user_id'] = $_GET['user_id'];
            }
            if (isset($_GET['empresa_id'])) {
                $filters['empresa_id'] = $_GET['empresa_id'];
            }
            if (isset($_GET['date_from'])) {
                $filters['date_from'] = $_GET['date_from'];
            }
            if (isset($_GET['date_to'])) {
                $filters['date_to'] = $_GET['date_to'];
            }
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            // Se for Admin da Empresa, filtrar apenas logs da sua empresa
            if ($user['tipo_usuario'] == 2 && $user['empresa_id']) {
                $filters['empresa_id'] = $user['empresa_id'];
            }
            
            // Parâmetros de paginação
            $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
            $pageSize = isset($_GET['per_page']) ? min(100, max(1, intval($_GET['per_page']))) : 20;
            
            $result = $this->logModel->getAll($filters, $page, $pageSize);
            
            Response::success($result, 'Logs recuperados com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Cria um novo log
     */
    public function create() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Dados inválidos', 400);
                return;
            }
            
            $validator = new Validator($input);
            $validator->required('level', 'Nível é obrigatório');
            $validator->required('message', 'Mensagem é obrigatória');
            $validator->in('level', ['debug', 'info', 'warning', 'error'], 'Nível deve ser: debug, info, warning ou error');

            
            $logData = [
                'level' => $input['level'],
                'message' => $input['message'],
                'context' => $input['context'] ?? null,
                'user_id' => $input['user_id'] ?? null,
                'empresa_id' => $input['empresa_id'] ?? null,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ];
            
            $logId = $this->logModel->create($logData);
            
            Response::created(['id' => $logId], 'Log criado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Limpa todos os logs (apenas Super Admin)
     */
    public function clear() {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissões (apenas Super Admin)
            if ($user['tipo_usuario'] != 1) {
                Response::forbidden('Acesso negado: Apenas Super Admin pode limpar logs');
                return;
            }
            
            $this->logModel->clearAll();
            
            Response::success(null, 'Todos os logs foram removidos com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Obtém estatísticas dos logs
     */
    public function stats() {
        try {
            // Verificar autenticação
            $user = JWT::validateToken();
            
            // Verificar permissões (apenas Super Admin e Admin da Empresa)
            if ($user['tipo_usuario'] != 1 && $user['tipo_usuario'] != 2) {
                Response::forbidden('Acesso negado: Apenas administradores podem visualizar estatísticas');
                return;
            }
            
            // Se for Admin da Empresa, filtrar apenas logs da sua empresa
            $empresaId = null;
            if ($user['tipo_usuario'] == 2 && $user['empresa_id']) {
                $empresaId = $user['empresa_id'];
            }
            
            $stats = $this->logModel->getStats($empresaId);
            
            Response::success($stats, 'Estatísticas recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
}

?>