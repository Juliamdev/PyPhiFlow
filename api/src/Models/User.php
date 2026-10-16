<?php
namespace App\Models;

use PDO;

class User {
    //Atributos privados para isolar a conexão e o nome da tabela
    private $conn;
    private $table_name = "users";

    //propriedades que representam as colunas da tabela do banco
    public $id;
    public $name;
    public $email;
    public $password;
    public $created_at;

    //O construtor. Toda vez que criamos um novo usuário precisamos passar a conexão com o banco
    public function __construct(PDO $db) {
        $this->conn = $db;
    }

    //Verifica se já existe um usuário com o email informado
    public function emailExists(): bool {
        $query = "SELECT id FROM " . $this->table_name . " WHERE email = :email LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(":email", $this->email);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    //Método lógico: cadastrar um novo usuário
    public function create(): bool {
        //Criamos a query SQL usando "Named placeholders" (:name, :email, etc) para evitar SQL Injection
        $query = "INSERT INTO " . $this->table_name . "
                  SET name = :name, email = :email, password_hash = :password_hash";

        // Preparamos a query no PDO
        $stmt = $this->conn->prepare($query);

        //Removemos espaços extras. O escape de HTML deve ser feito na hora de exibir, não ao salvar
        $this->name = trim($this->name);
        $this->email = strtolower(trim($this->email));

        //Vinculamos os valores reais aos placeholders do SQL
        $stmt->bindValue(":name", $this->name);
        $stmt->bindValue(":email", $this->email);

        //Criptografia de senha. A senha não é salva como texto limpo no banco
        //A senha é usada exatamente como foi digitada (sem sanitização), senão ela seria alterada
        $hashed_password = password_hash($this->password, PASSWORD_BCRYPT);
        $stmt->bindValue(":password_hash", $hashed_password);

        //Executamos a query. Se tudo der certo retorna true
        if ($stmt->execute()) {
            $this->id = (int) $this->conn->lastInsertId();
            return true;
        }
        return false;
    }
}
