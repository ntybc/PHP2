<?php
$arquivo_perguntas = "perguntas/perguntas.txt";
$arquivo_respostas = "respostas/respostas.txt";

// Só aceita dados vindos do formulário
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: criar_pergunta_texto.php");
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

// Descobre o próximo id: pega o id da última linha do arquivo e soma 1
function pegar_proximo_id($arquivo)
{
    if (!file_exists($arquivo)) {
        return 1;
    }

    $linhas = file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (empty($linhas)) {
        return 1;
    }

    $ultima_linha = end($linhas);
    $campos = explode(";", $ultima_linha);
    return (int) $campos[0] + 1;
}

// Pega os dados do formulário
$texto_pergunta = limpar_texto($_POST["texto_pergunta"] ?? "");
$texto_resposta = limpar_texto($_POST["texto_resposta"] ?? "");

// A pergunta é obrigatória, a resposta de orientação é opcional
if ($texto_pergunta === "") {
    echo "Preencha a pergunta.";
    echo '<p><a href="criar_pergunta_texto.php">Voltar</a></p>';
    exit;
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

echo "Pergunta de texto salva com sucesso! (id " . $id_pergunta . ")";
echo '<p><a href="criar_pergunta_texto.php">Criar outra pergunta de texto</a> | <a href="listar_perguntas.php">Ver perguntas</a> | <a href="index.php">Menu</a></p>';