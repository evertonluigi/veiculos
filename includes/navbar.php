<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="/teste2/dashboard.php">
            <img src="https://netcentersp.com.br/wp-content/uploads/2024/03/Logo-Final_PSD-1024x307-1.png" alt="Logo" height="30" class="d-inline-block align-text-top me-2">
            Controle de Veículos
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Separador para manter o menu fixo no centro -->
        <div class="d-none d-lg-block me-auto" style="width: 50px;"></div>
        
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?php echo (basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>" href="/teste2/dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                
                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/veiculos/') !== false) ? 'active' : ''; ?>" href="/teste2/veiculos/cadastro.php">
                        <i class="fas fa-car"></i> Veículos
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/usuarios/') !== false) ? 'active' : ''; ?>" href="/teste2/usuarios/cadastro.php">
                        <i class="fas fa-users"></i> Usuários
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/abastecimentos/') !== false) ? 'active' : ''; ?>" href="/teste2/abastecimentos/cadastro.php">
                        <i class="fas fa-gas-pump"></i> Abastecimentos
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo (strpos($_SERVER['PHP_SELF'], '/manutencoes/') !== false) ? 'active' : ''; ?>" href="/teste2/manutencoes/cadastro.php">
                        <i class="fas fa-tools"></i> Manutenções
                    </a>
                </li>
                
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo (strpos($_SERVER['PHP_SELF'], '/relatorios/') !== false) ? 'active' : ''; ?>" href="#" id="relatoriosDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-file-alt"></i> Relatórios
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="relatoriosDropdown">
                        <li><a class="dropdown-item" href="/teste2/relatorios/veiculos.php">Veículos</a></li>
                        <li><a class="dropdown-item" href="/teste2/relatorios/abastecimentos.php">Abastecimentos</a></li>
                        <li><a class="dropdown-item" href="/teste2/relatorios/manutencoes.php">Manutenções</a></li>
                        <li><a class="dropdown-item" href="/teste2/relatorios/usuarios.php">Usuários</a></li>
			<li><a class="dropdown-item" href="/teste2/relatorios/uso_veiculos.php">Uso Veiculos</a></li>
                    </ul>
                </li>
            </ul>
        </div>
        
        <!-- Separador para manter o menu fixo no centro -->
        <div class="d-none d-lg-block me-auto" style="width: 50px;"></div>
        
        <ul class="navbar-nav">
            <li class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-user"></i> <?php echo $_SESSION['user_nome']; ?>
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                    <li><a class="dropdown-item" href="/teste2/perfil.php">Perfil</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><a class="dropdown-item" href="/teste2/logout.php">Sair</a></li>
                </ul>
            </li>
        </ul>
    </div>
</nav>
