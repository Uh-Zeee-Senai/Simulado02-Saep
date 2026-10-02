<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = '';

// Exclusão
if (isset($_GET['excluir'])) {
    try {
        $conn->prepare("DELETE FROM insumos WHERE idinsumos = ?")->execute([(int)$_GET['excluir']]);
        $mensagem = 'Insumo excluído com sucesso.';
        $tipoMensagem = 'sucesso';
    } catch (PDOException $e) {
        $mensagem = 'Erro ao excluir (verifique se existem movimentações vinculadas): ' . $e->getMessage();
        $tipoMensagem = 'erro';
    }
}

// Cadastro / Edição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['idinsumos'] ?? 0);
    $data = [
        'nome'              => trim($_POST['nome'] ?? ''),
        'custo'             => (float)str_replace(',', '.', $_POST['custo'] ?? '0'),
        'quantidade'        => (int)($_POST['quantidade'] ?? 0),
        'quantidade_minima' => (int)($_POST['quantidade_minima'] ?? 0),
        'categoria'         => trim($_POST['categoria'] ?? '') ?: null,
    ];

    if (!$data['nome'] || $data['custo'] <= 0) {
        $mensagem = 'Preencha todos os campos obrigatórios!';
        $tipoMensagem = 'erro';
    } else {
        try {
            if ($id > 0) {
                $sql = "UPDATE insumos SET nome = :nome, custo = :custo, quantidade = :quantidade, quantidade_minima = :quantidade_minima, categoria = :categoria WHERE idinsumos = :id";
                $data['id'] = $id;
            } else {
                $sql = "INSERT INTO insumos (nome, custo, quantidade, quantidade_minima, categoria) VALUES (:nome, :custo, :quantidade, :quantidade_minima, :categoria)";
            }
            $conn->prepare($sql)->execute($data);
            $mensagem = $id > 0 ? 'Insumo atualizado!' : 'Insumo cadastrado!';
            $tipoMensagem = 'sucesso';
        } catch (PDOException $e) {
            $mensagem = 'Erro: ' . $e->getMessage();
            $tipoMensagem = 'erro';
        }
    }
}

// Buscar insumo para edição
$insumoEditar = null;
if (isset($_GET['editar'])) {
    $stmt = $conn->prepare("SELECT * FROM insumos WHERE idinsumos = ?");
    $stmt->execute([(int)$_GET['editar']]);
    $insumoEditar = $stmt->fetch();
}

// Listar
$busca = trim($_GET['busca'] ?? '');
$sql = "SELECT * FROM insumos" . ($busca ? " WHERE nome LIKE :b OR categoria LIKE :b" : "") . " ORDER BY nome";
$stmt = $conn->prepare($sql);
$stmt->execute($busca ? ['b' => "%$busca%"] : []);
$insumos = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cadastro de Insumos - Indústria</title>
    <link rel="stylesheet" href="estilo.css">
</head>
<body>
    <h1>Cadastro de Insumos - Indústria</h1>
    <p><a href="index.php">⬅️ Voltar</a></p>
    <hr>

    <?php if ($mensagem): ?>
        <p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>"><?= htmlspecialchars($mensagem) ?></p>
        <hr>
    <?php endif; ?>

    <h2><?= $insumoEditar ? 'Editar' : 'Novo' ?> Insumo</h2>
    <form method="POST">
        <input type="hidden" name="idinsumos" value="<?= $insumoEditar['idinsumos'] ?? 0 ?>">
        
        <label>Nome: * 
            <input type="text" name="nome" size="40" value="<?= htmlspecialchars($insumoEditar['nome'] ?? '') ?>" required>
        </label><br><br>

        <label>Categoria: * 
            <input type="text" name="categoria" size="30" value="<?= htmlspecialchars($insumoEditar['categoria'] ?? '') ?>" required>
        </label><br><br>

        <label>Custo Unitário (R$): * 
            <input type="number" step="0.01" min="0.01" name="custo" value="<?= htmlspecialchars($insumoEditar['custo'] ?? '') ?>" required>
        </label><br><br>

        <label>Quantidade em Estoque: 
            <input type="number" name="quantidade" min="0" value="<?= $insumoEditar['quantidade'] ?? 0 ?>">
        </label><br><br>

        <label>Quantidade Mínima: 
            <input type="number" name="quantidade_minima" min="0" value="<?= $insumoEditar['quantidade_minima'] ?? 5 ?>">
        </label><br><br>

        <button type="submit">Salvar</button>
        <?php if ($insumoEditar): ?>
            <a href="insumos.php"><button type="button">Cancelar</button></a>
        <?php endif; ?>
    </form>

    <hr>

    <h2>Lista de Insumos</h2>
    <form method="GET">
        <label>Buscar: 
            <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Nome ou Categoria...">
        </label>
        <button type="submit">Buscar</button>
        <?php if ($busca): ?>
            <a href="insumos.php">Limpar</a>
        <?php endif; ?>
    </form>
    <br>

    <?php if ($insumos): ?>
        <table border="1" cellpadding="5" cellspacing="0">
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Custo</th>
                <th>Estoque Atual</th>
                <th>Estoque Mínimo</th>
                <th>Aviso</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($insumos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['idinsumos']) ?></td>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['categoria'] ?? '') ?></td>
                    <td>R$ <?= number_format($p['custo'], 2, ',', '.') ?></td>
                    <td><?= $p['quantidade'] ?></td>
                    <td><?= $p['quantidade_minima'] ?></td>
                    <td><?= $p['quantidade'] < $p['quantidade_minima'] ? '<strong style="color:red;">Abaixo do Mínimo</strong>' : 'Normal' ?></td>
                    <td>
                        <a href="insumos.php?editar=<?= $p['idinsumos'] ?>">Editar</a> | 
                        <a href="insumos.php?excluir=<?= $p['idinsumos'] ?>" onclick="return confirm('Deseja realmente excluir este insumo?')">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>Nenhum insumo cadastrado.</p>
    <?php endif; ?>
</body>
</html>