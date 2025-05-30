<?php
session_start();
require_once 'config.php';

// Se o usuário já estiver logado, redirecionar para o painel principal
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

// Processar o formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $matricula = trim($_POST['matricula'] ?? '');

    // Validação básica
    if (empty($nome) || empty($matricula)) {
        $error = 'Por favor, preencha todos os campos.';
    } else {
        // Verifica se o usuário existe
        try {
            $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE nome = ? AND matricula = ? LIMIT 1");
            $stmt->execute([$nome, $matricula]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Login bem sucedido
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_nome'] = $user['nome'];
                $_SESSION['user_nivel'] = $user['nivel_acesso'];

                // Registrar login
                $stmt = $pdo->prepare("INSERT INTO logs_acesso (usuario_id, acao, ip) VALUES (?, 'login', ?)");
                $stmt->execute([$user['id'], $_SERVER['REMOTE_ADDR']]);

                // Redirecionar para o painel
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Nome ou matrícula inválidos.';

                // Verifica se a tabela de usuários existe ou está vazia
                $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
                $count = $stmt->fetchColumn();

                if ($count == 0) {
                    // Se não existirem usuários, cria um usuário padrão 
                    // e redireciona para a configuração inicial
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, matricula, nivel_acesso) VALUES (?, ?, 'admin')");
                    $stmt->execute(['Administrador', '123456']);

                    $error = 'Sistema inicializado. Use Nome: Administrador e Matrícula: 123456 para o primeiro acesso.';
                }
            }
        } catch (PDOException $e) {
            // Verifica se é um erro de tabela não existente
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                // Criar tabela de usuários
                try {
                    $pdo->exec("CREATE TABLE IF NOT EXISTS usuarios (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        nome VARCHAR(100) NOT NULL,
                        matricula VARCHAR(20) NOT NULL,
                        nivel_acesso ENUM('admin', 'operador', 'supervisor') NOT NULL DEFAULT 'operador',
                        ativo BOOLEAN NOT NULL DEFAULT TRUE,
                        ultimo_acesso DATETIME,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");

                    $pdo->exec("CREATE TABLE IF NOT EXISTS logs_acesso (
                        id INT AUTO_INCREMENT PRIMARY KEY,
                        usuario_id INT,
                        acao VARCHAR(50) NOT NULL,
                        ip VARCHAR(45) NOT NULL,
                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                        FOREIGN KEY (usuario_id) REFERENCES usuarios(id)
                    )");

                    // Inserir usuário padrão
                    $stmt = $pdo->prepare("INSERT INTO usuarios (nome, matricula, nivel_acesso) VALUES (?, ?, 'admin')");
                    $stmt->execute(['Administrador', '123456']);

                    $error = 'Sistema inicializado. Use Nome: Administrador e Matrícula: 123456 para o primeiro acesso.';
                } catch (PDOException $e2) {
                    $error = 'Erro ao inicializar o sistema: ' . $e2->getMessage();
                }
            } else {
                $error = 'Erro ao processar login: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Controle OP</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary-color: #007bff;
            --primary-dark: #0056b3;
            --secondary-color: #6c757d;
            --success-color: #28a745;
            --danger-color: #dc3545;
            --light-color: #f8f9fa;
            --dark-color: #343a40;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .login-container {
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            transition: transform 0.3s;
        }

        .login-container:hover {
            transform: translateY(-5px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header .logo {
            font-size: 60px;
            color: var(--primary-color);
            margin-bottom: 20px;
        }

        .login-header h1 {
            font-size: 28px;
            color: var(--dark-color);
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--secondary-color);
            font-size: 16px;
        }

        .login-form .form-group {
            margin-bottom: 20px;
        }

        .login-form label {
            display: block;
            margin-bottom: 8px;
            color: var(--dark-color);
            font-weight: 500;
        }

        .login-form .input-group {
            position: relative;
        }

        .login-form .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--secondary-color);
        }

        .login-form input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .login-form input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
            outline: none;
        }

        .login-form button {
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.3s;
            margin-top: 10px;
        }

        .login-form button:hover {
            background-color: var(--primary-dark);
        }

        .error-message {
            background-color: rgba(220, 53, 69, 0.1);
            color: var(--danger-color);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid var(--danger-color);
        }

        .info-message {
            background-color: rgba(0, 123, 255, 0.1);
            color: var(--primary-color);
            padding: 10px 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
            border-left: 4px solid var(--primary-color);
        }

        .login-footer {
            text-align: center;
            margin-top: 30px;
            color: var(--secondary-color);
            font-size: 14px;
        }

        .login-footer a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .login-footer a:hover {
            text-decoration: underline;
        }

        /* Animação para mensagem de erro */
        @keyframes shake {

            0%,
            100% {
                transform: translateX(0);
            }

            10%,
            30%,
            50%,
            70%,
            90% {
                transform: translateX(-5px);
            }

            20%,
            40%,
            60%,
            80% {
                transform: translateX(5px);
            }
        }

        .error-shake {
            animation: shake 0.6s;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 20px;
                max-width: 320px;
            }
        }
    </style>
</head>

<body>
    <div class="login-container" id="loginContainer">
        <div class="login-header">
            <div class="logo">
                <i class="fas fa-cogs"></i>
            </div>
            <h1>Sistema de Controle Operacional</h1>
            <p>Faça login para acessar o sistema</p>
        </div>

        <?php if ($error): ?>
            <div class="error-message" id="errorMessage">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form class="login-form" method="post" action="">
            <div class="form-group">
                <label for="nome">Nome</label>
                <div class="input-group">
                    <i class="fas fa-user input-icon"></i>
                    <input type="text" id="nome" name="nome" placeholder="Digite seu nome" autofocus required>
                </div>
            </div>

            <div class="form-group">
                <label for="matricula">Matrícula</label>
                <div class="input-group">
                    <i class="fas fa-id-card input-icon"></i>
                    <input type="text" id="matricula" name="matricula" placeholder="Digite sua matrícula" required>
                </div>
            </div>

            <button type="submit">
                <i class="fas fa-sign-in-alt"></i> Entrar
            </button>
        </form>

        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> Controle OP - Todos os direitos reservados</p>
        </div>
    </div>

    <script>
        // Animação para mensagem de erro
        document.addEventListener('DOMContentLoaded', function() {
            const errorMessage = document.getElementById('errorMessage');
            if (errorMessage) {
                errorMessage.classList.add('error-shake');

                // Auto-hide message after 10 seconds
                setTimeout(function() {
                    errorMessage.style.opacity = '0';
                    errorMessage.style.transition = 'opacity 0.5s';

                    // Remove from DOM after fade out
                    setTimeout(function() {
                        errorMessage.style.display = 'none';
                    }, 500);
                }, 10000);
            }
        });
    </script>
</body>

</html>