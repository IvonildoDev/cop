<?php
// filepath: c:\xampp\htdocs\cop\save_refeicao.php
require_once 'config.php';

// Verificar se foi feito POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $tipo = $_POST['tipoRefeicaoHidden'] ?? '';
    $inicioRefeicao = $_POST['refeicaoInicio'] ?? '';
    $fimRefeicao = !empty($_POST['refeicaoFim']) ? $_POST['refeicaoFim'] : null;
    $status = $_POST['refeicaoStatus'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';

    // Validações
    if (empty($tipo)) {
        header("Location: refeicao.php?error=" . urlencode("Selecione um tipo de refeição"));
        exit;
    }

    if (empty($inicioRefeicao)) {
        header("Location: refeicao.php?error=" . urlencode("É necessário iniciar a refeição"));
        exit;
    }

    // Calcular duração se tiver início e fim
    $duracaoSegundos = null;
    if (!empty($inicioRefeicao) && !empty($fimRefeicao)) {
        $inicio = new DateTime($inicioRefeicao);
        $fim = new DateTime($fimRefeicao);
        $intervalo = $inicio->diff($fim);
        $duracaoSegundos = $intervalo->h * 3600 + $intervalo->i * 60 + $intervalo->s;
    }

    try {
        // Inserir no banco de dados
        $stmt = $pdo->prepare("
            INSERT INTO refeicoes 
            (tipo, inicio_refeicao, fim_refeicao, duracao_segundos, observacoes) 
            VALUES 
            (:tipo, :inicioRefeicao, :fimRefeicao, :duracaoSegundos, :observacoes)
        ");

        $stmt->execute([
            ':tipo' => $tipo,
            ':inicioRefeicao' => $inicioRefeicao,
            ':fimRefeicao' => $fimRefeicao,
            ':duracaoSegundos' => $duracaoSegundos,
            ':observacoes' => $observacoes
        ]);

        // Obter nome legível do tipo
        $tipoNome = '';
        switch ($tipo) {
            case 'cafe_manha':
                $tipoNome = 'Café da Manhã';
                break;
            case 'almoco':
                $tipoNome = 'Almoço';
                break;
            case 'jantar':
                $tipoNome = 'Jantar';
                break;
            case 'lanche':
                $tipoNome = 'Lanche';
                break;
        }

        // Redirecionar com mensagem de sucesso
        header("Location: refeicao.php?success=" . urlencode("Refeição ({$tipoNome}) registrada com sucesso!"));
        exit;
    } catch (PDOException $e) {
        // Redirecionar com mensagem de erro
        header("Location: refeicao.php?error=" . urlencode("Erro ao registrar refeição: " . $e->getMessage()));
        exit;
    }
} else {
    // Se não for POST, redirecionar para a página de refeição
    header("Location: refeicao.php");
    exit;
}
