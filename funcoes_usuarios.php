<?php
// Funções usadas por todas as telas de usuários.
// Cada arquivo de usuário começa com: require "funcoes_usuarios.php";

$arquivo_usuarios = "usuarios/usuarios.txt";

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

// Deixa só os números do CPF (tira pontos, traço e espaços)
function limpar_cpf($cpf)
{
    return preg_replace("/\D/", "", $cpf);
}

// Confere se o CPF (só números) é válido, usando os dois dígitos verificadores
function cpf_valido($cpf)
{
    if (strlen($cpf) !== 11) {
        return false;
    }

    // CPF com todos os números iguais (ex: 11111111111) não vale
    if (preg_match('/^(\d)\1{10}$/', $cpf)) {
        return false;
    }

    // Calcula o 1º dígito (tamanho 9) e depois o 2º (tamanho 10)
    for ($tamanho = 9; $tamanho <= 10; $tamanho++) {
        $soma = 0;

        for ($posicao = 0; $posicao < $tamanho; $posicao++) {
            $soma += (int) $cpf[$posicao] * (($tamanho + 1) - $posicao);
        }

        $digito_certo = ((10 * $soma) % 11) % 10;

        if ((int) $cpf[$tamanho] !== $digito_certo) {
            return false;
        }
    }

    return true;
}

// Mostra o CPF assim: 123.456.789-09
function formatar_cpf($cpf)
{
    if (strlen($cpf) !== 11) {
        return $cpf;
    }

    return substr($cpf, 0, 3) . "." . substr($cpf, 3, 3) . "." . substr($cpf, 6, 3) . "-" . substr($cpf, 9, 2);
}

// Confere se a data (AAAA-MM-DD) existe e não está no futuro
function data_valida($data)
{
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $data, $partes)) {
        return false;
    }

    $ano = (int) $partes[1];
    $mes = (int) $partes[2];
    $dia = (int) $partes[3];

    if (!checkdate($mes, $dia, $ano)) {
        return false;
    }

    // Datas no formato AAAA-MM-DD podem ser comparadas como texto
    return $data <= date("Y-m-d");
}

// Troca 2000-05-20 por 20/05/2000 para mostrar na tela
function formatar_data($data)
{
    $partes = explode("-", $data);

    if (count($partes) !== 3) {
        return $data;
    }

    return $partes[2] . "/" . $partes[1] . "/" . $partes[0];
}

// Confere todos os campos e devolve uma lista com as mensagens de erro (lista vazia = tudo certo)
// $senha_obrigatoria: true ao criar, false ao alterar (senha vazia mantém a atual)
// $id_usuario_atual: ao alterar, é o id do próprio usuário (para ele não "repetir" consigo mesmo)
function validar_dados_usuario($arquivo_usuarios, $nome_usuario, $data_nascimento, $cpf_usuario, $email_usuario, $senha_usuario, $senha_obrigatoria, $id_usuario_atual)
{
    $lista_erros = [];

    if ($nome_usuario === "") {
        $lista_erros[] = "Preencha o nome.";
    }

    if (!data_valida($data_nascimento)) {
        $lista_erros[] = "Data de nascimento inválida.";
    }

    if (!cpf_valido($cpf_usuario)) {
        $lista_erros[] = "CPF inválido.";
    }

    if (!filter_var($email_usuario, FILTER_VALIDATE_EMAIL)) {
        $lista_erros[] = "E-mail inválido.";
    }

    if (($senha_obrigatoria || $senha_usuario !== "") && strlen($senha_usuario) < 6) {
        $lista_erros[] = "A senha precisa ter pelo menos 6 caracteres.";
    }

    // Não pode repetir CPF nem e-mail de outro usuário
    foreach (ler_linhas($arquivo_usuarios) as $linha_usuario) {
        $campos_usuario = explode(";", $linha_usuario);

        if ($campos_usuario[0] === $id_usuario_atual) {
            continue;
        }

        if ($campos_usuario[3] === $cpf_usuario) {
            $lista_erros[] = "Já existe um usuário com esse CPF.";
        }

        if (strtolower($campos_usuario[4]) === strtolower($email_usuario)) {
            $lista_erros[] = "Já existe um usuário com esse e-mail.";
        }
    }

    return $lista_erros;
}