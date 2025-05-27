<?php
require_once 'vendor/autoload.php';
require_once 'config.php';

// Carregar configurações
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        die("Configurações não encontradas. Por favor, configure o sistema primeiro.");
    }
} catch (PDOException $e) {
    die("Erro ao carregar configurações: " . $e->getMessage());
}

// Estender a classe TCPDF para personalizar o cabeçalho
class MYPDF extends TCPDF
{
    protected $customHeaderData;  // Changed name to avoid conflict

    // Override TCPDF's setHeaderData with the original signature
    public function setHeaderData($ln = '', $lw = 0, $ht = '', $hs = '', $tc = array(0, 0, 0), $lc = array(0, 0, 0))
    {
        // Call parent method to maintain original functionality
        parent::setHeaderData($ln, $lw, $ht, $hs, $tc, $lc);
    }

    // Add a new method with a different name for your custom data
    public function setCustomHeaderData($data)
    {
        $this->customHeaderData = $data;
    }

    public function Header()
    {
        // Use customHeaderData instead of headerData
        if ($this->customHeaderData) {
            $this->SetFont('helvetica', 'B', 12);
            $this->Cell(0, 10, 'RELATÓRIO DE OPERAÇÕES', 0, 1, 'C');

            $this->SetFont('helvetica', '', 10);
            $this->Cell(95, 5, 'Operador: ' . $this->customHeaderData['nome_operador_principal'], 0, 0);
            $this->Cell(95, 5, 'Unidade: ' . $this->customHeaderData['nome_unidade'], 0, 1);

            $this->Cell(95, 5, 'Auxiliar: ' . $this->customHeaderData['nome_auxiliar'], 0, 0);
            $this->Cell(95, 5, 'Placa: ' . $this->customHeaderData['placa_veiculo'], 0, 1);

            $this->SetLineWidth(0.1);
            $this->Line(10, 32, 200, 32);
            $this->SetY(35);
        }
    }

    public function Footer()
    {
        $this->SetY(-15);
        $this->SetFont('helvetica', 'I', 8);
        $this->Cell(0, 10, 'Página ' . $this->getAliasNumPage() . '/' . $this->getAliasNbPages(), 0, false, 'C');
        $this->Cell(0, 10, 'Gerado em: ' . date('d/m/Y H:i:s'), 0, false, 'R');
    }
}

// Criar instância do PDF
$pdf = new MYPDF('P', 'mm', 'A4', true, 'UTF-8', false);

// Definir dados do cabeçalho
$pdf->setCustomHeaderData($config);

// Configurar o PDF
$pdf->SetCreator('Controle OP');
$pdf->SetAuthor($config['nome_operador_principal']);
$pdf->SetTitle('Histórico de Operações');
$pdf->SetSubject('Registro de Operações');

// Configurar margens
$pdf->SetMargins(10, 40, 10);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

$pdf->SetAutoPageBreak(true, 15);
$pdf->setImageScale(1.25);

// Adicionar uma página
$pdf->AddPage();

// Conteúdo
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Histórico de Operações', 0, 1, 'C');
$pdf->Ln(5);

// Get operations from the database
try {
    $stmt = $pdo->query("SELECT * FROM operacoes ORDER BY created_at DESC");
    $operacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set font for content
    $pdf->SetFont('helvetica', '', 10);

    foreach ($operacoes as $op) {
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 10, 'Operação: ' . $op['nome_op_aux'], 0, 1);
        $pdf->SetFont('helvetica', '', 10);

        $pdf->Cell(0, 6, 'Início: ' . $op['inicio_operacao'], 0, 1);
        $pdf->Cell(0, 6, 'KM Inicial: ' . $op['km_inicial'], 0, 1);
        $pdf->Cell(0, 6, 'Tipo de Operação: ' . $op['tipo_operacao'], 0, 1);
        $pdf->Cell(0, 6, 'Cidade: ' . $op['nome_cidade'], 0, 1);
        $pdf->Cell(0, 6, 'Poço/Serviço: ' . $op['nome_poco_serv'], 0, 1);
        $pdf->Cell(0, 6, 'Operador: ' . $op['nome_operador'], 0, 1);
        $pdf->Cell(0, 6, 'Volume (bbl): ' . $op['volume_bbl'], 0, 1);
        $pdf->Cell(0, 6, 'Temperatura (°C): ' . $op['temperatura'], 0, 1);
        $pdf->Cell(0, 6, 'Pressão (PSI/KGF): ' . $op['pressao'], 0, 1);

        // Handle potentially long description text
        $pdf->MultiCell(0, 6, 'Descrição: ' . $op['descricao_atividades'], 0, 'L');

        // Show end time and final km if available
        if (!empty($op['fim_operacao'])) {
            $pdf->Cell(0, 6, 'Fim: ' . $op['fim_operacao'], 0, 1);
        }
        if (!empty($op['km_final'])) {
            $pdf->Cell(0, 6, 'KM Final: ' . $op['km_final'], 0, 1);
        }

        // Informações de Aguardo
        if (!empty($op['aguardo_inicio'])) {
            $pdf->Cell(0, 6, 'Período de Aguardo:', 0, 1);
            $pdf->Cell(10, 6, '', 0, 0);
            $pdf->Cell(0, 6, 'Início: ' . $op['aguardo_inicio'], 0, 1);
            $pdf->Cell(10, 6, '', 0, 0);
            $pdf->Cell(0, 6, 'Motivo: ' . $op['aguardo_motivo'], 0, 1);

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

        $pdf->Ln(5);
        $pdf->Cell(0, 0, '', 'T', 1); // Draw a line
        $pdf->Ln(5);
    }
} catch (PDOException $e) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Erro ao carregar dados: ' . $e->getMessage(), 0, 1);
}

