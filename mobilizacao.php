<?php
require_once 'config.php';

// Carregar as configurações do sistema
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    // Se não há configurações, redirecionar para a página de configuração inicial
    if (!$config || empty($config['nome_operador_principal'])) {
        header("Location: config_inicial.php");
        exit;
    }
} catch (PDOException $e) {
    // Se a tabela não existir, significa que o banco precisa ser inicializado
    header("Location: setup.php");
    exit;
}

// Fetch mobilization history
try {
    $stmt = $pdo->query("SELECT * FROM mobilizacoes ORDER BY created_at DESC LIMIT 10");
    $mobilizacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao carregar histórico de mobilizações: " . $e->getMessage();
    $mobilizacoes = [];
}

// Display success or error messages
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mobilização do Equipamento - Controle OP</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .header-info {
            background-color: #f8f9fa;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }

        .header-info .info-grupo {
            flex: 1;
            min-width: 200px;
            padding: 0 15px;
            margin-bottom: 10px;
        }

        .header-info h3 {
            margin-top: 0;
            color: #343a40;
            font-size: 16px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }

        .header-info p {
            margin: 5px 0;
            font-size: 14px;
        }

        .header-info .edit-link {
            margin-top: 10px;
            display: inline-block;
            font-size: 12px;
            text-decoration: none;
            color: #6c757d;
        }

        .header-info .edit-link:hover {
            color: #007bff;
        }

        /* Estilos para mobilização */
        .mobilizacao-container {
            margin-bottom: 25px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .mobilizacao-grupo {
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        .mobilizacao-grupo:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .mobilizacao-titulo {
            font-weight: bold;
            margin-bottom: 10px;
            color: #495057;
        }

        .mobilizacao-botoes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 10px;
        }

        .mobilizacao-status {
            font-size: 14px;
            color: #6c757d;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .mobilizacao-status-texto {
            font-style: italic;
        }

        .mobilizacao-tempo {
            margin-top: 5px;
            font-size: 14px;
            color: #495057;
            font-weight: bold;
        }

        .mobilizacao-status i {
            font-size: 16px;
        }

        .status-aguardando {
            color: #6c757d;
        }

        .status-ativo {
            color: #28a745;
            animation: pulsate 1.5s infinite;
        }

        .status-concluido {
            color: #007bff;
        }

        .btn-mobilizacao {
            padding: 8px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.3s ease;
        }

        .btn-mobilizacao.iniciar {
            background-color: #28a745;
            color: white;
        }

        .btn-mobilizacao.iniciar:hover {
            background-color: #218838;
        }

        .btn-mobilizacao.finalizar {
            background-color: #17a2b8;
            color: white;
        }

        .btn-mobilizacao.finalizar:hover {
            background-color: #138496;
        }

        .btn-mobilizacao:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.65;
        }

        @keyframes pulsate {
            0% {
                opacity: 0.8;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0.8;
            }
        }

        /* Estilos para o histórico */
        .historico-container {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .historico-container h2 {
            color: #343a40;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-top: 0;
        }

        .historico-container ul {
            list-style-type: none;
            padding: 0;
        }

        .historico-container li {
            padding: 10px;
            border-bottom: 1px solid #e9ecef;
        }

        .historico-container li:last-child {
            border-bottom: none;
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="hamburger">
            <div class="bar"></div>
            <div class="bar"></div>
            <div class="bar"></div>
        </div>
        <ul class="nav-menu">
            <li><a href="index.php"><i class="fas fa-home"></i> Operação</a></li>
            <li><a href="mobilizacao.php" class="active"><i class="fas fa-truck-loading"></i> Mobilização</a></li>
            <li><a href="desmobilizacao.php"><i class="fas fa-truck"></i> Desmobilização</a></li>
            <li><a href="deslocamento.php"><i class="fas fa-route"></i> Deslocamento</a></li>
            <li><a href="aguardo.php"><i class="fas fa-pause-circle"></i> Aguardos</a></li>
            <li><a href="abastecimento.php"><i class="fas fa-gas-pump"></i> Abastecimento</a></li>
            <li><a href="refeicao.php"><i class="fas fa-utensils"></i> Refeições</a></li>
            <li><a href="relatorio.php"><i class="fas fa-chart-bar"></i> Relatórios</a></li>
            <li><a href="config_inicial.php"><i class="fas fa-cog"></i> Configurações</a></li>
        </ul>
    </nav>

    <div class="container">
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <h1>Mobilização do Equipamento</h1>

        <!-- Informações do cabeçalho -->
        <div class="header-info">
            <div class="info-grupo">
                <h3>Operador e Auxiliar</h3>
                <p><strong>Operador:</strong> <?php echo htmlspecialchars($config['nome_operador_principal']); ?></p>
                <p><strong>Auxiliar:</strong> <?php echo htmlspecialchars($config['nome_auxiliar']); ?></p>
            </div>
            <div class="info-grupo">
                <h3>Unidade e Veículo</h3>
                <p><strong>Unidade:</strong> <?php echo htmlspecialchars($config['nome_unidade']); ?></p>
                <p><strong>Placa:</strong> <?php echo htmlspecialchars($config['placa_veiculo']); ?></p>
            </div>
            <div class="info-grupo text-right">
                <a href="config_inicial.php" class="edit-link"><i class="fas fa-edit"></i> Editar informações</a>
            </div>
        </div>

        <form id="mobilizacaoForm" action="save_mobilizacao.php" method="POST">
            <!-- Mobilização -->
            <div class="mobilizacao-container">
                <div class="mobilizacao-grupo">
                    <div class="mobilizacao-titulo">Mobilização do Equipamento</div>
                    <div class="mobilizacao-botoes">
                        <button type="button" id="inicioMobilizacao" class="btn-mobilizacao iniciar">
                            <i class="fas fa-play"></i> Início Mobilização
                        </button>
                        <button type="button" id="fimMobilizacao" class="btn-mobilizacao finalizar" disabled>
                            <i class="fas fa-flag-checkered"></i> Finalizar Mobilização
                        </button>
                    </div>
                    <div class="mobilizacao-status">
                        <i id="statusMobilizacaoIcon" class="fas fa-clock status-aguardando"></i>
                        <span id="statusMobilizacao" class="mobilizacao-status-texto">Aguardando início da mobilização</span>
                    </div>
                    <div id="tempoMobilizacao" class="mobilizacao-tempo"></div>
                </div>
            </div>

            <!-- Local da Mobilização -->
            <label for="localMobilizacao">Local da Mobilização:</label>
            <input type="text" id="localMobilizacao" name="localMobilizacao" required>

            <!-- Observações (sem o required) -->
            <!-- <label for="observacoes">Observações (até 500 caracteres):</label>
            <textarea id="observacoes" name="observacoes" maxlength="500" rows="4"></textarea> -->

            <!-- Campos ocultos para registrar os tempos -->
            <input type="hidden" id="inicioMobilizacaoTimestamp" name="inicioMobilizacaoTimestamp">
            <input type="hidden" id="fimMobilizacaoTimestamp" name="fimMobilizacaoTimestamp">
            <input type="hidden" id="mobilizacaoInicio" name="mobilizacaoInicio">
            <input type="hidden" id="mobilizacaoFim" name="mobilizacaoFim">
            <input type="hidden" id="mobilizacaoStatus" name="mobilizacaoStatus" value="Não iniciada">

            <button type="submit" class="btn-mobilizacao finalizar">
                <i class="fas fa-save"></i> Salvar Mobilização
            </button>
        </form>

        <div class="historico-container">
            <h2>Histórico de Mobilizações</h2>
            <ul id="historicoMobilizacoes">
                <?php foreach ($mobilizacoes as $mob): ?>
                    <li>
                        <strong>Início:</strong> <?php echo date('d/m/Y H:i', strtotime($mob['inicio_mobilizacao'])); ?><br>
                        <strong>Local:</strong> <?php echo htmlspecialchars($mob['local_mobilizacao']); ?><br>
                        <strong>Fim:</strong> <?php echo !empty($mob['fim_mobilizacao']) ? date('d/m/Y H:i', strtotime($mob['fim_mobilizacao'])) : 'Em andamento'; ?><br>
                        <?php if (!empty($mob['duracao_segundos'])): ?>
                            <strong>Duração:</strong>
                            <?php
                            $horas = floor($mob['duracao_segundos'] / 3600);
                            $minutos = floor(($mob['duracao_segundos'] % 3600) / 60);
                            echo sprintf("%02d:%02d", $horas, $minutos);
                            ?><br>
                        <?php endif; ?>
                        <strong>Status:</strong> <?php echo htmlspecialchars($mob['status']); ?>
                    </li>
                <?php endforeach; ?>

                <?php if (empty($mobilizacoes)): ?>
                    <li>Nenhuma mobilização registrada.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <script>
        // Função para formatar o tempo decorrido
        function formatarTempo(segundos) {
            const horas = Math.floor(segundos / 3600);
            const minutos = Math.floor((segundos % 3600) / 60);
            const segs = segundos % 60;

            return `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }

        // Variáveis para controle do tempo
        let timerMobilizacao = null;
        let segundosMobilizacao = 0;

        // Controle de mobilização
        document.getElementById('inicioMobilizacao').addEventListener('click', function() {
            // Registrar o início da mobilização
            const now = new Date();
            document.getElementById('inicioMobilizacaoTimestamp').value = now.getTime();
            document.getElementById('mobilizacaoInicio').value = now.toISOString();

            // Atualizar interface
            this.disabled = true;
            document.getElementById('fimMobilizacao').disabled = false;

            const statusIcon = document.getElementById('statusMobilizacaoIcon');
            const statusText = document.getElementById('statusMobilizacao');
            statusIcon.className = 'fas fa-cog fa-spin status-ativo';
            statusText.textContent = 'Equipe em montagem dos equipamentos'; // Frase padrão correta

            // Iniciar o cronômetro
            timerMobilizacao = setInterval(function() {
                segundosMobilizacao++;
                document.getElementById('tempoMobilizacao').textContent = 'Tempo: ' + formatarTempo(segundosMobilizacao);
            }, 1000);

            document.getElementById('mobilizacaoStatus').value = 'Em andamento';
        });

        document.getElementById('fimMobilizacao').addEventListener('click', function() {
            // Registrar o fim da mobilização
            const now = new Date();
            document.getElementById('fimMobilizacaoTimestamp').value = now.getTime();
            document.getElementById('mobilizacaoFim').value = now.toISOString();

            // Atualizar interface
            this.disabled = true;

            // Parar o cronômetro
            clearInterval(timerMobilizacao);

            const statusIcon = document.getElementById('statusMobilizacaoIcon');
            const statusText = document.getElementById('statusMobilizacao');
            statusIcon.className = 'fas fa-check-circle status-concluido';
            statusText.textContent = 'Montagem do equipamento concluída';

            document.getElementById('mobilizacaoStatus').value = 'Concluída';
        });

        // Validação do formulário
        document.getElementById('mobilizacaoForm').addEventListener('submit', function(e) {
            const localMobilizacao = document.getElementById('localMobilizacao').value.trim();
            const mobilizacaoStatus = document.getElementById('mobilizacaoStatus').value;

            if (localMobilizacao === '') {
                e.preventDefault();
                alert('Por favor, informe o local da mobilização.');
                return false;
            }

            if (mobilizacaoStatus === 'Não iniciada') {
                e.preventDefault();
                alert('Por favor, inicie a mobilização antes de salvar.');
                return false;
            }

            return true;
        });

        // Menu hamburger
        document.querySelector('.hamburger').addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('active');
        });
    </script>
</body>

</html>
<?php
// Para mobilizações
foreach ($mobilizacoes as $index => $mob) {
    $pdf->Cell(40, 6, 'Início:', 1, 0, 'L');
    $inicio_data = new DateTime($mob['inicio_mobilizacao']);
    $pdf->Cell(0, 6, $inicio_data->format('d/m/Y H:i'), 1, 1, 'L');

    $pdf->Cell(40, 6, 'Fim:', 1, 0, 'L');
    if (!empty($mob['fim_mobilizacao'])) {
        $fim_data = new DateTime($mob['fim_mobilizacao']);
        $pdf->Cell(0, 6, $fim_data->format('d/m/Y H:i'), 1, 1, 'L');
    } else {
        $pdf->Cell(0, 6, 'Em andamento', 1, 1, 'L');
    }
}

// Para desmobilizações
foreach ($desmobilizacoes as $index => $desmob) {
    $pdf->Cell(40, 6, 'Início:', 1, 0, 'L');
    $inicio_data = new DateTime($desmob['inicio_desmobilizacao']);
    $pdf->Cell(0, 6, $inicio_data->format('d/m/Y H:i'), 1, 1, 'L');

    $pdf->Cell(40, 6, 'Fim:', 1, 0, 'L');
    if (!empty($desmob['fim_desmobilizacao'])) {
        $fim_data = new DateTime($desmob['fim_desmobilizacao']);
        $pdf->Cell(0, 6, $fim_data->format('d/m/Y H:i'), 1, 1, 'L');
    } else {
        $pdf->Cell(0, 6, 'Em andamento', 1, 1, 'L');
    }
}
?>