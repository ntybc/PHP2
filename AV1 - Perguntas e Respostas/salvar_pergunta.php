<?php
$arquivo_perguntas = "perguntas/perguntas.txt";
$arquivo_respostas = "respostas/respostas.txt";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: criar_pergunta.php");
    exit;
}

function limpar_texto($texto)
{
    $texto = trim($texto);
    $texto = str_replace(["\r", "\n"], " ", $texto);
    $texto = str_replace(";", ",", $texto);
    return $texto;
}

function pegar_proximo_id($arquivo)
{
    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (empty($linhas)) {
        return 1;
    }

    $ultima_linha = end($linhas);
    $campos = explode(";", $ultima_linha);
    return (int) $campos[0] + 1;
}

$texto_pergunta = limpar_texto($_POST["texto_pergunta"] ?? "");
$numero_resposta_certa = (int) ($_POST["resposta_certa"] ?? 0);

$lista_respostas = [];
for ($numero = 1; $numero <= 5; $numero++) {
    $lista_respostas[] = limpar_texto($_POST["resposta_" . $numero] ?? "");
}

$tem_resposta_vazia = in_array("", $lista_respostas);

if ($texto_pergunta === "" || $tem_resposta_vazia || $numero_resposta_certa < 1 || $numero_resposta_certa > 5) {
    echo "Preencha a pergunta, as 5 respostas e marque a resposta certa.";
    echo '<p><a href="criar_pergunta.php">Voltar</a></p>';
    exit;
}

$id_pergunta = pegar_proximo_id($arquivo_perguntas);
$linha_pergunta = $id_pergunta . ";" . $texto_pergunta . "\n";
file_put_contents($arquivo_perguntas, $linha_pergunta, FILE_APPEND | LOCK_EX);

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

echo "Pergunta salva com sucesso! (id " . $id_pergunta . ")";
echo '<p><a href="criar_pergunta.php">Criar outra pergunta</a> | <a href="listar_perguntas.php">Ver perguntas</a> | <a href="index.php">Menu</a></p>';