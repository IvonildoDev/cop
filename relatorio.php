<?php
// Include needed files
require_once 'config.php';
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
}

// Inicializar variáveis
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-7 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$tipoRelatorio = isset($_GET['tipo_relatorio']) ? $_GET['tipo_relatorio'] : 'todos';

// Flag para verificar se o formulário foi enviado
$formSubmitted = isset($_GET['filtrar']) || isset($_GET['export_pdf']);

// Para exportar em PDF
$exportPDF = isset($_GET['export_pdf']);

// Carregar as configurações do sistema
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        header("Location: config_inicial.php");
        exit;
    }
} catch (PDOException $e) {
    die("Erro ao carregar configurações: " . $e->getMessage());
}

// Inicializar arrays vazios para todos os tipos de dados
$operacoes = [];
$deslocamentos = [];
$aguardos = [];
$abastecimentos = [];
$refeicoes = [];

if ($formSubmitted) {
    // Filtro de data comum para todas as consultas
    $dateFilter = " WHERE DATE(created_at) BETWEEN :startDate AND :endDate ";
    $params = [
        ':startDate' => $startDate,
        ':endDate' => $endDate
    ];

    // Quando "todos" está selecionado, carregamos todos os dados independentemente de erros
    if ($tipoRelatorio == 'todos') {
        try {
            $sql = "SELECT * FROM operacoes" . $dateFilter . "ORDER BY inicio_operacao DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $operacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Apenas registrar erro, não interromper a execução
            error_log("Erro ao buscar operações: " . $e->getMessage());
            $operacoes = []; // Garantir array vazio em caso de erro
        }

        try {
            $sql = "SELECT * FROM deslocamentos" . $dateFilter . "ORDER BY inicio_deslocamento DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $deslocamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar deslocamentos: " . $e->getMessage());
            $deslocamentos = [];
        }

        try {
            $sql = "SELECT * FROM aguardos" . $dateFilter . "ORDER BY inicio_aguardo DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $aguardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar aguardos: " . $e->getMessage());
            $aguardos = [];
        }

        try {
            $sql = "SELECT * FROM abastecimentos" . $dateFilter . "ORDER BY inicio_abastecimento DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $abastecimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar abastecimentos: " . $e->getMessage());
            $abastecimentos = [];
        }

        try {
            $sql = "SELECT * FROM refeicoes" . $dateFilter . "ORDER BY inicio_refeicao DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $refeicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Erro ao buscar refeições: " . $e->getMessage());
            $refeicoes = [];
        }
    } else {
        // Para tipos específicos, carregamos apenas o tipo selecionado
        switch ($tipoRelatorio) {
            case 'operacoes':
                try {
                    $sql = "SELECT * FROM operacoes" . $dateFilter . "ORDER BY inicio_operacao DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $operacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $error = "Erro ao buscar operações: " . $e->getMessage();
                }
                break;

            case 'deslocamentos':
                try {
                    $sql = "SELECT * FROM deslocamentos" . $dateFilter . "ORDER BY inicio_deslocamento DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $deslocamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $error = "Erro ao buscar deslocamentos: " . $e->getMessage();
                }
                break;

            case 'aguardos':
                try {
                    $sql = "SELECT * FROM aguardos" . $dateFilter . "ORDER BY inicio_aguardo DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $aguardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $error = "Erro ao buscar aguardos: " . $e->getMessage();
                }
                break;

            case 'abastecimentos':
                try {
                    $sql = "SELECT * FROM abastecimentos" . $dateFilter . "ORDER BY inicio_abastecimento DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $abastecimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $error = "Erro ao buscar abastecimentos: " . $e->getMessage();
                }
                break;

            case 'refeicoes':
                try {
                    $sql = "SELECT * FROM refeicoes" . $dateFilter . "ORDER BY inicio_refeicao DESC";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute($params);
                    $refeicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                } catch (PDOException $e) {
                    $error = "Erro ao buscar refeições: " . $e->getMessage();
                }
                break;
        }
    }
}

