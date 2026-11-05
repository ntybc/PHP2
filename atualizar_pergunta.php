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
$numero_resposta_certa = (int) ($_POST["resposta_certa"] ?? 0);

$lista_respostas = [];
for ($numero = 1; $numero <= 5; $numero++) {
    $lista_respostas[] = limpar_texto($_POST["resposta_" . $numero] ?? "");
}

// Validações simples
$tem_resposta_vazia = in_array("", $lista_respostas);

if ($id_pergunta === "" || $texto_pergunta === "" || $tem_resposta_vazia || $numero_resposta_certa < 1 || $numero_resposta_certa > 5) {
    echo "Preencha a pergunta, as 5 respostas e marque a resposta certa.";
    echo '<p><a href="alterar_pergunta.php?id_pergunta=' . htmlspecialchars($id_pergunta) . '">Voltar</a></p>';
    exit;
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

// Volta para a lista
header("Location: listar_perguntas.php");
exit;