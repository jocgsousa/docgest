<?php

require_once __DIR__ . '/../models/DocumentType.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class DocumentTypeController {
    private $documentType;
    private $validator;
    
    public function __construct() {
        $this->documentType = new DocumentType();
        $this->validator = new Validator([]);
    }
    
    public function index() {
        try {
            $user = JWT::requireAuth();
            
            // Parâmetros de paginação
            $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? max(1, min(100, (int)$_GET['limit'])) : 10;
            $search = isset($_GET['search']) ? trim($_GET['search']) : '';
            $status = isset($_GET['status']) ? $_GET['status'] : '';
            
            // Buscar tipos de documentos com paginação
            $result = $this->documentType->findAllPaginated($page, $limit, $search, $status);
            
            Response::success([
                'data' => $result['data'],
                'total' => $result['total'],
                'page' => $page,
                'limit' => $limit,
                'totalPages' => ceil($result['total'] / $limit)
            ], 'Tipos de documentos listados com sucesso');
        } catch (Exception $e) {
            Response::error('Erro ao listar tipos de documentos: ' . $e->getMessage());
        }
    }
    
    public function show($id) {
        try {
            $user = JWT::requireAuth();
            
            $documentType = $this->documentType->findById($id);
            
            if (!$documentType) {
                Response::notFound('Tipo de documento não encontrado');
                return;
            }
            
            Response::success($documentType, 'Tipo de documento encontrado');
        } catch (Exception $e) {
            Response::error('Erro ao buscar tipo de documento: ' . $e->getMessage());
        }
    }
    
    public function store() {
        try {
            $user = JWT::requireAuth();
            
            // Apenas super admin e admin podem criar tipos de documentos
            if ($user['tipo_usuario'] > 2) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            // Processar dados JSON se necessário
            $input = file_get_contents('php://input');
            $requestData = [];
            
            // Tentar decodificar como JSON primeiro
            if (!empty($input)) {
                $jsonData = json_decode($input, true);
                if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
                    $requestData = $jsonData;
                } else {
                    // Fallback para parse_str se não for JSON válido
                    parse_str($input, $requestData);
                }
            }
            
            // Mesclar com $_POST (prioridade para $_POST)
            $requestData = array_merge($requestData, $_POST);
            
            // Validação dos dados
            $data = [
                'nome' => trim($requestData['nome'] ?? ''),
                'descricao' => trim($requestData['descricao'] ?? ''),
                'ativo' => isset($requestData['ativo']) ? (bool)$requestData['ativo'] : true
            ];
            
            $validator = new Validator($data);
            $validator->required('nome', 'Nome é obrigatório');
            $validator->max('nome', 100, 'Nome deve ter no máximo 100 caracteres');
            $validator->max('descricao', 500, 'Descrição deve ter no máximo 500 caracteres');
            
            if ($validator->hasErrors()) {
                Response::validation($validator->getErrors());
                return;
            }
            
            // Verificar se já existe um tipo com o mesmo nome
            if ($this->documentType->existsByName($data['nome'])) {
                Response::validation(['nome' => ['Já existe um tipo de documento com este nome']]);
                return;
            }
            
            $documentTypeId = $this->documentType->create($data);
            
            if ($documentTypeId) {
                $newDocumentType = $this->documentType->findById($documentTypeId);
                Response::created($newDocumentType, 'Tipo de documento criado com sucesso');
            } else {
                Response::error('Erro ao criar tipo de documento');
            }
        } catch (Exception $e) {
            Response::error('Erro ao criar tipo de documento: ' . $e->getMessage());
        }
    }
    
    public function update($id) {
        try {
            $user = JWT::requireAuth();
            
            // Apenas super admin e admin podem atualizar tipos de documentos
            if ($user['tipo_usuario'] > 2) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            $documentType = $this->documentType->findById($id);
            
            if (!$documentType) {
                Response::notFound('Tipo de documento não encontrado');
                return;
            }
            
            // Processar dados PUT/JSON se necessário
            $input = file_get_contents('php://input');
            $requestData = [];
            
            // Tentar decodificar como JSON primeiro
            if (!empty($input)) {
                $jsonData = json_decode($input, true);
                if ($jsonData && json_last_error() === JSON_ERROR_NONE) {
                    $requestData = $jsonData;
                } else {
                    // Fallback para parse_str se não for JSON válido
                    parse_str($input, $requestData);
                }
            }
            
            // Mesclar com $_POST (prioridade para $_POST)
            $requestData = array_merge($requestData, $_POST);
            
            // Validação dos dados
            $data = [
                'nome' => isset($requestData['nome']) ? trim($requestData['nome']) : $documentType['nome'],
                'descricao' => isset($requestData['descricao']) ? trim($requestData['descricao']) : $documentType['descricao'],
                'ativo' => isset($requestData['ativo']) ? (bool)$requestData['ativo'] : (bool)$documentType['ativo']
            ];
            
            $validator = new Validator($data);
            $validator->required('nome', 'Nome é obrigatório');
            $validator->max('nome', 100, 'Nome deve ter no máximo 100 caracteres');
            $validator->max('descricao', 500, 'Descrição deve ter no máximo 500 caracteres');
            
            if ($validator->hasErrors()) {
                Response::validation($validator->getErrors());
                return;
            }
            
            // Verificar se já existe outro tipo com o mesmo nome
            if ($data['nome'] !== $documentType['nome'] && $this->documentType->existsByName($data['nome'])) {
                Response::validation(['nome' => ['Já existe um tipo de documento com este nome']]);
                return;
            }
            
            $success = $this->documentType->update($id, $data);
            
            if ($success) {
                $updatedDocumentType = $this->documentType->findById($id);
                Response::success($updatedDocumentType, 'Tipo de documento atualizado com sucesso');
            } else {
                Response::error('Erro ao atualizar tipo de documento');
            }
        } catch (Exception $e) {
            Response::error('Erro ao atualizar tipo de documento: ' . $e->getMessage());
        }
    }
    
    public function destroy($id) {
        try {
            $user = JWT::requireAuth();
            
            // Apenas super admin e admin podem excluir tipos de documentos
            if ($user['tipo_usuario'] > 2) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            $documentType = $this->documentType->findById($id);
            
            if (!$documentType) {
                Response::notFound('Tipo de documento não encontrado');
                return;
            }
            
            // Verificar se existem documentos usando este tipo
            if ($this->documentType->hasDocuments($id)) {
                Response::validation(['id' => ['Não é possível excluir este tipo de documento pois existem documentos vinculados a ele']]);
                return;
            }
            
            $success = $this->documentType->delete($id);
            
            if ($success) {
                Response::success(null, 'Tipo de documento excluído com sucesso');
            } else {
                Response::error('Erro ao excluir tipo de documento');
            }
        } catch (Exception $e) {
            Response::error('Erro ao excluir tipo de documento: ' . $e->getMessage());
        }
    }
}