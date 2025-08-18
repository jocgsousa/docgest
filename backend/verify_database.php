<?php
require_once 'config/database.php';
require_once 'models/Profession.php';

echo "=== VERIFICAÇÃO DO BANCO DE DADOS ===\n\n";

// Configurar conexão com banco
$database = new Database();
$db = $database->getConnection();

$profession = new Profession($db);

// Buscar profissões com caracteres especiais
$testNames = [
    'Agente fiscal têxtil',
    'Agente fiscal metrológico',
    'Avaliador de produtos dos meios de comunicação'
];

echo "Verificando profissões com caracteres especiais no banco:\n\n";

foreach ($testNames as $name) {
    $result = $profession->findByName($name);
    
    if ($result) {
        echo "✓ Encontrada: {$result['nome']}\n";
        echo "  Descrição: {$result['descricao']}\n";
        echo "  ID: {$result['id']}\n\n";
    } else {
        echo "✗ Não encontrada: $name\n\n";
    }
}

// Buscar algumas profissões que podem ter problemas de codificação
echo "=== BUSCA POR PROFISSÕES COM POSSÍVEIS PROBLEMAS ===\n";

try {
    $stmt = $db->prepare("SELECT * FROM profissoes WHERE nome LIKE '%txtil%' OR nome LIKE '%metrolgico%' OR nome LIKE '%comunicao%' LIMIT 10");
    $stmt->execute();
    $problematicProfessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($problematicProfessions) {
        echo "Profissões com possíveis problemas de codificação:\n";
        foreach ($problematicProfessions as $prof) {
            echo "- ID {$prof['id']}: {$prof['nome']}\n";
        }
    } else {
        echo "✓ Nenhuma profissão com problemas de codificação encontrada!\n";
    }
    
} catch (Exception $e) {
    echo "Erro na consulta: " . $e->getMessage() . "\n";
}

echo "\n=== BUSCA POR PROFISSÕES COM CARACTERES ESPECIAIS CORRETOS ===\n";

try {
    $stmt = $db->prepare("SELECT * FROM profissoes WHERE nome LIKE '%têxtil%' OR nome LIKE '%metrológico%' OR nome LIKE '%comunicação%' LIMIT 10");
    $stmt->execute();
    $correctProfessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if ($correctProfessions) {
        echo "Profissões com caracteres especiais corretos:\n";
        foreach ($correctProfessions as $prof) {
            echo "✓ ID {$prof['id']}: {$prof['nome']}\n";
        }
    } else {
        echo "Nenhuma profissão com caracteres especiais corretos encontrada.\n";
    }
    
} catch (Exception $e) {
    echo "Erro na consulta: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICAÇÃO CONCLUÍDA ===\n";
?>