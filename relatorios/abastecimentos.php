<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include_once '../config/database.php';
include_once '../model/abastecimento.php';
include_once '../model/veiculo.php';

$database = new Database();
$db = $database->getConnection();

$abastecimento = new Abastecimento($db);
$veiculo = new Veiculo($db);

// Definir período para filtrar relatório
$data_inicio = date('Y-m-01'); // Primeiro dia do mês atual
$data_fim = date('Y-m-t'); // Último dia do mês atual

if(isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $data_inicio = $_GET['data_inicio'];
}

if(isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $data_fim = $_GET['data_fim'];
}

// Obter abastecimentos filtrados por período
$abastecimentos_lista = $abastecimento->listarPorPeriodo($data_inicio, $data_fim);

// Dados para gráficos
$total_por_combustivel = $abastecimento->totalLitrosPorTipo();
$media_preco_por_mes = $abastecimento->mediaPorLitroPorMes();

// Preparar dados para o gráfico de combustível
$combustivel_labels = [];
$combustivel_data = [];

foreach($total_por_combustivel as $item) {
    switch($item['tipo_combustivel']) {
        case 'gasolina':
            $combustivel_labels[] = 'Gasolina';
            break;
        case 'etanol':
            $combustivel_labels[] = 'Etanol';
            break;
        case 'diesel':
            $combustivel_labels[] = 'Diesel';
            break;
        case 'gnv':
            $combustivel_labels[] = 'GNV';
            break;
    }
    $combustivel_data[] = $item['total_litros'];
}

// Preparar dados para o gráfico de média de preço
$meses_labels = [];
$gasolina_data = [];
$etanol_data = [];
$diesel_data = [];
$gnv_data = [];

foreach($media_preco_por_mes as $item) {
    $mes_ano = $item['mes'] . '/' . $item['ano'];
    
    if(!in_array($mes_ano, $meses_labels)) {
        $meses_labels[] = $mes_ano;
    }
    
    switch($item['tipo_combustivel']) {
        case 'gasolina':
            $gasolina_data[$mes_ano] = $item['media_preco'];
            break;
        case 'etanol':
            $etanol_data[$mes_ano] = $item['media_preco'];
            break;
        case 'diesel':
            $diesel_data[$mes_ano] = $item['media_preco'];
            break;
        case 'gnv':
            $gnv_data[$mes_ano] = $item['media_preco'];
            break;
    }
}

// Organizar dados
$gasolina_values = [];
$etanol_values = [];
$diesel_values = [];
$gnv_values = [];

foreach($meses_labels as $mes) {
    $gasolina_values[] = isset($gasolina_data[$mes]) ? $gasolina_data[$mes] : null;
    $etanol_values[] = isset($etanol_data[$mes]) ? $etanol_data[$mes] : null;
    $diesel_values[] = isset($diesel_data[$mes]) ? $diesel_data[$mes] : null;
    $gnv_values[] = isset($gnv_data[$mes]) ? $gnv_data[$mes] : null;
}

// Calcular estatísticas do período filtrado
$estatisticas = [
    'total_abastecimentos' => 0,
    'total_litros' => 0,
    'total_valor' => 0,
    'media_valor_litro' => 0
];

$abastecimentos_periodo = [];

while($row = $abastecimentos_lista->fetch(PDO::FETCH_ASSOC)) {
    $estatisticas['total_abastecimentos']++;
    $estatisticas['total_litros'] += $row['litros'];
    $estatisticas['total_valor'] += $row['valor'];
    
    $abastecimentos_periodo[] = $row;
}

