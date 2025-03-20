<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include_once '../config/database.php';
include_once '../model/veiculo.php';
include_once '../model/usuario.php';
include_once '../model/uso_veiculo.php';

$database = new Database();
$db = $database->getConnection();

$veiculo = new Veiculo($db);
$usuario = new Usuario($db);
$uso_veiculo = new UsoVeiculo($db);

$mensagem = '';
$tipo_mensagem = '';

// Verificar se o formulário foi enviado
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar se é uma operação de exclusão
    if(isset($_POST['excluir_id']) && !empty($_POST['excluir_id'])) {
        $veiculo->id = $_POST['excluir_id'];
        
        if($veiculo->excluir(true)) {
            $mensagem = "Veículo removido com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível remover o veículo.";
            $tipo_mensagem = "danger";
        }
    }
    // Verificar se é uma operação de desativação
    else if(isset($_POST['desativar_id']) && !empty($_POST['desativar_id'])) {
        $veiculo->id = $_POST['desativar_id'];
        
        if($veiculo->desativar()) {
            $mensagem = "Veículo desativado com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível desativar o veículo.";
            $tipo_mensagem = "danger";
        }
    }
    // Verificar se é uma operação de ativação
    else if(isset($_POST['ativar_id']) && !empty($_POST['ativar_id'])) {
        $veiculo->id = $_POST['ativar_id'];
        
        if($veiculo->ativar()) {
            $mensagem = "Veículo ativado com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível ativar o veículo.";
            $tipo_mensagem = "danger";
        }
    }
    // Verificar se é uma operação de iniciar uso
    else if(isset($_POST['iniciar_uso_veiculo_id']) && !empty($_POST['iniciar_uso_veiculo_id'])) {
        $uso_veiculo->veiculo_id = $_POST['iniciar_uso_veiculo_id'];
        $uso_veiculo->usuario_id = $_POST['usuario_id'];
        $uso_veiculo->km_saida = $_POST['km_saida'];
        $uso_veiculo->motivo = $_POST['motivo'];

        // Verificar KM de saída
        $veiculo->id = $uso_veiculo->veiculo_id;
        $veiculo->ler();

        if($uso_veiculo->km_saida < $veiculo->km_atual) {
            $mensagem = "Erro: A quilometragem de saída deve ser maior ou igual à quilometragem atual do veículo.";
            $tipo_mensagem = "danger";
        } else {
            if($uso_veiculo->iniciar()) {
                // Atualizar KM do veículo
                $veiculo->atualizarKM($uso_veiculo->km_saida);
                // Atualizar status do veículo para em uso
                $veiculo->definirEmUso();

                $mensagem = "Uso do veículo iniciado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro ao iniciar uso do veículo.";
                $tipo_mensagem = "danger";
            }
        }
    }
    // Verificar se é uma operação de finalizar uso
    else if(isset($_POST['finalizar_uso_id']) && !empty($_POST['finalizar_uso_id'])) {
        $uso_veiculo->id = $_POST['finalizar_uso_id'];
        $km_retorno = floatval($_POST['km_retorno']);
        $uso_veiculo->observacoes = $_POST['observacoes'];

        // Buscar dados do uso atual
        $uso_veiculo->ler();

        // Definir KM de retorno
        $uso_veiculo->km_retorno = $km_retorno;

        // Verificar se KM retorno é maior que KM saída
        if($uso_veiculo->km_retorno <= floatval($uso_veiculo->km_saida)) {
            $mensagem = "Erro: A quilometragem de retorno deve ser maior que a quilometragem de saída.";
            $tipo_mensagem = "danger";
        } else {
            if($uso_veiculo->finalizar()) {
                // Atualizar KM do veículo
                $veiculo->id = $uso_veiculo->veiculo_id;
                $veiculo->atualizarKM($uso_veiculo->km_retorno);
                // Atualizar status do veículo para disponível
                $veiculo->definirDisponivel();

                $mensagem = "Uso do veículo finalizado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Erro ao finalizar uso do veículo.";
                $tipo_mensagem = "danger";
            }
        }
    }
    // Verificar se é uma operação de edição ou cadastro de veículo
    else {
        $veiculo->placa = $_POST['placa'];
        $veiculo->modelo = $_POST['modelo'];
        $veiculo->marca = $_POST['marca'];
        $veiculo->ano = $_POST['ano'];
        // Garantir que km_atual seja um número inteiro
        $veiculo->km_atual = intval($_POST['km_atual']);

        if(isset($_POST['id']) && !empty($_POST['id'])) {
            // Edição
            $veiculo->id = $_POST['id'];
            if($veiculo->atualizar()) {
                $mensagem = "Veículo atualizado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível atualizar o veículo.";
                $tipo_mensagem = "danger";
            }
        } else {
            // Cadastro
            if($veiculo->criar()) {
                $mensagem = "Veículo cadastrado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível cadastrar o veículo.";
                $tipo_mensagem = "danger";
            }
        }
    }
}

