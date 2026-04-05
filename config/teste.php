<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "1. Arquivo de teste rodando...<br>";

require_once 'database.php';
echo "2. Conseguiu dar o require na classe...<br>";

$db = new Database();
$conexao = $db->getConnection();

if($conexao) {
    echo "3. SUCESSO! Conectou no MySQL perfeitamente!";
}
?>