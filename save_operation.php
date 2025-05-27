<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $inicioOperacao = $_POST['inicioOperacao'] ?? '';
    $nomeOpAux = $_POST['nomeOpAux'] ?? '';
    $tipoOperacao = $_POST['tipoOperacao'] ?? '';
    $nomeCidade = $_POST['nomeCidade'] ?? '';
    $nomePocoServ = $_POST['nomePocoServ'] ?? '';
    $nomeOperador = $_POST['nomeOperador'] ?? '';
    $volumeBbl = $_POST['volumeBbl'] ?? '';
    $temperatura = $_POST['temperatura'] ?? '';
    $pressao = $_POST['pressao'] ?? '';
    $descricaoAtividades = $_POST['descricaoAtividades'] ?? '';
    $fimOperacao = $_POST['fimOperacao'] ?? null;

    // Dados de mobilização e desmobilização
    $mobilizacaoInicio = $_POST['mobilizacaoInicio'] ?? null;
    $mobilizacaoFim = $_POST['mobilizacaoFim'] ?? null;
    $desmobilizacaoInicio = $_POST['desmobilizacaoInicio'] ?? null;
    $desmobilizacaoFim = $_POST['desmobilizacaoFim'] ?? null;
    $mobilizacaoStatus = $_POST['mobilizacaoStatus'] ?? 'Não iniciada';
    $desmobilizacaoStatus = $_POST['desmobilizacaoStatus'] ?? 'Não iniciada';

    // Dados de aguardo
    $aguardoInicio = $_POST['inicioAguardoTimestamp'] ?? null;
    $aguardoFim = $_POST['fimAguardoTimestamp'] ?? null;
    $motivoAguardo = $_POST['motivoAguardoValor'] ?? null;
    $aguardoStatus = $_POST['aguardoStatus'] ?? 'Não iniciado';

    // Validação básica
    if (
        empty($inicioOperacao) || empty($nomeOpAux) || empty($tipoOperacao) ||
        empty($nomeCidade) || empty($nomePocoServ) || empty($nomeOperador) ||
        empty($volumeBbl) || empty($temperatura) || empty($pressao)
    ) {
        header("Location: index.php?error=Todos os campos obrigatórios devem ser preenchidos");
        exit;
    }

    // Alterando a estrutura da tabela para incluir os novos campos, se necessário
    try {
        // Verificar se as colunas de mobilização e desmobilização já existem
        $stmt = $pdo->query("SHOW COLUMNS FROM operacoes LIKE 'mobilizacao_inicio'");
        if ($stmt->rowCount() === 0) {
            // Adicionar colunas para mobilização e desmobilização
            $pdo->exec("ALTER TABLE operacoes 
                ADD COLUMN mobilizacao_inicio DATETIME NULL,
                ADD COLUMN mobilizacao_fim DATETIME NULL,
                ADD COLUMN desmobilizacao_inicio DATETIME NULL,
                ADD COLUMN desmobilizacao_fim DATETIME NULL,
                ADD COLUMN mobilizacao_status VARCHAR(20) DEFAULT 'Não iniciada',
                ADD COLUMN desmobilizacao_status VARCHAR(20) DEFAULT 'Não iniciada'");
        }

        // Verificar se as colunas de aguardo já existem
        $stmt = $pdo->query("SHOW COLUMNS FROM operacoes LIKE 'aguardo_inicio'");
        if ($stmt->rowCount() === 0) {
            $pdo->exec("ALTER TABLE operacoes 
                ADD COLUMN aguardo_inicio DATETIME NULL,
                ADD COLUMN aguardo_fim DATETIME NULL,
                ADD COLUMN aguardo_motivo TEXT NULL,
                ADD COLUMN aguardo_status VARCHAR(20) DEFAULT 'Não iniciado'");
        }

        // Verificar se precisamos remover colunas de quilometragem
        $stmt = $pdo->query("SHOW COLUMNS FROM operacoes LIKE 'km_final'");
        if ($stmt->rowCount() > 0) {
            // Estas colunas serão mantidas para compatibilidade retroativa
            // mas não serão mais utilizadas na interface principal
        }
    } catch (PDOException $e) {
        header("Location: index.php?error=Erro ao atualizar estrutura do banco: " . $e->getMessage());
        exit;
    }

    try {
        // Inserir operação no banco de dados
        $stmt = $pdo->prepare("INSERT INTO operacoes (
            inicio_operacao, nome_op_aux, tipo_operacao, nome_cidade, nome_poco_serv,
            nome_operador, volume_bbl, temperatura, pressao, descricao_atividades,
            fim_operacao, mobilizacao_inicio, mobilizacao_fim, desmobilizacao_inicio,
            desmobilizacao_fim, mobilizacao_status, desmobilizacao_status,
            aguardo_inicio, aguardo_fim, aguardo_motivo, aguardo_status
        ) VALUES (
            :inicio_operacao, :nome_op_aux, :tipo_operacao, :nome_cidade, :nome_poco_serv,
            :nome_operador, :volume_bbl, :temperatura, :pressao, :descricao_atividades,
            :fim_operacao, :mobilizacao_inicio, :mobilizacao_fim, :desmobilizacao_inicio,
            :desmobilizacao_fim, :mobilizacao_status, :desmobilizacao_status,
            :aguardo_inicio, :aguardo_fim, :aguardo_motivo, :aguardo_status
        )");

        $stmt->execute([
            ':inicio_operacao' => $inicioOperacao,
            ':nome_op_aux' => $nomeOpAux,
            ':tipo_operacao' => $tipoOperacao,
            ':nome_cidade' => $nomeCidade,
            ':nome_poco_serv' => $nomePocoServ,
            ':nome_operador' => $nomeOperador,
            ':volume_bbl' => $volumeBbl,
            ':temperatura' => $temperatura,
            ':pressao' => $pressao,
            ':descricao_atividades' => $descricaoAtividades,
            ':fim_operacao' => $fimOperacao,
            ':mobilizacao_inicio' => $mobilizacaoInicio,
            ':mobilizacao_fim' => $mobilizacaoFim,
            ':desmobilizacao_inicio' => $desmobilizacaoInicio,
            ':desmobilizacao_fim' => $desmobilizacaoFim,
            ':mobilizacao_status' => $mobilizacaoStatus,
            ':desmobilizacao_status' => $desmobilizacaoStatus,
            ':aguardo_inicio' => $aguardoInicio,
            ':aguardo_fim' => $aguardoFim,
            ':aguardo_motivo' => $motivoAguardo,
            ':aguardo_status' => $aguardoStatus
        ]);

        header("Location: index.php?success=Operação salva com sucesso");
        exit;
    } catch (PDOException $e) {
        header("Location: index.php?error=Erro ao salvar operação: " . $e->getMessage());
        exit;
    }
} else {
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
