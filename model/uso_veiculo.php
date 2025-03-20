<?php
class UsoVeiculo {
    private $conn;
    private $table_name = "uso_veiculos";
    
    public $id;
    public $veiculo_id;
    public $usuario_id;
    public $km_saida;
    public $km_retorno;
    public $data_saida;
    public $data_retorno;
    public $motivo;
    public $observacoes;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Iniciar uso de veículo
    public function iniciar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET veiculo_id = :veiculo_id, usuario_id = :usuario_id, 
                  km_saida = :km_saida, motivo = :motivo, 
                  data_saida = NOW()";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->veiculo_id = htmlspecialchars(strip_tags($this->veiculo_id));
        $this->usuario_id = htmlspecialchars(strip_tags($this->usuario_id));
        $this->km_saida = htmlspecialchars(strip_tags($this->km_saida));
        $this->motivo = htmlspecialchars(strip_tags($this->motivo));
        
        // Bind values
        $stmt->bindParam(":veiculo_id", $this->veiculo_id);
        $stmt->bindParam(":usuario_id", $this->usuario_id);
        $stmt->bindParam(":km_saida", $this->km_saida);
        $stmt->bindParam(":motivo", $this->motivo);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Finalizar uso de veículo
    public function finalizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET km_retorno = :km_retorno, observacoes = :observacoes, 
                  data_retorno = NOW()
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->km_retorno = htmlspecialchars(strip_tags($this->km_retorno));
        $this->observacoes = htmlspecialchars(strip_tags($this->observacoes));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Bind values
        $stmt->bindParam(":km_retorno", $this->km_retorno);
        $stmt->bindParam(":observacoes", $this->observacoes);
        $stmt->bindParam(":id", $this->id);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Ler dados de um uso específico
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
            $this->km_saida = $row['km_saida'];
            $this->km_retorno = $row['km_retorno'];
            $this->data_saida = $row['data_saida'];
            $this->data_retorno = $row['data_retorno'];
            $this->motivo = $row['motivo'];
            $this->observacoes = $row['observacoes'];
            return true;
        }
        
        return false;
    }
    
    // Verificar se um usuário está com veículo em uso
    public function emUsoUsuario($usuario_id) {
        $query = "SELECT u.*, v.id as veiculo_id, v.placa, v.modelo, v.marca, v.ano
                  FROM " . $this->table_name . " u
                  JOIN veiculos v ON u.veiculo_id = v.id
                  WHERE u.usuario_id = ? AND u.data_retorno IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $usuario_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            return $row;
        }
        
        return false;
    }
    
    // Listar usos de veículos por usuário
    public function listarPorUsuario($usuario_id) {
        $query = "SELECT u.*, v.placa, v.modelo
                  FROM " . $this->table_name . " u
                  JOIN veiculos v ON u.veiculo_id = v.id
                  WHERE u.usuario_id = ?
                  ORDER BY u.data_saida DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $usuario_id);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar todos os usos de veículos
    public function listar() {
        $query = "SELECT u.*, v.placa, v.modelo, us.nome as nome_usuario
                  FROM " . $this->table_name . " u
                  JOIN veiculos v ON u.veiculo_id = v.id
                  JOIN usuarios us ON u.usuario_id = us.id
                  ORDER BY u.data_saida DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar usos ativos (veículos em uso)
    public function listarAtivos() {
        $query = "SELECT u.*, v.placa, v.modelo, us.nome as nome_usuario
                  FROM " . $this->table_name . " u
                  JOIN veiculos v ON u.veiculo_id = v.id
                  JOIN usuarios us ON u.usuario_id = us.id
                  WHERE u.data_retorno IS NULL
                  ORDER BY u.data_saida DESC";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Total de KM percorridos por período
    public function totalKmPorPeriodo($data_inicio, $data_fim) {
        $query = "SELECT SUM(km_retorno - km_saida) as total_km
                  FROM " . $this->table_name . "
                  WHERE data_retorno IS NOT NULL
                  AND DATE(data_saida) BETWEEN :data_inicio AND :data_fim";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':data_inicio', $data_inicio);
        $stmt->bindParam(':data_fim', $data_fim);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total_km'] ? $row['total_km'] : 0;
    }
    
    // Verificar se um veículo está em uso
    public function veiculoEmUso($veiculo_id) {
        $query = "SELECT COUNT(*) as total
                  FROM " . $this->table_name . "
                  WHERE veiculo_id = ? AND data_retorno IS NULL";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $veiculo_id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row['total'] > 0;
    }
}
?>
