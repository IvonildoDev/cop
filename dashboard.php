<?php
// Start with PHP code, includes, configurations, etc.
require_once 'config.php';

// Authentication check
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Verificar se o usuário é administrador
$is_admin = ($_SESSION['user_nivel'] ?? '') === 'admin';

// Database queries and data preparation
try {
    // Get any data you need from the database
    $stmt = $pdo->query("SELECT * FROM operacoes ORDER BY inicio_operacao DESC LIMIT 5");
    $recent_operations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get statistics or other dashboard data
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM operacoes");
    $total_operations = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Contagem de usuários (apenas para administradores)
    if ($is_admin) {
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
        $total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Buscar usuários recentes
        $stmt = $pdo->query("SELECT * FROM usuarios ORDER BY created_at DESC LIMIT 5");
        $recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    $error_message = "Erro ao carregar dados: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Controle OP</title>
    <link rel="stylesheet" href="css/styles.css">
    <link rel="stylesheet" href="css/sidebar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Estilos específicos para o dashboard */
        .dashboard-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 0;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
        }

        .card-header i {
            font-size: 20px;
            margin-right: 10px;
        }

        .card-header i.operations {
            color: #007bff;
        }

        .card-header i.users {
            color: #28a745;
        }

        .card-header i.reports {
            color: #fd7e14;
        }

        .card-header h3 {
            margin: 0;
            font-size: 18px;
        }

        .card-body {
            padding: 20px;
            text-align: center;
        }

        .card-body .number {
            font-size: 36px;
            font-weight: bold;
            color: #343a40;
            margin: 0;
        }

        .card-body .label {
            color: #6c757d;
            margin-top: 5px;
        }

        .card-footer {
            padding: 10px 20px;
            background-color: #f8f9fa;
            border-top: 1px solid #e9ecef;
            text-align: right;
        }

        .card-footer a {
            color: #007bff;
            text-decoration: none;
            font-size: 14px;
        }

        .dashboard-recent {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 30px;
        }

        .dashboard-recent h2 {
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
            color: #343a40;
        }

        .dashboard-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }

        .welcome-banner {
            background-color: #fff;
            padding: 20px;
            margin-bottom: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-message h2 {
            margin: 0 0 10px 0;
            color: #343a40;
        }

        .welcome-message p {
            color: #6c757d;
            margin: 0;
        }

        .user-profile {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            background-color: #007bff;
            color: #fff;
            font-size: 24px;
            font-weight: bold;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }

        .user-details {
            text-align: right;
        }

        .user-name {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 5px;
            color: #343a40;
        }

        .user-role {
            color: #6c757d;
            font-size: 14px;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-admin {
            background-color: #d4f7e6;
            color: #0e9b67;
        }

        .badge-operator {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        @media (max-width: 768px) {
            .welcome-banner {
                flex-direction: column;
                text-align: center;
            }

            .user-profile {
                margin-top: 20px;
                flex-direction: column;
            }

            .user-avatar {
                margin-right: 0;
                margin-bottom: 10px;
            }

            .user-details {
                text-align: center;
            }

            .dashboard-actions {
                flex-direction: column;
            }
        }
    </style>
</head>

<body>
    <?php include 'includes/sidebar.php'; ?>

    <div class="main-content">
        <?php include 'includes/header.php'; ?>

        <div class="welcome-banner">
            <div class="welcome-message">
                <h2>Bem-vindo ao Painel de Controle</h2>
                <p>Este é o seu portal para gerenciar todas as operações do sistema.</p>
            </div>
            <div class="user-profile">
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['user_nome'] ?? 'U', 0, 1)); ?>
                </div>
                <div class="user-details">
                    <div class="user-name"><?php echo htmlspecialchars($_SESSION['user_nome'] ?? 'Usuário'); ?></div>
                    <div class="user-role">
                        <?php if ($is_admin): ?>
                            <span class="badge badge-admin">Administrador</span>
                        <?php else: ?>
                            <span class="badge badge-operator">Operador</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($error_message)): ?>
            <div class="alert error"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-tasks operations"></i>
                    <h3>Operações</h3>
                </div>
                <div class="card-body">
                    <p class="number"><?php echo $total_operations ?? 0; ?></p>
                    <p class="label">Total de Operações</p>
                </div>
                <div class="card-footer">
                    <a href="operacao.php">Ver todas</a>
                </div>
            </div>

            <?php if ($is_admin): ?>
                <!-- Card de usuários (apenas para administradores) -->
                <div class="card">
                    <div class="card-header">
                        <i class="fas fa-users users"></i>
                        <h3>Usuários</h3>
                    </div>
                    <div class="card-body">
                        <p class="number"><?php echo $total_users ?? 0; ?></p>
                        <p class="label">Total de Usuários</p>
                    </div>
                    <div class="card-footer">
                        <a href="usuarios.php">Gerenciar Usuários</a>
                    </div>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar reports"></i>
                    <h3>Relatórios</h3>
                </div>
                <div class="card-body">
                    <p class="number"><i class="fas fa-file-alt"></i></p>
                    <p class="label">Gerar Relatórios</p>
                </div>
                <div class="card-footer">
                    <a href="relatorio.php">Acessar</a>
                </div>
            </div>
        </div>

        <!-- Mostrar histórico de operações para todos usuários -->
        <div class="dashboard-recent">
            <h2>Operações Recentes</h2>
            <?php if (!empty($recent_operations)): ?>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Operador</th>
                            <th>Cidade</th>
                            <th>Tipo</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_operations as $op): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($op['inicio_operacao'])); ?></td>
                                <td><?php echo htmlspecialchars($op['nome_operador']); ?></td>
                                <td><?php echo htmlspecialchars($op['nome_cidade']); ?></td>
                                <td><?php echo htmlspecialchars($op['tipo_operacao']); ?></td>
                                <td>
                                    <a href="view_operacao.php?id=<?php echo $op['id']; ?>" class="btn small">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>Nenhuma operação registrada.</p>
            <?php endif; ?>
        </div>

        <?php if ($is_admin): ?>
            <!-- Mostrar usuários recentes apenas para administradores -->
            <div class="dashboard-recent">
                <h2>Usuários Recentes</h2>
                <?php if (!empty($recent_users)): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Matrícula</th>
                                <th>Nível de Acesso</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
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
                                            <span class="badge badge-admin">Ativo</span>
                                        <?php else: ?>
                                            <span class="badge badge-operator">Inativo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="editar_usuario.php?id=<?php echo $user['id']; ?>" class="btn small">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>Nenhum usuário registrado.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="dashboard-actions">
            <?php if ($is_admin): ?>
                <a href="novo_usuario.php" class="btn primary">
                    <i class="fas fa-user-plus"></i> Novo Usuário
                </a>
            <?php endif; ?>

            <a href="relatorio.php" class="btn secondary">
                <i class="fas fa-chart-bar"></i> Gerar Relatório
            </a>

            <a href="configuracoes.php" class="btn secondary">
                <i class="fas fa-cog"></i> Configurações
            </a>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    <script src="js/sidebar.js"></script>
</body>

</html>