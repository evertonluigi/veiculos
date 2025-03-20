<?php
class Manutencao {
    private $conn;
    private $table_name = "manutencoes";
    
    public $id;
    public $veiculo_id;
    public $tipo;
    public $km_inicio;
    public $data_inicio;
    public $finalizada;
    public $data_fim;
    public $valor;
    public $descricao;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Criar manutenção
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET veiculo_id = :veiculo_id, tipo = :tipo, 
                  km_inicio = :km_inicio, finalizada = 0";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->veiculo_id = htmlspecialchars(strip_tags($this->veiculo_id));
        $this->tipo = htmlspecialchars(strip_tags($this->tipo));
        $this->km_inicio = htmlspecialchars(strip_tags($this->km_inicio));
        
        // Bind values
        $stmt->bindParam(":veiculo_id", $this->veiculo_id);
        $stmt->bindParam(":tipo", $this->tipo);
        $stmt->bindParam(":km_inicio", $this->km_inicio);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Finalizar manutenção
    public function finalizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET finalizada = 1, data_fim = CURRENT_TIMESTAMP, 
                  valor = :valor, descricao = :descricao 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->valor = htmlspecialchars(strip_tags($this->valor));
        $this->descricao = htmlspecialchars(strip_tags($this->descricao));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind values
        $stmt->bindParam(":valor", $this->valor);
        $stmt->bindParam(":descricao", $this->descricao);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Excluir manutenção
    public function excluir() {
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Ler uma única manutenção
    public function ler() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->veiculo_id = $row['veiculo_id'];
            $this->tipo = $row['tipo'];
            $this->km_inicio = $row['km_inicio'];
            $this->data_inicio = $row['data_inicio'];
            $this->finalizada = $row['finalizada'];
            $this->data_fim = $row['data_fim'];
            $this->valor = $row['valor'];
            $this->descricao = $row['descricao'];
            return true;
        }
        
        return false;
    }
    
    // Listar todas as manutenções
    public function listar() {
        $query = "SELECT m.*, v.placa, v.modelo
                  FROM " . $this->table_name . " m
                  JOIN veiculos v ON m.veiculo_id = v.id
                  ORDER BY m.data_inicio DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar manutenções por veículo
    public function listarPorVeiculo($veiculo_id) {
        $query = "SELECT m.*, v.placa, v.modelo
                  FROM " . $this->table_name . " m
                  JOIN veiculos v ON m.veiculo_id = v.id
                  WHERE m.veiculo_id = ?
                  ORDER BY m.data_inicio DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $veiculo_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar manutenções em andamento
    public function listarEmAndamento() {
        $query = "SELECT m.*, v.placa, v.modelo
                  FROM " . $this->table_name . " m
                  JOIN veiculos v ON m.veiculo_id = v.id
                  WHERE m.finalizada = 0
                  ORDER BY m.data_inicio ASC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar últimas manutenções
    public function listarUltimas($limit) {
        $query = "SELECT m.*, v.placa, v.modelo
                  FROM " . $this->table_name . " m
                  JOIN veiculos v ON m.veiculo_id = v.id
                  ORDER BY m.data_inicio DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Total de manutenções no mês atual
    public function totalMesAtual() {
        $query = "SELECT SUM(valor) as total
                  FROM " . $this->table_name . "
                  WHERE (MONTH(data_fim) = MONTH(CURRENT_DATE())
                  AND YEAR(data_fim) = YEAR(CURRENT_DATE())
                  AND finalizada = 1)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ? $row['total'] : 0;
    }
    
    // Total de manutenções por tipo
    public function totalPorTipo() {
        $query = "SELECT tipo, COUNT(*) as total_manutencoes, SUM(valor) as total_valor
                  FROM " . $this->table_name . "
                  WHERE finalizada = 1
                  GROUP BY tipo
                  ORDER BY total_manutencoes DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Média de custo por veículo
    public function mediaCustoPorVeiculo() {
        $query = "SELECT v.placa, v.modelo, COUNT(*) as total_manutencoes, 
                    SUM(m.valor) as total_valor, AVG(m.valor) as media_valor
                  FROM " . $this->table_name . " m
                  JOIN veiculos v ON m.veiculo_id = v.id
                  WHERE m.finalizada = 1
                  GROUP BY m.veiculo_id
                  ORDER BY total_valor DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
