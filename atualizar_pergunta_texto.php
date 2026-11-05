<?php
$arquivo_perguntas = "perguntas/perguntas.txt";
$arquivo_respostas = "respostas/respostas.txt";

// Só aceita dados vindos do formulário
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: listar_perguntas.php");
    exit;
}

// Tira espaços, quebras de linha e o ";" (que é o separador do arquivo)
function limpar_texto($texto)
{
    $texto = trim($texto);
    $texto = str_replace(["\r", "\n"], " ", $texto);
    $texto = str_replace(";", ",", $texto);
    return $texto;
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

// Pega os dados do formulário
$id_pergunta = $_POST["id_pergunta"] ?? "";
$texto_pergunta = limpar_texto($_POST["texto_pergunta"] ?? "");
$texto_resposta = limpar_texto($_POST["texto_resposta"] ?? "");

// A pergunta é obrigatória, a resposta de orientação é opcional
if ($id_pergunta === "" || $texto_pergunta === "") {
    echo "Preencha a pergunta.";
    echo '<p><a href="alterar_pergunta_texto.php?id_pergunta=' . htmlspecialchars($id_pergunta) . '">Voltar</a></p>';
    exit;
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
            echo "Esta pergunta não é de texto.";
            echo '<p><a href="listar_perguntas.php">Voltar para a lista</a></p>';
            exit;
        }

        $linha_pergunta = $id_pergunta . ";" . $texto_pergunta . ";texto";
        $pergunta_encontrada = true;
    }

    $perguntas_atualizadas[] = $linha_pergunta;
}

if (!$pergunta_encontrada) {
    echo "Pergunta não encontrada.";
    echo '<p><a href="listar_perguntas.php">Voltar para a lista</a></p>';
    exit;
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
    $novo_id_resposta = 1;

    if (!empty($linhas_respostas)) {
        $campos_ultima_linha = explode(";", end($linhas_respostas));
        $novo_id_resposta = (int) $campos_ultima_linha[0] + 1;
    }

    $respostas_atualizadas[] = $novo_id_resposta . ";" . $id_pergunta . ";" . $texto_resposta . ";" . $is_resposta_certa;
}

salvar_linhas($arquivo_perguntas, $perguntas_atualizadas);
salvar_linhas($arquivo_respostas, $respostas_atualizadas);

// Volta para a lista
header("Location: listar_perguntas.php");
exit;