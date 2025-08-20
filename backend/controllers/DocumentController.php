<?php

require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/DocumentAssinante.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/JWT.php';

class DocumentController {
    private $document;
    private $documentAssinante;
    private $validator;
    
    public function __construct() {
        $this->document = new Document();
        $this->documentAssinante = new DocumentAssinante();
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
                'status' => $_POST['status'] ?? 'rascunho'
            ];
            
            $validator = new Validator($data);
            $validator->required('titulo', 'Título é obrigatório');
            $validator->required('empresa_id', 'Empresa é obrigatória');
            
            // Validar status se fornecido
            $validStatuses = ['rascunho', 'enviado', 'assinado', 'cancelado'];
            if (!empty($data['status']) && !in_array($data['status'], $validStatuses)) {
                $validator->addError('status', 'Status inválido');
            }
            

            if ($validator->hasErrors()) {
                Response::validation($validator->getErrors());
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
                'criado_por' => $user['user_id'],
                'empresa_id' => $data['empresa_id'],
                'filial_id' => $data['filial_id']
            ];
            
            // Remove campos nulos para evitar erros de constraint
            $documentData = array_filter($documentData, function($value) {
                return $value !== null;
            });
            
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
                'status' => isset($_POST['status']) ? $_POST['status'] : $document['status']
            ];
            
            if (!$this->validator->validate($data, $rules)) {
                Response::validation($this->validator->getErrors());
                return;
            }
            
            // Validar status se fornecido
            $validStatuses = ['rascunho', 'enviado', 'assinado', 'cancelado'];
            if (isset($_POST['status']) && !in_array($_POST['status'], $validStatuses)) {
                Response::validation(['status' => ['Status inválido']]);
                return;
            }
            
            $updateData = [
                'titulo' => $data['titulo'],
                'descricao' => $data['descricao'],
                'empresa_id' => $data['empresa_id'],
                'filial_id' => $data['filial_id'],
                'status' => $data['status']
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
            
            // Processar assinantes se fornecidos
            $assinantes = [];
            if (isset($_POST['assinantes']) && !empty($_POST['assinantes'])) {
                $assinantes = json_decode($_POST['assinantes'], true);
                if (!is_array($assinantes)) {
                    $assinantes = [];
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
                    if ($assinante['usuario_id'] == $user['id']) {
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
     * Gera um hash único para acesso ao documento
     */
    private function generateAccessHash() {
        return hash('sha256', uniqid() . microtime(true) . random_bytes(16));
    }
}