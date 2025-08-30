<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class AuthController {
    private $userModel;
    
    public function __construct() {
        $this->userModel = new User();
    }
    
    /**
     * Login do usuário
     */
    public function login() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Normalizar campo de senha (aceitar 'password' ou 'senha')
            if (isset($input['password']) && !isset($input['senha'])) {
                $input['senha'] = $input['password'];
            }
            
            // Validação
            $validator = Validator::make($input)
                ->required('email', 'Email é obrigatório')
                ->email('email', 'Email deve ser válido')
                ->required('senha', 'Senha é obrigatória');
            
            // Buscar usuário
            $user = $this->userModel->findByEmail($input['email']);
            
            if (!$user) {
                Response::error('Credenciais inválidas', 401);
            }
            
            // Verificar senha
            if (!$this->userModel->verifyPassword($input['senha'], $user['senha'])) {
                Response::error('Credenciais inválidas', 401);
            }
            
            // Atualizar último login
            $this->userModel->updateLastLogin($user['id']);
            
            // Gerar token
            $token = JWT::generateUserToken($user);
            
            // Remover senha dos dados do usuário
            unset($user['senha']);
            
            // Converter campos numéricos para inteiros
            if (isset($user['id'])) {
                $user['id'] = (int) $user['id'];
            }
            if (isset($user['tipo_usuario'])) {
                $user['tipo_usuario'] = (int) $user['tipo_usuario'];
            }
            if (isset($user['empresa_id'])) {
                $user['empresa_id'] = (int) $user['empresa_id'];
            }
            if (isset($user['filial_id'])) {
                $user['filial_id'] = (int) $user['filial_id'];
            }
            
            Response::success([
                'user' => $user,
                'token' => $token
            ], 'Login realizado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Registro de novo usuário
     */
    public function register() {
        try {
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
                ->confirmed('senha', 'Confirmação de senha não confere')
                ->required('cpf', 'CPF é obrigatório')
                ->cpf('cpf', 'CPF deve ser válido')
                ->unique('cpf', 'usuarios', 'cpf', null, 'Este CPF já está em uso')
                ->required('telefone', 'Telefone é obrigatório')
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
            $currentUser = JWT::validateToken();
            
            // Super admin pode criar qualquer tipo
            if ($currentUser['tipo_usuario'] == 1) {
                // OK
            }
            // Admin da empresa pode criar admin da empresa e assinantes da sua empresa
            elseif ($currentUser['tipo_usuario'] == 2) {
                if ($input['tipo_usuario'] == 1) {
                    Response::forbidden('Você não pode criar super administradores');
                }
                if ($input['empresa_id'] != $currentUser['empresa_id']) {
                    Response::forbidden('Você só pode criar usuários da sua empresa');
                }
            }
            // Assinantes não podem criar usuários
            else {
                Response::forbidden('Você não tem permissão para criar usuários');
            }
            
            // Criar usuário
            $userData = [
                'nome' => $input['nome'],
                'email' => $input['email'],
                'senha' => $input['senha'],
                'cpf' => preg_replace('/[^0-9]/', '', $input['cpf']),
                'telefone' => $input['telefone'],
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
     * Obter dados do usuário logado
     */
    public function me() {
        try {
            $currentUser = JWT::requireAuth();
            
            $user = $this->userModel->findById($currentUser['user_id']);
            
            if (!$user) {
                Response::notFound('Usuário não encontrado');
            }
            
            // Remover senha
            unset($user['senha']);
            
            // Converter campos numéricos para inteiros
            if (isset($user['id'])) {
                $user['id'] = (int) $user['id'];
            }
            if (isset($user['tipo_usuario'])) {
                $user['tipo_usuario'] = (int) $user['tipo_usuario'];
            }
            if (isset($user['empresa_id'])) {
                $user['empresa_id'] = (int) $user['empresa_id'];
            }
            if (isset($user['filial_id'])) {
                $user['filial_id'] = (int) $user['filial_id'];
            }
            
            Response::success($user, 'Dados do usuário recuperados com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Atualizar perfil do usuário logado
     */
    public function updateProfile() {
        try {
            $currentUser = JWT::requireAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação
            $validator = Validator::make($input);
            
            if (isset($input['nome'])) {
                $validator->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
                         ->max('nome', 100, 'Nome deve ter no máximo 100 caracteres');
            }
            
            if (isset($input['email'])) {
                $validator->email('email', 'Email deve ser válido')
                         ->unique('email', 'usuarios', 'email', $currentUser['user_id'], 'Este email já está em uso');
            }
            
            if (isset($input['cpf'])) {
                $validator->cpf('cpf', 'CPF deve ser válido')
                         ->unique('cpf', 'usuarios', 'cpf', $currentUser['user_id'], 'Este CPF já está em uso');
            }
            
            if (isset($input['senha'])) {
                $validator->min('senha', 6, 'Senha deve ter pelo menos 6 caracteres')
                         ->confirmed('senha', 'Confirmação de senha não confere');
            }
            

            
            // Atualizar usuário
            $updateData = [];
            $allowedFields = ['nome', 'email', 'cpf', 'telefone', 'senha'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if ($field === 'cpf') {
                        $updateData[$field] = preg_replace('/[^0-9]/', '', $input[$field]);
                    } else {
                        $updateData[$field] = $input[$field];
                    }
                }
            }
            
            $user = $this->userModel->update($currentUser['user_id'], $updateData);
            
            if (!$user) {
                Response::error('Erro ao atualizar perfil', 500);
            }
            
            // Remover senha
            unset($user['senha']);
            
            Response::updated($user, 'Perfil atualizado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Alterar senha
     */
    public function changePassword() {
        try {
            $currentUser = JWT::requireAuth();
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação
            $validator = Validator::make($input)
                ->required('senha_atual', 'Senha atual é obrigatória')
                ->required('nova_senha', 'Nova senha é obrigatória')
                ->min('nova_senha', 6, 'Nova senha deve ter pelo menos 6 caracteres')
                ->confirmed('nova_senha', 'Confirmação da nova senha não confere');
            

            
            // Buscar usuário
            $user = $this->userModel->findById($currentUser['user_id']);
            
            if (!$user) {
                Response::notFound('Usuário não encontrado');
            }
            
            // Verificar senha atual
            if (!$this->userModel->verifyPassword($input['senha_atual'], $user['senha'])) {
                Response::error('Senha atual incorreta', 400);
            }
            
            // Atualizar senha
            $updated = $this->userModel->update($currentUser['user_id'], [
                'senha' => $input['nova_senha']
            ]);
            
            if (!$updated) {
                Response::error('Erro ao alterar senha', 500);
            }
            
            Response::success(null, 'Senha alterada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Registro externo de assinante (sem autenticação)
     */
    public function registerExternal() {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Mapear campos do frontend para o backend
            if (isset($input['tipo']) && !isset($input['tipo_usuario'])) {
                // Mapear tipo para tipo_usuario
                $tipoMap = [
                    'assinante' => 3,
                    'admin' => 2,
                    'super_admin' => 1
                ];
                $input['tipo_usuario'] = $tipoMap[$input['tipo']] ?? 3; // Default para assinante
            }
            
            // Buscar empresa pelo código
            if (isset($input['codigoEmpresa'])) {
                require_once __DIR__ . '/../models/Company.php';
                $companyModel = new Company();
                $empresa = $companyModel->findByCode($input['codigoEmpresa']);
                
                if (!$empresa) {
                    Response::error('Código da empresa não encontrado', 400);
                    return;
                }
                
                $input['empresa_id'] = $empresa['id'];
            }
            
            // Validações básicas primeiro
            $validator = Validator::make($input)
                ->required('nome', 'Nome é obrigatório')
                ->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
                ->max('nome', 100, 'Nome deve ter no máximo 100 caracteres')
                ->required('email', 'Email é obrigatório')
                ->email('email', 'Email deve ser válido')
                ->required('senha', 'Senha é obrigatória')
                ->min('senha', 6, 'Senha deve ter pelo menos 6 caracteres')
                ->required('cpf', 'CPF é obrigatório')
                ->cpf('cpf', 'CPF deve ser válido')
                ->required('telefone', 'Telefone é obrigatório')
                ->required('empresa_id', 'Empresa é obrigatória')
                ->exists('empresa_id', 'empresas', 'id', 'Empresa não encontrada');
            
            // Verificar se email já existe
            if ($this->userModel->emailExists($input['email'])) {
                Response::error('Este email já está cadastrado no sistema', 409);
            }
            
            // Verificar se CPF já existe
            $cpfLimpo = preg_replace('/[^0-9]/', '', $input['cpf']);
            if ($this->userModel->cpfExists($cpfLimpo)) {
                Response::error('Este CPF já está cadastrado no sistema', 409);
            }
            
            // Para cadastro externo, sempre será assinante (tipo 3)
            $input['tipo_usuario'] = 3;
            
            // Buscar filial matriz da empresa para definir como padrão
            require_once __DIR__ . '/../models/Branch.php';
            $branchModel = new Branch();
            $matrizFilial = $branchModel->getMatrizByEmpresa($input['empresa_id']);
            
            $filialId = null;
            if ($matrizFilial) {
                $filialId = $matrizFilial['id'];
            }
            
            // Criar usuário
            $userData = [
                'nome' => $input['nome'],
                'email' => $input['email'],
                'senha' => $input['senha'], // Remover hash duplo - o modelo User já faz o hash
                'cpf' => $cpfLimpo,
                'telefone' => $input['telefone'],
                'profissao_id' => 12, // Profissão padrão 'Outros' para cadastro externo
                'tipo_usuario' => $input['tipo_usuario'],
                'empresa_id' => $input['empresa_id'],
                'filial_id' => $filialId
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
     * Logout (invalidar token - implementação básica)
     */
    public function logout() {
        try {
            JWT::requireAuth();
            
            // Em uma implementação mais robusta, você adicionaria o token a uma blacklist
            // Por enquanto, apenas retornamos sucesso
            
            Response::success(null, 'Logout realizado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Verificar se token é válido
     */
    public function verifyToken() {
        try {
            $user = JWT::requireAuth();
            
            Response::success([
                'valid' => true,
                'user' => $user
            ], 'Token válido');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
}

?>