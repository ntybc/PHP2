<?php
// ÚNICO lugar com os dados de conexão com o banco (MySQL/MariaDB do XAMPP).
// Se o seu MySQL tiver outra senha ou outro nome de usuário, mude só aqui.
$servidor = "127.0.0.1"; // (127.0.0.1 = este computador, direto por TCP)
$porta = "3306";
$nome_banco = "av1_perguntas";
$usuario_banco = "root";
$senha_banco = ""; // no XAMPP o root vem sem senha

try {
    $pdo = new PDO(
        "mysql:host=" . $servidor . ";port=" . $porta . ";dbname=" . $nome_banco . ";charset=utf8mb4",
        $usuario_banco,
        $senha_banco,
        [
            // Se der erro no SQL, o PDO lança uma exceção (em vez de falhar calado)
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            // fetch() devolve uma lista com o nome das colunas
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            // Usa prepared statements de verdade (protege contra SQL injection)
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $erro) {
    // Todas as telas esperam JSON, então o erro também sai em JSON
    header("Content-Type: application/json; charset=utf-8");
    http_response_code(500);
    echo json_encode(
        ["erro" => "Não foi possível conectar ao banco de dados. Confira se o MySQL está ligado e se o banco foi criado (sql/banco.sql)."],
        JSON_UNESCAPED_UNICODE
    );
    exit;
}
