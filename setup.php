<?php
require_once 'config.php';

// Check if tables already exist
function tableExists($pdo, $table) {
    try {
        $result = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        return $result !== false;
    } catch (Exception $e) {
        return false;
    }
}

// Create tables if they don't exist
try {
    // Create configuracoes table
    if (!tableExists($pdo, 'configuracoes')) {
        $pdo->exec("CREATE TABLE configuracoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome_operador_principal VARCHAR(100) NOT NULL,
            nome_auxiliar VARCHAR(100) NOT NULL,
            nome_unidade VARCHAR(100) NOT NULL,
            placa_veiculo VARCHAR(20) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Insert default configuration
        $pdo->exec("INSERT INTO configuracoes (nome_operador_principal, nome_auxiliar, nome_unidade, placa_veiculo) 
                   VALUES ('Nome do Operador', 'Nome do Auxiliar', 'Unidade Padrão', 'ABC-1234')");
    }
    
    // Create operations table
    if (!tableExists($pdo, 'operacoes')) {
        $pdo->exec("CREATE TABLE operacoes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            inicio_operacao DATETIME NOT NULL,
            km_inicial FLOAT NOT NULL,
            nome_op_aux VARCHAR(100) NOT NULL,
            tipo_operacao VARCHAR(100) NOT NULL,
            nome_cidade VARCHAR(100) NOT NULL,
            nome_poco_serv VARCHAR(100) NOT NULL,
            nome_operador VARCHAR(100) NOT NULL,
            volume_bbl FLOAT,
            temperatura FLOAT,
            pressao VARCHAR(50),
            descricao_atividades TEXT,
            fim_operacao DATETIME,
            km_final FLOAT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }
    
    $success = true;
    $message = "Banco de dados configurado com sucesso!";
    
} catch (PDOException $e) {
    $success = false;
    $message = "Erro ao configurar banco de dados: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalação - Controle OP</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .setup-container {
            max-width: 700px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .setup-container h1 {
            text-align: center;
            margin-bottom: 30px;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .message.success {
            background-color: #d4edda;
            color: #155724;
        }
        .message.error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .setup-step {
            margin: 20px 0;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .setup-step h3 {
            margin-top: 0;
        }
    </style>
</head>
<body>
    <div class="setup-container">
        <h1>Instalação do Sistema de Controle OP</h1>
        
        <?php if (isset($success) && $success): ?>
            <div class="message success">
                <?php echo $message; ?>
                <p>Você será redirecionado para a página inicial em 5 segundos.</p>
            </div>
            <script>
                setTimeout(function() {
                    window.location.href = 'index.php';
                }, 5000);
            </script>
        <?php else: ?>
            <div class="message error">
                <?php echo $message ?? 'Ocorreu um erro desconhecido.'; ?>
            </div>
            <a href="index.php" class="btn">Voltar para a página inicial</a>
        <?php endif; ?>
    </div>
</body>
</html>