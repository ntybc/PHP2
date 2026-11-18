<?php
// API das perguntas: o JavaScript chama este arquivo e recebe JSON de volta.
// A ação vem no endereço, por exemplo: api/perguntas.php?acao=listar
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
function buscar_pergunta($arquivo_perguntas, $id_pergunta_procurada)
{
    foreach (ler_linhas($arquivo_perguntas) as $linha_pergunta) {
        // Linha da pergunta: id_pergunta;texto_pergunta;tipo_pergunta
        $campos_pergunta = explode(";", $linha_pergunta);

        if ($campos_pergunta[0] === $id_pergunta_procurada) {
            return [
                "id_pergunta" => $campos_pergunta[0],
                "texto_pergunta" => $campos_pergunta[1],
                // Perguntas antigas, sem o tipo, contam como múltipla escolha
                "tipo_pergunta" => $campos_pergunta[2] ?? "multipla_escolha"
            ];
        }
    }

    return null;
}

// Junta as respostas que têm o mesmo id_pergunta
function buscar_respostas($arquivo_respostas, $id_pergunta)
{
    $lista_respostas = [];

    foreach (ler_linhas($arquivo_respostas) as $linha_resposta) {
        // Linha da resposta: id_resposta;id_pergunta;texto_resposta;is_resposta_certa
        $campos_resposta = explode(";", $linha_resposta);

        if ($campos_resposta[1] === $id_pergunta) {
            $lista_respostas[] = [
                "id_resposta" => $campos_resposta[0],
                "texto_resposta" => $campos_resposta[2],
                "is_resposta_certa" => $campos_resposta[3] === "1"
            ];
        }
    }

    return $lista_respostas;
}

// ---------- listar ----------
function listar_perguntas($arquivo_perguntas, $arquivo_respostas)
{
    $lista_perguntas = [];

    foreach (ler_linhas($arquivo_perguntas) as $linha_pergunta) {
        $campos_pergunta = explode(";", $linha_pergunta);

        $lista_perguntas[] = [
            "id_pergunta" => $campos_pergunta[0],
            "texto_pergunta" => $campos_pergunta[1],
            "tipo_pergunta" => $campos_pergunta[2] ?? "multipla_escolha",
            "respostas" => buscar_respostas($arquivo_respostas, $campos_pergunta[0])
        ];
    }

    responder(["perguntas" => $lista_perguntas]);
}

// ---------- ver ----------
function ver_pergunta($arquivo_perguntas, $arquivo_respostas)
{
    // O id vem no endereço: api/perguntas.php?acao=ver&id_pergunta=1
    $id_pergunta_procurada = pegar_campo($_GET, "id_pergunta");

    $pergunta = buscar_pergunta($arquivo_perguntas, $id_pergunta_procurada);

    if ($pergunta === null) {
        responder_erro("Pergunta não encontrada.", 404);
    }

    $pergunta["respostas"] = buscar_respostas($arquivo_respostas, $id_pergunta_procurada);

    responder(["pergunta" => $pergunta]);
}

// ---------- criar_multipla ----------
function criar_pergunta_multipla($arquivo_perguntas, $arquivo_respostas)
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

    // Grava a pergunta (igual à AV1: múltipla escolha fica sem o tipo, que já conta como múltipla escolha)
    $id_pergunta = pegar_proximo_id($arquivo_perguntas);
    $linha_pergunta = $id_pergunta . ";" . $texto_pergunta . "\n";
    file_put_contents($arquivo_perguntas, $linha_pergunta, FILE_APPEND | LOCK_EX);

    // Grava as 5 respostas, marcando 1 na certa e 0 nas outras
    $id_resposta = pegar_proximo_id($arquivo_respostas);

    foreach ($lista_respostas as $posicao => $texto_resposta) {
        if ($posicao + 1 === $numero_resposta_certa) {
            $is_resposta_certa = 1;
        } else {
            $is_resposta_certa = 0;
        }

        $linha_resposta = $id_resposta . ";" . $id_pergunta . ";" . $texto_resposta . ";" . $is_resposta_certa . "\n";
        file_put_contents($arquivo_respostas, $linha_resposta, FILE_APPEND | LOCK_EX);

        $id_resposta++;
    }

    responder([
        "mensagem" => "Pergunta salva com sucesso! (id " . $id_pergunta . ")",
        "id_pergunta" => $id_pergunta
    ], 201);
}

