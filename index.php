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

// Fetch operation history
try {
    $stmt = $pdo->query("SELECT * FROM operacoes ORDER BY created_at DESC");
    $operacoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao carregar histórico: " . $e->getMessage();
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
    <title>Controle OP</title>
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

        /* Novos estilos para os botões de mobilização e desmobilização */
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

        /* Estilos para o modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0, 0, 0);
            background-color: rgba(0, 0, 0, 0.4);
            padding-top: 60px;
        }

        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 500px;
            border-radius: 5px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .aguardo-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 12px;
            color: white;
            background-color: #007bff;
            margin-left: 5px;
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
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>
        <?php if ($error): ?>
            <p class="error"><?php echo $error; ?></p>
        <?php endif; ?>

        <h1>Controle de Operações</h1>

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

        <form id="operacaoForm" action="save_operation.php" method="POST">
            <!-- Tempo de operação modificado - apenas botões de início e fim -->
            <div class="tempo-operacao">
                <div class="tempo-grupo">
                    <label for="inicioOperacao">Início da Operação:</label>
                    <div class="input-grupo">
                        <input type="datetime-local" id="inicioOperacao" name="inicioOperacao" readonly required>
                        <button type="button" id="marcarInicio" class="btn-mobilizacao iniciar">
                            <i class="fas fa-play-circle"></i> Início Operação
                        </button>
                    </div>
                </div>
            </div>

            <!-- Adicione logo após o botão "Início Operação" e antes do campo "tipoOperacao" -->
            <input type="hidden" id="nomeOpAux" name="nomeOpAux" value="<?php echo htmlspecialchars($config['nome_operador_principal'] . ' / ' . $config['nome_auxiliar']); ?>">

            <label for="tipoOperacao">Tipo de Operação:</label>
            <input type="text" id="tipoOperacao" name="tipoOperacao" required>

            <label for="nomeCidade">Selecione a Cidade:</label>
            <select id="nomeCidade" name="nomeCidade" required onchange="toggleOutraCidade()">
                <option value="">Selecione uma cidade</option>
                <option value="Maceió">Maceió</option>
                <option value="Pilar">Pilar</option>
                <option value="São Miguel dos Campos">São Miguel dos Campos</option>
                <option value="Coruripe">Coruripe</option>
                <option value="Satuba">Satuba</option>
                <option value="outra">Outra cidade</option>
            </select>

            <div id="outraCidadeContainer" style="display:none;">
                <label for="outraCidade">Digite o nome da cidade:</label>
                <input type="text" id="outraCidade" name="outraCidade" placeholder="Nome da cidade">
            </div>

            <label for="nomePocoServ">Lugar Execução Sev:</label>
            <input type="text" id="nomePocoServ" name="nomePocoServ" required>

            <label for="nomeOperador">Rep Empre:</label>
            <input type="text" id="nomeOperador" name="nomeOperador" required>

            <label for="volumeBbl">Volume (bbl):</label>
            <input type="text" id="volumeBbl" name="volumeBbl" inputmode="decimal" pattern="[0-9]+(\.[0-9]+)?" placeholder="Ex: 10" required>

            <label for="temperatura">Temperatura (°C):</label>
            <input type="text" id="temperatura" name="temperatura" inputmode="decimal" pattern="[0-9]+(\.[0-9]+)?" placeholder="Ex: 25" required>

            <label for="pressao">Pressão (PSI/KGF):</label>
            <input type="text" id="pressao" name="pressao" inputmode="decimal" pattern="[0-9]+(\.[0-9]+)?" placeholder="Ex: 150" required>

            <label for="descricaoAtividades">Descrição das Atividades (até 500 caracteres):</label>
            <textarea id="descricaoAtividades" name="descricaoAtividades" maxlength="500" rows="4"></textarea>

            <div class="tempo-operacao">
                <div class="tempo-grupo">
                    <label for="fimOperacao">Fim da Operação:</label>
                    <input type="datetime-local" id="fimOperacao" name="fimOperacao" readonly>
                    <!-- O botão "Salvar Operação" já serve como finalizador -->
                </div>
            </div>
            <button type="submit" class="btn-mobilizacao finalizar">
                <i class="fas fa-save"></i> Salvar Operação
            </button>
        </form>

        <h2>Histórico de Operações</h2>
        <div class="historico-container">
            <ul id="historicoOperacoes">
                <?php foreach ($operacoes as $op): ?>
                    <li>
                        <strong>Início:</strong> <?php echo htmlspecialchars($op['inicio_operacao']); ?><br>
                        <strong>Nome OP/Aux:</strong> <?php echo htmlspecialchars($op['nome_op_aux']); ?><br>
                        <strong>Tipo Operação:</strong> <?php echo htmlspecialchars($op['tipo_operacao']); ?><br>
                        <strong>Cidade:</strong> <?php echo htmlspecialchars($op['nome_cidade']); ?><br>
                        <strong>Poço/Serviço:</strong> <?php echo htmlspecialchars($op['nome_poco_serv']); ?><br>
                        <strong>Operador:</strong> <?php echo htmlspecialchars($op['nome_operador']); ?><br>
                        <strong>Volume (bbl):</strong> <?php echo htmlspecialchars($op['volume_bbl']); ?><br>
                        <strong>Temperatura (°C):</strong> <?php echo htmlspecialchars($op['temperatura']); ?><br>
                        <strong>Pressão (PSI/KGF):</strong> <?php echo htmlspecialchars($op['pressao']); ?><br>
                        <strong>Descrição:</strong> <?php echo htmlspecialchars($op['descricao_atividades']); ?><br>

                        <?php if (!empty($op['aguardo_inicio'])): ?>
                            <strong>Aguardo:</strong> <?php echo htmlspecialchars($op['aguardo_motivo']); ?>
                            <span class="aguardo-badge">
                                <?php
                                if (!empty($op['aguardo_fim'])) {
                                    $inicio = new DateTime($op['aguardo_inicio']);
                                    $fim = new DateTime($op['aguardo_fim']);
                                    $intervalo = $inicio->diff($fim);

                                    echo $intervalo->format('%Hh %Im');
                                } else {
                                    echo "Em andamento";
                                }
                                ?>
                            </span><br>
                        <?php endif; ?>

                        <strong>Fim:</strong> <?php echo htmlspecialchars($op['fim_operacao'] ?? '-'); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="botoes-acao">
            <a href="generate_pdf.php"><button type="button" id="gerarPDF">Gerar PDF do Histórico</button></a>
            <a href="clear_history.php"><button type="button" id="limparHistorico" class="btn-limpar">Limpar Histórico</button></a>
        </div>
    </div>

    <script>
        // Função para formatar o tempo decorrido (mantemos para outras funcionalidades)
        function formatarTempo(segundos) {
            const horas = Math.floor(segundos / 3600);
            const minutos = Math.floor((segundos % 3600) / 60);
            const segs = segundos % 60;

            return `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }

        // Botão Início Operação
        document.getElementById('marcarInicio').addEventListener('click', function() {
            const now = new Date();
            const formattedDate = now.toISOString().slice(0, 16);
            document.getElementById('inicioOperacao').value = formattedDate;

            // Desabilitar o botão depois de clicado
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-check-circle"></i> Operação Iniciada';
            this.classList.remove('iniciar');
            this.classList.add('finalizar');
        });

        document.querySelector('.hamburger').addEventListener('click', function() {
            document.querySelector('.nav-menu').classList.toggle('active');
        });

        // Função para mostrar ou esconder o campo "outra cidade"
        function toggleOutraCidade() {
            const cidadeSelect = document.getElementById('nomeCidade');
            const outraCidadeContainer = document.getElementById('outraCidadeContainer');
            const outraCidadeInput = document.getElementById('outraCidade');

            // Mostrar ou esconder o campo de entrada de texto com base na seleção
            if (cidadeSelect.value === 'outra') {
                outraCidadeContainer.style.display = 'block';
                outraCidadeInput.setAttribute('required', 'required');
            } else {
                outraCidadeContainer.style.display = 'none';
                outraCidadeInput.removeAttribute('required');
                outraCidadeInput.value = '';
            }
        }

        // Chamar a função no carregamento da página para garantir estado inicial correto
        document.addEventListener('DOMContentLoaded', function() {
            toggleOutraCidade();
        });
    </script>
</body>

</html>