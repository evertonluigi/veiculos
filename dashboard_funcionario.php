<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'funcionario') {
    header("Location: index.php");
    exit;
}

include_once 'config/database.php';
include_once 'model/veiculo.php';
include_once 'model/uso_veiculo.php';
include_once 'model/abastecimento.php';

$database = new Database();
$db = $database->getConnection();

$veiculo = new Veiculo($db);
$uso_veiculo = new UsoVeiculo($db);
$abastecimento = new Abastecimento($db);

$mensagem = '';
$tipo_mensagem = '';

// Ações de formulário
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Iniciar uso de veículo
    if(isset($_POST['iniciar_uso']) && isset($_POST['veiculo_id']) && !empty($_POST['veiculo_id'])) {
        $uso_veiculo->veiculo_id = $_POST['veiculo_id'];
        $uso_veiculo->usuario_id = $_SESSION['user_id'];
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

    // Finalizar uso de veículo
    else if(isset($_POST['finalizar_uso']) && isset($_POST['uso_id']) && !empty($_POST['uso_id'])) {
        $uso_veiculo->id = $_POST['uso_id'];
        $km_retorno_temp = floatval($_POST['km_retorno']); // Armazena em variável temporária
        $uso_veiculo->observacoes = $_POST['observacoes'];

        // Buscar dados do uso atual
        $uso_veiculo->ler();
        
        // Restaura o valor após a leitura
        $uso_veiculo->km_retorno = $km_retorno_temp;

        // Verifique com valores numéricos
        if($uso_veiculo->km_retorno <= floatval($uso_veiculo->km_saida)) {
            $mensagem = "Erro: A quilometragem de retorno (" . number_format($uso_veiculo->km_retorno, 2, ',', '.') .
                        ") deve ser maior que a quilometragem de saída (" .
                        number_format($uso_veiculo->km_saida, 2, ',', '.') . ").";
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

    // Registrar abastecimento
    else if(isset($_POST['registrar_abastecimento'])) {
        $abastecimento->veiculo_id = $_POST['veiculo_id'];
        $abastecimento->usuario_id = $_SESSION['user_id'];
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

// Verificar se o funcionário tem algum veículo em uso
$veiculo_em_uso = $uso_veiculo->emUsoUsuario($_SESSION['user_id']);

// Obter veículos disponíveis (se não estiver utilizando nenhum)
$veiculos_disponiveis = [];
if(!$veiculo_em_uso) {
    $veiculos_disp = $veiculo->listarDisponiveis();
    while($row = $veiculos_disp->fetch(PDO::FETCH_ASSOC)) {
        $veiculos_disponiveis[] = $row;
    }
}

// Histórico de usos do funcionário
$historico_usos = $uso_veiculo->listarPorUsuario($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Dashboard do Funcionário</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard_funcionario.php">Controle de Veículos</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['user_nome']; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="perfil.php">Perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php">Sair</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Dashboard do Funcionário</h1>

        <?php if($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if($veiculo_em_uso): ?>
            <!-- Card de Veículo em Uso -->
            <div class="card shadow mb-4 border-left-warning">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-warning">Veículo Atualmente em Uso</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-lg-6">
                            <h5 class="card-title"><?php echo $veiculo_em_uso['modelo'] . ' - ' . $veiculo_em_uso['placa']; ?></h5>
                            <p class="card-text">
                                <strong>Marca:</strong> <?php echo $veiculo_em_uso['marca']; ?><br>
                                <strong>Ano:</strong> <?php echo $veiculo_em_uso['ano']; ?><br>
                                <strong>KM saída:</strong> <?php echo number_format($veiculo_em_uso['km_saida'], 2, ',', '.'); ?><br>
                                <strong>Data/Hora saída:</strong> <?php echo date('d/m/Y H:i', strtotime($veiculo_em_uso['data_saida'])); ?><br>
                                <strong>Motivo:</strong> <?php echo $veiculo_em_uso['motivo']; ?>
                            </p>
                        </div>
                        <div class="col-lg-6 d-flex flex-column justify-content-center align-items-end">
                            <button class="btn btn-success mb-2" data-bs-toggle="modal" data-bs-target="#finalizarUsoModal">
                                <i class="fas fa-check-circle"></i> Finalizar Uso
                            </button>
                            <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#abastecerModal">
                                <i class="fas fa-gas-pump"></i> Registrar Abastecimento
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Card de Selecionar Veículo -->
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Selecionar Veículo para Uso</h6>
                </div>
                <div class="card-body">
                    <?php if(count($veiculos_disponiveis) > 0): ?>
                        <form method="post" action="">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="veiculo_id" class="form-label">Veículo Disponível</label>
                                    <select class="form-select" id="veiculo_id" name="veiculo_id" required>
                                        <option value="">Selecione um veículo</option>
                                        <?php foreach($veiculos_disponiveis as $v): ?>
                                            <option value="<?php echo $v['id']; ?>" data-km="<?php echo $v['km_atual']; ?>">
                                                <?php echo $v['placa'] . ' - ' . $v['modelo'] . ' (' . $v['marca'] . ')'; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-2 mb-3">
                                    <label for="km_saida" class="form-label">KM de Saída</label>
                                    <input type="number" step="0.01" class="form-control" id="km_saida" name="km_saida" required readonly>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="motivo" class="form-label">Motivo de Uso</label>
                                    <input type="text" class="form-control" id="motivo" name="motivo" required placeholder="Descreva o motivo de uso do veículo">
                                </div>
                            </div>
                            <div class="d-grid d-md-flex justify-content-md-end">
                                <button type="submit" name="iniciar_uso" class="btn btn-success">
                                    <i class="fas fa-car"></i> Iniciar Uso do Veículo
                                </button>
                            </div>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle"></i> Não há veículos disponíveis no momento. Por favor, tente novamente mais tarde.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Histórico de Usos -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Meu Histórico de Uso de Veículos</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Data Saída</th>
                                <th>Veículo</th>
                                <th>KM Saída</th>
                                <th>KM Retorno</th>
                                <th>Distância</th>
                                <th>Status</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $historico_usos->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($row['data_saida'])); ?></td>
                                <td><?php echo $row['placa'] . ' - ' . $row['modelo']; ?></td>
                                <td><?php echo number_format($row['km_saida'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php
                                    if($row['data_retorno']) {
                                        echo number_format($row['km_retorno'], 2, ',', '.');
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php
                                    if($row['data_retorno']) {
                                        echo number_format($row['km_retorno'] - $row['km_saida'], 2, ',', '.') . ' km';
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if($row['data_retorno']): ?>
                                        <span class="badge bg-success">Finalizado</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning">Em Uso</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $row['motivo']; ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Finalização de Uso -->
    <?php if($veiculo_em_uso): ?>
    <div class="modal fade" id="finalizarUsoModal" tabindex="-1" aria-labelledby="finalizarUsoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="finalizarUsoModalLabel">Finalizar Uso do Veículo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="" id="formFinalizarUso" onsubmit="return validarFormulario()">
                    <div class="modal-body">
                        <input type="hidden" name="uso_id" value="<?php echo $veiculo_em_uso['id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Veículo</label>
                            <input type="text" class="form-control" value="<?php echo $veiculo_em_uso['placa'] . ' - ' . $veiculo_em_uso['modelo']; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">KM de Saída</label>
                            <input type="text" class="form-control" value="<?php echo number_format($veiculo_em_uso['km_saida'], 2, ',', '.'); ?>" readonly>
                            <input type="hidden" id="km_saida_valor" value="<?php echo $veiculo_em_uso['km_saida']; ?>">
                        </div>

                        <div class="mb-3">
                            <label for="km_retorno" class="form-label">KM de Retorno</label>
                            <input type="number" step="0.01" class="form-control" id="km_retorno" name="km_retorno" required
                                   min="<?php echo $veiculo_em_uso['km_saida'] + 0.01; ?>">
                            <small class="text-muted">Deve ser maior que o KM de saída (<?php echo number_format($veiculo_em_uso['km_saida'], 2, ',', '.'); ?>)</small>
                            <div class="invalid-feedback">
                                A quilometragem de retorno deve ser maior que a quilometragem de saída.
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="finalizar_uso" class="btn btn-success">Finalizar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Abastecimento -->
    <div class="modal fade" id="abastecerModal" tabindex="-1" aria-labelledby="abastecerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="abastecerModalLabel">Registrar Abastecimento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="veiculo_id" value="<?php echo $veiculo_em_uso['veiculo_id']; ?>">

                        <div class="mb-3">
                            <label class="form-label">Veículo</label>
                            <input type="text" class="form-control" value="<?php echo $veiculo_em_uso['placa'] . ' - ' . $veiculo_em_uso['modelo']; ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label for="km" class="form-label">KM Atual</label>
                            <input type="number" step="0.01" class="form-control" id="km" name="km" required
                                   min="<?php echo $veiculo_em_uso['km_saida']; ?>" value="<?php echo $veiculo_em_uso['km_saida']; ?>">
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
                        <button type="submit" name="registrar_abastecimento" class="btn btn-primary">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
        // Função para validar o formulário de finalização
        function validarFormulario() {
            const kmSaida = parseFloat(document.getElementById('km_saida_valor').value);
            const kmRetorno = parseFloat(document.getElementById('km_retorno').value);
            const kmRetornoInput = document.getElementById('km_retorno');

            if (kmRetorno <= kmSaida) {
                kmRetornoInput.classList.add('is-invalid');
                return false;
            } else {
                kmRetornoInput.classList.remove('is-invalid');
                return true;
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            // Validação em tempo real para o KM de retorno
            const kmRetornoInput = document.getElementById('km_retorno');
            if (kmRetornoInput) {
                kmRetornoInput.addEventListener('input', function() {
                    const kmSaida = parseFloat(document.getElementById('km_saida_valor').value);
                    const kmRetorno = parseFloat(this.value);

                    if (kmRetorno <= kmSaida) {
                        this.classList.add('is-invalid');
                    } else {
                        this.classList.remove('is-invalid');
                    }
                });
            }

            // Mostrar KM atual do veículo selecionado
            const veiculoSelect = document.getElementById('veiculo_id');
            if(veiculoSelect) {
                veiculoSelect.addEventListener('change', function() {
                    const option = this.options[this.selectedIndex];
                    if (option.value) {
                        const km = option.getAttribute('data-km');
                        document.getElementById('km_saida').value = km;
                    } else {
                        document.getElementById('km_saida').value = '';
                    }
                });
            }
        });
    </script>
</body>
</html>
