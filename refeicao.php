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

// Iniciar uma nova refeição
if (isset($_POST['iniciar_refeicao'])) {
    $tipo = $_POST['tipo'] ?? '';

    if (empty($tipo)) {
        $error = "Por favor, selecione o tipo de refeição.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO refeicoes 
                (tipo, inicio_refeicao) 
                VALUES (:tipo, NOW())");

            $stmt->execute([
                ':tipo' => $tipo
            ]);

            $success = "Refeição iniciada com sucesso!";
            $refeicao_id = $pdo->lastInsertId();

            // Redirecionar para evitar reenvio do formulário
            header("Location: refeicao.php?active_id=" . $refeicao_id . "&success=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = "Erro ao iniciar refeição: " . $e->getMessage();
        }
    }
}

// Finalizar uma refeição ativa
if (isset($_POST['finalizar_refeicao'])) {
    $refeicao_id = $_POST['refeicao_id'] ?? 0;

    if (empty($refeicao_id)) {
        $error = "ID de refeição inválido.";
    } else {
        try {
            // Obter timestamp de início para calcular duração
            $stmt = $pdo->prepare("SELECT inicio_refeicao FROM refeicoes WHERE id = :id");
            $stmt->execute([':id' => $refeicao_id]);
            $inicio = $stmt->fetchColumn();

            if ($inicio) {
                $inicio_dt = new DateTime($inicio);
                $fim_dt = new DateTime();
                $duracao = $fim_dt->getTimestamp() - $inicio_dt->getTimestamp();

                $stmt = $pdo->prepare("UPDATE refeicoes SET 
                    fim_refeicao = NOW(),
                    duracao_segundos = :duracao
                    WHERE id = :id AND fim_refeicao IS NULL");

                $stmt->execute([
                    ':duracao' => $duracao,
                    ':id' => $refeicao_id
                ]);

                if ($stmt->rowCount() > 0) {
                    $success = "Refeição finalizada com sucesso!";
                    header("Location: refeicao.php?success=" . urlencode($success));
                    exit;
                } else {
                    $error = "Refeição não encontrada ou já finalizada.";
                }
            } else {
                $error = "Refeição não encontrada.";
            }
        } catch (PDOException $e) {
            $error = "Erro ao finalizar refeição: " . $e->getMessage();
        }
    }
}

// Verificar se existe uma refeição ativa
$refeicao_ativa = null;
$active_id = $_GET['active_id'] ?? null;

if ($active_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM refeicoes WHERE id = :id AND fim_refeicao IS NULL");
        $stmt->execute([':id' => $active_id]);
        $refeicao_ativa = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Erro ao carregar refeição ativa: " . $e->getMessage();
    }
}

// Se não houver refeição ativa pelo GET, verificar se existe alguma em andamento
if (!$refeicao_ativa) {
    try {
        $stmt = $pdo->query("SELECT * FROM refeicoes WHERE fim_refeicao IS NULL ORDER BY inicio_refeicao DESC LIMIT 1");
        $refeicao_ativa = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($refeicao_ativa) {
            $active_id = $refeicao_ativa['id'];
        }
    } catch (PDOException $e) {
        $error = "Erro ao verificar refeições ativas: " . $e->getMessage();
    }
}

// Carregar histórico de refeições
try {
    $stmt = $pdo->query("SELECT * FROM refeicoes ORDER BY inicio_refeicao DESC LIMIT 20");
    $refeicoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao carregar histórico: " . $e->getMessage();
    $refeicoes = [];
}

