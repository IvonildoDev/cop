<?php
require_once 'config.php';

// Verificar se foi feito POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $inicioAbastecimento = $_POST['abastecimentoInicio'];
    $fimAbastecimento = !empty($_POST['abastecimentoFim']) ? $_POST['abastecimentoFim'] : null;
    $tipo = $_POST['tipoAbastecimentoHidden'];

    // Calculamos a duração apenas se tivermos início e fim
    $duracaoSegundos = null;
    if (!empty($inicioAbastecimento) && !empty($fimAbastecimento)) {
        $inicio = new DateTime($inicioAbastecimento);
        $fim = new DateTime($fimAbastecimento);
        $intervalo = $inicio->diff($fim);
        $duracaoSegundos = $intervalo->h * 3600 + $intervalo->i * 60 + $intervalo->s;
    }

    try {
        // Verificar se a tabela contém campos desnecessários e ajustar a consulta SQL
        $stmt = $pdo->prepare("
            INSERT INTO abastecimentos 
            (inicio_abastecimento, fim_abastecimento, tipo, duracao_segundos) 
            VALUES 
            (:inicioAbastecimento, :fimAbastecimento, :tipo, :duracaoSegundos)
        ");

        $stmt->execute([
            ':inicioAbastecimento' => $inicioAbastecimento,
            ':fimAbastecimento' => $fimAbastecimento,
            ':tipo' => $tipo,
            ':duracaoSegundos' => $duracaoSegundos
        ]);

        // Redirecionar com mensagem de sucesso
        header("Location: abastecimento.php?success=" . urlencode("Abastecimento registrado com sucesso!"));
        exit;
    } catch (PDOException $e) {
        // Redirecionar com mensagem de erro
        header("Location: abastecimento.php?error=" . urlencode("Erro ao registrar abastecimento: " . $e->getMessage()));
        exit;
    }
} else {
    // Se não for POST, redirecionar para a página de abastecimento
    header("Location: abastecimento.php");
    exit;
}
