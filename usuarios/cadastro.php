<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include_once '../config/database.php';
include_once '../model/usuario.php';

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);

$mensagem = '';
$tipo_mensagem = '';

// Verificar se o formulário foi enviado
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar se é uma operação de exclusão
    if(isset($_POST['excluir_id']) && !empty($_POST['excluir_id'])) {
        $usuario->id = $_POST['excluir_id'];
        
        // Remover restrição de exclusão do usuário logado
        if($usuario->excluir()) {
            $mensagem = "Usuário excluído com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível excluir o usuário. Verifique se não há registros associados.";
            $tipo_mensagem = "danger";
        }
    } 
    // Verificar se é uma operação de desativação
    else if(isset($_POST['desativar_id']) && !empty($_POST['desativar_id'])) {
        $usuario->id = $_POST['desativar_id'];
        
        // Impedir que o usuário desative a si mesmo
        if($usuario->id == $_SESSION['user_id']) {
            $mensagem = "Você não pode desativar seu próprio usuário.";
            $tipo_mensagem = "danger";
        }
        else if($usuario->desativar()) {
            $mensagem = "Usuário desativado com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível desativar o usuário.";
            $tipo_mensagem = "danger";
        }
    } 
    // Verificar se é uma operação de ativação
    else if(isset($_POST['ativar_id']) && !empty($_POST['ativar_id'])) {
        $usuario->id = $_POST['ativar_id'];
        
        if($usuario->reativar()) {
            $mensagem = "Usuário ativado com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível ativar o usuário.";
            $tipo_mensagem = "danger";
        }
    } 
    // Verificar se é uma operação de edição ou cadastro
    else {
        $usuario->nome = $_POST['nome'];
        $usuario->login = $_POST['login'];
        $usuario->nivel = $_POST['nivel'];
        
        if(isset($_POST['id']) && !empty($_POST['id'])) {
            // Edição
            $usuario->id = $_POST['id'];
            $usuario->ativo = 1; // Garantir que o usuário esteja ativo
            
            // Verificar se a senha foi alterada
            if(!empty($_POST['senha'])) {
                $usuario->senha = $_POST['senha']; // Usando senha sem hash para simplicidade
            }
            
            if($usuario->atualizar()) {
                $mensagem = "Usuário atualizado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível atualizar o usuário.";
                $tipo_mensagem = "danger";
            }
        } else {
            // Cadastro
            if(empty($_POST['senha'])) {
                $mensagem = "A senha é obrigatória para novos usuários.";
                $tipo_mensagem = "danger";
            } else {
                $usuario->senha = $_POST['senha']; // Usando senha sem hash para simplicidade
                
                if($usuario->criar()) {
                    $mensagem = "Usuário cadastrado com sucesso!";
                    $tipo_mensagem = "success";
                } else {
                    $mensagem = "Não foi possível cadastrar o usuário. Verifique se o login já existe.";
                    $tipo_mensagem = "danger";
                }
            }
        }
    }
}

