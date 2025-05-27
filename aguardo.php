<?php
// filepath: c:\xampp\htdocs\cop\aguardo.php
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

// Verificar se a tabela de aguardos existe, caso contrário, criá-la
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'aguardos'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("CREATE TABLE aguardos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inicio_aguardo DATETIME NOT NULL,
            fim_aguardo DATETIME,
            motivo TEXT NOT NULL,
            operacao_id INT,
            duracao_segundos INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (operacao_id) REFERENCES operacoes(id) ON DELETE SET NULL
        )");
    }
} catch (PDOException $e) {
    $error = "Erro ao verificar tabela de aguardos: " . $e->getMessage();
}

// Iniciar um novo aguardo
if (isset($_POST['iniciar_aguardo'])) {
    $motivo = $_POST['motivo'] ?? '';

    if (empty($motivo)) {
        $error = "Por favor, informe o motivo do aguardo.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO aguardos (inicio_aguardo, motivo) VALUES (NOW(), :motivo)");
            $stmt->execute([':motivo' => $motivo]);

            $success = "Aguardo iniciado com sucesso!";
            $aguardo_id = $pdo->lastInsertId();

            // Redirecionar para evitar reenvio do formulário
            header("Location: aguardo.php?active_id=" . $aguardo_id . "&success=" . urlencode($success));
            exit;
        } catch (PDOException $e) {
            $error = "Erro ao iniciar aguardo: " . $e->getMessage();
        }
    }
}

// Finalizar um aguardo ativo
if (isset($_POST['finalizar_aguardo'])) {
    $aguardo_id = $_POST['aguardo_id'] ?? 0;

    if (empty($aguardo_id)) {
        $error = "ID de aguardo inválido.";
    } else {
        try {
            // Obter timestamp de início para calcular duração
            $stmt = $pdo->prepare("SELECT inicio_aguardo FROM aguardos WHERE id = :id");
            $stmt->execute([':id' => $aguardo_id]);
            $inicio = $stmt->fetchColumn();

            if ($inicio) {
                $inicio_dt = new DateTime($inicio);
                $fim_dt = new DateTime();
                $duracao = $fim_dt->getTimestamp() - $inicio_dt->getTimestamp();

                $stmt = $pdo->prepare("UPDATE aguardos SET 
                    fim_aguardo = NOW(),
                    duracao_segundos = :duracao
                    WHERE id = :id AND fim_aguardo IS NULL");

                $stmt->execute([
                    ':duracao' => $duracao,
                    ':id' => $aguardo_id
                ]);

                if ($stmt->rowCount() > 0) {
                    $success = "Aguardo finalizado com sucesso!";
                    header("Location: aguardo.php?success=" . urlencode($success));
                    exit;
                } else {
                    $error = "Aguardo não encontrado ou já finalizado.";
                }
            } else {
                $error = "Aguardo não encontrado.";
            }
        } catch (PDOException $e) {
            $error = "Erro ao finalizar aguardo: " . $e->getMessage();
        }
    }
}

// Verificar se existe um aguardo ativo
$aguardo_ativo = null;
$active_id = $_GET['active_id'] ?? null;

if ($active_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM aguardos WHERE id = :id AND fim_aguardo IS NULL");
        $stmt->execute([':id' => $active_id]);
        $aguardo_ativo = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Erro ao carregar aguardo ativo: " . $e->getMessage();
    }
}

