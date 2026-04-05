<?php

class Database {
    // Credenciais do banco de dados (Ajuste conforme seu ambiente local)
    private $host = "localhost";
    private $db_name = "u454088166_financas_db";
    private $username = "u454088166_nogueira"; 
    private $password = "F@lxmen30"; 
    public $conn;

    // Método para obter a conexão com o banco
    public function getConnection() {
        $this->conn = null;

        try {
            // Cria a string de conexão (DSN)
            $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            
            // Instancia o PDO
            $this->conn = new PDO($dsn, $this->username, $this->password);
            
            // ==========================================
            // CONFIGURAÇÕES DE SEGURANÇA E COMPORTAMENTO
            // ==========================================
            
            // 1. Lança exceções (erros) caso algo dê errado no SQL, facilitando o debug
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // 2. Define o formato de retorno padrão como Array Associativo (ex: $linha['nome'])
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // 3. Desativa emulações de prepared statements (Força o MySQL a fazer o prepared statement real, mais seguro)
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            
        } catch(PDOException $exception) {
            // Em um ambiente de produção real, você guardaria isso em um log de erros
            // e mostraria uma mensagem amigável ao usuário.
            die("Erro de conexão com o banco de dados: " . $exception->getMessage());
        }

        return $this->conn;
    }
}
?>