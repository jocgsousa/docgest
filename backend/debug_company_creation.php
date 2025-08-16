<?php
require_once 'utils/Validator.php';
require_once 'utils/Response.php';
require_once 'models/Company.php';

echo "=== DEBUG CRIAÇÃO DE EMPRESA ===\n";

// Dados de teste
$input = [
    'nome' => 'Empresa Teste Debug',
    'cnpj' => '79.432.023/0001-25',
    'email' => 'teste.debug@empresa.com',
    'telefone' => '(11) 99999-9999',
    'plano_id' => 1,
    'data_vencimento' => '2025-09-14'
];

echo "Dados de entrada:\n";
print_r($input);

echo "\n1. Testando validação com Validator:\n";

// Criar validator
$validator = Validator::make($input)
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
    ->date('data_vencimento', 'Y-m-d', 'Data de vencimento deve ser válida no formato Y-m-d (AAAA-MM-DD)');

if ($validator->hasErrors()) {
    echo "ERROS DE VALIDAÇÃO:\n";
    print_r($validator->getErrors());
} else {
    echo "Validação passou sem erros\n";
}

echo "\n2. Testando verificação de CNPJ duplicado:\n";

// Limpar CNPJ
$cnpj = preg_replace('/[^0-9]/', '', $input['cnpj']);
echo "CNPJ limpo: {$cnpj}\n";

// Verificar se CNPJ já existe
$companyModel = new Company();
$cnpjExists = $companyModel->cnpjExists($cnpj);

echo "CNPJ já existe? " . ($cnpjExists ? 'SIM' : 'NÃO') . "\n";

if ($cnpjExists) {
    echo "ERRO: Este CNPJ já está em uso\n";
    
    // Buscar empresa com este CNPJ
    $existingCompany = $companyModel->findByCnpj($cnpj);
    if ($existingCompany) {
        echo "Empresa existente:\n";
        print_r($existingCompany);
    }
} else {
    echo "CNPJ disponível para uso\n";
}

echo "\n3. Testando verificação de email duplicado:\n";

$emailExists = $companyModel->emailExists($input['email']);
echo "Email já existe? " . ($emailExists ? 'SIM' : 'NÃO') . "\n";

if ($emailExists) {
    echo "ERRO: Este email já está em uso\n";
} else {
    echo "Email disponível para uso\n";
}

echo "\n=== RESUMO ===\n";
if ($validator->hasErrors()) {
    echo "PROBLEMA: Erros de validação encontrados\n";
} elseif ($cnpjExists) {
    echo "PROBLEMA: CNPJ já existe no banco\n";
} elseif ($emailExists) {
    echo "PROBLEMA: Email já existe no banco\n";
} else {
    echo "TUDO OK: Empresa pode ser criada\n";
}
?>