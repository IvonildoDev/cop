<?php
require_once 'config.php';

// Verificar se foi feito POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $inicioDesmobilizacao = $_POST['desmobilizacaoInicio'];
    $fimDesmobilizacao = !empty($_POST['desmobilizacaoFim']) ? $_POST['desmobilizacaoFim'] : null;
    $localDesmobilizacao = $_POST['localDesmobilizacao'];
    $status = $_POST['desmobilizacaoStatus'];
    $observacoes = $_POST['observacoes'];

    // Calcular duração em segundos se tiver início e fim
    $duracaoSegundos = null;
    if (!empty($inicioDesmobilizacao) && !empty($fimDesmobilizacao)) {
        $inicio = new DateTime($inicioDesmobilizacao);
        $fim = new DateTime($fimDesmobilizacao);
        $intervalo = $inicio->diff($fim);
        $duracaoSegundos = $intervalo->h * 3600 + $intervalo->i * 60 + $intervalo->s;
    }

    try {
        // Inserir no banco de dados
        $stmt = $pdo->prepare("
            INSERT INTO desmobilizacoes 
            (inicio_desmobilizacao, fim_desmobilizacao, local_desmobilizacao, status, duracao_segundos, observacoes) 
            VALUES 
            (:inicioDesmobilizacao, :fimDesmobilizacao, :localDesmobilizacao, :status, :duracaoSegundos, :observacoes)
        ");

        $stmt->execute([
            ':inicioDesmobilizacao' => $inicioDesmobilizacao,
            ':fimDesmobilizacao' => $fimDesmobilizacao,
            ':localDesmobilizacao' => $localDesmobilizacao,
            ':status' => $status,
            ':duracaoSegundos' => $duracaoSegundos,
            ':observacoes' => $observacoes
        ]);

        // Redirecionar com mensagem de sucesso
        header("Location: desmobilizacao.php?success=" . urlencode("Desmobilização registrada com sucesso!"));
        exit;
    } catch (PDOException $e) {
        // Redirecionar com mensagem de erro
        header("Location: desmobilizacao.php?error=" . urlencode("Erro ao registrar desmobilização: " . $e->getMessage()));
        exit;
    }
} else {
    // Se não for POST, redirecionar para a página de desmobilização
    header("Location: desmobilizacao.php");
    exit;
}
