<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar perguntas</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Perguntas de múltipla escolha</h1>

    <form action="salvar_pergunta.php" method="post">
        <p>
            <label>Pergunta:</label><br>
            <textarea name="texto_pergunta" rows="3" cols="60" required></textarea>
        </p>

        <p>Respostas (marque a correta):</p>

        <?php for ($numero = 1; $numero <= 5; $numero++) { ?>
            <p>
                <input type="radio" name="resposta_certa" value="<?php echo $numero; ?>" required>
                <input type="text" name="resposta_<?php echo $numero; ?>" size="60" placeholder="Resposta <?php echo $numero; ?>" required>
            </p>
        <?php } ?>

        <button type="submit">Salvar pergunta</button>
    </form>

    <p><a href="index.php">Voltar</a></p>
</body>
</html>