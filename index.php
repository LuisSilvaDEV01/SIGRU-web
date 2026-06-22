<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

$total_usuarios = $conn->query("SELECT COUNT(*) AS n FROM usuario")->fetch_assoc()['n'];
$refeicoes_hoje = $conn->query("SELECT COUNT(*) AS n FROM refeicao WHERE DATE(horario_entrada) = CURDATE()")->fetch_assoc()['n'];
$fila_espera    = $conn->query("SELECT COUNT(*) AS n FROM itens_fila WHERE situacao = 'em espera'")->fetch_assoc()['n'];
$caixa_hoje     = $conn->query("SELECT COALESCE(SUM(valor),0) AS v FROM refeicao WHERE DATE(horario_entrada) = CURDATE()")->fetch_assoc()['v'];

$ultimas_refeicoes = $conn->query("
    SELECT u.nome_completo, ca.turno, ca.data_servico, r.valor,
           r.horario_entrada, r.horario_saida
    FROM refeicao r
    JOIN usuario u ON u.id_usuario = r.id_usuario
    JOIN itens_cardapio ic ON ic.id_itens_cardapio = r.id_itens_cardapio
    JOIN cardapio ca ON ca.id_cardapio = ic.id_cardapio
    ORDER BY r.horario_entrada DESC LIMIT 6
");

$estoque = $conn->query("
    SELECT p.nome_produto, p.unidade_medida, e.quantidade
    FROM estoque e
    JOIN produto p ON p.id_produto = e.id_produto
    ORDER BY e.quantidade ASC LIMIT 6
");

// Cardápio do dia agrupado por turno
$cardapio_dia = $conn->query("
    SELECT ca.turno, ict.descricao AS tipo_item, ic.descricao AS detalhe
    FROM cardapio ca
    JOIN itens_cardapio ic ON ic.id_cardapio = ca.id_cardapio
    JOIN itens_cardapio_tipo ict ON ict.id_tipo = ic.id_tipo
    WHERE DATE(ca.data_servico) = CURDATE()
    ORDER BY ca.turno, ict.descricao
");

$cardapio_agrupado = [];
while ($c = $cardapio_dia->fetch_assoc()) {
    $cardapio_agrupado[$c['turno']][] = $c;
}
?>

<!-- Métricas -->
<div class="metric-grid">
    <div class="metric-card accent">
        <div class="metric-label">Usuários cadastrados</div>
        <div class="metric-value"><?= $total_usuarios ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Refeições hoje</div>
        <div class="metric-value"><?= $refeicoes_hoje ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Em fila agora</div>
        <div class="metric-value"><?= $fila_espera ?></div>
    </div>
    <div class="metric-card accent">
        <div class="metric-label">Caixa do dia</div>
        <div class="metric-value">R$ <?= number_format($caixa_hoje, 2, ',', '.') ?></div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem">

    <!-- Cardápio do dia -->
    <div class="card" style="margin-bottom:0">
        <div class="card-title"><i class="ti ti-clipboard-list"></i> Cardápio de hoje</div>
        <?php if (empty($cardapio_agrupado)): ?>
            <p class="text-muted">Nenhum cardápio cadastrado para hoje.</p>
        <?php else: ?>
            <?php foreach ($cardapio_agrupado as $turno => $itens): ?>
                <div style="margin-bottom:1rem">
                    <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;
                                color:var(--text-muted);margin-bottom:8px;padding-bottom:6px;
                                border-bottom:1px solid var(--border)">
                        <i class="ti <?= $turno === 'Almoço' ? 'ti-sun' : 'ti-moon' ?>"></i>
                        <?= htmlspecialchars($turno) ?>
                    </div>
                    <?php foreach ($itens as $item): ?>
                        <div style="display:flex;align-items:flex-start;gap:8px;padding:6px 0;
                                    border-bottom:1px solid var(--border)">
                            <span class="pill pill-purple" style="flex-shrink:0;margin-top:1px">
                                <?= htmlspecialchars($item['tipo_item']) ?>
                            </span>
                            <?php if ($item['detalhe']): ?>
                                <span style="font-size:12px;color:var(--text-muted);line-height:1.5">
                                    <?= htmlspecialchars($item['detalhe']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Estoque -->
    <div class="card" style="margin-bottom:0">
        <div class="card-title"><i class="ti ti-package"></i> Estoque atual</div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Produto</th><th>Qtd.</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php while ($e = $estoque->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($e['nome_produto']) ?></td>
                        <td><?= $e['quantidade'] ?> <?= htmlspecialchars($e['unidade_medida']) ?></td>
                        <td>
                            <?php if ($e['quantidade'] < 80): ?>
                                <span class="pill pill-amber">Baixo</span>
                            <?php else: ?>
                                <span class="pill pill-green">OK</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Últimas refeições -->
<div class="card" style="margin-top:1.25rem">
    <div class="card-title"><i class="ti ti-tools-kitchen-2"></i> Últimas refeições</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Usuário</th>
                    <th>Cardápio</th>
                    <th>Valor</th>
                    <th>Entrada</th>
                    <th>Saída</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($r = $ultimas_refeicoes->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($r['nome_completo']) ?></td>
                    <td><?= htmlspecialchars($r['turno']) ?> — <?= date('d/m', strtotime($r['data_servico'])) ?></td>
                    <td>R$ <?= number_format($r['valor'], 2, ',', '.') ?></td>
                    <td><?= date('H:i', strtotime($r['horario_entrada'])) ?></td>
                    <td>
                        <?php if ($r['horario_saida']): ?>
                            <?= date('H:i', strtotime($r['horario_saida'])) ?>
                        <?php else: ?>
                            <span class="pill pill-amber">Em curso</span>
                        <?php endif; ?>
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
