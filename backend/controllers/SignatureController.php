<?php

require_once __DIR__ . '/../models/Signature.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';
require_once __DIR__ . '/../utils/JWT.php';

class SignatureController {
    private $signature;
    private $document;
    private $validator;
    
    public function __construct() {
        $this->signature = new Signature();
        $this->document = new Document();
        $this->validator = new Validator();
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
            
            $result = $this->signature->findAll($filters, $page, $pageSize);
            
            Response::paginated($result['data'], $result['total'], $page, $pageSize);
        } catch (Exception $e) {
            Response::error('Erro ao buscar assinaturas: ' . $e->getMessage());
        }
    }
    
    public function show($id) {
        try {
            $user = JWT::requireAuth();
            
            $signature = $this->signature->findById($id);
            
            if (!$signature) {
                Response::notFound('Assinatura não encontrada');
                return;
            }
            
            // Verificar permissões
            if ($user['tipo_usuario'] == 2 && $signature['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            if ($user['tipo_usuario'] == 3) {
                if ($signature['empresa_id'] != $user['empresa_id'] || 
                    ($user['filial_id'] && $signature['filial_id'] != $user['filial_id'])) {
                    Response::forbidden('Acesso negado');
                    return;
                }
            }
            
            Response::success($signature);
        } catch (Exception $e) {
            Response::error('Erro ao buscar assinatura: ' . $e->getMessage());
        }
    }
    
    public function store() {
        try {
            $user = JWT::requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validação dos dados
            $rules = [
                'documento_id' => 'required|integer|exists:documentos,id',
                'signatarios' => 'required|array|min:1',
                'signatarios.*.nome' => 'required|max:255',
                'signatarios.*.email' => 'required|email|max:255',
                'signatarios.*.ordem' => 'required|integer|min:1'
            ];
            
            if (!$this->validator->validate($input, $rules)) {
                Response::validation($this->validator->getErrors());
                return;
            }
            
            // Verificar se o documento existe e se o usuário tem permissão
            $document = $this->document->findById($input['documento_id']);
            
            if (!$document) {
                Response::notFound('Documento não encontrado');
                return;
            }
            
            // Verificar permissões no documento
            if ($user['tipo_usuario'] == 2 && $document['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Acesso negado ao documento');
                return;
            }
            
            if ($user['tipo_usuario'] == 3) {
                if ($document['empresa_id'] != $user['empresa_id'] || 
                    ($user['filial_id'] && $document['filial_id'] != $user['filial_id'])) {
                    Response::forbidden('Acesso negado ao documento');
                    return;
                }
            }
            
            // Verificar se o documento já tem uma assinatura ativa
            $existingSignatures = $this->signature->findAll(['documento_id' => $input['documento_id']], 1, 1);
            if (!empty($existingSignatures['data'])) {
                foreach ($existingSignatures['data'] as $existing) {
                    if (in_array($existing['status'], ['pendente', 'assinado'])) {
                        Response::validation(['documento_id' => ['Este documento já possui uma assinatura ativa']]);
                        return;
                    }
                }
            }
            
            // Preparar dados da assinatura
            $signatureData = [
                'documento_id' => $input['documento_id'],
                'status' => 'pendente',
                'data_expiracao' => date('Y-m-d H:i:s', strtotime('+30 days')), // 30 dias para expirar
                'criado_por' => $user['id'],
                'empresa_id' => $user['empresa_id'],
                'filial_id' => $user['filial_id']
            ];
            
            $signatureId = $this->signature->create($signatureData);
            
            if ($signatureId) {
                // Adicionar signatários
                foreach ($input['signatarios'] as $signerData) {
                    $token = bin2hex(random_bytes(32)); // Token único para cada signatário
                    
                    $signer = [
                        'nome' => $signerData['nome'],
                        'email' => $signerData['email'],
                        'ordem' => $signerData['ordem'],
                        'status' => 'pendente',
                        'token' => $token
                    ];
                    
                    $this->signature->addSigner($signatureId, $signer);
                }
                
                // Atualizar status do documento para 'enviado'
                $this->document->updateStatus($input['documento_id'], 'enviado');
                
                // Buscar a assinatura criada com todos os dados
                $newSignature = $this->signature->findById($signatureId);
                
                // Aqui você pode implementar o envio de emails para os signatários
                // $this->sendSignatureEmails($newSignature);
                
                Response::created($newSignature, 'Assinatura criada com sucesso');
            } else {
                Response::error('Erro ao criar assinatura');
            }
        } catch (Exception $e) {
            Response::error('Erro ao criar assinatura: ' . $e->getMessage());
        }
    }
    
    public function cancel($id) {
        try {
            $user = JWT::requireAuth();
            
            $signature = $this->signature->findById($id);
            
            if (!$signature) {
                Response::notFound('Assinatura não encontrada');
                return;
            }
            
            // Verificar permissões
            if ($user['tipo_usuario'] == 2 && $signature['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            if ($user['tipo_usuario'] == 3) {
                if ($signature['criado_por'] != $user['id']) {
                    Response::forbidden('Acesso negado');
                    return;
                }
            }
            
            // Verificar se pode ser cancelada
            if ($signature['status'] === 'assinado') {
                Response::validation(['status' => ['Não é possível cancelar uma assinatura já concluída']]);
                return;
            }
            
            if ($this->signature->updateStatus($id, 'cancelado')) {
                // Atualizar status do documento para 'cancelado'
                $this->document->updateStatus($signature['documento_id'], 'cancelado');
                
                $updatedSignature = $this->signature->findById($id);
                Response::success($updatedSignature, 'Assinatura cancelada com sucesso');
            } else {
                Response::error('Erro ao cancelar assinatura');
            }
        } catch (Exception $e) {
            Response::error('Erro ao cancelar assinatura: ' . $e->getMessage());
        }
    }
    
    public function sendReminder($id) {
        try {
            $user = JWT::requireAuth();
            
            $signature = $this->signature->findById($id);
            
            if (!$signature) {
                Response::notFound('Assinatura não encontrada');
                return;
            }
            
            // Verificar permissões
            if ($user['tipo_usuario'] == 2 && $signature['empresa_id'] != $user['empresa_id']) {
                Response::forbidden('Acesso negado');
                return;
            }
            
            if ($user['tipo_usuario'] == 3) {
                if ($signature['criado_por'] != $user['id']) {
                    Response::forbidden('Acesso negado');
                    return;
                }
            }
            
            // Verificar se está pendente
            if ($signature['status'] !== 'pendente') {
                Response::validation(['status' => ['Só é possível enviar lembrete para assinaturas pendentes']]);
                return;
            }
            
            // Aqui você implementaria o envio de emails de lembrete
            // $this->sendReminderEmails($signature);
            
            Response::success(null, 'Lembrete enviado com sucesso');
        } catch (Exception $e) {
            Response::error('Erro ao enviar lembrete: ' . $e->getMessage());
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
            
            $stats = $this->signature->getStats($filters);
            
            Response::success($stats);
        } catch (Exception $e) {
            Response::error('Erro ao buscar estatísticas: ' . $e->getMessage());
        }
    }
    
    public function sign($token) {
        try {
            // Buscar signatário pelo token
            $signer = $this->signature->getSignerByToken($token);
            
            if (!$signer) {
                Response::notFound('Token de assinatura inválido');
                return;
            }
            
            // Verificar se já foi assinado
            if ($signer['status'] === 'assinado') {
                Response::validation(['token' => ['Este documento já foi assinado por você']]);
                return;
            }
            
            // Verificar se foi rejeitado
            if ($signer['status'] === 'rejeitado') {
                Response::validation(['token' => ['Você rejeitou a assinatura deste documento']]);
                return;
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            $action = $input['action'] ?? 'sign'; // 'sign' ou 'reject'
            
            if ($action === 'sign') {
                // Marcar como assinado
                $this->signature->updateSignerStatus($signer['id'], 'assinado', date('Y-m-d H:i:s'));
                
                // Verificar se todos os signatários assinaram
                $allSigners = $this->signature->getSigners($signer['assinatura_id']);
                $allSigned = true;
                
                foreach ($allSigners as $s) {
                    if ($s['id'] == $signer['id']) {
                        continue; // Pular o signatário atual (já foi atualizado)
                    }
                    if ($s['status'] !== 'assinado') {
                        $allSigned = false;
                        break;
                    }
                }
                
                // Se todos assinaram, atualizar status da assinatura e documento
                if ($allSigned) {
                    $this->signature->updateStatus($signer['assinatura_id'], 'assinado');
                    $this->document->updateStatus($signer['documento_id'], 'assinado');
                }
                
                Response::success(null, 'Documento assinado com sucesso');
            } elseif ($action === 'reject') {
                // Marcar como rejeitado
                $this->signature->updateSignerStatus($signer['id'], 'rejeitado');
                
                // Atualizar status da assinatura e documento para rejeitado
                $this->signature->updateStatus($signer['assinatura_id'], 'rejeitado');
                $this->document->updateStatus($signer['documento_id'], 'cancelado');
                
                Response::success(null, 'Assinatura rejeitada');
            } else {
                Response::validation(['action' => ['Ação inválida']]);
            }
        } catch (Exception $e) {
            Response::error('Erro ao processar assinatura: ' . $e->getMessage());
        }
    }
    
    public function getSigningPage($token) {
        try {
            // Buscar signatário pelo token
            $signer = $this->signature->getSignerByToken($token);
            
            if (!$signer) {
                Response::notFound('Token de assinatura inválido');
                return;
            }
            
            // Retornar dados para a página de assinatura
            $data = [
                'signer' => [
                    'nome' => $signer['nome'],
                    'email' => $signer['email'],
                    'status' => $signer['status']
                ],
                'document' => [
                    'titulo' => $signer['documento_titulo'],
                    'caminho_arquivo' => $signer['caminho_arquivo']
                ],
                'token' => $token
            ];
            
            Response::success($data);
        } catch (Exception $e) {
            Response::error('Erro ao buscar dados da assinatura: ' . $e->getMessage());
        }
    }
    
    public function pending() {
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
            
            $signatures = $this->signature->getPendingSignatures($filters);
            
            Response::success($signatures);
        } catch (Exception $e) {
            Response::error('Erro ao buscar assinaturas pendentes: ' . $e->getMessage());
        }
    }
    
    // Método para processar assinaturas expiradas (pode ser chamado via cron)
    public function processExpired() {
        try {
            $expiredSignatures = $this->signature->getExpiredSignatures();
            
            foreach ($expiredSignatures as $signature) {
                $this->signature->updateStatus($signature['id'], 'expirado');
                $this->document->updateStatus($signature['documento_id'], 'cancelado');
            }
            
            Response::success(null, count($expiredSignatures) . ' assinaturas expiradas processadas');
        } catch (Exception $e) {
            Response::error('Erro ao processar assinaturas expiradas: ' . $e->getMessage());
        }
    }
    
    // Métodos privados para envio de emails (implementar conforme necessário)
    private function sendSignatureEmails($signature) {
        // Implementar envio de emails para os signatários
        // Usar a configuração de email do config.php
    }
    
    private function sendReminderEmails($signature) {
        // Implementar envio de emails de lembrete
    }
}