<?php

require_once __DIR__ . '/../models/Plan.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class PlanController {
    private $planModel;
    
    public function __construct() {
        $this->planModel = new Plan();
    }
    
    /**
     * Lista planos
     */
    public function index() {
        try {
            JWT::requireAuth(); // Qualquer usuário autenticado pode ver planos
            
            // Parâmetros de consulta
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $pageSize = isset($_GET['page_size']) ? min((int)$_GET['page_size'], MAX_PAGE_SIZE) : DEFAULT_PAGE_SIZE;
            
            $filters = [];
            
            // Filtros opcionais
            if (isset($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            $result = $this->planModel->list($filters, $page, $pageSize);
            
            Response::paginated(
                $result['data'],
                $result['total'],
                $page,
                $pageSize,
                'Planos recuperados com sucesso'
            );
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Busca plano por ID
     */
    public function show($id) {
        try {
            JWT::requireAuth(); // Qualquer usuário autenticado pode ver planos
            
            $plan = $this->planModel->findById($id);
            
            if (!$plan) {
                Response::notFound('Plano não encontrado');
            }
            
            Response::success($plan, 'Plano recuperado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Cria novo plano
     */
    public function store() {
        try {
            JWT::requireAdmin(); // Apenas super admin pode criar planos
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação
            $validator = Validator::make($input)
                ->required('nome', 'Nome é obrigatório')
                ->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
                ->max('nome', 100, 'Nome deve ter no máximo 100 caracteres')
                ->required('limite_usuarios', 'Limite de usuários é obrigatório')
                ->integer('limite_usuarios', 'Limite de usuários deve ser um número inteiro')
                ->required('limite_documentos', 'Limite de documentos é obrigatório')
                ->integer('limite_documentos', 'Limite de documentos deve ser um número inteiro')
                ->required('limite_assinaturas', 'Limite de assinaturas é obrigatório')
                ->integer('limite_assinaturas', 'Limite de assinaturas deve ser um número inteiro');
            
            // Validar preço se fornecido
            if (isset($input['preco'])) {
                $validator->numeric('preco', 'Preço deve ser um número válido');
            }
            

            
            // Verificar se nome já existe
            if ($this->planModel->nameExists($input['nome'])) {
                Response::validation(['nome' => ['Este nome já está em uso']]);
            }
            
            // Validar valores mínimos
            if (isset($input['preco']) && $input['preco'] < 0) {
                Response::validation(['preco' => ['Preço deve ser maior ou igual a zero']]);
            }
            
            if ($input['limite_usuarios'] < 1) {
                Response::validation(['limite_usuarios' => ['Limite de usuários deve ser maior que zero']]);
            }
            
            if ($input['limite_documentos'] < 1) {
                Response::validation(['limite_documentos' => ['Limite de documentos deve ser maior que zero']]);
            }
            
            if ($input['limite_assinaturas'] < 1) {
                Response::validation(['limite_assinaturas' => ['Limite de assinaturas deve ser maior que zero']]);
            }
            
            // Criar plano
            $planData = [
                'nome' => $input['nome'],
                'descricao' => $input['descricao'] ?? null,
                'preco' => $input['preco'] ?? 0, // Padrão para plano gratuito
                'limite_usuarios' => $input['limite_usuarios'],
                'limite_documentos' => $input['limite_documentos'],
                'limite_assinaturas' => $input['limite_assinaturas']
            ];
            
            $plan = $this->planModel->create($planData);
            
            if (!$plan) {
                Response::error('Erro ao criar plano', 500);
            }
            
            Response::created($plan, 'Plano criado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Atualiza plano
     */
    public function update($id) {
        try {
            JWT::requireAdmin(); // Apenas super admin pode editar planos
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Buscar plano
            $plan = $this->planModel->findById($id);
            
            if (!$plan) {
                Response::notFound('Plano não encontrado');
            }
            
            // Validação
            $validator = Validator::make($input);
            
            if (isset($input['nome'])) {
                $validator->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
                         ->max('nome', 100, 'Nome deve ter no máximo 100 caracteres');
            }
            
            if (isset($input['preco'])) {
                $validator->numeric('preco', 'Preço deve ser um número válido');
            }
            
            if (isset($input['limite_usuarios'])) {
                $validator->integer('limite_usuarios', 'Limite de usuários deve ser um número inteiro');
            }
            
            if (isset($input['limite_documentos'])) {
                $validator->integer('limite_documentos', 'Limite de documentos deve ser um número inteiro');
            }
            
            if (isset($input['limite_assinaturas'])) {
                $validator->integer('limite_assinaturas', 'Limite de assinaturas deve ser um número inteiro');
            }
            

            
            // Verificar unicidade de nome
            if (isset($input['nome'])) {
                if ($this->planModel->nameExists($input['nome'], $id)) {
                    Response::validation(['nome' => ['Este nome já está em uso']]);
                }
            }
            
            // Validar valores mínimos
            if (isset($input['preco']) && $input['preco'] < 0) {
                Response::validation(['preco' => ['Preço deve ser maior ou igual a zero']]);
            }
            
            if (isset($input['limite_usuarios']) && $input['limite_usuarios'] < 1) {
                Response::validation(['limite_usuarios' => ['Limite de usuários deve ser maior que zero']]);
            }
            
            if (isset($input['limite_documentos']) && $input['limite_documentos'] < 1) {
                Response::validation(['limite_documentos' => ['Limite de documentos deve ser maior que zero']]);
            }
            
            if (isset($input['limite_assinaturas']) && $input['limite_assinaturas'] < 1) {
                Response::validation(['limite_assinaturas' => ['Limite de assinaturas deve ser maior que zero']]);
            }
            
            // Preparar dados para atualização
            $updateData = [];
            $allowedFields = ['nome', 'descricao', 'preco', 'limite_usuarios', 'limite_documentos', 'limite_assinaturas'];
            
            foreach ($allowedFields as $field) {
                if (isset($input[$field])) {
                    $updateData[$field] = $input[$field];
                }
            }
            
            $updatedPlan = $this->planModel->update($id, $updateData);
            
            if (!$updatedPlan) {
                Response::error('Erro ao atualizar plano', 500);
            }
            
            Response::updated($updatedPlan, 'Plano atualizado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Remove plano (soft delete)
     */
    public function destroy($id) {
        try {
            JWT::requireAdmin(); // Apenas super admin pode excluir planos
            
            // Buscar plano
            $plan = $this->planModel->findById($id);
            
            if (!$plan) {
                Response::notFound('Plano não encontrado');
            }
            
            $deleted = $this->planModel->delete($id);
            
            if (!$deleted) {
                Response::error('Erro ao excluir plano', 500);
            }
            
            Response::deleted('Plano excluído com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Lista todos os planos (para selects)
     */
    public function listAll() {
        try {
            JWT::requireAuth(); // Qualquer usuário autenticado pode ver planos
            
            $plans = $this->planModel->listAll();
            
            Response::success($plans, 'Lista de planos recuperada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Estatísticas de planos
     */
    public function stats() {
        try {
            JWT::requireAdmin(); // Apenas super admin
            
            $stats = $this->planModel->getStats();
            $mostUsed = $this->planModel->getMostUsed();
            
            $response = [
                'geral' => $stats,
                'mais_utilizados' => $mostUsed
            ];
            
            Response::success($response, 'Estatísticas de planos recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Empresas de um plano
     */
    public function companies($id) {
        try {
            JWT::requireAdmin(); // Apenas super admin
            
            // Buscar plano
            $plan = $this->planModel->findById($id);
            
            if (!$plan) {
                Response::notFound('Plano não encontrado');
            }
            
            $companies = $this->planModel->getCompanies($id);
            
            Response::success($companies, 'Empresas do plano recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
}

?>