<?php

require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/DocumentAssinante.php';
require_once __DIR__ . '/../models/DocumentAssinanteSolicitado.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/JWT.php';

class DocumentController {
    private $document;
    private $documentAssinante;
    private $documentAssinanteSolicitado;
    private $validator;
    
    public function __construct() {
        $this->document = new Document();
        $this->documentAssinante = new DocumentAssinante();
        $this->documentAssinanteSolicitado = new DocumentAssinanteSolicitado();
        $this->validator = new Validator();
    }
    
    /**
     * Converte extensões de arquivo em MIME types
     */
    private function getExtensionToMimeMap() {
        return [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'txt' => 'text/plain',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'rtf' => 'application/rtf',
            'odt' => 'application/vnd.oasis.opendocument.text'
        ];
    }
    
    /**
     * Obtém tipos MIME permitidos das configurações
     */
    private function getAllowedMimeTypes() {
        $settings = new Settings();
        $allowedExtensions = $settings->get('allowed_file_types');
        
        if (!$allowedExtensions) {
            // Fallback para tipos padrão
            return ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'text/plain'];
        }
        
        $extensions = array_map('trim', explode(',', $allowedExtensions));
        $mimeMap = $this->getExtensionToMimeMap();
        $allowedMimes = [];
        
        foreach ($extensions as $ext) {
            if (isset($mimeMap[$ext])) {
                $allowedMimes[] = $mimeMap[$ext];
            }
        }
        
