<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar pergunta de texto</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Criar pergunta de resposta em texto</h1>

    <form action="salvar_pergunta_texto.php" method="post">
        <p>
            <label>Pergunta:</label><br>
            <textarea name="texto_pergunta" rows="3" cols="60" required></textarea>
        </p>

        <p>
            <label>Resposta de orientação (opcional, não tem correção):</label><br>
            <textarea name="texto_resposta" rows="3" cols="60"></textarea>
        </p>

        <button type="submit">Salvar pergunta</button>
    </form>

    <p><a href="index.php">Voltar</a></p>
</body>
</html>