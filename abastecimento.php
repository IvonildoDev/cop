<?php
// filepath: c:\xampp\htdocs\cop\abastecimento.php
require_once 'config.php';

// Carregar as configurações do sistema
try {
    $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$config) {
        // Se não houver configurações, redirecionar para a página de configuração inicial
        header("Location: config_inicial.php");
        exit;
    }
} catch (PDOException $e) {
    $error = "Erro ao carregar configurações: " . $e->getMessage();
    // Inicializar $config como array vazio para evitar erros
    $config = [
        'nome_operador_principal' => 'Não configurado',
        'nome_auxiliar' => 'Não configurado',
        'nome_unidade' => 'Não configurado',
        'placa_veiculo' => 'Não configurado'
    ];
}

// Verificar se existe a tabela de abastecimentos
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'abastecimentos'");
    if ($stmt->rowCount() == 0) {
        // Criar tabela
        $pdo->exec("CREATE TABLE abastecimentos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            tipo VARCHAR(20) NOT NULL,
            inicio_abastecimento DATETIME NOT NULL,
            fim_abastecimento DATETIME,
            duracao_segundos INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
} catch (PDOException $e) {
    $error = "Erro ao verificar tabela: " . $e->getMessage();
}

// Buscar histórico de abastecimentos
try {
    $stmt = $pdo->query("SELECT * FROM abastecimentos ORDER BY created_at DESC LIMIT 10");
    $abastecimentos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao carregar histórico: " . $e->getMessage();
    $abastecimentos = [];
}

// Mensagens de sucesso ou erro
$success = isset($_GET['success']) ? htmlspecialchars($_GET['success']) : '';
$error = isset($error) ? $error : (isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '');
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abastecimento - Controle OP</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos específicos para abastecimento */
        .tipo-abastecimento {
            margin-bottom: 20px;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 10px;
        }

        .radio-option {
            display: flex;
            align-items: center;
            background-color: #f8f9fa;
            border: 2px solid #ced4da;
            border-radius: 5px;
            padding: 15px 20px;
            cursor: pointer;
            transition: all 0.2s ease;
            width: 150px;
            justify-content: center;
        }

        .radio-option:hover {
            background-color: #e9ecef;
        }

        .radio-option input[type="radio"] {
            margin-right: 10px;
        }

        .radio-option label {
            font-weight: 500;
            cursor: pointer;
        }

        .radio-option.selected {
            background-color: #d1ecf1;
            border-color: #0d6efd;
        }

        .radio-option.selected label {
            color: #0d6efd;
            font-weight: 700;
        }

        .abastecimento-container {
            margin-top: 20px;
            padding: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .abastecimento-grupo {
            margin-bottom: 20px;
        }

        .abastecimento-titulo {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #343a40;
        }

        .abastecimento-botoes {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .btn-abastecimento {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-abastecimento.iniciar {
            background-color: #28a745;
            color: white;
        }

        .btn-abastecimento.iniciar:hover:not(:disabled) {
            background-color: #218838;
        }

        .btn-abastecimento.finalizar {
            background-color: #17a2b8;
            color: white;
        }

        .btn-abastecimento.finalizar:hover:not(:disabled) {
            background-color: #138496;
        }

        .btn-abastecimento.salvar {
            background-color: #007bff;
            color: white;
            margin-top: 20px;
        }

        .btn-abastecimento.salvar:hover {
            background-color: #0069d9;
        }

        .btn-abastecimento:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .abastecimento-status {
            margin-top: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-aguardando {
            color: #6c757d;
        }

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

        .status-concluido {
            color: #28a745;
        }

        .abastecimento-tempo {
            margin-top: 10px;
            font-weight: bold;
            color: #495057;
        }

        .historico-container {
            margin-top: 30px;
        }

        .historico-titulo {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 15px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 10px;
        }

        .historico-item {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .historico-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .historico-tipo {
            font-weight: bold;
        }

        .historico-data,
        .historico-duracao {
            font-size: 14px;
            color: #6c757d;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .empty-state {
            text-align: center;
            padding: 30px;
            color: #6c757d;
        }

        .agua-icon {
            color: #007bff;
        }

        .combustivel-icon {
            color: #fd7e14;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <h1>Abastecimento</h1>

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

        <form id="abastecimentoForm" action="save_abastecimento.php" method="POST">
            <!-- Tipo de abastecimento -->
            <div class="tipo-abastecimento">
                <h3>Selecione o tipo de abastecimento:</h3>
                <div class="radio-group">
                    <div class="radio-option">
                        <input type="radio" id="agua" name="tipoAbastecimento" value="agua" required>
                        <label for="agua"><i class="fas fa-tint agua-icon"></i> Água</label>
                    </div>
                    <div class="radio-option">
                        <input type="radio" id="combustivel" name="tipoAbastecimento" value="combustivel" required>
                        <label for="combustivel"><i class="fas fa-gas-pump combustivel-icon"></i> Combustível</label>
                    </div>
                </div>
            </div>

            <!-- Botões de controle -->
            <div class="abastecimento-container">
                <div class="abastecimento-grupo">
                    <div class="abastecimento-titulo">Abastecimento</div>
                    <div class="abastecimento-botoes">
                        <button type="button" id="inicioAbastecimento" class="btn-abastecimento iniciar" disabled>
                            <i class="fas fa-play"></i> Iniciar Abastecimento
                        </button>
                        <button type="button" id="fimAbastecimento" class="btn-abastecimento finalizar" disabled>
                            <i class="fas fa-flag-checkered"></i> Finalizar Abastecimento
                        </button>
                    </div>
                    <div class="abastecimento-status">
                        <i id="statusAbastecimentoIcon" class="fas fa-clock status-aguardando"></i>
                        <span id="statusAbastecimento" class="abastecimento-status-texto">Aguardando início do abastecimento</span>
                    </div>
                    <div id="tempoAbastecimento" class="abastecimento-tempo"></div>
                </div>
            </div>

            <!-- Campos ocultos para registrar os tempos -->
            <input type="hidden" id="inicioAbastecimentoTimestamp" name="inicioAbastecimentoTimestamp">
            <input type="hidden" id="fimAbastecimentoTimestamp" name="fimAbastecimentoTimestamp">
            <input type="hidden" id="abastecimentoInicio" name="abastecimentoInicio">
            <input type="hidden" id="abastecimentoFim" name="abastecimentoFim">
            <input type="hidden" id="abastecimentoStatus" name="abastecimentoStatus" value="Não iniciado">
            <input type="hidden" id="tipoAbastecimentoHidden" name="tipoAbastecimentoHidden">

            <button type="submit" class="btn-abastecimento salvar">
                <i class="fas fa-save"></i> Salvar Abastecimento
            </button>
        </form>

        <!-- Histórico de Abastecimentos -->
        <div class="historico-container">
            <h2 class="historico-titulo">Histórico de Abastecimentos</h2>

            <?php if (count($abastecimentos) > 0): ?>
                <?php foreach ($abastecimentos as $abastecimento): ?>
                    <div class="historico-item">
                        <div class="historico-header">
                            <div class="historico-tipo">
                                <?php if ($abastecimento['tipo'] == 'agua'): ?>
                                    <i class="fas fa-tint agua-icon"></i> Abastecimento de Água
                                <?php else: ?>
                                    <i class="fas fa-gas-pump combustivel-icon"></i> Abastecimento de Combustível
                                <?php endif; ?>
                            </div>
                            <div class="historico-data">
                                <?php
                                $data = new DateTime($abastecimento['inicio_abastecimento']);
                                echo $data->format('d/m/Y H:i');
                                ?>
                            </div>
                        </div>

                        <?php if (!empty($abastecimento['fim_abastecimento'])): ?>
                            <div class="historico-duracao">
                                Duração:
                                <?php
                                $horas = floor($abastecimento['duracao_segundos'] / 3600);
                                $minutos = floor(($abastecimento['duracao_segundos'] % 3600) / 60);
                                echo sprintf("%02d:%02d", $horas, $minutos);
                                ?>
                            </div>
                        <?php else: ?>
                            <div class="historico-duracao">Em andamento</div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-info-circle"></i> Nenhum abastecimento registrado.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <!-- Scripts específicos de abastecimento -->
    <script>
        // Função para formatar o tempo decorrido
        function formatarTempo(segundos) {
            const horas = Math.floor(segundos / 3600);
            const minutos = Math.floor((segundos % 3600) / 60);
            const segs = segundos % 60;

            return `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }

        // Variáveis para controle do tempo
        let timerAbastecimento = null;
        let segundosAbastecimento = 0;

        document.addEventListener('DOMContentLoaded', function() {
            console.log('Script de abastecimento carregado');

            // Referência aos elementos
            const tipoAguaRadio = document.getElementById('agua');
            const tipoCombustivelRadio = document.getElementById('combustivel');
            const iniciarButton = document.getElementById('inicioAbastecimento');
            const finalizarButton = document.getElementById('fimAbastecimento');
            const radioOptions = document.querySelectorAll('.radio-option');

            // Função para verificar se um tipo foi selecionado
            function checkTipoSelecionado() {
                if (tipoAguaRadio.checked || tipoCombustivelRadio.checked) {
                    iniciarButton.disabled = false;
                } else {
                    iniciarButton.disabled = true;
                }
            }

            // Adicionar eventos aos radio buttons
            tipoAguaRadio.addEventListener('change', function() {
                checkTipoSelecionado();
                // Destacar visualmente a opção selecionada
                radioOptions.forEach(option => {
                    option.classList.remove('selected');
                });
                tipoAguaRadio.closest('.radio-option').classList.add('selected');
            });

            tipoCombustivelRadio.addEventListener('change', function() {
                checkTipoSelecionado();
                // Destacar visualmente a opção selecionada
                radioOptions.forEach(option => {
                    option.classList.remove('selected');
                });
                tipoCombustivelRadio.closest('.radio-option').classList.add('selected');
            });

            // Adicionar evento de clique às div's da opção para melhorar a experiência do usuário
            radioOptions.forEach(option => {
                option.addEventListener('click', function() {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;

                        // Disparar o evento change manualmente
                        const event = new Event('change');
                        radio.dispatchEvent(event);
                    }
                });
            });

            // Verificar o estado inicial
            checkTipoSelecionado();

            // Controle de abastecimento
            iniciarButton.addEventListener('click', function() {
                console.log('Botão iniciar clicado');

                // Registrar o início do abastecimento
                const now = new Date();
                document.getElementById('inicioAbastecimentoTimestamp').value = now.getTime();
                document.getElementById('abastecimentoInicio').value = now.toISOString();

                // Atualizar interface
                this.disabled = true;
                finalizarButton.disabled = false;

                const statusIcon = document.getElementById('statusAbastecimentoIcon');
                const statusText = document.getElementById('statusAbastecimento');
                statusIcon.className = 'fas fa-gas-pump status-ativo';

                if (tipoAguaRadio.checked) {
                    statusText.textContent = 'Abastecimento de água em andamento';
                    document.getElementById('tipoAbastecimentoHidden').value = 'agua';
                } else {
                    statusText.textContent = 'Abastecimento de combustível em andamento';
                    document.getElementById('tipoAbastecimentoHidden').value = 'combustivel';
                }

                // Iniciar o cronômetro
                timerAbastecimento = setInterval(function() {
                    segundosAbastecimento++;
                    document.getElementById('tempoAbastecimento').textContent = 'Tempo: ' + formatarTempo(segundosAbastecimento);
                }, 1000);

                document.getElementById('abastecimentoStatus').value = 'Em andamento';
            });

            finalizarButton.addEventListener('click', function() {
                console.log('Botão finalizar clicado');

                // Registrar o fim do abastecimento
                const now = new Date();
                document.getElementById('fimAbastecimentoTimestamp').value = now.getTime();
                document.getElementById('abastecimentoFim').value = now.toISOString();

                // Atualizar interface
                this.disabled = true;

                // Parar o cronômetro
                clearInterval(timerAbastecimento);

                const statusIcon = document.getElementById('statusAbastecimentoIcon');
                const statusText = document.getElementById('statusAbastecimento');
                statusIcon.className = 'fas fa-check-circle status-concluido';
                statusText.textContent = 'Abastecimento concluído';

                document.getElementById('abastecimentoStatus').value = 'Concluído';
            });

            // Validação do formulário
            document.getElementById('abastecimentoForm').addEventListener('submit', function(e) {
                const abastecimentoStatus = document.getElementById('abastecimentoStatus').value;

                if (abastecimentoStatus === 'Não iniciado') {
                    e.preventDefault();
                    alert('Por favor, inicie o abastecimento antes de salvar.');
                    return false;
                }

                return true;
            });
        });
    </script>
</body>

</html>