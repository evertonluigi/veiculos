<?php
session_start();
if(isset($_SESSION['user_id'])) {
    // Redirecionar com base no nível do usuário
    if($_SESSION['user_nivel'] == 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: dashboard_funcionario.php");
    }
    exit;
}

// Verificar credenciais
if($_SERVER["REQUEST_METHOD"] == "POST") {
    include_once 'config/database.php';
    include_once 'model/usuario.php';

    $database = new Database();
    $db = $database->getConnection();

    $usuario = new Usuario($db);
    $usuario->login = $_POST['login'];
    $usuario->senha = $_POST['senha'];

    if($usuario->autenticar()) {
        $_SESSION['user_id'] = $usuario->id;
        $_SESSION['user_nome'] = $usuario->nome;
        $_SESSION['user_nivel'] = $usuario->nivel;

        // Redirecionar com base no nível do usuário
        if($_SESSION['user_nivel'] == 'admin') {
            header("Location: dashboard.php");
        } else {
            header("Location: dashboard_funcionario.php");
        }
        exit;
    } else {
        $login_error = "Credenciais inválidas!";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            max-width: 400px;
            width: 100%;
        }
        .logo-container {
            text-align: center;
            margin-bottom: 30px;
        }
        .logo {
            max-width: 300px;
            height: auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
        }
        .card-header {
            background-color: #fff;
            border-bottom: none;
            padding: 25px 25px 0 25px;
        }
        .card-body {
            padding: 25px;
        }
        .btn-primary {
            background-color: #0d6efd;
            border-color: #0d6efd;
            padding: 10px;
            font-weight: 500;
        }
        .form-control {
            padding: 10px;
            border-radius: 5px;
        }
        .form-control:focus {
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            border-color: #86b7fe;
        }
        .system-title {
            font-size: 24px;
            font-weight: 600;
            color: #333;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="logo-container">
            <!-- Substitua pela sua logo real -->
            <img src="https://netcentersp.com.br/wp-content/uploads/2024/08/Logo-Final_PSD-1024x307-1.png" alt="Logo da Empresa" class="logo" onerror="this.src='https://via.placeholder.com/150x80?text=LOGO';this.onerror='';">
        </div>
        
        <div class="card">
            <div class="card-header text-center">
                <h4 class="system-title">Controle de Veículos</h4>
            </div>
            <div class="card-body">
                <?php if(isset($login_error)): ?>
                    <div class="alert alert-danger"><?php echo $login_error; ?></div>
                <?php endif; ?>

                <form method="post" action="">
                    <div class="mb-3">
                        <label for="login" class="form-label">Usuário</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-user"></i></span>
                            <input type="text" class="form-control" id="login" name="login" required placeholder="Digite seu usuário">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="senha" class="form-label">Senha</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-lock"></i></span>
                            <input type="password" class="form-control" id="senha" name="senha" required placeholder="Digite sua senha">
                        </div>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-sign-in-alt me-2"></i>Entrar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
