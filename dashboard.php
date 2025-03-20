<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: index.php");
    exit;
}

include_once 'config/database.php';
include_once 'model/veiculo.php';
include_once 'model/abastecimento.php';
include_once 'model/manutencao.php';

$database = new Database();
$db = $database->getConnection();

$veiculo = new Veiculo($db);
$abastecimento = new Abastecimento($db);
$manutencao = new Manutencao($db);

// Contar veículos
$total_veiculos = $veiculo->contarTotal();
$veiculos_manutencao = $veiculo->contarEmManutencao();
$veiculos_disponiveis = $total_veiculos - $veiculos_manutencao;

// Somar abastecimentos do mês
$total_abastecimento = $abastecimento->totalMesAtual();

// Somar manutenções do mês
$total_manutencao = $manutencao->totalMesAtual();

// Obter últimos 5 abastecimentos
$ultimos_abastecimentos = $abastecimento->listarUltimos(5);

// Obter últimas 5 manutenções
$ultimas_manutencoes = $manutencao->listarUltimas(5);

// Dados para gráficos
$abastecimentos_por_mes = $abastecimento->totalPorMes();
$manutencoes_por_tipo = $manutencao->totalPorTipo();

// Preparar dados para o gráfico de abastecimentos
$abastecimento_labels = [];
$abastecimento_values = [];

foreach($abastecimentos_por_mes as $aba) {
    $abastecimento_labels[] = $aba['mes'] . '/' . $aba['ano'];
    $abastecimento_values[] = $aba['total'];
}

// Preparar dados para o gráfico de manutenções
$manutencao_labels = [];
$manutencao_values = [];

foreach($manutencoes_por_tipo as $man) {
    $manutencao_labels[] = $man['tipo'];
    $manutencao_values[] = $man['total_valor'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <?php include 'includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <h1 class="mb-4">Dashboard</h1>
        
        <!-- Cards de resumo -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total de Veículos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $total_veiculos; ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-car fa-2x text-gray-300"></i>
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
                                    Veículos Disponíveis</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $veiculos_disponiveis; ?></div>
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
                                    Abastecimentos (Mês)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($total_abastecimento, 2, ',', '.'); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-gas-pump fa-2x text-gray-300"></i>
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
                                    Manutenções (Mês)</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($total_manutencao, 2, ',', '.'); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tools fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mb-4">
            <!-- Gráfico de Abastecimentos -->
            <div class="col-xl-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Abastecimentos por Mês</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-area">
                            <canvas id="abastecimentoChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráfico de Manutenções -->
            <div class="col-xl-6">
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Manutenções por Tipo</h6>
                    </div>
                    <div class="card-body">
                        <div class="chart-pie">
                            <canvas id="manutencaoChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Últimos Abastecimentos e Manutenções -->
        <div class="row">
            <!-- Últimos Abastecimentos -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Últimos Abastecimentos</h6>
                        <a href="abastecimentos/cadastro.php" class="btn btn-sm btn-primary">
                            Ver Todos
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Veículo</th>
                                        <th>Usuário</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ultimos_abastecimentos as $a): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($a['data'])); ?></td>
                                        <td><?php echo $a['placa']; ?></td>
                                        <td><?php echo $a['nome_usuario']; ?></td>
                                        <td>R$ <?php echo number_format($a['valor'], 2, ',', '.'); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Últimas Manutenções -->
            <div class="col-lg-6 mb-4">
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">Últimas Manutenções</h6>
                        <a href="manutencoes/cadastro.php" class="btn btn-sm btn-primary">
                            Ver Todas
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Veículo</th>
                                        <th>Tipo</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($ultimas_manutencoes as $m): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($m['data_inicio'])); ?></td>
                                        <td><?php echo $m['placa']; ?></td>
                                        <td><?php echo $m['tipo']; ?></td>
                                        <td>
                                            <?php if($m['finalizada']): ?>
                                                <span class="badge bg-success">Finalizada</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning">Em andamento</span>
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
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.0/chart.min.js"></script>
    <script src="assets/js/scripts.js"></script>
    
    <script>
        // Dados para os gráficos
        const abastecimentoData = {
            labels: <?php echo json_encode($abastecimento_labels); ?>,
            values: <?php echo json_encode($abastecimento_values); ?>
        };
        
        const manutencaoData = {
            labels: <?php echo json_encode($manutencao_labels); ?>,
            values: <?php echo json_encode($manutencao_values); ?>
        };
        
        // Inicializar gráficos quando o DOM estiver pronto
        document.addEventListener('DOMContentLoaded', function() {
            const abastecimentoCtx = document.getElementById('abastecimentoChart').getContext('2d');
            const manutencaoCtx = document.getElementById('manutencaoChart').getContext('2d');
            
            // Gráfico de Abastecimentos
            new Chart(abastecimentoCtx, {
                type: 'bar',
                data: {
                    labels: abastecimentoData.labels,
                    datasets: [{
                        label: 'Valor (R$)',
                        data: abastecimentoData.values,
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
            });
            
            // Gráfico de Manutenções
            new Chart(manutencaoCtx, {
                type: 'pie',
                data: {
                    labels: manutencaoData.labels,
                    datasets: [{
                        label: 'Valor (R$)',
                        data: manutencaoData.values,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)',
                            'rgba(255, 206, 86, 0.2)',
                            'rgba(75, 192, 192, 0.2)',
                            'rgba(153, 102, 255, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)',
                            'rgba(153, 102, 255, 1)'
                        ],
                        borderWidth: 1
                    }]
                }
            });
        });
    </script>
</body>
</html>
