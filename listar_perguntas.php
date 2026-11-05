<?php
$arquivo_perguntas = "perguntas/perguntas.txt";

// Lê o arquivo e devolve uma lista com as linhas (ou lista vazia se não existir)
function ler_linhas($arquivo)
{
    if (!file_exists($arquivo)) {
        return [];
    }
    return file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

$linhas_perguntas = ler_linhas($arquivo_perguntas);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Listar perguntas</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Perguntas cadastradas</h1>

    <?php if (empty($linhas_perguntas)) { ?>
        <p>Nenhuma pergunta cadastrada ainda.</p>
    <?php } ?>

    <ul>
        <?php foreach ($linhas_perguntas as $linha_pergunta) { ?>
            <?php
            // Separa a linha da pergunta: id_pergunta;texto_pergunta;tipo_pergunta
            $campos_pergunta = explode(";", $linha_pergunta);
            $id_pergunta = $campos_pergunta[0];
            $texto_pergunta = $campos_pergunta[1];

            // Perguntas antigas, sem o tipo, contam como múltipla escolha
            $tipo_pergunta = $campos_pergunta[2] ?? "multipla_escolha";
            ?>

            <li>
                <?php echo $id_pergunta; ?>. <?php echo htmlspecialchars($texto_pergunta); ?>
                <em>(<?php echo $tipo_pergunta === "texto" ? "texto" : "múltipla escolha"; ?>)</em>

                <a href="ver_pergunta.php?id_pergunta=<?php echo $id_pergunta; ?>">Ver</a>

                <?php // Cada tipo de pergunta tem a sua tela de alterar ?>
                <?php if ($tipo_pergunta === "texto") { ?>
                    <a href="alterar_pergunta_texto.php?id_pergunta=<?php echo $id_pergunta; ?>">Alterar</a>
                <?php } else { ?>
                    <a href="alterar_pergunta.php?id_pergunta=<?php echo $id_pergunta; ?>">Alterar</a>
                <?php } ?>

                <form action="excluir_pergunta.php" method="post" style="display:inline" onsubmit="return confirm('Tem certeza que deseja excluir esta pergunta e suas respostas?');">
                    <input type="hidden" name="id_pergunta" value="<?php echo $id_pergunta; ?>">
                    <button type="submit">Excluir</button>
                </form>
            </li>
        <?php } ?>
    </ul>

    <p><a href="criar_pergunta.php">Criar pergunta de múltipla escolha</a> | <a href="criar_pergunta_texto.php">Criar pergunta de texto</a> | <a href="index.php">Menu</a></p>
</body>
</html>