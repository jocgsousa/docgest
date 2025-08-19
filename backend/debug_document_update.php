<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config/database.php';
require_once 'models/Document.php';

echo "=== DEBUG DOCUMENT UPDATE ===\n";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n";

// Simular o processamento PUT como no DocumentController
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $input = file_get_contents('php://input');
    
    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false) {
        echo "\n=== PROCESSING MULTIPART DATA ===\n";
        parseMultipartFormData($input);
    }
}

echo "\n=== \$_POST DATA ===\n";
var_dump($_POST);

echo "\n=== \$_FILES DATA ===\n";
var_dump($_FILES);

// Testar atualização se ID fornecido
if (isset($_GET['id'])) {
    $id = $_GET['id'];
    echo "\n=== TESTING UPDATE FOR DOCUMENT ID: $id ===\n";
    
    $database = new Database();
    $db = $database->getConnection();
    $document = new Document($db);
    
    // Buscar documento atual
    $currentDoc = $document->findById($id);
    echo "Current document:\n";
    var_dump($currentDoc);
    
    // Preparar dados de atualização
    $updateData = [];
    if (isset($_POST['titulo'])) $updateData['titulo'] = $_POST['titulo'];
    if (isset($_POST['descricao'])) $updateData['descricao'] = $_POST['descricao'];
    if (isset($_POST['empresa_id'])) $updateData['empresa_id'] = $_POST['empresa_id'];
    if (isset($_POST['filial_id'])) $updateData['filial_id'] = $_POST['filial_id'];
    
    // Processar arquivo se enviado
    if (isset($_FILES['arquivo']) && $_FILES['arquivo']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['arquivo'];
        echo "\nFile data:\n";
        var_dump($file);
        
        $updateData['nome_arquivo'] = $file['name'];
        $updateData['caminho_arquivo'] = 'uploads/documents/test_' . $file['name'];
        $updateData['tamanho_arquivo'] = $file['size'];
        $updateData['tipo_arquivo'] = $file['type'];
    }
    
    echo "\nUpdate data:\n";
    var_dump($updateData);
    
    if (!empty($updateData)) {
        $result = $document->update($id, $updateData);
        echo "\nUpdate result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";
        
        if ($result) {
            $updatedDoc = $document->findById($id);
            echo "Updated document:\n";
            var_dump($updatedDoc);
        }
    }
}

function parseMultipartFormData($input) {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    preg_match('/boundary=(.*)$/', $contentType, $matches);
    if (!isset($matches[1])) {
        return;
    }
    
    $boundary = '--' . $matches[1];
    $parts = explode($boundary, $input);
    
    foreach ($parts as $part) {
        if (empty(trim($part)) || $part === '--') {
            continue;
        }
        
        $sections = explode("\r\n\r\n", $part, 2);
        if (count($sections) !== 2) {
            continue;
        }
        
        $headers = $sections[0];
        $content = rtrim($sections[1], "\r\n");
        
        if (preg_match('/name="([^"]+)"/', $headers, $nameMatches)) {
            $fieldName = $nameMatches[1];
            
            if (strpos($headers, 'filename=') !== false) {
                if (preg_match('/filename="([^"]+)"/', $headers, $filenameMatches)) {
                    $filename = $filenameMatches[1];
                    
                    $contentType = 'application/octet-stream';
                    if (preg_match('/Content-Type: (.+)/', $headers, $typeMatches)) {
                        $contentType = trim($typeMatches[1]);
                    }
                    
                    $tempFile = tempnam(sys_get_temp_dir(), 'upload_');
                    file_put_contents($tempFile, $content);
                    
                    $_FILES[$fieldName] = [
                        'name' => $filename,
                        'type' => $contentType,
                        'size' => strlen($content),
                        'tmp_name' => $tempFile,
                        'error' => UPLOAD_ERR_OK
                    ];
                }
            } else {
                $_POST[$fieldName] = $content;
            }
        }
    }
}
?>