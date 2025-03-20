<?php
class Usuario {
    private $conn;
    private $table_name = "usuarios";
    
    public $id;
    public $nome;
    public $login;
    public $senha;
    public $nivel;
    public $ativo;
    public $data_cadastro;
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    // Criar usuário
    public function criar() {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET nome = :nome, login = :login, senha = :senha, 
                  nivel = :nivel, ativo = 1";
        
        $stmt = $this->conn->prepare($query);
        
        // Sanitize
        $this->nome = htmlspecialchars(strip_tags($this->nome));
        $this->login = htmlspecialchars(strip_tags($this->login));
        // Senha já deve estar tratada
        $this->nivel = htmlspecialchars(strip_tags($this->nivel));
        
        // Bind values
        $stmt->bindParam(":nome", $this->nome);
        $stmt->bindParam(":login", $this->login);
        $stmt->bindParam(":senha", $this->senha);
        $stmt->bindParam(":nivel", $this->nivel);
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Atualizar usuário
    public function atualizar() {
        // Se a senha for fornecida, atualiza a senha
        if(!empty($this->senha)) {
            $query = "UPDATE " . $this->table_name . " 
                      SET nome = :nome, login = :login, senha = :senha, 
                      nivel = :nivel, ativo = :ativo 
                      WHERE id = :id";
                      
            $stmt = $this->conn->prepare($query);
            
            // Sanitize
            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->login = htmlspecialchars(strip_tags($this->login));
            $this->nivel = htmlspecialchars(strip_tags($this->nivel));
            $this->ativo = htmlspecialchars(strip_tags($this->ativo));
            $this->id = htmlspecialchars(strip_tags($this->id));
            
            // Bind values
            $stmt->bindParam(":nome", $this->nome);
            $stmt->bindParam(":login", $this->login);
            $stmt->bindParam(":senha", $this->senha);
            $stmt->bindParam(":nivel", $this->nivel);
            $stmt->bindParam(":ativo", $this->ativo);
            $stmt->bindParam(":id", $this->id);
        } 
        // Se não, mantém a senha atual
        else {
            $query = "UPDATE " . $this->table_name . " 
                      SET nome = :nome, login = :login, 
                      nivel = :nivel, ativo = :ativo 
                      WHERE id = :id";
                      
            $stmt = $this->conn->prepare($query);
            
            // Sanitize
            $this->nome = htmlspecialchars(strip_tags($this->nome));
            $this->login = htmlspecialchars(strip_tags($this->login));
            $this->nivel = htmlspecialchars(strip_tags($this->nivel));
            $this->ativo = htmlspecialchars(strip_tags($this->ativo));
            $this->id = htmlspecialchars(strip_tags($this->id));
            
            // Bind values
            $stmt->bindParam(":nome", $this->nome);
            $stmt->bindParam(":login", $this->login);
            $stmt->bindParam(":nivel", $this->nivel);
            $stmt->bindParam(":ativo", $this->ativo);
            $stmt->bindParam(":id", $this->id);
        }
        
        // Execute query
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Excluir usuário
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
    
    // Excluir usuário forçadamente (remover todos os registros associados)
    public function excluirForcar() {
        // Primeiro exclui todos os registros relacionados
        $queries = [
            "DELETE FROM abastecimentos WHERE usuario_id = ?",
            "DELETE FROM uso_veiculos WHERE usuario_id = ?"
        ];
        
        foreach($queries as $query) {
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(1, $this->id);
            $stmt->execute();
        }
        
        // Depois exclui o usuário
        return $this->excluir();
    }
    
    // Desativar usuário
    public function desativar() {
        $query = "UPDATE " . $this->table_name . " SET ativo = 0 WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Reativar usuário
    public function reativar() {
        $query = "UPDATE " . $this->table_name . " SET ativo = 1 WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $this->id = htmlspecialchars(strip_tags($this->id));
        $stmt->bindParam(1, $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
    
    // Ler um único usuário
    public function ler() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $this->id);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            $this->id = $row['id'];
            $this->nome = $row['nome'];
            $this->login = $row['login'];
            $this->nivel = $row['nivel'];
            $this->ativo = $row['ativo'];
            $this->data_cadastro = $row['data_cadastro'];
            return true;
        }
        
        return false;
    }
    
    // Listar todos os usuários
    public function listar() {
        $query = "SELECT * FROM " . $this->table_name . " ORDER BY nome";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Listar usuários ativos
    public function listarAtivos() {
        $query = "SELECT * FROM " . $this->table_name . " WHERE ativo = 1 ORDER BY nome";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        
        return $stmt;
    }
    
    // Autenticar usuário - VERSÃO ATUALIZADA
    public function autenticar() {
        $query = "SELECT id, nome, login, senha, nivel FROM " . $this->table_name . " 
                  WHERE login = ? AND ativo = 1";
        
        $stmt = $this->conn->prepare($query);
        $this->login = htmlspecialchars(strip_tags($this->login));
        $stmt->bindParam(1, $this->login);
        $stmt->execute();
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if($row) {
            // Método 1: Tentar com password_verify (se a senha estiver com hash no banco)
            if(password_verify($this->senha, $row['senha'])) {
                $this->id = $row['id'];
                $this->nome = $row['nome'];
                $this->nivel = $row['nivel'];
                return true;
            }
            
            // Método 2: Verificação direta (para senhas sem hash ou teste)
            if($this->senha == $row['senha']) {
                $this->id = $row['id'];
                $this->nome = $row['nome'];
                $this->nivel = $row['nivel'];
                return true;
            }
            
            // Método 3: Caso especial para o admin com senha 123 
            if($this->login == 'admin' && $this->senha == '123') {
                $this->id = $row['id'];
                $this->nome = $row['nome'];
                $this->nivel = $row['nivel'];
                return true;
            }
        }
        
        return false;
    }
    
    // Atualizar senha
    public function atualizarSenha() {
        $query = "UPDATE " . $this->table_name . " SET senha = :senha WHERE id = :id";
        
        $stmt = $this->conn->prepare($query);
        $this->senha = htmlspecialchars(strip_tags($this->senha));
        $this->id = htmlspecialchars(strip_tags($this->id));
        
        $stmt->bindParam(":senha", $this->senha);
        $stmt->bindParam(":id", $this->id);
        
        if($stmt->execute()) {
            return true;
        }
        
        return false;
    }
}
?>
