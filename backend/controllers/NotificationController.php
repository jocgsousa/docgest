<?php

require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class NotificationController {
    private $notificationModel;
    
    public function __construct() {
        $this->notificationModel = new Notification();
    }
    
    /**
     * Lista notificações do usuário logado
     */
    public function index() {
        try {
            $currentUser = JWT::requireAuth();
            
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $pageSize = isset($_GET['page_size']) ? min((int)$_GET['page_size'], 50) : 10;
            $offset = ($page - 1) * $pageSize;
            
            $notifications = $this->notificationModel->listByUser(
                $currentUser['user_id'], 
                $pageSize, 
                $offset
            );
            
            $unreadCount = $this->notificationModel->countUnreadByUser($currentUser['user_id']);
            
            Response::success([
                'notifications' => $notifications,
                'unread_count' => $unreadCount,
                'page' => $page,
                'page_size' => $pageSize
            ]);
            
        } catch (Exception $e) {
            Response::error('Erro ao buscar notificações: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Conta notificações não lidas
     */
    public function unreadCount() {
        try {
            $currentUser = JWT::requireAuth();
            
            $count = $this->notificationModel->countUnreadByUser($currentUser['user_id']);
            
            Response::success(['count' => $count]);
            
        } catch (Exception $e) {
            Response::error('Erro ao contar notificações: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Marca notificação como lida
     */
    public function markAsRead($id) {
        try {
            $currentUser = JWT::requireAuth();
            
            if (!$id) {
                Response::error('ID da notificação é obrigatório', 400);
                return;
            }
            
            $result = $this->notificationModel->markAsRead($id, $currentUser['user_id']);
            
            if ($result) {
                Response::success(['message' => 'Notificação marcada como lida']);
            } else {
                Response::error('Erro ao marcar notificação como lida', 500);
            }
            
        } catch (Exception $e) {
            Response::error('Erro ao marcar notificação: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Marca todas as notificações como lidas
     */
    public function markAllAsRead() {
        try {
            $currentUser = JWT::requireAuth();
            
            $result = $this->notificationModel->markAllAsRead($currentUser['user_id']);
            
            if ($result) {
                Response::success(['message' => 'Todas as notificações foram marcadas como lidas']);
            } else {
                Response::error('Erro ao marcar notificações como lidas', 500);
            }
            
        } catch (Exception $e) {
            Response::error('Erro ao marcar notificações: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Cria uma nova notificação (apenas para admins)
     */
    public function create() {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação dos dados
            $validator = new Validator($input);
            $validator->required('titulo');
            $validator->required('mensagem');
            $validator->required('tipo');
            
            if ($validator->hasErrors()) {
                Response::error('Dados inválidos', 400, $validator->getErrors());
                return;
            }
            
            // Validar tipo de notificação
            $allowedTypes = ['info', 'success', 'warning', 'error'];
            if (!in_array($input['tipo'], $allowedTypes)) {
                Response::error('Tipo de notificação inválido', 400);
                return;
            }
            
            $data = [
                'titulo' => $input['titulo'],
                'mensagem' => $input['mensagem'],
                'tipo' => $input['tipo'],
                'usuario_remetente_id' => $currentUser['user_id']
            ];
            
            // Determinar destinatários baseado no tipo de usuário e parâmetros
            if (isset($input['send_to_all']) && $input['send_to_all'] && $currentUser['tipo_usuario'] == 1) {
                // Super Admin pode enviar para todos
                $created = $this->notificationModel->createForAllUsers($data);
                Response::success([
                    'message' => 'Notificação enviada para todos os usuários',
                    'notifications_created' => $created
                ]);
                
            } elseif (isset($input['usuario_destinatario_id'])) {
                // Enviar para usuário específico
                $data['usuario_destinatario_id'] = $input['usuario_destinatario_id'];
                $data['empresa_id'] = $input['empresa_id'] ?? $currentUser['empresa_id'];
                
                // Verificar se admin da empresa pode enviar para este usuário
                if ($currentUser['tipo_usuario'] == 2) {
                    // Verificar se o usuário destinatário pertence à mesma empresa
                    $userModel = new User();
                    $targetUser = $userModel->findById($input['usuario_destinatario_id']);
                    
                    if (!$targetUser || $targetUser['empresa_id'] != $currentUser['empresa_id']) {
                        Response::error('Você só pode enviar notificações para usuários da sua empresa', 403);
                        return;
                    }
                }
                
                $id = $this->notificationModel->create($data);
                
                if ($id) {
                    Response::success([
                        'message' => 'Notificação criada com sucesso',
                        'id' => $id
                    ]);
                } else {
                    Response::error('Erro ao criar notificação', 500);
                }
                
            } elseif ($currentUser['tipo_usuario'] == 2) {
                // Admin da empresa envia para todos da empresa
                $created = $this->notificationModel->createForCompany($data, $currentUser['empresa_id']);
                Response::success([
                    'message' => 'Notificação enviada para todos os usuários da empresa',
                    'notifications_created' => $created
                ]);
                
            } else {
                Response::error('Destinatário da notificação não especificado', 400);
            }
            
        } catch (Exception $e) {
            Response::error('Erro ao criar notificação: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Busca notificação por ID
     */
    public function show($id) {
        try {
            $currentUser = JWT::requireAuth();
            
            if (!$id) {
                Response::error('ID da notificação é obrigatório', 400);
                return;
            }
            
            $notification = $this->notificationModel->findById($id);
            
            if (!$notification) {
                Response::error('Notificação não encontrada', 404);
                return;
            }
            
            // Verificar se o usuário pode ver esta notificação
            if ($notification['usuario_destinatario_id'] != $currentUser['user_id']) {
                Response::error('Acesso negado', 403);
                return;
            }
            
            // Marcar como lida automaticamente
            if (!$notification['lida']) {
                $this->notificationModel->markAsRead($id, $currentUser['user_id']);
                $notification['lida'] = 1;
                $notification['data_leitura'] = date('Y-m-d H:i:s');
            }
            
            Response::success($notification);
            
        } catch (Exception $e) {
            Response::error('Erro ao buscar notificação: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Remove notificação
     */
    public function delete($id) {
        try {
            $currentUser = JWT::requireAuth();
            
            if (!$id) {
                Response::error('ID da notificação é obrigatório', 400);
                return;
            }
            
            $result = $this->notificationModel->delete($id, $currentUser['user_id']);
            
            if ($result) {
                Response::success(['message' => 'Notificação removida com sucesso']);
            } else {
                Response::error('Erro ao remover notificação', 500);
            }
            
        } catch (Exception $e) {
            Response::error('Erro ao remover notificação: ' . $e->getMessage(), 500);
        }
    }
}