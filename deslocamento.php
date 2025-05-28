<?php
// No início de cada arquivo, altere:
require_once 'config.php';
// require_once 'includes/auth_check.php'; // Autenticação temporariamente desabilitada

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

// Iniciar um novo deslocamento
if (isset($_POST['iniciar_deslocamento'])) {
    $origem = $_POST['origem'] ?? '';
    $destino = $_POST['destino'] ?? '';
    $kmInicial = $_POST['km_inicial'] ?? 0;
    $observacoes = $_POST['observacoes'] ?? '';

    // Validação simples
    if (empty($origem) || empty($destino) || empty($kmInicial)) {
        $error = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO deslocamentos 
                (origem, destino, km_inicial, inicio_deslocamento, observacoes) 
                VALUES (:origem, :destino, :km_inicial, NOW(), :observacoes)");

            $stmt->execute([
                ':origem' => $origem,
                ':destino' => $destino,
                ':km_inicial' => $kmInicial,
                ':observacoes' => $observacoes
            ]);

            $success = "Deslocamento iniciado com sucesso!";
            $deslocamento_atual_id = $pdo->lastInsertId();

            // Redirecionar para evitar reenvio do formulário
            header("Location: deslocamento.php?active_id=" . $deslocamento_atual_id . "&success=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = "Erro ao iniciar deslocamento: " . $e->getMessage();
        }
    }
}

// Finalizar um deslocamento ativo
if (isset($_POST['finalizar_deslocamento'])) {
    $deslocamentoId = $_POST['deslocamento_id'] ?? 0;
    $kmFinal = $_POST['km_final'] ?? 0;

    if (empty($deslocamentoId) || empty($kmFinal)) {
        $error = "Por favor, preencha a quilometragem final.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE deslocamentos 
                SET km_final = :km_final, fim_deslocamento = NOW()
                WHERE id = :id AND fim_deslocamento IS NULL");

            $stmt->execute([
                ':km_final' => $kmFinal,
                ':id' => $deslocamentoId
            ]);

            if ($stmt->rowCount() > 0) {
                $success = "Deslocamento finalizado com sucesso!";
                header("Location: deslocamento.php?success=" . urlencode($success));
                exit;
            } else {
                $error = "Deslocamento não encontrado ou já finalizado.";
            }
        } catch (PDOException $e) {
            $error = "Erro ao finalizar deslocamento: " . $e->getMessage();
        }
    }
}

// Verificar se existe um deslocamento ativo
$deslocamento_ativo = null;
$active_id = $_GET['active_id'] ?? null;

if ($active_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM deslocamentos WHERE id = :id AND fim_deslocamento IS NULL");
        $stmt->execute([':id' => $active_id]);
        $deslocamento_ativo = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Erro ao carregar deslocamento ativo: " . $e->getMessage();
    }
}

