<?php
// Funções só dos usuários (CPF, data e validação do cadastro).
// Mesmas funções do funcoes_usuarios.php da AV1, sem mudar as regras.

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

// Transforma a linha do arquivo em dados para o JSON.
// A senha (campo 5) NUNCA vai para o JavaScript.
function montar_usuario($campos_usuario)
{
    return [
        "id_usuario" => $campos_usuario[0],
        "nome_usuario" => $campos_usuario[1],
        "data_nascimento" => $campos_usuario[2],
        "data_nascimento_formatada" => formatar_data($campos_usuario[2]),
        "cpf_usuario" => $campos_usuario[3],
        "cpf_formatado" => formatar_cpf($campos_usuario[3]),
        "email_usuario" => $campos_usuario[4]
    ];
}
