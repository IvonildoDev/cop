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
    <title>Operação - Controle OP</title>
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

        /* Status da operação */
        .operacao-status {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .operacao-status.ativo {
            border-left: 5px solid #28a745;
        }

        .operacao-status.concluido {
            border-left: 5px solid #007bff;
        }

        .operacao-status-title {
            font-weight: bold;
            margin-bottom: 10px;
            color: #343a40;
            font-size: 16px;
        }

        .operacao-status-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .operacao-status-icon {
            font-size: 24px;
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

        .operacao-status-text {
            font-size: 14px;
        }

        .operacao-tempo {
            margin-top: 10px;
            font-weight: bold;
            color: #343a40;
        }

        /* Botões de ação */
        .botoes-acao-operacao {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .btn-operacao {
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

        .btn-operacao.iniciar {
            background-color: #28a745;
            color: white;
        }

        .btn-operacao.iniciar:hover:not(:disabled) {
            background-color: #218838;
        }

        .btn-operacao.salvar {
            background-color: #007bff;
            color: white;
        }

        .btn-operacao.salvar:hover:not(:disabled) {
            background-color: #0069d9;
        }

        .btn-operacao:disabled {
            background-color: #6c757d;
            opacity: 0.65;
            cursor: not-allowed;
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

        /* Estilos para o formulário */
        .form-operacao {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #343a40;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 14px;
        }

        .form-group select {
            height: 41px;
        }

        /* Histórico */
        .historico-container {
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        #historicoOperacoes {
            list-style: none;
            padding: 0;
        }

        #historicoOperacoes li {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
        }

        #historicoOperacoes li:last-child {
            border-bottom: none;
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

        .botoes-acao {
            margin-top: 20px;
            display: flex;
            gap: 10px;
        }

        .botoes-acao button {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .botoes-acao button#gerarPDF {
            background-color: #007bff;
            color: white;
        }

        .botoes-acao button.btn-limpar {
            background-color: #dc3545;
            color: white;
        }

        /* Mensagens */
        .success {
            padding: 15px;
            background-color: #d4edda;
            color: #155724;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        .error {
            padding: 15px;
            background-color: #f8d7da;
            color: #721c24;
            border-radius: 4px;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php if (isset($success) && $success): ?>
            <p class="success"><?php echo $success; ?></p>
        <?php endif; ?>

        <?php if (isset($error) && $error): ?>
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

        <!-- Status da operação -->
        <div id="operacaoStatus" class="operacao-status">
            <div class="operacao-status-title">Status da Operação</div>
            <div class="operacao-status-info">
                <i id="statusIcon" class="fas fa-clock operacao-status-icon status-aguardando"></i>
                <span id="statusText" class="operacao-status-text">Aguardando início da operação</span>
            </div>
            <div id="tempoOperacao" class="operacao-tempo"></div>
        </div>

        <!-- Botões de Ação -->
        <div class="botoes-acao-operacao">
            <button type="button" id="iniciarOperacao" class="btn-operacao iniciar">
                <i class="fas fa-play"></i> Iniciar Operação
            </button>
        </div>

        <form id="operacaoForm" action="save_operation.php" method="POST" class="form-operacao">
            <!-- Campos ocultos para registrar os tempos -->
            <input type="hidden" id="inicioOperacao" name="inicioOperacao">
            <input type="hidden" id="fimOperacao" name="fimOperacao">

            <!-- Nome Op/Auxiliar oculto -->
            <input type="hidden" id="nomeOpAux" name="nomeOpAux" value="<?php echo htmlspecialchars($config['nome_operador_principal'] . ' / ' . $config['nome_auxiliar']); ?>">

            <div class="form-group">
                <label for="tipoOperacao">Tipo de Operação:</label>
                <input type="text" id="tipoOperacao" name="tipoOperacao" required>
            </div>

            <div class="form-group">
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
            </div>

            <div id="outraCidadeContainer" class="form-group" style="display:none;">
                <label for="outraCidade">Digite o nome da cidade:</label>
                <input type="text" id="outraCidade" name="outraCidade" placeholder="Nome da cidade">
            </div>

            <div class="form-group">
                <label for="nomePocoServ">Lugar Execução Sev:</label>
                <input type="text" id="nomePocoServ" name="nomePocoServ" required>
            </div>

            <div class="form-group">
                <label for="nomeOperador">Rep Empre:</label>
                <input type="text" id="nomeOperador" name="nomeOperador" required>
            </div>

            <div class="form-group">
                <label for="volumeBbl">Volume (bbl):</label>
                <input type="text" id="volumeBbl" name="volumeBbl" inputmode="decimal" pattern="[0-9]+(\.[0-9]+)?" placeholder="Ex: 10" required>
            </div>

            <div class="form-group">
                <label for="temperatura">Temperatura (°C):</label>
                <input type="text" id="temperatura" name="temperatura" inputmode="decimal" pattern="[0-9]+(\.[0-9]+)?" placeholder="Ex: 25" required>
            </div>

            <div class="form-group">
                <label for="pressao">Pressão (PSI/KGF):</label>
                <input type="text" id="pressao" name="pressao" inputmode="decimal" pattern="[0-9]+(\.[0-9]+)?" placeholder="Ex: 150" required>
            </div>

            <div class="form-group">
                <label for="descricaoAtividades">Descrição das Atividades (até 500 caracteres):</label>
                <textarea id="descricaoAtividades" name="descricaoAtividades" maxlength="500" rows="4"></textarea>
            </div>

            <button type="submit" id="salvarOperacao" class="btn-operacao salvar" disabled>
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
        let timerOperacao = null;
        let segundosOperacao = 0;
        let operacaoEmAndamento = false;

        // Função para formatar o tempo decorrido
        function formatarTempo(segundos) {
            const horas = Math.floor(segundos / 3600);
            const minutos = Math.floor((segundos % 3600) / 60);
            const segs = segundos % 60;

            return `${horas.toString().padStart(2, '0')}:${minutos.toString().padStart(2, '0')}:${segs.toString().padStart(2, '0')}`;
        }

        // Função para formatar data e hora
        function formatarDataHora(data) {
            return data.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        // Botão Início Operação
        document.getElementById('iniciarOperacao').addEventListener('click', function() {
            if (operacaoEmAndamento) return;

            operacaoEmAndamento = true;
            const now = new Date();

            // Obter o offset do fuso horário em minutos
            const timezoneOffset = now.getTimezoneOffset();

            // Criar uma nova data ajustada para o fuso horário local
            const localDate = new Date(now.getTime() - (timezoneOffset * 60000));

            // Extrair a parte da data formatada em ISO (YYYY-MM-DDTHH:mm:ss.sssZ)
            // e converter para o formato do MySQL (YYYY-MM-DD HH:mm:ss)
            const localDateString = localDate.toISOString().slice(0, 19).replace('T', ' ');

            // Registrar início nos campos ocultos
            document.getElementById('inicioOperacao').value = localDateString;

            // Atualizar status
            const statusIcon = document.getElementById('statusIcon');
            const statusText = document.getElementById('statusText');
            const statusDiv = document.getElementById('operacaoStatus');

            statusIcon.className = 'fas fa-cogs operacao-status-icon status-ativo';
            statusText.textContent = 'Operação em andamento - Início: ' + formatarDataHora(now);
            statusDiv.className = 'operacao-status ativo';

            // Iniciar cronômetro
            timerOperacao = setInterval(function() {
                segundosOperacao++;
                document.getElementById('tempoOperacao').textContent = 'Tempo decorrido: ' + formatarTempo(segundosOperacao);
            }, 1000);

            // Desabilitar o botão depois de clicado
            this.disabled = true;

            // Habilitar botão salvar
            document.getElementById('salvarOperacao').disabled = false;
        });

        // Quando o formulário é enviado
        document.getElementById('operacaoForm').addEventListener('submit', function(e) {
            // Verificar se a operação foi iniciada
            if (!operacaoEmAndamento) {
                e.preventDefault();
                alert('Por favor, inicie a operação antes de salvar.');
                return;
            }

            // Registrar hora de fim no momento do salvamento
            const now = new Date();

            // Obter o offset do fuso horário em minutos
            const timezoneOffset = now.getTimezoneOffset();

            // Criar uma nova data ajustada para o fuso horário local
            const localDate = new Date(now.getTime() - (timezoneOffset * 60000));

            // Extrair a parte da data formatada em ISO (YYYY-MM-DDTHH:mm:ss.sssZ)
            // e converter para o formato do MySQL (YYYY-MM-DD HH:mm:ss)
            const localDateString = localDate.toISOString().slice(0, 19).replace('T', ' ');

            document.getElementById('fimOperacao').value = localDateString;

            // Parar o cronômetro
            clearInterval(timerOperacao);

            // Atualizar status
            const statusIcon = document.getElementById('statusIcon');
            const statusText = document.getElementById('statusText');
            const statusDiv = document.getElementById('operacaoStatus');

            statusIcon.className = 'fas fa-check-circle operacao-status-icon status-concluido';
            statusText.textContent = 'Operação finalizada - ' + formatarDataHora(now);
            statusDiv.className = 'operacao-status concluido';
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
    <script src="js/sidebar.js"></script>
</body>

</html>