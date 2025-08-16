<?php

require_once __DIR__ . '/utils/Validator.php';
require_once __DIR__ . '/utils/Response.php';
require_once __DIR__ . '/config/config.php';

echo "=== TESTE DE VALIDAÇÃO DE CNPJ ===\n\n";

// Testar o CNPJ original do usuário
$cnpjOriginal = "79.432.023/0001-25";
echo "1. Testando CNPJ original: $cnpjOriginal\n";

$testData = [
    "cnpj" => $cnpjOriginal
];

try {
    $validator = Validator::make($testData)
        ->cnpj('cnpj', 'CNPJ deve ser válido');
    
    $validator->validate();
    echo "CNPJ '$cnpjOriginal' é VÁLIDO\n\n";
} catch (Exception $e) {
    echo "CNPJ '$cnpjOriginal' é INVÁLIDO: " . $e->getMessage() . "\n\n";
}

// Testar outros formatos de CNPJ
$cnpjTestes = [
    "79432023000125", // Sem formatação
    "79.432.023/0001-25", // Com formatação
    "11.222.333/0001-81", // CNPJ válido
    "12.345.678/0001-90", // CNPJ inválido
    "00.000.000/0000-00"  // CNPJ inválido
];

echo "2. Testando diferentes formatos de CNPJ:\n";
foreach ($cnpjTestes as $cnpj) {
    echo "Testando: $cnpj -> ";
    
    $testData = ["cnpj" => $cnpj];
    
    try {
        $validator = Validator::make($testData)
            ->cnpj('cnpj', 'CNPJ deve ser válido');
        
        $validator->validate();
        echo "VÁLIDO\n";
    } catch (Exception $e) {
        echo "INVÁLIDO\n";
    }
}

echo "\n3. Verificando implementação da validação de CNPJ:\n";

// Vamos ver como está implementada a validação de CNPJ
echo "Verificando se a classe Validator tem o método cnpj...\n";

if (method_exists('Validator', 'cnpj')) {
    echo "Método cnpj existe na classe Validator\n";
} else {
    echo "Método cnpj NÃO existe na classe Validator\n";
}

// Testar validação manual do CNPJ
echo "\n4. Testando validação manual do CNPJ:\n";

function validarCNPJ($cnpj) {
    // Remove caracteres não numéricos
    $cnpj = preg_replace('/[^0-9]/', '', $cnpj);
    
    // Verifica se tem 14 dígitos
    if (strlen($cnpj) != 14) {
        return false;
    }
    
    // Verifica se todos os dígitos são iguais
    if (preg_match('/^(\d)\1{13}$/', $cnpj)) {
        return false;
    }
    
    // Calcula o primeiro dígito verificador
    $soma = 0;
    $multiplicador = 5;
    for ($i = 0; $i < 12; $i++) {
        $soma += $cnpj[$i] * $multiplicador;
        $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
    }
    $resto = $soma % 11;
    $dv1 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Calcula o segundo dígito verificador
    $soma = 0;
    $multiplicador = 6;
    for ($i = 0; $i < 13; $i++) {
        $soma += $cnpj[$i] * $multiplicador;
        $multiplicador = ($multiplicador == 2) ? 9 : $multiplicador - 1;
    }
    $resto = $soma % 11;
    $dv2 = ($resto < 2) ? 0 : 11 - $resto;
    
    // Verifica se os dígitos calculados conferem
    return ($cnpj[12] == $dv1 && $cnpj[13] == $dv2);
}

foreach ($cnpjTestes as $cnpj) {
    $resultado = validarCNPJ($cnpj) ? "VÁLIDO" : "INVÁLIDO";
    echo "$cnpj -> $resultado\n";
}

?>