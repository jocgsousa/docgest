<?php
require_once 'config/database.php';
require_once 'models/Profession.php';

echo "=== TESTE DE CORREÇÃO DE CODIFICAÇÃO ===\n\n";

// Configurar conexão com banco
$database = new Database();
$db = $database->getConnection();

// Simular upload do arquivo CSV
$csvFile = 'c:/Users/TI/Desktop/SOLUCOES/_DEV/docgest/cbo2002-ocupacao.csv';

if (!file_exists($csvFile)) {
    die("Arquivo CSV não encontrado: $csvFile\n");
}

echo "Arquivo CSV encontrado: $csvFile\n";

// Detectar codificação do arquivo
$content = file_get_contents($csvFile);
$encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
echo "Codificação detectada: " . ($encoding ?: 'Desconhecida') . "\n\n";

// Simular o processo de conversão como no controller
$tempFile = null;
$handle = fopen($csvFile, 'r');

if ($encoding && $encoding !== 'UTF-8') {
    echo "Convertendo arquivo de $encoding para UTF-8...\n";
    
    // Criar arquivo temporário
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_utf8_');
    $tempHandle = fopen($tempFile, 'w');
    
    // Converter linha por linha
    $lineCount = 0;
    while (($line = fgets($handle)) !== false) {
        $convertedLine = mb_convert_encoding($line, 'UTF-8', $encoding);
        fwrite($tempHandle, $convertedLine);
        $lineCount++;
    }
    
    fclose($tempHandle);
    fclose($handle);
    
    echo "Arquivo convertido criado: $tempFile\n";
    echo "Linhas convertidas: $lineCount\n\n";
    
    // Abrir arquivo convertido
    $handle = fopen($tempFile, 'r');
}

// Testar algumas linhas específicas com caracteres especiais
echo "=== TESTANDO LINHAS COM CARACTERES ESPECIAIS ===\n";

$testLines = [
    1135, // Agente fiscal têxtil
    1136, // Outras linhas com acentos
    1137,
    1138,
    1139
];

$lineNumber = 0;
$testedLines = [];

while (($data = fgetcsv($handle, 1000, ';')) !== false) {
    $lineNumber++;
    
    if (in_array($lineNumber, $testLines)) {
        if (count($data) >= 2) {
            $codigo = trim($data[0]);
            $titulo = trim($data[1]);
            
            $testedLines[] = [
                'linha' => $lineNumber,
                'codigo' => $codigo,
                'titulo' => $titulo,
                'tem_especiais' => preg_match('/[áàâãéèêíìîóòôõúùûç]/i', $titulo)
            ];
            
            echo "Linha $lineNumber: [$codigo] $titulo\n";
            
            if (count($testedLines) >= 5) {
                break;
            }
        }
    }
}

fclose($handle);

// Limpar arquivo temporário
if ($tempFile && file_exists($tempFile)) {
    unlink($tempFile);
    echo "\nArquivo temporário removido.\n";
}

echo "\n=== RESUMO DOS TESTES ===\n";
foreach ($testedLines as $line) {
    $status = $line['tem_especiais'] ? '✓ Caracteres especiais detectados' : '✗ Sem caracteres especiais';
    echo "Linha {$line['linha']}: {$line['titulo']} - $status\n";
}

echo "\n=== TESTE DE INSERÇÃO NO BANCO ===\n";

// Testar inserção de uma profissão com caracteres especiais
$testProfession = [
    'nome' => 'Agente fiscal têxtil',
    'descricao' => 'Profissional responsável pela fiscalização têxtil com acentuação'
];

try {
    $profession = new Profession($db);
    
    // Verificar se já existe
    $existing = $profession->findByName($testProfession['nome']);
    
    if ($existing) {
        echo "Profissão já existe no banco: {$testProfession['nome']}\n";
        echo "Descrição atual: {$existing['descricao']}\n";
    } else {
        $result = $profession->create($testProfession);
        if ($result) {
            echo "✓ Profissão criada com sucesso: {$testProfession['nome']}\n";
        } else {
            echo "✗ Erro ao criar profissão\n";
        }
    }
    
} catch (Exception $e) {
    echo "✗ Erro: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>