<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Company.php';
require_once __DIR__ . '/../models/Document.php';
require_once __DIR__ . '/../models/Signature.php';
require_once __DIR__ . '/../utils/JWT.php';
require_once __DIR__ . '/../utils/Response.php';

class DashboardController {
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
     * Retorna estatísticas do dashboard
     */
    public function stats() {
        try {
            $currentUser = JWT::requireAuth();
            
            $stats = [];
            
            if ($currentUser['tipo_usuario'] == 1) {
                // Super Admin - estatísticas globais
                $stats = [
                    'usuarios' => $this->userModel->count(),
                    'empresas' => $this->companyModel->count(),
                    'documentos' => $this->documentModel->count(),
                    'assinaturas' => $this->signatureModel->count(),
                    'pendentes' => $this->signatureModel->countPending()
                ];
            } elseif ($currentUser['tipo_usuario'] == 2) {
                // Admin da Empresa - estatísticas da empresa
                $stats = [
                    'usuarios' => $this->userModel->countByCompany($currentUser['empresa_id']),
                    'documentos' => $this->documentModel->countByCompany($currentUser['empresa_id']),
                    'assinaturas' => $this->signatureModel->countByCompany($currentUser['empresa_id']),
                    'pendentes' => $this->signatureModel->countPendingByCompany($currentUser['empresa_id'])
                ];
            } else {
                // Assinante - estatísticas pessoais
                $stats = [
                    'documentos' => $this->documentModel->countByUser($currentUser['id']),
                    'assinaturas' => $this->signatureModel->countByUser($currentUser['id']),
                    'pendentes' => $this->signatureModel->countPendingByUser($currentUser['id'])
                ];
            }
            
            Response::success($stats, 'Estatísticas recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Retorna atividades recentes
     */
    public function activities() {
        try {
            $currentUser = JWT::requireAuth();
            
            $activities = [];
            
            if ($currentUser['tipo_usuario'] == 1) {
                // Super Admin - atividades globais
                $activities = $this->getGlobalActivities();
            } elseif ($currentUser['tipo_usuario'] == 2) {
                // Admin da Empresa - atividades da empresa
                $activities = $this->getCompanyActivities($currentUser['empresa_id']);
            } else {
                // Assinante - atividades pessoais
                $activities = $this->getUserActivities($currentUser['id']);
            }
            
            Response::success($activities, 'Atividades recuperadas com sucesso');
            
        } catch (Exception $e) {
            Response::handleException($e);
        }
    }
    
    /**
     * Atividades globais (Super Admin)
     */
    private function getGlobalActivities() {
        $activities = [];
        
        // Últimos documentos criados
        $recentDocuments = $this->documentModel->getRecent(5);
        foreach ($recentDocuments as $doc) {
            $activities[] = [
                'icon' => '📄',
                'color' => '#3B82F6',
                'text' => "Documento '{$doc['titulo']}' foi criado",
                'time' => $this->timeAgo($doc['data_criacao'])
            ];
        }
        
        // Últimas assinaturas
        $recentSignatures = $this->signatureModel->getRecent(5);
        foreach ($recentSignatures as $sig) {
            $activities[] = [
                'icon' => '✍️',
                'color' => '#10B981',
                'text' => "Documento '{$sig['documento_titulo']}' foi assinado",
                'time' => $this->timeAgo($sig['data_criacao'])
            ];
        }
        
        // Ordenar por data
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, 10);
    }
    
    /**
     * Atividades da empresa (Admin da Empresa)
     */
    private function getCompanyActivities($companyId) {
        $activities = [];
        
        // Documentos da empresa
        $recentDocuments = $this->documentModel->getRecentByCompany($companyId, 5);
        foreach ($recentDocuments as $doc) {
            $activities[] = [
                'icon' => '📄',
                'color' => '#3B82F6',
                'text' => "Documento '{$doc['titulo']}' foi criado",
                'time' => $this->timeAgo($doc['data_criacao'])
            ];
        }
        
        // Assinaturas da empresa
        $recentSignatures = $this->signatureModel->getRecentByCompany($companyId, 5);
        foreach ($recentSignatures as $sig) {
            $activities[] = [
                'icon' => '✍️',
                'color' => '#10B981',
                'text' => "Documento '{$sig['documento_titulo']}' foi assinado",
                'time' => $this->timeAgo($sig['data_criacao'])
            ];
        }
        
        // Ordenar por data
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, 10);
    }
    
    /**
     * Atividades do usuário (Assinante)
     */
    private function getUserActivities($userId) {
        $activities = [];
        
        // Documentos do usuário
        $recentDocuments = $this->documentModel->getRecentByUser($userId, 5);
        foreach ($recentDocuments as $doc) {
            $activities[] = [
                'icon' => '📄',
                'color' => '#3B82F6',
                'text' => "Você criou o documento '{$doc['titulo']}'",
                'time' => $this->timeAgo($doc['data_criacao'])
            ];
        }
        
        // Assinaturas do usuário
        $recentSignatures = $this->signatureModel->getRecentByUser($userId, 5);
        foreach ($recentSignatures as $sig) {
            $activities[] = [
                'icon' => '✍️',
                'color' => '#10B981',
                'text' => "Você assinou o documento '{$sig['documento_titulo']}'",
                'time' => $this->timeAgo($sig['data_criacao'])
            ];
        }
        
        // Ordenar por data
        usort($activities, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });
        
        return array_slice($activities, 0, 10);
    }
    
    /**
     * Converte timestamp em tempo relativo
     */
    private function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        
        if ($time < 60) {
            return 'Agora mesmo';
        } elseif ($time < 3600) {
            $minutes = floor($time / 60);
            return $minutes . ' minuto' . ($minutes > 1 ? 's' : '') . ' atrás';
        } elseif ($time < 86400) {
            $hours = floor($time / 3600);
            return $hours . ' hora' . ($hours > 1 ? 's' : '') . ' atrás';
        } elseif ($time < 2592000) {
            $days = floor($time / 86400);
            return $days . ' dia' . ($days > 1 ? 's' : '') . ' atrás';
        } else {
            return date('d/m/Y', strtotime($datetime));
        }
    }
}

?>