// Exportar para PDF
if ($exportPDF) {
    if (class_exists('TCPDF')) {
        // Criar nova instância de PDF
        $pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);

        // Configurar o PDF
        $pdf->SetCreator('Sistema COP');
        $pdf->SetAuthor($config['nome_operador_principal'] . ' e ' . $config['nome_auxiliar']);
        $pdf->SetTitle('Relatório de Atividades');
        $pdf->SetSubject('Relatório de Atividades');
        $pdf->SetKeywords('Operações, Deslocamentos, Aguardos, Abastecimentos, Refeições');

        // Remover cabeçalho/rodapé padrão
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Configurar margens
        $pdf->SetMargins(15, 15, 15);
        $pdf->SetAutoPageBreak(true, 15);

        // Adicionar página
        $pdf->AddPage();

        // Título e cabeçalho
        $pdf->SetFont('helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'RELATÓRIO DE ATIVIDADES', 0, 1, 'C');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(0, 5, 'Período: ' . date('d/m/Y', strtotime($startDate)) . ' a ' . date('d/m/Y', strtotime($endDate)), 0, 1, 'C');

        // Informações da equipe
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'INFORMAÇÕES DA EQUIPE', 0, 1, 'L');
        $pdf->SetFont('helvetica', '', 10);
        $pdf->Cell(45, 6, 'Operador Principal:', 0, 0);
        $pdf->Cell(0, 6, $config['nome_operador_principal'], 0, 1);
        $pdf->Cell(45, 6, 'Auxiliar:', 0, 0);
        $pdf->Cell(0, 6, $config['nome_auxiliar'], 0, 1);
        $pdf->Cell(45, 6, 'Unidade:', 0, 0);
        $pdf->Cell(0, 6, $config['nome_unidade'], 0, 1);
        $pdf->Cell(45, 6, 'Placa do Veículo:', 0, 0);
        $pdf->Cell(0, 6, $config['placa_veiculo'], 0, 1);
        $pdf->Ln(3);

        // Sempre incluir a seção quando todos estiver selecionado, mesmo que não tenha dados
        // ----- OPERAÇÕES -----
        if ($tipoRelatorio == 'todos' || $tipoRelatorio == 'operacoes') {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, '1. OPERAÇÕES', 0, 1, 'L');

            if (count($operacoes) > 0) {
                foreach ($operacoes as $index => $op) {
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 7, 'Operação #' . ($index + 1), 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 9);

                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell(60, 6, 'Data/Hora Início:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($op['inicio_operacao'])), 1, 1);

                    if (!empty($op['fim_operacao'])) {
                        $pdf->Cell(60, 6, 'Data/Hora Fim:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($op['fim_operacao'])), 1, 1);
                    }

                    $pdf->Cell(60, 6, 'Tipo de Operação:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $op['tipo_operacao'], 1, 1);

                    $pdf->Cell(60, 6, 'Cidade:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $op['nome_cidade'], 1, 1);

                    $pdf->Cell(60, 6, 'Local de Execução:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $op['nome_poco_serv'], 1, 1);

                    $pdf->Cell(60, 6, 'Representante da Empresa:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $op['nome_operador'], 1, 1);

                    $pdf->Cell(60, 6, 'Volume (bbl):', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $op['volume_bbl'], 1, 1);

                    $pdf->Cell(60, 6, 'Temperatura (°C):', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $op['temperatura'], 1, 1);

                    $pdf->Cell(60, 6, 'Pressão (PSI/KGF):', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $op['pressao'], 1, 1);

                    if (!empty($op['descricao_atividades'])) {
                        $pdf->Cell(60, 6, 'Descrição das Atividades:', 1, 0, 'L', true);
                        $pdf->MultiCell(0, 6, $op['descricao_atividades'], 1, 'L');
                    }

                    // Informações de mobilização/desmobilização
                    if (!empty($op['mobilizacao_inicio'])) {
                        $pdf->Cell(60, 6, 'Mobilização - Início:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($op['mobilizacao_inicio'])), 1, 1);
                    }

                    if (!empty($op['mobilizacao_fim'])) {
                        $pdf->Cell(60, 6, 'Mobilização - Fim:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($op['mobilizacao_fim'])), 1, 1);
                    }

                    if (!empty($op['desmobilizacao_inicio'])) {
                        $pdf->Cell(60, 6, 'Desmobilização - Início:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($op['desmobilizacao_inicio'])), 1, 1);
                    }

                    if (!empty($op['desmobilizacao_fim'])) {
                        $pdf->Cell(60, 6, 'Desmobilização - Fim:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($op['desmobilizacao_fim'])), 1, 1);
                    }

                    $pdf->Ln(5);
                }
            } else {
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->Cell(0, 10, 'Nenhuma operação registrada no período selecionado.', 0, 1);
            }

            if ($tipoRelatorio == 'todos') {
                $pdf->AddPage();
            }
        }

        // ----- DESLOCAMENTOS -----
        if ($tipoRelatorio == 'todos' || $tipoRelatorio == 'deslocamentos') {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, '2. DESLOCAMENTOS', 0, 1, 'L');

            if (count($deslocamentos) > 0) {
                foreach ($deslocamentos as $index => $desloc) {
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 7, 'Deslocamento #' . ($index + 1), 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 9);

                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell(60, 6, 'Origem:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $desloc['origem'], 1, 1);

                    $pdf->Cell(60, 6, 'Destino:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $desloc['destino'], 1, 1);

                    $pdf->Cell(60, 6, 'Data/Hora Início:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($desloc['inicio_deslocamento'])), 1, 1);

                    if (!empty($desloc['fim_deslocamento'])) {
                        $pdf->Cell(60, 6, 'Data/Hora Fim:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($desloc['fim_deslocamento'])), 1, 1);
                    }

                    $pdf->Cell(60, 6, 'KM Inicial:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, number_format($desloc['km_inicial'], 1, ',', '.') . ' km', 1, 1);

                    if (!empty($desloc['km_final'])) {
                        $pdf->Cell(60, 6, 'KM Final:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, number_format($desloc['km_final'], 1, ',', '.') . ' km', 1, 1);

                        $pdf->Cell(60, 6, 'Distância Percorrida:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, number_format($desloc['km_final'] - $desloc['km_inicial'], 1, ',', '.') . ' km', 1, 1);
                    }

                    $pdf->Cell(60, 6, 'Status:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, empty($desloc['fim_deslocamento']) ? 'Em andamento' : 'Concluído', 1, 1);

                    if (!empty($desloc['observacoes'])) {
                        $pdf->Cell(60, 6, 'Observações:', 1, 0, 'L', true);
                        $pdf->MultiCell(0, 6, $desloc['observacoes'], 1, 'L');
                    }

                    $pdf->Ln(5);
                }
            } else {
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->Cell(0, 10, 'Nenhum deslocamento registrado no período selecionado.', 0, 1);
            }

            $pdf->Ln(5);
        }

        // ----- AGUARDOS -----
        if ($tipoRelatorio == 'todos' || $tipoRelatorio == 'aguardos') {
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, '3. AGUARDOS', 0, 1, 'L');

            if (count($aguardos) > 0) {
                foreach ($aguardos as $index => $aguardo) {
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 7, 'Aguardo #' . ($index + 1), 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 9);

                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell(60, 6, 'Data/Hora Início:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($aguardo['inicio_aguardo'])), 1, 1);

                    if (!empty($aguardo['fim_aguardo'])) {
                        $pdf->Cell(60, 6, 'Data/Hora Fim:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($aguardo['fim_aguardo'])), 1, 1);
                    }

                    $pdf->Cell(60, 6, 'Motivo:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $aguardo['motivo'], 1, 1);

                    if (!empty($aguardo['duracao_segundos'])) {
                        $horas = floor($aguardo['duracao_segundos'] / 3600);
                        $minutos = floor(($aguardo['duracao_segundos'] % 3600) / 60);

                        $pdf->Cell(60, 6, 'Duração:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, sprintf("%02d:%02d", $horas, $minutos), 1, 1);
                    }

                    $pdf->Cell(60, 6, 'Status:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, empty($aguardo['fim_aguardo']) ? 'Em andamento' : 'Concluído', 1, 1);

                    if (!empty($aguardo['observacoes'])) {
                        $pdf->Cell(60, 6, 'Observações:', 1, 0, 'L', true);
                        $pdf->MultiCell(0, 6, $aguardo['observacoes'], 1, 'L');
                    }

                    $pdf->Ln(5);
                }
            } else {
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->Cell(0, 10, 'Nenhum aguardo registrado no período selecionado.', 0, 1);
            }

            $pdf->Ln(5);
        }

        // ----- ABASTECIMENTOS -----
        if ($tipoRelatorio == 'todos' || $tipoRelatorio == 'abastecimentos') {
            // Verificar se precisa adicionar uma nova página
            if ($pdf->getY() > 180 && $tipoRelatorio == 'todos') {
                $pdf->AddPage();
            }

            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, '4. ABASTECIMENTOS', 0, 1, 'L');

            if (count($abastecimentos) > 0) {
                foreach ($abastecimentos as $index => $abast) {
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 7, 'Abastecimento #' . ($index + 1), 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 9);

                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell(60, 6, 'Tipo:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, ($abast['tipo'] == 'agua') ? 'Água' : 'Combustível', 1, 1);

                    $pdf->Cell(60, 6, 'Data/Hora Início:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($abast['inicio_abastecimento'])), 1, 1);

                    if (!empty($abast['fim_abastecimento'])) {
                        $pdf->Cell(60, 6, 'Data/Hora Fim:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($abast['fim_abastecimento'])), 1, 1);
                    }

                    if (!empty($abast['quantidade'])) {
                        $pdf->Cell(60, 6, 'Quantidade:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, number_format($abast['quantidade'], 1, ',', '.') . ' L', 1, 1);
                    }

                    if (!empty($abast['km_atual'])) {
                        $pdf->Cell(60, 6, 'Km Atual:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, number_format($abast['km_atual'], 1, ',', '.') . ' km', 1, 1);
                    }

                    if (!empty($abast['local'])) {
                        $pdf->Cell(60, 6, 'Local:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, $abast['local'], 1, 1);
                    }

                    $pdf->Cell(60, 6, 'Status:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, empty($abast['fim_abastecimento']) ? 'Em andamento' : 'Concluído', 1, 1);

                    $pdf->Ln(5);
                }
            } else {
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->Cell(0, 10, 'Nenhum abastecimento registrado no período selecionado.', 0, 1);
            }

            $pdf->Ln(5);
        }

        // ----- REFEIÇÕES -----
        if ($tipoRelatorio == 'todos' || $tipoRelatorio == 'refeicoes') {
            // Verificar se precisa adicionar uma nova página
            if ($pdf->getY() > 180 && $tipoRelatorio == 'todos') {
                $pdf->AddPage();
            }

            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, '5. REFEIÇÕES', 0, 1, 'L');

            if (count($refeicoes) > 0) {
                foreach ($refeicoes as $index => $refeicao) {
                    $pdf->SetFont('helvetica', 'B', 11);
                    $pdf->Cell(0, 7, 'Refeição #' . ($index + 1), 0, 1, 'L');
                    $pdf->SetFont('helvetica', '', 9);

                    // Formatação do tipo de refeição
                    $tipoFormatado = '';
                    switch ($refeicao['tipo']) {
                        case 'cafe':
                            $tipoFormatado = 'Café da Manhã';
                            break;
                        case 'almoco':
                            $tipoFormatado = 'Almoço';
                            break;
                        case 'jantar':
                            $tipoFormatado = 'Jantar';
                            break;
                        case 'lanche':
                            $tipoFormatado = 'Lanche';
                            break;
                        default:
                            $tipoFormatado = ucfirst($refeicao['tipo']);
                    }

                    $pdf->SetFillColor(240, 240, 240);
                    $pdf->Cell(60, 6, 'Tipo de Refeição:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, $tipoFormatado, 1, 1);

                    $pdf->Cell(60, 6, 'Data/Hora Início:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($refeicao['inicio_refeicao'])), 1, 1);

                    if (!empty($refeicao['fim_refeicao'])) {
                        $pdf->Cell(60, 6, 'Data/Hora Fim:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, date('d/m/Y H:i', strtotime($refeicao['fim_refeicao'])), 1, 1);
                    }

                    if (!empty($refeicao['duracao_segundos'])) {
                        $horas = floor($refeicao['duracao_segundos'] / 3600);
                        $minutos = floor(($refeicao['duracao_segundos'] % 3600) / 60);

                        $pdf->Cell(60, 6, 'Duração:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, sprintf("%02d:%02d", $horas, $minutos), 1, 1);
                    }

                    if (!empty($refeicao['local'])) {
                        $pdf->Cell(60, 6, 'Local:', 1, 0, 'L', true);
                        $pdf->Cell(0, 6, $refeicao['local'], 1, 1);
                    }

                    $pdf->Cell(60, 6, 'Status:', 1, 0, 'L', true);
                    $pdf->Cell(0, 6, empty($refeicao['fim_refeicao']) ? 'Em andamento' : 'Concluída', 1, 1);

                    $pdf->Ln(5);
                }
            } else {
                $pdf->SetFont('helvetica', 'I', 10);
                $pdf->Cell(0, 10, 'Nenhuma refeição registrada no período selecionado.', 0, 1);
            }
        }

        // Resumo e estatísticas (só mostrar quando "todos" estiver selecionado)
        if ($tipoRelatorio == 'todos') {
            $pdf->AddPage();
            $pdf->SetFont('helvetica', 'B', 14);
            $pdf->Cell(0, 10, 'RESUMO DE ATIVIDADES', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);

            // Estatísticas de operações
            $pdf->SetFillColor(240, 240, 240);
            $pdf->Cell(0, 7, 'Operações', 1, 1, 'L', true);
            $pdf->Cell(100, 6, 'Total de operações:', 1, 0, 'L');
            $pdf->Cell(0, 6, count($operacoes), 1, 1, 'L');

            // Estatísticas de deslocamentos
            $pdf->Cell(0, 7, 'Deslocamentos', 1, 1, 'L', true);
            $pdf->Cell(100, 6, 'Total de deslocamentos:', 1, 0, 'L');
            $pdf->Cell(0, 6, count($deslocamentos), 1, 1, 'L');

            if (count($deslocamentos) > 0) {
                $kmTotal = 0;
                foreach ($deslocamentos as $desloc) {
                    if (!empty($desloc['km_final']) && !empty($desloc['km_inicial'])) {
                        $kmTotal += ($desloc['km_final'] - $desloc['km_inicial']);
                    }
                }
                $pdf->Cell(100, 6, 'Quilometragem total:', 1, 0, 'L');
                $pdf->Cell(0, 6, number_format($kmTotal, 1, ',', '.') . ' km', 1, 1, 'L');
            }

            // Estatísticas de aguardos
            $pdf->Cell(0, 7, 'Aguardos', 1, 1, 'L', true);
            $pdf->Cell(100, 6, 'Total de aguardos:', 1, 0, 'L');
            $pdf->Cell(0, 6, count($aguardos), 1, 1, 'L');

            if (count($aguardos) > 0) {
                $tempoTotalAguardos = 0;
                foreach ($aguardos as $aguardo) {
                    if (!empty($aguardo['duracao_segundos'])) {
                        $tempoTotalAguardos += $aguardo['duracao_segundos'];
                    }
                }
                $horasAguardos = floor($tempoTotalAguardos / 3600);
                $minutosAguardos = floor(($tempoTotalAguardos % 3600) / 60);

                $pdf->Cell(100, 6, 'Tempo total em aguardo:', 1, 0, 'L');
                $pdf->Cell(0, 6, sprintf("%02d:%02d", $horasAguardos, $minutosAguardos), 1, 1, 'L');
            }

            // Estatísticas de abastecimentos
            $pdf->Cell(0, 7, 'Abastecimentos', 1, 1, 'L', true);
            $pdf->Cell(100, 6, 'Total de abastecimentos:', 1, 0, 'L');
            $pdf->Cell(0, 6, count($abastecimentos), 1, 1, 'L');

            if (count($abastecimentos) > 0) {
                $totalCombustivel = 0;
                $totalAgua = 0;

                foreach ($abastecimentos as $abast) {
                    if (!empty($abast['quantidade'])) {
                        if ($abast['tipo'] == 'combustivel') {
                            $totalCombustivel += $abast['quantidade'];
                        } else if ($abast['tipo'] == 'agua') {
                            $totalAgua += $abast['quantidade'];
                        }
                    }
                }

                $pdf->Cell(100, 6, 'Total de combustível:', 1, 0, 'L');
                $pdf->Cell(0, 6, number_format($totalCombustivel, 1, ',', '.') . ' L', 1, 1, 'L');

                $pdf->Cell(100, 6, 'Total de água:', 1, 0, 'L');
                $pdf->Cell(0, 6, number_format($totalAgua, 1, ',', '.') . ' L', 1, 1, 'L');
            }

            // Estatísticas de refeições
            $pdf->Cell(0, 7, 'Refeições', 1, 1, 'L', true);
            $pdf->Cell(100, 6, 'Total de refeições:', 1, 0, 'L');
            $pdf->Cell(0, 6, count($refeicoes), 1, 1, 'L');

            if (count($refeicoes) > 0) {
                $cafes = 0;
                $almocos = 0;
                $jantares = 0;
                $lanches = 0;

                foreach ($refeicoes as $refeicao) {
                    switch ($refeicao['tipo']) {
                        case 'cafe':
                            $cafes++;
                            break;
                        case 'almoco':
                            $almocos++;
                            break;
                        case 'jantar':
                            $jantares++;
                            break;
                        case 'lanche':
                            $lanches++;
                            break;
                    }
                }

                $pdf->Cell(100, 6, 'Cafés da manhã:', 1, 0, 'L');
                $pdf->Cell(0, 6, $cafes, 1, 1, 'L');

                $pdf->Cell(100, 6, 'Almoços:', 1, 0, 'L');
                $pdf->Cell(0, 6, $almocos, 1, 1, 'L');

                $pdf->Cell(100, 6, 'Jantares:', 1, 0, 'L');
                $pdf->Cell(0, 6, $jantares, 1, 1, 'L');

                $pdf->Cell(100, 6, 'Lanches:', 1, 0, 'L');
                $pdf->Cell(0, 6, $lanches, 1, 1, 'L');
            }
        }

        // Rodapé com assinaturas
        $pdf->Ln(20);
        $pdf->Cell(0, 10, '___________________________________          ___________________________________', 0, 1, 'C');
        $pdf->Cell(0, 5, $config['nome_operador_principal'] . '                                ' . $config['nome_auxiliar'], 0, 1, 'C');
        $pdf->Cell(0, 5, 'Operador Principal                                                    Auxiliar', 0, 1, 'C');

        // Rodapé com data e hora de geração
        $pdf->SetY(-15);
        $pdf->SetFont('helvetica', 'I', 8);
        $pdf->Cell(0, 10, 'Relatório gerado em ' . date('d/m/Y H:i:s'), 0, 0, 'C');

        // Saída do PDF
        $fileName = 'Relatorio_' . date('Y-m-d') . '.pdf';
        $pdf->Output($fileName, 'D'); // 'D' para download
        exit;
    } else {
        die("TCPDF library not found. Make sure it's installed via Composer.");
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatórios</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <nav class="navbar">
        <div class="hamburger">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
        <ul class="nav-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="deslocamento.php"><i class="fas fa-route"></i> Deslocamento</a></li>
            <li><a href="aguardo.php"><i class="fas fa-pause-circle"></i> Aguardos</a></li>
            <li><a href="abastecimento.php"><i class="fas fa-gas-pump"></i> Abastecimento</a></li>
            <li><a href="refeicao.php"><i class="fas fa-utensils"></i> Refeições</a></li>
            <!-- <li><a href="relatorio.php" class="active"><i class="fas fa-chart-bar"></i> Relatórios</a></li> -->
            <li><a href="config_inicial.php"><i class="fas fa-cog"></i> Configurações</a></li>
        </ul>
    </nav>

    <div class="container">
        <div class="title-with-icon">
            <i class="fas fa-chart-bar"></i>
            <h1>Relatórios</h1>
        </div>

        <form method="GET" action="">
            <div class="form-row">
                <div class="form-group">
                    <label for="start_date">Data Inicial:</label>
                    <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>">
                </div>

                <div class="form-group">
                    <label for="end_date">Data Final:</label>
                    <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>">
                </div>

                <div class="form-group">
                    <label for="tipo_relatorio">Tipo de Relatório:</label>
                    <select id="tipo_relatorio" name="tipo_relatorio">
                        <option value="todos" <?php echo ($tipoRelatorio == 'todos') ? 'selected' : ''; ?>>Todos</option>
                        <option value="operacoes" <?php echo ($tipoRelatorio == 'operacoes') ? 'selected' : ''; ?>>Operações</option>
                        <option value="deslocamentos" <?php echo ($tipoRelatorio == 'deslocamentos') ? 'selected' : ''; ?>>Deslocamentos</option>
                        <option value="aguardos" <?php echo ($tipoRelatorio == 'aguardos') ? 'selected' : ''; ?>>Aguardos</option>
                        <option value="abastecimentos" <?php echo ($tipoRelatorio == 'abastecimentos') ? 'selected' : ''; ?>>Abastecimentos</option>
                        <option value="refeicoes" <?php echo ($tipoRelatorio == 'refeicoes') ? 'selected' : ''; ?>>Refeições</option>
                    </select>
                </div>
            </div>

            <div class="form-buttons">
                <button type="submit" name="filtrar" class="btn primary">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <button type="submit" name="export_pdf" class="btn secondary">
                    <i class="fas fa-file-pdf"></i> Exportar PDF
                </button>
            </div>
        </form>

        <!-- Display results here based on the filter -->
        <?php if ($formSubmitted): ?>
            <?php if ($tipoRelatorio == 'todos' || $tipoRelatorio == 'operacoes'): ?>
                <h2 class="titulo-secao"><i class="fas fa-clipboard-list"></i> Operações</h2>
                <?php if (count($operacoes) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Início</th>
                                    <th>Cidade</th>
                                    <th>Poço/Serviço</th>
                                    <th>Operador</th>
                                    <th>Volume (bbl)</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($operacoes as $op): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($op['inicio_operacao'])); ?></td>
                                        <td><?php echo htmlspecialchars($op['nome_cidade']); ?></td>
                                        <td><?php echo htmlspecialchars($op['nome_poco_serv']); ?></td>
                                        <td><?php echo htmlspecialchars($op['nome_operador']); ?></td>
                                        <td><?php echo $op['volume_bbl']; ?></td>
                                        <td>
                                            <?php if (empty($op['fim_operacao'])): ?>
                                                <span class="status status-ativo">Em andamento</span>
                                            <?php else: ?>
                                                <span class="status status-finalizado">Concluída</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="alert info">Nenhuma operação encontrada no período selecionado.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($tipoRelatorio == 'todos' || $tipoRelatorio == 'deslocamentos'): ?>
                <h2 class="titulo-secao"><i class="fas fa-route"></i> Deslocamentos</h2>
                <?php if (count($deslocamentos) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Origem</th>
                                    <th>Destino</th>
                                    <th>Início</th>
                                    <th>KM Inicial</th>
                                    <th>KM Final</th>
                                    <th>Distância</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($deslocamentos as $desloc): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($desloc['origem']); ?></td>
                                        <td><?php echo htmlspecialchars($desloc['destino']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($desloc['inicio_deslocamento'])); ?></td>
                                        <td><?php echo number_format($desloc['km_inicial'], 1, ',', '.'); ?> km</td>
                                        <td>
                                            <?php if (!empty($desloc['km_final'])): ?>
                                                <?php echo number_format($desloc['km_final'], 1, ',', '.'); ?> km
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($desloc['km_final'])): ?>
                                                <?php echo number_format($desloc['km_final'] - $desloc['km_inicial'], 1, ',', '.'); ?> km
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (empty($desloc['fim_deslocamento'])): ?>
                                                <span class="status status-ativo">Em andamento</span>
                                            <?php else: ?>
                                                <span class="status status-finalizado">Concluído</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="alert info">Nenhum deslocamento encontrado no período selecionado.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($tipoRelatorio == 'todos' || $tipoRelatorio == 'aguardos'): ?>
                <h2 class="titulo-secao"><i class="fas fa-pause-circle"></i> Aguardos</h2>
                <?php if (count($aguardos) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Início</th>
                                    <th>Motivo</th>
                                    <th>Fim</th>
                                    <th>Duração</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aguardos as $aguardo): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y H:i', strtotime($aguardo['inicio_aguardo'])); ?></td>
                                        <td><?php echo htmlspecialchars($aguardo['motivo']); ?></td>
                                        <td>
                                            <?php if (!empty($aguardo['fim_aguardo'])): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($aguardo['fim_aguardo'])); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($aguardo['duracao_segundos'])): ?>
                                                <?php
                                                $duracao = $aguardo['duracao_segundos'];
                                                $horas = floor($duracao / 3600);
                                                $minutos = floor(($duracao % 3600) / 60);
                                                echo sprintf("%02d:%02d", $horas, $minutos);
                                                ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (empty($aguardo['fim_aguardo'])): ?>
                                                <span class="status status-ativo">Em andamento</span>
                                            <?php else: ?>
                                                <span class="status status-finalizado">Concluído</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="alert info">Nenhum aguardo encontrado no período selecionado.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($tipoRelatorio == 'todos' || $tipoRelatorio == 'abastecimentos'): ?>
                <h2 class="titulo-secao"><i class="fas fa-gas-pump"></i> Abastecimentos</h2>
                <?php if (count($abastecimentos) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Início</th>
                                    <th>Fim</th>
                                    <th>Quantidade</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($abastecimentos as $abast): ?>
                                    <tr>
                                        <td>
                                            <?php if ($abast['tipo'] == 'agua'): ?>
                                                <i class="fas fa-tint"></i> Água
                                            <?php else: ?>
                                                <i class="fas fa-gas-pump"></i> Combustível
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($abast['inicio_abastecimento'])); ?></td>
                                        <td>
                                            <?php if (!empty($abast['fim_abastecimento'])): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($abast['fim_abastecimento'])); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($abast['quantidade'])): ?>
                                                <?php echo number_format($abast['quantidade'], 1, ',', '.'); ?> L
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (empty($abast['fim_abastecimento'])): ?>
                                                <span class="status status-ativo">Em andamento</span>
                                            <?php else: ?>
                                                <span class="status status-finalizado">Concluído</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="alert info">Nenhum abastecimento encontrado no período selecionado.</p>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($tipoRelatorio == 'todos' || $tipoRelatorio == 'refeicoes'): ?>
                <h2 class="titulo-secao"><i class="fas fa-utensils"></i> Refeições</h2>
                <?php if (count($refeicoes) > 0): ?>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Tipo</th>
                                    <th>Início</th>
                                    <th>Fim</th>
                                    <th>Duração</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($refeicoes as $refeicao): ?>
                                    <tr>
                                        <td>
                                            <?php
                                            $tipo_formatado = '';
                                            $icone = '';
                                            switch ($refeicao['tipo']) {
                                                case 'cafe':
                                                    $tipo_formatado = 'Café da Manhã';
                                                    $icone = 'fa-coffee';
                                                    break;
                                                case 'almoco':
                                                    $tipo_formatado = 'Almoço';
                                                    $icone = 'fa-hamburger';
                                                    break;
                                                case 'jantar':
                                                    $tipo_formatado = 'Jantar';
                                                    $icone = 'fa-utensils';
                                                    break;
                                                case 'lanche':
                                                    $tipo_formatado = 'Lanche';
                                                    $icone = 'fa-cookie-bite';
                                                    break;
                                                default:
                                                    $tipo_formatado = ucfirst($refeicao['tipo']);
                                                    $icone = 'fa-utensils';
                                            }
                                            ?>
                                            <i class="fas <?php echo $icone; ?>"></i> <?php echo $tipo_formatado; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($refeicao['inicio_refeicao'])); ?></td>
                                        <td>
                                            <?php if (!empty($refeicao['fim_refeicao'])): ?>
                                                <?php echo date('d/m/Y H:i', strtotime($refeicao['fim_refeicao'])); ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($refeicao['duracao_segundos'])): ?>
                                                <?php
                                                $duracao = $refeicao['duracao_segundos'];
                                                $horas = floor($duracao / 3600);
                                                $minutos = floor(($duracao % 3600) / 60);
                                                echo sprintf("%02d:%02d", $horas, $minutos);
                                                ?>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (empty($refeicao['fim_refeicao'])): ?>
                                                <span class="status status-ativo">Em andamento</span>
                                            <?php else: ?>
                                                <span class="status status-finalizado">Concluída</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="alert info">Nenhuma refeição encontrada no período selecionado.</p>
                <?php endif; ?>
            <?php endif; ?>

        <?php else: ?>
            <div class="alert info">
                <p><i class="fas fa-info-circle"></i> Selecione um período e clique em "Filtrar" para visualizar os relatórios.</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.querySelector(".hamburger");
            const navMenu = document.querySelector(".nav-menu");

            hamburger.addEventListener("click", function() {
                navMenu.classList.toggle("active");
            });
        });
    </script>
</body>

</html>