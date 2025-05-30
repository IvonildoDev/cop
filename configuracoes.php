<?php
require_once 'config.php';

$message = '';
$dbUpdateMessage = '';

try {
    // Verificar se as colunas já existem
    $stmt = $pdo->prepare("SHOW COLUMNS FROM configuracoes LIKE 'nome_operador_principal'");
    $stmt->execute();
    $column_exists = $stmt->rowCount() > 0;

    // Se a coluna não existir, adicione-a
    if (!$column_exists) {
        $pdo->exec("ALTER TABLE configuracoes 
            ADD COLUMN nome_operador_principal VARCHAR(100) NOT NULL DEFAULT '',
            ADD COLUMN nome_auxiliar VARCHAR(100) NOT NULL DEFAULT ''");

        $dbUpdateMessage = "Tabela atualizada com sucesso!";
    }
} catch (PDOException $e) {
    $dbUpdateMessage = "Erro ao atualizar banco de dados: " . $e->getMessage();
}

// Verificar se já existem configurações
$stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

// Se não existir nenhuma configuração, inserir uma linha vazia
if (!$config) {
    try {
        $pdo->exec("INSERT INTO configuracoes (nome_operador_principal, nome_auxiliar, nome_unidade, placa_veiculo) 
                  VALUES ('', '', '', '')");
        // Buscar a configuração recém-criada
        $stmt = $pdo->query("SELECT * FROM configuracoes LIMIT 1");
        $config = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $dbUpdateMessage = "Erro ao criar configuração inicial: " . $e->getMessage();
    }
}

// Se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Obter dados do formulário
    $nome_operador = $_POST['nome_operador'] ?? '';
    $nome_auxiliar = $_POST['nome_auxiliar'] ?? '';
    $nome_unidade = $_POST['nome_unidade'] ?? '';
    $placa_veiculo = $_POST['placa_veiculo'] ?? '';

    // Validar e sanitizar entradas
    $nome_operador = htmlspecialchars(trim($nome_operador));
    $nome_auxiliar = htmlspecialchars(trim($nome_auxiliar));
    $nome_unidade = htmlspecialchars(trim($nome_unidade));
    $placa_veiculo = htmlspecialchars(trim($placa_veiculo));

    // Atualizar as configurações no banco de dados
    try {
        $stmt = $pdo->prepare("UPDATE configuracoes SET 
            nome_operador_principal = :nome_operador, 
            nome_auxiliar = :nome_auxiliar, 
            nome_unidade = :nome_unidade, 
            placa_veiculo = :placa_veiculo 
            WHERE id = :id");

        $stmt->execute([
            ':nome_operador' => $nome_operador,
            ':nome_auxiliar' => $nome_auxiliar,
            ':nome_unidade' => $nome_unidade,
            ':placa_veiculo' => $placa_veiculo,
            ':id' => $config['id']
        ]);
        $message = 'Configurações salvas com sucesso!';

        // Atualizar a variável de configuração
        $config = [
            'id' => $config['id'],
            'nome_operador_principal' => $nome_operador,
            'nome_auxiliar' => $nome_auxiliar,
            'nome_unidade' => $nome_unidade,
            'placa_veiculo' => $placa_veiculo,
        ];

        // Redirecionar para a página principal após 2 segundos
        header("refresh:2;url=index.php");
    } catch (PDOException $e) {
        $message = 'Erro ao salvar configurações: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações - Controle OP</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Estilos específicos de configurações -->
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

        <h1>Configurações do Sistema</h1>

        <div class="config-container">
            <h1>Configuração Inicial do Sistema</h1>

            <?php if ($dbUpdateMessage): ?>
                <div class="alert info"><?php echo $dbUpdateMessage; ?></div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert success"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="nome_operador">Nome do Operador Principal:</label>
                <input type="text" id="nome_operador" name="nome_operador" value="<?php echo htmlspecialchars($config['nome_operador_principal'] ?? ''); ?>" required>

                <label for="nome_auxiliar">Nome do Auxiliar:</label>
                <input type="text" id="nome_auxiliar" name="nome_auxiliar" value="<?php echo htmlspecialchars($config['nome_auxiliar'] ?? ''); ?>" required>

                <label for="nome_unidade">Nome da Unidade:</label>
                <input type="text" id="nome_unidade" name="nome_unidade" value="<?php echo htmlspecialchars($config['nome_unidade'] ?? ''); ?>" required>

                <label for="placa_veiculo">Placa do Veículo:</label>
                <input type="text" id="placa_veiculo" name="placa_veiculo"
                    value="<?php echo htmlspecialchars($config['placa_veiculo'] ?? ''); ?>"
                    pattern="[A-Za-z]{3}[0-9][A-Za-z0-9][0-9]{2}"
                    title="Formato de placa: ABC1D23 ou ABC1234" required>

                <button type="submit" class="btn primary">
                    <i class="fas fa-save"></i> Salvar Configurações
                </button>
            </form>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <!-- Scripts específicos de configurações -->
</body>

</html>