// Carregar histórico de deslocamentos
try {
    $stmt = $pdo->query("SELECT * FROM deslocamentos ORDER BY created_at DESC LIMIT 10");
    $deslocamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao carregar histórico: " . $e->getMessage();
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
    <title>Controle de Deslocamento</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <nav class="navbar"></nav>
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
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <div class="title-with-icon">
            <i class="fas fa-route"></i>
            <h1>Controle de Deslocamento</h1>
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

        <?php if ($deslocamento_ativo): ?>
            <!-- Formulário para finalizar deslocamento -->
            <div class="deslocamento-card deslocamento-ativo">
                <div class="deslocamento-header">
                    <div class="deslocamento-rota">
                        <?php echo htmlspecialchars($deslocamento_ativo['origem']); ?> →
                        <?php echo htmlspecialchars($deslocamento_ativo['destino']); ?>
                    </div>
                    <div class="deslocamento-status status-ativo">ATIVO</div>
                </div>
                <div class="deslocamento-info">
                    <div>
                        <strong>Início:</strong>
                        <?php echo (new DateTime($deslocamento_ativo['inicio_deslocamento']))->format('d/m/Y H:i:s'); ?>
                    </div>
                    <div>
                        <strong>KM Inicial:</strong>
                        <?php echo number_format($deslocamento_ativo['km_inicial'], 1, ',', '.'); ?> km
                    </div>
                </div>

                <form action="deslocamento.php" method="POST">
                    <input type="hidden" name="deslocamento_id" value="<?php echo $deslocamento_ativo['id']; ?>">

                    <label for="km_final">Quilometragem Final (km):</label>
                    <input type="number" id="km_final" name="km_final" step="0.1" required>

                    <button type="submit" name="finalizar_deslocamento" class="btn-finalizar">
                        <i class="fas fa-flag-checkered"></i> Finalizar Deslocamento
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Formulário para iniciar novo deslocamento -->
            <form action="deslocamento.php" method="POST">
                <h3><i class="fas fa-plus-circle"></i> Novo Deslocamento</h3>

                <label for="origem">Origem:</label>
                <input type="text" id="origem" name="origem" required>

                <label for="destino">Destino:</label>
                <input type="text" id="destino" name="destino" required>

                <label for="km_inicial">Quilometragem Inicial (km):</label>
                <input type="number" id="km_inicial" name="km_inicial" step="0.1" required>

                <label for="observacoes">Observações:</label>
                <textarea id="observacoes" name="observacoes" rows="3"></textarea>

                <button type="submit" name="iniciar_deslocamento">
                    <i class="fas fa-play"></i> Iniciar Deslocamento
                </button>
            </form>
        <?php endif; ?>

        <!-- Histórico de deslocamentos -->
        <h2><i class="fas fa-history"></i> Histórico de Deslocamentos</h2>

        <?php if (count($deslocamentos) > 0): ?>
            <?php foreach ($deslocamentos as $deslocamento): ?>
                <div class="deslocamento-card <?php echo $deslocamento['fim_deslocamento'] ? 'deslocamento-finalizado' : 'deslocamento-ativo'; ?>">
                    <div class="deslocamento-header">
                        <div class="deslocamento-rota">
                            <?php echo htmlspecialchars($deslocamento['origem']); ?> →
                            <?php echo htmlspecialchars($deslocamento['destino']); ?>
                        </div>
                        <div class="deslocamento-status <?php echo $deslocamento['fim_deslocamento'] ? 'status-finalizado' : 'status-ativo'; ?>">
                            <?php echo $deslocamento['fim_deslocamento'] ? 'FINALIZADO' : 'ATIVO'; ?>
                        </div>
                    </div>

                    <div class="deslocamento-info">
                        <div>
                            <strong>Início:</strong>
                            <?php echo (new DateTime($deslocamento['inicio_deslocamento']))->format('d/m/Y H:i:s'); ?>
                        </div>
                        <div>
                            <strong>KM Inicial:</strong>
                            <?php echo number_format($deslocamento['km_inicial'], 1, ',', '.'); ?> km
                        </div>

                        <?php if ($deslocamento['fim_deslocamento']): ?>
                            <div>
                                <strong>Fim:</strong>
                                <?php echo (new DateTime($deslocamento['fim_deslocamento']))->format('d/m/Y H:i:s'); ?>
                            </div>
                            <div>
                                <strong>KM Final:</strong>
                                <?php echo number_format($deslocamento['km_final'], 1, ',', '.'); ?> km
                            </div>
                            <div>
                                <strong>Distância:</strong>
                                <?php echo number_format($deslocamento['km_final'] - $deslocamento['km_inicial'], 1, ',', '.'); ?> km
                            </div>
                        <?php else: ?>
                            <div>
                                <a href="deslocamento.php?active_id=<?php echo $deslocamento['id']; ?>" class="btn-small">
                                    <i class="fas fa-flag-checkered"></i> Finalizar
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($deslocamento['observacoes'])): ?>
                        <div class="deslocamento-observacoes">
                            <strong>Observações:</strong> <?php echo nl2br(htmlspecialchars($deslocamento['observacoes'])); ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhum deslocamento registrado.</p>
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

            // Adicionar data e hora atual ao campo de início quando o botão é clicado
            const btnMarcarInicio = document.getElementById("marcarInicio");
            if (btnMarcarInicio) {
                btnMarcarInicio.addEventListener("click", function() {
                    const now = new Date();
                    const dataFormatada = now.toISOString().slice(0, 16);
                    document.getElementById("inicioOperacao").value = dataFormatada;
                });
            }
        });
    </script>
</body>

</html>