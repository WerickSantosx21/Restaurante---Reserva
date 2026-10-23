<?php 
//indica o endereço do servidor do banco.
$host = 'localhost';
//informa o nome do banco que queremos acessar.
$banco = 'restaurante';
//define a conta que o PHP utilizará para se conectar.
$usuario = 'root';

$senha = '';
//permite armazenar corretamente acentos, símbolos e emojis.
$charset = 'utf8mb4'; 
//O DSN (Data Source Name) reúne o tipo de banco, o endereço, o nome do banco e a codificação.
$dsn = "mysql:host=$host;dbname=$banco;charset=$charset";

try{
    $pdo = new PDO($dsn, $usuario, $senha);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erro ao conectar com o banco de dados:.");
}

?>