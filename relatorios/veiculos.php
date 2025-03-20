<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include_once '../config/database.php';
include_once '../model/veiculo.php';
include_once '../model/abastecimento.php';
include_once '../model/manutencao.php';

$database = new Database();
$db = $database->getConnection();

$veiculo = new Veiculo($db);
$abastecimento = new Abastecimento($db);
$manutencao = new Manutencao($db);

// Obter estatísticas
$estatisticas = $veiculo->obterEstatisticas();
$km_media = $veiculo->obterKmMedia();

// Preparar dados para gráficos
$status_labels = [];
$status_data = [];
foreach($estatisticas['por_status'] as $item) {
    switch($item['status']) {
        case 'disponivel':
            $status_labels[] = 'Disponível';
            break;
        case 'em_uso':
            $status_labels[] = 'Em Uso';
            break;
        case 'manutencao':
            $status_labels[] = 'Em Manutenção';
            break;
    }
    $status_data[] = $item['total'];
}

$marca_labels = [];
$marca_data = [];
foreach($estatisticas['por_marca'] as $item) {
    $marca_labels[] = $item['marca'];
    $marca_data[] = $item['total'];
}

$ano_labels = [];
$ano_data = [];
foreach($estatisticas['por_ano'] as $item) {
    $ano_labels[] = $item['ano'];
    $ano_data[] = $item['total'];
}

// Obter veículos com mais gastos em abastecimento
$veiculos_lista = $veiculo->listar();
$gastos_abastecimento = [];
$gastos_manutencao = [];

while ($v = $veiculos_lista->fetch(PDO::FETCH_ASSOC)) {
    // Gastos com abastecimento
    $abas = $abastecimento->listarPorVeiculo($v['id']);
    $total_abastecimento = 0;
    
    while ($a = $abas->fetch(PDO::FETCH_ASSOC)) {
        $total_abastecimento += $a['valor'];
    }
    
    $gastos_abastecimento[] = [
        'id' => $v['id'],
        'placa' => $v['placa'],
        'modelo' => $v['modelo'],
        'marca' => $v['marca'],
        'valor' => $total_abastecimento
    ];
    
    // Gastos com manutenção
    $mans = $manutencao->listarPorVeiculo($v['id']);
    $total_manutencao = 0;
    
    while ($m = $mans->fetch(PDO::FETCH_ASSOC)) {
        if($m['finalizada']) {
            $total_manutencao += $m['valor'];
        }
    }
    
    $gastos_manutencao[] = [
        'id' => $v['id'],
        'placa' => $v['placa'],
        'modelo' => $v['modelo'],
        'marca' => $v['marca'],
        'valor' => $total_manutencao
    ];
}

// Ordenar por valor
usort($gastos_abastecimento, function($a, $b) {
    return $b['valor'] <=> $a['valor'];
});

usort($gastos_manutencao, function($a, $b) {
    return $b['valor'] <=> $a['valor'];
});

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Relatório de Veículos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
	<div class="container-fluid p-4">    
            <h1 class="mb-4">Relatório de Veículos</h1>
            
            <!-- Gráficos -->
            <div class="row mb-4">
                <!-- Gráfico de Status -->
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Veículos por Status</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoStatus" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico de Marcas -->
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Veículos por Marca</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoMarcas" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico de Anos -->
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Veículos por Ano</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoAnos" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Top Veículos por Gasto -->
            <div class="row mb-4">
                <!-- Top Gastos com Abastecimento -->
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Veículos - Gastos com Abastecimento</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Veículo</th>
                                            <th>Placa</th>
                                            <th>Marca</th>
                                            <th>Total Gasto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $count = 0;
                                        foreach($gastos_abastecimento as $item):
                                            $count++;
                                            if($count > 5) break; // Top 5
                                        ?>
                                        <tr>
                                            <td><?php echo $item['modelo']; ?></td>
                                            <td><?php echo $item['placa']; ?></td>
                                            <td><?php echo $item['marca']; ?></td>
                                            <td>R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Gastos com Manutenção -->
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Veículos - Gastos com Manutenção</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Veículo</th>
                                            <th>Placa</th>
                                            <th>Marca</th>
                                            <th>Total Gasto</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $count = 0;
                                        foreach($gastos_manutencao as $item):
                                            $count++;
                                            if($count > 5) break; // Top 5
                                        ?>
                                        <tr>
                                            <td><?php echo $item['modelo']; ?></td>
                                            <td><?php echo $item['placa']; ?></td>
                                            <td><?php echo $item['marca']; ?></td>
                                            <td>R$ <?php echo number_format($item['valor'], 2, ',', '.'); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de Quilometragem -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Detalhes de Quilometragem</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Veículo</th>
                                    <th>Placa</th>
                                    <th>Marca</th>
                                    <th>KM Atual</th>
                                    <th>Abastecimentos</th>
                                    <th>Manutenções</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($km_media as $item): ?>
                                <tr>
                                    <td><?php echo $item['modelo']; ?></td>
                                    <td><?php echo $item['placa']; ?></td>
                                    <td><?php echo $item['marca']; ?></td>
                                    <td><?php echo number_format($item['km_atual'], 2, ',', '.'); ?> km</td>
                                    <td><?php echo $item['total_abastecimentos']; ?></td>
                                    <td><?php echo $item['total_manutencoes']; ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
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
        const statusLabels = <?php echo json_encode($status_labels); ?>;
        const statusData = <?php echo json_encode($status_data); ?>;
        
        const marcaLabels = <?php echo json_encode($marca_labels); ?>;
        const marcaData = <?php echo json_encode($marca_data); ?>;
        
        const anoLabels = <?php echo json_encode($ano_labels); ?>;
        const anoData = <?php echo json_encode($ano_data); ?>;
        
        // Gráfico de Status
        const graficoStatus = new Chart(
            document.getElementById('graficoStatus'),
            {
                type: 'pie',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: [
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(255, 99, 132, 0.2)'
                        ],
                        borderColor: [
                            'rgba(75, 192, 192, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(255, 99, 132, 1)'
                        ],
                        borderWidth: 1
                    }]
                }
            }
        );
        
        // Gráfico de Marcas
        const graficoMarcas = new Chart(
            document.getElementById('graficoMarcas'),
            {
                type: 'bar',
                data: {
                    labels: marcaLabels,
                    datasets: [{
                        label: 'Quantidade',
                        data: marcaData,
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
        
        // Gráfico de Anos
        const graficoAnos = new Chart(
            document.getElementById('graficoAnos'),
            {
                type: 'bar',
                data: {
                    labels: anoLabels,
                    datasets: [{
                        label: 'Quantidade',
                        data: anoData,
                        backgroundColor: 'rgba(153, 102, 255, 0.2)',
                        borderColor: 'rgba(153, 102, 255, 1)',
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
    </script>
</body>
</html>
