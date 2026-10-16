<?php

namespace App\Config;

use PDO;

class Database {
    private $host;
    private $port;
    private $db_name;
    private $username;
    private $password;
    private $conn;

    public function __construct() {
        // As credenciais vêm das variáveis de ambiente (docker-compose ou arquivo .env), nunca fixas no código
        $this->host = self::env('DB_HOST', 'mysql');
        $this->port = self::env('DB_PORT', '3306');
        $this->db_name = self::env('DB_NAME', 'pyphiflow');
        $this->username = self::env('DB_USER', '');
        $this->password = self::env('DB_PASSWORD', '');
    }

    // Lê uma variável de ambiente, seja carregada pelo Dotenv ($_ENV) ou definida pelo Docker (getenv)
    private static function env(string $key, string $default): string {
        $value = $_ENV[$key] ?? getenv($key);
        return ($value === false || $value === '') ? $default : (string) $value;
    }

    // Lança PDOException em caso de falha; quem chama decide como responder
    public function getConnection(): PDO {
        if ($this->conn === null) {
            //Cria a linha de conexão (DSN) dizendo o tipo de banco, host, porta e o nome do banco
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name . ";charset=utf8mb4";
            //instancia a conexão usando o PDO, passando o DSN, usuário e senha
            $this->conn = new PDO($dsn, $this->username, $this->password, [
                //Configura o PDO para lançar exceções em caso de erro
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return $this->conn;
    }
}