// ---------- criar_texto ----------
function criar_pergunta_texto($arquivo_perguntas, $arquivo_respostas)
{
    exigir_post();
    $dados = ler_dados_json();

    $texto_pergunta = limpar_texto(pegar_campo($dados, "texto_pergunta"));
    $texto_resposta = limpar_texto(pegar_campo($dados, "texto_resposta"));

    // A pergunta é obrigatória, a resposta de orientação é opcional
    if ($texto_pergunta === "") {
        responder_erro("Preencha a pergunta.");
    }

    // Salva a pergunta com o tipo "texto"
    $id_pergunta = pegar_proximo_id($arquivo_perguntas);
    $linha_pergunta = $id_pergunta . ";" . $texto_pergunta . ";texto\n";
    file_put_contents($arquivo_perguntas, $linha_pergunta, FILE_APPEND | LOCK_EX);

    // Se tiver resposta de orientação, salva ligada à pergunta (is_resposta_certa = 0, pois não tem correção)
    if ($texto_resposta !== "") {
        $id_resposta = pegar_proximo_id($arquivo_respostas);
        $is_resposta_certa = 0;

        $linha_resposta = $id_resposta . ";" . $id_pergunta . ";" . $texto_resposta . ";" . $is_resposta_certa . "\n";
        file_put_contents($arquivo_respostas, $linha_resposta, FILE_APPEND | LOCK_EX);
    }

    responder([
        "mensagem" => "Pergunta de texto salva com sucesso! (id " . $id_pergunta . ")",
        "id_pergunta" => $id_pergunta
    ], 201);
}

// ---------- alterar_multipla ----------
function alterar_pergunta_multipla($arquivo_perguntas, $arquivo_respostas)
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
    $pergunta = buscar_pergunta($arquivo_perguntas, $id_pergunta);

    if ($pergunta === null) {
        responder_erro("Pergunta não encontrada.", 404);
    }

    if ($pergunta["tipo_pergunta"] === "texto") {
        responder_erro("Esta pergunta não é de múltipla escolha.");
    }

    // Pergunta: troca só o texto da linha que tem esse id
    $perguntas_atualizadas = [];

    foreach (ler_linhas($arquivo_perguntas) as $linha_pergunta) {
        $campos_pergunta = explode(";", $linha_pergunta);

        if ($campos_pergunta[0] === $id_pergunta) {
            $linha_pergunta = $id_pergunta . ";" . $texto_pergunta . ";multipla_escolha";
        }

        $perguntas_atualizadas[] = $linha_pergunta;
    }

    // Respostas: nas linhas dessa pergunta, troca o texto e quem é a certa (os ids ficam iguais)
    $respostas_atualizadas = [];
    $posicao = 0;

    foreach (ler_linhas($arquivo_respostas) as $linha_resposta) {
        $campos_resposta = explode(";", $linha_resposta);

        if ($campos_resposta[1] === $id_pergunta && $posicao < 5) {
            $id_resposta = $campos_resposta[0];

            if ($posicao + 1 === $numero_resposta_certa) {
                $is_resposta_certa = 1;
            } else {
                $is_resposta_certa = 0;
            }

            $linha_resposta = $id_resposta . ";" . $id_pergunta . ";" . $lista_respostas[$posicao] . ";" . $is_resposta_certa;
            $posicao++;
        }

        $respostas_atualizadas[] = $linha_resposta;
    }

    salvar_linhas($arquivo_perguntas, $perguntas_atualizadas);
    salvar_linhas($arquivo_respostas, $respostas_atualizadas);

    responder(["mensagem" => "Pergunta alterada com sucesso!"]);
}

