<?php
// API dos usuários: o JavaScript chama este arquivo e recebe JSON de volta.
// A ação vem no endereço, por exemplo: api/usuarios.php?acao=listar
// Os dados ficam na tabela "usuarios" do banco (antes era o usuarios.txt).
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

// Procura o usuário pelo id e devolve a linha do banco (ou false se não achar)
function buscar_usuario($pdo, $id_usuario)
{
    $comando = $pdo->prepare("SELECT * FROM usuarios WHERE id_usuario = ?");
    $comando->execute([(int) $id_usuario]);
    return $comando->fetch();
}

// ---------- listar ----------
function listar_usuarios($pdo)
{
    $lista_usuarios = [];

    $comando = $pdo->query("SELECT * FROM usuarios ORDER BY id_usuario");

    foreach ($comando->fetchAll() as $linha_usuario) {
        $lista_usuarios[] = montar_usuario($linha_usuario);
    }

    responder(["usuarios" => $lista_usuarios]);
}

// ---------- ver ----------
function ver_usuario($pdo)
{
    // O id vem no endereço: api/usuarios.php?acao=ver&id_usuario=1
    $id_usuario_procurado = pegar_campo($_GET, "id_usuario");

    $linha_usuario = buscar_usuario($pdo, $id_usuario_procurado);

    if (!$linha_usuario) {
        responder_erro("Usuário não encontrado.", 404);
    }

    responder(["usuario" => montar_usuario($linha_usuario)]);
}

// ---------- criar ----------
function criar_usuario($pdo)
{
    exigir_post();
    $dados = ler_dados_json();

    $nome_usuario = limpar_texto(pegar_campo($dados, "nome_usuario"));
    $data_nascimento = trim(pegar_campo($dados, "data_nascimento"));
    $cpf_usuario = limpar_cpf(pegar_campo($dados, "cpf_usuario"));
    $email_usuario = limpar_texto(pegar_campo($dados, "email_usuario"));
    $senha_usuario = pegar_campo($dados, "senha_usuario");

    // Confere os campos (senha obrigatória, usuário novo ainda não tem id)
    $lista_erros = validar_dados_usuario($pdo, $nome_usuario, $data_nascimento, $cpf_usuario, $email_usuario, $senha_usuario, true, "");

    if (!empty($lista_erros)) {
        responder_erros_validacao($lista_erros);
    }

    // A senha nunca é gravada em texto puro, só o "hash" dela
    $senha_gravada = password_hash($senha_usuario, PASSWORD_DEFAULT);

    // O id é gerado pelo banco (AUTO_INCREMENT)
    $comando = $pdo->prepare("INSERT INTO usuarios (nome_usuario, data_nascimento, cpf, email_usuario, senha_usuario) VALUES (?, ?, ?, ?, ?)");
    $comando->execute([$nome_usuario, $data_nascimento, $cpf_usuario, $email_usuario, $senha_gravada]);

    $id_usuario = (int) $pdo->lastInsertId();

    responder([
        "mensagem" => "Usuário salvo com sucesso! (id " . $id_usuario . ")",
        "id_usuario" => $id_usuario
    ], 201);
}

// ---------- alterar ----------
function alterar_usuario($pdo)
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
    $lista_erros = validar_dados_usuario($pdo, $nome_usuario, $data_nascimento, $cpf_usuario, $email_usuario, $senha_usuario, false, $id_usuario);

    if ($id_usuario === "") {
        $lista_erros[] = "Usuário não informado.";
    }

    if (!empty($lista_erros)) {
        responder_erros_validacao($lista_erros);
    }

    if (!buscar_usuario($pdo, $id_usuario)) {
        responder_erro("Usuário não encontrado.", 404);
    }

    if ($senha_usuario !== "") {
        // Veio senha nova: troca tudo, inclusive a senha
        $senha_gravada = password_hash($senha_usuario, PASSWORD_DEFAULT);

        $comando = $pdo->prepare("UPDATE usuarios SET nome_usuario = ?, data_nascimento = ?, cpf = ?, email_usuario = ?, senha_usuario = ? WHERE id_usuario = ?");
        $comando->execute([$nome_usuario, $data_nascimento, $cpf_usuario, $email_usuario, $senha_gravada, (int) $id_usuario]);
    } else {
        // Sem senha nova: mantém a senha que já estava no banco
        $comando = $pdo->prepare("UPDATE usuarios SET nome_usuario = ?, data_nascimento = ?, cpf = ?, email_usuario = ? WHERE id_usuario = ?");
        $comando->execute([$nome_usuario, $data_nascimento, $cpf_usuario, $email_usuario, (int) $id_usuario]);
    }

    responder(["mensagem" => "Usuário alterado com sucesso!"]);
}

// ---------- excluir ----------
function excluir_usuario($pdo)
{
    exigir_post();
    $dados = ler_dados_json();

    $id_usuario_excluir = pegar_campo($dados, "id_usuario");

    if ($id_usuario_excluir === "") {
        responder_erro("Usuário não informado.");
    }

    if (!buscar_usuario($pdo, $id_usuario_excluir)) {
        responder_erro("Usuário não encontrado.", 404);
    }

    $comando = $pdo->prepare("DELETE FROM usuarios WHERE id_usuario = ?");
    $comando->execute([(int) $id_usuario_excluir]);

    responder(["mensagem" => "Usuário excluído com sucesso!"]);
}

// ---------- escolhe a ação ----------
$acao = pegar_campo($_GET, "acao");

switch ($acao) {
    case "listar":
        listar_usuarios($pdo);
        break;
    case "ver":
        ver_usuario($pdo);
        break;
    case "criar":
        criar_usuario($pdo);
        break;
    case "alterar":
        alterar_usuario($pdo);
        break;
    case "excluir":
        excluir_usuario($pdo);
        break;
    default:
        responder_erro("Ação inválida.");
}
