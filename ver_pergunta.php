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

// Pega o id que veio no endereço: ver_pergunta.php?id_pergunta=1
$id_pergunta_procurada = $_GET["id_pergunta"] ?? "";

$pergunta_encontrada = false;
$texto_pergunta = "";
$tipo_pergunta = "multipla_escolha";

// Procura a pergunta pelo id
foreach (ler_linhas($arquivo_perguntas) as $linha_pergunta) {
    $campos_pergunta = explode(";", $linha_pergunta);

    if ($campos_pergunta[0] === $id_pergunta_procurada) {
        $texto_pergunta = $campos_pergunta[1];

        // Perguntas antigas, sem o tipo, contam como múltipla escolha
        $tipo_pergunta = $campos_pergunta[2] ?? "multipla_escolha";

        $pergunta_encontrada = true;
        break;
    }
}

// Se achou a pergunta, junta as respostas que têm o mesmo id_pergunta
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
    <title>Ver pergunta</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Pergunta</h1>

    <?php if (!$pergunta_encontrada) { ?>
        <p>Pergunta não encontrada.</p>
    <?php } else { ?>
        <h3><?php echo $id_pergunta_procurada; ?>. <?php echo htmlspecialchars($texto_pergunta); ?></h3>

        <?php if ($tipo_pergunta === "texto") { ?>

            <p><em>Pergunta de resposta em texto (sem correção).</em></p>

            <?php if (empty($lista_respostas)) { ?>
                <p>Sem resposta de orientação.</p>
            <?php } else { ?>
                <p><strong>Resposta de orientação:</strong> <?php echo htmlspecialchars($lista_respostas[0][2]); ?></p>
            <?php } ?>

        <?php } else { ?>

            <ul>
                <?php foreach ($lista_respostas as $campos_resposta) { ?>
                    <li>
                        <?php echo htmlspecialchars($campos_resposta[2]); ?>
                        <?php if ($campos_resposta[3] === "1") { ?>
                            <strong>(certa)</strong>
                        <?php } ?>
                    </li>
                <?php } ?>
            </ul>

        <?php } ?>
    <?php } ?>

    <p><a href="listar_perguntas.php">Voltar para a lista</a> | <a href="index.php">Menu</a></p>
</body>
</html>