// Carregar lista de usuários
$usuarios = $usuario->listar();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Cadastro de Usuários</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Cadastro de Usuários</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#usuarioModal">
                <i class="fas fa-plus"></i> Novo Usuário
            </button>
        </div>
        
        <?php if($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Usuários Cadastrados</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Login</th>
                                <th>Nível</th>
                                <th>Status</th>
                                <th>Data de Cadastro</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $usuarios->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr <?php echo ($row['ativo'] == 0) ? 'class="table-secondary text-muted"' : ''; ?>>
                                <td><?php echo $row['nome']; ?></td>
                                <td><?php echo $row['login']; ?></td>
                                <td>
                                    <?php if($row['nivel'] == 'admin'): ?>
                                        <span class="badge bg-danger">Administrador</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Funcionário</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['ativo'] == 1): ?>
                                        <span class="badge bg-success">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">Desativado</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($row['data_cadastro'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-primary editar-usuario" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#usuarioModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-nome="<?php echo $row['nome']; ?>"
                                            data-login="<?php echo $row['login']; ?>"
                                            data-nivel="<?php echo $row['nivel']; ?>">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if($row['ativo'] == 1): ?>
                                        <!-- Botão para desativar usuário -->
                                        <button class="btn btn-sm btn-warning desativar-usuario"
                                                data-bs-toggle="modal"
                                                data-bs-target="#desativarModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-nome="<?php echo $row['nome']; ?>">
                                            <i class="fas fa-user-slash"></i>
                                        </button>
                                    <?php else: ?>
                                        <!-- Botão para ativar usuário -->
                                        <button class="btn btn-sm btn-success ativar-usuario"
                                                data-bs-toggle="modal"
                                                data-bs-target="#ativarModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-nome="<?php echo $row['nome']; ?>">
                                            <i class="fas fa-user-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <!-- Botão para excluir usuário -->
                                    <button class="btn btn-sm btn-danger excluir-usuario"
                                            data-bs-toggle="modal"
                                            data-bs-target="#excluirModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-nome="<?php echo $row['nome']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Cadastro/Edição -->
    <div class="modal fade" id="usuarioModal" tabindex="-1" aria-labelledby="usuarioModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="usuarioModalLabel">Novo Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="usuario_id">
                        
                        <div class="mb-3">
                            <label for="nome" class="form-label">Nome</label>
                            <input type="text" class="form-control" id="nome" name="nome" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="login" class="form-label">Login</label>
                            <input type="text" class="form-control" id="login" name="login" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="senha" class="form-label">Senha <small class="text-muted" id="senha_info">(deixe em branco para manter a senha atual)</small></label>
                            <input type="password" class="form-control" id="senha" name="senha">
                        </div>
                        
                        <div class="mb-3">
                            <label for="nivel" class="form-label">Nível</label>
                            <select class="form-select" id="nivel" name="nivel" required>
                                <option value="admin">Administrador</option>
                                <option value="funcionario">Funcionário</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Desativação -->
    <div class="modal fade" id="desativarModal" tabindex="-1" aria-labelledby="desativarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="desativarModalLabel">Desativar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja desativar o usuário <strong><span id="nome_desativar"></span></strong>?</p>
                    <p class="text-warning">O usuário desativado não poderá mais acessar o sistema, mas todos os seus registros serão mantidos.</p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="">
                        <input type="hidden" name="desativar_id" id="desativar_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Desativar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Ativação -->
    <div class="modal fade" id="ativarModal" tabindex="-1" aria-labelledby="ativarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ativarModalLabel">Ativar Usuário</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja ativar o usuário <strong><span id="nome_ativar"></span></strong>?</p>
                    <p class="text-success">O usuário ativado poderá acessar novamente o sistema.</p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="">
                        <input type="hidden" name="ativar_id" id="ativar_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Ativar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal de Exclusão -->
    <div class="modal fade" id="excluirModal" tabindex="-1" aria-labelledby="excluirModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="excluirModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja <strong>excluir permanentemente</strong> o usuário <strong><span id="nome_excluir"></span></strong>?</p>
                    <p class="text-danger"><strong>Atenção:</strong> Esta ação não pode ser desfeita e todos os registros associados serão excluídos.</p>
                    <p class="text-warning"><strong>Recomendação:</strong> Em vez de excluir, considere desativar o usuário para manter o histórico.</p>
                </div>
                <div class="modal-footer">
                    <form method="post" action="">
                        <input type="hidden" name="excluir_id" id="excluir_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir Permanentemente</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        // JavaScript para edição de usuários
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar modal de edição
            const editarBtns = document.querySelectorAll('.editar-usuario');
            editarBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nome = this.getAttribute('data-nome');
                    const login = this.getAttribute('data-login');
                    const nivel = this.getAttribute('data-nivel');
                    
                    document.getElementById('usuarioModalLabel').textContent = 'Editar Usuário';
                    document.getElementById('usuario_id').value = id;
                    document.getElementById('nome').value = nome;
                    document.getElementById('login').value = login;
                    document.getElementById('nivel').value = nivel;
                    document.getElementById('senha').value = '';
                    document.getElementById('senha_info').style.display = 'inline';
                });
            });
            
            // Configurar modal para novo usuário
            const novoBtn = document.querySelector('[data-bs-target="#usuarioModal"]');
            novoBtn.addEventListener('click', function() {
                document.getElementById('usuarioModalLabel').textContent = 'Novo Usuário';
                document.getElementById('usuario_id').value = '';
                document.getElementById('nome').value = '';
                document.getElementById('login').value = '';
                document.getElementById('senha').value = '';
                document.getElementById('nivel').value = 'funcionario';
                document.getElementById('senha_info').style.display = 'none';
            });
            
            // Configurar modal de desativação
            const desativarBtns = document.querySelectorAll('.desativar-usuario');
            desativarBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nome = this.getAttribute('data-nome');
                    document.getElementById('desativar_id').value = id;
                    document.getElementById('nome_desativar').textContent = nome;
                });
            });
            
            // Configurar modal de ativação
            const ativarBtns = document.querySelectorAll('.ativar-usuario');
            ativarBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const nome = this.getAttribute('data-nome');
                    document.getElementById('ativar_id').value = id;
                    document.getElementById('nome_ativar').textContent = nome;
                });
            });
            
            // Configurar modal de exclusão
            const excluirBtns = document.querySelectorAll('.excluir-usuario');
            excluirBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.getElementById('excluir_id').value = id;
                    document.getElementById('nome_excluir').textContent = this.getAttribute('data-nome');
                });
            });
        });
    </script>
</body>
</html>
