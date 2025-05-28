<?php
require_once 'config.php';

// Verificar se foi feito POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $inicioMobilizacao = $_POST['mobilizacaoInicio'];
    $fimMobilizacao = !empty($_POST['mobilizacaoFim']) ? $_POST['mobilizacaoFim'] : null;
    $localMobilizacao = $_POST['localMobilizacao'];
    $status = $_POST['mobilizacaoStatus'];
    $observacoes = isset($_POST['observacoes']) ? $_POST['observacoes'] : ''; // Define como string vazia se não existir

    // Calcular duração em segundos se tiver início e fim
    $duracaoSegundos = null;
    if (!empty($inicioMobilizacao) && !empty($fimMobilizacao)) {
        $inicio = new DateTime($inicioMobilizacao);
        $fim = new DateTime($fimMobilizacao);
        $intervalo = $inicio->diff($fim);
        $duracaoSegundos = $intervalo->h * 3600 + $intervalo->i * 60 + $intervalo->s;
    }

    try {
        // Verificar se a tabela existe
        try {
            $pdo->query("SELECT 1 FROM mobilizacoes LIMIT 1");
        } catch (PDOException $e) {
            // Se a tabela não existir, redirecionamos para o setup
            header("Location: setup_mobilizacao.php");
            exit;
        }

        // Inserir no banco de dados
        $stmt = $pdo->prepare("
            INSERT INTO mobilizacoes 
            (inicio_mobilizacao, fim_mobilizacao, local_mobilizacao, status, duracao_segundos, observacoes) 
            VALUES 
            (:inicioMobilizacao, :fimMobilizacao, :localMobilizacao, :status, :duracaoSegundos, :observacoes)
        ");

        $stmt->execute([
            ':inicioMobilizacao' => $inicioMobilizacao,
            ':fimMobilizacao' => $fimMobilizacao,
            ':localMobilizacao' => $localMobilizacao,
            ':status' => $status,
            ':duracaoSegundos' => $duracaoSegundos,
            ':observacoes' => $observacoes
        ]);

        // Redirecionar com mensagem de sucesso
        header("Location: mobilizacao.php?success=" . urlencode("Mobilização registrada com sucesso!"));
        exit;
    } catch (PDOException $e) {
        // Redirecionar com mensagem de erro
        header("Location: mobilizacao.php?error=" . urlencode("Erro ao registrar mobilização: " . $e->getMessage()));
        exit;
    }
} else {
    // Se não for POST, redirecionar para a página de mobilização
    header("Location: mobilizacao.php");
    exit;
}
