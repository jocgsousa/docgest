<?php

require_once __DIR__ . '/utils/Validator.php';
require_once __DIR__ . '/utils/Response.php';

// Teste com os dados fornecidos pelo usuário
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

echo "Testando validação de data...\n";
echo "Data fornecida: " . $testData['data_vencimento'] . "\n";

// Teste 1: Validação com formato padrão Y-m-d
$validator1 = new Validator($testData);
$validator1->date('data_vencimento', 'Y-m-d', 'Data de vencimento deve ser válida no formato Y-m-d');

if ($validator1->hasErrors()) {
    echo "Erro com formato Y-m-d: " . json_encode($validator1->getErrors()) . "\n";
} else {
    echo "Sucesso com formato Y-m-d\n";
}

// Teste 2: Verificar se a data é válida usando DateTime
$date = DateTime::createFromFormat('Y-m-d', $testData['data_vencimento']);
if ($date && $date->format('Y-m-d') === $testData['data_vencimento']) {
    echo "DateTime::createFromFormat funcionou corretamente\n";
    echo "Data formatada: " . $date->format('Y-m-d') . "\n";
} else {
    echo "DateTime::createFromFormat falhou\n";
    if ($date) {
        echo "Data formatada: " . $date->format('Y-m-d') . "\n";
    }
}

// Teste 3: Verificar diferentes formatos
$formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d'];
foreach ($formats as $format) {
    $testDate = DateTime::createFromFormat($format, $testData['data_vencimento']);
    if ($testDate && $testDate->format($format) === $testData['data_vencimento']) {
        echo "Formato {$format}: VÁLIDO\n";
    } else {
        echo "Formato {$format}: INVÁLIDO\n";
    }
}

?>