// Mensagens de sucesso/erro
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error = isset($error) ? $error : (isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '');
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Refeições</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .refeicao-card {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 5px solid #17a2b8;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .refeicao-ativa {
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
            border-left-color: #28a745;
        }

        .refeicao-finalizada {
            opacity: 0.9;
            border-left-color: #6c757d;
        }

        .refeicao-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }

        .refeicao-tipo {
            font-weight: bold;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .refeicao-status {
            background-color: #f8f9fa;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }

        .status-ativo {
            background-color: #d4edda;
            color: #155724;
        }

        .status-finalizado {
            background-color: #e2e3e5;
            color: #383d41;
        }

        .refeicao-info {
            display: flex;
            flex-wrap: wrap;
        }

        .refeicao-info div {
            flex: 1;
            min-width: 150px;
            margin-bottom: 10px;
        }

        .titulo-secao {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 30px 0 15px 0;
        }

        .titulo-secao i {
            font-size: 20px;
            color: #17a2b8;
        }

        .tipo-botoes {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 20px 0;
        }

        .tipo-botao {
            flex: 1;
            min-width: 120px;
            padding: 20px 15px;
            border-radius: 8px;
            background-color: #ffffff;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.2s ease;
            border: 2px solid #dee2e6;
        }

        .tipo-botao:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .tipo-botao.selected {
            border-color: #28a745;
            background-color: #f0fff4;
        }

        .tipo-botao i {
            font-size: 28px;
            margin-bottom: 12px;
        }

        .tipo-botao.cafe i {
            color: #6f4e37;
        }

        .tipo-botao.almoco i {
            color: #fd7e14;
        }

        .tipo-botao.jantar i {
            color: #17a2b8;
        }

        .tipo-botao.lanche i {
            color: #9c27b0;
        }

        .cronometro {
            font-size: 24px;
            font-weight: bold;
            margin: 15px 0;
            color: #28a745;
            text-align: center;
        }

        .pulsating {
            animation: pulsate 1.5s infinite;
        }

        @keyframes pulsate {
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

        .btn-iniciar {
            width: 100%;
            padding: 15px !important;
            font-size: 18px !important;
            margin-top: 20px;
            background-color: #28a745;
        }

        .btn-iniciar:hover {
            background-color: #218838;
        }

        .btn-iniciar:disabled {
            background-color: #6c757d;
            cursor: not-allowed;
            opacity: 0.7;
        }

        .btn-finalizar {
            width: 100%;
            padding: 15px !important;
            font-size: 18px !important;
            margin-top: 20px;
            background-color: #dc3545;
        }

        .btn-finalizar:hover {
            background-color: #c82333;
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
            <li><a href="index.php" class="active"><i class="fas fa-home"></i> Operação</a></li>
            <li><a href="mobilizacao.php"><i class="fas fa-truck-loading"></i> Mobilização</a></li>
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
            <p class="alert success"><?php echo $success; ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="alert error"><?php echo $error; ?></p>
        <?php endif; ?>

        <div class="title-with-icon">
            <i class="fas fa-utensils"></i>
            <h1>Controle de Refeições</h1>
        </div>

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

        <?php if ($refeicao_ativa): ?>
            <!-- Refeição em andamento -->
            <div class="refeicao-card refeicao-ativa">
                <div class="refeicao-header">
                    <div class="refeicao-tipo">
                        <i class="fas <?php
                                        switch ($refeicao_ativa['tipo']) {
                                            case 'cafe':
                                                echo 'fa-coffee';
                                                break;
                                            case 'almoco':
                                                echo 'fa-hamburger';
                                                break;
                                            case 'jantar':
                                                echo 'fa-utensils';
                                                break;
                                            default:
                                                echo 'fa-cookie-bite';
                                                break;
                                        }
                                        ?>"></i>
                        <?php
                        switch ($refeicao_ativa['tipo']) {
                            case 'cafe':
                                echo 'Café da Manhã';
                                break;
                            case 'almoco':
                                echo 'Almoço';
                                break;
                            case 'jantar':
                                echo 'Jantar';
                                break;
                            case 'lanche':
                                echo 'Lanche';
                                break;
                            default:
                                echo ucfirst($refeicao_ativa['tipo']);
                                break;
                        }
                        ?>
                    </div>
                    <div class="refeicao-status status-ativo">EM ANDAMENTO</div>
                </div>
                <div class="refeicao-info">
                    <div>
                        <strong>Início:</strong>
                        <?php echo (new DateTime($refeicao_ativa['inicio_refeicao']))->format('d/m/Y H:i:s'); ?>
                    </div>
                </div>

                <div class="cronometro pulsating" id="cronometroRefeicao">
                    <i class="fas fa-hourglass-half"></i>
                    <span id="tempoDecorrido">Calculando...</span>
                </div>

                <form action="refeicao.php" method="POST">
                    <input type="hidden" name="refeicao_id" value="<?php echo $refeicao_ativa['id']; ?>">
                    <button type="submit" name="finalizar_refeicao" class="btn-finalizar">
                        <i class="fas fa-check-circle"></i> Finalizar Refeição
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Formulário para iniciar nova refeição -->
            <div class="card">
                <h2 class="titulo-secao"><i class="fas fa-plus-circle"></i> Nova Refeição</h2>
                <p>Selecione o tipo de refeição abaixo para registrar:</p>

                <form action="refeicao.php" method="POST">
                    <div class="tipo-botoes">
                        <div class="tipo-botao cafe" id="btn-cafe">
                            <i class="fas fa-coffee"></i>
                            <strong>Café da Manhã</strong>
                        </div>
                        <div class="tipo-botao almoco" id="btn-almoco">
                            <i class="fas fa-hamburger"></i>
                            <strong>Almoço</strong>
                        </div>
                        <div class="tipo-botao jantar" id="btn-jantar">
                            <i class="fas fa-utensils"></i>
                            <strong>Jantar</strong>
                        </div>
                        <div class="tipo-botao lanche" id="btn-lanche">
                            <i class="fas fa-cookie-bite"></i>
                            <strong>Lanche</strong>
                        </div>
                    </div>
                    <input type="hidden" id="tipo" name="tipo" required>
                    <button type="submit" name="iniciar_refeicao" id="btn-iniciar" class="btn-iniciar" disabled>
                        <i class="fas fa-play"></i> Iniciar Refeição
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Histórico de refeições -->
        <h2 class="titulo-secao"><i class="fas fa-history"></i> Histórico de Refeições</h2>

        <?php if (count($refeicoes) > 0): ?>
            <?php foreach ($refeicoes as $refeicao): ?>
                <div class="refeicao-card <?php echo $refeicao['fim_refeicao'] ? 'refeicao-finalizada' : 'refeicao-ativa'; ?>">
                    <div class="refeicao-header">
                        <div class="refeicao-tipo">
                            <i class="fas <?php
                                            switch ($refeicao['tipo']) {
                                                case 'cafe':
                                                    echo 'fa-coffee';
                                                    break;
                                                case 'almoco':
                                                    echo 'fa-hamburger';
                                                    break;
                                                case 'jantar':
                                                    echo 'fa-utensils';
                                                    break;
                                                default:
                                                    echo 'fa-cookie-bite';
                                                    break;
                                            }
                                            ?>"></i>
                            <?php
                            switch ($refeicao['tipo']) {
                                case 'cafe':
                                    echo 'Café da Manhã';
                                    break;
                                case 'almoco':
                                    echo 'Almoço';
                                    break;
                                case 'jantar':
                                    echo 'Jantar';
                                    break;
                                case 'lanche':
                                    echo 'Lanche';
                                    break;
                                default:
                                    echo ucfirst($refeicao['tipo']);
                                    break;
                            }
                            ?>
                        </div>
                        <div class="refeicao-status <?php echo $refeicao['fim_refeicao'] ? 'status-finalizado' : 'status-ativo'; ?>">
                            <?php echo $refeicao['fim_refeicao'] ? 'FINALIZADO' : 'EM ANDAMENTO'; ?>
                        </div>
                    </div>

                    <div class="refeicao-info">
                        <div>
                            <strong>Início:</strong>
                            <?php echo (new DateTime($refeicao['inicio_refeicao']))->format('d/m/Y H:i:s'); ?>
                        </div>

                        <?php if (!empty($refeicao['fim_refeicao'])): ?>
                            <div>
                                <strong>Fim:</strong>
                                <?php echo (new DateTime($refeicao['fim_refeicao']))->format('d/m/Y H:i:s'); ?>
                            </div>
                            <div>
                                <strong>Duração:</strong>
                                <?php
                                $duracao = $refeicao['duracao_segundos'];
                                $horas = floor($duracao / 3600);
                                $minutos = floor(($duracao % 3600) / 60);
                                $segundos = $duracao % 60;
                                echo sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);
                                ?>
                            </div>
                        <?php else: ?>
                            <div>
                                <a href="refeicao.php?active_id=<?php echo $refeicao['id']; ?>" class="btn btn-small btn-success">
                                    <i class="fas fa-check-circle"></i> Finalizar
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert info">
                <i class="fas fa-info-circle"></i> Nenhuma refeição registrada ainda.
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Adicionar o JavaScript para o menu hamburger
        document.addEventListener('DOMContentLoaded', function() {
            const hamburger = document.querySelector(".hamburger");
            const navMenu = document.querySelector(".nav-menu");

            hamburger.addEventListener("click", function() {
                navMenu.classList.toggle("active");
            });

            <?php if (!$refeicao_ativa): ?>
                // Seleção do tipo de refeição
                const btnCafe = document.getElementById('btn-cafe');
                const btnAlmoco = document.getElementById('btn-almoco');
                const btnJantar = document.getElementById('btn-jantar');
                const btnLanche = document.getElementById('btn-lanche');
                const tipoInput = document.getElementById('tipo');
                const btnIniciar = document.getElementById('btn-iniciar');

                // Função para remover a seleção de todos os botões
                function removerSelecao() {
                    btnCafe.classList.remove('selected');
                    btnAlmoco.classList.remove('selected');
                    btnJantar.classList.remove('selected');
                    btnLanche.classList.remove('selected');
                }

                btnCafe.addEventListener('click', function() {
                    removerSelecao();
                    btnCafe.classList.add('selected');
                    tipoInput.value = 'cafe';
                    btnIniciar.disabled = false;
                });

                btnAlmoco.addEventListener('click', function() {
                    removerSelecao();
                    btnAlmoco.classList.add('selected');
                    tipoInput.value = 'almoco';
                    btnIniciar.disabled = false;
                });

                btnJantar.addEventListener('click', function() {
                    removerSelecao();
                    btnJantar.classList.add('selected');
                    tipoInput.value = 'jantar';
                    btnIniciar.disabled = false;
                });

                btnLanche.addEventListener('click', function() {
                    removerSelecao();
                    btnLanche.classList.add('selected');
                    tipoInput.value = 'lanche';
                    btnIniciar.disabled = false;
                });
            <?php endif; ?>

            <?php if ($refeicao_ativa): ?>
                // Cronômetro para refeição ativa
                const inicioRefeicao = new Date("<?php echo $refeicao_ativa['inicio_refeicao']; ?>").getTime();

                function atualizarCronometro() {
                    const agora = new Date().getTime();
                    const diferenca = Math.floor((agora - inicioRefeicao) / 1000);

                    const horas = Math.floor(diferenca / 3600);
                    const minutos = Math.floor((diferenca % 3600) / 60);
                    const segundos = diferenca % 60;

                    document.getElementById('tempoDecorrido').textContent =
                        `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundos).padStart(2, '0')}`;
                }

                // Atualizar imediatamente e depois a cada segundo
                atualizarCronometro();
                setInterval(atualizarCronometro, 1000);
            <?php endif; ?>
        });
    </script>
</body>

</html>