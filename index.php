<?php
require_once 'config.php';
verificarLogin();

if(isset($_GET['logout'])){
    session_destroy();
    header("location: login.php"); 
}

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema Controle de Estoque</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Sistema Controle de Estoque</h1>
    <div>
        <p>
            Usuário Logado: <?php echo $_SESSION['usuario_nome'] ?>            
        </p>
    </div>
    <hr>
    <h2>Menu</h2>
    <ul>
        <li><a href="insumos.php">Cadastro de Insumos</a></li>
        <li><a href="movimentacao.php">Estoque e Movimentações</a></li>
        <li><a href="index.php?logout=1">Sair</a></li>
    </ul>
</body>
</html>