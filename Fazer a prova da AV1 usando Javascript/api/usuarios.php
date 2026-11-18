<?php
// API dos usuários: o JavaScript chama este arquivo e recebe JSON de volta.
// A ação vem no endereço, por exemplo: api/usuarios.php?acao=listar
//
// Ações:
//   listar   (GET)  lista os usuários (sem a senha!)
//   ver      (GET)  um usuário: ?acao=ver&id_usuario=1
//   criar    (POST) cria usuário
//   alterar  (POST) altera usuário (senha vazia mantém a atual)
//   excluir  (POST) exclui usuário
require "funcoes.php";
require "funcoes_usuarios.php";

// Quando tem erro de validação, manda todas as mensagens:
// {"erro": "mensagens juntas", "erros": ["mensagem 1", "mensagem 2"]}
function responder_erros_validacao($lista_erros)
{
    responder([
        "erro" => implode(" ", $lista_erros),
        "erros" => $lista_erros
    ], 400);
}

// ---------- listar ----------
function listar_usuarios($arquivo_usuarios)
{
    $lista_usuarios = [];

    foreach (ler_linhas($arquivo_usuarios) as $linha_usuario) {
        // Linha do usuário: id_usuario;nome_usuario;data_nascimento;cpf;email_usuario;senha_usuario
        $campos_usuario = explode(";", $linha_usuario);
        $lista_usuarios[] = montar_usuario($campos_usuario);
    }

    responder(["usuarios" => $lista_usuarios]);
}

// ---------- ver ----------
function ver_usuario($arquivo_usuarios)
{
    // O id vem no endereço: api/usuarios.php?acao=ver&id_usuario=1
    $id_usuario_procurado = pegar_campo($_GET, "id_usuario");

    foreach (ler_linhas($arquivo_usuarios) as $linha_usuario) {
        $campos_usuario = explode(";", $linha_usuario);

        if ($campos_usuario[0] === $id_usuario_procurado) {
            responder(["usuario" => montar_usuario($campos_usuario)]);
        }
    }

    responder_erro("Usuário não encontrado.", 404);
}

// ---------- criar ----------
function criar_usuario($arquivo_usuarios)
{
    exigir_post();
    $dados = ler_dados_json();

    $nome_usuario = limpar_texto(pegar_campo($dados, "nome_usuario"));
    $data_nascimento = trim(pegar_campo($dados, "data_nascimento"));
    $cpf_usuario = limpar_cpf(pegar_campo($dados, "cpf_usuario"));
    $email_usuario = limpar_texto(pegar_campo($dados, "email_usuario"));
    $senha_usuario = pegar_campo($dados, "senha_usuario");

    // Confere os campos (senha obrigatória, usuário novo ainda não tem id)
    $lista_erros = validar_dados_usuario($arquivo_usuarios, $nome_usuario, $data_nascimento, $cpf_usuario, $email_usuario, $senha_usuario, true, "");

    if (!empty($lista_erros)) {
        responder_erros_validacao($lista_erros);
    }

    // A senha nunca é gravada em texto puro, só o "hash" dela
    $senha_gravada = password_hash($senha_usuario, PASSWORD_DEFAULT);

    $id_usuario = pegar_proximo_id($arquivo_usuarios);

    $linha_usuario = $id_usuario . ";" . $nome_usuario . ";" . $data_nascimento . ";" . $cpf_usuario . ";" . $email_usuario . ";" . $senha_gravada . "\n";
    file_put_contents($arquivo_usuarios, $linha_usuario, FILE_APPEND | LOCK_EX);

    responder([
        "mensagem" => "Usuário salvo com sucesso! (id " . $id_usuario . ")",
        "id_usuario" => $id_usuario
    ], 201);
}

// ---------- alterar ----------
function alterar_usuario($arquivo_usuarios)
{
    exigir_post();
    $dados = ler_dados_json();

    $id_usuario = pegar_campo($dados, "id_usuario");
    $nome_usuario = limpar_texto(pegar_campo($dados, "nome_usuario"));
    $data_nascimento = trim(pegar_campo($dados, "data_nascimento"));
    $cpf_usuario = limpar_cpf(pegar_campo($dados, "cpf_usuario"));
    $email_usuario = limpar_texto(pegar_campo($dados, "email_usuario"));
    $senha_usuario = pegar_campo($dados, "senha_usuario");

    // Confere os campos (senha só é conferida se foi digitada uma nova)
    $lista_erros = validar_dados_usuario($arquivo_usuarios, $nome_usuario, $data_nascimento, $cpf_usuario, $email_usuario, $senha_usuario, false, $id_usuario);

    if ($id_usuario === "") {
        $lista_erros[] = "Usuário não informado.";
    }

    if (!empty($lista_erros)) {
        responder_erros_validacao($lista_erros);
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
        responder_erro("Usuário não encontrado.", 404);
    }

    salvar_linhas($arquivo_usuarios, $usuarios_atualizados);

    responder(["mensagem" => "Usuário alterado com sucesso!"]);
}

// ---------- excluir ----------
function excluir_usuario($arquivo_usuarios)
{
    exigir_post();
    $dados = ler_dados_json();

    $id_usuario_excluir = pegar_campo($dados, "id_usuario");

    if ($id_usuario_excluir === "") {
        responder_erro("Usuário não informado.");
    }

    // Mantém todos os usuários, menos o que tem o id escolhido
    $usuario_encontrado = false;
    $usuarios_que_ficam = [];

    foreach (ler_linhas($arquivo_usuarios) as $linha_usuario) {
        $campos_usuario = explode(";", $linha_usuario);

        if ($campos_usuario[0] !== $id_usuario_excluir) {
            $usuarios_que_ficam[] = $linha_usuario;
        } else {
            $usuario_encontrado = true;
        }
    }

    if (!$usuario_encontrado) {
        responder_erro("Usuário não encontrado.", 404);
    }

    salvar_linhas($arquivo_usuarios, $usuarios_que_ficam);

    responder(["mensagem" => "Usuário excluído com sucesso!"]);
}

// ---------- escolhe a ação ----------
$acao = pegar_campo($_GET, "acao");

switch ($acao) {
    case "listar":
        listar_usuarios($arquivo_usuarios);
        break;
    case "ver":
        ver_usuario($arquivo_usuarios);
        break;
    case "criar":
        criar_usuario($arquivo_usuarios);
        break;
    case "alterar":
        alterar_usuario($arquivo_usuarios);
        break;
    case "excluir":
        excluir_usuario($arquivo_usuarios);
        break;
    default:
        responder_erro("Ação inválida.");
}
