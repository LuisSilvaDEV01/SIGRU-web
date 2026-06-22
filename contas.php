<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

$erro    = '';
$sucesso = '';

// --- Nova conta a pagar ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'nova_pagar') {
    $valor           = (float) str_replace(',', '.', $_POST['valor']);
    $data_vencimento = $conn->real_escape_string($_POST['data_vencimento']);
    $origem          = $conn->real_escape_string(trim($_POST['origem']));
    $cnpj_fornecedor = $conn->real_escape_string(trim($_POST['cnpj_fornecedor'] ?? ''));

    if ($valor <= 0 || !$data_vencimento || !$origem) {
        $erro = 'Preencha valor, data de vencimento e origem.';
    } else {
        $cnpj_sql = $cnpj_fornecedor ? "'$cnpj_fornecedor'" : 'NULL';
        $conn->query("
            INSERT INTO conta_pagar (valor, data_vencimento, status, origem, cnpj_fornecedor)
            VALUES ($valor, '$data_vencimento', 'PENDENTE', '$origem', $cnpj_sql)
        ");
        $sucesso = 'Conta a pagar cadastrada com sucesso!';
    }
}

// --- Nova conta a receber ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'nova_receber') {
    $valor         = (float) str_replace(',', '.', $_POST['valor_receber']);
    $data_prevista = $conn->real_escape_string($_POST['data_prevista']);
    $origem        = $conn->real_escape_string(trim($_POST['origem_receber']));

    if ($valor <= 0 || !$data_prevista || !$origem) {
        $erro = 'Preencha valor, data prevista e origem.';
    } else {
        $conn->query("
            INSERT INTO conta_receber (valor, data_prevista, origem)
            VALUES ($valor, '$data_prevista', '$origem')
        ");
        $sucesso = 'Conta a receber cadastrada com sucesso!';
    }
}

// --- Marcar conta a pagar como PAGA ---
if (isset($_GET['pagar'])) {
    $id = (int) $_GET['pagar'];
    $conn->query("UPDATE conta_pagar SET status = 'PAGO' WHERE id_conta_pagar = $id");
    header("Location: contas.php?msg=pago");
    exit;
}

// --- Reabrir conta (voltar para pendente) ---
if (isset($_GET['reabrir'])) {
    $id = (int) $_GET['reabrir'];
    $conn->query("UPDATE conta_pagar SET status = 'PENDENTE' WHERE id_conta_pagar = $id");
    header("Location: contas.php?msg=reaberto");
    exit;
}

// --- Excluir conta a pagar ---
if (isset($_GET['excluir_pagar'])) {
    $id = (int) $_GET['excluir_pagar'];
    $conn->query("DELETE FROM conta_pagar WHERE id_conta_pagar = $id");
    header("Location: contas.php?msg=excluido");
    exit;
}

// --- Excluir conta a receber ---
if (isset($_GET['excluir_receber'])) {
    $id = (int) $_GET['excluir_receber'];
    $conn->query("DELETE FROM conta_receber WHERE id_conta_receber = $id");
    header("Location: contas.php?msg=excluido");
    exit;
}

$msg = $_GET['msg'] ?? '';

// --- Dados auxiliares ---
$fornecedores = $conn->query("SELECT cnpj, razao_social FROM fornecedores ORDER BY razao_social");

