<?php
// filepath: c:\xampp\htdocs\cop\editar_usuario.php
session_start();
require_once 'config.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Verificar se foi fornecido um ID válido
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: usuarios.php');
    exit;
}

$user_id = $_GET['id'];

// Processar formulário quando enviado
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
    $matricula = filter_input(INPUT_POST, 'matricula', FILTER_SANITIZE_STRING);
    $nivel_acesso = filter_input(INPUT_POST, 'nivel_acesso', FILTER_SANITIZE_STRING);
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    // Validação básica
    if (empty($nome) || empty($matricula)) {
        $error = "Por favor, preencha todos os campos obrigatórios.";
    } else {
        try {
            // Verificar se a matrícula já está em uso por outro usuário
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM usuarios WHERE matricula = ? AND id != ?");
            $stmt->execute([$matricula, $user_id]);

            if ($stmt->fetchColumn() > 0) {
                $error = "Esta matrícula já está em uso por outro usuário.";
            } else {
                // Atualizar usuário
                $stmt = $pdo->prepare("UPDATE usuarios SET nome = ?, matricula = ?, nivel_acesso = ?, ativo = ? WHERE id = ?");
                $stmt->execute([$nome, $matricula, $nivel_acesso, $ativo, $user_id]);

                $success = "Usuário atualizado com sucesso!";
            }
        } catch (PDOException $e) {
            $error = "Erro ao atualizar usuário: " . $e->getMessage();
        }
    }
}

// Buscar dados do usuário
try {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        header('Location: usuarios.php');
        exit;
    }
} catch (PDOException $e) {
    $error = "Erro ao buscar dados do usuário: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Usuário - Controle OP</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <h1><i class="fas fa-user-edit"></i> Editar Usuário</h1>

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
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="matricula">Matrícula: <span class="required">*</span></label>
                        <input type="text" id="matricula" name="matricula" value="<?php echo htmlspecialchars($usuario['matricula']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="nivel_acesso">Nível de Acesso:</label>
                        <select id="nivel_acesso" name="nivel_acesso">
                            <option value="operador" <?php echo $usuario['nivel_acesso'] == 'operador' ? 'selected' : ''; ?>>Operador</option>
                            <option value="supervisor" <?php echo $usuario['nivel_acesso'] == 'supervisor' ? 'selected' : ''; ?>>Supervisor</option>
                            <option value="admin" <?php echo $usuario['nivel_acesso'] == 'admin' ? 'selected' : ''; ?>>Administrador</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="ativo" <?php echo $usuario['ativo'] ? 'checked' : ''; ?>>
                            Usuário ativo
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Criado em:</label>
                        <div class="form-control-static">
                            <?php echo date('d/m/Y H:i', strtotime($usuario['created_at'])); ?>
                        </div>
                    </div>

                    <?php if ($usuario['ultimo_acesso']): ?>
                        <div class="form-group">
                            <label>Último acesso:</label>
                            <div class="form-control-static">
                                <?php echo date('d/m/Y H:i', strtotime($usuario['ultimo_acesso'])); ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="form-actions">
                        <button type="submit" class="btn primary">
                            <i class="fas fa-save"></i> Salvar Alterações
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