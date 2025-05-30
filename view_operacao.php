<?php

// Incluir a conexão com o banco de dados
require_once 'config.php';

// Check if ID is set in URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$id = $_GET['id'];

// Get operation details from database
try {
    $stmt = $pdo->prepare("SELECT * FROM operacoes WHERE id = ?");
    $stmt->execute([$id]);
    $operacao = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$operacao) {
        // Operation not found
        header('Location: index.php');
        exit;
    }
    
} catch (PDOException $e) {
    echo "Erro ao buscar operação: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizar Operação</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-4">
        <h1>Detalhes da Operação #<?php echo htmlspecialchars($operacao['id']); ?></h1>
        
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Operação em <?php echo htmlspecialchars($operacao['nome_cidade']); ?></h5>
                <span class="badge badge-primary"><?php echo htmlspecialchars($operacao['tipo_operacao']); ?></span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Informações Básicas</h6>
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th>Início da Operação:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($operacao['inicio_operacao'])); ?></td>
                            </tr>
                            <tr>
                                <th>Cidade:</th>
                                <td><?php echo htmlspecialchars($operacao['nome_cidade']); ?></td>
                            </tr>
                            <tr>
                                <th>Tipo de Operação:</th>
                                <td><?php echo htmlspecialchars($operacao['tipo_operacao']); ?></td>
                            </tr>
                            <tr>
                                <th>Operador:</th>
                                <td><?php echo htmlspecialchars($operacao['nome_operador']); ?></td>
                            </tr>
                            <tr>
                                <th>Auxiliar de Operação:</th>
                                <td><?php echo htmlspecialchars($operacao['nome_op_aux']); ?></td>
                            </tr>
                            <tr>
                                <th>Poço/Serviço:</th>
                                <td><?php echo htmlspecialchars($operacao['nome_poco_serv']); ?></td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="col-md-6">
                        <h6>Detalhes Técnicos</h6>
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th>KM Inicial:</th>
                                <td><?php echo number_format($operacao['km_inicial'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php if (!empty($operacao['km_final'])): ?>
                            <tr>
                                <th>KM Final:</th>
                                <td><?php echo number_format($operacao['km_final'], 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <th>KM Percorridos:</th>
                                <td><?php echo number_format($operacao['km_final'] - $operacao['km_inicial'], 0, ',', '.'); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Volume (BBL):</th>
                                <td><?php echo number_format($operacao['volume_bbl'], 0, ',', '.'); ?></td>
                            </tr>
                            <tr>
                                <th>Temperatura:</th>
                                <td><?php echo $operacao['temperatura']; ?>°C</td>
                            </tr>
                            <tr>
                                <th>Pressão:</th>
                                <td><?php echo $operacao['pressao']; ?> PSI</td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6>Status da Operação</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <table class="table table-bordered table-sm">
                                    <tr>
                                        <th>Mobilização:</th>
                                        <td>
                                            <span class="badge <?php echo ($operacao['mobilizacao_status'] == 'Concluída') ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo htmlspecialchars($operacao['mobilizacao_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php if (!empty($operacao['mobilizacao_inicio'])): ?>
                                    <tr>
                                        <th>Início da Mobilização:</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($operacao['mobilizacao_inicio'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($operacao['mobilizacao_fim'])): ?>
                                    <tr>
                                        <th>Fim da Mobilização:</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($operacao['mobilizacao_fim'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="table table-bordered table-sm">
                                    <tr>
                                        <th>Desmobilização:</th>
                                        <td>
                                            <span class="badge <?php echo ($operacao['desmobilizacao_status'] == 'Concluída') ? 'badge-success' : 'badge-warning'; ?>">
                                                <?php echo htmlspecialchars($operacao['desmobilizacao_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php if (!empty($operacao['desmobilizacao_inicio'])): ?>
                                    <tr>
                                        <th>Início da Desmobilização:</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($operacao['desmobilizacao_inicio'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                    <?php if (!empty($operacao['desmobilizacao_fim'])): ?>
                                    <tr>
                                        <th>Fim da Desmobilização:</th>
                                        <td><?php echo date('d/m/Y H:i', strtotime($operacao['desmobilizacao_fim'])); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (!empty($operacao['aguardo_inicio'])): ?>
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6>Informações de Aguardo</h6>
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th>Início do Aguardo:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($operacao['aguardo_inicio'])); ?></td>
                            </tr>
                            <?php if (!empty($operacao['aguardo_fim'])): ?>
                            <tr>
                                <th>Fim do Aguardo:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($operacao['aguardo_fim'])); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>Motivo do Aguardo:</th>
                                <td><?php echo nl2br(htmlspecialchars($operacao['aguardo_motivo'] ?? 'Não informado')); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <h6>Cronologia</h6>
                        <table class="table table-bordered table-sm">
                            <tr>
                                <th>Criado em:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($operacao['created_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Última atualização:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($operacao['updated_at'])); ?></td>
                            </tr>
                            <tr>
                                <th>Início da Operação:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($operacao['inicio_operacao'])); ?></td>
                            </tr>
                            <?php if (!empty($operacao['fim_operacao'])): ?>
                            <tr>
                                <th>Fim da Operação:</th>
                                <td><?php echo date('d/m/Y H:i', strtotime($operacao['fim_operacao'])); ?></td>
                            </tr>
                            <tr>
                                <th>Duração Total:</th>
                                <td>
                                    <?php 
                                        $inicio = new DateTime($operacao['inicio_operacao']);
                                        $fim = new DateTime($operacao['fim_operacao']);
                                        $diff = $inicio->diff($fim);
                                        echo $diff->format('%a dias, %h horas e %i minutos');
                                    ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </table>
                    </div>
                </div>
                
                <?php if (!empty($operacao['descricao_atividades'])): ?>
                <div class="row mt-4">
                    <div class="col-md-12">
                        <h6>Descrição das Atividades</h6>
                        <div class="p-3 bg-light border rounded">
                            <?php echo nl2br(htmlspecialchars($operacao['descricao_atividades'])); ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer">
                <a href="index.php" class="btn btn-secondary">Voltar</a>
                <a href="edit_operacao.php?id=<?php echo $operacao['id']; ?>" class="btn btn-primary">Editar</a>
                <a href="#" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">Excluir</a>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir esta operação? Esta ação não pode ser desfeita.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <a href="delete_operacao.php?id=<?php echo $operacao['id']; ?>" class="btn btn-danger">Excluir</a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>