<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_nivel'] != 'admin') {
    header("Location: ../index.php");
    exit;
}

include_once '../config/database.php';
include_once '../model/veiculo.php';
include_once '../model/documentacao.php';
include_once '../model/usuario.php';

$database = new Database();
$db = $database->getConnection();

$veiculo = new Veiculo($db);
$documentacao = new Documentacao($db);
$usuario = new Usuario($db);

$mensagem = '';
$tipo_mensagem = '';

// Verificar se o formulário foi enviado
if($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verificar se é uma operação de exclusão
    if(isset($_POST['excluir_id']) && !empty($_POST['excluir_id'])) {
        $documentacao->id = $_POST['excluir_id'];
        
        if($documentacao->excluir()) {
            $mensagem = "Registro excluído com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível excluir o registro.";
            $tipo_mensagem = "danger";
        }
    }
    // Verificar se é uma operação de edição ou cadastro de IPVA
    else if(isset($_POST['tipo']) && $_POST['tipo'] == 'IPVA') {
        $documentacao->veiculo_id = $_POST['veiculo_id'];
        $documentacao->tipo = 'IPVA';
        $documentacao->ano_referencia = $_POST['ano_referencia'];
        $documentacao->descricao = "IPVA " . $_POST['ano_referencia'];
        $documentacao->valor = $_POST['valor'];
        $documentacao->data_vencimento = $_POST['data_vencimento'];
        $documentacao->data_pagamento = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;
        $documentacao->parcelas = $_POST['parcelas'];
        $documentacao->status = !empty($_POST['data_pagamento']) ? 'pago' : 'pendente';
        $documentacao->observacoes = $_POST['observacoes'];
        
        if(isset($_POST['id']) && !empty($_POST['id'])) {
            // Edição
            $documentacao->id = $_POST['id'];
            if($documentacao->atualizar()) {
                $mensagem = "IPVA atualizado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível atualizar o IPVA.";
                $tipo_mensagem = "danger";
            }
        } else {
            // Cadastro
            if($documentacao->criar()) {
                $mensagem = "IPVA cadastrado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível cadastrar o IPVA.";
                $tipo_mensagem = "danger";
            }
        }
    }
    // Verificar se é uma operação de edição ou cadastro de Licenciamento
    else if(isset($_POST['tipo']) && $_POST['tipo'] == 'Licenciamento') {
        $documentacao->veiculo_id = $_POST['veiculo_id'];
        $documentacao->tipo = 'Licenciamento';
        $documentacao->ano_referencia = $_POST['ano_referencia'];
        $documentacao->descricao = "Licenciamento " . $_POST['ano_referencia'];
        $documentacao->valor = $_POST['valor'];
        $documentacao->data_vencimento = $_POST['data_vencimento'];
        $documentacao->data_pagamento = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;
        $documentacao->parcelas = 1; // Licenciamento é sempre à vista
        $documentacao->status = !empty($_POST['data_pagamento']) ? 'pago' : 'pendente';
        $documentacao->observacoes = $_POST['observacoes'];
        
        if(isset($_POST['id']) && !empty($_POST['id'])) {
            // Edição
            $documentacao->id = $_POST['id'];
            if($documentacao->atualizar()) {
                $mensagem = "Licenciamento atualizado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível atualizar o Licenciamento.";
                $tipo_mensagem = "danger";
            }
        } else {
            // Cadastro
            if($documentacao->criar()) {
                $mensagem = "Licenciamento cadastrado com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível cadastrar o Licenciamento.";
                $tipo_mensagem = "danger";
            }
        }
    }
    // Verificar se é uma operação de edição ou cadastro de Multa
    else if(isset($_POST['tipo']) && $_POST['tipo'] == 'Multa') {
        $documentacao->veiculo_id = $_POST['veiculo_id'];
        $documentacao->tipo = 'Multa';
        $documentacao->descricao = $_POST['descricao'];
        $documentacao->valor = $_POST['valor'];
        $documentacao->data_vencimento = $_POST['data_vencimento'];
        $documentacao->data_pagamento = !empty($_POST['data_pagamento']) ? $_POST['data_pagamento'] : null;
        $documentacao->funcionario_id = $_POST['funcionario_id'];
        $documentacao->data_infracao = $_POST['data_infracao'];
        $documentacao->local_infracao = $_POST['local_infracao'];
        $documentacao->status = !empty($_POST['data_pagamento']) ? 'pago' : 'pendente';
        $documentacao->observacoes = $_POST['observacoes'];
        
        if(isset($_POST['id']) && !empty($_POST['id'])) {
            // Edição
            $documentacao->id = $_POST['id'];
            if($documentacao->atualizar()) {
                $mensagem = "Multa atualizada com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível atualizar a Multa.";
                $tipo_mensagem = "danger";
            }
        } else {
            // Cadastro
            if($documentacao->criar()) {
                $mensagem = "Multa cadastrada com sucesso!";
                $tipo_mensagem = "success";
            } else {
                $mensagem = "Não foi possível cadastrar a Multa.";
                $tipo_mensagem = "danger";
            }
        }
    }
    // Verificar se é uma operação de registro de pagamento
    else if(isset($_POST['pagamento_id']) && !empty($_POST['pagamento_id'])) {
        $documentacao->id = $_POST['pagamento_id'];
        $documentacao->data_pagamento = $_POST['data_pagamento_modal'];
        $documentacao->status = 'pago';
        $documentacao->observacoes = $_POST['observacoes_pagamento'];
        
        if($documentacao->registrarPagamento()) {
            $mensagem = "Pagamento registrado com sucesso!";
            $tipo_mensagem = "success";
        } else {
            $mensagem = "Não foi possível registrar o pagamento.";
            $tipo_mensagem = "danger";
        }
    }
}

