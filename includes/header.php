<?php
// filepath: c:\xampp\htdocs\cop\includes\header.php
?>
<header class="main-header">
    <div class="header-left">
        <button id="sidebar-toggle" class="sidebar-toggle">
            <i class="fas fa-bars" id="sidebar-toggle-icon"></i>
        </button>
        <div class="logo">
            <a href="dashboard.php">
                <i class="fas fa-cogs"></i>
                <span>Controle OP</span>
            </a>
        </div>
    </div>
    <div class="header-right">
        <div class="user-info">
            <span><?php echo $_SESSION['user_nome'] ?? 'Usuário'; ?></span>
            <a href="logout.php" class="logout-btn" title="Sair">
                <i class="fas fa-sign-out-alt"></i>
            </a>
        </div>
    </div>
</header>