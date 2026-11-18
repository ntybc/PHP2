<?php
// API das perguntas: o JavaScript chama este arquivo e recebe JSON de volta.
// A ação vem no endereço, por exemplo: api/perguntas.php?acao=listar
// Os dados ficam nas tabelas "perguntas" e "respostas" do banco (antes eram os .txt).
//
// Ações:
//   listar            (GET)  lista todas as perguntas com as respostas
//   ver               (GET)  uma pergunta: ?acao=ver&id_pergunta=1
//   criar_multipla    (POST) cria pergunta de múltipla escolha (5 respostas)
//   criar_texto       (POST) cria pergunta de texto (resposta de orientação opcional)
//   alterar_multipla  (POST) altera pergunta de múltipla escolha e as respostas
//   alterar_texto     (POST) altera pergunta de texto e a resposta de orientação
//   excluir           (POST) exclui a pergunta e as respostas dela
require "funcoes.php";

// Procura a pergunta pelo id e devolve os dados dela (ou null se não achar)
function buscar_pergunta($pdo, $id_pergunta_procurada)
{
    $comando = $pdo->prepare("SELECT id_pergunta, texto_pergunta, tipo_pergunta FROM perguntas WHERE id_pergunta = ?");
    $comando->execute([(int) $id_pergunta_procurada]);
    $linha_pergunta = $comando->fetch();

    if (!$linha_pergunta) {
        return null;
    }

    return [
        "id_pergunta" => (string) $linha_pergunta["id_pergunta"],
        "texto_pergunta" => $linha_pergunta["texto_pergunta"],
        "tipo_pergunta" => $linha_pergunta["tipo_pergunta"]
    ];
}

// Junta as respostas que têm o mesmo id_pergunta
function buscar_respostas($pdo, $id_pergunta)
{
    $lista_respostas = [];

    $comando = $pdo->prepare("SELECT id_resposta, texto_resposta, is_resposta_certa FROM respostas WHERE id_pergunta = ? ORDER BY id_resposta");
    $comando->execute([(int) $id_pergunta]);

    foreach ($comando->fetchAll() as $linha_resposta) {
        $lista_respostas[] = [
            "id_resposta" => (string) $linha_resposta["id_resposta"],
            "texto_resposta" => $linha_resposta["texto_resposta"],
            "is_resposta_certa" => (int) $linha_resposta["is_resposta_certa"] === 1
        ];
    }

    return $lista_respostas;
}

// ---------- listar ----------
function listar_perguntas($pdo)
{
    $lista_perguntas = [];

    $comando = $pdo->query("SELECT id_pergunta, texto_pergunta, tipo_pergunta FROM perguntas ORDER BY id_pergunta");

    foreach ($comando->fetchAll() as $linha_pergunta) {
        $lista_perguntas[] = [
            "id_pergunta" => (string) $linha_pergunta["id_pergunta"],
            "texto_pergunta" => $linha_pergunta["texto_pergunta"],
            "tipo_pergunta" => $linha_pergunta["tipo_pergunta"],
            "respostas" => buscar_respostas($pdo, $linha_pergunta["id_pergunta"])
        ];
    }

    responder(["perguntas" => $lista_perguntas]);
}

// ---------- ver ----------
function ver_pergunta($pdo)
{
    // O id vem no endereço: api/perguntas.php?acao=ver&id_pergunta=1
    $id_pergunta_procurada = pegar_campo($_GET, "id_pergunta");

    $pergunta = buscar_pergunta($pdo, $id_pergunta_procurada);

    if ($pergunta === null) {
        responder_erro("Pergunta não encontrada.", 404);
    }

    $pergunta["respostas"] = buscar_respostas($pdo, $id_pergunta_procurada);

    responder(["pergunta" => $pergunta]);
}

// ---------- criar_multipla ----------
function criar_pergunta_multipla($pdo)
{
    exigir_post();
    $dados = ler_dados_json();

    $texto_pergunta = limpar_texto(pegar_campo($dados, "texto_pergunta"));
    $numero_resposta_certa = (int) pegar_campo($dados, "resposta_certa");

    // As respostas vêm como resposta_1 ... resposta_5 (igual ao formulário da AV1)
    $lista_respostas = [];
    for ($numero = 1; $numero <= 5; $numero++) {
        $lista_respostas[] = limpar_texto(pegar_campo($dados, "resposta_" . $numero));
    }

    $tem_resposta_vazia = in_array("", $lista_respostas);

    if ($texto_pergunta === "" || $tem_resposta_vazia || $numero_resposta_certa < 1 || $numero_resposta_certa > 5) {
        responder_erro("Preencha a pergunta, as 5 respostas e marque a resposta certa.");
    }

    // Transação: ou grava a pergunta E as 5 respostas, ou não grava nada
    $pdo->beginTransaction();

    try {
        // O id da pergunta é gerado pelo banco (AUTO_INCREMENT)
        $comando = $pdo->prepare("INSERT INTO perguntas (texto_pergunta, tipo_pergunta) VALUES (?, 'multipla_escolha')");
        $comando->execute([$texto_pergunta]);
        $id_pergunta = (int) $pdo->lastInsertId();

        // Grava as 5 respostas, marcando 1 na certa e 0 nas outras
        $comando = $pdo->prepare("INSERT INTO respostas (id_pergunta, texto_resposta, is_resposta_certa) VALUES (?, ?, ?)");

        foreach ($lista_respostas as $posicao => $texto_resposta) {
            if ($posicao + 1 === $numero_resposta_certa) {
                $is_resposta_certa = 1;
            } else {
                $is_resposta_certa = 0;
            }

            $comando->execute([$id_pergunta, $texto_resposta, $is_resposta_certa]);
        }

        $pdo->commit();
    } catch (PDOException $erro) {
        $pdo->rollBack();
        responder_erro("Não foi possível salvar a pergunta.", 500);
    }

    responder([
        "mensagem" => "Pergunta salva com sucesso! (id " . $id_pergunta . ")",
        "id_pergunta" => $id_pergunta
    ], 201);
}