// Carregar lista de veículos (incluindo os inativos)
$veiculos = $veiculo->listarTodos();

// Carregar lista de usuários funcionários para o modal de iniciar uso
$usuarios_lista = $usuario->listarAtivos();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Cadastro de Veículos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Cadastro de Veículos</h1>
            <div>
                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#iniciarUsoModal">
                    <i class="fas fa-car"></i> Novo Uso
                </button>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#veiculoModal">
                    <i class="fas fa-plus"></i> Novo Veículo
                </button>
            </div>
        </div>

        <?php if($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Veículos Cadastrados</h6>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="mostrarInativos">
                    <label class="form-check-label" for="mostrarInativos">Mostrar inativos</label>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Placa</th>
                                <th>Modelo</th>
                                <th>Marca</th>
                                <th>Ano</th>
                                <th>KM Atual</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Reiniciar o ponteiro do resultado
                            $veiculos_array = [];
                            while ($row = $veiculos->fetch(PDO::FETCH_ASSOC)) {
                                $veiculos_array[] = $row;
                            }

                            foreach($veiculos_array as $row):
                                // Verificar se o veículo está em uso
                                $em_uso = false;
                                $dados_uso = null;
                                if($row['status'] == 'em_uso') {
                                    $em_uso = true;
                                    // Buscar informações do uso atual
                                    $veiculo->id = $row['id'];
                                    $dados_uso = $veiculo->obterUsuarioEmUso();
                                }
                                
                                // Determinar se a linha deve ser mostrada inicialmente (esconder inativos)
                                $linha_classe = isset($row['ativo']) && $row['ativo'] == 0 ? 'linha-inativa d-none' : '';
                            ?>
                            <tr class="<?php echo $linha_classe; ?>">
                                <td><?php echo $row['placa']; ?></td>
                                <td><?php echo $row['modelo']; ?></td>
                                <td><?php echo $row['marca']; ?></td>
                                <td><?php echo $row['ano']; ?></td>
                                <td><?php echo intval($row['km_atual']); ?> km</td>
                                <td>
                                    <?php if(isset($row['ativo']) && $row['ativo'] == 0): ?>
                                        <span class="badge bg-secondary">Inativo</span>
                                    <?php elseif($row['status'] == 'disponivel'): ?>
                                        <span class="badge bg-success">Disponível</span>
                                    <?php elseif($row['status'] == 'em_uso'): ?>
                                        <span class="badge bg-warning">Em Uso</span>
                                        <?php if($dados_uso): ?>
                                            <small class="d-block">por <?php echo $dados_uso['nome']; ?></small>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Em Manutenção</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if(isset($row['ativo']) && $row['ativo'] == 0): ?>
                                        <!-- Botão de ativação para veículos inativos -->
                                        <button class="btn btn-sm btn-success ativar-veiculo"
                                                data-bs-toggle="modal"
                                                data-bs-target="#ativarModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-placa="<?php echo $row['placa']; ?>">
                                            <i class="fas fa-toggle-on"></i>
                                        </button>
                                    <?php else: ?>
                                        <!-- Botão de edição -->
                                        <button class="btn btn-sm btn-primary editar-veiculo"
                                                data-bs-toggle="modal"
                                                data-bs-target="#veiculoModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-placa="<?php echo $row['placa']; ?>"
                                                data-modelo="<?php echo $row['modelo']; ?>"
                                                data-marca="<?php echo $row['marca']; ?>"
                                                data-ano="<?php echo $row['ano']; ?>"
                                                data-km="<?php echo intval($row['km_atual']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>

                                        <!-- Botão de exclusão -->
                                        <button class="btn btn-sm btn-danger excluir-veiculo"
                                                data-bs-toggle="modal"
                                                data-bs-target="#excluirModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-placa="<?php echo $row['placa']; ?>">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        
                                        <!-- Botão de desativação -->
                                        <button class="btn btn-sm btn-secondary desativar-veiculo"
                                                data-bs-toggle="modal"
                                                data-bs-target="#desativarModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-placa="<?php echo $row['placa']; ?>">
                                            <i class="fas fa-toggle-off"></i>
                                        </button>

                                        <?php if($row['status'] == 'disponivel'): ?>
                                            <!-- Botão de iniciar uso para veículos disponíveis -->
                                            <button class="btn btn-sm btn-success iniciar-uso"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#iniciarUsoModal"
                                                    data-id="<?php echo $row['id']; ?>"
                                                    data-placa="<?php echo $row['placa']; ?>"
                                                    data-modelo="<?php echo $row['modelo']; ?>"
                                                    data-km="<?php echo intval($row['km_atual']); ?>">
                                                <i class="fas fa-car"></i>
                                            </button>
                                        <?php elseif($row['status'] == 'em_uso' && $dados_uso): ?>
                                            <!-- Botão de finalizar uso para veículos em uso -->
                                            <button class="btn btn-sm btn-warning finalizar-uso"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#finalizarUsoModal"
                                                    data-uso-id="<?php echo $dados_uso['uso_id']; ?>"
                                                    data-placa="<?php echo $row['placa']; ?>"
                                                    data-modelo="<?php echo $row['modelo']; ?>"
                                                    data-usuario="<?php echo $dados_uso['nome']; ?>"
                                                    data-km="<?php echo intval($row['km_atual']); ?>">
                                                <i class="fas fa-flag-checkered"></i>
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cadastro/Edição de Veículo -->
    <div class="modal fade" id="veiculoModal" tabindex="-1" aria-labelledby="veiculoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="veiculoModalLabel">Novo Veículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="veiculo_id">

                        <div class="mb-3">
                            <label for="placa" class="form-label">Placa</label>
                            <input type="text" class="form-control" id="placa" name="placa" required>
                        </div>

                        <div class="mb-3">
                            <label for="modelo" class="form-label">Modelo</label>
                            <input type="text" class="form-control" id="modelo" name="modelo" required>
                        </div>

                        <div class="mb-3">
                            <label for="marca" class="form-label">Marca</label>
                            <input type="text" class="form-control" id="marca" name="marca" required>
                        </div>

                        <div class="mb-3">
                            <label for="ano" class="form-label">Ano</label>
                            <input type="number" class="form-control" id="ano" name="ano" required>
                        </div>

                        <div class="mb-3">
                            <label for="km_atual" class="form-label">KM Atual</label>
                            <input type="number" class="form-control" id="km_atual" name="km_atual" required min="0">
                            <small class="text-muted">Insira apenas números inteiros, sem decimais</small>
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

    <!-- Modal de Iniciar Uso de Veículo -->
    <div class="modal fade" id="iniciarUsoModal" tabindex="-1" aria-labelledby="iniciarUsoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="iniciarUsoModalLabel">Iniciar Uso de Veículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="iniciar_uso_veiculo_id" class="form-label">Veículo</label>
                            <select class="form-select" id="iniciar_uso_veiculo_id" name="iniciar_uso_veiculo_id" required>
                                <option value="">Selecione um veículo</option>
                                <?php
                                // Listar apenas veículos disponíveis
                                foreach($veiculos_array as $v) {
                                    if($v['status'] == 'disponivel' && (!isset($v['ativo']) || $v['ativo'] == 1)) {
                                        echo '<option value="' . $v['id'] . '" data-km="' . intval($v['km_atual']) . '">';
                                        echo $v['placa'] . ' - ' . $v['modelo'] . ' (' . $v['marca'] . ')';
                                        echo '</option>';
                                    }
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="usuario_id" class="form-label">Funcionário</label>
                            <select class="form-select" id="usuario_id" name="usuario_id" required>
                                <option value="">Selecione um funcionário</option>
                                <?php
                                while ($u = $usuarios_lista->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $u['id'] . '">' . $u['nome'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="km_saida" class="form-label">KM de Saída</label>
                            <input type="number" step="0.01" class="form-control" id="km_saida" name="km_saida" required>
                            <small class="text-muted">KM atual do veículo: <span id="iniciar_km_atual">-</span></small>
                        </div>

                        <div class="mb-3">
                            <label for="motivo" class="form-label">Motivo de Uso</label>
                            <input type="text" class="form-control" id="motivo" name="motivo" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Iniciar Uso</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Finalizar Uso de Veículo -->
    <div class="modal fade" id="finalizarUsoModal" tabindex="-1" aria-labelledby="finalizarUsoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finalizarUsoModalLabel">Finalizar Uso de Veículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="" id="formFinalizarUso">
                    <div class="modal-body">
                        <input type="hidden" name="finalizar_uso_id" id="finalizar_uso_id">

                        <div class="mb-3">
                            <label class="form-label">Veículo</label>
                            <input type="text" class="form-control" id="finalizar_veiculo_info" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Funcionário</label>
                            <input type="text" class="form-control" id="finalizar_usuario_info" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="km_retorno" class="form-label">KM de Retorno</label>
                            <input type="number" step="0.01" class="form-control" id="km_retorno" name="km_retorno" required>
                            <small class="text-muted">Deve ser maior que o KM de saída (<span id="finalizar_km_atual">0</span>)</small>
                            <div id="km_feedback" class="invalid-feedback">
                                A quilometragem de retorno deve ser maior que a de saída.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Finalizar Uso</button>
                    </div>
                </form>
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
                    <p>Tem certeza que deseja excluir o veículo <span id="placa_excluir"></span>?</p>
                    <div class="alert alert-warning">
                        <small>Nota: Se preferir manter o histórico, considere usar a opção "Desativar" em vez de excluir.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <form method="post" action="">
                        <input type="hidden" name="excluir_id" id="excluir_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-danger">Excluir</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Desativação -->
    <div class="modal fade" id="desativarModal" tabindex="-1" aria-labelledby="desativarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="desativarModalLabel">Desativar Veículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja desativar o veículo <span id="placa_desativar"></span>?</p>
                    <div class="alert alert-info">
                        <small>Ao desativar um veículo, ele não será excluído e o histórico será mantido, mas não estará disponível para uso.</small>
                    </div>
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
                    <h5 class="modal-title" id="ativarModalLabel">Ativar Veículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Deseja reativar o veículo <span id="placa_ativar"></span>?</p>
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

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        // JavaScript para manipulação dos modais
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar o checkbox para mostrar/ocultar veículos inativos
            const checkboxInativos = document.getElementById('mostrarInativos');
            checkboxInativos.addEventListener('change', function() {
                const linhasInativas = document.querySelectorAll('.linha-inativa');
                linhasInativas.forEach(linha => {
                    if (this.checked) {
                        linha.classList.remove('d-none');
                    } else {
                        linha.classList.add('d-none');
                    }
                });
            });

            // Configurar modal de edição
            const editarBtns = document.querySelectorAll('.editar-veiculo');
            editarBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const placa = this.getAttribute('data-placa');
                    const modelo = this.getAttribute('data-modelo');
                    const marca = this.getAttribute('data-marca');
                    const ano = this.getAttribute('data-ano');
                    const km = this.getAttribute('data-km');

                    document.getElementById('veiculoModalLabel').textContent = 'Editar Veículo';
                    document.getElementById('veiculo_id').value = id;
                    document.getElementById('placa').value = placa;
                    document.getElementById('modelo').value = modelo;
                    document.getElementById('marca').value = marca;
                    document.getElementById('ano').value = ano;
                    document.getElementById('km_atual').value = km;
                });
            });

            // Configurar modal para novo veículo
            const novoBtn = document.querySelector('[data-bs-target="#veiculoModal"]');
            novoBtn.addEventListener('click', function() {
                document.getElementById('veiculoModalLabel').textContent = 'Novo Veículo';
                document.getElementById('veiculo_id').value = '';
                document.getElementById('placa').value = '';
                document.getElementById('modelo').value = '';
                document.getElementById('marca').value = '';
                document.getElementById('ano').value = '';
                document.getElementById('km_atual').value = '';
            });

            // Configurar modal de exclusão
            const excluirBtns = document.querySelectorAll('.excluir-veiculo');
            excluirBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.getElementById('excluir_id').value = id;
                    document.getElementById('placa_excluir').textContent = this.getAttribute('data-placa');
                });
            });
            
            // Configurar modal de desativação
            const desativarBtns = document.querySelectorAll('.desativar-veiculo');
            desativarBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.getElementById('desativar_id').value = id;
                    document.getElementById('placa_desativar').textContent = this.getAttribute('data-placa');
                });
            });
            
            // Configurar modal de ativação
            const ativarBtns = document.querySelectorAll('.ativar-veiculo');
            ativarBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.getElementById('ativar_id').value = id;
                    document.getElementById('placa_ativar').textContent = this.getAttribute('data-placa');
                });
            });

            // Configurar modal de iniciar uso
            const iniciarUsoBtns = document.querySelectorAll('.iniciar-uso');
            iniciarUsoBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const km = this.getAttribute('data-km');

                    document.getElementById('iniciar_uso_veiculo_id').value = id;
                    document.getElementById('iniciar_km_atual').textContent = km;
                    document.getElementById('km_saida').value = km;
                    document.getElementById('km_saida').min = km;
                });
            });

            // Atualizar KM atual ao selecionar veículo no modal de iniciar uso
            const veiculoSelect = document.getElementById('iniciar_uso_veiculo_id');
            if (veiculoSelect) {
                veiculoSelect.addEventListener('change', function() {
                    const option = this.options[this.selectedIndex];
                    if (option.value) {
                        const km = option.getAttribute('data-km');
                        document.getElementById('iniciar_km_atual').textContent = km;
                        document.getElementById('km_saida').value = km;
                        document.getElementById('km_saida').min = km;
                    } else {
                        document.getElementById('iniciar_km_atual').textContent = '-';
                        document.getElementById('km_saida').value = '';
                    }
                });
            }

            // Configurar modal de finalizar uso
            const finalizarUsoBtns = document.querySelectorAll('.finalizar-uso');
            finalizarUsoBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const usoId = this.getAttribute('data-uso-id');
                    const placa = this.getAttribute('data-placa');
                    const modelo = this.getAttribute('data-modelo');
                    const usuario = this.getAttribute('data-usuario');
                    const km = this.getAttribute('data-km');

                    document.getElementById('finalizar_uso_id').value = usoId;
                    document.getElementById('finalizar_veiculo_info').value = placa + ' - ' + modelo;
                    document.getElementById('finalizar_usuario_info').value = usuario;
                    document.getElementById('finalizar_km_atual').textContent = km;
                    document.getElementById('km_retorno').value = '';
                    document.getElementById('km_retorno').min = parseFloat(km) + 0.01;
                });
            });

            // Validação em tempo real para o KM de retorno
            const kmRetornoInput = document.getElementById('km_retorno');
            if (kmRetornoInput) {
                kmRetornoInput.addEventListener('input', function() {
                    const kmAtual = parseFloat(document.getElementById('finalizar_km_atual').textContent);
                    const kmRetorno = parseFloat(this.value);

                    if (kmRetorno <= kmAtual) {
                        this.classList.add('is-invalid');
                        document.getElementById('km_feedback').style.display = 'block';
                    } else {
                        this.classList.remove('is-invalid');
                        document.getElementById('km_feedback').style.display = 'none';
                    }
                });
            }

            // Validação do formulário de finalizar uso
            const formFinalizarUso = document.getElementById('formFinalizarUso');
            if (formFinalizarUso) {
                formFinalizarUso.addEventListener('submit', function(event) {
                    const kmAtual = parseFloat(document.getElementById('finalizar_km_atual').textContent);
                    const kmRetorno = parseFloat(document.getElementById('km_retorno').value);

                    if (kmRetorno <= kmAtual) {
                        event.preventDefault();
                        document.getElementById('km_retorno').classList.add('is-invalid');
                        document.getElementById('km_feedback').style.display = 'block';
                        return false;
                    }

                    return true;
                });
            }
        });
    </script>
</body>
</html>
