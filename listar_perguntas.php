<?php
$arquivo_perguntas = "perguntas/perguntas.txt";
$arquivo_respostas = "respostas/respostas.txt";

function ler_linhas($arquivo)
{
    if (!file_exists($arquivo)) {
        return [];
    }
    return file($arquivo, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
}

$linhas_perguntas = ler_linhas($arquivo_perguntas);
$linhas_respostas = ler_linhas($arquivo_respostas);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Listar perguntas</title>
</head>
<body>
    <h1>Perguntas cadastradas</h1>

    <?php if (empty($linhas_perguntas)) { ?>
        <p>Nenhuma pergunta cadastrada ainda.</p>
    <?php } ?>

    <?php foreach ($linhas_perguntas as $linha_pergunta) { ?>
        <?php
        $campos_pergunta = explode(";", $linha_pergunta);
        $id_pergunta = $campos_pergunta[0];
        $texto_pergunta = $campos_pergunta[1];
        ?>

        <h3><?php echo $id_pergunta; ?>. <?php echo htmlspecialchars($texto_pergunta); ?></h3>

        <ul>
            <?php foreach ($linhas_respostas as $linha_resposta) { ?>
                <?php
                $campos_resposta = explode(";", $linha_resposta);
                $id_resposta = $campos_resposta[0];
                $id_pergunta_da_resposta = $campos_resposta[1];
                $texto_resposta = $campos_resposta[2];
                $is_resposta_certa = $campos_resposta[3];
                ?>

                <?php ?>
                <?php if ($id_pergunta_da_resposta === $id_pergunta) { ?>
                    <li>
                        <?php echo htmlspecialchars($texto_resposta); ?>
                        <?php if ($is_resposta_certa === "1") { ?>
                            <strong>(certa)</strong>
                        <?php } ?>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>
    <?php } ?>

    <p><a href="criar_pergunta.php">Criar pergunta</a> | <a href="index.php">Menu</a></p>
</body>
</html>