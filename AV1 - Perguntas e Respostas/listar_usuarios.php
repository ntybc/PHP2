<?php
require "funcoes_usuarios.php";

$linhas_usuarios = ler_linhas($arquivo_usuarios);
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <title>Listar usuários</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Usuários cadastrados</h1>

    <?php if (empty($linhas_usuarios)) { ?>
        <p>Nenhum usuário cadastrado ainda.</p>
    <?php } else { ?>

        <table border="1" cellpadding="6" cellspacing="0">
            <tr>
                <th>Id</th>
                <th>Nome</th>
                <th>Data de nascimento</th>
                <th>CPF</th>
                <th>E-mail</th>
                <th>Ações</th>
            </tr>

            <?php foreach ($linhas_usuarios as $linha_usuario) { ?>
                <?php
                // Linha do usuário: id_usuario;nome_usuario;data_nascimento;cpf;email_usuario;senha_usuario
                $campos_usuario = explode(";", $linha_usuario);
                $id_usuario = $campos_usuario[0];
                $nome_usuario = $campos_usuario[1];
                $data_nascimento = $campos_usuario[2];
                $cpf_usuario = $campos_usuario[3];
                $email_usuario = $campos_usuario[4];
                // A senha (campo 5) não aparece na tela
                ?>
                <tr>
                    <td><?php echo $id_usuario; ?></td>
                    <td><?php echo htmlspecialchars($nome_usuario); ?></td>
                    <td><?php echo formatar_data($data_nascimento); ?></td>
                    <td><?php echo formatar_cpf($cpf_usuario); ?></td>
                    <td><?php echo htmlspecialchars($email_usuario); ?></td>
                    <td>
                        <a href="alterar_usuario.php?id_usuario=<?php echo $id_usuario; ?>">Alterar</a>

                        <form action="excluir_usuario.php" method="post" style="display:inline" onsubmit="return confirm('Tem certeza que deseja excluir este usuário?');">
                            <input type="hidden" name="id_usuario" value="<?php echo $id_usuario; ?>">
                            <button type="submit">Excluir</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        </table>

    <?php } ?>

    <p><a href="criar_usuario.php">Criar usuário</a> | <a href="index.php">Menu</a></p>
</body>
</html>