// ---------- criar_texto ----------
function criar_pergunta_texto($pdo)
{
    exigir_post();
    $dados = ler_dados_json();

    $texto_pergunta = limpar_texto(pegar_campo($dados, "texto_pergunta"));
    $texto_resposta = limpar_texto(pegar_campo($dados, "texto_resposta"));

    // A pergunta é obrigatória, a resposta de orientação é opcional
    if ($texto_pergunta === "") {
        responder_erro("Preencha a pergunta.");
    }

    $pdo->beginTransaction();

    try {
        // Salva a pergunta com o tipo "texto"
        $comando = $pdo->prepare("INSERT INTO perguntas (texto_pergunta, tipo_pergunta) VALUES (?, 'texto')");
        $comando->execute([$texto_pergunta]);
        $id_pergunta = (int) $pdo->lastInsertId();

        // Se tiver resposta de orientação, salva ligada à pergunta (is_resposta_certa = 0, pois não tem correção)
        if ($texto_resposta !== "") {
            $comando = $pdo->prepare("INSERT INTO respostas (id_pergunta, texto_resposta, is_resposta_certa) VALUES (?, ?, 0)");
            $comando->execute([$id_pergunta, $texto_resposta]);
        }

        $pdo->commit();
    } catch (PDOException $erro) {
        $pdo->rollBack();
        responder_erro("Não foi possível salvar a pergunta.", 500);
    }

    responder([
        "mensagem" => "Pergunta de texto salva com sucesso! (id " . $id_pergunta . ")",
        "id_pergunta" => $id_pergunta
    ], 201);
}

// ---------- alterar_multipla ----------
function alterar_pergunta_multipla($pdo)
{
    exigir_post();
    $dados = ler_dados_json();

    $id_pergunta = pegar_campo($dados, "id_pergunta");
    $texto_pergunta = limpar_texto(pegar_campo($dados, "texto_pergunta"));
    $numero_resposta_certa = (int) pegar_campo($dados, "resposta_certa");

    $lista_respostas = [];
    for ($numero = 1; $numero <= 5; $numero++) {
        $lista_respostas[] = limpar_texto(pegar_campo($dados, "resposta_" . $numero));
    }

    // Validações simples
    $tem_resposta_vazia = in_array("", $lista_respostas);

    if ($id_pergunta === "" || $texto_pergunta === "" || $tem_resposta_vazia || $numero_resposta_certa < 1 || $numero_resposta_certa > 5) {
        responder_erro("Preencha a pergunta, as 5 respostas e marque a resposta certa.");
    }

    // Confere se a pergunta existe e se é mesmo de múltipla escolha
    $pergunta = buscar_pergunta($pdo, $id_pergunta);

    if ($pergunta === null) {
        responder_erro("Pergunta não encontrada.", 404);
    }

    if ($pergunta["tipo_pergunta"] === "texto") {
        responder_erro("Esta pergunta não é de múltipla escolha.");
    }

    // Ids das respostas dessa pergunta, na ordem (os ids ficam iguais, só muda o conteúdo)
    $comando = $pdo->prepare("SELECT id_resposta FROM respostas WHERE id_pergunta = ? ORDER BY id_resposta");
    $comando->execute([(int) $id_pergunta]);
    $ids_respostas = $comando->fetchAll(PDO::FETCH_COLUMN);

    $pdo->beginTransaction();

    try {
        // Pergunta: troca só o texto
        $comando = $pdo->prepare("UPDATE perguntas SET texto_pergunta = ? WHERE id_pergunta = ?");
        $comando->execute([$texto_pergunta, (int) $id_pergunta]);

        // Respostas: troca o texto e quem é a certa
        $comando_alterar = $pdo->prepare("UPDATE respostas SET texto_resposta = ?, is_resposta_certa = ? WHERE id_resposta = ?");
        $comando_criar = $pdo->prepare("INSERT INTO respostas (id_pergunta, texto_resposta, is_resposta_certa) VALUES (?, ?, ?)");

        foreach ($lista_respostas as $posicao => $texto_resposta) {
            if ($posicao + 1 === $numero_resposta_certa) {
                $is_resposta_certa = 1;
            } else {
                $is_resposta_certa = 0;
            }

            if (isset($ids_respostas[$posicao])) {
                $comando_alterar->execute([$texto_resposta, $is_resposta_certa, (int) $ids_respostas[$posicao]]);
            } else {
                // A pergunta tinha menos de 5 respostas: cria a que faltava
                $comando_criar->execute([(int) $id_pergunta, $texto_resposta, $is_resposta_certa]);
            }
        }

        $pdo->commit();
    } catch (PDOException $erro) {
        $pdo->rollBack();
        responder_erro("Não foi possível alterar a pergunta.", 500);
    }

    responder(["mensagem" => "Pergunta alterada com sucesso!"]);
}

