<?php
require_once 'config.php';

try {
    $pdo->exec("DELETE FROM operacoes");
    header('Location: index.php?success=Histórico limpo com sucesso!');
    exit;
} catch (PDOException $e) {
    header('Location: index.php?error=Erro ao limpar histórico: ' . urlencode($e->getMessage()));
    exit;
}
