<?php
// Determinar qual página está ativa
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <h2>COP</h2>
        <i class="fas fa-bars menu-toggle" id="sidebar-toggle-icon"></i>
    </div>
    <nav class="menu">
        <ul class="sidebar-menu">
            <li class="<?php echo $current_page == 'index.php' ? 'active' : ''; ?>">
                <a href="index.php">
                    <i class="fas fa-home"></i>
                    <span>Início</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'operacao.php' ? 'active' : ''; ?>">
                <a href="operacao.php">
                    <i class="fas fa-cogs"></i>
                    <span>Operações</span>
                </a>
            </li>
            <!-- Novas páginas adicionadas -->
            <li class="<?php echo $current_page == 'mobilizacao.php' ? 'active' : ''; ?>">
                <a href="mobilizacao.php">
                    <i class="fas fa-truck-moving"></i>
                    <span>Mobilização</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'desmobilizacao.php' ? 'active' : ''; ?>">
                <a href="desmobilizacao.php">
                    <i class="fas fa-truck-loading"></i>
                    <span>Desmobilização</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'refeicao.php' ? 'active' : ''; ?>">
                <a href="refeicao.php">
                    <i class="fas fa-utensils"></i>
                    <span>Refeição</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'deslocamento.php' ? 'active' : ''; ?>">
                <a href="deslocamento.php">
                    <i class="fas fa-route"></i>
                    <span>Deslocamento</span>
                </a>
            </li>
            <!-- Fim das novas páginas -->
            <li class="<?php echo $current_page == 'abastecimento.php' ? 'active' : ''; ?>">
                <a href="abastecimento.php">
                    <i class="fas fa-gas-pump"></i>
                    <span>Abastecimento</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'relatorio.php' ? 'active' : ''; ?>">
                <a href="relatorio.php">
                    <i class="fas fa-file-alt"></i>
                    <span>Relatórios</span>
                </a>
            </li>
            <li class="<?php echo $current_page == 'configuracoes.php' ? 'active' : ''; ?>">
                <a href="configuracoes.php">
                    <i class="fas fa-cog"></i>
                    <span>Configurações</span>
                </a>
            </li>
        </ul>
    </nav>
</div>

<button class="sidebar-toggle" id="sidebar-toggle">
    <i class="fas fa-bars"></i>
</button>

<div class="overlay" id="overlay"></div>