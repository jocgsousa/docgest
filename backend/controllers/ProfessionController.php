<?php

require_once __DIR__ . '/../models/Profession.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class ProfessionController {
    private $professionModel;
    
    public function __construct() {
        $this->professionModel = new Profession();
    }
    
    /**
     * Lista profissões
     */
    public function index() {
        try {
            $currentUser = JWT::requireAdmin();
            
            // Parâmetros de consulta
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $pageSize = isset($_GET['pageSize']) ? min((int)$_GET['pageSize'], 100) : (isset($_GET['page_size']) ? min((int)$_GET['page_size'], 100) : 20);
            
            $filters = [];
            
            // Filtros opcionais
            if (isset($_GET['search']) && $_GET['search'] !== '') {
                $filters['search'] = $_GET['search'];
            }
            
            if (isset($_GET['ativo']) && $_GET['ativo'] !== '') {
                $filters['ativo'] = $_GET['ativo'];
            }
            
            $result = $this->professionModel->list($filters, $page, $pageSize);
            
            Response::paginated(
                $result['data'],
                $result['total'],
                $page,
                $pageSize,
                'Profissões recuperadas com sucesso'
            );
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Lista todas as profissões ativas (para selects)
     */
    public function listAll() {
        try {
            // Permitir acesso para qualquer usuário autenticado
            JWT::requireAuth();
            
            $professions = $this->professionModel->listAll();
            
            Response::success($professions, 'Profissões recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Busca profissão por ID
     */
    public function show($id) {
        try {
            $currentUser = JWT::requireAdmin();
            
            $profession = $this->professionModel->findById($id);
            
            if (!$profession) {
                Response::notFound('Profissão não encontrada');
            }
            
            // Adicionar contagem de usuários
            $profession['usuarios_count'] = $this->professionModel->countUsers($id);
            
            Response::success($profession, 'Profissão recuperada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Cria nova profissão
     */
    public function store() {
        try {
            $currentUser = JWT::requireAdmin();
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação
            $rules = [
                'nome' => 'required|min:2|max:100'
            ];
            
            if (isset($input['descricao'])) {
                $rules['descricao'] = 'max:500';
            }
            
            $validator = new Validator();
            if (!$validator->validate($input, $rules)) {
                Response::validation($validator->getErrors());
                return;
            }
            
            // Criar profissão
            $professionData = [
                'nome' => trim($input['nome']),
                'descricao' => isset($input['descricao']) ? trim($input['descricao']) : null
            ];
            
            $profession = $this->professionModel->create($professionData);
            
            if (!$profession) {
                Response::error('Erro ao criar profissão', 500);
            }
            
            Response::created($profession, 'Profissão criada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Atualiza profissão
     */
    public function update($id) {
        try {
            $currentUser = JWT::requireAdmin();
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Buscar profissão
            $profession = $this->professionModel->findById($id);
            
            if (!$profession) {
                Response::notFound('Profissão não encontrada');
            }
            
            // Validação dos dados de entrada
            $rules = [];
            $dataToValidate = [];
            
            if (isset($input['nome'])) {
                $rules['nome'] = 'required|min:2|max:100';
                $dataToValidate['nome'] = $input['nome'];
            }
            
            if (isset($input['descricao'])) {
                $rules['descricao'] = 'max:500';
                $dataToValidate['descricao'] = $input['descricao'];
            }
            
            if (isset($input['ativo'])) {
                $rules['ativo'] = 'boolean';
                $dataToValidate['ativo'] = $input['ativo'];
            }
            
            if (!empty($rules)) {
                $validator = new Validator();
                if (!$validator->validate($dataToValidate, $rules)) {
                    Response::validation($validator->getErrors());
                    return;
                }
            }
            
            // Preparar dados para atualização
            $updateData = [];
            
            if (isset($input['nome'])) {
                $updateData['nome'] = trim($input['nome']);
            }
            
            if (isset($input['descricao'])) {
                $updateData['descricao'] = trim($input['descricao']);
            }
            
            if (isset($input['ativo'])) {
                $updateData['ativo'] = $input['ativo'] ? 1 : 0;
            }
            
            if (empty($updateData)) {
                Response::error('Nenhum campo válido para atualização', 400);
            }
            
            $updatedProfession = $this->professionModel->update($id, $updateData);
            
            if (!$updatedProfession) {
                Response::error('Erro ao atualizar profissão', 500);
            }
            
            Response::success($updatedProfession, 'Profissão atualizada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Desativa profissão (soft delete)
     */
    public function destroy($id) {
        try {
            $currentUser = JWT::requireAdmin();
            
            // Buscar profissão
            $profession = $this->professionModel->findById($id);
            
            if (!$profession) {
                Response::notFound('Profissão não encontrada');
            }
            
            $result = $this->professionModel->delete($id);
            
            if (!$result) {
                Response::error('Erro ao desativar profissão', 500);
            }
            
            Response::success(null, 'Profissão desativada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Ativa profissão
     */
    public function activate($id) {
        try {
            $currentUser = JWT::requireAdmin();
            
            // Buscar profissão (incluindo inativas)
            $sql = "SELECT * FROM profissoes WHERE id = :id";
            $stmt = $this->professionModel->conn->prepare($sql);
            $stmt->bindParam(':id', $id);
            $stmt->execute();
            $profession = $stmt->fetch();
            
            if (!$profession) {
                Response::notFound('Profissão não encontrada');
            }
            
            $result = $this->professionModel->activate($id);
            
            if (!$result) {
                Response::error('Erro ao ativar profissão', 500);
            }
            
            Response::success(null, 'Profissão ativada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Conta usuários que usam uma profissão
     */
    public function countUsers($id) {
        try {
            $currentUser = JWT::requireAdmin();
            
            $profession = $this->professionModel->findById($id);
            
            if (!$profession) {
                Response::notFound('Profissão não encontrada');
            }
            
            $count = $this->professionModel->countUsers($id);
            
            Response::success(['count' => $count], 'Contagem realizada com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Importa profissões de arquivo CSV
     */
    public function import() {
        try {
            $currentUser = JWT::requireAdmin();
            
            // Verifica se foi enviado um arquivo
            if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
                Response::error('Arquivo CSV não foi enviado corretamente', 400);
            }
            
            $file = $_FILES['csv_file'];
            
            // Valida tipo do arquivo
            $allowedTypes = ['text/csv', 'application/csv', 'text/plain'];
            $fileType = mime_content_type($file['tmp_name']);
            
            if (!in_array($fileType, $allowedTypes) && !str_ends_with($file['name'], '.csv')) {
                Response::error('Arquivo deve ser do tipo CSV', 400);
            }
            
            // Processa o arquivo CSV
            $handle = fopen($file['tmp_name'], 'r');
            if (!$handle) {
                Response::error('Erro ao abrir arquivo CSV', 500);
            }
            
            // Detectar e converter codificação do arquivo
            $fileContent = file_get_contents($file['tmp_name']);
            $encoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
            
            if ($encoding && $encoding !== 'UTF-8') {
                // Converter para UTF-8 e salvar temporariamente
                $utf8Content = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
                $tempFile = tempnam(sys_get_temp_dir(), 'csv_utf8_');
                file_put_contents($tempFile, $utf8Content);
                
                // Fechar handle anterior e abrir o arquivo convertido
                fclose($handle);
                $handle = fopen($tempFile, 'r');
                
                if (!$handle) {
                    Response::error('Erro ao processar arquivo CSV com codificação correta', 500);
                }
            }
            
            $imported = 0;
            $updated = 0;
            $errors = [];
            $lineNumber = 0;
            
            // Pula o cabeçalho se existir
            $header = fgetcsv($handle, 1000, ';'); // Usando ponto e vírgula como separador
            if ($header && (strtoupper($header[0]) === 'CODIGO' || strtoupper($header[0]) === 'CÓDIGO')) {
                $lineNumber++;
            } else {
                // Se não há cabeçalho, volta para o início
                rewind($handle);
            }
            
            while (($data = fgetcsv($handle, 1000, ';')) !== FALSE) { // Usando ponto e vírgula
                $lineNumber++;
                
                // Verifica se a linha tem pelo menos 2 colunas
                if (count($data) < 2) {
                    $errors[] = "Linha {$lineNumber}: Dados insuficientes";
                    continue;
                }
                
                $codigo = trim($data[0]);
                $titulo = trim($data[1]);
                
                // Valida dados obrigatórios
                if (empty($codigo) || empty($titulo)) {
                    $errors[] = "Linha {$lineNumber}: Código e título são obrigatórios";
                    continue;
                }
                
                try {
                    // Verifica se já existe uma profissão com este nome (titulo)
                    $existing = $this->professionModel->findByName($titulo);
                    
                    if ($existing) {
                        // Atualiza profissão existente
                        $result = $this->professionModel->updateByName($titulo, [
                            'descricao' => null,
                            'ativo' => 1
                        ]);
                        
                        if ($result) {
                            $updated++;
                        } else {
                            $errors[] = "Linha {$lineNumber}: Erro ao atualizar profissão '{$titulo}'";
                        }
                    } else {
                        // Cria nova profissão
                        $result = $this->professionModel->create([
                            'nome' => $titulo,
                            'descricao' => null
                        ]);
                        
                        if ($result) {
                            $imported++;
                        } else {
                            $errors[] = "Linha {$lineNumber}: Erro ao criar profissão '{$titulo}'";
                        }
                    }
                } catch (Exception $e) {
                    $errors[] = "Linha {$lineNumber}: {$e->getMessage()} - Código: '{$codigo}', Título: '{$titulo}'";
                }
            }
            
            fclose($handle);
            
            // Limpar arquivo temporário se foi criado
            if (isset($tempFile) && file_exists($tempFile)) {
                unlink($tempFile);
            }
            
            // Prepara resposta
            $response = [
                'imported' => $imported,
                'updated' => $updated,
                'total_processed' => $imported + $updated,
                'errors_count' => count($errors)
            ];
            
            if (!empty($errors)) {
                $response['errors'] = array_slice($errors, 0, 20); // Aumenta para 20 erros para melhor diagnóstico
                $response['sample_errors'] = array_slice($errors, 0, 5); // Primeiros 5 erros para análise
            }
            
            $message = "Importação concluída: {$imported} criadas, {$updated} atualizadas";
            if (count($errors) > 0) {
                $message .= ", " . count($errors) . " erros";
            }
            
            Response::success($response, $message);
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
}