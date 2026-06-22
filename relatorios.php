<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

// --- Filtro de período ---
$hoje = date('Y-m-d');
$data_inicio = $_GET['data_inicio'] ?? $hoje;
$data_fim    = $_GET['data_fim']    ?? $hoje;

// Validação simples (evita datas inválidas / invertidas)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_inicio)) $data_inicio = $hoje;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_fim))    $data_fim    = $hoje;
if ($data_inicio > $data_fim) { [$data_inicio, $data_fim] = [$data_fim, $data_inicio]; }

$di = $conn->real_escape_string($data_inicio);
$df = $conn->real_escape_string($data_fim);

$periodo_label = ($di === $df)
    ? date('d/m/Y', strtotime($di))
    : date('d/m/Y', strtotime($di)) . ' até ' . date('d/m/Y', strtotime($df));

// --- Refeições por categoria (no período) ---
$refeicoes_cat = $conn->query("
    SELECT c.descricao AS categoria,
           COUNT(*) AS qtd,
           SUM(r.valor) AS total
    FROM refeicao r
    JOIN usuario u ON u.id_usuario = r.id_usuario
    JOIN categoria_usuario c ON c.id_categoria = u.id_categoria
    WHERE DATE(r.horario_entrada) BETWEEN '$di' AND '$df'
    GROUP BY c.descricao
    ORDER BY total DESC
");

$total_periodo = $conn->query("
    SELECT COALESCE(SUM(valor),0) AS v FROM refeicao
    WHERE DATE(horario_entrada) BETWEEN '$di' AND '$df'
")->fetch_assoc()['v'];

$qtd_refeicoes_periodo = $conn->query("
    SELECT COUNT(*) AS n FROM refeicao
    WHERE DATE(horario_entrada) BETWEEN '$di' AND '$df'
")->fetch_assoc()['n'];

// --- Contas a pagar pendentes (vencimento no período) ---
$contas_pagar = $conn->query("
    SELECT cp.id_conta_pagar, cp.valor, cp.data_vencimento, cp.status, cp.origem,
           f.razao_social
    FROM conta_pagar cp
    LEFT JOIN fornecedores f ON f.cnpj = cp.cnpj_fornecedor
    WHERE cp.status = 'PENDENTE'
      AND DATE(cp.data_vencimento) BETWEEN '$di' AND '$df'
    ORDER BY cp.data_vencimento
");

$total_pendente = $conn->query("
    SELECT COALESCE(SUM(valor),0) AS v FROM conta_pagar
    WHERE status = 'PENDENTE' AND DATE(data_vencimento) BETWEEN '$di' AND '$df'
")->fetch_assoc()['v'];

// --- Saldo das carteiras (não depende de período, é foto do momento atual) ---
$carteiras = $conn->query("
    SELECT u.nome_completo, c.descricao AS categoria, cd.saldo
    FROM carteira_digital cd
    JOIN usuario u ON u.id_usuario = cd.id_usuario
    JOIN categoria_usuario c ON c.id_categoria = u.id_categoria
    ORDER BY cd.saldo DESC
");

// --- Contas a receber (data prevista no período) ---
$contas_receber = $conn->query("
    SELECT valor, data_prevista, origem FROM conta_receber
    WHERE DATE(data_prevista) BETWEEN '$di' AND '$df'
    ORDER BY data_prevista
");

$total_receber = $conn->query("
    SELECT COALESCE(SUM(valor),0) AS v FROM conta_receber
    WHERE DATE(data_prevista) BETWEEN '$di' AND '$df'
")->fetch_assoc()['v'];

// --- Movimentações de estoque (no período) ---
$movimentos = $conn->query("
    SELECT p.nome_produto, m.tipo_movimento, m.quantidade_mov,
           m.data_movimento, f.razao_social
    FROM movimento_estoque m
    JOIN produto p ON p.id_produto = m.id_produto
    LEFT JOIN fornecedores f ON f.cnpj = m.cnpj_fornecedor
    WHERE DATE(m.data_movimento) BETWEEN '$di' AND '$df'
    ORDER BY m.data_movimento DESC
");
?>

<!-- Filtro de período -->
<div class="card">
    <div class="card-title"><i class="ti ti-calendar-stats"></i> Filtrar relatórios por período</div>
    <form method="GET" class="flex gap-2" style="align-items:flex-end;flex-wrap:wrap">
        <div class="form-group">
            <label>Data inicial</label>
            <input type="date" name="data_inicio" value="<?= htmlspecialchars($data_inicio) ?>">
        </div>
        <div class="form-group">
            <label>Data final</label>
            <input type="date" name="data_fim" value="<?= htmlspecialchars($data_fim) ?>">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-filter"></i> Aplicar filtro
        </button>
        <a href="relatorios.php" class="btn btn-secondary">
            <i class="ti ti-refresh"></i> Hoje
        </a>
        <div class="flex gap-2" style="margin-left:auto">
            <?php
            $atalhos = [
                '7 dias'  => date('Y-m-d', strtotime('-6 days')),
                '30 dias' => date('Y-m-d', strtotime('-29 days')),
                'Este mês'=> date('Y-m-01'),
            ];
            foreach ($atalhos as $label => $inicio):
            ?>
                <a href="relatorios.php?data_inicio=<?= $inicio ?>&data_fim=<?= $hoje ?>"
                   class="btn btn-secondary btn-sm"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
    </form>
    <div class="text-muted" style="margin-top:0.75rem">
        <i class="ti ti-info-circle"></i> Exibindo dados de <strong><?= $periodo_label ?></strong>
        <span style="margin-left:6px">(o saldo das carteiras sempre reflete o momento atual, independente do filtro)</span>
    </div>
</div>

<!-- Refeições por categoria -->
<div class="card">
    <div class="card-title"><i class="ti ti-tools-kitchen-2"></i> Refeições por categoria — <?= $periodo_label ?></div>
    <?php if ($refeicoes_cat->num_rows === 0): ?>
        <p class="text-muted">Nenhuma refeição registrada no período selecionado.</p>
    <?php else: ?>
        <?php while ($r = $refeicoes_cat->fetch_assoc()): ?>
        <div class="report-row">
            <span class="label"><?= htmlspecialchars($r['categoria']) ?></span>
            <span class="value"><?= $r['qtd'] ?> refeição(ões) &nbsp;·&nbsp; R$ <?= number_format($r['total'], 2, ',', '.') ?></span>
        </div>
        <?php endwhile; ?>
        <div class="report-row total">
            <span class="label">Total arrecadado no período (<?= $qtd_refeicoes_periodo ?> refeições)</span>
            <span class="value">R$ <?= number_format($total_periodo, 2, ',', '.') ?></span>
        </div>
    <?php endif; ?>
</div>

<!-- Contas a pagar -->
<div class="card">
    <div class="card-title"><i class="ti ti-file-invoice"></i> Contas a pagar pendentes — vencimento no período</div>
    <?php if ($contas_pagar->num_rows === 0): ?>
        <p class="text-muted">Nenhuma conta pendente com vencimento no período selecionado.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Origem</th><th>Fornecedor</th><th>Valor</th><th>Vencimento</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php while ($c = $contas_pagar->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($c['origem']) ?></td>
                    <td><?= htmlspecialchars($c['razao_social'] ?? '—') ?></td>
                    <td>R$ <?= number_format($c['valor'], 2, ',', '.') ?></td>
                    <td><?= date('d/m/Y', strtotime($c['data_vencimento'])) ?></td>
                    <td><span class="pill pill-amber"><?= htmlspecialchars($c['status']) ?></span></td>
                </tr>
                <?php endwhile; ?>
                <tr>
                    <td colspan="2"><strong>Total pendente no período</strong></td>
                    <td><strong>R$ <?= number_format($total_pendente, 2, ',', '.') ?></strong></td>
                    <td colspan="2"></td>
                </tr>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Saldo carteiras -->
<div class="card">
    <div class="card-title"><i class="ti ti-wallet"></i> Saldo das carteiras digitais <span class="text-muted">(momento atual)</span></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Usuário</th><th>Categoria</th><th>Saldo</th></tr>
            </thead>
            <tbody>
                <?php while ($c = $carteiras->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($c['nome_completo']) ?></td>
                    <td><?= htmlspecialchars($c['categoria']) ?></td>
                    <td>R$ <?= number_format($c['saldo'], 2, ',', '.') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Contas a receber -->
<div class="card">
    <div class="card-title"><i class="ti ti-cash"></i> Contas a receber — previstas no período</div>
    <?php if ($contas_receber->num_rows === 0): ?>
        <p class="text-muted">Nenhuma conta a receber prevista no período selecionado.</p>
    <?php else: ?>
        <?php while ($r = $contas_receber->fetch_assoc()): ?>
        <div class="report-row">
            <span class="label"><?= htmlspecialchars($r['origem']) ?> — <?= date('d/m/Y', strtotime($r['data_prevista'])) ?></span>
            <span class="value">R$ <?= number_format($r['valor'], 2, ',', '.') ?></span>
        </div>
        <?php endwhile; ?>
        <div class="report-row total">
            <span class="label">Total previsto no período</span>
            <span class="value">R$ <?= number_format($total_receber, 2, ',', '.') ?></span>
        </div>
    <?php endif; ?>
</div>

<!-- Movimentações de estoque -->
<div class="card">
    <div class="card-title"><i class="ti ti-arrows-exchange"></i> Movimentações de estoque — <?= $periodo_label ?></div>
    <?php if ($movimentos->num_rows === 0): ?>
        <p class="text-muted">Nenhuma movimentação de estoque no período selecionado.</p>
    <?php else: ?>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>Produto</th><th>Tipo</th><th>Qtd</th><th>Data</th><th>Fornecedor</th></tr>
            </thead>
            <tbody>
                <?php while ($m = $movimentos->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($m['nome_produto']) ?></td>
                    <td>
                        <span class="pill <?= $m['tipo_movimento'] === 'ENTRADA' ? 'pill-green' : 'pill-red' ?>">
                            <?= $m['tipo_movimento'] ?>
                        </span>
                    </td>
                    <td><?= $m['quantidade_mov'] ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($m['data_movimento'])) ?></td>
                    <td><?= htmlspecialchars($m['razao_social'] ?? '—') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
