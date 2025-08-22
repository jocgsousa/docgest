<?php

require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Plan.php';
require_once __DIR__ . '/../models/Signature.php';
require_once __DIR__ . '/../models/User.php';

class UsageController {
    private $companyModel;
    private $documentModel;
    private $planModel;
    private $signatureModel;
    private $userModel;
    
    public function __construct() {
        $this->companyModel = new Company();
        $this->documentModel = new Document();
        $this->planModel = new Plan();
        $this->signatureModel = new Signature();
        $this->userModel = new User();
    }
    
    /**
     * Retorna dados de uso atual do plano da empresa
     */
    public function current() {
        try {
            $currentUser = JWT::requireAuth();
            
            // Apenas administradores de empresa podem acessar
            if ($currentUser['tipo_usuario'] != 2) {
                Response::error('Acesso negado', 403);
                return;
            }
            
            $empresaId = $currentUser['empresa_id'];
            
            // Obter parâmetros de data da requisição
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            
            // Se não foram fornecidas datas, usar o mês atual
            if (!$startDate || !$endDate) {
                $startDate = date('Y-m-01'); // Primeiro dia do mês atual
                $endDate = date('Y-m-t');    // Último dia do mês atual
            }
            
            // Buscar dados do plano da empresa com filtro de data
            $planUsage = $this->companyModel->getPlanUsageByDateRange($empresaId, $startDate, $endDate);
            
            if (!$planUsage) {
                Response::error('Empresa não encontrada', 404);
                return;
            }
            
            // Buscar dados da empresa para obter data de vencimento
            $empresa = $this->companyModel->findById($empresaId);
            
            // Calcular dias restantes baseado na vigência do plano
            $today = new DateTime();
            $dataVencimento = new DateTime($empresa['data_vencimento']);
            $daysRemaining = max(0, $today->diff($dataVencimento)->days);
            
            // Se a data de vencimento já passou, dias restantes = 0
            if ($dataVencimento < $today) {
                $daysRemaining = 0;
            }
            
            // Calcular uso de armazenamento (simulado - pode ser implementado futuramente)
            $storageUsedMb = $this->calculateStorageUsage($empresaId);
            $storageLimitMb = 1000; // 1GB padrão - pode vir do plano
            
            // Buscar informações do plano
            $plano = $this->planModel->findById($empresa['plano_id']);
            
            // Montar resposta no formato esperado pelo frontend
            $response = [
                'current_period' => [
                    'documents_sent' => (int)$planUsage['documentos_usados'],
                    'documents_signed' => $this->signatureModel->countByCompanyAndDateRange($empresaId, $startDate, $endDate),
                    'storage_used_mb' => $storageUsedMb,
                    'api_calls' => 0, // Pode ser implementado futuramente
                    'whatsapp_messages' => 0 // Pode ser implementado futuramente
                ],
                'limits' => [
                    'documents_per_month' => (int)$planUsage['limite_documentos'],
                    'storage_limit_mb' => $storageLimitMb,
                    'api_calls_per_month' => 10000, // Padrão
                    'whatsapp_messages_per_month' => 1000 // Padrão
                ],
                'days_remaining' => $daysRemaining,
                'plan_name' => $plano ? $plano['nome'] : 'Plano Atual',
                'plan_days' => $plano ? (int)$plano['dias'] : 30,
                'period_start' => $startDate,
                'period_end' => $endDate,
                'billing_cycle' => 'plan_based',
                'plan_expired' => $daysRemaining === 0
            ];
            
            Response::success($response, 'Dados de uso recuperados com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Retorna histórico de uso
     */
    public function history() {
        try {
            $currentUser = JWT::requireAuth();
            
            // Apenas administradores de empresa podem acessar
            if ($currentUser['tipo_usuario'] != 2) {
                Response::error('Acesso negado', 403);
                return;
            }
            
            $empresaId = $currentUser['empresa_id'];
            
            // Buscar histórico dos últimos 12 meses
            $history = $this->getUsageHistory($empresaId, 12);
            
            Response::success($history, 'Histórico de uso recuperado com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Calcula uso de armazenamento (simulado)
     */
    private function calculateStorageUsage($empresaId) {
        // Implementação básica - pode ser melhorada para calcular tamanho real dos arquivos
        $documentCount = $this->documentModel->countByCompany($empresaId);
        return $documentCount * 2; // Aproximadamente 2MB por documento
    }
    
    /**
     * Busca histórico de uso por meses
     */
    private function getUsageHistory($empresaId, $months = 12) {
        $history = [];
        
        for ($i = 0; $i < $months; $i++) {
            $date = new DateTime();
            $date->sub(new DateInterval("P{$i}M"));
            $monthYear = $date->format('Y-m');
            
            // Buscar dados do mês (implementação básica)
            $documentsInMonth = $this->getDocumentsInMonth($empresaId, $monthYear);
            $signaturesInMonth = $this->getSignaturesInMonth($empresaId, $monthYear);
            
            $history[] = [
                'month' => $date->format('Y-m'),
                'month_name' => $this->getMonthName($date->format('n')),
                'year' => $date->format('Y'),
                'documents_sent' => $documentsInMonth,
                'documents_signed' => $signaturesInMonth,
                'storage_used_mb' => $documentsInMonth * 2, // Simulado
                'api_calls' => 0,
                'whatsapp_messages' => 0
            ];
        }
        
        return array_reverse($history); // Mais recente primeiro
    }
    
    /**
     * Conta documentos criados em um mês específico
     */
    private function getDocumentsInMonth($empresaId, $monthYear) {
        $sql = "SELECT COUNT(*) FROM documentos 
                WHERE empresa_id = :empresa_id 
                AND DATE_FORMAT(created_at, '%Y-%m') = :month_year
                AND ativo = 1";
        
        $stmt = $this->documentModel->db->prepare($sql);
        $stmt->bindParam(':empresa_id', $empresaId);
        $stmt->bindParam(':month_year', $monthYear);
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Conta assinaturas criadas em um mês específico
     */
    private function getSignaturesInMonth($empresaId, $monthYear) {
        $sql = "SELECT COUNT(*) FROM assinaturas 
                WHERE empresa_id = :empresa_id 
                AND DATE_FORMAT(created_at, '%Y-%m') = :month_year
                AND ativo = 1";
        
        $stmt = $this->signatureModel->db->prepare($sql);
        $stmt->bindParam(':empresa_id', $empresaId);
        $stmt->bindParam(':month_year', $monthYear);
        $stmt->execute();
        
        return (int)$stmt->fetchColumn();
    }
    
    /**
     * Retorna nome do mês em português
     */
    private function getMonthName($monthNumber) {
        $months = [
            1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
            5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
            9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
        ];
        
        return $months[(int)$monthNumber] ?? 'Desconhecido';
    }
}

?>