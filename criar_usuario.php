<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Criar usuário</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Criar usuário</h1>

    <form action="salvar_usuario.php" method="post">
        <p>
            <label>Nome:</label><br>
            <input type="text" name="nome_usuario" size="50" required>
        </p>

        <p>
            <label>Data de nascimento:</label><br>
            <input type="date" name="data_nascimento" max="<?php echo date('Y-m-d'); ?>" required>
        </p>

        <p>
            <label>CPF:</label><br>
            <input type="text" name="cpf_usuario" size="20" maxlength="14" placeholder="000.000.000-00" required>
        </p>

        <p>
            <label>E-mail:</label><br>
            <input type="email" name="email_usuario" size="50" required>
        </p>

        <p>
            <label>Senha (mínimo 6 caracteres):</label><br>
            <input type="password" name="senha_usuario" minlength="6" required>
        </p>

        <button type="submit">Salvar usuário</button>
    </form>

    <p><a href="listar_usuarios.php">Voltar para a lista</a> | <a href="index.php">Menu</a></p>
</body>
</html>