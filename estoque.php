<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

$erro    = '';
$sucesso = '';

// --- Cadastrar novo produto (mercadoria) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'novo_produto') {
    $nome_produto   = $conn->real_escape_string(trim($_POST['nome_produto']));
    $unidade_medida = $conn->real_escape_string(trim($_POST['unidade_medida']));
    $id_tipo        = (int) $_POST['id_tipo_produto'];
    $qtd_inicial    = (int) ($_POST['quantidade_inicial'] ?? 0);

    if (!$nome_produto || !$unidade_medida || !$id_tipo) {
        $erro = 'Preencha o nome, a unidade de medida e a categoria do produto.';
    } else {
        $conn->query("
            INSERT INTO produto (nome_produto, unidade_medida, id_tipo_produto)
            VALUES ('$nome_produto', '$unidade_medida', $id_tipo)
        ");
        $novo_id_produto = $conn->insert_id;

        // Cria registro inicial de estoque
        $conn->query("
            INSERT INTO estoque (quantidade, data_atualizacao, id_produto)
            VALUES ($qtd_inicial, NOW(), $novo_id_produto)
        ");

        // Se entrou com quantidade inicial, registra como movimentação de entrada
        if ($qtd_inicial > 0) {
            $conn->query("
                INSERT INTO movimento_estoque (tipo_movimento, quantidade_mov, data_movimento, id_produto, cnpj_fornecedor)
                VALUES ('ENTRADA', $qtd_inicial, NOW(), $novo_id_produto, NULL)
            ");
        }

        $sucesso = 'Mercadoria "' . htmlspecialchars($nome_produto) . '" cadastrada com sucesso!';
    }
}

// --- Registrar movimentação (entrada ou saída) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'movimentar') {
    $id_produto      = (int)   $_POST['id_produto'];
    $tipo_movimento  = in_array($_POST['tipo_movimento'], ['ENTRADA','SAIDA']) ? $_POST['tipo_movimento'] : '';
    $quantidade_mov  = (int)   $_POST['quantidade_mov'];
    $cnpj_fornecedor = $conn->real_escape_string(trim($_POST['cnpj_fornecedor'] ?? ''));

    if (!$id_produto || !$tipo_movimento || $quantidade_mov <= 0) {
        $erro = 'Preencha todos os campos obrigatórios e informe uma quantidade maior que zero.';
    } else {
        $estoque_atual = $conn->query("SELECT quantidade FROM estoque WHERE id_produto = $id_produto")->fetch_assoc();

        if ($tipo_movimento === 'SAIDA' && (!$estoque_atual || $estoque_atual['quantidade'] < $quantidade_mov)) {
            $erro = 'Quantidade insuficiente em estoque para registrar esta saída.';
        } else {
            $cnpj_sql = $cnpj_fornecedor ? "'$cnpj_fornecedor'" : 'NULL';

            $conn->query("
                INSERT INTO movimento_estoque (tipo_movimento, quantidade_mov, data_movimento, id_produto, cnpj_fornecedor)
                VALUES ('$tipo_movimento', $quantidade_mov, NOW(), $id_produto, $cnpj_sql)
            ");

            if ($estoque_atual) {
                $op = $tipo_movimento === 'ENTRADA' ? '+' : '-';
                $conn->query("
                    UPDATE estoque
                    SET quantidade = quantidade $op $quantidade_mov, data_atualizacao = NOW()
                    WHERE id_produto = $id_produto
                ");
            } else {
                $conn->query("
                    INSERT INTO estoque (quantidade, data_atualizacao, id_produto)
                    VALUES ($quantidade_mov, NOW(), $id_produto)
                ");
            }

            $sucesso = 'Movimentação registrada com sucesso!';
        }
    }
}

// --- Dados para formulários ---
$produtos      = $conn->query("SELECT id_produto, nome_produto, unidade_medida FROM produto ORDER BY nome_produto");
$fornecedores  = $conn->query("SELECT cnpj, razao_social FROM fornecedores ORDER BY razao_social");
$tipos_produto = $conn->query("SELECT id_tipo, descricao_tipo FROM tipo_produto ORDER BY descricao_tipo");
$tipos_arr = [];
while ($t = $tipos_produto->fetch_assoc()) $tipos_arr[] = $t;

// --- Estoque atual ---
$estoque = $conn->query("
    SELECT p.id_produto, p.nome_produto, p.unidade_medida,
           tp.descricao_tipo,
           COALESCE(e.quantidade, 0) AS quantidade,
           e.data_atualizacao
    FROM produto p
    JOIN tipo_produto tp ON tp.id_tipo = p.id_tipo_produto
    LEFT JOIN estoque e ON e.id_produto = p.id_produto
    ORDER BY tp.descricao_tipo, p.nome_produto
");

// --- Histórico de movimentações (últimas 15) ---
$historico = $conn->query("
    SELECT m.tipo_movimento, m.quantidade_mov, m.data_movimento,
           p.nome_produto, p.unidade_medida,
           f.razao_social
    FROM movimento_estoque m
    JOIN produto p ON p.id_produto = m.id_produto
    LEFT JOIN fornecedores f ON f.cnpj = m.cnpj_fornecedor
    ORDER BY m.data_movimento DESC
    LIMIT 15
");
?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> <?= $sucesso ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<!-- Abas: Nova mercadoria / Movimentação -->
<div class="card">
    <div class="flex gap-2" style="margin-bottom:1.25rem;border-bottom:1px solid var(--border);padding-bottom:0">
        <button type="button" class="tab-btn active" data-tab="tab-produto"
                style="padding:8px 4px;margin-right:1.5rem;border:none;background:none;cursor:pointer;
                       font-size:13px;font-weight:500;color:var(--purple-600);
                       border-bottom:2px solid var(--purple-600)">
            <i class="ti ti-box"></i> Cadastrar mercadoria
        </button>
        <button type="button" class="tab-btn" data-tab="tab-movimento"
                style="padding:8px 4px;border:none;background:none;cursor:pointer;
                       font-size:13px;font-weight:500;color:var(--text-muted);border-bottom:2px solid transparent">
            <i class="ti ti-arrows-exchange"></i> Registrar movimentação
        </button>
    </div>

    <!-- Cadastrar nova mercadoria -->
    <div id="tab-produto" class="tab-content">
        <form method="POST">
            <input type="hidden" name="action" value="novo_produto">
            <div class="form-grid">
                <div class="form-group">
                    <label>Nome da mercadoria *</label>
                    <input type="text" name="nome_produto" placeholder="Ex: Óleo de Soja 900ml"
                           value="<?= htmlspecialchars($_POST['nome_produto'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Categoria *</label>
                    <select name="id_tipo_produto">
                        <option value="">Selecione...</option>
                        <?php foreach ($tipos_arr as $t): ?>
                            <option value="<?= $t['id_tipo'] ?>"
                                <?= (($_POST['id_tipo_produto'] ?? '') == $t['id_tipo']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($t['descricao_tipo']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Unidade de medida *</label>
                    <select name="unidade_medida">
                        <option value="KG" <?= (($_POST['unidade_medida'] ?? '') === 'KG') ? 'selected' : '' ?>>KG — Quilograma</option>
                        <option value="LT" <?= (($_POST['unidade_medida'] ?? '') === 'LT') ? 'selected' : '' ?>>LT — Litro</option>
                        <option value="UN" <?= (($_POST['unidade_medida'] ?? '') === 'UN') ? 'selected' : '' ?>>UN — Unidade</option>
                        <option value="CX" <?= (($_POST['unidade_medida'] ?? '') === 'CX') ? 'selected' : '' ?>>CX — Caixa</option>
                        <option value="PT" <?= (($_POST['unidade_medida'] ?? '') === 'PT') ? 'selected' : '' ?>>PT — Pacote</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantidade inicial em estoque</label>
                    <input type="number" name="quantidade_inicial" min="0" placeholder="0 (opcional)"
                           value="<?= htmlspecialchars($_POST['quantidade_inicial'] ?? '') ?>">
                </div>
            </div>
            <div style="margin-top:1rem">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy"></i> Cadastrar mercadoria
                </button>
            </div>
        </form>
    </div>

    <!-- Registrar movimentação -->
    <div id="tab-movimento" class="tab-content" style="display:none">
        <form method="POST">
            <input type="hidden" name="action" value="movimentar">
            <div class="form-grid">
                <div class="form-group">
                    <label>Produto *</label>
                    <select name="id_produto" required>
                        <option value="">Selecione o produto...</option>
                        <?php while ($p = $produtos->fetch_assoc()): ?>
                            <option value="<?= $p['id_produto'] ?>">
                                <?= htmlspecialchars($p['nome_produto']) ?> (<?= $p['unidade_medida'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Tipo de movimentação *</label>
                    <select name="tipo_movimento" id="tipo_movimento" required>
                        <option value="">Selecione...</option>
                        <option value="ENTRADA">↑ Entrada (compra / recebimento)</option>
                        <option value="SAIDA">↓ Saída (consumo / descarte)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Quantidade *</label>
                    <input type="number" name="quantidade_mov" min="1" placeholder="Ex: 50">
                </div>
                <div class="form-group" id="campo-fornecedor">
                    <label>Fornecedor <span class="text-muted">(obrigatório para entradas)</span></label>
                    <select name="cnpj_fornecedor">
                        <option value="">— Nenhum / não se aplica —</option>
                        <?php
                        $fornecedores->data_seek(0);
                        while ($f = $fornecedores->fetch_assoc()):
                        ?>
                            <option value="<?= $f['cnpj'] ?>"><?= htmlspecialchars($f['razao_social']) ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>
            <div style="margin-top:1rem">
                <button type="submit" class="btn btn-primary">
                    <i class="ti ti-device-floppy"></i> Registrar movimentação
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Estoque atual -->
<div class="card">
    <div class="card-title"><i class="ti ti-package"></i> Estoque atual por produto</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Produto</th>
                    <th>Categoria</th>
                    <th>Quantidade</th>
                    <th>Unidade</th>
                    <th>Última atualização</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($e = $estoque->fetch_assoc()): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($e['nome_produto']) ?></strong></td>
                    <td><?= htmlspecialchars($e['descricao_tipo']) ?></td>
                    <td style="font-size:15px;font-weight:600"><?= $e['quantidade'] ?></td>
                    <td><?= htmlspecialchars($e['unidade_medida']) ?></td>
                    <td class="text-muted">
                        <?= $e['data_atualizacao'] ? date('d/m/Y H:i', strtotime($e['data_atualizacao'])) : '—' ?>
                    </td>
                    <td>
                        <?php if ($e['quantidade'] == 0): ?>
                            <span class="pill pill-red">Zerado</span>
                        <?php elseif ($e['quantidade'] < 80): ?>
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

<!-- Histórico -->
<div class="card">
    <div class="card-title"><i class="ti ti-history"></i> Histórico de movimentações recentes</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Data / Hora</th>
                    <th>Produto</th>
                    <th>Tipo</th>
                    <th>Quantidade</th>
                    <th>Fornecedor</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($h = $historico->fetch_assoc()): ?>
                <tr>
                    <td class="text-muted"><?= date('d/m/Y H:i', strtotime($h['data_movimento'])) ?></td>
                    <td><?= htmlspecialchars($h['nome_produto']) ?></td>
                    <td>
                        <?php if ($h['tipo_movimento'] === 'ENTRADA'): ?>
                            <span class="pill pill-green">↑ Entrada</span>
                        <?php else: ?>
                            <span class="pill pill-red">↓ Saída</span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= $h['quantidade_mov'] ?></strong> <?= htmlspecialchars($h['unidade_medida']) ?></td>
                    <td><?= htmlspecialchars($h['razao_social'] ?? '—') ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// Alternar abas
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

// Mostrar/ocultar campo fornecedor conforme tipo
const tipoMov = document.getElementById('tipo_movimento');
if (tipoMov) {
    tipoMov.addEventListener('change', function () {
        const label = document.querySelector('#campo-fornecedor label span');
        label.textContent = this.value === 'ENTRADA'
            ? '(obrigatório para entradas)'
            : '(opcional para saídas)';
    });
}
</script>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
