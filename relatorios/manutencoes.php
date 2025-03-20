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

// Filtrar por status
$status = isset($_GET['status']) ? $_GET['status'] : 'todos';

// Obter estatísticas
$total_por_tipo = $manutencao->totalPorTipo();
$custo_por_veiculo = $manutencao->mediaCustoPorVeiculo();
$manutencoes_em_andamento = $manutencao->listarEmAndamento();

// Obter manutenções com base no filtro
if($status == 'em_andamento') {
    $manutencoes_lista = $manutencao->listarEmAndamento();
} elseif($status == 'finalizadas') {
    $manutencoes_lista = $manutencao->listarFinalizadas();
} else {
    $manutencoes_lista = $manutencao->listar();
}

// Preparar dados para o gráfico de tipos
$tipo_labels = [];
$tipo_data = [];
$tipo_valores = [];

foreach($total_por_tipo as $item) {
    $tipo_labels[] = $item['tipo'];
    $tipo_data[] = $item['total_manutencoes'];
    $tipo_valores[] = $item['total_valor'];
}

// Limitar a 7 tipos para melhor visualização
if(count($tipo_labels) > 7) {
    $tipo_labels = array_slice($tipo_labels, 0, 7);
    $tipo_data = array_slice($tipo_data, 0, 7);
    $tipo_valores = array_slice($tipo_valores, 0, 7);
}

// Calcular estatísticas
$estatisticas = [
    'total_manutencoes' => 0,
    'manutencoes_finalizadas' => 0,
    'manutencoes_andamento' => 0,
    'valor_total' => 0,
    'media_valor' => 0
];

$manutencoes_array = [];
$total_manutencoes = 0;
$total_finalizadas = 0;
$total_em_andamento = 0;
$total_valor = 0;

// Processar todas as manutenções para estatísticas
$todas_manutencoes = $manutencao->listar();
while($row = $todas_manutencoes->fetch(PDO::FETCH_ASSOC)) {
    $total_manutencoes++;
    
    if($row['finalizada']) {
        $total_finalizadas++;
        $total_valor += $row['valor'];
    } else {
        $total_em_andamento++;
    }
    
    $manutencoes_array[] = $row;
}

$estatisticas['total_manutencoes'] = $total_manutencoes;
$estatisticas['manutencoes_finalizadas'] = $total_finalizadas;
$estatisticas['manutencoes_andamento'] = $total_em_andamento;
$estatisticas['valor_total'] = $total_valor;

