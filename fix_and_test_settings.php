<?php

require_once 'backend/config/database.php';
require_once 'backend/models/Settings.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    $settings = new Settings();
    
    echo "=== CORREÇÃO E TESTE DE CONFIGURAÇÕES ===\n\n";
    
    // 1. Corrigir o campo allowed_file_types
    echo "1. Corrigindo campo allowed_file_types...\n";
    $sql = "UPDATE configuracoes SET valor = 'pdf,doc,docx,jpg,jpeg,png' WHERE chave = 'allowed_file_types'";
    $stmt = $conn->prepare($sql);
    if ($stmt->execute()) {
        echo "✅ Campo allowed_file_types atualizado com sucesso!\n";
    } else {
        echo "❌ Erro ao atualizar allowed_file_types\n";
    }
    
    // 2. Verificar todos os valores atuais
    echo "\n2. Valores atuais na base de dados:\n";
    echo "===================================\n";
    $allSettings = $settings->getAll();
    foreach ($allSettings as $key => $value) {
        $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
        echo "- $key = '$displayValue'\n";
    }
    
    // 3. Simular resposta da API /settings
    echo "\n3. Simulação da resposta da API /settings:\n";
    echo "==========================================\n";
    
    // Simular o que o SettingsController retornaria
    $apiResponse = [];
    
    // Buscar configurações da mesma forma que o controller
    $sql = "SELECT chave, valor, tipo FROM configuracoes WHERE ativo = 1";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    while ($row = $stmt->fetch()) {
        $value = $row['valor'];
        
        // Converter valores baseado no tipo
        switch ($row['tipo']) {
            case 'boolean':
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                break;
            case 'number':
                $value = is_numeric($value) ? (float)$value : $value;
                break;
            case 'json':
                $value = json_decode($value, true);
                break;
            // 'string' mantém como está
        }
        
        $apiResponse[$row['chave']] = $value;
    }
    
    echo json_encode($apiResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    
    // 4. Verificar campos específicos importantes
    echo "\n\n4. Verificação de campos críticos:\n";
    echo "==================================\n";
    
    $criticalFields = [
        'app_name' => 'Nome da aplicação',
        'allowed_file_types' => 'Tipos de arquivo permitidos',
        'max_file_size' => 'Tamanho máximo de arquivo',
        'email_notifications' => 'Notificações por email'
    ];
    
    foreach ($criticalFields as $field => $description) {
        if (isset($apiResponse[$field])) {
            $value = $apiResponse[$field];
            $displayValue = is_bool($value) ? ($value ? 'true' : 'false') : $value;
            echo "✅ $description ($field): '$displayValue'\n";
        } else {
            echo "❌ $description ($field): CAMPO NÃO ENCONTRADO\n";
        }
    }
    
    echo "\n=== TESTE CONCLUÍDO ===\n";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

?>