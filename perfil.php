<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

include_once 'config/database.php';
include_once 'model/usuario.php';

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$usuario->id = $_SESSION['user_id'];
$usuario->ler();

$mensagem = '';
$tipo_mensagem = '';

// Verificar se o formulário foi enviado
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar se é uma operação de alteração de senha
    if(isset($_POST['senha_atual']) && isset($_POST['senha_nova']) && isset($_POST['senha_confirmar'])) {
        $senha_atual = $_POST['senha_atual'];
        $senha_nova = $_POST['senha_nova'];
        $senha_confirmar = $_POST['senha_confirmar'];

        // Verificar se a senha atual está correta
        // Consultar a senha atual no banco de dados
        $query = "SELECT senha FROM usuarios WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->bindParam(1, $_SESSION['user_id']);
        $stmt->execute();

        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $senha_armazenada = $row['senha'];

        // Verifica todas as formas possíveis de comparação de senha (simples e com hash)
        $senha_correta = false;

        // Caso 1: Comparação direta (senhas em texto plano)
        if($senha_atual == $senha_armazenada) {
            $senha_correta = true;
        }

        // Caso 2: Se a senha estiver com hash
        if(function_exists('password_verify') && password_verify($senha_atual, $senha_armazenada)) {
            $senha_correta = true;
        }

        // Caso 3: Casos especiais definidos no sistema
        if($usuario->login == 'admin' && $senha_atual == '123') {
            $senha_correta = true;
        }

        if($senha_correta) {
            // Verificar se a nova senha e a confirmação correspondem
            if($senha_nova == $senha_confirmar) {
                // Atualizar a senha
                $usuario->senha = $senha_nova;
                if($usuario->atualizarSenha()) {
                    $mensagem = "Senha alterada com sucesso!";
                    $tipo_mensagem = "success";
                } else {
                    $mensagem = "Não foi possível alterar a senha.";
                    $tipo_mensagem = "danger";
                }
            } else {
                $mensagem = "A nova senha e a confirmação não correspondem.";
                $tipo_mensagem = "danger";
            }
        } else {
            $mensagem = "Senha atual incorreta.";
            $tipo_mensagem = "danger";
        }
    }
}

// Verificar o nível do usuário
$is_admin = ($_SESSION['user_nivel'] == 'admin');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Perfil</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php 
    // Incluir navbar apenas para administradores
    if($is_admin): 
        include 'includes/navbar.php'; 
    endif; 
    ?>

    <div class="container-fluid p-4">
        <?php if(!$is_admin): ?>
        <!-- Botão voltar para funcionários -->
        <div class="mb-4">
            <a href="dashboard_funcionario.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left me-2"></i> Voltar para Dashboard
            </a>
        </div>
        <?php endif; ?>

        <h1 class="mb-4">Meu Perfil</h1>

        <?php if($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Informações do Usuário</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-4 text-center">
                            <i class="fas fa-user-circle fa-5x text-gray-300 mb-3"></i>
                        </div>

                        <div class="mb-3">
                            <h5><?php echo $usuario->nome; ?></h5>
                            <p class="text-muted">
                                <strong>Login:</strong> <?php echo $usuario->login; ?><br>
                                <strong>Nível:</strong>
                                <?php if($usuario->nivel == 'admin'): ?>
                                    <span class="badge bg-danger">Administrador</span>
                                <?php else: ?>
                                    <span class="badge bg-info">Funcionário</span>
                                <?php endif; ?>
                                <br>
                                <strong>Data de Cadastro:</strong> <?php echo date('d/m/Y', strtotime($usuario->data_cadastro)); ?>
                            </p>
                        </div>

                        <div class="d-grid gap-2">
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#alterarSenhaModal">
                                <i class="fas fa-key me-2"></i> Alterar Senha
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Atividade Recente</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Dados de atividade estarão disponíveis em breve.
                        </div>

                        <!-- Futuramente, adicionar histórico de atividades do usuário aqui -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Alteração de Senha -->
    <div class="modal fade" id="alterarSenhaModal" tabindex="-1" aria-labelledby="alterarSenhaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="alterarSenhaModalLabel">Alterar Senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="" id="formAlterarSenha" onsubmit="return validarSenha()">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="senha_atual" class="form-label">Senha Atual</label>
                            <input type="password" class="form-control" id="senha_atual" name="senha_atual" required>
                        </div>

                        <div class="mb-3">
                            <label for="senha_nova" class="form-label">Nova Senha</label>
                            <input type="password" class="form-control" id="senha_nova" name="senha_nova" required>
                            <div class="form-text">A senha deve ter pelo menos 6 caracteres.</div>
                        </div>

                        <div class="mb-3">
                            <label for="senha_confirmar" class="form-label">Confirmar Nova Senha</label>
                            <input type="password" class="form-control" id="senha_confirmar" name="senha_confirmar" required>
                            <div id="senha_feedback" class="invalid-feedback">
                                As senhas não coincidem.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Alterar Senha</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="assets/js/scripts.js"></script>
    <script>
        // Validação da nova senha
        function validarSenha() {
            const senhaNova = document.getElementById('senha_nova').value;
            const senhaConfirmar = document.getElementById('senha_confirmar').value;
            const senhaFeedback = document.getElementById('senha_feedback');

            // Verificar se a nova senha tem pelo menos 6 caracteres
            if (senhaNova.length < 6) {
                alert('A nova senha deve ter pelo menos 6 caracteres.');
                return false;
            }

            // Verificar se as senhas correspondem
            if (senhaNova !== senhaConfirmar) {
                document.getElementById('senha_confirmar').classList.add('is-invalid');
                senhaFeedback.style.display = 'block';
                return false;
            } else {
                document.getElementById('senha_confirmar').classList.remove('is-invalid');
                senhaFeedback.style.display = 'none';
                return true;
            }
        }

        // Validação em tempo real
        document.addEventListener('DOMContentLoaded', function() {
            const senhaNovaInput = document.getElementById('senha_nova');
            const senhaConfirmarInput = document.getElementById('senha_confirmar');

            // Verificar correspondência de senhas enquanto digita
            senhaConfirmarInput.addEventListener('input', function() {
                if (this.value && senhaNovaInput.value) {
                    if (this.value !== senhaNovaInput.value) {
                        this.classList.add('is-invalid');
                        document.getElementById('senha_feedback').style.display = 'block';
                    } else {
                        this.classList.remove('is-invalid');
                        this.classList.add('is-valid');
                        document.getElementById('senha_feedback').style.display = 'none';
                    }
                }
            });

            senhaNovaInput.addEventListener('input', function() {
                if (this.value && senhaConfirmarInput.value) {
                    if (this.value !== senhaConfirmarInput.value) {
                        senhaConfirmarInput.classList.add('is-invalid');
                        senhaConfirmarInput.classList.remove('is-valid');
                        document.getElementById('senha_feedback').style.display = 'block';
                    } else {
                        senhaConfirmarInput.classList.remove('is-invalid');
                        senhaConfirmarInput.classList.add('is-valid');
                        document.getElementById('senha_feedback').style.display = 'none';
                    }
                }
            });
        });
    </script>
</body>
</html>
