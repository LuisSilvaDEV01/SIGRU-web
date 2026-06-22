<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

// --- Excluir item do cardápio ---
if (isset($_GET['excluir_item'])) {
    $id = (int) $_GET['excluir_item'];
    $conn->query("DELETE FROM itens_cardapio WHERE id_itens_cardapio = $id");
    header("Location: cardapio.php?msg=item_excluido");
    exit;
}

// --- Excluir cardápio inteiro ---
if (isset($_GET['excluir_cardapio'])) {
    $id = (int) $_GET['excluir_cardapio'];
    $conn->query("DELETE FROM itens_cardapio WHERE id_cardapio = $id");
    $conn->query("DELETE FROM cardapio WHERE id_cardapio = $id");
    header("Location: cardapio.php?msg=cardapio_excluido");
    exit;
}

$erro   = '';
$sucesso = '';
$msg    = $_GET['msg'] ?? '';

// --- Cadastrar novo cardápio com itens ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'novo_cardapio') {
    $data_servico = $conn->real_escape_string($_POST['data_servico']);
    $turno        = $conn->real_escape_string($_POST['turno']);
    $tipos        = $_POST['tipos']       ?? [];   // array de id_tipo
    $descricoes   = $_POST['descricoes']  ?? [];   // array de descricao opcional

    if (!$data_servico || !$turno || empty($tipos)) {
        $erro = 'Preencha a data, o turno e adicione ao menos um item.';
    } else {
        // Verifica se já existe cardápio para essa data+turno
        $existe = $conn->query("SELECT id_cardapio FROM cardapio
                                WHERE DATE(data_servico) = '$data_servico' AND turno = '$turno'")->fetch_assoc();
        if ($existe) {
            $id_cardapio = $existe['id_cardapio'];
        } else {
            $conn->query("INSERT INTO cardapio (data_servico, turno) VALUES ('$data_servico 00:00:00', '$turno')");
            $id_cardapio = $conn->insert_id;
        }

        foreach ($tipos as $i => $id_tipo) {
            $id_tipo  = (int) $id_tipo;
            $desc     = $conn->real_escape_string(trim($descricoes[$i] ?? ''));
            $desc_sql = $desc ? "'$desc'" : 'NULL';
            if ($id_tipo) {
                $conn->query("INSERT INTO itens_cardapio (id_cardapio, id_tipo, descricao)
                              VALUES ($id_cardapio, $id_tipo, $desc_sql)");
            }
        }
        $sucesso = 'Cardápio cadastrado com sucesso!';
    }
}

// --- Listar cardápios (mais recentes primeiro) ---
$cardapios_raw = $conn->query("
    SELECT ca.id_cardapio, ca.data_servico, ca.turno,
           ic.id_itens_cardapio, ict.descricao AS tipo_item, ic.descricao AS detalhe
    FROM cardapio ca
    LEFT JOIN itens_cardapio ic ON ic.id_cardapio = ca.id_cardapio
    LEFT JOIN itens_cardapio_tipo ict ON ict.id_tipo = ic.id_tipo
    ORDER BY ca.data_servico DESC, ca.turno, ict.descricao
");

// Agrupar por cardápio
$cardapios = [];
while ($r = $cardapios_raw->fetch_assoc()) {
    $cid = $r['id_cardapio'];
    if (!isset($cardapios[$cid])) {
        $cardapios[$cid] = [
            'id'           => $cid,
            'data_servico' => $r['data_servico'],
            'turno'        => $r['turno'],
            'itens'        => [],
        ];
    }
    if ($r['id_itens_cardapio']) {
        $cardapios[$cid]['itens'][] = [
            'id'      => $r['id_itens_cardapio'],
            'tipo'    => $r['tipo_item'],
            'detalhe' => $r['detalhe'],
        ];
    }
}

// Tipos de item disponíveis
$tipos_disponiveis = $conn->query("SELECT id_tipo, descricao FROM itens_cardapio_tipo ORDER BY descricao");
$tipos_arr = [];
while ($t = $tipos_disponiveis->fetch_assoc()) $tipos_arr[] = $t;
?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> <?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
<?php if ($msg === 'item_excluido'): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> Item removido do cardápio.</div>
<?php elseif ($msg === 'cardapio_excluido'): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> Cardápio excluído.</div>
<?php endif; ?>

<!-- Formulário de cadastro -->
<div class="card">
    <div class="card-title"><i class="ti ti-plus"></i> Novo cardápio</div>
    <form method="POST" id="form-cardapio">
        <input type="hidden" name="action" value="novo_cardapio">
        <div class="form-grid" style="margin-bottom:1rem">
            <div class="form-group">
                <label>Data do serviço *</label>
                <input type="date" name="data_servico" value="<?= date('Y-m-d') ?>" required>
            </div>
            <div class="form-group">
                <label>Turno *</label>
                <select name="turno" required>
                    <option value="Almoço">Almoço</option>
                    <option value="Jantar">Jantar</option>
                </select>
            </div>
        </div>

        <!-- Itens dinâmicos -->
        <div style="margin-bottom:0.75rem">
            <div style="font-size:12px;font-weight:500;color:var(--text-muted);margin-bottom:8px">
                Itens do cardápio *
            </div>
            <div id="itens-container" style="display:flex;flex-direction:column;gap:8px">
                <!-- linha inicial -->
                <div class="item-linha" style="display:grid;grid-template-columns:200px 1fr auto;gap:8px;align-items:center">
                    <select name="tipos[]" required>
                        <option value="">Tipo...</option>
                        <?php foreach ($tipos_arr as $t): ?>
                            <option value="<?= $t['id_tipo'] ?>"><?= htmlspecialchars($t['descricao']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="descricoes[]" placeholder="Descrição detalhada (opcional — ex: arroz, feijão, frango grelhado)">
                    <button type="button" class="btn btn-danger btn-sm remover-item" style="white-space:nowrap">
                        <i class="ti ti-trash"></i>
                    </button>
                </div>
            </div>
        </div>

        <div class="flex gap-2" style="margin-top:1rem">
            <button type="button" id="btn-add-item" class="btn btn-secondary">
                <i class="ti ti-plus"></i> Adicionar item
            </button>
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy"></i> Salvar cardápio
            </button>
        </div>
    </form>
</div>

<!-- Listagem de cardápios -->
<div class="card">
    <div class="card-title"><i class="ti ti-clipboard-list"></i> Cardápios cadastrados</div>

    <?php if (empty($cardapios)): ?>
        <p class="text-muted">Nenhum cardápio cadastrado ainda.</p>
    <?php else: ?>
        <?php foreach ($cardapios as $c): ?>
        <div style="border:1px solid var(--border);border-radius:var(--radius-md);margin-bottom:1rem;overflow:hidden">
            <!-- Cabeçalho do cardápio -->
            <div style="display:flex;justify-content:space-between;align-items:center;
                        padding:10px 16px;background:var(--gray-50);border-bottom:1px solid var(--border)">
                <div style="display:flex;align-items:center;gap:10px">
                    <i class="ti <?= $c['turno'] === 'Almoço' ? 'ti-sun' : 'ti-moon' ?>"
                       style="color:var(--purple-600)"></i>
                    <strong><?= date('d/m/Y', strtotime($c['data_servico'])) ?></strong>
                    <span class="pill pill-purple"><?= htmlspecialchars($c['turno']) ?></span>
                    <?php if (date('Y-m-d', strtotime($c['data_servico'])) === date('Y-m-d')): ?>
                        <span class="pill pill-green">Hoje</span>
                    <?php endif; ?>
                </div>
                <a href="cardapio.php?excluir_cardapio=<?= $c['id'] ?>"
                   class="btn btn-danger btn-sm"
                   onclick="return confirm('Excluir este cardápio e todos os seus itens?')">
                    <i class="ti ti-trash"></i> Excluir
                </a>
            </div>

            <!-- Itens -->
            <?php if (empty($c['itens'])): ?>
                <div style="padding:10px 16px;color:var(--text-muted);font-size:13px">Sem itens cadastrados.</div>
            <?php else: ?>
                <table style="width:100%;border-collapse:collapse;font-size:13px">
                    <thead>
                        <tr>
                            <th style="text-align:left;padding:8px 16px;font-size:11px;font-weight:600;
                                       text-transform:uppercase;letter-spacing:0.4px;color:var(--text-muted);
                                       border-bottom:1px solid var(--border)">Tipo</th>
                            <th style="text-align:left;padding:8px 16px;font-size:11px;font-weight:600;
                                       text-transform:uppercase;letter-spacing:0.4px;color:var(--text-muted);
                                       border-bottom:1px solid var(--border)">Descrição</th>
                            <th style="padding:8px 16px;border-bottom:1px solid var(--border)"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($c['itens'] as $item): ?>
                        <tr>
                            <td style="padding:8px 16px;border-bottom:1px solid var(--border);vertical-align:middle">
                                <span class="pill pill-purple"><?= htmlspecialchars($item['tipo']) ?></span>
                            </td>
                            <td style="padding:8px 16px;border-bottom:1px solid var(--border);color:var(--text-muted)">
                                <?= $item['detalhe'] ? htmlspecialchars($item['detalhe']) : '—' ?>
                            </td>
                            <td style="padding:8px 16px;border-bottom:1px solid var(--border);text-align:right">
                                <a href="cardapio.php?excluir_item=<?= $item['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Remover este item?')">
                                    <i class="ti ti-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
const tiposOpts = `<?php
    $opts = '';
    foreach ($tipos_arr as $t) {
        $opts .= '<option value="' . $t['id_tipo'] . '">' . htmlspecialchars($t['descricao']) . '</option>';
    }
    echo addslashes($opts);
?>`;

document.getElementById('btn-add-item').addEventListener('click', () => {
    const container = document.getElementById('itens-container');
    const div = document.createElement('div');
    div.className = 'item-linha';
    div.style.cssText = 'display:grid;grid-template-columns:200px 1fr auto;gap:8px;align-items:center';
    div.innerHTML = `
        <select name="tipos[]">
            <option value="">Tipo...</option>
            ${tiposOpts}
        </select>
        <input type="text" name="descricoes[]" placeholder="Descrição detalhada (opcional)">
        <button type="button" class="btn btn-danger btn-sm remover-item"><i class="ti ti-trash"></i></button>
    `;
    container.appendChild(div);
    div.querySelector('.remover-item').addEventListener('click', () => div.remove());
});

// Remover linha inicial
document.querySelectorAll('.remover-item').forEach(btn => {
    btn.addEventListener('click', () => btn.closest('.item-linha').remove());
});
</script>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