// Adicionar seção de deslocamentos
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Registro de Deslocamentos', 0, 1, 'C');
$pdf->Ln(5);

// Get deslocamentos from the database
try {
    $stmt = $pdo->query("SELECT * FROM deslocamentos ORDER BY inicio_deslocamento DESC");
    $deslocamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set font for content
    $pdf->SetFont('helvetica', '', 10);

    if (count($deslocamentos) > 0) {
        foreach ($deslocamentos as $deslocamento) {
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 10, 'Rota: ' . $deslocamento['origem'] . ' → ' . $deslocamento['destino'], 0, 1);
            $pdf->SetFont('helvetica', '', 10);

            // Format início
            $data_inicio = new DateTime($deslocamento['inicio_deslocamento']);
            $pdf->Cell(0, 6, 'Início: ' . $data_inicio->format('d/m/Y H:i:s'), 0, 1);
            $pdf->Cell(0, 6, 'KM Inicial: ' . number_format($deslocamento['km_inicial'], 1, ',', '.') . ' km', 0, 1);

            // Show end time and final km if available
            if (!empty($deslocamento['fim_deslocamento'])) {
                $data_fim = new DateTime($deslocamento['fim_deslocamento']);
                $pdf->Cell(0, 6, 'Fim: ' . $data_fim->format('d/m/Y H:i:s'), 0, 1);
                $pdf->Cell(0, 6, 'KM Final: ' . number_format($deslocamento['km_final'], 1, ',', '.') . ' km', 0, 1);

                // Calculate and display distance
                $distancia = $deslocamento['km_final'] - $deslocamento['km_inicial'];
                $pdf->Cell(0, 6, 'Distância Percorrida: ' . number_format($distancia, 1, ',', '.') . ' km', 0, 1);

                // Calculate and display time spent
                $interval = $data_inicio->diff($data_fim);
                $tempo_formatado = '';
                if ($interval->d > 0) $tempo_formatado .= $interval->d . ' dia(s), ';
                if ($interval->h > 0) $tempo_formatado .= $interval->h . ' hora(s), ';
                $tempo_formatado .= $interval->i . ' minuto(s)';
                $pdf->Cell(0, 6, 'Tempo de Deslocamento: ' . $tempo_formatado, 0, 1);
            } else {
                $pdf->Cell(0, 6, 'Status: Em andamento', 0, 1);
            }

            // Add notes if available
            if (!empty($deslocamento['observacoes'])) {
                $pdf->Ln(2);
                $pdf->MultiCell(0, 6, 'Observações: ' . $deslocamento['observacoes'], 0, 'L');
            }

            $pdf->Ln(5);
            $pdf->Cell(0, 0, '', 'T', 1); // Draw a line
            $pdf->Ln(5);
        }
    } else {
        $pdf->Cell(0, 10, 'Nenhum deslocamento registrado.', 0, 1);
    }
} catch (PDOException $e) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Erro ao carregar dados de deslocamento: ' . $e->getMessage(), 0, 1);
}

// Adicionar seção de abastecimentos
$pdf->AddPage();
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'Registro de Abastecimentos', 0, 1, 'C');
$pdf->Ln(5);

// Get abastecimentos from the database
try {
    $stmt = $pdo->query("SELECT * FROM abastecimentos ORDER BY inicio_abastecimento DESC");
    $abastecimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Set font for content
    $pdf->SetFont('helvetica', '', 10);

    if (count($abastecimentos) > 0) {
        foreach ($abastecimentos as $abastecimento) {
            $pdf->SetFont('helvetica', 'B', 12);

            if ($abastecimento['tipo'] == 'agua') {
                $pdf->Cell(0, 10, 'Abastecimento de Água', 0, 1);
            } else {
                $pdf->Cell(0, 10, 'Abastecimento de Combustível', 0, 1);
            }

            $pdf->SetFont('helvetica', '', 10);

            // Format início
            $data_inicio = new DateTime($abastecimento['inicio_abastecimento']);
            $pdf->Cell(0, 6, 'Início: ' . $data_inicio->format('d/m/Y H:i:s'), 0, 1);

            // Show end time and quantity if available
            if (!empty($abastecimento['fim_abastecimento'])) {
                $data_fim = new DateTime($abastecimento['fim_abastecimento']);
                $pdf->Cell(0, 6, 'Fim: ' . $data_fim->format('d/m/Y H:i:s'), 0, 1);
                $pdf->Cell(0, 6, 'Quantidade: ' . number_format($abastecimento['quantidade'], 1, ',', '.') . ' litros', 0, 1);

                // Display time spent
                $duracao = $abastecimento['duracao_segundos'];
                $horas = floor($duracao / 3600);
                $minutos = floor(($duracao % 3600) / 60);
                $segundos = $duracao % 60;
                $tempo_formatado = sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);
                $pdf->Cell(0, 6, 'Tempo de Abastecimento: ' . $tempo_formatado, 0, 1);
            } else {
                $pdf->Cell(0, 6, 'Status: Em andamento', 0, 1);
            }

            // Add notes if available
            if (!empty($abastecimento['observacoes'])) {
                $pdf->Ln(2);
                $pdf->MultiCell(0, 6, 'Observações: ' . $abastecimento['observacoes'], 0, 'L');
            }

            $pdf->Ln(5);
            $pdf->Cell(0, 0, '', 'T', 1); // Draw a line
            $pdf->Ln(5);
        }
    } else {
        $pdf->Cell(0, 10, 'Nenhum abastecimento registrado.', 0, 1);
    }
} catch (PDOException $e) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Erro ao carregar dados de abastecimento: ' . $e->getMessage(), 0, 1);
}

// Output the PDF
$pdf->Output('historico_operacoes.pdf', 'I'); // 'I' displays inline in browser
