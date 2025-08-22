<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/CompanyController.php';
require_once __DIR__ . '/../controllers/PlanController.php';
require_once __DIR__ . '/../controllers/DocumentController.php';
require_once __DIR__ . '/../controllers/DocumentTypeController.php';
require_once __DIR__ . '/../controllers/SignatureController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/ReportController.php';
require_once __DIR__ . '/../controllers/SettingsController.php';
require_once __DIR__ . '/../controllers/LogController.php';
require_once __DIR__ . '/../controllers/BranchController.php';
require_once __DIR__ . '/../controllers/ProfessionController.php';
require_once __DIR__ . '/../controllers/NotificationController.php';
require_once __DIR__ . '/../utils/Response.php';

// Configurar CORS
setCorsHeaders();

// Tratar requisições OPTIONS (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Obter método HTTP e URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/backend', '', $uri); // Remove /backend do início se existir
$uri = rtrim($uri, '/'); // Remove barra final

// Dividir URI em segmentos
$segments = explode('/', trim($uri, '/'));

// Remover segmentos vazios
$segments = array_filter($segments);
$segments = array_values($segments);

// Se a primeira parte for 'api', remover ela
if (!empty($segments) && $segments[0] === 'api') {
    array_shift($segments);
}

try {
    // Roteamento
    if (empty($segments)) {
        Response::success(['message' => 'DocGest API v1.0', 'status' => 'online'], 'API funcionando corretamente');
    }
    
    $resource = $segments[0] ?? '';
    $id = $segments[1] ?? null;
    $action = $segments[2] ?? null;
    
    // Verificar se action está nos query parameters
    if ($action === null && isset($_GET['action'])) {
        $action = $_GET['action'];
    }
    
    // Rota pública para informações da aplicação
    if ($resource === 'app-info' && $method === 'GET') {
        $settingsController = new SettingsController();
        $settingsController->getAppInfo();
        exit;
    }
    
    // Rota pública para configurações de upload
    if ($resource === 'upload-config' && $method === 'GET') {
        $settingsController = new SettingsController();
        $settingsController->getUploadConfig();
        exit;
    }
    
    switch ($resource) {
        // Rotas de Autenticação
        case 'auth':
            $controller = new AuthController();
            
            switch ($method) {
                case 'POST':
                    if ($id === 'login') {
                        $controller->login();
                    } elseif ($id === 'register') {
                        $controller->register();
                    } elseif ($id === 'register-external') {
                        $controller->registerExternal();
                    } elseif ($id === 'logout') {
                        $controller->logout();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'GET':
                    if ($id === 'me') {
                        $controller->me();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id === 'profile') {
                        $controller->updateProfile();
                    } elseif ($id === 'password') {
                        $controller->changePassword();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Usuários
        case 'users':
            $controller = new UserController();
            
            switch ($method) {
                case 'GET':
                    if ($id === null) {
                        $controller->index();
                    } elseif ($id === 'stats') {
                        $controller->stats();
                    } elseif ($id === 'by-company-branch') {
                        $controller->getByCompanyAndBranch();
                    } elseif ($id === 'requests') {
                        $controller->listRequests();
                    } elseif ($action === null) {
                        $controller->show($id);
                    } elseif ($action === 'empresa') {
                        $controller->byEmpresa($id);
                    } elseif ($action === 'filial') {
                        $controller->byFilial($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === null) {
                        $controller->store();
                    } elseif ($id === 'create-request') {
                        $controller->createRequest();
                    } elseif ($id === 'update-request') {
                        $controller->updateRequest();
                    } elseif ($id === 'generate-registration-link') {
                        $controller->generateRegistrationLink();
                    } elseif ($id === 'validate-registration-token') {
                        $controller->validateRegistrationToken();
                    } elseif ($id === 'mark-token-used') {
                        $controller->markTokenAsUsed();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id !== null && $action === null) {
                        $controller->update($id);
                    } elseif ($id !== null && $action === 'deactivate') {
                        $controller->deactivate($id);
                    } elseif ($id !== null && $action === 'activate') {
                        $controller->activate($id);
                    } elseif ($id !== null && $action === 'reject-deletion') {
                        $controller->rejectDeletion($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id !== null && $action === null) {
                        $controller->update($id);
                    } elseif ($id !== null && $action === 'deactivate') {
                        $controller->deactivate($id);
                    } elseif ($id !== null && $action === 'activate') {
                        $controller->activate($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'DELETE':
                    if ($id !== null && $action === null) {
                        $controller->destroy($id);
                    } elseif ($id !== null && $action === 'permanent') {
                        $controller->delete($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Notificações
        case 'notifications':
            $controller = new NotificationController();
            
            switch ($method) {
                case 'GET':
                    if ($id === null) {
                        $controller->index();
                    } elseif ($id === 'unread-count') {
                        $controller->unreadCount();
                    } elseif ($action === null) {
                        $controller->show($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === null) {
                        $controller->create();
                    } elseif ($id === 'mark-all-read') {
                        $controller->markAllAsRead();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id !== null && $action === 'mark-read') {
                        $controller->markAsRead($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'DELETE':
                    if ($id !== null && $action === null) {
                        $controller->delete($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Empresas
        case 'companies':
            $controller = new CompanyController();
            
            switch ($method) {
                case 'GET':
                    if ($id === null) {
                        $controller->index();
                    } elseif ($id === 'all') {
                        $controller->listAll();
                    } elseif ($id === 'stats') {
                        $controller->stats();
                    } elseif ($id === 'expiring') {
                        $controller->expiring();
                    } elseif ($id === 'expired') {
                        $controller->expired();
                    } elseif ($action === null) {
                        $controller->show($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === null) {
                        $controller->store();
                    } elseif ($action === 'renew') {
                        $controller->renew($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id !== null && $action === null) {
                        $controller->update($id);
                    } elseif ($id !== null && $action === 'renew') {
                        $controller->renew($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'DELETE':
                    if ($id !== null && $action === null) {
                        $controller->destroy($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Filiais
        case 'branches':
            $controller = new BranchController();
            
            switch ($method) {
                case 'GET':
                    if ($id === null) {
                        $controller->index();
                    } elseif ($id === 'all') {
                        $controller->listAll();
                    } elseif ($id === 'stats') {
                        $controller->stats();
                    } elseif ($action === null) {
                        $controller->show($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === null) {
                        $controller->store();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id !== null && $action === null) {
                        $controller->update($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'DELETE':
                    if ($id !== null && $action === null) {
                        $controller->destroy($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Planos
        case 'plans':
            $controller = new PlanController();
            
            switch ($method) {
                case 'GET':
                    if ($id === null) {
                        $controller->index();
                    } elseif ($id === 'all') {
                        $controller->listAll();
                    } elseif ($id === 'stats') {
                        $controller->stats();
                    } elseif ($action === null) {
                        $controller->show($id);
                    } elseif ($action === 'companies') {
                        $controller->companies($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === null) {
                        $controller->store();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id !== null && $action === null) {
                        $controller->update($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'DELETE':
                    if ($id !== null && $action === null) {
                        $controller->destroy($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Documentos
        case 'documents':
            $controller = new DocumentController();
            
            switch ($method) {
                case 'GET':
                    if ($id === null) {
                        $controller->index();
                    } elseif ($id === 'stats') {
                        $controller->stats();
                    } elseif ($id === 'select') {
                        $controller->select();
                    } elseif ($action === null) {
                        $controller->show($id);
                    } elseif ($action === 'download') {
                        $controller->download($id); // $id agora será o hash
                    } elseif ($action === 'view') {
                        $controller->view($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === null) {
                        $controller->store();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id !== null && $action === null) {
                        $controller->update($id);
                    } elseif ($id !== null && $action === 'status') {
                        $controller->updateStatus($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'DELETE':
                    if ($id !== null && $action === null) {
                        $controller->destroy($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Tipos de Documentos
        case 'document-types':
            $controller = new DocumentTypeController();
            
            switch ($method) {
                case 'GET':
                    if ($id === null) {
                        $controller->index();
                    } elseif ($action === null) {
                        $controller->show($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === null) {
                        $controller->store();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id !== null && $action === null) {
                        $controller->update($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'DELETE':
                    if ($id !== null && $action === null) {
                        $controller->destroy($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Assinaturas
        case 'signatures':
            $controller = new SignatureController();
            
            switch ($method) {
                case 'GET':
                    if ($id === null) {
                        $controller->index();
                    } elseif ($id === 'stats') {
                        $controller->stats();
                    } elseif ($id === 'pending') {
                        $controller->pending();
                    } elseif ($action === null) {
                        $controller->show($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === null) {
                        $controller->store();
                    } elseif ($id === 'expired') {
                        $controller->processExpired();
                    } elseif ($action === 'reminder') {
                        $controller->sendReminder($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id !== null && $action === 'cancel') {
                        $controller->cancel($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas do Dashboard
        case 'dashboard':
            $controller = new DashboardController();
            
            switch ($method) {
                case 'GET':
                    if ($id === 'stats') {
                        $controller->stats();
                    } elseif ($id === 'activities') {
                        $controller->activities();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Relatórios
        case 'reports':
            $controller = new ReportController();
            
            switch ($method) {
                case 'GET':
                    if ($id === 'users') {
                        $controller->users();
                    } elseif ($id === 'companies') {
                        $controller->companies();
                    } elseif ($id === 'documents') {
                        $controller->documents();
                    } elseif ($id === 'signatures') {
                        $controller->signatures();
                    } elseif ($id === 'activities') {
                        $controller->activities();
                    } elseif ($id === 'financial') {
                        $controller->financial();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Configurações
        case 'settings':
            $controller = new SettingsController();
            
            switch ($method) {
                case 'GET':
                    if ($id === null) {
                        $controller->index(); // Buscar todas as configurações
                    } elseif ($id === 'metadata') {
                        $controller->getAllWithMetadata(); // Buscar com metadados
                    } elseif ($action === null) {
                        $controller->show($id); // Buscar configuração específica
                    } elseif ($action === 'category') {
                        $controller->getByCategory($id); // Buscar por categoria
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id === null) {
                        $controller->update(); // Atualizar múltiplas configurações
                    } else {
                        $controller->updateSingle($id); // Atualizar configuração específica
                    }
                    break;
                    
                case 'POST':
                    if ($id === 'restore-defaults') {
                        $controller->restoreDefaults(); // Restaurar configurações padrão
                    } elseif ($id === null) {
                        $controller->store(); // Criar nova configuração
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'DELETE':
                    if ($id !== null) {
                        $controller->delete($id); // Remover configuração
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Logs
        case 'logs':
            $controller = new LogController();
            
            switch ($method) {
                case 'GET':
                    if ($id === 'stats') {
                        $controller->stats();
                    } elseif ($id === null) {
                        $controller->index();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === null) {
                        $controller->create();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'DELETE':
                    if ($id === null) {
                        $controller->clear();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas de Profissões
        case 'professions':
            $controller = new ProfessionController();
            
            switch ($method) {
                case 'GET':
                    if ($id === null) {
                        $controller->index();
                    } elseif ($id === 'all') {
                        $controller->listAll();
                    } elseif ($action === null) {
                        $controller->show($id);
                    } elseif ($action === 'count-users') {
                        $controller->countUsers($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === null) {
                        $controller->store();
                    } elseif ($id === 'import') {
                        $controller->import();
                    } elseif ($action === 'activate') {
                        $controller->activate($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id !== null && $action === null) {
                        $controller->update($id);
                    } elseif ($id !== null && $action === 'activate') {
                        $controller->activate($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'DELETE':
                    if ($id !== null && $action === null) {
                        $controller->destroy($id);
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rota pública para assinatura de documentos
        case 'sign':
            $controller = new SignatureController();
            
            switch ($method) {
                case 'GET':
                    if ($id !== null) {
                        $controller->getSigningPage($id); // $id é o token
                    } else {
                        Response::notFound('Token não fornecido');
                    }
                    break;
                    
                case 'POST':
                    if ($id !== null) {
                        $controller->sign($id); // $id é o token
                    } else {
                        Response::notFound('Token não fornecido');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        // Rotas Públicas
        case 'public':
            $publicResource = $id ?? '';
            $publicId = $action ?? null;
            
            switch ($publicResource) {
                case 'plans':
                    $controller = new PlanController();
                    
                    switch ($method) {
                        case 'GET':
                            if ($publicId === null) {
                                $controller->publicIndex();
                            } else {
                                $controller->publicShow($publicId);
                            }
                            break;
                            
                        default:
                            Response::error('Método não permitido', 405);
                    }
                    break;
                    
                default:
                    Response::notFound('Recurso público não encontrado');
            }
            break;
            
        // Rotas de Uso/Usage
        case 'usage':
            require_once __DIR__ . '/../controllers/UsageController.php';
            $controller = new UsageController();
            
            switch ($method) {
                case 'GET':
                    if ($id === 'current') {
                        $controller->current();
                    } elseif ($id === 'history') {
                        $controller->history();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                default:
                    Response::error('Método não permitido', 405);
            }
            break;
            
        default:
            Response::notFound('Recurso não encontrado');
    }
    
} catch (Exception $e) {
    Response::handleException($e);
}

?>