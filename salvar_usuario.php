<?php
require "funcoes_usuarios.php";

// Só aceita dados vindos do formulário
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: criar_usuario.php");
    exit;
}

// Pega os dados do formulário
$nome_usuario = limpar_texto($_POST["nome_usuario"] ?? "");
$data_nascimento = trim($_POST["data_nascimento"] ?? "");
$cpf_usuario = limpar_cpf($_POST["cpf_usuario"] ?? "");
$email_usuario = limpar_texto($_POST["email_usuario"] ?? "");
$senha_usuario = $_POST["senha_usuario"] ?? "";

// Confere os campos (senha obrigatória, usuário novo ainda não tem id)
$lista_erros = validar_dados_usuario($arquivo_usuarios, $nome_usuario, $data_nascimento, $cpf_usuario, $email_usuario, $senha_usuario, true, "");

if (!empty($lista_erros)) {
    foreach ($lista_erros as $mensagem_erro) {
        echo "<p>" . htmlspecialchars($mensagem_erro) . "</p>";
    }
    echo '<p><a href="criar_usuario.php">Voltar</a></p>';
    exit;
}

// A senha nunca é gravada em texto puro, só o "hash" dela
$senha_gravada = password_hash($senha_usuario, PASSWORD_DEFAULT);

$id_usuario = pegar_proximo_id($arquivo_usuarios);

$linha_usuario = $id_usuario . ";" . $nome_usuario . ";" . $data_nascimento . ";" . $cpf_usuario . ";" . $email_usuario . ";" . $senha_gravada . "\n";
file_put_contents($arquivo_usuarios, $linha_usuario, FILE_APPEND | LOCK_EX);

// Volta para a lista
header("Location: listar_usuarios.php");
exit;