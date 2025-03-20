<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include_once '../config/database.php';
include_once '../model/manutencao.php';
include_once '../model/veiculo.php';

$database = new Database();
$db = $database->getConnection();

$manutencao = new Manutencao($db);
$veiculo = new Veiculo($db);

$mensagem = '';
$tipo_mensagem = '';

// Verificar se o formulário foi enviado
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar se é uma operação de finalização
    if(isset($_POST['finalizar_id']) && !empty($_POST['finalizar_id'])) {
        $manutencao->id = $_POST['finalizar_id'];
        $manutencao->valor = $_POST['valor_final'];
        $manutencao->descricao = $_POST['descricao_final'];
        
        if($manutencao->finalizar()) {
            // Atualizar status do veículo
            $manutencao->ler();
            $veiculo->id = $manutencao->veiculo_id;
            $veiculo->definirDisponivel();
            
            $mensagem = "Manutenção finalizada com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível finalizar a manutenção.";
            $tipo_mensagem = "danger";
        }
    } 
    // Verificar se é uma operação de exclusão
    else if(isset($_POST['excluir_id']) && !empty($_POST['excluir_id'])) {
        $manutencao->id = $_POST['excluir_id'];
        
        // Verificar se a manutenção está em andamento
        $manutencao->ler();
        $em_andamento = !$manutencao->finalizada;
        
        if($manutencao->excluir()) {
            // Se a manutenção estava em andamento, restaurar o status do veículo
            if($em_andamento) {
                $veiculo->id = $manutencao->veiculo_id;
                $veiculo->definirDisponivel();
            }
            
            $mensagem = "Manutenção excluída com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível excluir a manutenção.";
            $tipo_mensagem = "danger";
        }
    } 
    // Cadastro de nova manutenção
    else {
        $manutencao->veiculo_id = $_POST['veiculo_id'];
        $manutencao->tipo = $_POST['tipo'];
        $manutencao->km_inicio = $_POST['km_inicio'];
        
        // Verificar se KM atual é maior ou igual ao último registrado
        $veiculo->id = $manutencao->veiculo_id;
        $veiculo->ler();
        
        if($manutencao->km_inicio < $veiculo->km_atual) {
            $mensagem = "Erro: A quilometragem informada é menor que a última registrada para o veículo.";
            $tipo_mensagem = "danger";
        } else {
            if($manutencao->criar()) {
                // Atualizar KM do veículo e definir status para "em manutenção"
                $veiculo->atualizarKM($manutencao->km_inicio);
                $veiculo->definirEmManutencao();
                
                $mensagem = "Manutenção registrada com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível registrar a manutenção.";
                $tipo_mensagem = "danger";
            }
        }
    }
}

// Carregar lista de manutenções
$manutencoes = $manutencao->listar();

