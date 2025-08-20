<?php

require_once 'backend/config/database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    echo "=== VERIFICAÇÃO DE CAMPOS DE CONFIGURAÇÃO ===\n\n";
    
    // Buscar todos os campos da base de dados
    $sql = "SELECT chave, valor, tipo, descricao, categoria FROM configuracoes WHERE ativo = 1 ORDER BY categoria, chave";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    
    $dbFields = [];
    $fieldsByCategory = [];
    
    echo "CAMPOS NA BASE DE DADOS:\n";
    echo "========================\n";
    
    while ($row = $stmt->fetch()) {
        $dbFields[] = $row['chave'];
        $fieldsByCategory[$row['categoria']][] = [
            'chave' => $row['chave'],
            'valor' => $row['valor'],
            'tipo' => $row['tipo'],
            'descricao' => $row['descricao']
        ];
        
        echo "- {$row['chave']} ({$row['categoria']}) = '{$row['valor']}' [{$row['tipo']}]\n";
        echo "  Descrição: {$row['descricao']}\n\n";
    }
    
    // Campos implementados no frontend (baseado no código Settings.js)
    $frontendFields = [
        // Configurações gerais
        'app_name',
        'max_file_size',
        'allowed_file_types',
        
        // Configurações de email
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_from_email',
        'smtp_from_name',
        
        // Configurações de notificação
        'email_notifications',
        'whatsapp_notifications',
        'signature_reminders',
        'expiration_alerts',
        
        // Configurações de segurança
        'password_min_length',
        'require_password_complexity',
        'session_timeout',
        'max_login_attempts',
        
        // Configurações de assinatura
        'signature_expiration_days',
        'auto_reminder_days',
        'max_signers_per_document'
    ];
    
    echo "\n\nCAMPOS IMPLEMENTADOS NO FRONTEND:\n";
    echo "=================================\n";
    foreach ($frontendFields as $field) {
        echo "- $field\n";
    }
    
    // Verificar campos que estão na BD mas não no frontend
    $missingInFrontend = array_diff($dbFields, $frontendFields);
    
    echo "\n\nCAMPOS NA BD MAS NÃO NO FRONTEND:\n";
    echo "=================================\n";
    if (empty($missingInFrontend)) {
        echo "✅ Todos os campos da BD estão implementados no frontend!\n";
    } else {
        foreach ($missingInFrontend as $field) {
            // Buscar detalhes do campo
            foreach ($fieldsByCategory as $category => $fields) {
                foreach ($fields as $fieldData) {
                    if ($fieldData['chave'] === $field) {
                        echo "❌ $field ({$category}) - {$fieldData['descricao']}\n";
                        echo "   Valor atual: '{$fieldData['valor']}' [{$fieldData['tipo']}]\n";
                        break 2;
                    }
                }
            }
        }
    }
    
    // Verificar campos que estão no frontend mas não na BD
    $missingInDB = array_diff($frontendFields, $dbFields);
    
    echo "\n\nCAMPOS NO FRONTEND MAS NÃO NA BD:\n";
    echo "=================================\n";
    if (empty($missingInDB)) {
        echo "✅ Todos os campos do frontend existem na BD!\n";
    } else {
        foreach ($missingInDB as $field) {
            echo "❌ $field - Campo implementado no frontend mas não existe na base de dados\n";
        }
    }
    
    echo "\n\nRESUMO POR CATEGORIA:\n";
    echo "=====================\n";
    foreach ($fieldsByCategory as $category => $fields) {
        echo "\n📁 $category:\n";
        foreach ($fields as $field) {
            $status = in_array($field['chave'], $frontendFields) ? '✅' : '❌';
            echo "  $status {$field['chave']} = '{$field['valor']}'\n";
        }
    }
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}

?>