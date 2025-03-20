<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include_once '../config/database.php';
include_once '../model/uso_veiculo.php';
include_once '../model/veiculo.php';
include_once '../model/usuario.php';

$database = new Database();
$db = $database->getConnection();

$uso_veiculo = new UsoVeiculo($db);
$veiculo = new Veiculo($db);
$usuario = new Usuario($db);

// Definir período para filtrar relatório
$data_inicio = date('Y-m-01'); // Primeiro dia do mês atual
$data_fim = date('Y-m-t'); // Último dia do mês atual

if(isset($_GET['data_inicio']) && !empty($_GET['data_inicio'])) {
    $data_inicio = $_GET['data_inicio'];
}

if(isset($_GET['data_fim']) && !empty($_GET['data_fim'])) {
    $data_fim = $_GET['data_fim'];
}

// Filtro adicional para veículo específico
$veiculo_filtro = isset($_GET['veiculo_id']) ? $_GET['veiculo_id'] : '';

// Filtro adicional para funcionário específico
$usuario_filtro = isset($_GET['usuario_id']) ? $_GET['usuario_id'] : '';

// Consulta SQL direta em vez de função personalizada
$sql = "SELECT uv.*, v.placa, v.modelo, u.nome as nome_usuario
        FROM uso_veiculos uv
        JOIN veiculos v ON uv.veiculo_id = v.id
        JOIN usuarios u ON uv.usuario_id = u.id
        WHERE DATE(uv.data_saida) BETWEEN :data_inicio AND :data_fim";

// Adicionar filtros extras se especificados
if(!empty($veiculo_filtro)) {
    $sql .= " AND uv.veiculo_id = :veiculo_id";
}

if(!empty($usuario_filtro)) {
    $sql .= " AND uv.usuario_id = :usuario_id";
}

$sql .= " ORDER BY uv.data_saida DESC";

$stmt = $db->prepare($sql);
$stmt->bindParam(':data_inicio', $data_inicio);
$stmt->bindParam(':data_fim', $data_fim);

if(!empty($veiculo_filtro)) {
    $stmt->bindParam(':veiculo_id', $veiculo_filtro);
}

if(!empty($usuario_filtro)) {
    $stmt->bindParam(':usuario_id', $usuario_filtro);
}

$stmt->execute();

// Carregar lista de veículos para o filtro
$veiculos_lista = $veiculo->listar();

// Carregar lista de usuários para o filtro
$usuarios_lista = $usuario->listar();

// Calcular estatísticas
$estatisticas = [
    'total_usos' => 0,
    'total_km' => 0,
    'media_km_por_uso' => 0,
    'total_horas' => 0
];

// Array para armazenar os dados para a tabela
$usos_dados = [];

// Processar os dados e calcular estatísticas
while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $estatisticas['total_usos']++;
    
    // Calcular duração em minutos
    $duracao_minutos = 0;
    if($row['data_retorno']) {
        $data_saida = new DateTime($row['data_saida']);
        $data_retorno = new DateTime($row['data_retorno']);
        $diff = $data_retorno->diff($data_saida);
        $duracao_minutos = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    } else {
        $data_saida = new DateTime($row['data_saida']);
        $data_atual = new DateTime();
        $diff = $data_atual->diff($data_saida);
        $duracao_minutos = ($diff->days * 24 * 60) + ($diff->h * 60) + $diff->i;
    }
    
    // Adicionar duração calculada ao registro
    $row['duracao_minutos'] = $duracao_minutos;
    
    // Se já retornou, calcular km percorrido
    if($row['data_retorno']) {
        $km_percorrido = $row['km_retorno'] - $row['km_saida'];
        $estatisticas['total_km'] += $km_percorrido;
        
        // Calcular duração em horas
        $duracao_horas = $duracao_minutos / 60;
        $estatisticas['total_horas'] += $duracao_horas;
    }
    
    // Adicionar à array de dados
    $usos_dados[] = $row;
}

