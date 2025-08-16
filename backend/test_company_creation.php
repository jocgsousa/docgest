<?php

require_once __DIR__ . '/controllers/CompanyController.php';
require_once __DIR__ . '/utils/JWT.php';
require_once __DIR__ . '/config/config.php';

// Simular dados de entrada
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Dados do usuário
$testData = [
    "nome" => "TSTES",
    "cnpj" => "79.432.023/0001-25",
    "email" => "asd@gmail.com",
    "telefone" => "94981413567",
    "endereco" => "Rua Sete de Junho",
    "cidade" => "Marabá",
    "estado" => "PA",
    "cep" => "68500300",
    "plano_id" => "1",
    "data_vencimento" => "2025-09-14"
];

echo "Testando criação de empresa...\n";
echo "Dados: " . json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";

// Simular input JSON
file_put_contents('php://temp', json_encode($testData));

// Testar apenas a validação
try {
    $validator = Validator::make($testData)
        ->required('nome', 'Nome é obrigatório')
        ->min('nome', 2, 'Nome deve ter pelo menos 2 caracteres')
        ->max('nome', 100, 'Nome deve ter no máximo 100 caracteres')
        ->required('cnpj', 'CNPJ é obrigatório')
        ->cnpj('cnpj', 'CNPJ deve ser válido')
        ->required('email', 'Email é obrigatório')
        ->email('email', 'Email deve ser válido')
        ->required('telefone', 'Telefone é obrigatório')
        ->required('plano_id', 'Plano é obrigatório')
        ->exists('plano_id', 'planos', 'id', 'Plano não encontrado')
        ->required('data_vencimento', 'Data de vencimento é obrigatória')
        ->date('data_vencimento', 'Y-m-d', 'Data de vencimento deve ser válida no formato AAAA-MM-DD');
    
    if ($validator->hasErrors()) {
        echo "Erros de validação encontrados:\n";
        echo json_encode($validator->getErrors(), JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Validação passou com sucesso!\n";
    }
    
} catch (Exception $e) {
    echo "Erro durante validação: " . $e->getMessage() . "\n";
}

?>