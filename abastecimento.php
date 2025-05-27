<?php
// filepath: c:\xampp\htdocs\cop\abastecimento.php
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

// Verificar se a tabela de abastecimentos existe, caso contrário, criá-la
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'abastecimentos'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE abastecimentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(20) NOT NULL,
            inicio_abastecimento DATETIME NOT NULL,
            fim_abastecimento DATETIME,
            quantidade FLOAT,
            observacoes TEXT,
            duracao_segundos INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
} catch (PDOException $e) {
    $error = "Erro ao verificar tabela de abastecimentos: " . $e->getMessage();
}

// Iniciar um novo abastecimento
if (isset($_POST['iniciar_abastecimento'])) {
    $tipo = $_POST['tipo'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';

    if (empty($tipo)) {
        $error = "Por favor, selecione o tipo de abastecimento.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO abastecimentos 
                (tipo, inicio_abastecimento, observacoes) 
                VALUES (:tipo, NOW(), :observacoes)");

            $stmt->execute([
                ':tipo' => $tipo,
                ':observacoes' => $observacoes
            ]);

            $success = "Abastecimento iniciado com sucesso!";
            $abastecimento_id = $pdo->lastInsertId();

            // Redirecionar para evitar reenvio do formulário
            header("Location: abastecimento.php?active_id=" . $abastecimento_id . "&success=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = "Erro ao iniciar abastecimento: " . $e->getMessage();
        }
    }
}

// Finalizar um abastecimento ativo
if (isset($_POST['finalizar_abastecimento'])) {
    $abastecimento_id = $_POST['abastecimento_id'] ?? 0;
    $quantidade = $_POST['quantidade'] ?? 0;

    if (empty($abastecimento_id)) {
        $error = "ID de abastecimento inválido.";
    } else if (empty($quantidade) || !is_numeric($quantidade)) {
        $error = "Por favor, informe a quantidade de litros abastecidos.";
    } else {
        try {
            // Obter timestamp de início para calcular duração
            $stmt = $pdo->prepare("SELECT inicio_abastecimento FROM abastecimentos WHERE id = :id");
            $stmt->execute([':id' => $abastecimento_id]);
            $inicio = $stmt->fetchColumn();

            if ($inicio) {
                $inicio_dt = new DateTime($inicio);
                $fim_dt = new DateTime();
                $duracao = $fim_dt->getTimestamp() - $inicio_dt->getTimestamp();

                $stmt = $pdo->prepare("UPDATE abastecimentos SET 
                    fim_abastecimento = NOW(),
                    quantidade = :quantidade,
                    duracao_segundos = :duracao
                    WHERE id = :id AND fim_abastecimento IS NULL");

                $stmt->execute([
                    ':quantidade' => $quantidade,
                    ':duracao' => $duracao,
                    ':id' => $abastecimento_id
                ]);

                if ($stmt->rowCount() > 0) {
                    $success = "Abastecimento finalizado com sucesso!";
                    header("Location: abastecimento.php?success=" . urlencode($success));
                    exit;
                } else {
                    $error = "Abastecimento não encontrado ou já finalizado.";
                }
            } else {
                $error = "Abastecimento não encontrado.";
            }
        } catch (PDOException $e) {
            $error = "Erro ao finalizar abastecimento: " . $e->getMessage();
        }
    }
}

// Verificar se existe um abastecimento ativo
$abastecimento_ativo = null;
$active_id = $_GET['active_id'] ?? null;

if ($active_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM abastecimentos WHERE id = :id AND fim_abastecimento IS NULL");
        $stmt->execute([':id' => $active_id]);
        $abastecimento_ativo = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Erro ao carregar abastecimento ativo: " . $e->getMessage();
    }
}

// Carregar histórico de abastecimentos
try {
    $stmt = $pdo->query("SELECT * FROM abastecimentos ORDER BY inicio_abastecimento DESC LIMIT 20");
    $abastecimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao carregar histórico: " . $e->getMessage();
    $abastecimentos = [];
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
    <title>Controle de Abastecimento</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .abastecimento-card {
            background-color: #f8f9fa;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 5px solid #17a2b8;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .abastecimento-agua {
            border-left-color: #007bff;
            background-color: #f0f8ff;
        }

        .abastecimento-combustivel {
            border-left-color: #fd7e14;
            background-color: #fff9f0;
        }

        .abastecimento-ativo {
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
        }

        .abastecimento-finalizado {
            opacity: 0.9;
        }

        .abastecimento-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }

        .abastecimento-tipo {
            font-weight: bold;
            font-size: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .abastecimento-tipo i.fa-gas-pump {
            color: #fd7e14;
        }

        .abastecimento-tipo i.fa-tint {
            color: #007bff;
        }

        .abastecimento-status {
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

        .abastecimento-info {
            display: flex;
            flex-wrap: wrap;
        }

        .abastecimento-info div {
            flex: 1;
            min-width: 150px;
            margin-bottom: 10px;
        }

        .title-with-icon {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
        }

        .title-with-icon i {
            font-size: 24px;
            color: #17a2b8;
        }

        .tipo-botoes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 15px;
        }

        .tipo-botao {
            flex: 1;
            min-width: 200px;
            padding: 15px;
            border-radius: 5px;
            border: 2px solid #dee2e6;
            background-color: #ffffff;
            cursor: pointer;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            transition: all 0.2s ease;
        }

        .tipo-botao:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .tipo-botao.selected {
            border-color: #007bff;
            background-color: #f0f8ff;
        }

        .tipo-botao i {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .tipo-botao.agua i {
            color: #007bff;
        }

        .tipo-botao.combustivel i {
            color: #fd7e14;
        }

        .cronometro {
            font-size: 18px;
            font-weight: bold;
            margin-top: 10px;
            color: #17a2b8;
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
            <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="deslocamento.php"><i class="fas fa-route"></i> Deslocamento</a></li>
            <li><a href="aguardo.php"><i class="fas fa-pause-circle"></i> Aguardos</a></li>
            <!-- <li><a href="abastecimento.php"><i class="fas fa-gas-pump"></i> Abastecimento</a></li> -->
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

        <div class="title-with-icon">
            <i class="fas fa-gas-pump"></i>
            <h1>Controle de Abastecimento</h1>
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

        <?php if ($abastecimento_ativo): ?>
            <!-- Formulário para finalizar abastecimento -->
            <div class="abastecimento-card abastecimento-ativo <?php echo $abastecimento_ativo['tipo'] == 'agua' ? 'abastecimento-agua' : 'abastecimento-combustivel'; ?>">
                <div class="abastecimento-header">
                    <div class="abastecimento-tipo">
                        <?php if ($abastecimento_ativo['tipo'] == 'agua'): ?>
                            <i class="fas fa-tint"></i> Abastecimento de Água
                        <?php else: ?>
                            <i class="fas fa-gas-pump"></i> Abastecimento de Combustível
                        <?php endif; ?>
                    </div>
                    <div class="abastecimento-status status-ativo">EM ANDAMENTO</div>
                </div>
                <div class="abastecimento-info">
                    <div>
                        <strong>Início:</strong>
                        <?php echo (new DateTime($abastecimento_ativo['inicio_abastecimento']))->format('d/m/Y H:i:s'); ?>
                    </div>
                </div>

                <?php if (!empty($abastecimento_ativo['observacoes'])): ?>
                    <div style="margin-bottom: 15px;">
                        <strong>Observações:</strong>
                        <?php echo nl2br(htmlspecialchars($abastecimento_ativo['observacoes'])); ?>
                    </div>
                <?php endif; ?>

                <div class="cronometro pulsating" id="cronometroAbastecimento">
                    <i class="fas fa-hourglass-half"></i>
                    <span id="tempoDecorrido">Calculando...</span>
                </div>

                <form action="abastecimento.php" method="POST" style="margin-top:15px">
                    <input type="hidden" name="abastecimento_id" value="<?php echo $abastecimento_ativo['id']; ?>">

                    <label for="quantidade">Quantidade (litros):</label>
                    <input type="number" id="quantidade" name="quantidade" step="0.1" min="0.1" required>

                    <button type="submit" name="finalizar_abastecimento" class="btn-finalizar">
                        <i class="fas fa-check-circle"></i> Finalizar Abastecimento
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Formulário para iniciar novo abastecimento -->
            <form action="abastecimento.php" method="POST">
                <h3><i class="fas fa-plus-circle"></i> Novo Abastecimento</h3>

                <div class="form-group">
                    <label>Selecione o tipo de abastecimento:</label>
                    <div class="tipo-botoes">
                        <div class="tipo-botao agua" id="btn-agua">
                            <i class="fas fa-tint"></i>
                            <strong>Água</strong>
                        </div>
                        <div class="tipo-botao combustivel" id="btn-combustivel">
                            <i class="fas fa-gas-pump"></i>
                            <strong>Combustível</strong>
                        </div>
                    </div>
                    <input type="hidden" id="tipo" name="tipo" required>
                </div>

                <label for="observacoes">Observações (opcional):</label>
                <textarea id="observacoes" name="observacoes" rows="3" placeholder="Informações adicionais sobre o abastecimento"></textarea>

                <button type="submit" name="iniciar_abastecimento" id="btn-iniciar" disabled>
                    <i class="fas fa-play"></i> Iniciar Abastecimento
                </button>
            </form>
        <?php endif; ?>

        <!-- Histórico de abastecimentos -->
        <h2><i class="fas fa-history"></i> Histórico de Abastecimentos</h2>

        <?php if (count($abastecimentos) > 0): ?>
            <?php foreach ($abastecimentos as $abastecimento): ?>
                <div class="abastecimento-card <?php echo $abastecimento['tipo'] == 'agua' ? 'abastecimento-agua' : 'abastecimento-combustivel'; ?> <?php echo $abastecimento['fim_abastecimento'] ? 'abastecimento-finalizado' : 'abastecimento-ativo'; ?>">
                    <div class="abastecimento-header">
                        <div class="abastecimento-tipo">
                            <?php if ($abastecimento['tipo'] == 'agua'): ?>
                                <i class="fas fa-tint"></i> Abastecimento de Água
                            <?php else: ?>
                                <i class="fas fa-gas-pump"></i> Abastecimento de Combustível
                            <?php endif; ?>
                        </div>
                        <div class="abastecimento-status <?php echo $abastecimento['fim_abastecimento'] ? 'status-finalizado' : 'status-ativo'; ?>">
                            <?php echo $abastecimento['fim_abastecimento'] ? 'FINALIZADO' : 'EM ANDAMENTO'; ?>
                        </div>
                    </div>

                    <div class="abastecimento-info">
                        <div>
                            <strong>Início:</strong>
                            <?php echo (new DateTime($abastecimento['inicio_abastecimento']))->format('d/m/Y H:i:s'); ?>
                        </div>

                        <?php if (!empty($abastecimento['fim_abastecimento'])): ?>
                            <div>
                                <strong>Fim:</strong>
                                <?php echo (new DateTime($abastecimento['fim_abastecimento']))->format('d/m/Y H:i:s'); ?>
                            </div>
                            <div>
                                <strong>Quantidade:</strong>
                                <?php echo number_format($abastecimento['quantidade'], 1, ',', '.'); ?> litros
                            </div>
                            <div>
                                <strong>Duração:</strong>
                                <?php
                                $duracao = $abastecimento['duracao_segundos'];
                                $horas = floor($duracao / 3600);
                                $minutos = floor(($duracao % 3600) / 60);
                                $segundos = $duracao % 60;
                                echo sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);
                                ?>
                            </div>
                        <?php else: ?>
                            <div>
                                <a href="abastecimento.php?active_id=<?php echo $abastecimento['id']; ?>" class="btn-small">
                                    <i class="fas fa-check-circle"></i> Finalizar
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($abastecimento['observacoes'])): ?>
                        <div style="margin-top: 10px;">
                            <strong>Observações:</strong>
                            <?php echo nl2br(htmlspecialchars($abastecimento['observacoes'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhum abastecimento registrado.</p>
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

            <?php if (!$abastecimento_ativo): ?>
                // Seleção do tipo de abastecimento
                const btnAgua = document.getElementById('btn-agua');
                const btnCombustivel = document.getElementById('btn-combustivel');
                const tipoInput = document.getElementById('tipo');
                const btnIniciar = document.getElementById('btn-iniciar');

                btnAgua.addEventListener('click', function() {
                    btnAgua.classList.add('selected');
                    btnCombustivel.classList.remove('selected');
                    tipoInput.value = 'agua';
                    btnIniciar.disabled = false;
                });

                btnCombustivel.addEventListener('click', function() {
                    btnCombustivel.classList.add('selected');
                    btnAgua.classList.remove('selected');
                    tipoInput.value = 'combustivel';
                    btnIniciar.disabled = false;
                });
            <?php endif; ?>

            <?php if ($abastecimento_ativo): ?>
                // Cronômetro para abastecimento ativo
                const inicioAbastecimento = new Date("<?php echo $abastecimento_ativo['inicio_abastecimento']; ?>").getTime();

                function atualizarCronometro() {
                    const agora = new Date().getTime();
                    const diferenca = Math.floor((agora - inicioAbastecimento) / 1000);

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