// Calcular média de km por uso se houver registros
if($estatisticas['total_usos'] > 0 && $estatisticas['total_km'] > 0) {
    $estatisticas['media_km_por_uso'] = $estatisticas['total_km'] / $estatisticas['total_usos'];
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Relatório de Uso de Veículos</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.2.2/css/buttons.bootstrap5.min.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid p-4">
        <h1 class="mb-4">Relatório de Uso de Veículos</h1>
        
        <!-- Filtros -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Filtros</h6>
            </div>
            <div class="card-body">
                <form method="get" action="" class="row g-3">
                    <!-- Filtro de período -->
                    <div class="col-md-3">
                        <label for="data_inicio" class="form-label">Data Inicial</label>
                        <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $data_inicio; ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="data_fim" class="form-label">Data Final</label>
                        <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $data_fim; ?>">
                    </div>
                    
                    <!-- Filtro de veículo -->
                    <div class="col-md-3">
                        <label for="veiculo_id" class="form-label">Veículo</label>
                        <select class="form-select" id="veiculo_id" name="veiculo_id">
                            <option value="">Todos os veículos</option>
                            <?php 
                            $veiculos_temp = $veiculos_lista;
                            while($v = $veiculos_temp->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                                <option value="<?php echo $v['id']; ?>" <?php echo ($veiculo_filtro == $v['id']) ? 'selected' : ''; ?>>
                                    <?php echo $v['placa'] . ' - ' . $v['modelo']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Filtro de usuário -->
                    <div class="col-md-3">
                        <label for="usuario_id" class="form-label">Funcionário</label>
                        <select class="form-select" id="usuario_id" name="usuario_id">
                            <option value="">Todos os funcionários</option>
                            <?php 
                            $usuarios_temp = $usuarios_lista;
                            while($u = $usuarios_temp->fetch(PDO::FETCH_ASSOC)): 
                            ?>
                                <option value="<?php echo $u['id']; ?>" <?php echo ($usuario_filtro == $u['id']) ? 'selected' : ''; ?>>
                                    <?php echo $u['nome']; ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <!-- Botões de filtro -->
                    <div class="col-md-12 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="?data_inicio=<?php echo date('Y-m-01'); ?>&data_fim=<?php echo date('Y-m-t'); ?>" class="btn btn-secondary ms-2">Mês Atual</a>
                        <a href="?data_inicio=<?php echo date('Y-01-01'); ?>&data_fim=<?php echo date('Y-12-31'); ?>" class="btn btn-info ms-2">Ano Atual</a>
                        <a href="?data_inicio=<?php echo date('Y-m-d', strtotime('-7 days')); ?>&data_fim=<?php echo date('Y-m-d'); ?>" class="btn btn-success ms-2">Últimos 7 dias</a>
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
                                    Total de Usos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $estatisticas['total_usos']; ?></div>
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
                                    Total KM Percorridos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($estatisticas['total_km'], 2, ',', '.'); ?> km</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-road fa-2x text-gray-300"></i>
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
                                    Média KM por Uso</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($estatisticas['media_km_por_uso'], 2, ',', '.'); ?> km</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-tachometer-alt fa-2x text-gray-300"></i>
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
                                    Total Horas de Uso</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($estatisticas['total_horas'], 1, ',', '.'); ?> h</div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-clock fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Tabela de Detalhes de Uso -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Detalhes de Uso de Veículos</h6>
                <div class="dropdown no-arrow">
                    <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="dropdownMenuLink">
                        <li><a class="dropdown-item" href="#" id="exportPDF"><i class="fas fa-file-pdf me-2"></i>Exportar PDF</a></li>
                        <li><a class="dropdown-item" href="#" id="exportExcel"><i class="fas fa-file-excel me-2"></i>Exportar Excel</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="#" id="printReport"><i class="fas fa-print me-2"></i>Imprimir</a></li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Data</th>
                                <th>Hora Início</th>
                                <th>Hora Final</th>
                                <th>Veículo</th>
                                <th>Funcionário</th>
                                <th>KM Inicial</th>
                                <th>KM Final</th>
                                <th>KM Percorrido</th>
                                <th>Duração</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($usos_dados as $uso): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($uso['data_saida'])); ?></td>
                                <td><?php echo date('H:i', strtotime($uso['data_saida'])); ?></td>
                                <td>
                                    <?php 
                                    if($uso['data_retorno']) {
                                        echo date('H:i', strtotime($uso['data_retorno']));
                                    } else {
                                        echo '<span class="badge bg-warning">Em uso</span>';
                                    }
                                    ?>
                                </td>
                                <td><?php echo $uso['placa'] . ' - ' . $uso['modelo']; ?></td>
                                <td><?php echo $uso['nome_usuario']; ?></td>
                                <td><?php echo number_format($uso['km_saida'], 2, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    if($uso['data_retorno']) {
                                        echo number_format($uso['km_retorno'], 2, ',', '.');
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    if($uso['data_retorno']) {
                                        echo number_format($uso['km_retorno'] - $uso['km_saida'], 2, ',', '.');
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php 
                                    // Formatar duração
                                    $horas = floor($uso['duracao_minutos'] / 60);
                                    $minutos = $uso['duracao_minutos'] % 60;
                                    
                                    if($uso['data_retorno']) {
                                        echo $horas . 'h ' . $minutos . 'm';
                                    } else {
                                        echo 'Em andamento';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if($uso['data_retorno']): ?>
                                        <span class="badge bg-success">Finalizado</span>
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
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <script src="../assets/js/scripts.js"></script>
    
<script>
        $(document).ready(function() {
            // Verifica se o DataTable já foi inicializado
            if (!$.fn.DataTable.isDataTable('#dataTable')) {
                // Inicializar DataTable apenas se ainda não estiver inicializado
                var table = $('#dataTable').DataTable({
                    language: {
                        url: '//cdn.datatables.net/plug-ins/1.10.24/i18n/Portuguese-Brasil.json'
                    },
                    responsive: true,
                    order: [[0, 'desc'], [1, 'desc']]
                });
            } else {
                // Se já estiver inicializado, apenas configura a ordem
                var table = $('#dataTable').DataTable();
                table.order([[0, 'desc'], [1, 'desc']]).draw();
            }
            
            // Para futura implementação de exportação
            $('#exportExcel').on('click', function() {
                alert('Funcionalidade em desenvolvimento');
            });
            
            $('#exportPDF').on('click', function() {
                alert('Funcionalidade em desenvolvimento');
            });
            
            $('#printReport').on('click', function() {
                window.print();
            });
        });
    </script></body>
</html>
