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

// Fetch desmobilization history
try {
    $stmt = $pdo->query("SELECT * FROM desmobilizacoes ORDER BY created_at DESC LIMIT 10");
    $desmobilizacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao carregar histórico de desmobilizações: " . $e->getMessage();
    $desmobilizacoes = [];
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
    <title>Desmobilização - Controle OP</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
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

        /* Estilos para desmobilização */
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
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php if ($success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <h1>Desmobilização do Equipamento</h1>

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

        <form id="desmobilizacaoForm" action="save_desmobilizacao.php" method="POST">
            <!-- Desmobilização -->
            <div class="mobilizacao-container">
                <div class="mobilizacao-grupo">
                    <div class="mobilizacao-titulo">Desmobilização do Equipamento</div>
                    <div class="mobilizacao-botoes">
                        <button type="button" id="inicioDesmobilizacao" class="btn-mobilizacao iniciar">
                            <i class="fas fa-play"></i> Início Desmobilização
                        </button>
                        <button type="button" id="fimDesmobilizacao" class="btn-mobilizacao finalizar" disabled>
                            <i class="fas fa-flag-checkered"></i> Finalizar Desmobilização
                        </button>
                    </div>
                    <div class="mobilizacao-status">
                        <i id="statusDesmobilizacaoIcon" class="fas fa-clock status-aguardando"></i>
                        <span id="statusDesmobilizacao" class="mobilizacao-status-texto">Aguardando início da desmobilização</span>
                    </div>
                    <div id="tempoDesmobilizacao" class="mobilizacao-tempo"></div>
                </div>
            </div>

            <!-- Local da Desmobilização -->
            <label for="localDesmobilizacao">Local da Desmobilização:</label>
            <input type="text" id="localDesmobilizacao" name="localDesmobilizacao" required>

            <!-- Observações (sem o required) -->
            <!-- <label for="observacoes">Observações (até 500 caracteres):</label>
            <textarea id="observacoes" name="observacoes" maxlength="500" rows="4"></textarea> -->

            <!-- Campos ocultos para registrar os tempos -->
            <input type="hidden" id="inicioDesmobilizacaoTimestamp" name="inicioDesmobilizacaoTimestamp">
            <input type="hidden" id="fimDesmobilizacaoTimestamp" name="fimDesmobilizacaoTimestamp">
            <input type="hidden" id="desmobilizacaoInicio" name="desmobilizacaoInicio">
            <input type="hidden" id="desmobilizacaoFim" name="desmobilizacaoFim">
            <input type="hidden" id="desmobilizacaoStatus" name="desmobilizacaoStatus" value="Não iniciada">

            <button type="submit" class="btn-mobilizacao finalizar">
                <i class="fas fa-save"></i> Salvar Desmobilização
            </button>
        </form>

        <div class="historico-container">
            <h2>Histórico de Desmobilizações</h2>
            <ul id="historicoDesmobilizacoes">
                <?php foreach ($desmobilizacoes as $desmob): ?>
                    <li>
                        <strong>Início:</strong> <?php echo date('d/m/Y H:i', strtotime($desmob['inicio_desmobilizacao'])); ?><br>
                        <strong>Local:</strong> <?php echo htmlspecialchars($desmob['local_desmobilizacao']); ?><br>
                        <strong>Fim:</strong> <?php echo !empty($desmob['fim_desmobilizacao']) ? date('d/m/Y H:i', strtotime($desmob['fim_desmobilizacao'])) : 'Em andamento'; ?><br>
                        <?php if (!empty($desmob['duracao_segundos'])): ?>
                            <strong>Duração:</strong>
                            <?php
                            $horas = floor($desmob['duracao_segundos'] / 3600);
                            $minutos = floor(($desmob['duracao_segundos'] % 3600) / 60);
                            echo sprintf("%02d:%02d", $horas, $minutos);
                            ?><br>
                        <?php endif; ?>
                        <strong>Status:</strong> <?php echo htmlspecialchars($desmob['status']); ?>
                    </li>
                <?php endforeach; ?>

                <?php if (empty($desmobilizacoes)): ?>
                    <li>Nenhuma desmobilização registrada.</li>
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
        let timerDesmobilizacao = null;
        let segundosDesmobilizacao = 0;

        // Controle de desmobilização
        document.getElementById('inicioDesmobilizacao').addEventListener('click', function() {
            // Registrar o início da desmobilização
            const now = new Date();
            document.getElementById('inicioDesmobilizacaoTimestamp').value = now.getTime();
            document.getElementById('desmobilizacaoInicio').value = now.toISOString();

            // Atualizar interface
            this.disabled = true;
            document.getElementById('fimDesmobilizacao').disabled = false;

            const statusIcon = document.getElementById('statusDesmobilizacaoIcon');
            const statusText = document.getElementById('statusDesmobilizacao');
            statusIcon.className = 'fas fa-cog fa-spin status-ativo';
            statusText.textContent = 'Equipe em desmontagem dos equipamentos'; // Frase padrão correta

            // Iniciar o cronômetro
            timerDesmobilizacao = setInterval(function() {
                segundosDesmobilizacao++;
                document.getElementById('tempoDesmobilizacao').textContent = 'Tempo: ' + formatarTempo(segundosDesmobilizacao);
            }, 1000);

            document.getElementById('desmobilizacaoStatus').value = 'Em andamento';
        });

        document.getElementById('fimDesmobilizacao').addEventListener('click', function() {
            // Registrar o fim da desmobilização
            const now = new Date();
            document.getElementById('fimDesmobilizacaoTimestamp').value = now.getTime();
            document.getElementById('desmobilizacaoFim').value = now.toISOString();

            // Atualizar interface
            this.disabled = true;

            // Parar o cronômetro
            clearInterval(timerDesmobilizacao);

            const statusIcon = document.getElementById('statusDesmobilizacaoIcon');
            const statusText = document.getElementById('statusDesmobilizacao');
            statusIcon.className = 'fas fa-check-circle status-concluido';
            statusText.textContent = 'Desmontagem do equipamento concluída';

            document.getElementById('desmobilizacaoStatus').value = 'Concluída';
        });

        // Validação do formulário
        document.getElementById('desmobilizacaoForm').addEventListener('submit', function(e) {
            const localDesmobilizacao = document.getElementById('localDesmobilizacao').value.trim();
            const desmobilizacaoStatus = document.getElementById('desmobilizacaoStatus').value;

            if (localDesmobilizacao === '') {
                e.preventDefault();
                alert('Por favor, informe o local da desmobilização.');
                return false;
            }

            if (desmobilizacaoStatus === 'Não iniciada') {
                e.preventDefault();
                alert('Por favor, inicie a desmobilização antes de salvar.');
                return false;
            }

            return true;
        });

        // Menu hamburger
        document.querySelector('.hamburger').addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('active');
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar for mobile
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebar-toggle');
            const sidebarToggleIcon = document.getElementById('sidebar-toggle-icon');
            const sidebarClose = document.querySelector('.sidebar-close');
            const overlay = document.getElementById('overlay');
            const body = document.body;

            function openSidebar() {
                sidebar.classList.add('active');
                overlay.classList.add('active');
                body.classList.add('sidebar-open');
                sidebarToggleIcon.classList.remove('fa-bars');
                sidebarToggleIcon.classList.add('fa-times');
            }

            function closeSidebar() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                body.classList.remove('sidebar-open');
                sidebarToggleIcon.classList.remove('fa-times');
                sidebarToggleIcon.classList.add('fa-bars');
            }

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    if (sidebar.classList.contains('active')) {
                        closeSidebar();
                    } else {
                        openSidebar();
                    }
                });
            }

            if (sidebarClose) {
                sidebarClose.addEventListener('click', closeSidebar);
            }

            if (overlay) {
                overlay.addEventListener('click', closeSidebar);
            }

            // Fechar sidebar ao clicar em um link no modo mobile
            const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
            if (window.innerWidth <= 768) {
                sidebarLinks.forEach(link => {
                    link.addEventListener('click', closeSidebar);
                });
            }
        });
    </script>
</body>

</html>