<?php

// Include database connection
require_once 'config.php';

// Check if user is logged in (add your authentication check here)
// session_start();
// if(!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit;
// }

// Check if ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $_SESSION['error'] = "ID da operação não fornecido.";
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

try {
    // First check if operation exists
    $checkStmt = $pdo->prepare("SELECT id FROM operacoes WHERE id = ?");
    $checkStmt->execute([$id]);

    if ($checkStmt->rowCount() === 0) {
        $_SESSION['error'] = "Operação não encontrada.";
        header('Location: index.php');
        exit;
    }

    // Begin transaction
    $pdo->beginTransaction();

    // Optional: Delete related records first if you have foreign key constraints
    // For example:
    // $pdo->prepare("DELETE FROM operacao_recursos WHERE operacao_id = ?")->execute([$id]);

    // Delete the operation
    $stmt = $pdo->prepare("DELETE FROM operacoes WHERE id = ?");
    $stmt->execute([$id]);

    // Commit transaction
    $pdo->commit();

    $_SESSION['success'] = "Operação excluída com sucesso.";
} catch (PDOException $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $_SESSION['error'] = "Erro ao excluir operação: " . $e->getMessage();
}

// Redirect back to the listing page
header('Location: index.php');
exit;
