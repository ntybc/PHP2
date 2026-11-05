<?php
require "funcoes_usuarios.php";

// Só aceita dados vindos do formulário
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar_usuarios.php");
    exit;
}

// Pega os dados do formulário
$id_usuario = $_POST["id_usuario"] ?? "";
$nome_usuario = limpar_texto($_POST["nome_usuario"] ?? "");
$data_nascimento = trim($_POST["data_nascimento"] ?? "");
$cpf_usuario = limpar_cpf($_POST["cpf_usuario"] ?? "");
$email_usuario = limpar_texto($_POST["email_usuario"] ?? "");
$senha_usuario = $_POST["senha_usuario"] ?? "";

// Confere os campos (senha só é conferida se foi digitada uma nova)
$lista_erros = validar_dados_usuario($arquivo_usuarios, $nome_usuario, $data_nascimento, $cpf_usuario, $email_usuario, $senha_usuario, false, $id_usuario);

if ($id_usuario === "") {
    $lista_erros[] = "Usuário não informado.";
}

if (!empty($lista_erros)) {
    foreach ($lista_erros as $mensagem_erro) {
        echo "<p>" . htmlspecialchars($mensagem_erro) . "</p>";
    }
    echo '<p><a href="alterar_usuario.php?id_usuario=' . htmlspecialchars($id_usuario) . '">Voltar</a></p>';
    exit;
}

// Troca a linha desse usuário
$usuario_encontrado = false;
$usuarios_atualizados = [];

foreach (ler_linhas($arquivo_usuarios) as $linha_usuario) {
    $campos_usuario = explode(";", $linha_usuario);

    if ($campos_usuario[0] === $id_usuario) {
        $usuario_encontrado = true;

        // Mantém a senha atual, a não ser que tenha sido digitada uma nova
        $senha_gravada = $campos_usuario[5];

        if ($senha_usuario !== "") {
            $senha_gravada = password_hash($senha_usuario, PASSWORD_DEFAULT);
        }

        $linha_usuario = $id_usuario . ";" . $nome_usuario . ";" . $data_nascimento . ";" . $cpf_usuario . ";" . $email_usuario . ";" . $senha_gravada;
    }

    $usuarios_atualizados[] = $linha_usuario;
}

if (!$usuario_encontrado) {
    echo "Usuário não encontrado.";
    echo '<p><a href="listar_usuarios.php">Voltar para a lista</a></p>';
    exit;
}

salvar_linhas($arquivo_usuarios, $usuarios_atualizados);

// Volta para a lista
header("Location: listar_usuarios.php");
exit;