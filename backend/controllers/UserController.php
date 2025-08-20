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
            
            $user = $this->userModel->findById($id);
            
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
            $user = $this->userModel->findById($id);
            
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
}

?>