// Carregar histórico de aguardos
try {
    $stmt = $pdo->query("SELECT * FROM aguardos ORDER BY inicio_aguardo DESC LIMIT 20");
    $aguardos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao carregar histórico: " . $e->getMessage();
    $aguardos = [];
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
    <title>Controle de Aguardos</title>
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
            <!-- <li><a href="aguardo.php" class="active"><i class="fas fa-pause-circle"></i> Aguardos</a></li> -->
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
            <i class="fas fa-pause-circle"></i>
            <h1>Controle de Aguardos</h1>
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

        <?php if ($aguardo_ativo): ?>
            <!-- Formulário para finalizar aguardo -->
            <div class="aguardo-card aguardo-ativo">
                <div class="aguardo-header">
                    <div class="aguardo-motivo">
                        <?php echo htmlspecialchars($aguardo_ativo['motivo']); ?>
                    </div>
                    <div class="aguardo-status status-ativo">AGUARDANDO</div>
                </div>
                <div class="aguardo-info">
                    <div>
                        <strong>Início:</strong>
                        <?php echo (new DateTime($aguardo_ativo['inicio_aguardo']))->format('d/m/Y H:i:s'); ?>
                    </div>
                </div>

                <div class="cronometro pulsating" id="cronometroAguardo">
                    <i class="fas fa-hourglass-half"></i>
                    <span id="tempoDecorrido">Calculando...</span>
                </div>

                <form action="aguardo.php" method="POST" style="margin-top:15px">
                    <input type="hidden" name="aguardo_id" value="<?php echo $aguardo_ativo['id']; ?>">
                    <button type="submit" name="finalizar_aguardo" class="btn-finalizar">
                        <i class="fas fa-stop-circle"></i> Finalizar Aguardo
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Formulário para iniciar novo aguardo -->
            <form action="aguardo.php" method="POST">
                <h3><i class="fas fa-plus-circle"></i> Novo Aguardo</h3>

                <label for="motivo">Motivo do Aguardo:</label>
                <textarea id="motivo" name="motivo" rows="3" required placeholder="Descreva o motivo do aguardo"></textarea>

                <button type="submit" name="iniciar_aguardo">
                    <i class="fas fa-play"></i> Iniciar Aguardo
                </button>
            </form>
        <?php endif; ?>

        <!-- Histórico de aguardos -->
        <h2><i class="fas fa-history"></i> Histórico de Aguardos</h2>

        <?php if (count($aguardos) > 0): ?>
            <?php foreach ($aguardos as $aguardo): ?>
                <div class="aguardo-card <?php echo $aguardo['fim_aguardo'] ? 'aguardo-finalizado' : 'aguardo-ativo'; ?>">
                    <div class="aguardo-header">
                        <div class="aguardo-motivo">
                            <?php echo htmlspecialchars($aguardo['motivo']); ?>
                        </div>
                        <div class="aguardo-status <?php echo $aguardo['fim_aguardo'] ? 'status-finalizado' : 'status-ativo'; ?>">
                            <?php echo $aguardo['fim_aguardo'] ? 'FINALIZADO' : 'AGUARDANDO'; ?>
                        </div>
                    </div>

                    <div class="aguardo-info">
                        <div>
                            <strong>Início:</strong>
                            <?php echo (new DateTime($aguardo['inicio_aguardo']))->format('d/m/Y H:i:s'); ?>
                        </div>

                        <?php if (!empty($aguardo['fim_aguardo'])): ?>
                            <div>
                                <strong>Fim:</strong>
                                <?php echo (new DateTime($aguardo['fim_aguardo']))->format('d/m/Y H:i:s'); ?>
                            </div>
                            <div>
                                <strong>Duração:</strong>
                                <?php
                                $duracao = $aguardo['duracao_segundos'];
                                $horas = floor($duracao / 3600);
                                $minutos = floor(($duracao % 3600) / 60);
                                $segundos = $duracao % 60;
                                echo sprintf("%02d:%02d:%02d", $horas, $minutos, $segundos);
                                ?>
                            </div>
                        <?php else: ?>
                            <div>
                                <a href="aguardo.php?active_id=<?php echo $aguardo['id']; ?>" class="btn-small">
                                    <i class="fas fa-stop-circle"></i> Finalizar
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>Nenhum aguardo registrado.</p>
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

            // Cronômetro para aguardo ativo
            <?php if ($aguardo_ativo): ?>
                const inicioAguardo = new Date("<?php echo $aguardo_ativo['inicio_aguardo']; ?>").getTime();

                function atualizarCronometro() {
                    const agora = new Date().getTime();
                    const diferenca = Math.floor((agora - inicioAguardo) / 1000);

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