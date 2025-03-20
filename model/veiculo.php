<?php
class Veiculo {
    // Conexão com o banco e propriedades da tabela
    private $conn;
    private $table_name = "veiculos";
    
    // Propriedades do objeto
    public $id;
    public $placa;
    public $modelo;
    public $marca;
    public $ano;
    public $km_atual;
    public $status;
    public $ativo;
    
    // Construtor
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Listar todos os veículos (mesmo método original, mantido para compatibilidade)
    public function listar() {
        // Verificar se existe a coluna ativo no banco
        try {
            $query = "SELECT id, placa, modelo, marca, ano, km_atual, status, ativo 
                      FROM " . $this->table_name . " 
                      WHERE ativo = 1 OR ativo IS NULL
                      ORDER BY placa ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        } catch (PDOException $e) {
            // Se a coluna ativo não existir, usa a consulta original
            $query = "SELECT id, placa, modelo, marca, ano, km_atual, status 
                      FROM " . $this->table_name . " 
                      ORDER BY placa ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }
        
        return $stmt;
    }
    
    // Listar todos os veículos, incluindo inativos (novo método)
    public function listarTodos() {
        try {
            $query = "SELECT id, placa, modelo, marca, ano, km_atual, status, ativo 
                      FROM " . $this->table_name . " 
                      ORDER BY placa ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        } catch (PDOException $e) {
            // Fallback para sistema sem coluna ativo
            $query = "SELECT id, placa, modelo, marca, ano, km_atual, status 
                      FROM " . $this->table_name . " 
                      ORDER BY placa ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
        }
        
        return $stmt;
    }
    
