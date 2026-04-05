<?php
// config/database.php
$host = "localhost";
$db_name = "u454088166_financas_db";
$username = "u454088166_nogueira";
$password = "F@lxmen30"; // No XAMPP/WAMP por padrão é vazio

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
}
?>