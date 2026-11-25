<?php 

$host = 'localhost';

$banco = 'restaurante';

$usuario = 'root';

$senha = '';

$charset = 'utf8mb4'; 

$dsn = "mysql:host=$host;dbname=$banco;charset=$charset";

try{
    $pdo = new PDO($dsn, $usuario, $senha);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro ao conectar com o banco de dados:.");
}

?>