// Carregar lista de veículos disponíveis para o formulário
$veiculos_disponiveis = $veiculo->listarDisponiveis();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Registro de Manutenções</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Registro de Manutenções</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#manutencaoModal">
                <i class="fas fa-tools"></i> Nova Manutenção
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
                <h6 class="m-0 font-weight-bold text-primary">Manutenções Registradas</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Início</th>
                                <th>Veículo</th>
                                <th>Tipo</th>
                                <th>KM</th>
                                <th>Status</th>
                                <th>Término</th>
                                <th>Valor</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $manutencoes->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($row['data_inicio'])); ?></td>
                                <td><?php echo $row['placa'] . ' - ' . $row['modelo']; ?></td>
                                <td><?php echo $row['tipo']; ?></td>
                                <td><?php echo number_format($row['km_inicio'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php if($row['finalizada']): ?>
                                        <span class="badge bg-success">Finalizada</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Em andamento</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if($row['data_fim']) {
                                        echo date('d/m/Y', strtotime($row['data_fim']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if($row['finalizada']) {
                                        echo 'R$ ' . number_format($row['valor'], 2, ',', '.');
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if(!$row['finalizada']): ?>
                                        <button class="btn btn-sm btn-success finalizar-manutencao"
                                                data-bs-toggle="modal"
                                                data-bs-target="#finalizarModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-veiculo="<?php echo $row['placa'] . ' - ' . $row['modelo']; ?>"
                                                data-tipo="<?php echo $row['tipo']; ?>">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button class="btn btn-sm btn-danger excluir-manutencao"
                                            data-bs-toggle="modal"
                                            data-bs-target="#excluirModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-info="<?php echo $row['placa'] . ' (' . $row['tipo'] . ')'; ?>">
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
    
    <!-- Modal de Cadastro -->
    <div class="modal fade" id="manutencaoModal" tabindex="-1" aria-labelledby="manutencaoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="manutencaoModalLabel">Nova Manutenção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="veiculo_id" class="form-label">Veículo</label>
                            <select class="form-select" id="veiculo_id" name="veiculo_id" required>
                                <option value="">Selecione um veículo</option>
                                <?php 
                                while ($v = $veiculos_disponiveis->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $v['id'] . '" data-km="' . $v['km_atual'] . '">' . $v['placa'] . ' - ' . $v['modelo'] . ' (' . $v['marca'] . ')</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo de Manutenção</label>
                            <input type="text" class="form-control" id="tipo" name="tipo" required 
                                   placeholder="Ex: Troca de óleo, Revisão geral, Troca de pneus...">
                        </div>
                        
                        <div class="mb-3">
                            <label for="km_inicio" class="form-label">KM Atual</label>
                            <input type="number" step="0.01" class="form-control" id="km_inicio" name="km_inicio" required>
                            <small class="text-muted">Último KM registrado: <span id="ultimo_km">-</span></small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Iniciar Manutenção</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal de Finalização -->
    <div class="modal fade" id="finalizarModal" tabindex="-1" aria-labelledby="finalizarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finalizarModalLabel">Finalizar Manutenção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <p>Você está finalizando a manutenção: <strong id="manutencao_info"></strong></p>

                        <div class="mb-3">
                            <label for="valor_final" class="form-label">Valor Total (R$)</label>
                            <input type="number" step="0.01" class="form-control" id="valor_final" name="valor_final" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="descricao_final" class="form-label">Descrição do Serviço Realizado</label>
                            <textarea class="form-control" id="descricao_final" name="descricao_final" rows="3" required></textarea>
                        </div>

                        <input type="hidden" name="finalizar_id" id="finalizar_id">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Finalizar Manutenção</button>
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
                    Tem certeza que deseja excluir a manutenção do veículo <span id="info_excluir"></span>?
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
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mostrar KM atual do veículo selecionado
            const veiculoSelect = document.getElementById('veiculo_id');
            veiculoSelect.addEventListener('change', function() {
                const option = this.options[this.selectedIndex];
                if (option.value) {
                    const km = option.getAttribute('data-km');
                    document.getElementById('ultimo_km').textContent = Number(km).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                    document.getElementById('km_inicio').min = km;
                    document.getElementById('km_inicio').value = km;
                } else {
                    document.getElementById('ultimo_km').textContent = '-';
                    document.getElementById('km_inicio').min = 0;
                    document.getElementById('km_inicio').value = '';
                }
            });
            
            // Configurar modal de finalização
            const finalizarBtns = document.querySelectorAll('.finalizar-manutencao');
            finalizarBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const veiculo = this.getAttribute('data-veiculo');
                    const tipo = this.getAttribute('data-tipo');
                    
                    document.getElementById('finalizar_id').value = id;
                    document.getElementById('manutencao_info').textContent = tipo + ' - ' + veiculo;
                });
            });
            
            // Configurar modal de exclusão
            const excluirBtns = document.querySelectorAll('.excluir-manutencao');
            excluirBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.getElementById('excluir_id').value = id;
                    document.getElementById('info_excluir').textContent = this.getAttribute('data-info');
                });
            });
        });
    </script>
</body>
</html>