        return $allowedMimes;
    }
    
    /**
     * Obtém tamanho máximo de arquivo das configurações
     */
    private function getMaxFileSize() {
        $settings = new Settings();
        $maxSizeMB = $settings->get('max_file_size');
        
        if (!$maxSizeMB) {
            $maxSizeMB = 10; // Fallback para 10MB
        }
        
        return $maxSizeMB * 1024 * 1024; // Converter para bytes
    }
    
    public function index() {
        try {
            $user = JWT::requireAuth();
            
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $pageSize = isset($_GET['page_size']) ? (int)$_GET['page_size'] : 10;
            
            $filters = [];
            
            if (isset($_GET['search']) && !empty($_GET['search'])) {
                $filters['search'] = $_GET['search'];
            }
            
            if (isset($_GET['status']) && !empty($_GET['status'])) {
                $filters['status'] = $_GET['status'];
            }
            
            // Filtrar por empresa/filial baseado no tipo de usuário
            if ($user['tipo_usuario'] == 2) { // Admin da empresa
                $filters['empresa_id'] = $user['empresa_id'];
            } elseif ($user['tipo_usuario'] == 3) { // Assinante
                $filters['empresa_id'] = $user['empresa_id'];
                if ($user['filial_id']) {
                    $filters['filial_id'] = $user['filial_id'];
                }
            }
            
            $result = $this->document->findAll($filters, $page, $pageSize);
            
            Response::paginated($result['data'], $result['total'], $page, $pageSize);
        } catch (Exception $e) {
            Response::error('Erro ao buscar documentos: ' . $e->getMessage());
        }
    }
    
    public function show($id) {
        try {
            $user = JWT::requireAuth();
            
            $document = $this->document->findById($id);
            
            if (!$document) {
                Response::notFound('Documento não encontrado');
                return;
            }
            
            // Verificar permissões
            if ($user['tipo_usuario'] == 2 && $document['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            if ($user['tipo_usuario'] == 3) {
                if ($document['empresa_id'] != $user['empresa_id'] || 
                    ($user['filial_id'] && $document['filial_id'] != $user['filial_id'])) {
                    Response::forbidden('Acesso negado');
                    return;
                }
            }
            
            Response::success($document);
        } catch (Exception $e) {
            Response::error('Erro ao buscar documento: ' . $e->getMessage());
        }
    }
    
    public function store() {
        try {
            $user = JWT::requireAuth();
            
            // Validação dos dados
            $rules = [
                'titulo' => 'required|max:255',
                'descricao' => 'max:1000',
                'empresa_id' => 'required'
            ];
            
            $data = [
                'titulo' => $_POST['titulo'] ?? '',
                'descricao' => $_POST['descricao'] ?? '',
                'empresa_id' => $_POST['empresa_id'] ?? '',
                'filial_id' => $_POST['filial_id'] ?? null,
                'assinantes' => $_POST['assinantes'] ?? '',
                'solicitar_assinatura' => isset($_POST['solicitar_assinatura']) ? (bool)$_POST['solicitar_assinatura'] : false,
                'assinantes_solicitados' => $_POST['assinantes_solicitados'] ?? '',
                'status' => $_POST['status'] ?? 'rascunho',
                'tipo_documento_id' => $_POST['tipo_documento_id'] ?? null,
                'prazo_assinatura' => $_POST['prazo_assinatura'] ?? null,
                'competencia' => $this->formatCompetenciaDate($_POST['competencia'] ?? null),
                'validade_legal' => $_POST['validade_legal'] ?? null
            ];
            
            $validator = new Validator($data);
            $validator->required('titulo', 'Título é obrigatório');
            $validator->required('empresa_id', 'Empresa é obrigatória');
            
            // Validar status se fornecido
            $validStatuses = ['rascunho', 'enviado', 'assinado', 'cancelado', 'arquivo'];
            if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
                $validator->addError('status', 'Status inválido');
            }
            

            if ($validator->hasErrors()) {
                Response::validation($validator->getErrors());
                return;
            }
            
            // Verificar se o plano da empresa está vencido (exceto para super admin)
            if ($user['tipo_usuario'] != 1) {
                require_once __DIR__ . '/../models/Company.php';
                $companyModel = new Company();
                if ($companyModel->isPlanExpired($user['empresa_id'])) {
                    Response::forbidden('Não é possível criar novos documentos. O plano da empresa está vencido.');
                    return;
                }
            }
            
            // Validar que usuários assinantes não podem definir assinantes ou solicitar assinatura
            if ($user['tipo_usuario'] == 3 && (!empty($data['assinantes']) || $data['solicitar_assinatura'])) {
                Response::forbidden('Usuários assinantes não podem definir assinantes ou solicitar assinatura para documentos');
                return;
            }
            
            // Validar que apenas Admin Empresa e Super Admin podem solicitar assinatura
            if ($data['solicitar_assinatura'] && !in_array($user['tipo_usuario'], [1, 2])) {
                Response::forbidden('Apenas administradores podem solicitar assinatura de documentos');
                return;
            }
            
            // Validar e processar assinantes
            $assinantes = [];
            if (is_string($data['assinantes'])) {
                $assinantes = json_decode($data['assinantes'], true);
            } else if (is_array($data['assinantes'])) {
                $assinantes = $data['assinantes'];
            }
            
            // Assinantes são opcionais, mas se fornecidos devem ser um array válido
            if (!empty($data['assinantes']) && (empty($assinantes) || !is_array($assinantes))) {
                Response::validation(['assinantes' => ['Formato de assinantes inválido']]);
                return;
            }
            
            // Processar upload do arquivo
            if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
                Response::validation(['arquivo' => ['Arquivo é obrigatório']]);
                return;
            }
            
            $file = $_FILES['arquivo'];
            $allowedTypes = $this->getAllowedMimeTypes();
            $maxSize = $this->getMaxFileSize();
            
            if (!in_array($file['type'], $allowedTypes)) {
                Response::validation(['arquivo' => ['Tipo de arquivo não permitido']]);
                return;
            }
            
            if ($file['size'] > $maxSize) {
                $maxSizeMB = $maxSize / (1024 * 1024);
                Response::validation(['arquivo' => ["Arquivo muito grande (máximo {$maxSizeMB}MB)"]]);
                return;
            }
            
            // Criar diretório se não existir
            $uploadDir = __DIR__ . '/../uploads/documents/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Gerar nome único para o arquivo
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = uniqid() . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                Response::error('Erro ao fazer upload do arquivo');
                return;
            }
            
            // Preparar dados para salvar
            $documentData = [
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'],
                'nome_arquivo' => $file['name'],
                'caminho_arquivo' => 'uploads/documents/' . $fileName,
                'tamanho_arquivo' => $file['size'],
                'tipo_arquivo' => $file['type'],
                'status' => $data['status'],
                'solicitar_assinatura' => $data['solicitar_assinatura'],
                'criado_por' => $user['user_id'],
                'empresa_id' => $data['empresa_id'],
                'filial_id' => $data['filial_id'],
                'tipo_documento_id' => $data['tipo_documento_id'],
                'prazo_assinatura' => $data['prazo_assinatura'],
                'competencia' => $this->formatCompetenciaDate($data['competencia']),
                'validade_legal' => $data['validade_legal'],
                'vinculado_a' => isset($data['vinculado_a']) && !empty($data['vinculado_a']) ? $data['vinculado_a'] : null
            ];
            
            // Remove campos nulos para evitar erros de constraint
            $documentData = array_filter($documentData, function($value) {
                return $value !== null;
            });
            
            // Processar assinantes solicitados se fornecidos
            $assinantesSolicitados = [];
            if ($data['solicitar_assinatura'] && !empty($data['assinantes_solicitados'])) {
                $assinantesSolicitados = json_decode($data['assinantes_solicitados'], true);
                if (!is_array($assinantesSolicitados)) {
                    $assinantesSolicitados = [];
                }
            }
            
            $documentId = $this->document->create($documentData);
            
            if ($documentId) {
                // Criar vinculações com os assinantes (se houver)
                $assinantesCreated = true;
                if (!empty($assinantes)) {
                    foreach ($assinantes as $assinante) {
                        $assinanteData = [
                            'documento_id' => $documentId,
                            'usuario_id' => $assinante,
                            'status' => 'pendente',
                            'observacoes' => null
                        ];
                        
                        if (!$this->documentAssinante->create($assinanteData)) {
                            $assinantesCreated = false;
                            break;
                        }
                    }
                }
                
                // Criar assinantes solicitados se fornecidos
                if (!empty($assinantesSolicitados)) {
                    foreach ($assinantesSolicitados as $assinante) {
                        $assinanteData = [
                            'documento_id' => $documentId,
                            'usuario_id' => $assinante,
                            'status' => 'pendente',
                            'data_solicitacao' => date('Y-m-d H:i:s')
                        ];
                        
                        if (!$this->documentAssinanteSolicitado->create($assinanteData)) {
                            $assinantesCreated = false;
                            break;
                        }
                    }
                }
                
                if ($assinantesCreated) {
                    $newDocument = $this->document->findById($documentId);
                    Response::created($newDocument, 'Documento criado com sucesso');
                } else {
                    // Remover arquivo e documento se falhou ao criar assinantes
                    if (file_exists($filePath)) {
                        unlink($filePath);
                    }
                    $this->document->delete($documentId);
                    Response::error('Erro ao vincular assinantes ao documento');
                }
            } else {
                // Remover arquivo se falhou ao salvar no banco
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
                Response::error('Erro ao criar documento');
            }
        } catch (Exception $e) {
            Response::error('Erro ao criar documento: ' . $e->getMessage());
        }
    }
    
    public function update($id) {
        try {
            // Processar dados PUT se necessário
            if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
                $input = file_get_contents('php://input');
                
                // Se o input contém dados de formulário multipart
                if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false) {
                    // Para multipart/form-data em PUT, precisamos fazer parse manual
                    $this->parseMultipartFormData($input);
                } elseif (!empty($input) && empty($_POST)) {
                    // Para outros tipos de conteúdo, fazer parse manual
                    $putData = [];
                    parse_str($input, $putData);
                    $_POST = array_merge($_POST, $putData);
                }
            }
            
            $user = JWT::requireAuth();
            
            $document = $this->document->findById($id);
            
            if (!$document) {
                Response::notFound('Documento não encontrado');
                return;
            }
            
            // Verificar permissões
            if ($user['tipo_usuario'] == 2 && $document['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            if ($user['tipo_usuario'] == 3) {
                if ($document['criado_por'] != $user['user_id']) {
                    Response::forbidden('Acesso negado');
                    return;
                }
            }
            
            // Validação dos dados
            $rules = [
                'titulo' => 'required|max:255',
                'descricao' => 'max:1000'
            ];
            
            $data = [
                'titulo' => isset($_POST['titulo']) ? $_POST['titulo'] : $document['titulo'],
                'descricao' => isset($_POST['descricao']) ? $_POST['descricao'] : $document['descricao'],
                'empresa_id' => isset($_POST['empresa_id']) ? $_POST['empresa_id'] : $document['empresa_id'],
                'filial_id' => isset($_POST['filial_id']) ? $_POST['filial_id'] : $document['filial_id'],
                'status' => isset($_POST['status']) ? $_POST['status'] : $document['status'],
                'tipo_documento_id' => isset($_POST['tipo_documento_id']) ? $_POST['tipo_documento_id'] : $document['tipo_documento_id'],
                'prazo_assinatura' => isset($_POST['prazo_assinatura']) ? $_POST['prazo_assinatura'] : $document['prazo_assinatura'],
                'competencia' => isset($_POST['competencia']) ? $this->formatCompetenciaDate($_POST['competencia']) : $document['competencia'],
                'validade_legal' => isset($_POST['validade_legal']) ? $_POST['validade_legal'] : $document['validade_legal'],
                'vinculado_a' => isset($_POST['vinculado_a']) && !empty($_POST['vinculado_a']) ? $_POST['vinculado_a'] : $document['vinculado_a']
            ];
            
            if (!$this->validator->validate($data, $rules)) {
                Response::validation($this->validator->getErrors());
                return;
            }
            
            // Validar status se fornecido
            $validStatuses = ['rascunho', 'enviado', 'assinado', 'cancelado', 'arquivo'];
            if (isset($_POST['status']) && !in_array($_POST['status'], $validStatuses)) {
                Response::validation(['status' => ['Status inválido']]);
                return;
            }
            
            $updateData = [
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'],
                'empresa_id' => $data['empresa_id'],
                'filial_id' => $data['filial_id'],
                'status' => $data['status'],
                'tipo_documento_id' => $data['tipo_documento_id'],
                'prazo_assinatura' => $data['prazo_assinatura'],
                'competencia' => $data['competencia'],
                'validade_legal' => $data['validade_legal'],
                'vinculado_a' => $data['vinculado_a'],
                'solicitar_assinatura' => isset($_POST['solicitar_assinatura']) ? (bool)$_POST['solicitar_assinatura'] : $document['solicitar_assinatura']
            ];
            
            // Verificar se o documento possui hash_acesso, se não tiver, gerar um
            if (empty($document['hash_acesso'])) {
                $updateData['hash_acesso'] = $this->generateAccessHash();
            }
            
            // Processar novo arquivo se enviado
            if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['arquivo'];
                $allowedTypes = $this->getAllowedMimeTypes();
                $maxSize = $this->getMaxFileSize();
                
                if (!in_array($file['type'], $allowedTypes)) {
                    Response::validation(['arquivo' => ['Tipo de arquivo não permitido']]);
                    return;
                }
                
                if ($file['size'] > $maxSize) {
                    $maxSizeMB = $maxSize / (1024 * 1024);
                    Response::validation(['arquivo' => ["Arquivo muito grande (máximo {$maxSizeMB}MB)"]]);
                    return;
                }
                
                // Criar diretório se não existir
                $uploadDir = __DIR__ . '/../uploads/documents/';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                // Gerar nome único para o arquivo
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = uniqid() . '_' . time() . '.' . $extension;
                $filePath = $uploadDir . $fileName;
                
                // Verificar se é um arquivo temporário criado pelo parser multipart ou upload normal
                $moveSuccess = false;
                if (is_uploaded_file($file['tmp_name'])) {
                    // Arquivo de upload normal (POST)
                    $moveSuccess = move_uploaded_file($file['tmp_name'], $filePath);
                } else {
                    // Arquivo temporário criado pelo parser multipart (PUT)
                    $moveSuccess = copy($file['tmp_name'], $filePath);
                    // Limpar arquivo temporário
                    if (file_exists($file['tmp_name'])) {
                        unlink($file['tmp_name']);
                    }
                }
                
                if ($moveSuccess) {
                    // Remover arquivo antigo
                    $oldFilePath = __DIR__ . '/../' . $document['caminho_arquivo'];
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                    
                    $updateData['nome_arquivo'] = $file['name'];
                    $updateData['caminho_arquivo'] = 'uploads/documents/' . $fileName;
                    $updateData['tamanho_arquivo'] = $file['size'];
                    $updateData['tipo_arquivo'] = $file['type'];
                    
                    // Gerar novo hash quando o arquivo é modificado
                    $updateData['hash_acesso'] = $this->generateAccessHash();
                }
            }
            
            // Validar que usuários assinantes não podem definir assinantes
            if ($user['tipo_usuario'] == 3 && isset($_POST['assinantes']) && !empty($_POST['assinantes'])) {
                Response::forbidden('Usuários assinantes não podem definir assinantes para documentos');
                return;
            }
            
            // Validar que usuários assinantes não podem alterar solicitar_assinatura para verdadeiro
            if ($user['tipo_usuario'] == 3 && isset($_POST['solicitar_assinatura']) && $_POST['solicitar_assinatura'] && !$document['solicitar_assinatura']) {
                Response::forbidden('Usuários assinantes não podem solicitar assinatura para documentos');
                return;
            }
            
            // Validar que apenas Admin Empresa e Super Admin podem solicitar assinatura
            if (isset($_POST['solicitar_assinatura']) && $_POST['solicitar_assinatura'] && !in_array($user['tipo_usuario'], [1, 2])) {
                Response::forbidden('Apenas administradores podem solicitar assinatura de documentos');
                return;
            }
            
            // Processar assinantes se fornecidos
            $assinantes = [];
            if (isset($_POST['assinantes']) && !empty($_POST['assinantes'])) {
                $assinantes = json_decode($_POST['assinantes'], true);
                if (!is_array($assinantes)) {
                    $assinantes = [];
                }
            }
            
            // Processar assinantes solicitados se fornecidos
            $assinantesSolicitados = [];
            if (isset($_POST['assinantes_solicitados']) && !empty($_POST['assinantes_solicitados'])) {
                $assinantesSolicitados = json_decode($_POST['assinantes_solicitados'], true);
                if (!is_array($assinantesSolicitados)) {
                    $assinantesSolicitados = [];
                }
            }
            
            if ($this->document->update($id, $updateData)) {
                // Atualizar assinantes se fornecidos
                if (!empty($assinantes)) {
                    // Remover assinantes existentes
                    $this->documentAssinante->deleteByDocumentId($id);
                    
                    // Criar novos assinantes
                    foreach ($assinantes as $assinante) {
                        $assinanteData = [
                            'documento_id' => $id,
                            'usuario_id' => $assinante,
                            'status' => 'pendente',
                            'observacoes' => null
                        ];
                        
                        $this->documentAssinante->create($assinanteData);
                    }
                }
                
                // Atualizar assinantes solicitados se fornecidos
                if (!empty($assinantesSolicitados)) {
                    // Remover assinantes solicitados existentes
                    $this->documentAssinanteSolicitado->deleteByDocumentId($id);
                    
                    // Criar novos assinantes solicitados
                    foreach ($assinantesSolicitados as $assinante) {
                        $assinanteData = [
                            'documento_id' => $id,
                            'usuario_id' => $assinante,
                            'status' => 'pendente',
                            'data_solicitacao' => date('Y-m-d H:i:s')
                        ];
                        
                        $this->documentAssinanteSolicitado->create($assinanteData);
                    }
                }
                
                $updatedDocument = $this->document->findById($id);
                Response::success($updatedDocument, 'Documento atualizado com sucesso');
            } else {
                Response::error('Erro ao atualizar documento');
            }
        } catch (Exception $e) {
            Response::error('Erro ao atualizar documento: ' . $e->getMessage());
        }
    }
    
    public function destroy($id) {
        try {
            $user = JWT::requireAuth();
            
            $document = $this->document->findById($id);
            
            if (!$document) {
                Response::notFound('Documento não encontrado');
                return;
            }
            
            // Verificar permissões
            if ($user['tipo_usuario'] == 2 && $document['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            if ($user['tipo_usuario'] == 3) {
                if ($document['criado_por'] != $user['user_id']) {
                    Response::forbidden('Acesso negado');
                    return;
                }
            }
            
            // Remover arquivo físico antes de excluir do banco
            if (!empty($document['caminho_arquivo'])) {
                $filePath = __DIR__ . '/../' . $document['caminho_arquivo'];
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            
            if ($this->document->delete($id)) {
                Response::success(null, 'Documento excluído com sucesso');
            } else {
                Response::error('Erro ao excluir documento');
            }
        } catch (Exception $e) {
            Response::error('Erro ao excluir documento: ' . $e->getMessage());
        }
    }
    
    public function download($hash) {
        try {
            $user = JWT::requireAuth();
            
            $document = $this->document->findByHash($hash);
            
            if (!$document) {
                Response::notFound('Documento não encontrado');
                return;
            }
            
            // Verificar permissões baseadas no tipo de usuário
            $hasAccess = $this->checkDocumentAccess($user, $document);
            
            if (!$hasAccess) {
                Response::forbidden('Acesso negado ao documento');
                return;
            }
            
            $filePath = __DIR__ . '/../' . $document['caminho_arquivo'];
            
            if (!file_exists($filePath)) {
                Response::notFound('Arquivo não encontrado');
                return;
            }
            
            // Definir headers para download
            header('Content-Type: ' . $document['tipo_arquivo']);
            header('Content-Disposition: attachment; filename="' . $document['nome_arquivo'] . '"');
            header('Content-Length: ' . filesize($filePath));
            
            // Enviar arquivo
            readfile($filePath);
            exit;
        } catch (Exception $e) {
            Response::error('Erro ao baixar documento: ' . $e->getMessage());
        }
    }
    
    public function view($id) {
        try {
            $user = JWT::requireAuth();
            
            $document = $this->document->findById($id);
            
            if (!$document) {
                Response::notFound('Documento não encontrado');
                return;
            }
            
            // Verificar permissões
            if ($user['tipo_usuario'] == 2 && $document['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            if ($user['tipo_usuario'] == 3) {
                if ($document['empresa_id'] != $user['empresa_id'] || 
                    ($user['filial_id'] && $document['filial_id'] != $user['filial_id'])) {
                    Response::forbidden('Acesso negado');
                    return;
                }
            }
            
            $filePath = __DIR__ . '/../' . $document['caminho_arquivo'];
            
            if (!file_exists($filePath)) {
                Response::notFound('Arquivo não encontrado');
                return;
            }
            
            $fileExtension = strtolower(pathinfo($document['nome_arquivo'], PATHINFO_EXTENSION));
            
            // Para arquivos de texto, retornar o conteúdo como JSON
            if ($fileExtension === 'txt') {
                $content = file_get_contents($filePath);
                Response::success([
                    'type' => 'text',
                    'content' => $content,
                    'filename' => $document['nome_arquivo']
                ]);
                return;
            }
            
            // Para PDFs e outros arquivos, servir diretamente para visualização
            header('Content-Type: ' . $document['tipo_arquivo']);
            header('Content-Disposition: inline; filename="' . $document['nome_arquivo'] . '"');
            header('Content-Length: ' . filesize($filePath));
            header('Cache-Control: no-cache, must-revalidate');
            header('Pragma: no-cache');
            header('X-Content-Type-Options: nosniff');
            
            // Enviar arquivo
            readfile($filePath);
            exit;
        } catch (Exception $e) {
            Response::error('Erro ao visualizar documento: ' . $e->getMessage());
        }
    }
    
    public function stats() {
        try {
            $user = JWT::requireAuth();
            
            $filters = [];
            
            // Filtrar por empresa/filial baseado no tipo de usuário
            if ($user['tipo_usuario'] == 2) { // Admin da empresa
                $filters['empresa_id'] = $user['empresa_id'];
            } elseif ($user['tipo_usuario'] == 3) { // Assinante
                $filters['empresa_id'] = $user['empresa_id'];
                if ($user['filial_id']) {
                    $filters['filial_id'] = $user['filial_id'];
                }
            }
            
            $stats = $this->document->getStats($filters);
            
            Response::success($stats);
        } catch (Exception $e) {
            Response::error('Erro ao buscar estatísticas: ' . $e->getMessage());
        }
    }
    
    public function select() {
        try {
            $user = JWT::requireAuth();
            
            $filters = [];
            
            // Filtrar por empresa/filial baseado no tipo de usuário
            if ($user['tipo_usuario'] == 2) { // Admin da empresa
                $filters['empresa_id'] = $user['empresa_id'];
            } elseif ($user['tipo_usuario'] == 3) { // Assinante
                $filters['empresa_id'] = $user['empresa_id'];
                if ($user['filial_id']) {
                    $filters['filial_id'] = $user['filial_id'];
                }
            }
            
            $documents = $this->document->getForSelect($filters);
            
            Response::success($documents);
        } catch (Exception $e) {
            Response::error('Erro ao buscar documentos: ' . $e->getMessage());
        }
    }
    
    public function updateStatus($id) {
        try {
            $user = JWT::requireAuth();
            
            $document = $this->document->findById($id);
            
            if (!$document) {
                Response::notFound('Documento não encontrado');
                return;
            }
            
            // Verificar permissões
            if ($user['tipo_usuario'] == 2 && $document['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            if ($user['tipo_usuario'] == 3) {
                if ($document['criado_por'] != $user['user_id']) {
                    Response::forbidden('Acesso negado');
                    return;
                }
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $status = $input['status'] ?? '';
            
            $allowedStatuses = ['rascunho', 'enviado', 'assinado', 'cancelado'];
            
            if (!in_array($status, $allowedStatuses)) {
                Response::validation(['status' => ['Status inválido']]);
                return;
            }
            
            if ($this->document->updateStatus($id, $status)) {
                $updatedDocument = $this->document->findById($id);
                Response::success($updatedDocument, 'Status atualizado com sucesso');
            } else {
                Response::error('Erro ao atualizar status');
            }
        } catch (Exception $e) {
            Response::error('Erro ao atualizar status: ' . $e->getMessage());
        }
    }

    private function parseMultipartFormData($input) {
        // Obter o boundary do Content-Type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        preg_match('/boundary=(.*)$/', $contentType, $matches);
        if (!isset($matches[1])) {
            return;
        }
        
        $boundary = '--' . $matches[1];
        $parts = explode($boundary, $input);
        
        foreach ($parts as $part) {
            if (empty(trim($part)) || $part === '--') {
                continue;
            }
            
            // Separar headers do conteúdo
            $sections = explode("\r\n\r\n", $part, 2);
            if (count($sections) !== 2) {
                continue;
            }
            
            $headers = $sections[0];
            $content = rtrim($sections[1], "\r\n");
            
            // Extrair o nome do campo
            if (preg_match('/name="([^"]+)"/', $headers, $nameMatches)) {
                $fieldName = $nameMatches[1];
                
                // Verificar se é um arquivo
                if (strpos($headers, 'filename=') !== false) {
                    // Processar arquivo
                    if (preg_match('/filename="([^"]+)"/', $headers, $filenameMatches)) {
                        $filename = $filenameMatches[1];
                        
                        // Extrair Content-Type se disponível
                        $contentType = 'application/octet-stream';
                        if (preg_match('/Content-Type: (.+)/', $headers, $typeMatches)) {
                            $contentType = trim($typeMatches[1]);
                        }
                        
                        // Criar entrada em $_FILES
                        $_FILES[$fieldName] = [
                            'name' => $filename,
                            'type' => $contentType,
                            'size' => strlen($content),
                            'tmp_name' => $this->createTempFile($content),
                            'error' => UPLOAD_ERR_OK
                        ];
                    }
                } else {
                    // Campo de texto normal
                    $_POST[$fieldName] = $content;
                }
            }
        }
    }
    
    private function createTempFile($content) {
        $tempFile = tempnam(sys_get_temp_dir(), 'upload_');
        file_put_contents($tempFile, $content);
        return $tempFile;
    }
    
    private function checkDocumentAccess($user, $document) {
        // Super administrador (tipo 1) tem acesso a tudo
        if ($user['tipo_usuario'] == 1) {
            return true;
        }
        
        // Administrador (tipo 2) pode acessar documentos da sua empresa
        if ($user['tipo_usuario'] == 2) {
            return $document['empresa_id'] == $user['empresa_id'];
        }
        
        // Usuário assinante (tipo 3) precisa verificar se tem acesso ao documento
        if ($user['tipo_usuario'] == 3) {
            // Verificar se pertence à mesma empresa
            if ($document['empresa_id'] != $user['empresa_id']) {
                return false;
            }
            
            // Verificar se pertence à mesma filial (se o usuário tem filial específica)
            if ($user['filial_id'] && $document['filial_id'] != $user['filial_id']) {
                return false;
            }
            
            // Verificar se o usuário está na lista de assinantes do documento
            if (isset($document['assinantes']) && is_array($document['assinantes'])) {
                foreach ($document['assinantes'] as $assinante) {
                    if ($assinante['usuario_id'] == $user['user_id']) {
                        return true;
                    }
                }
            }
            
            // Se não está na lista de assinantes, não tem acesso
            return false;
        }
        
        return false;
    }
    
    /**
     * Busca todos os tipos de documentos ativos
     */
    public function getDocumentTypes() {
        try {
            JWT::requireAuth();
            
            $types = $this->document->getDocumentTypes();
            Response::success($types);
        } catch (Exception $e) {
            Response::error('Erro ao buscar tipos de documentos: ' . $e->getMessage());
        }
    }
    
    /**
     * Processa assinatura de documento solicitado
     */
    public function signDocument($documentoId) {
        try {
            $user = JWT::requireAuth();
            
            // Apenas usuários assinantes podem assinar documentos
            if ($user['tipo_usuario'] != 3) {
                Response::forbidden('Apenas usuários assinantes podem assinar documentos');
                return;
            }
            
            // Processar dados do FormData
            $tipoAssinatura = $_POST['tipo_assinatura'] ?? '';
            $observacoes = $_POST['observacoes'] ?? '';
            $posicaoX = $_POST['posicao_x'] ?? null;
            $posicaoY = $_POST['posicao_y'] ?? null;
            $pagina = $_POST['pagina'] ?? 1;
            
            // Validar dados obrigatórios
            $errors = [];
            if (empty($documentoId)) {
                $errors['documento_id'] = ['ID do documento é obrigatório'];
            }
            if (empty($tipoAssinatura) || !in_array($tipoAssinatura, ['eletronica', 'digital'])) {
                $errors['tipo_assinatura'] = ['Tipo de assinatura inválido'];
            }
            
            // Validar coordenadas de posição
            if ($posicaoX === null || $posicaoY === null) {
                $errors['posicao'] = ['Posição da assinatura é obrigatória'];
            } elseif (!is_numeric($posicaoX) || !is_numeric($posicaoY) || $posicaoX < 0 || $posicaoY < 0) {
                $errors['posicao'] = ['Coordenadas de posição inválidas'];
            }
            
            if (!is_numeric($pagina) || $pagina < 1) {
                $errors['pagina'] = ['Número da página inválido'];
            }
            
            // Validações específicas por tipo de assinatura
            if ($tipoAssinatura === 'eletronica') {
                $assinaturaData = $_POST['assinatura_data'] ?? '';
                if (empty($assinaturaData)) {
                    $errors['assinatura_data'] = ['Dados da assinatura eletrônica são obrigatórios'];
                }
            } elseif ($tipoAssinatura === 'digital') {
                $tipoCertificado = $_POST['tipo_certificado'] ?? 'arquivo';
                $senha_certificado = $_POST['senha_certificado'] ?? '';
                
                if (empty($senha_certificado)) {
                    $errors['senha_certificado'] = ['Senha do certificado é obrigatória'];
                }
                
                if ($tipoCertificado === 'arquivo') {
                    if (!isset($_FILES['certificado']) || $_FILES['certificado']['error'] !== UPLOAD_ERR_OK) {
                        $errors['certificado'] = ['Certificado digital é obrigatório'];
                    }
                } elseif ($tipoCertificado === 'instalado') {
                    $certificadoInstalado = $_POST['certificado_instalado'] ?? '';
                    if (empty($certificadoInstalado)) {
                        $errors['certificado_instalado'] = ['Certificado instalado é obrigatório'];
                    }
                } else {
                    $errors['tipo_certificado'] = ['Tipo de certificado inválido'];
                }
            }
            
            if (!empty($errors)) {
                Response::validation($errors);
                return;
            }
            
            // Buscar o documento
            $document = $this->document->findById($documentoId);
            if (!$document) {
                Response::notFound('Documento não encontrado');
                return;
            }
            
            // Verificar se o documento solicita assinatura
            if (!$document['solicitar_assinatura']) {
                Response::validation(['documento_id' => ['Este documento não solicita assinatura']]);
                return;
            }
            
            // Verificar se o usuário está na lista de assinantes solicitados
            $assinanteSolicitado = $this->documentAssinanteSolicitado->findByDocumentAndUser($documentoId, $user['user_id']);
            if (!$assinanteSolicitado) {
                Response::forbidden('Você não está autorizado a assinar este documento');
                return;
            }
            
            // Verificar se já foi assinado
            if ($assinanteSolicitado['status'] === 'assinado') {
                Response::validation(['documento_id' => ['Você já assinou este documento']]);
                return;
            }
            
            // Verificar se foi rejeitado
            if ($assinanteSolicitado['status'] === 'rejeitado') {
                Response::validation(['documento_id' => ['Você rejeitou a assinatura deste documento']]);
                return;
            }
            
            // Processar assinatura baseada no tipo
            $signatureResult = null;
            $positionData = [
                'x' => (float)$posicaoX,
                'y' => (float)$posicaoY,
                'page' => (int)$pagina
            ];
            
            if ($tipoAssinatura === 'digital') {
                $tipoCertificado = $_POST['tipo_certificado'] ?? 'arquivo';
                if ($tipoCertificado === 'arquivo') {
                    $signatureResult = $this->processDigitalSignature($document, $_FILES['certificado'], $_POST['senha_certificado'], $user, $positionData);
                } elseif ($tipoCertificado === 'instalado') {
                    $certificadoInstalado = json_decode($_POST['certificado_instalado'], true);
                    $signatureResult = $this->processInstalledCertificateSignature($document, $certificadoInstalado, $_POST['senha_certificado'], $user, $positionData);
                }
            } elseif ($tipoAssinatura === 'eletronica') {
                $signatureResult = $this->processElectronicSignature($document, $_POST['assinatura_data'], $user, $positionData);
            }
            
            if (!$signatureResult['success']) {
                Response::validation(['assinatura' => [$signatureResult['error']]]);
                return;
            }
            
            // Atualizar status do assinante solicitado
            $updateData = [
                'status' => 'assinado',
                'data_assinatura' => date('Y-m-d H:i:s'),
                'tipo_assinatura' => $tipoAssinatura,
                'observacoes' => $observacoes
            ];
            
            // Adicionar dados específicos da assinatura
            if (isset($signatureResult['signature_path'])) {
                $updateData['assinatura_path'] = $signatureResult['signature_path'];
            }
            if (isset($signatureResult['certificate_info'])) {
                $updateData['certificado_info'] = json_encode($signatureResult['certificate_info']);
            }
            
            if ($this->documentAssinanteSolicitado->updateStatus($assinanteSolicitado['id'], $updateData)) {
                // Verificar se todos os assinantes solicitados já assinaram
                $todosAssinantes = $this->documentAssinanteSolicitado->findByDocument($documentoId);
                $todosAssinaram = true;
                
                foreach ($todosAssinantes as $assinante) {
                    if ($assinante['status'] !== 'assinado') {
                        $todosAssinaram = false;
                        break;
                    }
                }
                
                // Se todos assinaram, atualizar status do documento
                if ($todosAssinaram) {
                    $this->document->updateStatus($documentoId, 'assinado');
                }
                
                Response::success(null, 'Documento assinado com sucesso');
            } else {
                Response::error('Erro ao processar assinatura');
            }
            
        } catch (Exception $e) {
            Response::error('Erro ao processar assinatura: ' . $e->getMessage());
        }
    }
    
    /**
     * Processa assinatura digital usando certificado
     */
    private function processDigitalSignature($document, $certificateFile, $password, $user, $positionData = null) {
        try {
            // Validar certificado sem armazená-lo
            $certificateInfo = $this->validateCertificate($certificateFile, $password);
            if (!$certificateInfo['valid']) {
                return ['success' => false, 'error' => $certificateInfo['error']];
            }
            
            // Aplicar assinatura digital ao documento (alterar propriedades)
            $signResult = $this->applyDigitalSignatureToDocument($document, $certificateInfo, $user, $positionData);
            if (!$signResult['success']) {
                return ['success' => false, 'error' => $signResult['error']];
            }
            
            return [
                'success' => true,
                'certificate_info' => [
                    'subject' => $certificateInfo['subject'],
                    'issuer' => $certificateInfo['issuer'],
                    'valid_from' => $certificateInfo['valid_from'],
                    'valid_to' => $certificateInfo['valid_to'],
                    'serial_number' => $certificateInfo['serial_number']
                ]
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao processar assinatura digital: ' . $e->getMessage()];
        }
    }
    
    /**
     * Processa assinatura eletrônica (lousa digital)
     */
    private function processElectronicSignature($document, $signatureData, $user, $positionData = null) {
        try {
            // Decodificar dados da assinatura (base64)
            $signatureInfo = json_decode($signatureData, true);
            if (!$signatureInfo) {
                return ['success' => false, 'error' => 'Dados de assinatura inválidos'];
            }
            
            // Salvar assinatura eletrônica
            $saveResult = $this->saveElectronicSignature($signatureInfo, $document['id'], $user['user_id'], $positionData);
            if (!$saveResult['success']) {
                return ['success' => false, 'error' => $saveResult['error']];
            }
            
            return [
                'success' => true,
                'signature_path' => $saveResult['path']
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao processar assinatura eletrônica: ' . $e->getMessage()];
        }
    }
    
    /**
     * Valida certificado digital sem armazená-lo
     */
    private function validateCertificate($certificateFile, $password) {
        try {
            // Verificar se houve erro no upload
            if ($certificateFile['error'] !== UPLOAD_ERR_OK) {
                return ['valid' => false, 'error' => 'Erro no upload do certificado'];
            }
            
            // Verificar tamanho do arquivo (máximo 5MB)
            if ($certificateFile['size'] > 5 * 1024 * 1024) {
                return ['valid' => false, 'error' => 'Certificado muito grande. Máximo 5MB'];
            }
            
            // Verificar extensão do arquivo
            $allowedExtensions = ['p12', 'pfx', 'pem', 'crt', 'cer'];
            $fileExtension = strtolower(pathinfo($certificateFile['name'], PATHINFO_EXTENSION));
            
            if (!in_array($fileExtension, $allowedExtensions)) {
                return ['valid' => false, 'error' => 'Formato de certificado inválido'];
            }
            
            // Ler conteúdo do certificado temporariamente
            $certificateContent = file_get_contents($certificateFile['tmp_name']);
            
            // Validar certificado baseado na extensão
            if (in_array($fileExtension, ['p12', 'pfx'])) {
                // Certificado PKCS#12
                $certs = [];
                if (!openssl_pkcs12_read($certificateContent, $certs, $password)) {
                    return ['valid' => false, 'error' => 'Senha do certificado inválida ou certificado corrompido'];
                }
                
                $certInfo = openssl_x509_parse($certs['cert']);
            } else {
                // Certificado PEM/CRT/CER
                $certInfo = openssl_x509_parse($certificateContent);
                if (!$certInfo) {
                    return ['valid' => false, 'error' => 'Certificado inválido ou corrompido'];
                }
            }
            
            // Verificar se o certificado ainda é válido
            $now = time();
            if ($certInfo['validFrom_time_t'] > $now) {
                return ['valid' => false, 'error' => 'Certificado ainda não é válido'];
            }
            if ($certInfo['validTo_time_t'] < $now) {
                return ['valid' => false, 'error' => 'Certificado expirado'];
            }
            
            return [
                'valid' => true,
                'subject' => $certInfo['subject']['CN'] ?? 'N/A',
                'issuer' => $certInfo['issuer']['CN'] ?? 'N/A',
                'valid_from' => date('Y-m-d H:i:s', $certInfo['validFrom_time_t']),
                'valid_to' => date('Y-m-d H:i:s', $certInfo['validTo_time_t']),
                'serial_number' => $certInfo['serialNumber'] ?? 'N/A',
                'certificate_data' => $certificateContent
            ];
            
        } catch (Exception $e) {
            return ['valid' => false, 'error' => 'Erro ao validar certificado: ' . $e->getMessage()];
        }
    }

    /**
     * Aplica assinatura digital ao documento (altera propriedades do arquivo)
     */
    private function applyDigitalSignatureToDocument($document, $certificateInfo, $user, $positionData = null) {
        try {
            error_log('DEBUG: Iniciando applyDigitalSignatureToDocument');
            $documentPath = __DIR__ . '/../' . $document['caminho_arquivo'];
            error_log('DEBUG: Caminho do documento: ' . $documentPath);
            
            if (!file_exists($documentPath)) {
                error_log('DEBUG: Arquivo não encontrado: ' . $documentPath);
                return ['success' => false, 'error' => 'Arquivo do documento não encontrado'];
            }
            
            // Buscar dados completos do usuário (necessário para ambos os tipos de arquivo)
            error_log('DEBUG: Buscando dados do usuário ID: ' . $user['user_id']);
            $userModel = new User();
            $fullUser = $userModel->findById($user['user_id']);
            error_log('DEBUG: Dados do usuário encontrados: ' . ($fullUser ? 'sim' : 'não'));
            
            // Verificar se é um PDF (mais comum para assinatura digital)
            $fileExtension = strtolower(pathinfo($document['nome_arquivo'], PATHINFO_EXTENSION));
            
            if ($fileExtension === 'pdf') {
                // Para PDFs, tentar adicionar assinatura visual, mas se falhar, criar apenas metadados
                
                $signatureMetadata = [
                    'signer_name' => $certificateInfo['subject'],
                    'signer_certificate_issuer' => $certificateInfo['issuer'],
                    'signature_date' => date('Y-m-d H:i:s'),
                    'certificate_serial' => $certificateInfo['serial_number'],
                    'user_id' => $user['user_id'],
                    'user_name' => $fullUser['nome'] ?? 'Usuário não encontrado'
                ];
                
                // Tentar criar uma versão assinada do documento
                error_log('DEBUG: Tentando criar PDF assinado');
                $signedDocumentPath = $this->createSignedPDF($documentPath, $signatureMetadata, $certificateInfo);
                error_log('DEBUG: PDF assinado criado: ' . ($signedDocumentPath ? $signedDocumentPath : 'falhou'));
                
                if ($signedDocumentPath) {
                    // Substituir o documento original pelo assinado
                    error_log('DEBUG: Tentando substituir arquivo original');
                    if (rename($signedDocumentPath, $documentPath)) {
                        error_log('DEBUG: Arquivo substituído com sucesso');
                        return ['success' => true, 'signed_document_path' => $document['nome_arquivo']];
                    } else {
                        error_log('DEBUG: Falha ao substituir arquivo original');
                    }
                } else {
                    // Se falhar na criação do PDF assinado, criar arquivo de metadados como fallback
                    error_log('DEBUG: Fallback - criando arquivo de metadados para PDF');
                    $metadataPath = $documentPath . '.signature_metadata.json';
                    $signatureMetadata['document_hash'] = hash_file('sha256', $documentPath);
                    $signatureMetadata['signature_method'] = 'metadata_only';
                    $signatureMetadata['reason'] = 'PDF compression not supported by free FPDI parser';
                    
                    if (file_put_contents($metadataPath, json_encode($signatureMetadata, JSON_PRETTY_PRINT))) {
                        return ['success' => true, 'metadata_path' => $metadataPath];
                    }
                }
            } else {
                // Para outros tipos de arquivo, criar um arquivo de metadados de assinatura
                $metadataPath = $documentPath . '.signature_metadata.json';
                $signatureMetadata = [
                    'document_file' => $document['nome_arquivo'],
                    'signer_name' => $certificateInfo['subject'],
                    'signer_certificate_issuer' => $certificateInfo['issuer'],
                    'signature_date' => date('Y-m-d H:i:s'),
                    'certificate_serial' => $certificateInfo['serial_number'],
                    'user_id' => $user['user_id'],
                    'user_name' => $fullUser['nome'] ?? 'Usuário não encontrado',
                    'document_hash' => hash_file('sha256', $documentPath)
                ];
                
                if (file_put_contents($metadataPath, json_encode($signatureMetadata, JSON_PRETTY_PRINT))) {
                    return ['success' => true, 'metadata_path' => $metadataPath];
                }
            }
            
            return ['success' => false, 'error' => 'Erro ao aplicar assinatura digital ao documento'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao processar assinatura digital: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cria uma versão assinada do PDF com assinatura visual e metadados
     */
    protected function createSignedPDF($originalPath, $metadata, $certificateInfo) {
        try {
            error_log('DEBUG: createSignedPDF - Iniciando');
            error_log('DEBUG: createSignedPDF - Arquivo original: ' . $originalPath);
            error_log('DEBUG: createSignedPDF - Arquivo existe: ' . (file_exists($originalPath) ? 'sim' : 'não'));
            
            require_once __DIR__ . '/../vendor/autoload.php';
            error_log('DEBUG: createSignedPDF - Autoload carregado');
            
            $signedPath = str_replace('.pdf', '_signed_' . time() . '.pdf', $originalPath);
            error_log('DEBUG: createSignedPDF - Caminho do arquivo assinado: ' . $signedPath);
            
            // Criar nova instância do FPDI (que estende TCPDF)
            error_log('DEBUG: createSignedPDF - Criando instância FPDI');
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
            error_log('DEBUG: createSignedPDF - FPDI criado com sucesso');
            
            // Configurar propriedades do documento
            $pdf->SetCreator('DocGest - Sistema de Gestão de Documentos');
            $pdf->SetAuthor($metadata['signer_name']);
            $pdf->SetTitle('Documento Assinado Digitalmente');
            $pdf->SetSubject('Assinatura Digital');
            error_log('DEBUG: createSignedPDF - Propriedades configuradas');
            
            // Importar páginas do PDF original
            error_log('DEBUG: createSignedPDF - Tentando importar páginas do PDF');
            $pageCount = $pdf->setSourceFile($originalPath);
            error_log('DEBUG: createSignedPDF - Número de páginas: ' . $pageCount);
            
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                error_log('DEBUG: createSignedPDF - Processando página: ' . $pageNo);
                // Importar página
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
                
                // Adicionar assinatura visual apenas na última página
                if ($pageNo == $pageCount) {
                    error_log('DEBUG: createSignedPDF - Adicionando assinatura visual na última página');
                    $this->addVisualSignature($pdf, $metadata, $certificateInfo);
                }
            }
            
            // Salvar PDF assinado
            error_log('DEBUG: createSignedPDF - Salvando PDF assinado');
            $pdf->Output($signedPath, 'F');
            error_log('DEBUG: createSignedPDF - PDF salvo com sucesso');
            
            return $signedPath;
            
        } catch (Exception $e) {
            error_log('ERRO createSignedPDF: ' . $e->getMessage());
            error_log('ERRO createSignedPDF Stack Trace: ' . $e->getTraceAsString());
            return false;
        }
    }
    
    /**
     * Adiciona assinatura visual ao PDF (versão compacta e discreta)
     */
    protected function addVisualSignature($pdf, $metadata, $certificateInfo) {
        try {
            // Posição da assinatura (canto inferior direito, mais compacta)
            $x = $pdf->getPageWidth() - 55;
            $y = $pdf->getPageHeight() - 25;
            
            // Configurar fonte menor e mais discreta
            $pdf->SetFont('helvetica', '', 6);
            $pdf->SetTextColor(100, 100, 100); // Cor cinza mais discreta
            
            // Desenhar caixa da assinatura mais fina
            $pdf->SetDrawColor(150, 150, 150);
            $pdf->SetLineWidth(0.2);
            $pdf->Rect($x, $y, 50, 20);
            
            // Título da assinatura mais compacto
            $pdf->SetFont('helvetica', 'B', 6);
            $pdf->SetXY($x + 1, $y + 1);
            $pdf->Cell(48, 3, 'ASSINADO DIGITALMENTE', 0, 1, 'C');
            
            // Linha separadora mais fina
            $pdf->Line($x + 1, $y + 4, $x + 49, $y + 4);
            
            // Informações essenciais da assinatura (mais compactas)
            $pdf->SetFont('helvetica', '', 5);
            
            // Nome do signatário (uma linha só)
            $pdf->SetXY($x + 1, $y + 5);
            $signerName = $this->truncateText($metadata['signer_name'], 20);
            $pdf->Cell(48, 2, 'Por: ' . $signerName, 0, 1, 'L');
            
            // Data (uma linha só)
            $pdf->SetXY($x + 1, $y + 8);
            $signatureDate = date('d/m/Y H:i', strtotime($metadata['signature_date']));
            $pdf->Cell(48, 2, 'Em: ' . $signatureDate, 0, 1, 'L');
            
            // Hash de validação compacto
            $pdf->SetXY($x + 1, $y + 11);
            $validationHash = substr(hash('sha256', json_encode($metadata)), 0, 12);
            $pdf->Cell(48, 2, 'ID: ' . strtoupper($validationHash), 0, 1, 'L');
            
            // Ícone de certificado digital menor
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetTextColor(0, 100, 0);
            $pdf->SetXY($x + 42, $y + 15);
            $pdf->Cell(6, 3, '🔒', 0, 1, 'C');
            
        } catch (Exception $e) {
            error_log('Erro ao adicionar assinatura visual: ' . $e->getMessage());
        }
    }
    
    /**
     * Trunca texto para caber na assinatura visual
     */
    protected function truncateText($text, $maxLength) {
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength - 3) . '...';
    }
    
    /**
     * Salva assinatura eletrônica na base de dados e arquivo no servidor
     */
    private function saveElectronicSignature($signatureInfo, $documentId, $userId, $positionData = null) {
        try {
            // Criar diretório de assinaturas se não existir
            $signaturesDir = __DIR__ . '/../uploads/signatures/';
            if (!is_dir($signaturesDir)) {
                mkdir($signaturesDir, 0755, true);
            }
            
            // Gerar nome único para o arquivo de assinatura
            $signatureFileName = 'signature_' . $documentId . '_' . $userId . '_' . time() . '.png';
            $signatureFilePath = $signaturesDir . $signatureFileName;
            
            // Processar dados da assinatura baseado no tipo
            if (isset($signatureInfo['type']) && $signatureInfo['type'] === 'drawn') {
                // Assinatura desenhada (canvas)
                $imageData = $signatureInfo['data'];
                
                // Remover prefixo data:image/png;base64, se presente
                if (strpos($imageData, 'data:image/png;base64,') === 0) {
                    $imageData = substr($imageData, strlen('data:image/png;base64,'));
                }
                
                // Decodificar e salvar imagem
                $decodedImage = base64_decode($imageData);
                if ($decodedImage && file_put_contents($signatureFilePath, $decodedImage)) {
                    return [
                        'success' => true,
                        'path' => 'uploads/signatures/' . $signatureFileName,
                        'type' => 'drawn'
                    ];
                }
            } elseif (isset($signatureInfo['type']) && $signatureInfo['type'] === 'typed') {
                // Assinatura digitada (texto com fonte)
                $text = $signatureInfo['text'] ?? '';
                $font = $signatureInfo['font'] ?? 'Arial';
                $fontSize = $signatureInfo['fontSize'] ?? 24;
                
                // Criar imagem da assinatura digitada
                $imageCreated = $this->createTypedSignatureImage($text, $font, $fontSize, $signatureFilePath);
                
                if ($imageCreated) {
                    return [
                        'success' => true,
                        'path' => 'uploads/signatures/' . $signatureFileName,
                        'type' => 'typed',
                        'text' => $text,
                        'font' => $font
                    ];
                }
            }
            
            return ['success' => false, 'error' => 'Erro ao salvar assinatura eletrônica'];
            
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Erro ao processar assinatura eletrônica: ' . $e->getMessage()];
        }
    }
    
    /**
     * Cria imagem de assinatura digitada
     */
    private function createTypedSignatureImage($text, $font, $fontSize, $outputPath) {
        try {
            // Dimensões da imagem
            $width = 400;
            $height = 100;
            
            // Criar imagem
            $image = imagecreate($width, $height);
            
            // Definir cores
            $backgroundColor = imagecolorallocate($image, 255, 255, 255); // Branco
            $textColor = imagecolorallocate($image, 0, 0, 0); // Preto
            
            // Adicionar texto
            $fontPath = $this->getFontPath($font);
            if ($fontPath && function_exists('imagettftext')) {
                // Usar fonte TTF se disponível
                imagettftext($image, $fontSize, 0, 20, 60, $textColor, $fontPath, $text);
            } else {
                // Usar fonte padrão
                imagestring($image, 5, 20, 30, $text, $textColor);
            }
            
            // Salvar imagem
            $result = imagepng($image, $outputPath);
            imagedestroy($image);
            
            return $result;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Obtém caminho da fonte baseado no nome
     */
    private function getFontPath($fontName) {
        $fontsDir = __DIR__ . '/../assets/fonts/';
        $fontFiles = [
            'Arial' => 'arial.ttf',
            'Times' => 'times.ttf',
            'Courier' => 'courier.ttf',
            'Helvetica' => 'helvetica.ttf'
        ];
        
        if (isset($fontFiles[$fontName])) {
            $fontPath = $fontsDir . $fontFiles[$fontName];
            if (file_exists($fontPath)) {
                return $fontPath;
            }
        }
        
        return null;
    }
    
    /**
     * Gera um hash único para acesso ao documento
     */
    /**
     * Processa assinatura com certificado instalado no Windows Certificate Store
     */
    private function processInstalledCertificateSignature($document, $certificateData, $password, $user, $positionData = null) {
        try {
            // Validar dados do certificado
            if (!isset($certificateData['thumbprint']) || !isset($certificateData['subject'])) {
                return ['success' => false, 'error' => 'Dados do certificado instalado inválidos'];
            }
            
            // Simular validação do certificado instalado
            // Em um ambiente real, seria necessário usar uma biblioteca específica
            // para acessar o Windows Certificate Store
            $certificateInfo = [
                'subject' => $certificateData['subject'] ?? 'N/A',
                'issuer' => $certificateData['issuer'] ?? 'N/A',
                'thumbprint' => $certificateData['thumbprint'] ?? 'N/A',
                'valid_from' => $certificateData['validFrom'] ?? null,
                'valid_to' => $certificateData['validTo'] ?? null,
                'serial_number' => $certificateData['serialNumber'] ?? 'N/A',
                'tipo_certificado' => 'instalado'
            ];
            
            // Verificar se o certificado está válido
            if (isset($certificateData['validTo'])) {
                $validTo = strtotime($certificateData['validTo']);
                if ($validTo && $validTo < time()) {
                    return ['success' => false, 'error' => 'Certificado expirado'];
                }
            }
            
            // Aplicar assinatura digital ao documento (alterar propriedades)
            $signResult = $this->applyDigitalSignatureToDocument($document, $certificateInfo, $user);
            if (!$signResult['success']) {
                return ['success' => false, 'error' => $signResult['error']];
            }
            
            // Gerar hash da assinatura (simulado)
            $signatureHash = hash('sha256', $document['id'] . $user['user_id'] . time() . $certificateData['thumbprint']);
            
            // Criar metadados da assinatura
            $signatureMetadata = [
                'timestamp' => date('Y-m-d H:i:s'),
                'user_id' => $user['user_id'],
                'document_id' => $document['id'],
                'certificate_thumbprint' => $certificateData['thumbprint'],
                'signature_hash' => $signatureHash,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ];
            
            return [
                'success' => true,
                'certificate_info' => $certificateInfo,
                'signature_metadata' => $signatureMetadata
            ];
            
        } catch (Exception $e) {
            error_log('Erro ao processar certificado instalado: ' . $e->getMessage());
            return ['success' => false, 'error' => 'Erro interno ao processar certificado instalado'];
        }
    }
    
    private function generateAccessHash() {
        return hash('sha256', uniqid() . microtime(true) . random_bytes(16));
    }
    
    /**
     * Formata a data de competência de YYYY-MM para YYYY-MM-01
     */
    private function formatCompetenciaDate($competencia) {
        if (empty($competencia)) {
            return null;
        }
        
        // Se já está no formato completo (YYYY-MM-DD), retorna como está
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $competencia)) {
            return $competencia;
        }
        
        // Se está no formato YYYY-MM, adiciona -01
        if (preg_match('/^\d{4}-\d{2}$/', $competencia)) {
            return $competencia . '-01';
        }
        
        // Se não está em nenhum formato esperado, retorna null
        return null;
    }
}