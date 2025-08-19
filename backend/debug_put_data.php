<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

echo "=== DEBUG PUT DATA ===\n";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "CONTENT_TYPE: " . ($_SERVER['CONTENT_TYPE'] ?? 'not set') . "\n";
echo "\n=== \$_POST ===\n";
var_dump($_POST);
echo "\n=== \$_REQUEST ===\n";
var_dump($_REQUEST);
echo "\n=== \$_FILES ===\n";
var_dump($_FILES);
echo "\n=== php://input ===\n";
$input = file_get_contents('php://input');
echo "Length: " . strlen($input) . "\n";
echo "First 500 chars: " . substr($input, 0, 500) . "\n";

// Tentar processar dados PUT
if ($_SERVER['REQUEST_METHOD'] === 'PUT' && empty($_POST)) {
    echo "\n=== PROCESSING PUT DATA ===\n";
    
    if (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false) {
        echo "Detected multipart/form-data\n";
        $_POST = array_merge($_POST, $_REQUEST);
        echo "After merge with \$_REQUEST:\n";
        var_dump($_POST);
    } else {
        echo "Not multipart/form-data, trying parse_str\n";
        $putData = [];
        parse_str($input, $putData);
        $_POST = array_merge($_POST, $putData);
        echo "After parse_str:\n";
        var_dump($_POST);
    }
}
?>