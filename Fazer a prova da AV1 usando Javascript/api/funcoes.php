<?php
// Funções usadas por todos os arquivos da API (perguntas.php e usuarios.php).
// Cada arquivo da API começa com: require "funcoes.php";

// Toda resposta da API é JSON (o JavaScript lê com resposta.json())
header("Content-Type: application/json; charset=utf-8");

// Fuso horário do Brasil (usado para saber se a data de nascimento está no futuro)
date_default_timezone_set("America/Sao_Paulo");

// Os arquivos .txt ficam fora da pasta api, por isso o "/../"
$arquivo_perguntas = __DIR__ . "/../perguntas/perguntas.txt";
$arquivo_respostas = __DIR__ . "/../respostas/respostas.txt";
$arquivo_usuarios = __DIR__ . "/../usuarios/usuarios.txt";

// Manda a resposta em JSON para o JavaScript e para o PHP aqui
function responder($dados, $codigo_http = 200)
{
    http_response_code($codigo_http);
    echo json_encode($dados, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// Manda uma mensagem de erro: {"erro": "..."}
// 400 = dados errados, 404 = não encontrado, 405 = método errado
function responder_erro($mensagem, $codigo_http = 400)
{
    responder(["erro" => $mensagem], $codigo_http);
}

// Ações que gravam ou apagam alguma coisa só podem vir por POST
function exigir_post()
{
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        responder_erro("Esta ação só aceita POST.", 405);
    }
}

// Lê o JSON que o JavaScript mandou no corpo da requisição (fetch com body)
// e devolve como lista (array). Se não veio nada, devolve lista vazia.
function ler_dados_json()
{
    $dados = json_decode(file_get_contents("php://input"), true);

    if (!is_array($dados)) {
        return [];
    }
    return $dados;
}

// Pega um campo dos dados como texto (se não existir ou não for texto/número, volta "")
function pegar_campo($dados, $nome_campo)
{
    $valor = $dados[$nome_campo] ?? "";

    if (!is_scalar($valor)) {
        return "";
    }
    return (string) $valor;
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

// Tira espaços, quebras de linha e o ";" (que é o separador do arquivo)
function limpar_texto($texto)
{
    $texto = trim($texto);
    $texto = str_replace(["\r", "\n"], " ", $texto);
    $texto = str_replace(";", ",", $texto);
    return $texto;
}

// Descobre o próximo id: pega o id da última linha do arquivo e soma 1
function pegar_proximo_id($arquivo)
{
    $linhas = ler_linhas($arquivo);

    if (empty($linhas)) {
        return 1;
    }

    $ultima_linha = end($linhas);
    $campos = explode(";", $ultima_linha);
    return (int) $campos[0] + 1;
}
