<?php
$arquivo_perguntas = "perguntas/perguntas.txt";
$arquivo_respostas = "respostas/respostas.txt";

// Só aceita dados vindos do formulário (excluir não pode acontecer só abrindo o endereço)
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar_perguntas.php");
    exit;
}

// Lê o arquivo e devolve uma lista com as linhas (ou lista vazia se não existir)
function ler_linhas($arquivo)
{
    if (!file_exists($arquivo)) {
        return [];
    }
    return file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

// Grava a lista de linhas no arquivo, trocando o conteúdo antigo
function salvar_linhas($arquivo, $linhas)
{
    $conteudo = "";

    if (!empty($linhas)) {
        $conteudo = implode("\n", $linhas) . "\n";
    }

    file_put_contents($arquivo, $conteudo, LOCK_EX);
}

$id_pergunta_excluir = $_POST["id_pergunta"] ?? "";

if ($id_pergunta_excluir === "") {
    header("Location: listar_perguntas.php");
    exit;
}

// Perguntas: mantém todas, menos a que tem o id escolhido
$perguntas_que_ficam = [];

foreach (ler_linhas($arquivo_perguntas) as $linha_pergunta) {
    $campos_pergunta = explode(";", $linha_pergunta);

    if ($campos_pergunta[0] !== $id_pergunta_excluir) {
        $perguntas_que_ficam[] = $linha_pergunta;
    }
}

// Respostas: mantém todas, menos as que pertencem a essa pergunta
$respostas_que_ficam = [];

foreach (ler_linhas($arquivo_respostas) as $linha_resposta) {
    $campos_resposta = explode(";", $linha_resposta);

    if ($campos_resposta[1] !== $id_pergunta_excluir) {
        $respostas_que_ficam[] = $linha_resposta;
    }
}

salvar_linhas($arquivo_perguntas, $perguntas_que_ficam);
salvar_linhas($arquivo_respostas, $respostas_que_ficam);

// Volta para a lista
header("Location: listar_perguntas.php");
exit;