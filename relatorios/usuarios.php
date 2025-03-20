<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include_once '../config/database.php';
include_once '../model/usuario.php';
include_once '../model/abastecimento.php';

$database = new Database();
$db = $database->getConnection();

$usuario = new Usuario($db);
$abastecimento = new Abastecimento($db);

// Obter todos os usuários
$usuarios_lista = $usuario->listar();
$usuarios_data = [];

while ($u = $usuarios_lista->fetch(PDO::FETCH_ASSOC)) {
    // Número de abastecimentos por usuário
    $abas = $abastecimento->listarPorUsuario($u['id']);
    $total_abastecimentos = 0;
    $total_valor = 0;
    $total_litros = 0;
    
    while ($a = $abas->fetch(PDO::FETCH_ASSOC)) {
        $total_abastecimentos++;
        $total_valor += $a['valor'];
        $total_litros += $a['litros'];
    }
    
    $usuarios_data[] = [
        'id' => $u['id'],
        'nome' => $u['nome'],
        'login' => $u['login'],
        'nivel' => $u['nivel'],
        'data_cadastro' => $u['data_cadastro'],
        'total_abastecimentos' => $total_abastecimentos,
        'total_valor' => $total_valor,
        'total_litros' => $total_litros
    ];
}

// Ordenar por número de abastecimentos
usort($usuarios_data, function($a, $b) {
    return $b['total_abastecimentos'] <=> $a['total_abastecimentos'];
});

// Preparar dados para gráficos
$usuarios_labels = [];
$abastecimentos_data = [];
$valor_data = [];

foreach($usuarios_data as $u) {
    if($u['total_abastecimentos'] > 0) { // Só incluir usuários com abastecimentos
        $usuarios_labels[] = $u['nome'];
        $abastecimentos_data[] = $u['total_abastecimentos'];
        $valor_data[] = $u['total_valor'];
    }
}

// Limitar a 10 usuários no gráfico para melhor visualização
if(count($usuarios_labels) > 10) {
    $usuarios_labels = array_slice($usuarios_labels, 0, 10);
    $abastecimentos_data = array_slice($abastecimentos_data, 0, 10);
    $valor_data = array_slice($valor_data, 0, 10);
}

// Obter estatísticas de nível de usuário
$nivel_admin = 0;
$nivel_funcionario = 0;

foreach($usuarios_data as $u) {
    if($u['nivel'] == 'admin') {
        $nivel_admin++;
    } else {
        $nivel_funcionario++;
    }
}

$nivel_labels = ['Administrador', 'Funcionário'];
$nivel_data = [$nivel_admin, $nivel_funcionario];
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Relatório de Usuários</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
<div class="container-fluid p-4">    
            <h1 class="mb-4">Relatório de Usuários</h1>
            
            <!-- Gráficos -->
            <div class="row mb-4">
                <!-- Gráfico de Nível de Usuário -->
                <div class="col-lg-4">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Usuários por Nível</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoNivel" height="250"></canvas>
                        </div>
                    </div>
                </div>
                
                <!-- Gráfico de Abastecimentos por Usuário -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Usuários - Total de Abastecimentos</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoAbastecimentos" height="250"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Gráfico de Valor Total por Usuário -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Usuários - Valor Total (R$)</h6>
                        </div>
                        <div class="card-body">
                            <canvas id="graficoValor" height="150"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Tabela de Detalhes de Usuários -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Detalhes dos Usuários</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nome</th>
                                    <th>Login</th>
                                    <th>Nível</th>
                                    <th>Data de Cadastro</th>
                                    <th>Abastecimentos</th>
                                    <th>Litros</th>
                                    <th>Valor Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($usuarios_data as $item): ?>
                                <tr>
                                    <td><?php echo $item['nome']; ?></td>
                                    <td><?php echo $item['login']; ?></td>
                                    <td>
                                        <?php if($item['nivel'] == 'admin'): ?>
                                            <span class="badge bg-danger">Administrador</span>
                                        <?php else: ?>
                                            <span class="badge bg-info">Funcionário</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo date('d/m/Y', strtotime($item['data_cadastro'])); ?></td>
                                    <td><?php echo $item['total_abastecimentos']; ?></td>
                                    <td><?php echo number_format($item['total_litros'], 2, ',', '.'); ?></td>
                                    <td>R$ <?php echo number_format($item['total_valor'], 2, ',', '.'); ?></td>
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
        const nivelLabels = <?php echo json_encode($nivel_labels); ?>;
        const nivelData = <?php echo json_encode($nivel_data); ?>;
        
        const usuariosLabels = <?php echo json_encode($usuarios_labels); ?>;
        const abastecimentosData = <?php echo json_encode($abastecimentos_data); ?>;
        const valorData = <?php echo json_encode($valor_data); ?>;
        
        // Gráfico de Nível
        const graficoNivel = new Chart(
            document.getElementById('graficoNivel'),
            {
                type: 'pie',
                data: {
                    labels: nivelLabels,
                    datasets: [{
                        data: nivelData,
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)'
                        ],
                        borderWidth: 1
                    }]
                }
            }
        );
        
        // Gráfico de Abastecimentos
        const graficoAbastecimentos = new Chart(
            document.getElementById('graficoAbastecimentos'),
            {
                type: 'bar',
                data: {
                    labels: usuariosLabels,
                    datasets: [{
                        label: 'Quantidade de Abastecimentos',
                        data: abastecimentosData,
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
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
        
        // Gráfico de Valor
        const graficoValor = new Chart(
            document.getElementById('graficoValor'),
            {
                type: 'bar',
                data: {
                    labels: usuariosLabels,
                    datasets: [{
                        label: 'Valor Total (R$)',
                        data: valorData,
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