// ---------- alterar_texto ----------
function alterar_pergunta_texto($arquivo_perguntas, $arquivo_respostas)
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

    // Pergunta: troca o texto da linha que tem esse id e mantém o tipo "texto"
    $pergunta_encontrada = false;
    $perguntas_atualizadas = [];

    foreach (ler_linhas($arquivo_perguntas) as $linha_pergunta) {
        $campos_pergunta = explode(";", $linha_pergunta);

        if ($campos_pergunta[0] === $id_pergunta) {
            $tipo_pergunta = $campos_pergunta[2] ?? "multipla_escolha";

            // Só altera aqui se for pergunta de texto
            if ($tipo_pergunta !== "texto") {
                responder_erro("Esta pergunta não é de texto.");
            }

            $linha_pergunta = $id_pergunta . ";" . $texto_pergunta . ";texto";
            $pergunta_encontrada = true;
        }

        $perguntas_atualizadas[] = $linha_pergunta;
    }

    if (!$pergunta_encontrada) {
        responder_erro("Pergunta não encontrada.", 404);
    }

    // Resposta de orientação
    $linhas_respostas = ler_linhas($arquivo_respostas);
    $respostas_atualizadas = [];
    $tinha_resposta = false;
    $is_resposta_certa = 0; // pergunta de texto não tem correção

    foreach ($linhas_respostas as $linha_resposta) {
        $campos_resposta = explode(";", $linha_resposta);

        if ($campos_resposta[1] === $id_pergunta && !$tinha_resposta) {
            $tinha_resposta = true;

            // Já tinha resposta: se o texto não está vazio, troca (o id fica igual). Se está vazio, não guarda a linha (apaga).
            if ($texto_resposta !== "") {
                $id_resposta = $campos_resposta[0];
                $respostas_atualizadas[] = $id_resposta . ";" . $id_pergunta . ";" . $texto_resposta . ";" . $is_resposta_certa;
            }
        } else {
            $respostas_atualizadas[] = $linha_resposta;
        }
    }

    // Não tinha resposta e agora tem: cria uma nova com o próximo id
    if (!$tinha_resposta && $texto_resposta !== "") {
        $novo_id_resposta = pegar_proximo_id($arquivo_respostas);
        $respostas_atualizadas[] = $novo_id_resposta . ";" . $id_pergunta . ";" . $texto_resposta . ";" . $is_resposta_certa;
    }

    salvar_linhas($arquivo_perguntas, $perguntas_atualizadas);
    salvar_linhas($arquivo_respostas, $respostas_atualizadas);

    responder(["mensagem" => "Pergunta de texto alterada com sucesso!"]);
}

// ---------- excluir ----------
function excluir_pergunta($arquivo_perguntas, $arquivo_respostas)
{
    exigir_post();
    $dados = ler_dados_json();

    $id_pergunta_excluir = pegar_campo($dados, "id_pergunta");

    if ($id_pergunta_excluir === "") {
        responder_erro("Pergunta não informada.");
    }

    if (buscar_pergunta($arquivo_perguntas, $id_pergunta_excluir) === null) {
        responder_erro("Pergunta não encontrada.", 404);
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

    responder(["mensagem" => "Pergunta excluída com sucesso!"]);
}

// ---------- escolhe a ação ----------
$acao = pegar_campo($_GET, "acao");

switch ($acao) {
    case "listar":
        listar_perguntas($arquivo_perguntas, $arquivo_respostas);
        break;
    case "ver":
        ver_pergunta($arquivo_perguntas, $arquivo_respostas);
        break;
    case "criar_multipla":
        criar_pergunta_multipla($arquivo_perguntas, $arquivo_respostas);
        break;
    case "criar_texto":
        criar_pergunta_texto($arquivo_perguntas, $arquivo_respostas);
        break;
    case "alterar_multipla":
        alterar_pergunta_multipla($arquivo_perguntas, $arquivo_respostas);
        break;
    case "alterar_texto":
        alterar_pergunta_texto($arquivo_perguntas, $arquivo_respostas);
        break;
    case "excluir":
        excluir_pergunta($arquivo_perguntas, $arquivo_respostas);
        break;
    default:
        responder_erro("Ação inválida.");
}
