<?php
require_once 'utils/Validator.php';
require_once 'utils/Response.php';

// Teste específico do CNPJ problemático
$cnpj = '79.432.023/0001-25';

echo "=== DEBUG CNPJ: {$cnpj} ===\n";

// Limpar CNPJ
$cnpj_limpo = preg_replace('/[^0-9]/', '', $cnpj);
echo "CNPJ limpo: {$cnpj_limpo}\n";
echo "Tamanho: " . strlen($cnpj_limpo) . "\n";

// Validação manual passo a passo
if (strlen($cnpj_limpo) != 14) {
    echo "ERRO: Tamanho inválido\n";
    exit;
}

// Primeiro dígito verificador
$weights1 = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
$sum = 0;
for ($i = 0; $i < 12; $i++) {
    $produto = $cnpj_limpo[$i] * $weights1[$i];
    echo "Posição {$i}: {$cnpj_limpo[$i]} x {$weights1[$i]} = {$produto}\n";
    $sum += $produto;
}
echo "Soma total: {$sum}\n";

$remainder = $sum % 11;
echo "Resto da divisão por 11: {$remainder}\n";

$digit1 = $remainder < 2 ? 0 : 11 - $remainder;
echo "Primeiro dígito calculado: {$digit1}\n";
echo "Primeiro dígito no CNPJ: {$cnpj_limpo[12]}\n";

if ($cnpj_limpo[12] != $digit1) {
    echo "ERRO: Primeiro dígito não confere\n";
    exit;
}

echo "Primeiro dígito OK\n";

// Segundo dígito verificador
$weights2 = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
$sum = 0;
for ($i = 0; $i < 13; $i++) {
    $produto = $cnpj_limpo[$i] * $weights2[$i];
    echo "Posição {$i}: {$cnpj_limpo[$i]} x {$weights2[$i]} = {$produto}\n";
    $sum += $produto;
}
echo "Soma total: {$sum}\n";

$remainder = $sum % 11;
echo "Resto da divisão por 11: {$remainder}\n";

$digit2 = $remainder < 2 ? 0 : 11 - $remainder;
echo "Segundo dígito calculado: {$digit2}\n";
echo "Segundo dígito no CNPJ: {$cnpj_limpo[13]}\n";

if ($cnpj_limpo[13] != $digit2) {
    echo "ERRO: Segundo dígito não confere\n";
    exit;
}

echo "Segundo dígito OK\n";
echo "CNPJ VÁLIDO!\n";

// Teste com o Validator
echo "\n=== TESTE COM VALIDATOR ===\n";
$data = ['cnpj' => $cnpj];
$validator = new Validator($data);
$validator->cnpj('cnpj');

if ($validator->hasErrors()) {
    echo "Validator encontrou erros:\n";
    print_r($validator->getErrors());
} else {
    echo "Validator: CNPJ válido\n";
}
?>