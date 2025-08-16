<?php

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/UserController.php';
require_once __DIR__ . '/../controllers/CompanyController.php';
require_once __DIR__ . '/../controllers/PlanController.php';
require_once __DIR__ . '/../controllers/DocumentController.php';
require_once __DIR__ . '/../controllers/SignatureController.php';
require_once __DIR__ . '/../controllers/DashboardController.php';
require_once __DIR__ . '/../controllers/ReportController.php';
require_once __DIR__ . '/../controllers/SettingsController.php';
require_once __DIR__ . '/../controllers/LogController.php';
require_once __DIR__ . '/../controllers/BranchController.php';
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
                    } elseif ($action === null) {
                        $controller->show($id);
                    } elseif ($action === 'download') {
                        $controller->download($id);
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
                        $controller->index();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'PUT':
                    if ($id === null) {
                        $controller->update();
                    } else {
                        Response::notFound('Endpoint não encontrado');
                    }
                    break;
                    
                case 'POST':
                    if ($id === 'reset') {
                        $controller->reset();
                    } elseif ($id === 'test-email') {
                        $controller->testEmail();
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
            
        default:
            Response::notFound('Recurso não encontrado');
    }
    
} catch (Exception $e) {
    Response::handleException($e);
}

?>