<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Signature.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

class ReportController {
    private $userModel;
    private $companyModel;
    private $documentModel;
    private $signatureModel;
    
    public function __construct() {
        $this->userModel = new User();
        $this->companyModel = new Company();
        $this->documentModel = new Document();
        $this->signatureModel = new Signature();
    }
    
    /**
     * Gera relatório de usuários
     */
    public function users() {
        try {
            $currentUser = JWT::requireAuth();
            
            // Apenas Super Admin e Admin da Empresa podem gerar relatórios de usuários
            if (!in_array($currentUser['tipo_usuario'], [1, 2])) {
                Response::unauthorized('Acesso negado');
                return;
            }
            
            $filters = [];
            
            // Admin da empresa só vê usuários da sua empresa
            if ($currentUser['tipo_usuario'] == 2) {
                $filters['empresa_id'] = $currentUser['empresa_id'];
            }
            
            // Filtros de data
            if (isset($_GET['start_date'])) {
                $filters['start_date'] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $filters['end_date'] = $_GET['end_date'];
            }
            
            $users = $this->userModel->getForReport($filters);
            
            $this->generatePDF('Relatório de Usuários', $users, [
                'Nome', 'Email', 'Tipo', 'Empresa', 'Status', 'Data Cadastro'
            ]);
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Gera relatório de empresas
     */
    public function companies() {
        try {
            JWT::requireAdmin(); // Apenas Super Admin
            
            $filters = [];
            
            // Filtros de data
            if (isset($_GET['start_date'])) {
                $filters['start_date'] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $filters['end_date'] = $_GET['end_date'];
            }
            
            $companies = $this->companyModel->getForReport($filters);
            
            $this->generatePDF('Relatório de Empresas', $companies, [
                'Nome Fantasia', 'CNPJ', 'Email', 'Plano', 'Vencimento', 'Status'
            ]);
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Gera relatório de documentos
     */
    public function documents() {
        try {
            $currentUser = JWT::requireAuth();
            
            $filters = [];
            
            // Filtrar por permissão do usuário
            if ($currentUser['tipo_usuario'] == 2) {
                $filters['empresa_id'] = $currentUser['empresa_id'];
            } elseif ($currentUser['tipo_usuario'] == 3) {
                $filters['criado_por'] = $currentUser['id'];
            }
            
            // Filtros de data
            if (isset($_GET['start_date'])) {
                $filters['start_date'] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $filters['end_date'] = $_GET['end_date'];
            }
            
            $documents = $this->documentModel->getForReport($filters);
            
            $this->generatePDF('Relatório de Documentos', $documents, [
                'Título', 'Status', 'Criado Por', 'Empresa', 'Data Criação', 'Tamanho'
            ]);
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Gera relatório de assinaturas
     */
    public function signatures() {
        try {
            $currentUser = JWT::requireAuth();
            
            $filters = [];
            
            // Filtrar por permissão do usuário
            if ($currentUser['tipo_usuario'] == 2) {
                $filters['empresa_id'] = $currentUser['empresa_id'];
            } elseif ($currentUser['tipo_usuario'] == 3) {
                $filters['criado_por'] = $currentUser['id'];
            }
            
            // Filtros de data
            if (isset($_GET['start_date'])) {
                $filters['start_date'] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $filters['end_date'] = $_GET['end_date'];
            }
            
            $signatures = $this->signatureModel->getForReport($filters);
            
            $this->generatePDF('Relatório de Assinaturas', $signatures, [
                'Documento', 'Status', 'Criado Por', 'Empresa', 'Data Criação', 'Expiração'
            ]);
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Gera relatório de atividades
     */
    public function activities() {
        try {
            $currentUser = JWT::requireAuth();
            
            // Apenas Super Admin e Admin da Empresa
            if (!in_array($currentUser['tipo_usuario'], [1, 2])) {
                Response::unauthorized('Acesso negado');
                return;
            }
            
            $filters = [];
            
            // Admin da empresa só vê atividades da sua empresa
            if ($currentUser['tipo_usuario'] == 2) {
                $filters['empresa_id'] = $currentUser['empresa_id'];
            }
            
            // Filtros de data
            if (isset($_GET['start_date'])) {
                $filters['start_date'] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $filters['end_date'] = $_GET['end_date'];
            }
            
            // Para este exemplo, vamos usar dados de documentos e assinaturas como atividades
            $activities = $this->getActivitiesData($filters);
            
            $this->generatePDF('Relatório de Atividades', $activities, [
                'Usuário', 'Ação', 'Descrição', 'Data', 'IP'
            ]);
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Gera relatório financeiro
     */
    public function financial() {
        try {
            JWT::requireAdmin(); // Apenas Super Admin
            
            $filters = [];
            
            // Filtros de data
            if (isset($_GET['start_date'])) {
                $filters['start_date'] = $_GET['start_date'];
            }
            if (isset($_GET['end_date'])) {
                $filters['end_date'] = $_GET['end_date'];
            }
            
            $financial = $this->getFinancialData($filters);
            
            $this->generatePDF('Relatório Financeiro', $financial, [
                'Empresa', 'Plano', 'Valor', 'Vencimento', 'Status', 'Receita'
            ]);
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Gera PDF do relatório
     */
    private function generatePDF($title, $data, $headers) {
        // Para este exemplo, vamos retornar dados em JSON
        // Em uma implementação real, você usaria uma biblioteca como TCPDF ou FPDF
        
        header('Content-Type: application/json');
        
        $report = [
            'title' => $title,
            'generated_at' => date('Y-m-d H:i:s'),
            'headers' => $headers,
            'data' => $data,
            'total_records' => count($data)
        ];
        
        Response::success($report, 'Relatório gerado com sucesso');
    }
    
    /**
     * Obtém dados de atividades
     */
    private function getActivitiesData($filters) {
        // Simulação de dados de atividades
        // Em uma implementação real, você teria uma tabela de logs/atividades
        return [
            [
                'usuario' => 'Admin Sistema',
                'acao' => 'Login',
                'descricao' => 'Usuário fez login no sistema',
                'data' => date('Y-m-d H:i:s'),
                'ip' => '192.168.1.1'
            ],
            [
                'usuario' => 'João Silva',
                'acao' => 'Criar Documento',
                'descricao' => 'Documento "Contrato de Serviços" criado',
                'data' => date('Y-m-d H:i:s', strtotime('-1 hour')),
                'ip' => '192.168.1.2'
            ]
        ];
    }
    
    /**
     * Obtém dados financeiros
     */
    private function getFinancialData($filters) {
        // Simulação de dados financeiros
        // Em uma implementação real, você calcularia receitas baseado nos planos das empresas
        return [
            [
                'empresa' => 'Empresa Exemplo Ltda',
                'plano' => 'Plano Premium',
                'valor' => 'R$ 199,90',
                'vencimento' => '2024-12-31',
                'status' => 'Ativo',
                'receita' => 'R$ 199,90'
            ],
            [
                'empresa' => 'Tech Solutions Inc',
                'plano' => 'Plano Básico',
                'valor' => 'R$ 99,90',
                'vencimento' => '2024-11-15',
                'status' => 'Vence em Breve',
                'receita' => 'R$ 99,90'
            ]
        ];
    }
}