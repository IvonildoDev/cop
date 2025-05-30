<?php
require_once 'config.php';

// Verificar se foi feito POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $inicioOperacao = $_POST['inicioOperacao'];
    $fimOperacao = $_POST['fimOperacao'];
    $nomeOpAux = $_POST['nomeOpAux'];
    $tipoOperacao = $_POST['tipoOperacao'];

    // Tratar a cidade (normal ou outra)
    if ($_POST['nomeCidade'] == 'outra') {
        $nomeCidade = $_POST['outraCidade'];
    } else {
        $nomeCidade = $_POST['nomeCidade'];
    }

    $nomePocoServ = $_POST['nomePocoServ'];
    $nomeOperador = $_POST['nomeOperador'];
    $volumeBbl = $_POST['volumeBbl'];
    $temperatura = $_POST['temperatura'];
    $pressao = $_POST['pressao'];
    $descricaoAtividades = $_POST['descricaoAtividades'];

    // Validação básica
    if (empty($inicioOperacao) || empty($volumeBbl)) {
        header("Location: operacao.php?error=Todos os campos obrigatórios devem ser preenchidos");
        exit;
    }

    try {
        // Inserir no banco de dados
        $stmt = $pdo->prepare("
            INSERT INTO operacoes 
            (inicio_operacao, fim_operacao, nome_op_aux, tipo_operacao, nome_cidade, 
             nome_poco_serv, nome_operador, volume_bbl, temperatura, pressao, descricao_atividades) 
            VALUES 
            (:inicioOperacao, :fimOperacao, :nomeOpAux, :tipoOperacao, :nomeCidade,
             :nomePocoServ, :nomeOperador, :volumeBbl, :temperatura, :pressao, :descricaoAtividades)
        ");

        $stmt->execute([
            ':inicioOperacao' => $inicioOperacao,
            ':fimOperacao' => $fimOperacao,
            ':nomeOpAux' => $nomeOpAux,
            ':tipoOperacao' => $tipoOperacao,
            ':nomeCidade' => $nomeCidade,
            ':nomePocoServ' => $nomePocoServ,
            ':nomeOperador' => $nomeOperador,
            ':volumeBbl' => $volumeBbl,
            ':temperatura' => $temperatura,
            ':pressao' => $pressao,
            ':descricaoAtividades' => $descricaoAtividades
        ]);

        // Redirecionar com mensagem de sucesso
        header("Location: index.php?success=" . urlencode("Operação registrada com sucesso!"));
        exit;
    } catch (PDOException $e) {
        // Redirecionar com mensagem de erro
        header("Location: index.php?error=" . urlencode("Erro ao registrar operação: " . $e->getMessage()));
        exit;
    }
} else {
    // Se não for POST, redirecionar para a página principal
    header("Location: index.php");
    exit;
}

// Adicione este código onde são exibidas as informações de cada operação no arquivo generate_pdf.php

// Informações de Mobilização
if (!empty($op['mobilizacao_inicio'])) {
    $pdf->Cell(0, 6, 'Mobilização:', 0, 1);
    $pdf->Cell(10, 6, '', 0, 0);
    $pdf->Cell(0, 6, 'Início: ' . $op['mobilizacao_inicio'], 0, 1);

    if (!empty($op['mobilizacao_fim'])) {
        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Fim: ' . $op['mobilizacao_fim'], 0, 1);

        // Calcular tempo de mobilização
        $inicio = new DateTime($op['mobilizacao_inicio']);
        $fim = new DateTime($op['mobilizacao_fim']);
        $intervalo = $inicio->diff($fim);

        $tempo = '';
        if ($intervalo->h > 0) $tempo .= $intervalo->h . 'h ';
        if ($intervalo->i > 0) $tempo .= $intervalo->i . 'min ';
        if ($intervalo->s > 0) $tempo .= $intervalo->s . 's';

        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Tempo de Mobilização: ' . $tempo, 0, 1);
    } else {
        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Status: Em andamento', 0, 1);
    }
}

// Informações de Desmobilização
if (!empty($op['desmobilizacao_inicio'])) {
    $pdf->Cell(0, 6, 'Desmobilização:', 0, 1);
    $pdf->Cell(10, 6, '', 0, 0);
    $pdf->Cell(0, 6, 'Início: ' . $op['desmobilizacao_inicio'], 0, 1);

    if (!empty($op['desmobilizacao_fim'])) {
        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Fim: ' . $op['desmobilizacao_fim'], 0, 1);

        // Calcular tempo de desmobilização
        $inicio = new DateTime($op['desmobilizacao_inicio']);
        $fim = new DateTime($op['desmobilizacao_fim']);
        $intervalo = $inicio->diff($fim);

        $tempo = '';
        if ($intervalo->h > 0) $tempo .= $intervalo->h . 'h ';
        if ($intervalo->i > 0) $tempo .= $intervalo->i . 'min ';
        if ($intervalo->s > 0) $tempo .= $intervalo->s . 's';

        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Tempo de Desmobilização: ' . $tempo, 0, 1);
    } else {
        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Status: Em andamento', 0, 1);
    }
}

// Informações de Aguardo
if (!empty($op['aguardo_inicio'])) {
    $pdf->Cell(0, 6, 'Aguardo:', 0, 1);
    $pdf->Cell(10, 6, '', 0, 0);
    $pdf->Cell(0, 6, 'Início: ' . $op['aguardo_inicio'], 0, 1);

    if (!empty($op['aguardo_fim'])) {
        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Fim: ' . $op['aguardo_fim'], 0, 1);

        // Calcular tempo de aguardo
        $inicio = new DateTime($op['aguardo_inicio']);
        $fim = new DateTime($op['aguardo_fim']);
        $intervalo = $inicio->diff($fim);

        $tempo = '';
        if ($intervalo->h > 0) $tempo .= $intervalo->h . 'h ';
        if ($intervalo->i > 0) $tempo .= $intervalo->i . 'min ';
        if ($intervalo->s > 0) $tempo .= $intervalo->s . 's';

        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Tempo de Aguardo: ' . $tempo, 0, 1);
    } else {
        $pdf->Cell(10, 6, '', 0, 0);
        $pdf->Cell(0, 6, 'Status: Em andamento', 0, 1);
    }
}
