<?php
require "funcoes_usuarios.php";

// Só aceita dados vindos do formulário (excluir não pode acontecer só abrindo o endereço)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar_usuarios.php");
    exit;
}

$id_usuario_excluir = $_POST["id_usuario"] ?? "";

if ($id_usuario_excluir === "") {
    header("Location: listar_usuarios.php");
    exit;
}

// Mantém todos os usuários, menos o que tem o id escolhido
$usuarios_que_ficam = [];

foreach (ler_linhas($arquivo_usuarios) as $linha_usuario) {
    $campos_usuario = explode(";", $linha_usuario);

    if ($campos_usuario[0] !== $id_usuario_excluir) {
        $usuarios_que_ficam[] = $linha_usuario;
    }
}

salvar_linhas($arquivo_usuarios, $usuarios_que_ficam);

// Volta para a lista
header("Location: listar_usuarios.php");
exit;