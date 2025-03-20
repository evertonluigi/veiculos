<?php
class Documentacao {
    // Conexão com o banco e propriedades da tabela
    private $conn;
    private $table_name = "documentacoes";
    
    // Propriedades do objeto
    public $id;
    public $veiculo_id;
    public $tipo;
    public $descricao;
    public $valor;
    public $data_vencimento;
    public $data_pagamento;
    public $parcelas;
    public $status;
    public $observacoes;
    
    // Propriedades específicas para IPVA e Licenciamento
    public $ano_referencia;
    
    // Propriedades específicas para Multas
    public $funcionario_id;
    public $data_infracao;
    public $local_infracao;
    
    // Construtor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Criar uma nova documentação
    public function criar() {
        // Verificar tipo para determinar quais campos incluir
        if ($this->tipo == 'IPVA' || $this->tipo == 'Licenciamento') {
            $query = "INSERT INTO " . $this->table_name . " 
                      (veiculo_id, tipo, descricao, valor, data_vencimento, data_pagamento, 
                      status, observacoes, ano_referencia, parcelas) 
                      VALUES (:veiculo_id, :tipo, :descricao, :valor, :data_vencimento, :data_pagamento, 
                      :status, :observacoes, :ano_referencia, :parcelas)";
        } else if ($this->tipo == 'Multa') {
            $query = "INSERT INTO " . $this->table_name . " 
                      (veiculo_id, tipo, descricao, valor, data_vencimento, data_pagamento, 
                      status, observacoes, funcionario_id, data_infracao, local_infracao) 
                      VALUES (:veiculo_id, :tipo, :descricao, :valor, :data_vencimento, :data_pagamento, 
                      :status, :observacoes, :funcionario_id, :data_infracao, :local_infracao)";
        } else {
            $query = "INSERT INTO " . $this->table_name . " 
                      (veiculo_id, tipo, descricao, valor, data_vencimento, data_pagamento, 
                      status, observacoes) 
                      VALUES (:veiculo_id, :tipo, :descricao, :valor, :data_vencimento, :data_pagamento, 
                      :status, :observacoes)";
        }
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar dados
        $this->veiculo_id = htmlspecialchars(strip_tags($this->veiculo_id));
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->valor = htmlspecialchars(strip_tags($this->valor));
        $this->data_vencimento = htmlspecialchars(strip_tags($this->data_vencimento));
        if($this->data_pagamento) {
            $this->data_pagamento = htmlspecialchars(strip_tags($this->data_pagamento));
        }
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));
        
        // Vincular parâmetros comuns
        $stmt->bindParam(":veiculo_id", $this->veiculo_id);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":valor", $this->valor);
        $stmt->bindParam(":data_vencimento", $this->data_vencimento);
        $stmt->bindParam(":data_pagamento", $this->data_pagamento);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":observacoes", $this->observacoes);
        
        // Vincular parâmetros específicos por tipo
        if ($this->tipo == 'IPVA' || $this->tipo == 'Licenciamento') {
            $this->ano_referencia = htmlspecialchars(strip_tags($this->ano_referencia));
            $this->parcelas = htmlspecialchars(strip_tags($this->parcelas));
            
            $stmt->bindParam(":ano_referencia", $this->ano_referencia);
            $stmt->bindParam(":parcelas", $this->parcelas);
        } else if ($this->tipo == 'Multa') {
            $this->funcionario_id = htmlspecialchars(strip_tags($this->funcionario_id));
            $this->data_infracao = htmlspecialchars(strip_tags($this->data_infracao));
            $this->local_infracao = htmlspecialchars(strip_tags($this->local_infracao));
            
            $stmt->bindParam(":funcionario_id", $this->funcionario_id);
            $stmt->bindParam(":data_infracao", $this->data_infracao);
            $stmt->bindParam(":local_infracao", $this->local_infracao);
        }
        
        // Executar query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Ler uma documentação específica
    public function ler() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->veiculo_id = $row['veiculo_id'];
            $this->tipo = $row['tipo'];
            $this->descricao = $row['descricao'];
            $this->valor = $row['valor'];
            $this->data_vencimento = $row['data_vencimento'];
            $this->data_pagamento = $row['data_pagamento'];
            $this->status = $row['status'];
            $this->observacoes = $row['observacoes'];
            
            // Atributos específicos
            if(isset($row['ano_referencia'])) $this->ano_referencia = $row['ano_referencia'];
            if(isset($row['parcelas'])) $this->parcelas = $row['parcelas'];
            if(isset($row['funcionario_id'])) $this->funcionario_id = $row['funcionario_id'];
            if(isset($row['data_infracao'])) $this->data_infracao = $row['data_infracao'];
            if(isset($row['local_infracao'])) $this->local_infracao = $row['local_infracao'];
            
            return true;
        }
        
        return false;
    }
    
	// Atualizar documentação
    public function atualizar() {
        // Verificar tipo para determinar quais campos atualizar
        if ($this->tipo == 'IPVA' || $this->tipo == 'Licenciamento') {
            $query = "UPDATE " . $this->table_name . " 
                      SET veiculo_id = :veiculo_id, 
                          descricao = :descricao, 
                          valor = :valor, 
                          data_vencimento = :data_vencimento, 
                          data_pagamento = :data_pagamento, 
                          status = :status, 
                          observacoes = :observacoes,
                          ano_referencia = :ano_referencia,
                          parcelas = :parcelas
                      WHERE id = :id";
        } else if ($this->tipo == 'Multa') {
            $query = "UPDATE " . $this->table_name . " 
                      SET veiculo_id = :veiculo_id, 
                          descricao = :descricao, 
                          valor = :valor, 
                          data_vencimento = :data_vencimento, 
                          data_pagamento = :data_pagamento, 
                          status = :status, 
                          observacoes = :observacoes,
                          funcionario_id = :funcionario_id,
                          data_infracao = :data_infracao,
                          local_infracao = :local_infracao
                      WHERE id = :id";
        } else {
            $query = "UPDATE " . $this->table_name . " 
                      SET veiculo_id = :veiculo_id, 
                          descricao = :descricao, 
                          valor = :valor, 
                          data_vencimento = :data_vencimento, 
                          data_pagamento = :data_pagamento, 
                          status = :status, 
                          observacoes = :observacoes
                      WHERE id = :id";
        }
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar dados
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->veiculo_id = htmlspecialchars(strip_tags($this->veiculo_id));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->valor = htmlspecialchars(strip_tags($this->valor));
        $this->data_vencimento = htmlspecialchars(strip_tags($this->data_vencimento));
        if($this->data_pagamento) {
            $this->data_pagamento = htmlspecialchars(strip_tags($this->data_pagamento));
        }
        $this->status = htmlspecialchars(strip_tags($this->status));
        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));
        
        // Vincular parâmetros comuns
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":veiculo_id", $this->veiculo_id);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":valor", $this->valor);
        $stmt->bindParam(":data_vencimento", $this->data_vencimento);
        $stmt->bindParam(":data_pagamento", $this->data_pagamento);
        $stmt->bindParam(":status", $this->status);
        $stmt->bindParam(":observacoes", $this->observacoes);
        
        // Vincular parâmetros específicos por tipo
        if ($this->tipo == 'IPVA' || $this->tipo == 'Licenciamento') {
            $this->ano_referencia = htmlspecialchars(strip_tags($this->ano_referencia));
            $this->parcelas = htmlspecialchars(strip_tags($this->parcelas));
            
            $stmt->bindParam(":ano_referencia", $this->ano_referencia);
            $stmt->bindParam(":parcelas", $this->parcelas);
        } else if ($this->tipo == 'Multa') {
            $this->funcionario_id = htmlspecialchars(strip_tags($this->funcionario_id));
            $this->data_infracao = htmlspecialchars(strip_tags($this->data_infracao));
            $this->local_infracao = htmlspecialchars(strip_tags($this->local_infracao));
            
            $stmt->bindParam(":funcionario_id", $this->funcionario_id);
            $stmt->bindParam(":data_infracao", $this->data_infracao);
            $stmt->bindParam(":local_infracao", $this->local_infracao);
        }
        
        // Executar query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Registrar pagamento
    public function registrarPagamento() {
        $query = "UPDATE " . $this->table_name . " 
                  SET data_pagamento = :data_pagamento, 
                      status = 'pago', 
                      observacoes = CONCAT(observacoes, :observacoes) 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar dados
        $this->id = htmlspecialchars(strip_tags($this->id));
        $this->data_pagamento = htmlspecialchars(strip_tags($this->data_pagamento));
        
        // Preparar observações
        $obs_pagamento = "";
        if ($this->observacoes) {
            $obs_pagamento = "\n[Pagamento: " . date('d/m/Y') . "] " . htmlspecialchars(strip_tags($this->observacoes));
        }
        
        // Vincular parâmetros
        $stmt->bindParam(":id", $this->id);
        $stmt->bindParam(":data_pagamento", $this->data_pagamento);
        $stmt->bindParam(":observacoes", $obs_pagamento);
        
        // Executar query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Excluir documentação
    public function excluir() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar dado
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Vincular parâmetro
        $stmt->bindParam(':id', $this->id);
        
        // Executar query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Listar todas as documentações
    public function listar() {
        $query = "SELECT d.*, v.placa, v.modelo, 
                  u.nome as funcionario_nome
                  FROM " . $this->table_name . " d
                  JOIN veiculos v ON d.veiculo_id = v.id
                  LEFT JOIN usuarios u ON d.funcionario_id = u.id
                  ORDER BY d.data_vencimento ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar documentações por veículo
    public function listarPorVeiculo($veiculo_id) {
        $query = "SELECT d.*, v.placa, v.modelo,
                  u.nome as funcionario_nome
                  FROM " . $this->table_name . " d
                  JOIN veiculos v ON d.veiculo_id = v.id
                  LEFT JOIN usuarios u ON d.funcionario_id = u.id
                  WHERE d.veiculo_id = :veiculo_id
                  ORDER BY d.data_vencimento ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':veiculo_id', $veiculo_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar documentações por tipo
    public function listarPorTipo($tipo) {
        $query = "SELECT d.*, v.placa, v.modelo,
                  u.nome as funcionario_nome
                  FROM " . $this->table_name . " d
                  JOIN veiculos v ON d.veiculo_id = v.id
                  LEFT JOIN usuarios u ON d.funcionario_id = u.id
                  WHERE d.tipo = :tipo
                  ORDER BY d.data_vencimento ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar documentações por status
    public function listarPorStatus($status) {
        $query = "SELECT d.*, v.placa, v.modelo,
                  u.nome as funcionario_nome
                  FROM " . $this->table_name . " d
                  JOIN veiculos v ON d.veiculo_id = v.id
                  LEFT JOIN usuarios u ON d.funcionario_id = u.id
                  WHERE d.status = :status
                  ORDER BY d.data_vencimento ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar documentações vencidas
    public function listarVencidas() {
        $hoje = date('Y-m-d');
        
        $query = "SELECT d.*, v.placa, v.modelo,
                  u.nome as funcionario_nome
                  FROM " . $this->table_name . " d
                  JOIN veiculos v ON d.veiculo_id = v.id
                  LEFT JOIN usuarios u ON d.funcionario_id = u.id
                  WHERE d.data_vencimento < :hoje AND d.status = 'pendente'
                  ORDER BY d.data_vencimento ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hoje', $hoje);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar documentações a vencer nos próximos X dias
    public function listarAVencer($dias) {
        $hoje = date('Y-m-d');
        $limite = date('Y-m-d', strtotime("+{$dias} days"));
        
        $query = "SELECT d.*, v.placa, v.modelo,
                  u.nome as funcionario_nome
                  FROM " . $this->table_name . " d
                  JOIN veiculos v ON d.veiculo_id = v.id
                  LEFT JOIN usuarios u ON d.funcionario_id = u.id
                  WHERE d.data_vencimento BETWEEN :hoje AND :limite AND d.status = 'pendente'
                  ORDER BY d.data_vencimento ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hoje', $hoje);
        $stmt->bindParam(':limite', $limite);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Contar total de documentações
    public function contarTotal() {
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Contar documentações por tipo e status
    public function contarPorTipo($tipo, $status = '') {
        if ($status) {
            $query = "SELECT COUNT(*) as total 
                      FROM " . $this->table_name . " 
                      WHERE tipo = :tipo AND status = :status";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tipo', $tipo);
            $stmt->bindParam(':status', $status);
        } else {
            $query = "SELECT COUNT(*) as total 
                      FROM " . $this->table_name . " 
                      WHERE tipo = :tipo";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':tipo', $tipo);
        }
        
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Contar documentações vencendo nos próximos X dias
    public function contarVencendo($dias) {
        $hoje = date('Y-m-d');
        $limite = date('Y-m-d', strtotime("+{$dias} days"));
        
        $query = "SELECT COUNT(*) as total 
                  FROM " . $this->table_name . " 
                  WHERE data_vencimento BETWEEN :hoje AND :limite 
                  AND status = 'pendente'";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':hoje', $hoje);
        $stmt->bindParam(':limite', $limite);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'];
    }
    
    // Somar valores por status
    public function somarPorStatus($status) {
        $query = "SELECT SUM(valor) as total 
                  FROM " . $this->table_name . " 
                  WHERE status = :status";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ? $row['total'] : 0;
    }
    
    // Somar valores por tipo
    public function somarPorTipo($tipo) {
        $query = "SELECT SUM(valor) as total 
                  FROM " . $this->table_name . " 
                  WHERE tipo = :tipo";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':tipo', $tipo);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ? $row['total'] : 0;
    }
    
    // Somar valores por funcionário responsável (para multas)
    public function somarPorFuncionario($funcionario_id) {
        $query = "SELECT SUM(valor) as total 
                  FROM " . $this->table_name . " 
                  WHERE tipo = 'Multa' AND funcionario_id = :funcionario_id";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':funcionario_id', $funcionario_id);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ? $row['total'] : 0;
    }
    
    // Obter total de gastos por tipo em um período
    public function totalPorTipoPeriodo($data_inicio, $data_fim) {
        $query = "SELECT tipo, SUM(valor) as total 
                  FROM " . $this->table_name . " 
                  WHERE status = 'pago' 
                  AND data_pagamento BETWEEN :data_inicio AND :data_fim 
                  GROUP BY tipo 
                  ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Obter total de gastos por veículo em um período
    public function totalPorVeiculoPeriodo($data_inicio, $data_fim) {
        $query = "SELECT d.veiculo_id, v.placa, v.modelo, SUM(d.valor) as total 
                  FROM " . $this->table_name . " d
                  JOIN veiculos v ON d.veiculo_id = v.id
                  WHERE d.status = 'pago' 
                  AND d.data_pagamento BETWEEN :data_inicio AND :data_fim 
                  GROUP BY d.veiculo_id 
                  ORDER BY total DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Obter total de multas por funcionário
    public function multasPorFuncionario() {
        $query = "SELECT d.funcionario_id, u.nome, COUNT(*) as total_multas, SUM(d.valor) as total_valor
                  FROM " . $this->table_name . " d
                  JOIN usuarios u ON d.funcionario_id = u.id
                  WHERE d.tipo = 'Multa'
                  GROUP BY d.funcionario_id
                  ORDER BY total_valor DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
}
?>