// Carregar lista de documentações
$documentacoes = $documentacao->listar();

// Carregar lista de veículos para o formulário
$veiculos_lista = $veiculo->listar();

// Carregar lista de funcionários para o formulário de multas
$funcionarios_lista = $usuario->listarAtivos();

// Somar valores
$total_pago = $documentacao->somarPorStatus('pago');
$total_pendente = $documentacao->somarPorStatus('pendente');

// Obter dados para mini dashboard
$ipvas_pendentes = $documentacao->contarPorTipo('IPVA', 'pendente');
$licenciamentos_pendentes = $documentacao->contarPorTipo('Licenciamento', 'pendente');
$multas_pendentes = $documentacao->contarPorTipo('Multa', 'pendente');

// Ano atual para referência
$ano_atual = date('Y');
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Controle de Veículos - Documentação</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Controle de Documentação</h1>
            <div>
                <button type="button" class="btn btn-success me-2" data-bs-toggle="modal" data-bs-target="#ipvaModal">
                    <i class="fas fa-file-invoice-dollar"></i> Novo IPVA
                </button>
                <button type="button" class="btn btn-info me-2" data-bs-toggle="modal" data-bs-target="#licenciamentoModal">
                    <i class="fas fa-id-card"></i> Novo Licenciamento
                </button>
                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#multaModal">
                    <i class="fas fa-exclamation-triangle"></i> Nova Multa
                </button>
            </div>
        </div>

        <?php if($mensagem): ?>
            <div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
                <?php echo $mensagem; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Mini Dashboard -->
        <div class="row mb-4">
            <div class="col-lg-12">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="m-0">Mini Dashboard de Documentação</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="card <?php echo $ipvas_pendentes > 0 ? 'bg-danger text-white' : 'bg-light'; ?> mb-3">
                                    <div class="card-body text-center">
                                        <h3><?php echo $ipvas_pendentes; ?></h3>
                                        <p class="mb-0">IPVA<?php echo $ipvas_pendentes != 1 ? 's' : ''; ?> Pendente<?php echo $ipvas_pendentes != 1 ? 's' : ''; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card <?php echo $licenciamentos_pendentes > 0 ? 'bg-danger text-white' : 'bg-light'; ?> mb-3">
                                    <div class="card-body text-center">
                                        <h3><?php echo $licenciamentos_pendentes; ?></h3>
                                        <p class="mb-0">Licenciamento<?php echo $licenciamentos_pendentes != 1 ? 's' : ''; ?> Pendente<?php echo $licenciamentos_pendentes != 1 ? 's' : ''; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card <?php echo $multas_pendentes > 0 ? 'bg-danger text-white' : 'bg-light'; ?> mb-3">
                                    <div class="card-body text-center">
                                        <h3><?php echo $multas_pendentes; ?></h3>
                                        <p class="mb-0">Multa<?php echo $multas_pendentes != 1 ? 's' : ''; ?> Pendente<?php echo $multas_pendentes != 1 ? 's' : ''; ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white mb-3">
                                    <div class="card-body text-center">
                                        <h3>R$ <?php echo number_format($total_pendente, 2, ',', '.'); ?></h3>
                                        <p class="mb-0">Total Pendente</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Alertas para documentos por vencer -->
                        <?php 
                        $vencendo_30_dias = $documentacao->listarAVencer(30);
                        if($vencendo_30_dias->rowCount() > 0): 
                        ?>
                        <div class="alert alert-warning mt-3">
                            <h5><i class="fas fa-exclamation-circle me-2"></i>Documentos vencendo nos próximos 30 dias:</h5>
                            <ul class="mb-0">
                                <?php while($doc = $vencendo_30_dias->fetch(PDO::FETCH_ASSOC)): ?>
                                <li>
                                    <strong><?php echo $doc['tipo']; ?>:</strong> 
                                    <?php echo $doc['placa']; ?> - 
                                    <?php echo $doc['descricao']; ?> - 
                                    Vence em <?php echo date('d/m/Y', strtotime($doc['data_vencimento'])); ?> - 
                                    R$ <?php echo number_format($doc['valor'], 2, ',', '.'); ?>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cards de resumo -->
        <div class="row mb-4">
            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-success shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                    Total Pago</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($total_pago, 2, ',', '.'); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
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
                                    Total Pendente</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800">R$ <?php echo number_format($total_pendente, 2, ',', '.'); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
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
                                    Vencendo em 30 dias</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $documentacao->contarVencendo(30); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-xl-3 col-md-6 mb-4">
                <div class="card border-left-primary shadow h-100 py-2">
                    <div class="card-body">
                        <div class="row no-gutters align-items-center">
                            <div class="col mr-2">
                                <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                    Total de Documentos</div>
                                <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $documentacao->contarTotal(); ?></div>
                            </div>
                            <div class="col-auto">
                                <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela de Documentações -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Documentações Registradas</h6>
                <div>
                    <div class="input-group">
                        <select class="form-select" id="filtroTipo">
                            <option value="">Todos os tipos</option>
                            <option value="IPVA">IPVA</option>
                            <option value="Licenciamento">Licenciamento</option>
                            <option value="Multa">Multa</option>
                        </select>
                        <input type="text" class="form-control" id="pesquisaDocumentacao" placeholder="Pesquisar...">
                        <button class="btn btn-outline-secondary" type="button">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>Veículo</th>
                                <th>Tipo</th>
                                <th>Descrição</th>
                                <th>Valor</th>
                                <th>Vencimento</th>
                                <th>Pagamento</th>
                                <th>Status</th>
                                <th>Informações Adicionais</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $documentacoes->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr data-tipo="<?php echo $row['tipo']; ?>">
                                <td><?php echo $row['placa'] . ' - ' . $row['modelo']; ?></td>
                                <td><?php echo $row['tipo']; ?></td>
                                <td><?php echo $row['descricao']; ?></td>
                                <td>R$ <?php echo number_format($row['valor'], 2, ',', '.'); ?></td>
                                <td><?php echo date('d/m/Y', strtotime($row['data_vencimento'])); ?></td>
                                <td>
                                    <?php 
                                    if($row['data_pagamento']) {
                                        echo date('d/m/Y', strtotime($row['data_pagamento']));
                                    } else {
                                        echo '-';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <?php if($row['status'] == 'pago'): ?>
                                        <span class="badge bg-success">Pago</span>
                                    <?php else: 
                                        // Verificar se está vencido
                                        $hoje = new DateTime();
                                        $vencimento = new DateTime($row['data_vencimento']);
                                        $vencido = $vencimento < $hoje;
                                    ?>
                                        <?php if($vencido): ?>
                                            <span class="badge bg-danger">Vencido</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning">Pendente</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if($row['tipo'] == 'IPVA' || $row['tipo'] == 'Licenciamento'): ?>
                                        Ano: <?php echo $row['ano_referencia']; ?>
                                        <?php if($row['tipo'] == 'IPVA'): ?>
                                            <br>Parcelas: <?php echo $row['parcelas']; ?>
                                        <?php endif; ?>
                                    <?php elseif($row['tipo'] == 'Multa'): ?>
                                        Responsável: <?php echo $row['funcionario_nome']; ?>
                                        <br>Data Infração: <?php echo date('d/m/Y', strtotime($row['data_infracao'])); ?>
                                        <br>Local: <?php echo $row['local_infracao']; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-primary editar-documentacao"
                                            data-bs-toggle="modal"
                                            data-bs-target="#<?php echo strtolower($row['tipo']); ?>Modal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-tipo="<?php echo $row['tipo']; ?>"
                                            data-veiculo-id="<?php echo $row['veiculo_id']; ?>"
                                            data-descricao="<?php echo $row['descricao']; ?>"
                                            data-valor="<?php echo $row['valor']; ?>"
                                            data-vencimento="<?php echo $row['data_vencimento']; ?>"
                                            data-pagamento="<?php echo $row['data_pagamento']; ?>"
                                            data-observacoes="<?php echo $row['observacoes']; ?>"
                                            <?php if($row['tipo'] == 'IPVA' || $row['tipo'] == 'Licenciamento'): ?>
                                            data-ano-referencia="<?php echo $row['ano_referencia']; ?>"
                                            <?php endif; ?>
                                            <?php if($row['tipo'] == 'IPVA'): ?>
                                            data-parcelas="<?php echo $row['parcelas']; ?>"
                                            <?php endif; ?>
                                            <?php if($row['tipo'] == 'Multa'): ?>
                                            data-funcionario-id="<?php echo $row['funcionario_id']; ?>"
                                            data-data-infracao="<?php echo $row['data_infracao']; ?>"
                                            data-local-infracao="<?php echo $row['local_infracao']; ?>"
                                            <?php endif; ?>>
                                        <i class="fas fa-edit"></i>
                                    </button>

                                    <button class="btn btn-sm btn-danger excluir-documentacao"
                                            data-bs-toggle="modal"
                                            data-bs-target="#excluirModal"
                                            data-id="<?php echo $row['id']; ?>"
                                            data-info="<?php echo $row['tipo'] . ' - ' . $row['placa']; ?>">
                                        <i class="fas fa-trash"></i>
                                    </button>

                                    <?php if($row['status'] == 'pendente'): ?>
                                        <button class="btn btn-sm btn-success registrar-pagamento"
                                                data-bs-toggle="modal"
                                                data-bs-target="#pagamentoModal"
                                                data-id="<?php echo $row['id']; ?>"
                                                data-info="<?php echo $row['tipo'] . ' - ' . $row['placa']; ?>"
                                                data-valor="<?php echo $row['valor']; ?>">
                                            <i class="fas fa-dollar-sign"></i>
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cadastro/Edição de IPVA -->
    <div class="modal fade" id="ipvaModal" tabindex="-1" aria-labelledby="ipvaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ipvaModalLabel">Novo IPVA</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="ipva_id">
                        <input type="hidden" name="tipo" value="IPVA">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="ipva_veiculo_id" class="form-label">Veículo</label>
                                <select class="form-select" id="ipva_veiculo_id" name="veiculo_id" required>
                                    <option value="">Selecione um veículo</option>
                                    <?php
                                    $veiculos_lista->execute(); // Reset do cursor
                                    while ($v = $veiculos_lista->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $v['id'] . '">' . $v['placa'] . ' - ' . $v['modelo'] . ' (' . $v['marca'] . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="ipva_ano_referencia" class="form-label">Ano de Referência</label>
                                <select class="form-select" id="ipva_ano_referencia" name="ano_referencia" required>
                                    <?php for($ano = $ano_atual + 1; $ano >= $ano_atual - 5; $ano--): ?>
                                        <option value="<?php echo $ano; ?>" <?php echo $ano == $ano_atual ? 'selected' : ''; ?>><?php echo $ano; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="ipva_valor" class="form-label">Valor</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" class="form-control" id="ipva_valor" name="valor" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="ipva_data_vencimento" class="form-label">Data de Vencimento</label>
                                <input type="date" class="form-control" id="ipva_data_vencimento" name="data_vencimento" required>
                            </div>
                            <div class="col-md-4">
                                <label for="ipva_parcelas" class="form-label">Parcelas</label>
                                <select class="form-select" id="ipva_parcelas" name="parcelas" required>
                                    <option value="1">À vista</option>
                                    <option value="2">2x</option>
                                    <option value="3">3x</option>
                                    <option value="4">4x</option>
                                    <option value="5">5x</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="ipva_data_pagamento" class="form-label">Data de Pagamento</label>
                            <input type="date" class="form-control" id="ipva_data_pagamento" name="data_pagamento">
                            <small class="text-muted">Deixe em branco se ainda não foi pago</small>
                        </div>

                        <div class="mb-3">
                            <label for="ipva_observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="ipva_observacoes" name="observacoes" rows="3"></textarea>
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

    <!-- Modal de Cadastro/Edição de Licenciamento -->
    <div class="modal fade" id="licenciamentoModal" tabindex="-1" aria-labelledby="licenciamentoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="licenciamentoModalLabel">Novo Licenciamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="licenciamento_id">
                        <input type="hidden" name="tipo" value="Licenciamento">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="licenciamento_veiculo_id" class="form-label">Veículo</label>
                                <select class="form-select" id="licenciamento_veiculo_id" name="veiculo_id" required>
                                    <option value="">Selecione um veículo</option>
                                    <?php
                                    $veiculos_lista->execute(); // Reset do cursor
                                    while ($v = $veiculos_lista->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $v['id'] . '">' . $v['placa'] . ' - ' . $v['modelo'] . ' (' . $v['marca'] . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="licenciamento_ano_referencia" class="form-label">Ano de Referência</label>
                                <select class="form-select" id="licenciamento_ano_referencia" name="ano_referencia" required>
                                    <?php for($ano = $ano_atual + 1; $ano >= $ano_atual - 5; $ano--): ?>
                                        <option value="<?php echo $ano; ?>" <?php echo $ano == $ano_atual ? 'selected' : ''; ?>><?php echo $ano; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="licenciamento_valor" class="form-label">Valor</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" class="form-control" id="licenciamento_valor" name="valor" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label for="licenciamento_data_vencimento" class="form-label">Data de Vencimento</label>
                                <input type="date" class="form-control" id="licenciamento_data_vencimento" name="data_vencimento" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="licenciamento_data_pagamento" class="form-label">Data de Pagamento</label>
                            <input type="date" class="form-control" id="licenciamento_data_pagamento" name="data_pagamento">
                            <small class="text-muted">Deixe em branco se ainda não foi pago</small>
                            <div class="form-text">Licenciamento só pode ser pago à vista.</div>
                        </div>

                        <div class="mb-3">
                            <label for="licenciamento_observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="licenciamento_observacoes" name="observacoes" rows="3"></textarea>
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

    <!-- Modal de Cadastro/Edição de Multa -->
    <div class="modal fade" id="multaModal" tabindex="-1" aria-labelledby="multaModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="multaModalLabel">Nova Multa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="id" id="multa_id">
                        <input type="hidden" name="tipo" value="Multa">

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="multa_veiculo_id" class="form-label">Veículo</label>
                                <select class="form-select" id="multa_veiculo_id" name="veiculo_id" required>
                                    <option value="">Selecione um veículo</option>
                                    <?php
                                    $veiculos_lista->execute(); // Reset do cursor
                                    while ($v = $veiculos_lista->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $v['id'] . '">' . $v['placa'] . ' - ' . $v['modelo'] . ' (' . $v['marca'] . ')</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="multa_funcionario_id" class="form-label">Funcionário Responsável</label>
                                <select class="form-select" id="multa_funcionario_id" name="funcionario_id" required>
                                    <option value="">Selecione o funcionário</option>
                                    <?php
                                    while ($u = $funcionarios_lista->fetch(PDO::FETCH_ASSOC)) {
                                        echo '<option value="' . $u['id'] . '">' . $u['nome'] . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="multa_descricao" class="form-label">Descrição da Infração</label>
                            <input type="text" class="form-control" id="multa_descricao" name="descricao" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="multa_data_infracao" class="form-label">Data da Infração</label>
                                <input type="date" class="form-control" id="multa_data_infracao" name="data_infracao" required>
                            </div>
                            <div class="col-md-8">
                                <label for="multa_local_infracao" class="form-label">Local da Infração</label>
                                <input type="text" class="form-control" id="multa_local_infracao" name="local_infracao" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="multa_valor" class="form-label">Valor</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="number" step="0.01" class="form-control" id="multa_valor" name="valor" required>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label for="multa_data_vencimento" class="form-label">Data de Vencimento</label>
                                <input type="date" class="form-control" id="multa_data_vencimento" name="data_vencimento" required>
                            </div>
                            <div class="col-md-4">
                                <label for="multa_data_pagamento" class="form-label">Data de Pagamento</label>
                                <input type="date" class="form-control" id="multa_data_pagamento" name="data_pagamento">
                                <small class="text-muted">Deixe em branco se ainda não foi pago</small>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="multa_observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="multa_observacoes" name="observacoes" rows="3"></textarea>
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

    <!-- Modal de Registro de Pagamento -->
    <div class="modal fade" id="pagamentoModal" tabindex="-1" aria-labelledby="pagamentoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="pagamentoModalLabel">Registrar Pagamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="">
                    <div class="modal-body">
                        <input type="hidden" name="pagamento_id" id="pagamento_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Documento</label>
                            <input type="text" class="form-control" id="pagamento_info" readonly>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Valor</label>
                            <div class="input-group">
                                <span class="input-group-text">R$</span>
                                <input type="text" class="form-control" id="pagamento_valor" readonly>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="data_pagamento_modal" class="form-label">Data de Pagamento</label>
                            <input type="date" class="form-control" id="data_pagamento_modal" name="data_pagamento_modal" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes_pagamento" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes_pagamento" name="observacoes_pagamento" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success">Confirmar Pagamento</button>
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
                    Tem certeza que deseja excluir o documento <span id="info_excluir"></span>?
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Configurar data de hoje como padrão para data de pagamento no modal
            document.getElementById('data_pagamento_modal').valueAsDate = new Date();
            
            // Configurar modal de edição para IPVA
            const editarBtnsIPVA = document.querySelectorAll('.editar-documentacao[data-tipo="IPVA"]');
            editarBtnsIPVA.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const veiculoId = this.getAttribute('data-veiculo-id');
                    const valor = this.getAttribute('data-valor');
                    const vencimento = this.getAttribute('data-vencimento');
                    const pagamento = this.getAttribute('data-pagamento');
                    const observacoes = this.getAttribute('data-observacoes');
                    const anoReferencia = this.getAttribute('data-ano-referencia');
                    const parcelas = this.getAttribute('data-parcelas');

                    document.getElementById('ipvaModalLabel').textContent = 'Editar IPVA';
                    document.getElementById('ipva_id').value = id;
                    document.getElementById('ipva_veiculo_id').value = veiculoId;
                    document.getElementById('ipva_valor').value = valor;
                    document.getElementById('ipva_data_vencimento').value = vencimento;
                    document.getElementById('ipva_data_pagamento').value = pagamento;
                    document.getElementById('ipva_observacoes').value = observacoes;
                    document.getElementById('ipva_ano_referencia').value = anoReferencia;
                    document.getElementById('ipva_parcelas').value = parcelas;
                });
            });
            
            // Configurar modal de edição para Licenciamento
            const editarBtnsLicenciamento = document.querySelectorAll('.editar-documentacao[data-tipo="Licenciamento"]');
            editarBtnsLicenciamento.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const veiculoId = this.getAttribute('data-veiculo-id');
                    const valor = this.getAttribute('data-valor');
                    const vencimento = this.getAttribute('data-vencimento');
                    const pagamento = this.getAttribute('data-pagamento');
                    const observacoes = this.getAttribute('data-observacoes');
                    const anoReferencia = this.getAttribute('data-ano-referencia');

                    document.getElementById('licenciamentoModalLabel').textContent = 'Editar Licenciamento';
                    document.getElementById('licenciamento_id').value = id;
                    document.getElementById('licenciamento_veiculo_id').value = veiculoId;
                    document.getElementById('licenciamento_valor').value = valor;
                    document.getElementById('licenciamento_data_vencimento').value = vencimento;
                    document.getElementById('licenciamento_data_pagamento').value = pagamento;
                    document.getElementById('licenciamento_observacoes').value = observacoes;
                    document.getElementById('licenciamento_ano_referencia').value = anoReferencia;
                });
            });
            
            // Configurar modal de edição para Multa
            const editarBtnsMulta = document.querySelectorAll('.editar-documentacao[data-tipo="Multa"]');
            editarBtnsMulta.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const veiculoId = this.getAttribute('data-veiculo-id');
                    const descricao = this.getAttribute('data-descricao');
                    const valor = this.getAttribute('data-valor');
                    const vencimento = this.getAttribute('data-vencimento');
                    const pagamento = this.getAttribute('data-pagamento');
                    const observacoes = this.getAttribute('data-observacoes');
                    const funcionarioId = this.getAttribute('data-funcionario-id');
                    const dataInfracao = this.getAttribute('data-data-infracao');
                    const localInfracao = this.getAttribute('data-local-infracao');

                    document.getElementById('multaModalLabel').textContent = 'Editar Multa';
                    document.getElementById('multa_id').value = id;
                    document.getElementById('multa_veiculo_id').value = veiculoId;
                    document.getElementById('multa_descricao').value = descricao;
                    document.getElementById('multa_valor').value = valor;
                    document.getElementById('multa_data_vencimento').value = vencimento;
                    document.getElementById('multa_data_pagamento').value = pagamento;
                    document.getElementById('multa_observacoes').value = observacoes;
                    document.getElementById('multa_funcionario_id').value = funcionarioId;
                    document.getElementById('multa_data_infracao').value = dataInfracao;
                    document.getElementById('multa_local_infracao').value = localInfracao;
                });
            });

            // Configurar modais para novos registros
            const novoBtns = document.querySelectorAll('[data-bs-target="#ipvaModal"], [data-bs-target="#licenciamentoModal"], [data-bs-target="#multaModal"]');
            novoBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const target = this.getAttribute('data-bs-target');
                    const tipoDoc = target.replace('#', '').replace('Modal', '');
                    
                    const modalId = tipoDoc + 'ModalLabel';
                    document.getElementById(modalId).textContent = 'Novo ' + tipoDoc.charAt(0).toUpperCase() + tipoDoc.slice(1);
                    
                    // Limpar formulário
                    const form = document.querySelector(target + ' form');
                    form.reset();
                    
                    // Limpar campo de ID
                    document.getElementById(tipoDoc + '_id').value = '';
                });
            });

            // Configurar modal de exclusão
            const excluirBtns = document.querySelectorAll('.excluir-documentacao');
            excluirBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    document.getElementById('excluir_id').value = id;
                    document.getElementById('info_excluir').textContent = this.getAttribute('data-info');
                });
            });

            // Configurar modal de pagamento
            const pagamentoBtns = document.querySelectorAll('.registrar-pagamento');
            pagamentoBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const id = this.getAttribute('data-id');
                    const info = this.getAttribute('data-info');
                    const valor = this.getAttribute('data-valor');
                    
                    document.getElementById('pagamento_id').value = id;
                    document.getElementById('pagamento_info').value = info;
                    document.getElementById('pagamento_valor').value = parseFloat(valor).toLocaleString('pt-BR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
                });
            });

            // Filtro de pesquisa para a tabela
            document.getElementById('pesquisaDocumentacao').addEventListener('keyup', function() {
                filtrarTabela();
            });
            
            // Filtro por tipo
            document.getElementById('filtroTipo').addEventListener('change', function() {
                filtrarTabela();
            });
            
            function filtrarTabela() {
                const termo = document.getElementById('pesquisaDocumentacao').value.toLowerCase();
                const tipo = document.getElementById('filtroTipo').value;
                const linhas = document.querySelectorAll('#dataTable tbody tr');

                linhas.forEach(linha => {
                    const conteudo = linha.textContent.toLowerCase();
                    const tipoLinha = linha.getAttribute('data-tipo');
                    let mostrar = conteudo.includes(termo);
                    
                    // Aplicar filtro por tipo
                    if (tipo !== '' && tipoLinha !== tipo) {
                        mostrar = false;
                    }
                    
                    if (mostrar) {
                        linha.style.display = '';
                    } else {
                        linha.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>