// --- Listagens ---
$contas_pagar = $conn->query("
    SELECT cp.id_conta_pagar, cp.valor, cp.data_vencimento, cp.status, cp.origem,
           f.razao_social
    FROM conta_pagar cp
    LEFT JOIN fornecedores f ON f.cnpj = cp.cnpj_fornecedor
    ORDER BY FIELD(cp.status,'PENDENTE','PAGO'), cp.data_vencimento
");

$contas_receber = $conn->query("
    SELECT id_conta_receber, valor, data_prevista, origem
    FROM conta_receber
    ORDER BY data_prevista
");

// --- Totais ---
$total_pendente = $conn->query("SELECT COALESCE(SUM(valor),0) AS v FROM conta_pagar WHERE status='PENDENTE'")->fetch_assoc()['v'];
$total_pago     = $conn->query("SELECT COALESCE(SUM(valor),0) AS v FROM conta_pagar WHERE status='PAGO'")->fetch_assoc()['v'];
$total_receber  = $conn->query("SELECT COALESCE(SUM(valor),0) AS v FROM conta_receber")->fetch_assoc()['v'];
$saldo_previsto = $total_receber - $total_pendente;
?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> <?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
<?php if ($msg === 'pago'): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> Conta marcada como paga.</div>
<?php elseif ($msg === 'reaberto'): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> Conta reaberta (pendente novamente).</div>
<?php elseif ($msg === 'excluido'): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> Conta excluída.</div>
<?php endif; ?>

<!-- Métricas financeiras -->
<div class="metric-grid">
    <div class="metric-card">
        <div class="metric-label">A pagar (pendente)</div>
        <div class="metric-value" style="color:var(--red-600)">R$ <?= number_format($total_pendente, 2, ',', '.') ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Já pago</div>
        <div class="metric-value" style="color:var(--teal-600)">R$ <?= number_format($total_pago, 2, ',', '.') ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">A receber</div>
        <div class="metric-value" style="color:var(--teal-600)">R$ <?= number_format($total_receber, 2, ',', '.') ?></div>
    </div>
    <div class="metric-card accent">
        <div class="metric-label">Saldo previsto</div>
        <div class="metric-value">R$ <?= number_format($saldo_previsto, 2, ',', '.') ?></div>
    </div>
</div>

<!-- Abas: Contas a pagar / Contas a receber -->
<div class="card">
    <div class="flex gap-2" style="margin-bottom:1.25rem;border-bottom:1px solid var(--border);padding-bottom:0">
        <button type="button" class="tab-btn active" data-tab="tab-pagar"
                style="padding:8px 4px;margin-right:1.5rem;border:none;background:none;cursor:pointer;
                       font-size:13px;font-weight:500;color:var(--purple-600);
                       border-bottom:2px solid var(--purple-600)">
            <i class="ti ti-file-invoice"></i> Contas a pagar
        </button>
        <button type="button" class="tab-btn" data-tab="tab-receber"
                style="padding:8px 4px;border:none;background:none;cursor:pointer;
                       font-size:13px;font-weight:500;color:var(--text-muted);border-bottom:2px solid transparent">
            <i class="ti ti-cash"></i> Contas a receber
        </button>
    </div>

    <!-- CONTAS A PAGAR -->
    <div id="tab-pagar" class="tab-content">
        <form method="POST" style="margin-bottom:1.5rem">
            <input type="hidden" name="action" value="nova_pagar">
            <div class="form-grid">
                <div class="form-group">
                    <label>Valor (R$) *</label>
                    <input type="number" name="valor" min="0.01" step="0.01" placeholder="Ex: 1500.00">
                </div>
                <div class="form-group">
                    <label>Data de vencimento *</label>
                    <input type="date" name="data_vencimento">
                </div>
                <div class="form-group">
                    <label>Origem *</label>
                    <select name="origem" id="origem_pagar">
                        <option value="FORNECEDOR">FORNECEDOR</option>
                        <option value="CEMIG">CEMIG (energia)</option>
                        <option value="COPASA">COPASA (água)</option>
                        <option value="MANUTENCAO">Manutenção</option>
                        <option value="OUTROS">Outros</option>
                    </select>
                </div>
                <div class="form-group" id="campo-fornecedor-pagar">
                    <label>Fornecedor</label>
                    <select name="cnpj_fornecedor">
                        <option value="">— Não se aplica —</option>
                        <?php while ($f = $fornecedores->fetch_assoc()): ?>
                            <option value="<?= $f['cnpj'] ?>"><?= htmlspecialchars($f['razao_social']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div style="margin-top:1rem">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy"></i> Lançar conta a pagar
                </button>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Origem</th><th>Fornecedor</th><th>Valor</th><th>Vencimento</th><th>Status</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php if ($contas_pagar->num_rows === 0): ?>
                        <tr><td colspan="6" class="text-muted" style="text-align:center;padding:1.5rem">Nenhuma conta cadastrada.</td></tr>
                    <?php endif; ?>
                    <?php while ($c = $contas_pagar->fetch_assoc()):
                        $pago = $c['status'] === 'PAGO';
                        $vencida = !$pago && strtotime($c['data_vencimento']) < strtotime(date('Y-m-d'));
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($c['origem']) ?></td>
                        <td><?= htmlspecialchars($c['razao_social'] ?? '—') ?></td>
                        <td>R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                        <td><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></td>
                        <td>
                            <?php if ($pago): ?>
                                <span class="pill pill-green">Pago</span>
                            <?php elseif ($vencida): ?>
                                <span class="pill pill-red">Vencida</span>
                            <?php else: ?>
                                <span class="pill pill-amber">Pendente</span>
                            <?php endif; ?>
                        </td>
                        <td class="flex gap-2">
                            <?php if (!$pago): ?>
                                <a href="contas.php?pagar=<?= $c['id_conta_pagar'] ?>" class="btn btn-secondary btn-sm">
                                    <i class="ti ti-check"></i> Marcar pago
                                </a>
                            <?php else: ?>
                                <a href="contas.php?reabrir=<?= $c['id_conta_pagar'] ?>" class="btn btn-secondary btn-sm">
                                    <i class="ti ti-rotate"></i> Reabrir
                                </a>
                            <?php endif; ?>
                            <a href="contas.php?excluir_pagar=<?= $c['id_conta_pagar'] ?>"
                               class="btn btn-danger btn-sm" onclick="return confirm('Excluir esta conta?')">
                                <i class="ti ti-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CONTAS A RECEBER -->
    <div id="tab-receber" class="tab-content" style="display:none">
        <form method="POST" style="margin-bottom:1.5rem">
            <input type="hidden" name="action" value="nova_receber">
            <div class="form-grid">
                <div class="form-group">
                    <label>Valor (R$) *</label>
                    <input type="number" name="valor_receber" min="0.01" step="0.01" placeholder="Ex: 15000.00">
                </div>
                <div class="form-group">
                    <label>Data prevista *</label>
                    <input type="date" name="data_prevista">
                </div>
                <div class="form-group full">
                    <label>Origem *</label>
                    <input type="text" name="origem_receber" placeholder="Ex: Subsídio Universidade, Recargas Consolidadas">
                </div>
            </div>
            <div style="margin-top:1rem">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy"></i> Lançar conta a receber
                </button>
            </div>
        </form>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Origem</th><th>Valor</th><th>Data prevista</th><th>Ações</th></tr>
                </thead>
                <tbody>
                    <?php if ($contas_receber->num_rows === 0): ?>
                        <tr><td colspan="4" class="text-muted" style="text-align:center;padding:1.5rem">Nenhuma conta cadastrada.</td></tr>
                    <?php endif; ?>
                    <?php while ($r = $contas_receber->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($r['origem']) ?></td>
                        <td>R$ <?= number_format($r['valor'], 2, ',', '.') ?></td>
                        <td><?= date('d/m/Y', strtotime($r['data_prevista'])) ?></td>
                        <td>
                            <a href="contas.php?excluir_receber=<?= $r['id_conta_receber'] ?>"
                               class="btn btn-danger btn-sm" onclick="return confirm('Excluir esta conta?')">
                                <i class="ti ti-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.style.color = 'var(--text-muted)';
            b.style.borderBottomColor = 'transparent';
        });
        document.querySelectorAll('.tab-content').forEach(c => c.style.display = 'none');
        btn.style.color = 'var(--purple-600)';
        btn.style.borderBottomColor = 'var(--purple-600)';
        document.getElementById(btn.dataset.tab).style.display = 'block';
    });
});

// Esconder campo fornecedor se origem não for FORNECEDOR
const origemSelect = document.getElementById('origem_pagar');
const campoFornecedor = document.getElementById('campo-fornecedor-pagar');
function atualizaCampoFornecedor() {
    campoFornecedor.style.display = origemSelect.value === 'FORNECEDOR' ? 'flex' : 'none';
}
if (origemSelect) {
    origemSelect.addEventListener('change', atualizaCampoFornecedor);
    atualizaCampoFornecedor();
}
</script>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
