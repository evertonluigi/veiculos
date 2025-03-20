<?php
class Abastecimento {
    private $conn;
    private $table_name = "abastecimentos";
    
    public $id;
    public $veiculo_id;
    public $usuario_id;
    public $km;
    public $litros;
    public $tipo_combustivel;
    public $valor;
    public $data;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Criar abastecimento
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET veiculo_id = :veiculo_id, usuario_id = :usuario_id, 
                  km = :km, litros = :litros, tipo_combustivel = :tipo_combustivel, 
                  valor = :valor";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->veiculo_id = htmlspecialchars(strip_tags($this->veiculo_id));
        $this->usuario_id = htmlspecialchars(strip_tags($this->usuario_id));
        $this->km = htmlspecialchars(strip_tags($this->km));
        $this->litros = htmlspecialchars(strip_tags($this->litros));
        $this->tipo_combustivel = htmlspecialchars(strip_tags($this->tipo_combustivel));
        $this->valor = htmlspecialchars(strip_tags($this->valor));
        
        // Bind values
        $stmt->bindParam(":veiculo_id", $this->veiculo_id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":km", $this->km);
        $stmt->bindParam(":litros", $this->litros);
        $stmt->bindParam(":tipo_combustivel", $this->tipo_combustivel);
        $stmt->bindParam(":valor", $this->valor);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Excluir abastecimento
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
    
    // Ler um único abastecimento
    public function ler() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->veiculo_id = $row['veiculo_id'];
            $this->usuario_id = $row['usuario_id'];
            $this->km = $row['km'];
            $this->litros = $row['litros'];
            $this->tipo_combustivel = $row['tipo_combustivel'];
            $this->valor = $row['valor'];
            $this->data = $row['data'];
            return true;
        }
        
        return false;
    }
    
    // Listar todos os abastecimentos
    public function listar() {
        $query = "SELECT a.*, v.placa, v.modelo, u.nome as nome_usuario
                  FROM " . $this->table_name . " a
                  JOIN veiculos v ON a.veiculo_id = v.id
                  JOIN usuarios u ON a.usuario_id = u.id
                  ORDER BY a.data DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar abastecimentos por veículo
    public function listarPorVeiculo($veiculo_id) {
        $query = "SELECT a.*, v.placa, v.modelo, u.nome as nome_usuario
                  FROM " . $this->table_name . " a
                  JOIN veiculos v ON a.veiculo_id = v.id
                  JOIN usuarios u ON a.usuario_id = u.id
                  WHERE a.veiculo_id = ?
                  ORDER BY a.data DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $veiculo_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar abastecimentos por usuário
    public function listarPorUsuario($usuario_id) {
        $query = "SELECT a.*, v.placa, v.modelo, u.nome as nome_usuario
                  FROM " . $this->table_name . " a
                  JOIN veiculos v ON a.veiculo_id = v.id
                  JOIN usuarios u ON a.usuario_id = u.id
                  WHERE a.usuario_id = ?
                  ORDER BY a.data DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $usuario_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar últimos abastecimentos
    public function listarUltimos($limit) {
        $query = "SELECT a.*, v.placa, v.modelo, u.nome as nome_usuario
                  FROM " . $this->table_name . " a
                  JOIN veiculos v ON a.veiculo_id = v.id
                  JOIN usuarios u ON a.usuario_id = u.id
                  ORDER BY a.data DESC
                  LIMIT ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Total de abastecimentos no mês atual
    public function totalMesAtual() {
        $query = "SELECT SUM(valor) as total
                  FROM " . $this->table_name . "
                  WHERE MONTH(data) = MONTH(CURRENT_DATE())
                  AND YEAR(data) = YEAR(CURRENT_DATE())";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] ? $row['total'] : 0;
    }
    
    // Total de litros por tipo de combustível no mês atual
    public function totalLitrosPorTipo() {
        $query = "SELECT tipo_combustivel, SUM(litros) as total_litros
                  FROM " . $this->table_name . "
                  WHERE MONTH(data) = MONTH(CURRENT_DATE())
                  AND YEAR(data) = YEAR(CURRENT_DATE())
                  GROUP BY tipo_combustivel";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Média de valor por litro por mês
    public function mediaPorLitroPorMes() {
        $query = "SELECT 
                    YEAR(data) as ano,
                    MONTH(data) as mes,
                    tipo_combustivel,
                    SUM(valor) / SUM(litros) as media_preco
                  FROM " . $this->table_name . "
                  GROUP BY YEAR(data), MONTH(data), tipo_combustivel
                  ORDER BY YEAR(data) DESC, MONTH(data) DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    // Listar abastecimentos por período
    public function listarPorPeriodo($data_inicio, $data_fim) {
        $query = "SELECT a.*, v.placa, v.modelo, u.nome as nome_usuario
                  FROM " . $this->table_name . " a
                  JOIN veiculos v ON a.veiculo_id = v.id
                  JOIN usuarios u ON a.usuario_id = u.id
                  WHERE DATE(a.data) BETWEEN :data_inicio AND :data_fim
                  ORDER BY a.data DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Total de abastecimentos por mês - MÉTODO ADICIONADO
    public function totalPorMes() {
        $query = "SELECT 
                    YEAR(data) as ano,
                    MONTH(data) as mes,
                    SUM(valor) as total
                  FROM " . $this->table_name . "
                  GROUP BY YEAR(data), MONTH(data)
                  ORDER BY YEAR(data) DESC, MONTH(data) DESC
                  LIMIT 6";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>
