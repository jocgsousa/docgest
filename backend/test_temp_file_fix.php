<?php
require_once 'config/database.php';
require_once 'models/Profession.php';
require_once 'utils/Response.php';

echo "=== TESTE DE CORREÇÃO DO ARQUIVO TEMPORÁRIO ===\n\n";

// Simular o processo de detecção e conversão de codificação
$csvFile = 'c:/Users/TI/Desktop/SOLUCOES/_DEV/docgest/cbo2002-ocupacao.csv';

if (!file_exists($csvFile)) {
    die("Arquivo CSV não encontrado: $csvFile\n");
}

echo "Testando processo de conversão de codificação...\n";

// Ler conteúdo do arquivo
$fileContent = file_get_contents($csvFile);
echo "Arquivo lido: " . strlen($fileContent) . " bytes\n";

// Detectar codificação
$encoding = mb_detect_encoding($fileContent, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
echo "Codificação detectada: " . ($encoding ?: 'Desconhecida') . "\n";

$tempFile = null;
$handle = fopen($csvFile, 'r');

if ($encoding && $encoding !== 'UTF-8') {
    echo "Convertendo de $encoding para UTF-8...\n";
    
    // Converter para UTF-8 e salvar temporariamente
    $utf8Content = mb_convert_encoding($fileContent, 'UTF-8', $encoding);
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_utf8_');
    
    echo "Arquivo temporário criado: $tempFile\n";
    
    $result = file_put_contents($tempFile, $utf8Content);
    echo "Bytes escritos no arquivo temporário: $result\n";
    
    // Verificar se o arquivo foi criado corretamente
    if (file_exists($tempFile)) {
        echo "✓ Arquivo temporário existe\n";
        echo "Tamanho do arquivo temporário: " . filesize($tempFile) . " bytes\n";
    } else {
        echo "✗ Erro: Arquivo temporário não foi criado\n";
        exit(1);
    }
    
    // Fechar handle anterior e abrir o arquivo convertido
    fclose($handle);
    $handle = fopen($tempFile, 'r');
    
    if (!$handle) {
        echo "✗ Erro: Não foi possível abrir o arquivo temporário\n";
        exit(1);
    } else {
        echo "✓ Arquivo temporário aberto com sucesso\n";
    }
} else {
    echo "Arquivo já está em UTF-8, não precisa converter\n";
}

// Testar leitura de algumas linhas
echo "\nTestando leitura do arquivo...\n";
$lineCount = 0;
while (($data = fgetcsv($handle, 1000, ';')) !== false && $lineCount < 5) {
    $lineCount++;
    if (count($data) >= 2) {
        $codigo = trim($data[0]);
        $titulo = trim($data[1]);
        echo "Linha $lineCount: [$codigo] $titulo\n";
    }
}

fclose($handle);

// Limpar arquivo temporário se foi criado
if (isset($tempFile) && file_exists($tempFile)) {
    unlink($tempFile);
    echo "\n✓ Arquivo temporário removido: $tempFile\n";
}

echo "\n=== TESTE CONCLUÍDO COM SUCESSO ===\n";
?>