// ---------- alterar_texto ----------
function alterar_pergunta_texto($pdo)
{
    exigir_post();
    $dados = ler_dados_json();

    $id_pergunta = pegar_campo($dados, "id_pergunta");
    $texto_pergunta = limpar_texto(pegar_campo($dados, "texto_pergunta"));
    $texto_resposta = limpar_texto(pegar_campo($dados, "texto_resposta"));

    // A pergunta é obrigatória, a resposta de orientação é opcional
    if ($id_pergunta === "" || $texto_pergunta === "") {
        responder_erro("Preencha a pergunta.");
    }

    $pergunta = buscar_pergunta($pdo, $id_pergunta);

    if ($pergunta === null) {
        responder_erro("Pergunta não encontrada.", 404);
    }

    // Só altera aqui se for pergunta de texto
    if ($pergunta["tipo_pergunta"] !== "texto") {
        responder_erro("Esta pergunta não é de texto.");
    }

    // Procura a resposta de orientação que já existe (se existir)
    $comando = $pdo->prepare("SELECT id_resposta FROM respostas WHERE id_pergunta = ? ORDER BY id_resposta LIMIT 1");
    $comando->execute([(int) $id_pergunta]);
    $id_resposta = $comando->fetchColumn();

    $pdo->beginTransaction();

    try {
        // Pergunta: troca o texto (o tipo continua "texto")
        $comando = $pdo->prepare("UPDATE perguntas SET texto_pergunta = ? WHERE id_pergunta = ?");
        $comando->execute([$texto_pergunta, (int) $id_pergunta]);

        // Resposta de orientação (pergunta de texto não tem correção, então is_resposta_certa = 0)
        if ($id_resposta !== false && $texto_resposta !== "") {
            // Já tinha e continua tendo: troca o texto (o id fica igual)
            $comando = $pdo->prepare("UPDATE respostas SET texto_resposta = ? WHERE id_resposta = ?");
            $comando->execute([$texto_resposta, (int) $id_resposta]);
        } elseif ($id_resposta !== false) {
            // Já tinha e o texto novo está vazio: apaga a resposta
            $comando = $pdo->prepare("DELETE FROM respostas WHERE id_resposta = ?");
            $comando->execute([(int) $id_resposta]);
        } elseif ($texto_resposta !== "") {
            // Não tinha e agora tem: cria uma nova
            $comando = $pdo->prepare("INSERT INTO respostas (id_pergunta, texto_resposta, is_resposta_certa) VALUES (?, ?, 0)");
            $comando->execute([(int) $id_pergunta, $texto_resposta]);
        }

        $pdo->commit();
    } catch (PDOException $erro) {
        $pdo->rollBack();
        responder_erro("Não foi possível alterar a pergunta.", 500);
    }

    responder(["mensagem" => "Pergunta de texto alterada com sucesso!"]);
}

// ---------- excluir ----------
function excluir_pergunta($pdo)
{
    exigir_post();
    $dados = ler_dados_json();

    $id_pergunta_excluir = pegar_campo($dados, "id_pergunta");

    if ($id_pergunta_excluir === "") {
        responder_erro("Pergunta não informada.");
    }

    if (buscar_pergunta($pdo, $id_pergunta_excluir) === null) {
        responder_erro("Pergunta não encontrada.", 404);
    }

    // As respostas dela são excluídas junto: a tabela respostas tem ON DELETE CASCADE
    $comando = $pdo->prepare("DELETE FROM perguntas WHERE id_pergunta = ?");
    $comando->execute([(int) $id_pergunta_excluir]);

    responder(["mensagem" => "Pergunta excluída com sucesso!"]);
}

// ---------- escolhe a ação ----------
$acao = pegar_campo($_GET, "acao");

switch ($acao) {
    case "listar":
        listar_perguntas($pdo);
        break;
    case "ver":
        ver_pergunta($pdo);
        break;
    case "criar_multipla":
        criar_pergunta_multipla($pdo);
        break;
    case "criar_texto":
        criar_pergunta_texto($pdo);
        break;
    case "alterar_multipla":
        alterar_pergunta_multipla($pdo);
        break;
    case "alterar_texto":
        alterar_pergunta_texto($pdo);
        break;
    case "excluir":
        excluir_pergunta($pdo);
        break;
    default:
        responder_erro("Ação inválida.");
}
