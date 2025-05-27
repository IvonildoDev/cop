<?php
// filepath: c:\xampp\htdocs\cop\registrar_aguardo.php
require_once 'config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $acao = $_POST['acao'] ?? '';
    $response = ['success' => false, 'message' => ''];

    try {
        if ($acao == 'iniciar') {
            $motivo = $_POST['motivo'] ?? '';
            $inicio = $_POST['inicio'] ?? date('Y-m-d H:i:s');

            // Inserir novo registro de aguardo
            $stmt = $pdo->prepare("INSERT INTO aguardos (inicio_aguardo, motivo) VALUES (:inicio, :motivo)");
            $stmt->execute([
                ':inicio' => $inicio,
                ':motivo' => $motivo
            ]);

            $aguardoId = $pdo->lastInsertId();
            $response = [
                'success' => true,
                'message' => 'Aguardo iniciado com sucesso!',
                'id' => $aguardoId
            ];

            // Armazenar o ID na sessão para uso posterior
            session_start();
            $_SESSION['aguardo_atual_id'] = $aguardoId;
        } elseif ($acao == 'finalizar') {
            session_start();
            $aguardoId = $_SESSION['aguardo_atual_id'] ?? 0;
            $fim = $_POST['fim'] ?? date('Y-m-d H:i:s');
            $duracao = $_POST['duracao'] ?? 0;

            if ($aguardoId) {
                $stmt = $pdo->prepare("UPDATE aguardos SET fim_aguardo = :fim, duracao_segundos = :duracao WHERE id = :id");
                $stmt->execute([
                    ':fim' => $fim,
                    ':duracao' => $duracao,
                    ':id' => $aguardoId
                ]);

                $response = [
                    'success' => true,
                    'message' => 'Aguardo finalizado com sucesso!'
                ];

                // Limpar o ID da sessão
                unset($_SESSION['aguardo_atual_id']);
            } else {
                $response = [
                    'success' => false,
                    'message' => 'ID de aguardo não encontrado na sessão'
                ];
            }
        } else {
            $response = [
                'success' => false,
                'message' => 'Ação desconhecida'
            ];
        }
    } catch (PDOException $e) {
        $response = [
            'success' => false,
            'message' => 'Erro no banco de dados: ' . $e->getMessage()
        ];
    }

    echo json_encode($response);
    exit;
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Método de requisição inválido'
    ]);
    exit;
}
