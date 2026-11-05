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

// Pega o id que veio no endereço: alterar_pergunta_texto.php?id_pergunta=1
$id_pergunta_procurada = $_GET["id_pergunta"] ?? "";

$pergunta_encontrada = false;
$texto_pergunta = "";
$tipo_pergunta = "multipla_escolha";

// Procura a pergunta pelo id
foreach (ler_linhas($arquivo_perguntas) as $linha_pergunta) {
    $campos_pergunta = explode(";", $linha_pergunta);

    if ($campos_pergunta[0] === $id_pergunta_procurada) {
        $texto_pergunta = $campos_pergunta[1];
        $tipo_pergunta = $campos_pergunta[2] ?? "multipla_escolha";
        $pergunta_encontrada = true;
        break;
    }
}

// Procura a resposta de orientação (se existir)
$texto_resposta = "";

if ($pergunta_encontrada) {
    foreach (ler_linhas($arquivo_respostas) as $linha_resposta) {
        $campos_resposta = explode(";", $linha_resposta);

        if ($campos_resposta[1] === $id_pergunta_procurada) {
            $texto_resposta = $campos_resposta[2];
            break;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Alterar pergunta de texto</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Alterar pergunta de texto</h1>

    <?php if (!$pergunta_encontrada) { ?>
        <p>Pergunta não encontrada.</p>
    <?php } elseif ($tipo_pergunta !== "texto") { ?>
        <p>Esta pergunta não é de texto. <a href="alterar_pergunta.php?id_pergunta=<?php echo $id_pergunta_procurada; ?>">Alterar como múltipla escolha</a></p>
    <?php } else { ?>

        <form action="atualizar_pergunta_texto.php" method="post">
            <input type="hidden" name="id_pergunta" value="<?php echo $id_pergunta_procurada; ?>">

            <p>
                <label>Pergunta:</label><br>
                <textarea name="texto_pergunta" rows="3" cols="60" required><?php echo htmlspecialchars($texto_pergunta); ?></textarea>
            </p>

            <p>
                <label>Resposta de orientação (opcional, não tem correção):</label><br>
                <textarea name="texto_resposta" rows="3" cols="60"><?php echo htmlspecialchars($texto_resposta); ?></textarea>
            </p>

            <button type="submit">Salvar alterações</button>
        </form>

    <?php } ?>

    <p><a href="listar_perguntas.php">Voltar para a lista</a> | <a href="index.php">Menu</a></p>
</body>
</html>