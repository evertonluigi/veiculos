<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include_once '../config/database.php';
include_once '../model/abastecimento.php';
include_once '../model/veiculo.php';
include_once '../model/usuario.php';

$database = new Database();
$db = $database->getConnection();

$abastecimento = new Abastecimento($db);
$veiculo = new Veiculo($db);
$usuario = new Usuario($db);

$mensagem = '';
$tipo_mensagem = '';

// Verificar se o formulário foi enviado
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar se é uma operação de exclusão
    if(isset($_POST['excluir_id']) && !empty($_POST['excluir_id'])) {
        $abastecimento->id = $_POST['excluir_id'];
        
        if($abastecimento->excluir()) {
            $mensagem = "Abastecimento excluído com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível excluir o abastecimento.";
            $tipo_mensagem = "danger";
        }
    } else {
        // Cadastro de abastecimento
        $abastecimento->veiculo_id = $_POST['veiculo_id'];
        $abastecimento->usuario_id = $_POST['usuario_id'];
        $abastecimento->km = $_POST['km'];
        $abastecimento->litros = $_POST['litros'];
        $abastecimento->tipo_combustivel = $_POST['tipo_combustivel'];
        $abastecimento->valor = $_POST['valor'];
        
        // Verificar se KM atual é maior que o último registrado
        $veiculo->id = $abastecimento->veiculo_id;
        $veiculo->ler();
        
        if($abastecimento->km < $veiculo->km_atual) {
            $mensagem = "Erro: A quilometragem informada é menor que a última registrada para o veículo.";
            $tipo_mensagem = "danger";
        } else {
            if($abastecimento->criar()) {
                // Atualizar KM do veículo
                $veiculo->atualizarKM($abastecimento->km);
                
                $mensagem = "Abastecimento registrado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível registrar o abastecimento.";
                $tipo_mensagem = "danger";
            }
        }
    }
}

// Carregar lista de abastecimentos
$abastecimentos = $abastecimento->listar();

// Carregar lista de veículos para o formulário
$veiculos_lista = $veiculo->listar();

// Carregar lista de usuários para o formulário
$usuarios_lista = $usuario->listar();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Registro de Abastecimentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Registro de Abastecimentos</h1>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#abastecimentoModal">
                <i class="fas fa-gas-pump"></i> Novo Abastecimento
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
                <h6 class="m-0 font-weight-bold text-primary">Abastecimentos Registrados</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Veículo</th>
                                <th>Usuário</th>
                                <th>KM</th>
                                <th>Litros</th>
                                <th>Combustível</th>
                                <th>Valor</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $abastecimentos->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['data'])); ?></td>
                                <td><?php echo $row['placa'] . ' - ' . $row['modelo']; ?></td>
                                <td><?php echo $row['nome_usuario']; ?></td>
                                <td><?php echo number_format($row['km'], 2, ',', '.'); ?></td>
                                <td><?php echo number_format($row['litros'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    switch($row['tipo_combustivel']) {
                                        case 'gasolina':
                                            echo '<span class="badge bg-danger">Gasolina</span>';
                                            break;
                                        case 'etanol':
                                            echo '<span class="badge bg-success">Etanol</span>';
                                            break;
                                        case 'diesel':
                                            echo '<span class="badge bg-warning">Diesel</span>';
                                            break;
                                        case 'gnv':
                                            echo '<span class="badge bg-info">GNV</span>';
                                            break;
                                        default:
                                            echo $row['tipo_combustivel'];
                                    }
                                    ?>
                                </td>
                                <td>R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-danger excluir-abastecimento"
                                            data-bs-toggle="modal"
                                            data-bs-target="#excluirModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-info="<?php echo $row['placa'] . ' em ' . date('d/m/Y', strtotime($row['data'])); ?>">
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
    <div class="modal fade" id="abastecimentoModal" tabindex="-1" aria-labelledby="abastecimentoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="abastecimentoModalLabel">Novo Abastecimento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="veiculo_id" class="form-label">Veículo</label>
                            <select class="form-select" id="veiculo_id" name="veiculo_id" required>
                                <option value="">Selecione um veículo</option>
                                <?php 
                                while ($v = $veiculos_lista->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $v['id'] . '" data-km="' . $v['km_atual'] . '">' . $v['placa'] . ' - ' . $v['modelo'] . ' (' . $v['marca'] . ')</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="usuario_id" class="form-label">Usuário</label>
                            <select class="form-select" id="usuario_id" name="usuario_id" required>
                                <option value="">Selecione um usuário</option>
                                <?php 
                                while ($u = $usuarios_lista->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . $u['id'] . '">' . $u['nome'] . '</option>';
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="km" class="form-label">KM Atual</label>
                            <input type="number" step="0.01" class="form-control" id="km" name="km" required>
                            <small class="text-muted">Último KM registrado: <span id="ultimo_km">-</span></small>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo_combustivel" class="form-label">Tipo de Combustível</label>
                            <select class="form-select" id="tipo_combustivel" name="tipo_combustivel" required>
                                <option value="gasolina">Gasolina</option>
                                <option value="etanol">Etanol</option>
                                <option value="diesel">Diesel</option>
                                <option value="gnv">GNV</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="litros" class="form-label">Quantidade (litros)</label>
                            <input type="number" step="0.01" class="form-control" id="litros" name="litros" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="valor" class="form-label">Valor Total (R$)</label>
                            <input type="number" step="0.01" class="form-control" id="valor" name="valor" required>
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
    
    <!-- Modal de Exclusão -->
    <div class="modal fade" id="excluirModal" tabindex="-1" aria-labelledby="excluirModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="excluirModalLabel">Confirmar Exclusão</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tem certeza que deseja excluir o abastecimento do veículo <span id="info_excluir"></span>?
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
                    document.getElementById('km').min = km;
                    document.getElementById('km').value = km;
                } else {
                    document.getElementById('ultimo_km').textContent = '-';
                    document.getElementById('km').min = 0;
                    document.getElementById('km').value = '';
                }
            });
            
            // Configurar modal de exclusão
            const excluirBtns = document.querySelectorAll('.excluir-abastecimento');
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
