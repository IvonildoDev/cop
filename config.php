<?php
// Definir fuso horário brasileiro
date_default_timezone_set('America/Sao_Paulo');

$host = 'localhost';
$dbname = 'controle_op';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão: " . $e->getMessage());
}
