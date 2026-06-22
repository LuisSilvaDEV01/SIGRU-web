<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

$erro   = '';
$sucesso = '';

// --- Registrar recarga ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'recarregar') {
    $id_usuario = (int)   $_POST['id_usuario'];
    $valor      = (float) str_replace(',', '.', $_POST['valor']);

    if (!$id_usuario || $valor <= 0) {
        $erro = 'Selecione um usuário e informe um valor maior que zero.';
    } else {
        $carteira = $conn->query("SELECT id_carteira FROM carteira_digital WHERE id_usuario = $id_usuario")->fetch_assoc();

        if (!$carteira) {
            // Cria carteira se não existir
            $conn->query("INSERT INTO carteira_digital (id_usuario, saldo) VALUES ($id_usuario, 0)");
            $id_carteira = $conn->insert_id;
        } else {
            $id_carteira = $carteira['id_carteira'];
        }

        $conn->query("UPDATE carteira_digital SET saldo = saldo + $valor WHERE id_carteira = $id_carteira");
        $conn->query("INSERT INTO recarga_historico (id_carteira, valor, data) VALUES ($id_carteira, $valor, NOW())");

        $sucesso = 'Recarga de R$ ' . number_format($valor, 2, ',', '.') . ' realizada com sucesso!';
    }
}

// --- Ajuste manual de saldo (correção) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ajustar') {
    $id_carteira  = (int)   $_POST['id_carteira'];
    $novo_saldo   = (float) str_replace(',', '.', $_POST['novo_saldo']);

    if (!$id_carteira || $novo_saldo < 0) {
        $erro = 'Informe um saldo válido (maior ou igual a zero).';
    } else {
        $conn->query("UPDATE carteira_digital SET saldo = $novo_saldo WHERE id_carteira = $id_carteira");
        $sucesso = 'Saldo ajustado com sucesso!';
    }
}

// --- Dados para formulário de recarga ---
$usuarios = $conn->query("
    SELECT u.id_usuario, u.nome_completo, u.matricula, c.descricao AS categoria
    FROM usuario u
    JOIN categoria_usuario c ON c.id_categoria = u.id_categoria
    ORDER BY u.nome_completo
");

// --- Carteiras com saldo ---
$carteiras = $conn->query("
    SELECT cd.id_carteira, cd.saldo,
           u.id_usuario, u.nome_completo, u.matricula,
           cat.descricao AS categoria
    FROM carteira_digital cd
    JOIN usuario u ON u.id_usuario = cd.id_usuario
    JOIN categoria_usuario cat ON cat.id_categoria = u.id_categoria
    ORDER BY u.nome_completo
");

// --- Histórico de recargas (últimas 20) ---
$historico = $conn->query("
    SELECT rh.valor, rh.data,
           u.nome_completo, u.matricula
    FROM recarga_historico rh
    JOIN carteira_digital cd ON cd.id_carteira = rh.id_carteira
    JOIN usuario u ON u.id_usuario = cd.id_usuario
    ORDER BY rh.data DESC
    LIMIT 20
");
?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> <?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;margin-bottom:1.25rem">

    <!-- Formulário de recarga -->
    <div class="card" style="margin-bottom:0">
        <div class="card-title"><i class="ti ti-plus"></i> Recarregar carteira</div>
        <form method="POST">
            <input type="hidden" name="action" value="recarregar">
            <div class="form-group" style="margin-bottom:1rem">
                <label>Usuário *</label>
                <select name="id_usuario" required>
                    <option value="">Selecione o usuário...</option>
                    <?php while ($u = $usuarios->fetch_assoc()): ?>
                        <option value="<?= $u['id_usuario'] ?>"
                            <?= (($_POST['id_usuario'] ?? '') == $u['id_usuario'] && ($_POST['action'] ?? '') === 'recarregar') ? 'selected' : '' ?>>
                            <?= htmlspecialchars($u['nome_completo']) ?>
                            — <?= htmlspecialchars($u['categoria']) ?>
                            (<?= $u['matricula'] ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group" style="margin-bottom:1rem">
                <label>Valor da recarga (R$) *</label>
                <input type="number" name="valor" min="0.01" step="0.01"
                       placeholder="Ex: 20.00"
                       value="<?= (($_POST['action'] ?? '') === 'recarregar') ? htmlspecialchars($_POST['valor'] ?? '') : '' ?>">
            </div>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-wallet"></i> Confirmar recarga
            </button>
        </form>
    </div>

    <!-- Histórico de recargas -->
    <div class="card" style="margin-bottom:0">
        <div class="card-title"><i class="ti ti-history"></i> Recargas recentes</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Valor</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($h = $historico->fetch_assoc()): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($h['nome_completo']) ?></strong><br>
                            <span class="text-muted"><?= $h['matricula'] ?></span>
                        </td>
                        <td style="color:var(--teal-600);font-weight:600">
                            + R$ <?= number_format($h['valor'], 2, ',', '.') ?>
                        </td>
                        <td class="text-muted"><?= date('d/m/Y H:i', strtotime($h['data'])) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Saldo de todas as carteiras -->
<div class="card">
    <div class="card-title"><i class="ti ti-credit-card"></i> Saldo das carteiras digitais</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Matrícula</th>
                    <th>Categoria</th>
                    <th>Saldo atual</th>
                    <th>Status</th>
                    <th>Ajustar saldo</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($c = $carteiras->fetch_assoc()):
                    $saldo = (float) $c['saldo'];
                    $cat = $c['categoria'];
                    $cls = match($cat) {
                        'Aluno'    => 'pill-purple',
                        'Bolsista' => 'pill-green',
                        'Servidor' => 'pill-amber',
                        default    => 'pill-gray'
                    };
                ?>
                <tr>
                    <td><strong><?= htmlspecialchars($c['nome_completo']) ?></strong></td>
                    <td class="text-muted"><?= $c['matricula'] ?></td>
                    <td><span class="pill <?= $cls ?>"><?= htmlspecialchars($cat) ?></span></td>
                    <td style="font-size:15px;font-weight:600;
                               color:<?= $saldo <= 0 ? 'var(--red-600)' : ($saldo < 10 ? 'var(--amber-600)' : 'var(--teal-600)') ?>">
                        R$ <?= number_format($saldo, 2, ',', '.') ?>
                    </td>
                    <td>
                        <?php if ($saldo <= 0): ?>
                            <span class="pill pill-red">Sem saldo</span>
                        <?php elseif ($saldo < 10): ?>
                            <span class="pill pill-amber">Saldo baixo</span>
                        <?php else: ?>
                            <span class="pill pill-green">OK</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <!-- Mini formulário de ajuste inline -->
                        <form method="POST" style="display:flex;gap:6px;align-items:center">
                            <input type="hidden" name="action" value="ajustar">
                            <input type="hidden" name="id_carteira" value="<?= $c['id_carteira'] ?>">
                            <input type="number" name="novo_saldo" min="0" step="0.01"
                                   value="<?= number_format($saldo, 2, '.', '') ?>"
                                   style="width:90px;padding:5px 8px;font-size:12px;
                                          border:1px solid var(--border-md);border-radius:var(--radius-md)">
                            <button type="submit" class="btn btn-secondary btn-sm"
                                    onclick="return confirm('Ajustar saldo para o valor informado?')">
                                <i class="ti ti-check"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
