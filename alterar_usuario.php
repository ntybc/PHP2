<?php
require "funcoes_usuarios.php";

// Pega o id que veio no endereço: alterar_usuario.php?id_usuario=1
$id_usuario_procurado = $_GET["id_usuario"] ?? "";

$usuario_encontrado = false;
$nome_usuario = "";
$data_nascimento = "";
$cpf_usuario = "";
$email_usuario = "";

// Procura o usuário pelo id
foreach (ler_linhas($arquivo_usuarios) as $linha_usuario) {
    $campos_usuario = explode(";", $linha_usuario);

    if ($campos_usuario[0] === $id_usuario_procurado) {
        $nome_usuario = $campos_usuario[1];
        $data_nascimento = $campos_usuario[2];
        $cpf_usuario = $campos_usuario[3];
        $email_usuario = $campos_usuario[4];
        $usuario_encontrado = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Alterar usuário</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Alterar usuário</h1>

    <?php if (!$usuario_encontrado) { ?>
        <p>Usuário não encontrado.</p>
    <?php } else { ?>

        <form action="atualizar_usuario.php" method="post">
            <input type="hidden" name="id_usuario" value="<?php echo htmlspecialchars($id_usuario_procurado); ?>">

            <p>
                <label>Nome:</label><br>
                <input type="text" name="nome_usuario" size="50" value="<?php echo htmlspecialchars($nome_usuario); ?>" required>
            </p>

            <p>
                <label>Data de nascimento:</label><br>
                <input type="date" name="data_nascimento" max="<?php echo date('Y-m-d'); ?>" value="<?php echo htmlspecialchars($data_nascimento); ?>" required>
            </p>

            <p>
                <label>CPF:</label><br>
                <input type="text" name="cpf_usuario" size="20" maxlength="14" value="<?php echo htmlspecialchars(formatar_cpf($cpf_usuario)); ?>" required>
            </p>

            <p>
                <label>E-mail:</label><br>
                <input type="email" name="email_usuario" size="50" value="<?php echo htmlspecialchars($email_usuario); ?>" required>
            </p>

            <p>
                <label>Nova senha (deixe em branco para manter a atual):</label><br>
                <input type="password" name="senha_usuario">
            </p>

            <button type="submit">Salvar alterações</button>
        </form>

    <?php } ?>

    <p><a href="listar_usuarios.php">Voltar para a lista</a> | <a href="index.php">Menu</a></p>
</body>
</html>