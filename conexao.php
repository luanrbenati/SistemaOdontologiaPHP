<?php
// conexao.php
$host = 'localhost';
$db   = 'srv_odonto'; 
$user = 'Odonto';              
$pass = 'Odonto@123';                  
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // Em produção, não mostre o erro na tela, grave num log
    die("Erro de conexão com o banco de dados.");
}
?>