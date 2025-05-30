<?php
// filepath: c:\xampp\htdocs\cop\novo_usuario.php
session_start();
require_once 'config.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Processar formulário quando enviado
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = htmlspecialchars(trim($_POST['nome'] ?? ''));
    $matricula = htmlspecialchars(trim($_POST['matricula'] ?? ''));
    $nivel_acesso = htmlspecialchars(trim($_POST['nivel_acesso'] ?? 'operador'));
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    // Validação básica
    if (empty($nome) || empty($matricula)) {
        $error = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Verificar se a matrícula já está em uso
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE matricula = ?");
            $stmt->execute([$matricula]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Esta matrícula já está em uso por outro usuário.";
            } else {
                // Inserir usuário
                $stmt = $pdo->prepare("INSERT INTO usuarios (nome, matricula, nivel_acesso, ativo) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nome, $matricula, $nivel_acesso, $ativo]);

                $success = "Usuário criado com sucesso!";
                // Limpar formulário
                $nome = $matricula = '';
                $nivel_acesso = 'operador';
                $ativo = 1;
            }
        } catch (PDOException $e) {
            $error = "Erro ao criar usuário: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Novo Usuário - Controle OP</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <h1><i class="fas fa-user-plus"></i> Novo Usuário</h1>

        <?php if ($success): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h2>Dados do Usuário</h2>
            </div>
            <div class="card-body">
                <form method="post" action="">
                    <div class="form-group">
                        <label for="nome">Nome: <span class="required">*</span></label>
                        <input type="text" id="nome" name="nome" value="<?php echo $nome ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="matricula">Matrícula: <span class="required">*</span></label>
                        <input type="text" id="matricula" name="matricula" value="<?php echo $matricula ?? ''; ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="nivel_acesso">Nível de Acesso:</label>
                        <select id="nivel_acesso" name="nivel_acesso">
                            <option value="operador" <?php echo ($nivel_acesso ?? '') == 'operador' ? 'selected' : ''; ?>>Operador</option>
                            <option value="supervisor" <?php echo ($nivel_acesso ?? '') == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            <option value="admin" <?php echo ($nivel_acesso ?? '') == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="ativo" <?php echo (!isset($ativo) || $ativo) ? 'checked' : ''; ?>>
                            Usuário ativo
                        </label>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn primary">
                            <i class="fas fa-save"></i> Salvar Usuário
                        </button>
                        <a href="usuarios.php" class="btn secondary">
                            <i class="fas fa-arrow-left"></i> Voltar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="js/sidebar.js"></script>
</body>

</html>