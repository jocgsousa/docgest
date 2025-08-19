<?php

require_once __DIR__ . '/../models/Branch.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class BranchController {
    private $branchModel;
    
    public function __construct() {
        $this->branchModel = new Branch();
    }
    
    /**
     * Lista filiais
     */
    public function index() {
        try {
            $user = JWT::requireAdminOrCompanyAdmin();
            
            // Parâmetros de consulta
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $pageSize = isset($_GET['page_size']) ? min((int)$_GET['page_size'], MAX_PAGE_SIZE) : DEFAULT_PAGE_SIZE;
            
            $filters = [];
            
            // Admin de empresa só pode ver filiais da sua empresa
            if ($user['tipo_usuario'] == 2) {
                $filters['empresa_id'] = $user['empresa_id'];
            }
            
            // Filtros opcionais
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            if (isset($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            $result = $this->branchModel->list($filters, $page, $pageSize);
            
            Response::paginated(
                $result['data'],
                $result['total'],
                $page,
                $pageSize,
                'Filiais recuperadas com sucesso'
            );
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Lista todas as filiais (para selects)
     */
    public function listAll() {
        try {
            $user = JWT::requireAdminOrCompanyAdmin();
            
            $empresaId = null;
            
            // Admin de empresa só pode ver filiais da sua empresa
            if ($user['tipo_usuario'] == 2) {
                $empresaId = $user['empresa_id'];
            } else {
                // Super admin pode filtrar por empresa_id se fornecido
                if (isset($_GET['empresa_id']) && !empty($_GET['empresa_id'])) {
                    $empresaId = $_GET['empresa_id'];
                }
            }
            
            $branches = $this->branchModel->listAll($empresaId);
            
            Response::success($branches, 'Filiais recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Exibe uma filial específica
     */
    public function show($id) {
        try {
            $user = JWT::requireAdminOrCompanyAdmin();
            
            $branch = $this->branchModel->findById($id);
            
            if (!$branch) {
                Response::notFound('Filial não encontrada');
            }
            
            // Admin de empresa só pode ver filiais da sua empresa
            if ($user['tipo_usuario'] == 2 && $branch['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Você não tem permissão para visualizar esta filial');
            }
            
            Response::success($branch, 'Filial recuperada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Cria nova filial
     */
    public function store() {
        try {
            $user = JWT::requireAdminOrCompanyAdmin();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::badRequest('Dados inválidos');
            }
            
            // Validação
            $validator = new Validator($input);
            $validator->required('nome', 'Nome é obrigatório')
                     ->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
                     ->max('nome', 150, 'Nome deve ter no máximo 150 caracteres');
            
            $validator->required('empresa_id', 'Empresa é obrigatória')
                     ->exists('empresa_id', 'empresas', 'id', 'Empresa não encontrada');
            
            // CNPJ é opcional, mas se fornecido deve ser válido
            if (isset($input['cnpj']) && !empty($input['cnpj'])) {
                $cnpj = preg_replace('/[^0-9]/', '', $input['cnpj']);
                $validator->cnpj('cnpj', 'CNPJ inválido');
                
                // Verificar se CNPJ já existe
                if ($this->branchModel->cnpjExists($cnpj)) {
                    $validator->addError('cnpj', 'CNPJ já está em uso');
                }
            }
            
            if (isset($input['email']) && !empty($input['email'])) {
                $validator->email('email', 'Email inválido');
            }
            

            
            // Verificar permissões
            // Admin de empresa só pode criar filiais da sua empresa
            if ($user['tipo_usuario'] == 2) {
                if ($input['empresa_id'] != $user['empresa_id']) {
                    Response::forbidden('Você só pode criar filiais da sua empresa');
                }
            }
            
            // Verificar limite de filiais do plano
            require_once __DIR__ . '/../models/Company.php';
            $companyModel = new Company();
            $company = $companyModel->findById($input['empresa_id']);
            
            if (!$company) {
                Response::notFound('Empresa não encontrada');
            }
            
            // Buscar informações do plano
            require_once __DIR__ . '/../models/Plan.php';
            $planModel = new Plan();
            $plan = $planModel->findById($company['plano_id']);
            
            if (!$plan) {
                Response::internalError('Plano da empresa não encontrado');
            }
            
            // Contar filiais atuais da empresa
            $currentBranches = $this->branchModel->countByCompany($input['empresa_id']);
            
            // Verificar se não excede o limite (999999 = ilimitado)
            if ($plan['limite_filiais'] != 999999 && $currentBranches >= $plan['limite_filiais']) {
                Response::forbidden('Limite de filiais do plano atingido. Limite atual: ' . $plan['limite_filiais'] . ' filiais');
            }
            
            // Preparar dados
            $branchData = [
                'empresa_id' => $input['empresa_id'],
                'nome' => $input['nome'],
                'cnpj' => isset($input['cnpj']) ? preg_replace('/[^0-9]/', '', $input['cnpj']) : null,
                'inscricao_estadual' => $input['inscricao_estadual'] ?? null,
                'endereco' => $input['endereco'] ?? null,
                'cidade' => $input['cidade'] ?? null,
                'estado' => $input['estado'] ?? null,
                'cep' => isset($input['cep']) ? preg_replace('/[^0-9]/', '', $input['cep']) : null,
                'telefone' => $input['telefone'] ?? null,
                'email' => $input['email'] ?? null,
                'responsavel' => $input['responsavel'] ?? null,
                'observacoes' => $input['observacoes'] ?? null
            ];
            
            $branchId = $this->branchModel->create($branchData);
            
            if ($branchId) {
                $branch = $this->branchModel->findById($branchId);
                Response::created($branch, 'Filial criada com sucesso');
            } else {
                Response::internalError('Erro ao criar filial');
            }
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Atualiza filial
     */
    public function update($id) {
        try {
            $user = JWT::requireAdminOrCompanyAdmin();
            
            $branch = $this->branchModel->findById($id);
            
            if (!$branch) {
                Response::notFound('Filial não encontrada');
            }
            
            // Admin de empresa só pode editar filiais da sua empresa
            if ($user['tipo_usuario'] == 2 && $branch['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Você não tem permissão para editar esta filial');
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                Response::badRequest('Dados inválidos');
            }
            
            // Validação
            $validator = new Validator($input);
            $validator->required('nome', 'Nome é obrigatório')
                     ->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
                     ->max('nome', 150, 'Nome deve ter no máximo 150 caracteres');
            
            // CNPJ é opcional, mas se fornecido deve ser válido
            if (isset($input['cnpj']) && !empty($input['cnpj'])) {
                $cnpj = preg_replace('/[^0-9]/', '', $input['cnpj']);
                $validator->cnpj('cnpj', 'CNPJ inválido');
                
                // Verificar se CNPJ já existe (excluindo o atual)
                if ($this->branchModel->cnpjExists($cnpj, $id)) {
                    $validator->addError('cnpj', 'CNPJ já está em uso');
                }
            }
            
            if (isset($input['email']) && !empty($input['email'])) {
                $validator->email('email', 'Email inválido');
            }
            

            
            // Preparar dados
            $branchData = [
                'nome' => $input['nome'],
                'cnpj' => isset($input['cnpj']) ? preg_replace('/[^0-9]/', '', $input['cnpj']) : null,
                'inscricao_estadual' => $input['inscricao_estadual'] ?? null,
                'endereco' => $input['endereco'] ?? null,
                'cidade' => $input['cidade'] ?? null,
                'estado' => $input['estado'] ?? null,
                'cep' => isset($input['cep']) ? preg_replace('/[^0-9]/', '', $input['cep']) : null,
                'telefone' => $input['telefone'] ?? null,
                'email' => $input['email'] ?? null,
                'responsavel' => $input['responsavel'] ?? null,
                'observacoes' => $input['observacoes'] ?? null
            ];
            
            if ($this->branchModel->update($id, $branchData)) {
                $updatedBranch = $this->branchModel->findById($id);
                Response::success($updatedBranch, 'Filial atualizada com sucesso');
            } else {
                Response::internalError('Erro ao atualizar filial');
            }
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Remove filial (soft delete)
     */
    public function destroy($id) {
        try {
            $user = JWT::requireAdminOrCompanyAdmin();
            
            $branch = $this->branchModel->findById($id);
            
            if (!$branch) {
                Response::notFound('Filial não encontrada');
            }
            
            // Admin de empresa só pode excluir filiais da sua empresa
            if ($user['tipo_usuario'] == 2 && $branch['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Você não tem permissão para excluir esta filial');
            }
            
            if ($this->branchModel->delete($id)) {
                Response::success(null, 'Filial excluída com sucesso');
            } else {
                Response::internalError('Erro ao excluir filial');
            }
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Estatísticas das filiais
     */
    public function stats() {
        try {
            $user = JWT::requireAdminOrCompanyAdmin();
            
            $filters = [];
            
            // Admin de empresa só pode ver estatísticas da sua empresa
            if ($user['tipo_usuario'] == 2) {
                $filters['empresa_id'] = $user['empresa_id'];
            }
            
            // Buscar todas as filiais para calcular estatísticas
            $result = $this->branchModel->list($filters, 1, 9999);
            $branches = $result['data'];
            
            $stats = [
                'total' => count($branches),
                'ativas' => count(array_filter($branches, function($b) { return $b['ativo']; })),
                'inativas' => count(array_filter($branches, function($b) { return !$b['ativo']; }))
            ];
            
            Response::success($stats, 'Estatísticas das filiais recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
}

?>