if($total_finalizadas > 0) {
    $estatisticas['media_valor'] = $total_valor / $total_finalizadas;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Relatório de Manutenções</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
		<div class="container-fluid p-4">    
            <h1 class="mb-4">Relatório de Manutenções</h1>
            
            <!-- Filtros -->
            <div class="mb-4">
                <div class="btn-group" role="group" aria-label="Filtros de status">
                    <a href="?status=todos" class="btn btn-<?php echo ($status == 'todos') ? 'primary' : 'outline-primary'; ?>">Todas</a>
                    <a href="?status=em_andamento" class="btn btn-<?php echo ($status == 'em_andamento') ? 'warning' : 'outline-warning'; ?>">Em Andamento</a>
                    <a href="?status=finalizadas" class="btn btn-<?php echo ($status == 'finalizadas') ? 'success' : 'outline-success'; ?>">Finalizadas</a>
                </div>
            </div>
            
            <!-- Cards de Estatísticas -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total de Manutenções</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estatisticas['total_manutencoes']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-tools fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Manutenções Finalizadas</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estatisticas['manutencoes_finalizadas']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Em Andamento</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estatisticas['manutencoes_andamento']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-danger shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                        Valor Total (R$)</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($estatisticas['valor_total'], 2, ',', '.'); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráficos -->
            <div class="row mb-4">
                <!-- Gráfico de Tipos -->
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Manutenções por Tipo</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoTipos" height="300"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico de Valores por Tipo -->
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Valor Total por Tipo (R$)</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoValores" height="300"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Veículos por Custo de Manutenção -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Veículos - Custo de Manutenção</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Veículo</th>
                                    <th>Placa</th>
                                    <th>Total de Manutenções</th>
                                    <th>Valor Total</th>
                                    <th>Valor Médio</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $count = 0;
                                foreach($custo_por_veiculo as $veiculo):
                                    $count++;
                                    if($count > 7) break; // Mostrar apenas os 7 primeiros
                                ?>
                                <tr>
                                    <td><?php echo $veiculo['modelo']; ?></td>
                                    <td><?php echo $veiculo['placa']; ?></td>
                                    <td><?php echo $veiculo['total_manutencoes']; ?></td>
                                    <td>R$ <?php echo number_format($veiculo['total_valor'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($veiculo['media_valor'], 2, ',', '.'); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de Manutenções -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <?php 
                        if($status == 'todos') echo "Todas as Manutenções";
                        elseif($status == 'em_andamento') echo "Manutenções em Andamento";
                        else echo "Manutenções Finalizadas";
                        ?>
                    </h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                            <li><a class="dropdown-item" href="#">Exportar PDF</a></li>
                            <li><a class="dropdown-item" href="#">Exportar Excel</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="#">Imprimir</a></li>
                        </ul>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Data Início</th>
                                    <th>Veículo</th>
                                    <th>Tipo</th>
                                    <th>KM</th>
                                    <th>Status</th>
                                    <th>Data Fim</th>
                                    <th>Valor (R$)</th>
                                    <th>Detalhes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while($row = $manutencoes_lista->fetch(PDO::FETCH_ASSOC)): ?>
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
                                        <button type="button" class="btn btn-sm btn-info ver-detalhes" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#detalhesModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-veiculo="<?php echo $row['placa'] . ' - ' . $row['modelo']; ?>"
                                                data-tipo="<?php echo $row['tipo']; ?>"
                                                data-descricao="<?php echo $row['descricao']; ?>">
                                            <i class="fas fa-eye"></i>
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
    </div>
    
    <!-- Modal de Detalhes -->
    <div class="modal fade" id="detalhesModal" tabindex="-1" aria-labelledby="detalhesModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="detalhesModalLabel">Detalhes da Manutenção</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 id="detalhes-veiculo" class="fw-bold"></h6>
                    <p id="detalhes-tipo" class="text-muted"></p>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Descrição do Serviço:</label>
                        <div id="detalhes-descricao" class="p-2 border rounded bg-light"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    <script>
        // Dados para os gráficos
        const tipoLabels = <?php echo json_encode($tipo_labels); ?>;
        const tipoData = <?php echo json_encode($tipo_data); ?>;
        const tipoValores = <?php echo json_encode($tipo_valores); ?>;
        
        // Gráfico de Tipos
        const graficoTipos = new Chart(
            document.getElementById('graficoTipos'),
            {
                type: 'bar',
                data: {
                    labels: tipoLabels,
                    datasets: [{
                        label: 'Quantidade',
                        data: tipoData,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );
        
        // Gráfico de Valores
        const graficoValores = new Chart(
            document.getElementById('graficoValores'),
            {
                type: 'bar',
                data: {
                    labels: tipoLabels,
                    datasets: [{
                        label: 'Valor Total (R$)',
                        data: tipoValores,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            }
        );
        
        // Modal de detalhes
        document.addEventListener('DOMContentLoaded', function() {
            const botoesDetalhes = document.querySelectorAll('.ver-detalhes');
            
            botoesDetalhes.forEach(botao => {
                botao.addEventListener('click', function() {
                    const veiculo = this.getAttribute('data-veiculo');
                    const tipo = this.getAttribute('data-tipo');
                    const descricao = this.getAttribute('data-descricao');
                    
                    document.getElementById('detalhes-veiculo').textContent = veiculo;
                    document.getElementById('detalhes-tipo').textContent = 'Tipo: ' + tipo;
                    
                    if(descricao) {
                        document.getElementById('detalhes-descricao').textContent = descricao;
                    } else {
                        document.getElementById('detalhes-descricao').textContent = 'Manutenção em andamento, sem descrição.';
                    }
                });
            });
        });
    </script>
</body>
</html>
