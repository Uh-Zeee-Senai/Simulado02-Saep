<?php
require_once 'config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Processo do LOGIN
$mensagemErro = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';

    if (empty($email) || empty($senha)) {
        $mensagemErro = "Por favor, preencha todos os campos";
    } else {
        try {
            $sql = "SELECT * FROM usuarios WHERE email = :email";
            $stmt = $conn->prepare($sql);
            $stmt->execute(['email' => $email]);
            $usuario = $stmt->fetch();

            if (!$usuario || $senha !== $usuario['senha']) {
                $mensagemErro = "Email ou senha incorretos!";
            } else {
                // Sucesso!
                $_SESSION['usuario_id'] = $usuario['idusuarios'];
                $_SESSION['usuario_nome'] = $usuario['nome'];
                $_SESSION['usuario_email'] = $usuario['email'];
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $mensagemErro = 'Erro ao processar login: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Controle de Estoque</title>
    <link rel="stylesheet" href="estilo.css">
</head>

<body>

    <?php if (!empty($mensagemErro)): ?>
        <p style="color: red;"><?= htmlspecialchars($mensagemErro) ?></p>
    <?php endif; ?>

    <main>
        <h1>Sistema Controle de Estoque</h1>
        <form action="" method="post">
            <div>
                <label for="email">Email:</label>
                <input type="email" name="email" id="email" required>
            </div>
            <br>
            <div>
                <label for="senha">Senha:</label>
                <input type="password" name="senha" id="senha" required>
            </div>
            <br>
            <button type="submit">Entrar</button>
        </form>
    </main>
</body>

</html>