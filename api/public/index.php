<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Dotenv\Dotenv;
use App\Config\Database;
use App\Models\User;

require __DIR__ . '/../vendor/autoload.php';

// Carrega as variáveis do arquivo .env (se existir) para $_ENV
Dotenv::createImmutable(__DIR__ . '/..')->safeLoad();

$app = AppFactory::create();

// Permite ler o corpo JSON das requisições com $request->getParsedBody()
$app->addBodyParsingMiddleware();

// Detalhes dos erros só são exibidos quando APP_DEBUG=true (nunca em produção)
$debug = filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN);
$app->addErrorMiddleware($debug, true, true);

// Função auxiliar para escrever respostas JSON
function jsonResponse(Response $response, array $data, int $status = 200): Response {
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
    return $response->withHeader('Content-Type', 'application/json')->withStatus($status);
}

// Rota raiz para testar se a API está online
$app->get('/', function (Request $request, Response $response, $args) {
    return jsonResponse($response, ['message' => 'PyPhiFlow API Gateway está online!']);
});

//ROTA: Cadastro de Usuário
$app->post('/api/users/register', function (Request $request, Response $response, $args) {

    // Pegamos os dados que foram enviados no corpo da requisição (JSON) como um array associativo do PHP
    $input = $request->getParsedBody();
    if (!is_array($input)) {
        $input = [];
    }

    $name = trim((string) ($input['name'] ?? ''));
    $email = trim((string) ($input['email'] ?? ''));
    $password = (string) ($input['password'] ?? '');

    //validação lógica básica: Verifica se o usuário preencheu os campos essenciais
    if ($name === '' || $email === '' || $password === '') {
        return jsonResponse($response, [
            'status' => 'Erro',
            'message' => 'Dados incompletos. Nome, email e senha são obrigatórios.'
        ], 400); //Bad request
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        return jsonResponse($response, [
            'status' => 'Erro',
            'message' => 'Email inválido.'
        ], 400);
    }

    if (mb_strlen($name) > 100) {
        return jsonResponse($response, [
            'status' => 'Erro',
            'message' => 'O nome deve ter no máximo 100 caracteres.'
        ], 400);
    }

    // O BCRYPT considera apenas os primeiros 72 bytes da senha
    if (mb_strlen($password) < 8 || strlen($password) > 72) {
        return jsonResponse($response, [
            'status' => 'Erro',
            'message' => 'A senha deve ter entre 8 e 72 caracteres.'
        ], 400);
    }

    try {
        // Conectamos ao banco de dados
        $database = new Database();
        $db = $database->getConnection();

        // Instancia o Model do usuário, passando a conexão do banco para ele
        $user = new User($db);

        //Alimenta as propriedades do objeto com os dados válidos
        $user->name = $name;
        $user->email = $email;
        $user->password = $password;

        if ($user->emailExists()) {
            return jsonResponse($response, [
                'status' => 'Erro',
                'message' => 'Este email já está cadastrado.'
            ], 409); //Conflict
        }

        // Chama o método create() do model e avaliamos o resultado
        if ($user->create()) {
            return jsonResponse($response, [
                'status' => 'Sucesso',
                'message' => 'Usuário cadastrado com sucesso!',
                'id' => $user->id
            ], 201); //Created
        }
    } catch (PDOException $e) {
        // Código 23000 = violação de restrição (ex.: email duplicado cadastrado ao mesmo tempo)
        if ($e->getCode() === '23000') {
            return jsonResponse($response, [
                'status' => 'Erro',
                'message' => 'Este email já está cadastrado.'
            ], 409);
        }
        error_log('Erro ao cadastrar usuário: ' . $e->getMessage());
    }

    return jsonResponse($response, [
        'status' => 'Erro',
        'message' => 'Não foi possível cadastrar o usuário.'
    ], 500); //Internal Server Error
});

$app->run();