    // Listar veículos disponíveis
    public function listarDisponiveis() {
        try {
            if($this->colunaAtivoExiste()) {
                // Se a coluna ativo existe, lista apenas veículos ativos e disponíveis
                $query = "SELECT id, placa, modelo, marca, ano, km_atual, status 
                          FROM " . $this->table_name . " 
                          WHERE status = 'disponivel' AND (ativo = 1 OR ativo IS NULL)
                          ORDER BY placa ASC";
            } else {
                // Se a coluna não existe, lista todos os veículos disponíveis
                $query = "SELECT id, placa, modelo, marca, ano, km_atual, status 
                          FROM " . $this->table_name . " 
                          WHERE status = 'disponivel'
                          ORDER BY placa ASC";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt;
        } catch (PDOException $e) {
            // Fallback genérico em caso de erro
            $query = "SELECT id, placa, modelo, marca, ano, km_atual, status 
                      FROM " . $this->table_name . " 
                      ORDER BY placa ASC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt;
        }
    }
    
    // Contar total de veículos (ativos ou todos, dependendo da existência da coluna ativo)
    public function contarTotal() {
        try {
            if($this->colunaAtivoExiste()) {
                // Se a coluna ativo existe, conta apenas os ativos
                $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " WHERE ativo = 1 OR ativo IS NULL";
            } else {
                // Se a coluna não existe, conta todos
                $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['total'];
        } catch (PDOException $e) {
            // Em caso de erro, retorna 0
            return 0;
        }
    }

    // Contar veículos em manutenção
    public function contarEmManutencao() {
        try {
            if($this->colunaAtivoExiste()) {
                // Se a coluna ativo existe, conta apenas os ativos em manutenção
                $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                          WHERE status = 'manutencao' AND (ativo = 1 OR ativo IS NULL)";
            } else {
                // Se a coluna não existe, conta todos em manutenção
                $query = "SELECT COUNT(*) as total FROM " . $this->table_name . " 
                          WHERE status = 'manutencao'";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $row['total'];
        } catch (PDOException $e) {
            // Em caso de erro, retorna 0
            return 0;
        }
    }
    
    // Criar um novo veículo (método original mantido)
    public function criar() {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                      (placa, modelo, marca, ano, km_atual, status, ativo) 
                      VALUES(:placa, :modelo, :marca, :ano, :km_atual, 'disponivel', 1)";
            
            $stmt = $this->conn->prepare($query);
        } catch (PDOException $e) {
            // Fallback para sistema sem coluna ativo
            $query = "INSERT INTO " . $this->table_name . " 
                      (placa, modelo, marca, ano, km_atual, status) 
                      VALUES(:placa, :modelo, :marca, :ano, :km_atual, 'disponivel')";
            
            $stmt = $this->conn->prepare($query);
        }
        
        // Sanitizar dados
        $this->placa = htmlspecialchars(strip_tags($this->placa));
        $this->modelo = htmlspecialchars(strip_tags($this->modelo));
        $this->marca = htmlspecialchars(strip_tags($this->marca));
        $this->ano = htmlspecialchars(strip_tags($this->ano));
        $this->km_atual = htmlspecialchars(strip_tags($this->km_atual));
        
        // Vincular parâmetros
        $stmt->bindParam(":placa", $this->placa);
        $stmt->bindParam(":modelo", $this->modelo);
        $stmt->bindParam(":marca", $this->marca);
        $stmt->bindParam(":ano", $this->ano);
        $stmt->bindParam(":km_atual", $this->km_atual);
        
        // Executar query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Ler um veículo específico
    public function ler() {
        try {
            $query = "SELECT placa, modelo, marca, ano, km_atual, status, ativo
                      FROM " . $this->table_name . " 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($row) {
                $this->placa = $row['placa'];
                $this->modelo = $row['modelo'];
                $this->marca = $row['marca'];
                $this->ano = $row['ano'];
                $this->km_atual = $row['km_atual'];
                $this->status = $row['status'];
                $this->ativo = isset($row['ativo']) ? $row['ativo'] : 1;
                return true;
            }
        } catch (PDOException $e) {
            // Fallback para sistema sem coluna ativo
            $query = "SELECT placa, modelo, marca, ano, km_atual, status
                      FROM " . $this->table_name . " 
                      WHERE id = :id";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if($row) {
                $this->placa = $row['placa'];
                $this->modelo = $row['modelo'];
                $this->marca = $row['marca'];
                $this->ano = $row['ano'];
                $this->km_atual = $row['km_atual'];
                $this->status = $row['status'];
                $this->ativo = 1; // Assume ativo por padrão
                return true;
            }
        }
        
        return false;
    }
    
    // Atualizar veículo
    public function atualizar() {
        $query = "UPDATE " . $this->table_name . " 
                  SET placa = :placa, 
                      modelo = :modelo, 
                      marca = :marca, 
                      ano = :ano, 
                      km_atual = :km_atual 
                  WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitizar dados
        $this->placa = htmlspecialchars(strip_tags($this->placa));
        $this->modelo = htmlspecialchars(strip_tags($this->modelo));
        $this->marca = htmlspecialchars(strip_tags($this->marca));
        $this->ano = htmlspecialchars(strip_tags($this->ano));
        $this->km_atual = htmlspecialchars(strip_tags($this->km_atual));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        // Vincular parâmetros
        $stmt->bindParam(":placa", $this->placa);
        $stmt->bindParam(":modelo", $this->modelo);
        $stmt->bindParam(":marca", $this->marca);
        $stmt->bindParam(":ano", $this->ano);
        $stmt->bindParam(":km_atual", $this->km_atual);
        $stmt->bindParam(":id", $this->id);
        
        // Executar query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Excluir veículo - MODIFICADO para excluir completamente
    public function excluir($forcar_exclusao = true) {
        try {
            // Início da transação
            $this->conn->beginTransaction();
            
            // Excluir registros relacionados em outras tabelas
            $tabelas_relacionadas = ['uso_veiculos', 'abastecimentos', 'manutencoes'];
            
            foreach($tabelas_relacionadas as $tabela) {
                try {
                    $query = "DELETE FROM " . $tabela . " WHERE veiculo_id = :id";
                    $stmt = $this->conn->prepare($query);
                    $stmt->bindParam(':id', $this->id);
                    $stmt->execute();
                } catch (Exception $e) {
                    // Ignora erros se a tabela não existir
                    continue;
                }
            }
            
            // Excluir o veículo
            $query = "DELETE FROM " . $this->table_name . " WHERE id = :id";
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':id', $this->id);
            $stmt->execute();
            
            // Confirmar a transação
            $this->conn->commit();
            
            return true;
        } catch (PDOException $e) {
            // Em caso de erro, desfaz todas as alterações
            $this->conn->rollback();
            
            // Se ainda assim não conseguir excluir e forçar_exclusao for false, tenta desativar
            if (!$forcar_exclusao && $this->colunaAtivoExiste()) {
                return $this->desativar();
            }
            
            return false;
        }
    }
    
    // Verificar se a coluna ativo existe na tabela
    private function colunaAtivoExiste() {
        try {
            $query = "SHOW COLUMNS FROM " . $this->table_name . " LIKE 'ativo'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    // Desativar veículo
    public function desativar() {
        if(!$this->colunaAtivoExiste()) {
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . " 
                  SET ativo = 0,
                      status = 'disponivel'
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Ativar veículo
    public function ativar() {
        if(!$this->colunaAtivoExiste()) {
            return false;
        }
        
        $query = "UPDATE " . $this->table_name . " 
                  SET ativo = 1
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        return $stmt->execute();
    }
    
    // Atualizar quilometragem
    public function atualizarKM($km) {
        $query = "UPDATE " . $this->table_name . " 
                  SET km_atual = :km 
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':km', $km);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            $this->km_atual = $km;
            return true;
        }
        
        return false;
    }
    
    // Definir status como 'em uso'
    public function definirEmUso() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'em_uso' 
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            $this->status = 'em_uso';
            return true;
        }
        
        return false;
    }
    
    // Definir status como 'em manutenção'
    public function definirEmManutencao() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'manutencao' 
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            $this->status = 'manutencao';
            return true;
        }
        
        return false;
    }
    
    // Definir status como 'disponível'
    public function definirDisponivel() {
        $query = "UPDATE " . $this->table_name . " 
                  SET status = 'disponivel' 
                  WHERE id = :id";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':id', $this->id);
        
        if($stmt->execute()) {
            $this->status = 'disponivel';
            return true;
        }
        
        return false;
    }
    
