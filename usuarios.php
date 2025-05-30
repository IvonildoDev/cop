<?php
// filepath: c:\xampp\htdocs\cop\usuarios.php
session_start();
require_once 'config.php';

// Verificar se o usuário está logado e é administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] !== 'admin') {
    header('Location: index.php');
    exit;
}

// Processar exclusão de usuário
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ? AND id != ?");
        $stmt->execute([$_GET['delete'], $_SESSION['user_id']]);
        $success = "Usuário excluído com sucesso.";
    } catch (PDOException $e) {
        $error = "Erro ao excluir usuário: " . $e->getMessage();
    }
}

// Processar ativação/desativação de usuário
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    try {
        // Obter status atual
        $stmt = $pdo->prepare("SELECT ativo FROM usuarios WHERE id = ?");
        $stmt->execute([$_GET['toggle']]);
        $current = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($current) {
            $new_status = $current['ativo'] ? 0 : 1;
            $stmt = $pdo->prepare("UPDATE usuarios SET ativo = ? WHERE id = ? AND id != ?");
            $stmt->execute([$new_status, $_GET['toggle'], $_SESSION['user_id']]);
            $success = "Status do usuário alterado com sucesso.";
        }
    } catch (PDOException $e) {
        $error = "Erro ao alterar status do usuário: " . $e->getMessage();
    }
}

// Buscar todos os usuários
try {
    $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY nome");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Erro ao buscar usuários: " . $e->getMessage();
    $usuarios = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Usuários - Controle OP</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <h1><i class="fas fa-users"></i> Gerenciamento de Usuários</h1>

        <?php if (isset($success)): ?>
            <div class="alert success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="actions-bar">
            <a href="novo_usuario.php" class="btn primary">
                <i class="fas fa-user-plus"></i> Novo Usuário
            </a>
        </div>

        <div class="card mt-4">
            <div class="card-header">
                <h2>Lista de Usuários</h2>
            </div>
            <div class="card-body">
                <?php if (empty($usuarios)): ?>
                    <p class="text-muted">Nenhum usuário encontrado.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nome</th>
                                    <th>Matrícula</th>
                                    <th>Nível de Acesso</th>
                                    <th>Status</th>
                                    <th>Último Acesso</th>
                                    <th>Criado em</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usuarios as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo htmlspecialchars($user['nome']); ?></td>
                                        <td><?php echo htmlspecialchars($user['matricula']); ?></td>
                                        <td>
                                            <?php
                                            switch ($user['nivel_acesso']) {
                                                case 'admin':
                                                    echo 'Administrador';
                                                    break;
                                                case 'supervisor':
                                                    echo 'Supervisor';
                                                    break;
                                                default:
                                                    echo 'Operador';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if ($user['ativo']): ?>
                                                <span class="status status-ativo">Ativo</span>
                                            <?php else: ?>
                                                <span class="status status-inativo">Inativo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo $user['ultimo_acesso'] ? date('d/m/Y H:i', strtotime($user['ultimo_acesso'])) : 'Nunca acessou'; ?>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                        <td>
                                            <div class="actions">
                                                <a href="editar_usuario.php?id=<?php echo $user['id']; ?>" class="btn small" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="?toggle=<?php echo $user['id']; ?>" class="btn small <?php echo $user['ativo'] ? 'warning' : 'success'; ?>" title="<?php echo $user['ativo'] ? 'Desativar' : 'Ativar'; ?>">
                                                        <i class="fas <?php echo $user['ativo'] ? 'fa-user-slash' : 'fa-user-check'; ?>"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $user['id']; ?>" class="btn small danger" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="js/sidebar.js"></script>
</body>

</html>