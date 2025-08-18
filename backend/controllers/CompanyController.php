<?php

require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class CompanyController {
    private $companyModel;
    
    public function __construct() {
        $this->companyModel = new Company();
    }
    
    /**
     * Lista empresas
     */
    public function index() {
        try {
            $user = JWT::requireAdminOrCompanyAdmin(); // Super admin ou admin de empresa podem listar empresas
            
            // Parâmetros de consulta
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $pageSize = isset($_GET['page_size']) ? min((int)$_GET['page_size'], MAX_PAGE_SIZE) : DEFAULT_PAGE_SIZE;
            
            $filters = [];
            
            // Admin de empresa só pode ver sua própria empresa
            if ($user['tipo_usuario'] == 2) {
                $filters['empresa_id'] = $user['empresa_id'];
            }
            
            // Filtros opcionais
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            if (isset($_GET['plano_id'])) {
                $filters['plano_id'] = $_GET['plano_id'];
            }
            
            $result = $this->companyModel->list($filters, $page, $pageSize);
            
            Response::paginated(
                $result['data'],
                $result['total'],
                $page,
                $pageSize,
                'Empresas recuperadas com sucesso'
            );
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Busca empresa por ID
     */
    public function show($id) {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            
            // Admin da empresa só pode ver sua própria empresa
            if ($currentUser['tipo_usuario'] == 2 && $id != $currentUser['empresa_id']) {
                Response::forbidden('Você não tem permissão para visualizar esta empresa');
            }
            
            $company = $this->companyModel->findById($id);
            
            if (!$company) {
                Response::notFound('Empresa não encontrada');
            }
            
            Response::success($company, 'Empresa recuperada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Cria nova empresa
     */
    public function store() {
        try {
            JWT::requireAdmin(); // Apenas super admin pode criar empresas
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação
            $validator = Validator::make($input)
                ->required('nome', 'Nome é obrigatório')
                ->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
                ->max('nome', 100, 'Nome deve ter no máximo 100 caracteres')
                ->required('cnpj', 'CNPJ é obrigatório')
                ->cnpj('cnpj', 'CNPJ deve ser válido')
                ->required('email', 'Email é obrigatório')
                ->email('email', 'Email deve ser válido')
                ->required('telefone', 'Telefone é obrigatório')
                ->required('plano_id', 'Plano é obrigatório')
                ->exists('plano_id', 'planos', 'id', 'Plano não encontrado')
                ->required('data_vencimento', 'Data de vencimento é obrigatória')
                ->date('data_vencimento', 'Y-m-d', 'Data de vencimento deve ser uma data válida');
            

            
            // Verificar se CNPJ já existe
            $cnpj = preg_replace('/[^0-9]/', '', $input['cnpj']);
            if ($this->companyModel->cnpjExists($cnpj)) {
                Response::validation(['cnpj' => ['Este CNPJ já está em uso']]);
            }
            
            // Verificar se email já existe
            if ($this->companyModel->emailExists($input['email'])) {
                Response::validation(['email' => ['Este email já está em uso']]);
            }
            
            // Criar empresa
            $companyData = [
                'nome' => $input['nome'],
                'cnpj' => $cnpj,
                'email' => $input['email'],
                'telefone' => $input['telefone'],
                'endereco' => $input['endereco'] ?? null,
                'cidade' => $input['cidade'] ?? null,
                'estado' => $input['estado'] ?? null,
                'cep' => isset($input['cep']) ? preg_replace('/[^0-9]/', '', $input['cep']) : null,
                'plano_id' => $input['plano_id'],
                'data_vencimento' => $input['data_vencimento']
            ];
            
            $company = $this->companyModel->create($companyData);
            
            if (!$company) {
                Response::error('Erro ao criar empresa', 500);
            }
            
            Response::created($company, 'Empresa criada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Atualiza empresa
     */
    public function update($id) {
        try {
            $currentUser = JWT::requireAdminOrCompanyAdmin();
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Admin da empresa só pode editar sua própria empresa
            if ($currentUser['tipo_usuario'] == 2 && $id != $currentUser['empresa_id']) {
                Response::forbidden('Você não tem permissão para editar esta empresa');
            }
            
            // Buscar empresa
            $company = $this->companyModel->findById($id);
            
            if (!$company) {
                Response::notFound('Empresa não encontrada');
            }
            
            // Validação
            $validator = Validator::make($input);
            
            if (isset($input['nome'])) {
                $validator->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
                         ->max('nome', 100, 'Nome deve ter no máximo 100 caracteres');
            }
            
            if (isset($input['cnpj'])) {
                $validator->cnpj('cnpj', 'CNPJ deve ser válido');
            }
            
            if (isset($input['email'])) {
                $validator->email('email', 'Email deve ser válido');
            }
            
            if (isset($input['plano_id'])) {
                $validator->exists('plano_id', 'planos', 'id', 'Plano não encontrado');
                
                // Admin da empresa não pode alterar plano
                if ($currentUser['tipo_usuario'] == 2) {
                    Response::forbidden('Você não pode alterar o plano da empresa');
                }
            }
            
            if (isset($input['data_vencimento'])) {
                $validator->date('data_vencimento', 'Y-m-d', 'Data de vencimento deve ser uma data válida');
                
                // Admin da empresa não pode alterar data de vencimento
                if ($currentUser['tipo_usuario'] == 2) {
                    Response::forbidden('Você não pode alterar a data de vencimento');
                }
            }
            

            
            // Verificar unicidade de CNPJ e email
            if (isset($input['cnpj'])) {
                $cnpj = preg_replace('/[^0-9]/', '', $input['cnpj']);
                if ($this->companyModel->cnpjExists($cnpj, $id)) {
                    Response::validation(['cnpj' => ['Este CNPJ já está em uso']]);
                }
                $input['cnpj'] = $cnpj;
            }
            
            if (isset($input['email'])) {
                if ($this->companyModel->emailExists($input['email'], $id)) {
                    Response::validation(['email' => ['Este email já está em uso']]);
                }
            }
            
            // Preparar dados para atualização
            $updateData = [];
            $allowedFields = ['nome', 'cnpj', 'email', 'telefone', 'endereco', 'cidade', 'estado', 'cep'];
            
            // Super admin pode alterar plano e data de vencimento
            if ($currentUser['tipo_usuario'] == 1) {
                $allowedFields[] = 'plano_id';
                $allowedFields[] = 'data_vencimento';
            }
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    if ($field === 'cep' && $input[$field]) {
                        $updateData[$field] = preg_replace('/[^0-9]/', '', $input[$field]);
                    } else {
                        $updateData[$field] = $input[$field];
                    }
                }
            }
            
            $updatedCompany = $this->companyModel->update($id, $updateData);
            
            if (!$updatedCompany) {
                Response::error('Erro ao atualizar empresa', 500);
            }
            
            Response::updated($updatedCompany, 'Empresa atualizada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Remove empresa (soft delete)
     */
    public function destroy($id) {
        try {
            JWT::requireAdmin(); // Apenas super admin pode excluir empresas
            
            // Buscar empresa
            $company = $this->companyModel->findById($id);
            
            if (!$company) {
                Response::notFound('Empresa não encontrada');
            }
            
            $deleted = $this->companyModel->delete($id);
            
            if (!$deleted) {
                Response::error('Erro ao excluir empresa', 500);
            }
            
            Response::deleted('Empresa excluída com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Lista todas as empresas (para selects)
     */
    public function listAll() {
        try {
            $user = JWT::requireAdminOrCompanyAdmin();
            
            // Admin de empresa só pode ver sua própria empresa
            if ($user['tipo_usuario'] == 2) {
                $companies = $this->companyModel->listByUser($user['empresa_id']);
            } else {
                // Super admin pode ver todas as empresas
                $companies = $this->companyModel->listAll();
            }
            
            Response::success($companies, 'Empresas recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Empresas com vencimento próximo
     */
    public function expiring() {
        try {
            JWT::requireAdmin(); // Apenas super admin
            
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
            $companies = $this->companyModel->findExpiringCompanies($days);
            
            Response::success($companies, 'Empresas com vencimento próximo recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Empresas vencidas
     */
    public function expired() {
        try {
            JWT::requireAdmin(); // Apenas super admin
            
            $companies = $this->companyModel->findExpiredCompanies();
            
            Response::success($companies, 'Empresas vencidas recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Estatísticas de empresas
     */
    public function stats() {
        try {
            JWT::requireAdmin(); // Apenas super admin
            
            $stats = $this->companyModel->getStats();
            $planStats = $this->companyModel->countByPlan();
            
            $response = [
                'geral' => $stats,
                'por_plano' => $planStats
            ];
            
            Response::success($response, 'Estatísticas de empresas recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Renovar empresa
     */
    public function renew($id) {
        try {
            JWT::requireAdmin(); // Apenas super admin
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Buscar empresa
            $company = $this->companyModel->findById($id);
            
            if (!$company) {
                Response::notFound('Empresa não encontrada');
            }
            
            // Validação
            $validator = Validator::make($input)
                ->required('data_vencimento', 'Nova data de vencimento é obrigatória')
                ->date('data_vencimento', 'Y-m-d', 'Data de vencimento deve ser uma data válida');
            

            
            // Atualizar data de vencimento
            $renewed = $this->companyModel->updateExpiration($id, $input['data_vencimento']);
            
            if (!$renewed) {
                Response::error('Erro ao renovar empresa', 500);
            }
            
            $updatedCompany = $this->companyModel->findById($id);
            
            Response::success($updatedCompany, 'Empresa renovada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
}

?>