    // Obter informações do usuário que está usando o veículo
    public function obterUsuarioEmUso() {
        $query = "SELECT uv.id as uso_id, u.nome 
                  FROM uso_veiculos uv 
                  JOIN usuarios u ON uv.usuario_id = u.id 
                  WHERE uv.veiculo_id = :veiculo_id 
                  AND uv.data_retorno IS NULL 
                  ORDER BY uv.data_saida DESC 
                  LIMIT 1";
                  
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':veiculo_id', $this->id);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    // Obter estatísticas dos veículos por status, marca e ano
    public function obterEstatisticas() {
        $result = [
            'por_status' => [],
            'por_marca' => [],
            'por_ano' => []
        ];
        
        try {
            // Estatísticas por status
            if($this->colunaAtivoExiste()) {
                $query_status = "SELECT status, COUNT(*) as total 
                                FROM " . $this->table_name . " 
                                WHERE ativo = 1 OR ativo IS NULL 
                                GROUP BY status";
            } else {
                $query_status = "SELECT status, COUNT(*) as total 
                                FROM " . $this->table_name . " 
                                GROUP BY status";
            }
            
            $stmt_status = $this->conn->prepare($query_status);
            $stmt_status->execute();
            
            while($row = $stmt_status->fetch(PDO::FETCH_ASSOC)) {
                $result['por_status'][] = $row;
            }
            
            // Estatísticas por marca
            if($this->colunaAtivoExiste()) {
                $query_marca = "SELECT marca, COUNT(*) as total 
                                FROM " . $this->table_name . " 
                                WHERE ativo = 1 OR ativo IS NULL 
                                GROUP BY marca 
                                ORDER BY total DESC";
            } else {
                $query_marca = "SELECT marca, COUNT(*) as total 
                                FROM " . $this->table_name . " 
                                GROUP BY marca 
                                ORDER BY total DESC";
            }
            
            $stmt_marca = $this->conn->prepare($query_marca);
            $stmt_marca->execute();
            
            while($row = $stmt_marca->fetch(PDO::FETCH_ASSOC)) {
                $result['por_marca'][] = $row;
            }
            
            // Estatísticas por ano
            if($this->colunaAtivoExiste()) {
                $query_ano = "SELECT ano, COUNT(*) as total 
                            FROM " . $this->table_name . " 
                            WHERE ativo = 1 OR ativo IS NULL 
                            GROUP BY ano 
                            ORDER BY ano DESC";
            } else {
                $query_ano = "SELECT ano, COUNT(*) as total 
                            FROM " . $this->table_name . " 
                            GROUP BY ano 
                            ORDER BY ano DESC";
            }
            
            $stmt_ano = $this->conn->prepare($query_ano);
            $stmt_ano->execute();
            
            while($row = $stmt_ano->fetch(PDO::FETCH_ASSOC)) {
                $result['por_ano'][] = $row;
            }
            
            return $result;
        } catch (PDOException $e) {
            // Em caso de erro, retorna array vazio
            return $result;
        }
    }

    // Obter detalhes de quilometragem e contagem de abastecimentos/manutenções
    public function obterKmMedia() {
        $result = [];
        
        try {
            if($this->colunaAtivoExiste()) {
                $query = "SELECT v.id, v.placa, v.modelo, v.marca, v.km_atual, 
                                (SELECT COUNT(*) FROM abastecimentos a WHERE a.veiculo_id = v.id) as total_abastecimentos,
                                (SELECT COUNT(*) FROM manutencoes m WHERE m.veiculo_id = v.id) as total_manutencoes
                        FROM " . $this->table_name . " v
                        WHERE v.ativo = 1 OR v.ativo IS NULL
                        ORDER BY v.placa";
            } else {
                $query = "SELECT v.id, v.placa, v.modelo, v.marca, v.km_atual,
                                (SELECT COUNT(*) FROM abastecimentos a WHERE a.veiculo_id = v.id) as total_abastecimentos,
                                (SELECT COUNT(*) FROM manutencoes m WHERE m.veiculo_id = v.id) as total_manutencoes
                        FROM " . $this->table_name . " v
                        ORDER BY v.placa";
            }
            
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $result[] = $row;
            }
            
            return $result;
        } catch (PDOException $e) {
            // Em caso de erro, tenta uma versão mais simples da consulta
            try {
                $query = "SELECT id, placa, modelo, marca, km_atual 
                        FROM " . $this->table_name . " 
                        ORDER BY placa";
                
                $stmt = $this->conn->prepare($query);
                $stmt->execute();
                
                $simplified_result = [];
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $row['total_abastecimentos'] = 0;
                    $row['total_manutencoes'] = 0;
                    $simplified_result[] = $row;
                }
                
                return $simplified_result;
            } catch (PDOException $e) {
                // Se ainda houver erro, retorna array vazio
                return [];
            }
        }
    }
}
?>
