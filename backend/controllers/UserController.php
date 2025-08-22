<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class UserController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Lista usuários
     */
    public function index() {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            // Parâmetros de consulta
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $pageSize = isset($_GET['page_size']) ? min((int)$_GET['page_size'], MAX_PAGE_SIZE) : DEFAULT_PAGE_SIZE;
            
            $filters = [];
            
            // Se for admin da empresa, filtrar apenas usuários da sua empresa
            if ($currentUser['tipo_usuario'] == 2) {
                $filters['empresa_id'] = $currentUser['empresa_id'];
            }
            
            // Excluir o usuário atual da listagem
            $filters['exclude_user_id'] = $currentUser['user_id'];
            
            // Filtros opcionais
            if (isset($_GET['empresa_id']) && $currentUser['tipo_usuario'] == 1) {
                $filters['empresa_id'] = $_GET['empresa_id'];
            }
            
            if (isset($_GET['filial_id'])) {
                $filters['filial_id'] = $_GET['filial_id'];
            }
            
            if (isset($_GET['tipo_usuario'])) {
                $filters['tipo_usuario'] = $_GET['tipo_usuario'];
            }
            
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            // Super admin e admin de empresa podem ver usuários inativos
            if ($currentUser['tipo_usuario'] == 1 || $currentUser['tipo_usuario'] == 2) {
                if (isset($_GET['incluir_inativos']) && $_GET['incluir_inativos'] == 'true') {
                    $filters['incluir_inativos'] = true;
                }
                
                if (isset($_GET['status'])) {
                    $filters['status'] = $_GET['status'];
                }
            }
            
            $result = $this->userModel->list($filters, $page, $pageSize);
            
            Response::paginated(
                $result['data'],
                $result['total'],
                $page,
                $pageSize,
                'Usuários recuperados com sucesso'
            );
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Busca usuário por ID
     */
    public function show($id) {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            $user = $this->userModel->findByIdIncludingInactive($id);
            
            if (!$user) {
                Response::notFound('Usuário não encontrado');
            }
            
            // Verificar permissões
            if ($currentUser['tipo_usuario'] == 2 && $user['empresa_id'] != $currentUser['empresa_id']) {
                Response::forbidden('Você não tem permissão para visualizar este usuário');
            }
            
            // Remover senha
            unset($user['senha']);
            
            Response::success($user, 'Usuário recuperado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Cria novo usuário
     */
    public function store() {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação
            $validator = Validator::make($input)
                ->required('nome', 'Nome é obrigatório')
                ->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
                ->max('nome', 100, 'Nome deve ter no máximo 100 caracteres')
                ->required('email', 'Email é obrigatório')
                ->email('email', 'Email deve ser válido')
                ->unique('email', 'usuarios', 'email', null, 'Este email já está em uso')
                ->required('senha', 'Senha é obrigatória')
                ->min('senha', 6, 'Senha deve ter pelo menos 6 caracteres')
                ->required('cpf', 'CPF é obrigatório')
                ->cpf('cpf', 'CPF deve ser válido')
                ->unique('cpf', 'usuarios', 'cpf', null, 'Este CPF já está em uso')
                ->required('telefone', 'Telefone é obrigatório')
                ->required('profissao_id', 'Profissão é obrigatória')
                ->exists('profissao_id', 'profissoes', 'id', 'Profissão não encontrada')
                ->required('tipo_usuario', 'Tipo de usuário é obrigatório')
                ->in('tipo_usuario', [1, 2, 3], 'Tipo de usuário inválido');
            
            // Validar empresa_id se não for super admin
            if ($input['tipo_usuario'] != 1) {
                $validator->required('empresa_id', 'Empresa é obrigatória')
                         ->exists('empresa_id', 'empresas', 'id', 'Empresa não encontrada');
            }
            
            // Validar filial_id se for assinante
            if ($input['tipo_usuario'] == 3) {
                $validator->required('filial_id', 'Filial é obrigatória')
                         ->exists('filial_id', 'filiais', 'id', 'Filial não encontrada');
            }
            

            
            // Verificar permissões
            // Super admin pode criar qualquer tipo
            if ($currentUser['tipo_usuario'] == 1) {
                // OK
            }
            // Admin da empresa pode criar admin da empresa e assinantes da sua empresa
            elseif ($currentUser['tipo_usuario'] == 2) {
                if ($input['tipo_usuario'] == 1) {
                    Response::forbidden('Você não pode criar super administradores');
                }
                if (isset($input['empresa_id']) && $input['empresa_id'] != $currentUser['empresa_id']) {
                    Response::forbidden('Você só pode criar usuários da sua empresa');
                }
                // Forçar empresa do admin logado
                $input['empresa_id'] = $currentUser['empresa_id'];
                
                // Verificar se o plano da empresa está vencido
                require_once __DIR__ . '/../models/Company.php';
                $companyModel = new Company();
                if ($companyModel->isPlanExpired($currentUser['empresa_id'])) {
                    Response::forbidden('Não é possível criar novos usuários. O plano da empresa está vencido.');
                }
            }
            
            // Criar usuário
            $userData = [
                'nome' => $input['nome'],
                'email' => $input['email'],
                'senha' => $input['senha'],
                'cpf' => preg_replace('/[^0-9]/', '', $input['cpf']),
                'telefone' => $input['telefone'],
                'profissao_id' => $input['profissao_id'],
                'tipo_usuario' => $input['tipo_usuario'],
                'empresa_id' => $input['empresa_id'] ?? null,
                'filial_id' => $input['filial_id'] ?? null
            ];
            
            $user = $this->userModel->create($userData);
            
            if (!$user) {
                Response::error('Erro ao criar usuário', 500);
            }
            
            // Remover senha dos dados retornados
            unset($user['senha']);
            
            Response::created($user, 'Usuário criado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Atualiza usuário
     */
    public function update($id) {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Buscar usuário
            $user = $this->userModel->findByIdIncludingInactive($id);
            
            if (!$user) {
                Response::notFound('Usuário não encontrado');
            }
            
            // Verificar permissões
            if ($currentUser['tipo_usuario'] == 2 && $user['empresa_id'] != $currentUser['empresa_id']) {
                Response::forbidden('Você não tem permissão para editar este usuário');
            }
            
            // Validação
            $validator = Validator::make($input);
            
            if (isset($input['nome'])) {
                $validator->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
                         ->max('nome', 100, 'Nome deve ter no máximo 100 caracteres');
            }
            
            if (isset($input['email'])) {
                $validator->email('email', 'Email deve ser válido')
                         ->unique('email', 'usuarios', 'email', $id, 'Este email já está em uso');
            }
            
            if (isset($input['cpf'])) {
                $validator->cpf('cpf', 'CPF deve ser válido')
                         ->unique('cpf', 'usuarios', 'cpf', $id, 'Este CPF já está em uso');
            }
            
            if (isset($input['senha'])) {
                $validator->min('senha', 6, 'Senha deve ter pelo menos 6 caracteres');
            }
            
            if (isset($input['tipo_usuario'])) {
                $validator->in('tipo_usuario', [1, 2, 3], 'Tipo de usuário inválido');
                
                // Admin da empresa não pode alterar tipo de usuário para super admin
                if ($currentUser['tipo_usuario'] == 2 && $input['tipo_usuario'] == 1) {
                    Response::forbidden('Você não pode criar super administradores');
                }
            }
            
            if (isset($input['empresa_id'])) {
                $validator->exists('empresa_id', 'empresas', 'id', 'Empresa não encontrada');
                
                // Admin da empresa não pode alterar empresa
                if ($currentUser['tipo_usuario'] == 2 && $input['empresa_id'] != $currentUser['empresa_id']) {
                    Response::forbidden('Você não pode alterar a empresa do usuário');
                }
            }
            
            if (isset($input['filial_id'])) {
                $validator->exists('filial_id', 'filiais', 'id', 'Filial não encontrada');
            }
            

            
            // Preparar dados para atualização
            $updateData = [];
            $allowedFields = ['nome', 'email', 'cpf', 'telefone', 'tipo_usuario', 'empresa_id', 'filial_id', 'senha'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if ($field === 'cpf') {
                        $updateData[$field] = preg_replace('/[^0-9]/', '', $input[$field]);
                    } else {
                        $updateData[$field] = $input[$field];
                    }
                }
            }
            
            $updatedUser = $this->userModel->update($id, $updateData);
            
            if (!$updatedUser) {
                Response::error('Erro ao atualizar usuário', 500);
            }
            
            // Remover senha
            unset($updatedUser['senha']);
            
            Response::updated($updatedUser, 'Usuário atualizado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Remove usuário (soft delete)
     */
    public function destroy($id) {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            // Buscar usuário
            $user = $this->userModel->findById($id);
            
            if (!$user) {
                Response::notFound('Usuário não encontrado');
            }
            
            // Verificar permissões
            if ($currentUser['tipo_usuario'] == 2 && $user['empresa_id'] != $currentUser['empresa_id']) {
                Response::forbidden('Você não tem permissão para excluir este usuário');
            }
            
            // Não permitir que o usuário exclua a si mesmo
            if ($user['id'] == $currentUser['user_id']) {
                Response::error('Você não pode excluir sua própria conta', 400);
            }
            
            $deleted = $this->userModel->delete($id);
            
            if (!$deleted) {
                Response::error('Erro ao excluir usuário', 500);
            }
            
            Response::deleted('Usuário excluído com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Lista usuários por empresa
     */
    public function byEmpresa($empresaId) {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            // Verificar permissões
            if ($currentUser['tipo_usuario'] == 2 && $empresaId != $currentUser['empresa_id']) {
                Response::forbidden('Você não tem permissão para visualizar usuários desta empresa');
            }
            
            $users = $this->userModel->findByEmpresa($empresaId);
            
            // Remover senhas
            foreach ($users as &$user) {
                unset($user['senha']);
            }
            
            Response::success($users, 'Usuários da empresa recuperados com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Lista usuários por filial
     */
    public function byFilial($filialId) {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            $users = $this->userModel->findByFilial($filialId);
            
            // Verificar se a filial pertence à empresa do usuário (se for admin da empresa)
            if ($currentUser['tipo_usuario'] == 2 && !empty($users)) {
                // Buscar empresa da filial através do primeiro usuário
                $firstUser = $this->userModel->findById($users[0]['id']);
                if ($firstUser['empresa_id'] != $currentUser['empresa_id']) {
                    Response::forbidden('Você não tem permissão para visualizar usuários desta filial');
                }
            }
            
            // Remover senhas
            foreach ($users as &$user) {
                unset($user['senha']);
            }
            
            Response::success($users, 'Usuários da filial recuperados com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Estatísticas de usuários
     */
    public function stats() {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            $empresaId = null;
            if ($currentUser['tipo_usuario'] == 2) {
                $empresaId = $currentUser['empresa_id'];
            }
            
            $stats = $this->userModel->countByType($empresaId);
            
            $formattedStats = [
                'super_admin' => 0,
                'admin_empresa' => 0,
                'assinante' => 0,
                'total' => 0
            ];
            
            foreach ($stats as $stat) {
                switch ($stat['tipo_usuario']) {
                    case 1:
                        $formattedStats['super_admin'] = (int)$stat['total'];
                        break;
                    case 2:
                        $formattedStats['admin_empresa'] = (int)$stat['total'];
                        break;
                    case 3:
                        $formattedStats['assinante'] = (int)$stat['total'];
                        break;
                }
                $formattedStats['total'] += (int)$stat['total'];
            }
            
            Response::success($formattedStats, 'Estatísticas de usuários recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Busca usuários por empresa e filial para seleção de assinantes
     */
    public function getByCompanyAndBranch() {
        try {
            $authUser = JWT::requireAuth();
            
            $empresa_id = $_GET['empresa_id'] ?? null;
            $filial_id = $_GET['filial_id'] ?? null;
            
            if (!$empresa_id) {
                Response::validation(['empresa_id' => ['Empresa é obrigatória']]);
                return;
            }
            
            // Verificar permissões
            if ($authUser['tipo_usuario'] == 2 && $authUser['empresa_id'] != $empresa_id) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            if ($authUser['tipo_usuario'] == 3) {
                if ($authUser['empresa_id'] != $empresa_id || 
                    ($filial_id && $authUser['filial_id'] != $filial_id)) {
                    Response::forbidden('Acesso negado');
                    return;
                }
            }
            
            $filters = [
                'empresa_id' => $empresa_id,
                'tipo_usuario' => 3 // Apenas assinantes
            ];
            
            if ($filial_id) {
                $filters['filial_id'] = $filial_id;
            }
            
            $result = $this->userModel->list($filters, 1, 1000); // Buscar todos os usuários
            
            // Formatar para select
            $users = array_map(function($user) {
                return [
                    'id' => $user['id'],
                    'nome' => $user['nome'],
                    'email' => $user['email'],
                    'filial_nome' => $user['filial_nome'] ?? 'Sem filial'
                ];
            }, $result['data']);
            
            Response::success($users);
        } catch (Exception $e) {
            Response::error('Erro ao buscar usuários: ' . $e->getMessage());
        }
    }
    
    /**
     * Desativa um usuário (soft delete)
     */
    public function deactivate($id) {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            $user = $this->userModel->findById($id);
            
            if (!$user) {
                Response::notFound('Usuário não encontrado');
            }
            
            // Verificar permissões
            if ($currentUser['tipo_usuario'] == 2 && $user['empresa_id'] != $currentUser['empresa_id']) {
                Response::forbidden('Você não tem permissão para desativar este usuário');
            }
            
            // Não permitir desativar o próprio usuário
            if ($user['id'] == $currentUser['user_id']) {
                Response::error('Você não pode desativar seu próprio usuário', 400);
            }
            
            error_log("Tentando desativar usuário ID: $id");
            $success = $this->userModel->deactivate($id);
            error_log("Resultado da desativação: " . ($success ? 'true' : 'false'));
            
            if (!$success) {
                error_log("Falha na desativação do usuário ID: $id");
                Response::error('Erro ao desativar usuário', 500);
            }
            
            Response::success(null, 'Usuário desativado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Ativa um usuário desativado
     */
    public function activate($id) {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            $user = $this->userModel->findByIdIncludingInactive($id);
            
            if (!$user) {
                Response::notFound('Usuário não encontrado');
            }
            
            // Verificar permissões
            if ($currentUser['tipo_usuario'] == 2 && $user['empresa_id'] != $currentUser['empresa_id']) {
                Response::forbidden('Você não tem permissão para ativar este usuário');
            }
            
            $success = $this->userModel->activate($id);
            
            if (!$success) {
                Response::error('Erro ao ativar usuário', 500);
            }
            
            Response::success(null, 'Usuário ativado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Exclui definitivamente um usuário (hard delete)
     */
    public function delete($id) {
        try {
            // Apenas super admin pode excluir definitivamente
            $currentUser = JWT::requireSuperAdmin();
            
            $user = $this->userModel->findByIdIncludingInactive($id);
            
            if (!$user) {
                Response::notFound('Usuário não encontrado');
            }
            
            // Não permitir excluir o próprio usuário
            if ($user['id'] == $currentUser['user_id']) {
                Response::error('Você não pode excluir seu próprio usuário', 400);
            }
            
            $success = $this->userModel->delete($id);
            
            if (!$success) {
                Response::error('Erro ao excluir usuário', 500);
            }
            
            Response::success(null, 'Usuário excluído definitivamente com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Cria uma solicitação de exclusão de usuário (LGPD)
     */
    public function requestDeletion() {
        try {
            // Apenas admin de empresa pode solicitar exclusão
            $currentUser = JWT::requireCompanyAdmin();
            
            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validação dos dados
            $validator = new Validator($data);
            $validator->required(['usuario_id', 'motivo'])
                     ->integer('usuario_id')
                     ->in('motivo', ['inatividade', 'mudanca_empresa', 'solicitacao_titular', 'violacao_politica', 'outros']);
            
            if (!$validator->isValid()) {
                Response::error('Dados inválidos', 400, $validator->getErrors());
            }
            
            // Verificar se o usuário alvo existe e pertence à mesma empresa
            $targetUser = $this->userModel->findById($data['usuario_id']);
            
            if (!$targetUser) {
                Response::notFound('Usuário não encontrado');
            }
            
            if ($targetUser['empresa_id'] != $currentUser['empresa_id']) {
                Response::forbidden('Você só pode solicitar exclusão de usuários da sua empresa');
            }
            
            // Verificar se já existe uma solicitação pendente para este usuário
            $existingRequest = $this->checkExistingDeletionRequest($data['usuario_id']);
            
            if ($existingRequest) {
                Response::error('Já existe uma solicitação de exclusão pendente para este usuário', 400);
            }
            
            // Criar a solicitação
            $requestData = [
                'usuario_solicitante_id' => $currentUser['user_id'],
                'usuario_alvo_id' => $data['usuario_id'],
                'motivo' => $data['motivo'],
                'motivo_detalhado' => $data['motivo_detalhado'] ?? null,
                'empresa_id' => $currentUser['empresa_id']
            ];
            
            $requestId = $this->createDeletionRequest($requestData);
            
            if (!$requestId) {
                Response::error('Erro ao criar solicitação de exclusão', 500);
            }
            
            // Notificar todos os super admins
            $this->notifySuperAdmins($requestId, $requestData, $targetUser);
            
            Response::success(
                ['request_id' => $requestId], 
                'Solicitação de exclusão criada com sucesso. Os administradores do sistema foram notificados.'
            );
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Verifica se já existe uma solicitação de exclusão pendente
     */
    private function checkExistingDeletionRequest($userId) {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            SELECT id FROM solicitacoes_exclusao 
            WHERE usuario_alvo_id = ? AND status = 'pendente' AND ativo = 1
        ");
        
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Cria uma solicitação de exclusão no banco de dados
     */
    private function createDeletionRequest($data) {
        $pdo = Database::getConnection();
        
        $stmt = $pdo->prepare("
            INSERT INTO solicitacoes_exclusao 
            (usuario_solicitante_id, usuario_alvo_id, motivo, motivo_detalhado, empresa_id) 
            VALUES (?, ?, ?, ?, ?)
        ");
        
        $success = $stmt->execute([
            $data['usuario_solicitante_id'],
            $data['usuario_alvo_id'],
            $data['motivo'],
            $data['motivo_detalhado'],
            $data['empresa_id']
        ]);
        
        return $success ? $pdo->lastInsertId() : false;
    }
    
    /**
     * Notifica todos os super admins sobre a solicitação de exclusão
     */
    private function notifySuperAdmins($requestId, $requestData, $targetUser) {
        $database = new Database();
        $pdo = $database->getConnection();
        
        // Buscar todos os super admins
        $stmt = $pdo->prepare("
            SELECT id FROM usuarios 
            WHERE tipo_usuario = 1 AND ativo = 1
        ");
        
        $stmt->execute();
        $superAdmins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Buscar dados do solicitante
        $solicitante = $this->userModel->findById($requestData['usuario_solicitante_id']);
        
        // Criar notificação para cada super admin
        $notificationStmt = $pdo->prepare("
            INSERT INTO notificacoes 
            (titulo, mensagem, tipo, usuario_destinatario_id, usuario_remetente_id, empresa_id) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $titulo = 'Nova Solicitação de Exclusão de Usuário (LGPD)';
        $mensagem = sprintf(
            'O administrador %s (%s) solicitou a exclusão do usuário %s (%s) por motivo: %s. %s',
            $solicitante['nome'],
            $solicitante['email'],
            $targetUser['nome'],
            $targetUser['email'],
            $this->getMotiveDescription($requestData['motivo']),
            isset($requestData['detalhes']) && $requestData['detalhes'] ? 'Detalhes: ' . $requestData['detalhes'] : ''
        );
        
        foreach ($superAdmins as $admin) {
            $notificationStmt->execute([
                $titulo,
                $mensagem,
                'warning',
                $admin['id'],
                $requestData['usuario_solicitante_id'],
                $requestData['empresa_id']
            ]);
        }
    }
    
    /**
     * Retorna a descrição do motivo da solicitação
     */
    private function getMotiveDescription($motivo) {
        $motivos = [
            'dados_incorretos' => 'Dados incorretos',
            'nao_utiliza_mais' => 'Não utiliza mais o sistema',
            'duplicacao' => 'Duplicação de conta',
            'violacao_termos' => 'Violação dos termos de uso',
            'solicitacao_usuario' => 'Solicitação do usuário',
            'outro' => 'Outros motivos'
        ];
        
        return $motivos[$motivo] ?? 'Motivo não especificado';
    }

    /**
     * Lista solicitações de exclusão
     */
    public function listDeletionRequests() {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            // Parâmetros de consulta
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $pageSize = isset($_GET['page_size']) ? min((int)$_GET['page_size'], MAX_PAGE_SIZE) : DEFAULT_PAGE_SIZE;
            $offset = ($page - 1) * $pageSize;
            
            $filters = [];
            $whereConditions = [];
            
            // Se for admin da empresa, filtrar apenas solicitações da sua empresa
            if ($currentUser['tipo_usuario'] == 2) {
                $whereConditions[] = "s.empresa_id = :empresa_id";
                $filters['empresa_id'] = $currentUser['empresa_id'];
            }
            
            // Filtros opcionais
            if (isset($_GET['motivo']) && !empty($_GET['motivo'])) {
                $whereConditions[] = "s.motivo = :motivo";
                $filters['motivo'] = $_GET['motivo'];
            }
            
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $whereConditions[] = "s.status = :status";
                $filters['status'] = $_GET['status'];
            }
            
            $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
            
            $pdo = Database::getConnection();
            
            // Query principal
            $sql = "
                SELECT 
                    s.id,
                    s.usuario_alvo_id,
                    s.usuario_solicitante_id,
                    s.empresa_id,
                    s.motivo,
                    s.motivo_detalhado,
                    s.status,
                    s.data_solicitacao,
                    s.data_processamento,
                    s.observacoes_admin,
                    ua.nome as usuario_alvo_nome,
                    ua.email as usuario_alvo_email,
                    us.nome as usuario_solicitante_nome,
                    us.email as usuario_solicitante_email,
                    e.nome as empresa_nome
                FROM solicitacoes_exclusao s
                LEFT JOIN usuarios ua ON s.usuario_alvo_id = ua.id
                LEFT JOIN usuarios us ON s.usuario_solicitante_id = us.id
                LEFT JOIN empresas e ON s.empresa_id = e.id
                {$whereClause}
                ORDER BY s.data_solicitacao DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $pdo->prepare($sql);
            
            // Bind dos filtros
            foreach ($filters as $key => $value) {
                $stmt->bindValue(":$key", $value);
            }
            
            $stmt->bindValue(':limit', $pageSize, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Contar total de registros
            $countSql = "
                SELECT COUNT(*) as total
                FROM solicitacoes_exclusao s
                LEFT JOIN usuarios ua ON s.usuario_alvo_id = ua.id
                LEFT JOIN usuarios us ON s.usuario_solicitante_id = us.id
                LEFT JOIN empresas e ON s.empresa_id = e.id
                {$whereClause}
            ";
            
            $countStmt = $pdo->prepare($countSql);
            
            // Bind dos filtros para contagem
            foreach ($filters as $key => $value) {
                $countStmt->bindValue(":$key", $value);
            }
            
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            Response::success([
                'requests' => $requests,
                'pagination' => [
                    'current_page' => $page,
                    'page_size' => $pageSize,
                    'total_items' => (int)$total,
                    'total_pages' => ceil($total / $pageSize)
                ]
            ]);
            
        } catch (Exception $e) {
            Response::error('Erro ao listar solicitações: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Atualiza uma solicitação de exclusão (apenas super admins)
     */
    public function updateDeletionRequest() {
        try {
            $currentUser = JWT::requireSuperAdmin();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::error('Dados inválidos', 400);
                return;
            }
            
            // Validar campos obrigatórios
            $requiredFields = ['id', 'status'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    Response::error("Campo '$field' é obrigatório", 400);
                    return;
                }
            }
            
            // Validar status
            $validStatuses = ['pendente', 'aprovada', 'rejeitada', 'processada'];
            if (!in_array($input['status'], $validStatuses)) {
                Response::error('Status inválido', 400);
                return;
            }
            
            $pdo = Database::getConnection();
            
            // Verificar se a solicitação existe
            $checkStmt = $pdo->prepare("SELECT * FROM solicitacoes_exclusao WHERE id = ?");
            $checkStmt->execute([$input['id']]);
            $request = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                Response::error('Solicitação não encontrada', 404);
                return;
            }
            
            // Preparar dados para atualização
            $updateData = [
                'status' => $input['status'],
                'data_processamento' => date('Y-m-d H:i:s')
            ];
            
            if (isset($input['observacoes_admin'])) {
                $updateData['observacoes_admin'] = $input['observacoes_admin'];
            }
            
            // Construir query de atualização
            $setClause = [];
            foreach ($updateData as $field => $value) {
                $setClause[] = "$field = :$field";
            }
            
            $sql = "UPDATE solicitacoes_exclusao SET " . implode(', ', $setClause) . " WHERE id = :id";
            
            $stmt = $pdo->prepare($sql);
            
            // Bind dos valores
            foreach ($updateData as $field => $value) {
                $stmt->bindValue(":$field", $value);
            }
            $stmt->bindValue(':id', $input['id'], PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                // Se a solicitação foi aprovada, desativar o usuário
                if ($input['status'] === 'aprovada') {
                    $this->deactivateUserFromRequest($request['usuario_alvo_id']);
                }
                
                Response::success([
                    'message' => 'Solicitação atualizada com sucesso',
                    'request_id' => $input['id']
                ]);
            } else {
                Response::error('Erro ao atualizar solicitação', 500);
            }
            
        } catch (Exception $e) {
            Response::error('Erro ao atualizar solicitação: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Desativa um usuário quando a solicitação é aprovada
     */
    private function deactivateUserFromRequest($userId) {
        try {
            $pdo = Database::getConnection();
            
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ?");
            $stmt->execute([$userId]);
            
        } catch (Exception $e) {
            error_log("Erro ao desativar usuário: " . $e->getMessage());
        }
    }
}

?>