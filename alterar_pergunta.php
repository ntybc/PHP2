<?php
$arquivo_perguntas = "perguntas/perguntas.txt";
$arquivo_respostas = "respostas/respostas.txt";

// Lê o arquivo e devolve uma lista com as linhas (ou lista vazia se não existir)
function ler_linhas($arquivo)
{
    if (!file_exists($arquivo)) {
        return [];
    }
    return file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

// Pega o id que veio no endereço: alterar_pergunta.php?id_pergunta=1
$id_pergunta_procurada = $_GET["id_pergunta"] ?? "";

$pergunta_encontrada = false;
$texto_pergunta = "";

// Procura a pergunta pelo id
foreach (ler_linhas($arquivo_perguntas) as $linha_pergunta) {
    $campos_pergunta = explode(";", $linha_pergunta);

    if ($campos_pergunta[0] === $id_pergunta_procurada) {
        $texto_pergunta = $campos_pergunta[1];
        $pergunta_encontrada = true;
        break;
    }
}

// Junta as respostas que pertencem a essa pergunta
$lista_respostas = [];

if ($pergunta_encontrada) {
    foreach (ler_linhas($arquivo_respostas) as $linha_resposta) {
        $campos_resposta = explode(";", $linha_resposta);

        if ($campos_resposta[1] === $id_pergunta_procurada) {
            $lista_respostas[] = $campos_resposta;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Alterar pergunta</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Alterar pergunta</h1>

    <?php if (!$pergunta_encontrada) { ?>
        <p>Pergunta não encontrada.</p>
    <?php } else { ?>

        <form action="atualizar_pergunta.php" method="post">
            <input type="hidden" name="id_pergunta" value="<?php echo $id_pergunta_procurada; ?>">

            <p>
                <label>Pergunta:</label><br>
                <textarea name="texto_pergunta" rows="3" cols="60" required><?php echo htmlspecialchars($texto_pergunta); ?></textarea>
            </p>

            <p>Respostas (marque a correta):</p>

            <?php for ($numero = 1; $numero <= 5; $numero++) { ?>
                <?php
                // A resposta número 1 está na posição 0 da lista, e assim por diante
                $texto_resposta = "";
                $is_resposta_certa = "0";

                if (isset($lista_respostas[$numero - 1])) {
                    $texto_resposta = $lista_respostas[$numero - 1][2];
                    $is_resposta_certa = $lista_respostas[$numero - 1][3];
                }
                ?>
                <p>
                    <input type="radio" name="resposta_certa" value="<?php echo $numero; ?>" <?php if ($is_resposta_certa === "1") { echo "checked"; } ?> required>
                    <input type="text" name="resposta_<?php echo $numero; ?>" size="60" value="<?php echo htmlspecialchars($texto_resposta); ?>" required>
                </p>
            <?php } ?>

            <button type="submit">Salvar alterações</button>
        </form>

    <?php } ?>

    <p><a href="listar_perguntas.php">Voltar para a lista</a> | <a href="index.php">Menu</a></p>
</body>
</html>