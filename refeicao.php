<?php
// filepath: c:\xampp\htdocs\cop\refeicao.php
require_once 'config.php';

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

// Verificar se a tabela de refeicoes existe, caso contrário, criá-la
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'refeicoes'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE refeicoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(20) NOT NULL,
            inicio_refeicao DATETIME NOT NULL,
            fim_refeicao DATETIME,
            duracao_segundos INT,
            observacoes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
} catch (PDOException $e) {
    $error = "Erro ao verificar tabela de refeições: " . $e->getMessage();
}

// Carregar histórico de refeições
try {
    $stmt = $pdo->query("SELECT * FROM refeicoes ORDER BY inicio_refeicao DESC LIMIT 10");
    $refeicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao carregar histórico de refeições: " . $e->getMessage();
    $refeicoes = [];
}

// Mensagens de sucesso/erro
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Refeições - Controle OP</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos específicos para refeições */
        .refeicao-cards {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }

        .refeicao-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            padding: 20px;
            width: calc(50% - 10px);
            min-width: 250px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            border: 2px solid transparent;
        }

        .refeicao-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            background-color: #f1f8ff;
        }

        .refeicao-card.selected {
            border: 2px solid #007bff;
            background-color: #e7f5ff;
        }

        .refeicao-card h3 {
            margin-top: 0;
            color: #343a40;
            font-size: 18px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .refeicao-card p {
            color: #6c757d;
            margin: 5px 0;
        }

        .refeicao-card i {
            font-size: 24px;
            margin-right: 10px;
            color: #6c757d;
        }

        .refeicao-card.selected i {
            color: #007bff;
        }

        .refeicao-card .check-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            color: #28a745;
            font-size: 20px;
            display: none;
        }

        .refeicao-card.selected .check-icon {
            display: block;
        }

        @media (max-width: 768px) {
            .refeicao-card {
                width: 100%;
            }
        }

        .acoes-container {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .btn-refeicao {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-right: 10px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .btn-refeicao.iniciar {
            background-color: #28a745;
            color: white;
        }

        .btn-refeicao.iniciar:hover:not(:disabled) {
            background-color: #218838;
        }

        .btn-refeicao.finalizar {
            background-color: #17a2b8;
            color: white;
        }

        .btn-refeicao.finalizar:hover:not(:disabled) {
            background-color: #138496;
        }

        .btn-refeicao:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.65;
        }

        .refeicao-status {
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .refeicao-tempo {
            margin-top: 10px;
            font-weight: bold;
            color: #495057;
        }

        .historico-container {
            margin-top: 30px;
        }

        .historico-container h2 {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }

        .historico-lista {
            list-style: none;
            padding: 0;
        }

        .historico-item {
            padding: 15px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .historico-info {
            flex: 1;
        }

        .historico-data {
            color: #6c757d;
            font-size: 14px;
        }

        .historico-tipo {
            font-weight: bold;
            color: #343a40;
        }

        .historico-duracao {
            background-color: #e9ecef;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 14px;
        }

        /* Status ativo com animação */
        .status-ativo {
            color: #28a745;
            animation: pulsating 1.5s infinite;
        }

        @keyframes pulsating {
            0% {
                opacity: 0.7;
            }

            50% {
                opacity: 1;
            }

            100% {
                opacity: 0.7;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <h1>Controle de Refeições</h1>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

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
        </div>

        <form id="refeicaoForm" action="save_refeicao.php" method="POST">
            <h3>Selecione o tipo de refeição abaixo para registrar:</h3>

            <div class="refeicao-cards">
                <!-- Card Café da Manhã -->
                <div class="refeicao-card" data-tipo="cafe_manha">
                    <i class="fas fa-coffee"></i>
                    <h3>Café da Manhã</h3>
                    <p>06:00 - 08:00</p>
                    <span class="check-icon"><i class="fas fa-check-circle"></i></span>
                    <input type="radio" name="tipoRefeicao" value="cafe_manha" style="display: none;">
                </div>

                <!-- Card Almoço -->
                <div class="refeicao-card" data-tipo="almoco">
                    <i class="fas fa-utensils"></i>
                    <h3>Almoço</h3>
                    <p>11:00 - 14:00</p>
                    <span class="check-icon"><i class="fas fa-check-circle"></i></span>
                    <input type="radio" name="tipoRefeicao" value="almoco" style="display: none;">
                </div>

                <!-- Card Jantar -->
                <div class="refeicao-card" data-tipo="jantar">
                    <i class="fas fa-moon"></i>
                    <h3>Jantar</h3>
                    <p>18:00 - 20:00</p>
                    <span class="check-icon"><i class="fas fa-check-circle"></i></span>
                    <input type="radio" name="tipoRefeicao" value="jantar" style="display: none;">
                </div>

                <!-- Card Lanche -->
                <div class="refeicao-card" data-tipo="lanche">
                    <i class="fas fa-hamburger"></i>
                    <h3>Lanche</h3>
                    <p>Qualquer horário</p>
                    <span class="check-icon"><i class="fas fa-check-circle"></i></span>
                    <input type="radio" name="tipoRefeicao" value="lanche" style="display: none;">
                </div>
            </div>

            <div class="acoes-container">
                <div class="refeicao-botoes">
                    <button type="button" id="inicioRefeicao" class="btn-refeicao iniciar" disabled>
                        <i class="fas fa-play"></i> Iniciar Refeição
                    </button>
                    <button type="button" id="fimRefeicao" class="btn-refeicao finalizar" disabled>
                        <i class="fas fa-flag-checkered"></i> Finalizar Refeição
                    </button>
                </div>
                <div class="refeicao-status">
                    <i id="statusRefeicaoIcon" class="fas fa-clock"></i>
                    <span id="statusRefeicao">Aguardando seleção de refeição</span>
                </div>
                <div id="tempoRefeicao" class="refeicao-tempo"></div>
            </div>

            <div class="form-group">
                <label for="observacoes">Observações (opcional):</label>
                <textarea id="observacoes" name="observacoes" class="form-control" rows="3"></textarea>
            </div>

            <!-- Campos ocultos -->
            <input type="hidden" id="tipoRefeicaoHidden" name="tipoRefeicaoHidden">
            <input type="hidden" id="inicioRefeicaoTimestamp" name="inicioRefeicaoTimestamp">
            <input type="hidden" id="fimRefeicaoTimestamp" name="fimRefeicaoTimestamp">
            <input type="hidden" id="refeicaoInicio" name="refeicaoInicio">
            <input type="hidden" id="refeicaoFim" name="refeicaoFim">
            <input type="hidden" id="refeicaoStatus" name="refeicaoStatus" value="Não iniciada">

            <button type="submit" class="btn-refeicao iniciar">
                <i class="fas fa-save"></i> Salvar Refeição
            </button>
        </form>

        <!-- Histórico de Refeições -->
        <div class="historico-container">
            <h2>Histórico de Refeições</h2>
            <ul class="historico-lista">
                <?php if (count($refeicoes) > 0): ?>
                    <?php foreach ($refeicoes as $refeicao): ?>
                        <?php
                        $tipo = '';
                        $icon = '';
                        switch ($refeicao['tipo']) {
                            case 'cafe_manha':
                                $tipo = 'Café da Manhã';
                                $icon = 'coffee';
                                break;
                            case 'almoco':
                                $tipo = 'Almoço';
                                $icon = 'utensils';
                                break;
                            case 'jantar':
                                $tipo = 'Jantar';
                                $icon = 'moon';
                                break;
                            case 'lanche':
                                $tipo = 'Lanche';
                                $icon = 'hamburger';
                                break;
                        }

                        $inicio = new DateTime($refeicao['inicio_refeicao']);
                        $duracao = '';

                        if (!empty($refeicao['fim_refeicao'])) {
                            $fim = new DateTime($refeicao['fim_refeicao']);
                            $horas = floor($refeicao['duracao_segundos'] / 3600);
                            $minutos = floor(($refeicao['duracao_segundos'] % 3600) / 60);
                            $duracao = sprintf("%02d:%02d", $horas, $minutos);
                        } else {
                            $duracao = 'Em andamento';
                        }
                        ?>
                        <li class="historico-item">
                            <div class="historico-info">
                                <div class="historico-tipo"><i class="fas fa-<?php echo $icon; ?>"></i> <?php echo $tipo; ?></div>
                                <div class="historico-data"><?php echo $inicio->format('d/m/Y H:i'); ?></div>
                            </div>
                            <span class="historico-duracao"><?php echo $duracao; ?></span>
                        </li>
                    <?php endforeach; ?>
                <?php else: ?>
                    <li class="historico-item">Nenhuma refeição registrada.</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Script de refeição carregado'); // Debug

            // Selecionar elementos
            const refeicaoCards = document.querySelectorAll('.refeicao-card');
            const iniciarButton = document.getElementById('inicioRefeicao');
            const finalizarButton = document.getElementById('fimRefeicao');
            const tipoRefeicaoHidden = document.getElementById('tipoRefeicaoHidden');
            const statusRefeicao = document.getElementById('statusRefeicao');
            const statusIcon = document.getElementById('statusRefeicaoIcon');

            let selectedCard = null;
            let timerRefeicao = null;
            let segundosRefeicao = 0;

            // Adicionar evento de clique aos cards
            refeicaoCards.forEach(card => {
                card.addEventListener('click', function() {
                    console.log('Card clicado:', this.dataset.tipo); // Debug

                    // Só permite selecionar se a refeição não estiver em andamento
                    if (document.getElementById('refeicaoStatus').value !== 'Em andamento') {
                        // Remover seleção anterior
                        refeicaoCards.forEach(c => {
                            c.classList.remove('selected');
                            c.querySelector('input').checked = false;
                        });

                        // Adicionar seleção ao card clicado
                        this.classList.add('selected');
                        this.querySelector('input').checked = true;

                        // Armazenar card selecionado
                        selectedCard = this;

                        // Ativar botão de iniciar
                        iniciarButton.disabled = false;

                        // Atualizar tipo oculto
                        tipoRefeicaoHidden.value = this.dataset.tipo;
                    }
                });
            });

            // Botão iniciar refeição
            iniciarButton.addEventListener('click', function() {
                console.log('Botão iniciar clicado'); // Debug

                if (selectedCard) {
                    console.log('Iniciando refeição:', selectedCard.dataset.tipo); // Debug

                    // Registrar início
                    const now = new Date();
                    document.getElementById('inicioRefeicaoTimestamp').value = now.getTime();
                    document.getElementById('refeicaoInicio').value = now.toISOString();

                    // Atualizar interface
                    this.disabled = true;
                    finalizarButton.disabled = false;

                    // Não permitir troca de refeição durante o processo
                    refeicaoCards.forEach(card => {
                        if (card !== selectedCard) {
                            card.style.opacity = '0.5';
                            card.style.pointerEvents = 'none';
                        }
                    });

                    // Atualizar status
                    statusIcon.className = 'fas fa-utensils status-ativo';

                    let tipoTexto = '';
                    switch (selectedCard.dataset.tipo) {
                        case 'cafe_manha':
                            tipoTexto = 'Café da Manhã';
                            break;
                        case 'almoco':
                            tipoTexto = 'Almoço';
                            break;
                        case 'jantar':
                            tipoTexto = 'Jantar';
                            break;
                        case 'lanche':
                            tipoTexto = 'Lanche';
                            break;
                    }

                    statusRefeicao.textContent = `${tipoTexto} em andamento`;

                    // Iniciar cronômetro
                    timerRefeicao = setInterval(function() {
                        segundosRefeicao++;
                        document.getElementById('tempoRefeicao').textContent = 'Tempo: ' + formatarTempo(segundosRefeicao);
                    }, 1000);

                    document.getElementById('refeicaoStatus').value = 'Em andamento';
                }
            });

            // Botão finalizar refeição
            finalizarButton.addEventListener('click', function() {
                console.log('Finalizando refeição'); // Debug

                // Registrar fim
                const now = new Date();
                document.getElementById('fimRefeicaoTimestamp').value = now.getTime();
                document.getElementById('refeicaoFim').value = now.toISOString();

                // Atualizar interface
                this.disabled = true;

                // Parar cronômetro
                clearInterval(timerRefeicao);

                // Atualizar status
                statusIcon.className = 'fas fa-check-circle';
                statusIcon.style.color = '#28a745';
                statusRefeicao.textContent = 'Refeição concluída';

                document.getElementById('refeicaoStatus').value = 'Concluída';

                // Restaurar opacidade dos cards
                refeicaoCards.forEach(card => {
                    card.style.opacity = '1';
                    card.style.pointerEvents = 'auto';
                });
            });

            // Validar formulário antes de enviar
            document.getElementById('refeicaoForm').addEventListener('submit', function(e) {
                const refeicaoStatus = document.getElementById('refeicaoStatus').value;

                if (!tipoRefeicaoHidden.value) {
                    e.preventDefault();
                    alert('Por favor, selecione um tipo de refeição.');
                    return false;
                }

                if (refeicaoStatus === 'Não iniciada') {
                    e.preventDefault();
                    alert('Por favor, inicie a refeição antes de salvar.');
                    return false;
                }

                return true;
            });
        });

        // Função para formatar tempo
        function formatarTempo(segundos) {
            const horas = Math.floor(segundos / 3600);
            const minutos = Math.floor((segundos % 3600) / 60);
            const segs = segundos % 60;

            return `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }
    </script>
    <script src="js/sidebar.js"></script>
</body>

</html>