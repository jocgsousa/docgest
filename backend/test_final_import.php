<?php
require_once 'config/database.php';
require_once 'models/Profession.php';

echo "=== TESTE FINAL DE IMPORTAÇÃO COM CORREÇÃO DE CODIFICAÇÃO ===\n\n";

// Configurar conexão com banco
$database = new Database();
$db = $database->getConnection();

// Simular upload do arquivo CSV
$csvFile = 'c:/Users/TI/Desktop/SOLUCOES/_DEV/docgest/cbo2002-ocupacao.csv';

if (!file_exists($csvFile)) {
    die("Arquivo CSV não encontrado: $csvFile\n");
}

echo "Arquivo CSV encontrado: $csvFile\n";

// Simular o processo completo do ProfessionController::import()
$startTime = microtime(true);
$imported = 0;
$updated = 0;
$errors = [];
$lineNumber = 0;

// Detectar codificação do arquivo
$content = file_get_contents($csvFile);
$encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true);
echo "Codificação detectada: " . ($encoding ?: 'Desconhecida') . "\n";

$tempFile = null;
$handle = fopen($csvFile, 'r');

// Converter para UTF-8 se necessário
if ($encoding && $encoding !== 'UTF-8') {
    echo "Convertendo arquivo de $encoding para UTF-8...\n";
    
    $tempFile = tempnam(sys_get_temp_dir(), 'csv_utf8_');
    $tempHandle = fopen($tempFile, 'w');
    
    while (($line = fgets($handle)) !== false) {
        $convertedLine = mb_convert_encoding($line, 'UTF-8', $encoding);
        fwrite($tempHandle, $convertedLine);
    }
    
    fclose($tempHandle);
    fclose($handle);
    
    $handle = fopen($tempFile, 'r');
    echo "Arquivo convertido para UTF-8.\n\n";
}

echo "Iniciando importação...\n";

$profession = new Profession($db);
$testLines = [];

// Processar arquivo CSV
while (($data = fgetcsv($handle, 1000, ';')) !== false) {
    $lineNumber++;
    
    // Pular cabeçalho
    if ($lineNumber === 1) {
        continue;
    }
    
    if (count($data) >= 2) {
        $codigo = trim($data[0]);
        $titulo = trim($data[1]);
        
        // Armazenar algumas linhas com caracteres especiais para teste
        if (preg_match('/[áàâãéèêíìîóòôõúùûç]/i', $titulo) && count($testLines) < 10) {
            $testLines[] = [
                'linha' => $lineNumber,
                'codigo' => $codigo,
                'titulo' => $titulo
            ];
        }
        
        try {
            // Verificar se já existe
            $existing = $profession->findByName($titulo);
            
            if ($existing) {
                // Atualizar
                $result = $profession->updateByName($titulo, [
                    'nome' => $titulo,
                    'descricao' => "Profissão CBO: $codigo - $titulo"
                ]);
                
                if ($result) {
                    $updated++;
                }
            } else {
                // Criar nova
                $result = $profession->create([
                    'nome' => $titulo,
                    'descricao' => "Profissão CBO: $codigo - $titulo"
                ]);
                
                if ($result) {
                    $imported++;
                }
            }
        } catch (Exception $e) {
            $errors[] = "Linha {$lineNumber}: {$e->getMessage()} - Código: '{$codigo}', Título: '{$titulo}'";
        }
    }
    
    // Mostrar progresso a cada 500 linhas
    if ($lineNumber % 500 === 0) {
        echo "Processadas $lineNumber linhas...\n";
    }
}

fclose($handle);

// Limpar arquivo temporário
if ($tempFile && file_exists($tempFile)) {
    unlink($tempFile);
}

$endTime = microtime(true);
$executionTime = round($endTime - $startTime, 2);

echo "\n=== RESULTADOS DA IMPORTAÇÃO ===\n";
echo "Tempo de execução: {$executionTime}s\n";
echo "Linhas processadas: " . ($lineNumber - 1) . "\n";
echo "Profissões criadas: $imported\n";
echo "Profissões atualizadas: $updated\n";
echo "Total processado: " . ($imported + $updated) . "\n";
echo "Erros: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\nPrimeiros 5 erros:\n";
    foreach (array_slice($errors, 0, 5) as $error) {
        echo "- $error\n";
    }
}

echo "\n=== LINHAS COM CARACTERES ESPECIAIS PROCESSADAS ===\n";
foreach ($testLines as $line) {
    echo "Linha {$line['linha']}: [{$line['codigo']}] {$line['titulo']}\n";
    
    // Verificar se foi salva corretamente no banco
    $saved = $profession->findByName($line['titulo']);
    if ($saved) {
        echo "  ✓ Salva no banco: {$saved['nome']}\n";
    } else {
        echo "  ✗ Não encontrada no banco\n";
    }
}

echo "\n=== TESTE FINAL CONCLUÍDO ===\n";

// Verificar algumas profissões específicas
echo "\n=== VERIFICAÇÃO DE PROFISSÕES ESPECÍFICAS ===\n";
$specificTests = [
    'Agente fiscal têxtil',
    'Agente fiscal metrológico'
];

foreach ($specificTests as $name) {
    $result = $profession->findByName($name);
    if ($result) {
        echo "✓ $name - Encontrada (ID: {$result['id']})\n";
    } else {
        echo "✗ $name - Não encontrada\n";
    }
}
?>