if($estatisticas['total_litros'] > 0) {
    $estatisticas['media_valor_litro'] = $estatisticas['total_valor'] / $estatisticas['total_litros'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Relatório de Abastecimentos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <h1 class="mb-4">Relatório de Abastecimentos</h1>
        
        <!-- Filtro de Período -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filtrar por Período</h6>
            </div>
            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <div class="col-md-4">
                        <label for="data_inicio" class="form-label">Data Inicial</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
                    </div>
                    <div class="col-md-4">
                        <label for="data_fim" class="form-label">Data Final</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="?data_inicio=<?php echo date('Y-m-01'); ?>&data_fim=<?php echo date('Y-m-t'); ?>" class="btn btn-secondary ms-2">Mês Atual</a>
                        <a href="?data_inicio=<?php echo date('Y-01-01'); ?>&data_fim=<?php echo date('Y-12-31'); ?>" class="btn btn-info ms-2">Ano Atual</a>
                    </div>
                </form>
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
                                    Total de Abastecimentos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estatisticas['total_abastecimentos']; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-gas-pump fa-2x text-gray-300"></i>
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
                                    Total de Litros</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($estatisticas['total_litros'], 2, ',', '.'); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tint fa-2x text-gray-300"></i>
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
                                    Total Gasto (R$)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($estatisticas['total_valor'], 2, ',', '.'); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-info shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                    Valor Médio por Litro</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($estatisticas['media_valor_litro'], 2, ',', '.'); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Gráficos -->
        <div class="row mb-4">
            <!-- Gráfico de Combustível -->
            <div class="col-lg-5">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Litros por Tipo de Combustível</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoCombustivel" height="260"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico de Preço Médio -->
            <div class="col-lg-7">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Preço Médio por Litro (R$)</h6>
                    </div>
                    <div class="card-body">
                        <canvas id="graficoPreco" height="260"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabela de Detalhes de Abastecimentos -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Detalhes dos Abastecimentos</h6>
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
                                <th>Data</th>
                                <th>Veículo</th>
                                <th>Usuário</th>
                                <th>KM</th>
                                <th>Combustível</th>
                                <th>Litros</th>
                                <th>Valor (R$)</th>
                                <th>Preço/Litro</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($abastecimentos_periodo as $item): ?>
                            <tr>
                                <td><?php echo date('d/m/Y H:i', strtotime($item['data'])); ?></td>
                                <td><?php echo $item['placa'] . ' - ' . $item['modelo']; ?></td>
                                <td><?php echo $item['nome_usuario']; ?></td>
                                <td><?php echo number_format($item['km'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    switch($item['tipo_combustivel']) {
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
                                            echo $item['tipo_combustivel'];
                                    }
                                    ?>
                                </td>
                                <td><?php echo number_format($item['litros'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                                <td>R$ <?php echo number_format($item['valor'] / $item['litros'], 2, ',', '.'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
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
        const combustivelLabels = <?php echo json_encode($combustivel_labels); ?>;
        const combustivelData = <?php echo json_encode($combustivel_data); ?>;
        
        const mesesLabels = <?php echo json_encode($meses_labels); ?>;
        const gasolinaValues = <?php echo json_encode($gasolina_values); ?>;
        const etanolValues = <?php echo json_encode($etanol_values); ?>;
        const dieselValues = <?php echo json_encode($diesel_values); ?>;
        const gnvValues = <?php echo json_encode($gnv_values); ?>;
        
        // Gráfico de Combustível
        const graficoCombustivel = new Chart(
            document.getElementById('graficoCombustivel'),
            {
                type: 'pie',
                data: {
                    labels: combustivelLabels,
                    datasets: [{
                        data: combustivelData,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(54, 162, 235, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(54, 162, 235, 1)'
                        ],
                        borderWidth: 1
                    }]
                }
            }
        );
        
        // Gráfico de Preço Médio
        const graficoPreco = new Chart(
            document.getElementById('graficoPreco'),
            {
                type: 'line',
                data: {
                    labels: mesesLabels,
                    datasets: [
                        {
                            label: 'Gasolina',
                            data: gasolinaValues,
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderColor: 'rgba(255, 99, 132, 1)',
                            borderWidth: 1,
                            tension: 0.2
                        },
                        {
                            label: 'Etanol',
                            data: etanolValues,
                            backgroundColor: 'rgba(75, 192, 192, 0.2)',
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 1,
                            tension: 0.2
                        },
                        {
                            label: 'Diesel',
                            data: dieselValues,
                            backgroundColor: 'rgba(255, 206, 86, 0.2)',
                            borderColor: 'rgba(255, 206, 86, 1)',
                            borderWidth: 1,
                            tension: 0.2
                        },
                        {
                            label: 'GNV',
                            data: gnvValues,
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderColor: 'rgba(54, 162, 235, 1)',
                            borderWidth: 1,
                            tension: 0.2
                        }
                    ]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: false
                        }
                    }
                }
            }
        );
    </script>
</body>
</html>
