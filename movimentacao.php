<?php
require_once 'config.php';
verificarLogin();

$mensagem = $tipoMensagem = $alertaEstoque = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $iid = (int)($_POST['idinsumos'] ?? 0);
    $tipo = $_POST['tipo'] ?? '';
    $qtd = (int)($_POST['quantidade'] ?? 0);
    $data = $_POST['data'] ?? '';

    if (!$iid || !$tipo || $qtd <= 0 || !$data) {
        $mensagem = 'Preencha todos os campos!';
        $tipoMensagem = 'erro';
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM insumos WHERE idinsumos = ?");
            $stmt->execute([$iid]);
            if (!($prod = $stmt->fetch())) {
                $mensagem = 'Produto não encontrado!';
                $tipoMensagem = 'erro';
            } else {
                $ant = (int)($prod['quantidade'] ?? 0);
                $novo = $tipo === 'ENTRADA' ? $ant + $qtd : $ant - $qtd;
                if ($novo < 0) {
                    $mensagem = 'Estoque insuficiente!';
                    $tipoMensagem = 'erro';
                } else {
                    $conn->beginTransaction();
                    $stmtMov = $conn->prepare("INSERT INTO movimentacao (data, quantidade, tipo, usuarios_idusuarios, insumos_idinsumos) VALUES (?, ?, ?, ?, ?)");
                    $stmtMov->execute([
                        $data . ' ' . date('H:i:s'),
                        $qtd,
                        $tipo === 'ENTRADA' ? 1 : 0,
                        $_SESSION['usuario_id'],
                        $iid
                    ]);
                    $conn->prepare("UPDATE insumos SET quantidade = ? WHERE idinsumos = ?")->execute([$novo, $iid]);
                    $conn->commit();
                    $mensagem = 'Movimentação registrada com sucesso!';
                    $tipoMensagem = 'sucesso';
                    if ($tipo === 'SAIDA' && $novo <= $prod['quantidade_minima']) {
                        $alertaEstoque = "ALERTA DE ESTOQUE BAIXO! O produto {$prod['nome']} está com estoque BAIXO! Atual: {$novo}, Mínimo: {$prod['quantidade_minima']}.";
                    }
                }
            }
        } catch (PDOException $e) {
            if ($conn->inTransaction()) $conn->rollBack();
            $mensagem = 'Erro: ' . $e->getMessage();
            $tipoMensagem = 'erro';
        }
    }
}

$insumos = $conn->query("SELECT * FROM insumos ORDER BY nome")->fetchAll();
$movimentacoes = $conn->query("SELECT m.*, p.nome AS produto_nome, u.nome AS usuario_nome FROM movimentacao m JOIN insumos p ON m.insumos_idinsumos = p.idinsumos JOIN usuarios u ON m.usuarios_idusuarios = u.idusuarios ORDER BY m.idmovimentacao DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <title>Gestão de Estoque - Industria</title>
    <link rel="stylesheet" href="estilo.css">
</head>

<body>
    <h1>Gestão de Estoque - Industria</h1>
    <p><a href="index.php">⬅️Voltar</a></p>
    <hr>

    <?php if ($mensagem): ?><p style="color: <?= $tipoMensagem === 'sucesso' ? 'green' : 'red' ?>"><?= htmlspecialchars($mensagem) ?></p>
        <hr><?php endif; ?>
    <?php if ($alertaEstoque): ?><p style="color: blue"><?= htmlspecialchars($alertaEstoque) ?></p>
        <hr><?php endif; ?>

    <h2>Nova Movimentação de Estoque</h2>
    <form method="post">
        <label>Produto:
            <select name="idinsumos" required>
                <option value="">Selecione...</option>
                <?php foreach ($insumos as $p): ?>
                    <option value="<?= $p['idinsumos'] ?>"><?= htmlspecialchars($p['nome']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <br>
        <label>Tipo:
            <input type="radio" name="tipo" value="ENTRADA" required> Entrada
            <input type="radio" name="tipo" value="SAIDA" required> Saída
        </label>
        <br>
        <label>Quantidade:
            <input type="number" name="quantidade" min="1" required>
        </label>
        <br>
        <label>
            Data:
            <input type="date" name="data" value="<?= date('Y-m-d') ?>" required>
        </label>
        <br>
        <br>
        <button type="submit">Registrar Movimentação</button>
    </form>

    <h2>Lista de insumos</h2>
    <?php if ($insumos): ?>
        <table border="1">
            <tr>
                <th>Código</th>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Estoque Atual</th>
                <th>Estoque Mínimo</th>
                <th>Aviso</th>
            </tr>
            <?php foreach ($insumos as $p): ?>
                <tr>
                    <td><?= htmlspecialchars($p['idinsumos']) ?></td>
                    <td><?= htmlspecialchars($p['nome']) ?></td>
                    <td><?= htmlspecialchars($p['categoria'] ?? '') ?></td>
                    <td>R$ <?= number_format($p['custo'], 2, ',', '.') ?></td>
                    <td><?= $p['quantidade'] ?></td>
                    <td><?= $p['quantidade_minima'] ?></td>
                    <td><?= $p['quantidade'] < $p['quantidade_minima'] ? '<strong>AVISO: estoque abaixo do mínimo</strong>' : 'Normal' ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?><p>Nenhum produto cadastrado.</p><?php endif; ?>

    <hr>

    <h2>Histórico de Movimentações</h2>
    <?php if ($movimentacoes): ?>
        <table border="1">
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Produto</th>
                <th>Tipo</th>
                <th>Quantidade</th>
                <th>Usuário</th>
            </tr>
            <?php foreach ($movimentacoes as $m): ?>
                <tr>
                    <td><?= $m['idmovimentacao'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($m['data'])) ?></td>
                    <td><?= htmlspecialchars($m['produto_nome']) ?></td>
                    <td><?= $m['tipo'] == 1 ? 'Entrada' : 'Saída' ?></td>
                    <td><?= $m['quantidade'] ?></td>
                    <td><?= htmlspecialchars($m['usuario_nome']) ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?><p>Nenhuma movimentação registrada.</p><?php endif; ?>
</body>

</html>