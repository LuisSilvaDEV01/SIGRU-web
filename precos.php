<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

$erro    = '';
$sucesso = '';

// --- Salvar/atualizar preço (upsert) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'salvar_preco') {
    $id_tipo      = (int)   $_POST['id_tipo'];
    $id_categoria = (int)   $_POST['id_categoria'];
    $valor        = (float) str_replace(',', '.', $_POST['valor']);

    if (!$id_tipo || !$id_categoria || $valor < 0) {
        $erro = 'Selecione o tipo de item, a categoria e informe um valor válido.';
    } else {
        $existe = $conn->query("
            SELECT id_categoria_valores FROM itens_cardapio_tipo_categorias_valores
            WHERE id_tipo = $id_tipo AND id_categoria = $id_categoria
        ")->fetch_assoc();

        if ($existe) {
            $conn->query("
                UPDATE itens_cardapio_tipo_categorias_valores
                SET valor = $valor
                WHERE id_categoria_valores = {$existe['id_categoria_valores']}
            ");
            $sucesso = 'Preço atualizado com sucesso!';
        } else {
            $conn->query("
                INSERT INTO itens_cardapio_tipo_categorias_valores (id_tipo, id_categoria, valor)
                VALUES ($id_tipo, $id_categoria, $valor)
            ");
            $sucesso = 'Preço cadastrado com sucesso!';
        }
    }
}

// --- Excluir preço (categoria deixa de ter valor definido para aquele tipo) ---
if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    $conn->query("DELETE FROM itens_cardapio_tipo_categorias_valores WHERE id_categoria_valores = $id");
    header("Location: precos.php?msg=excluido");
    exit;
}

// --- Novo tipo de item de cardápio ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'novo_tipo') {
    $descricao = $conn->real_escape_string(trim($_POST['descricao_tipo']));
    if (!$descricao) {
        $erro = 'Informe a descrição do novo tipo de item.';
    } else {
        $conn->query("INSERT INTO itens_cardapio_tipo (descricao) VALUES ('$descricao')");
        $sucesso = 'Tipo de item "' . htmlspecialchars($descricao) . '" cadastrado!';
    }
}

$msg = $_GET['msg'] ?? '';

// --- Dados para a matriz de preços ---
$tipos = $conn->query("SELECT id_tipo, descricao FROM itens_cardapio_tipo ORDER BY descricao");
$tipos_arr = [];
while ($t = $tipos->fetch_assoc()) $tipos_arr[] = $t;

$categorias = $conn->query("SELECT id_categoria, descricao FROM categoria_usuario ORDER BY id_categoria");
$categorias_arr = [];
while ($c = $categorias->fetch_assoc()) $categorias_arr[] = $c;

// Mapa de preços existentes: [id_tipo][id_categoria] = ['valor'=>x,'id'=>y]
$precos_raw = $conn->query("SELECT * FROM itens_cardapio_tipo_categorias_valores");
$mapa_precos = [];
while ($p = $precos_raw->fetch_assoc()) {
    $mapa_precos[$p['id_tipo']][$p['id_categoria']] = [
        'valor' => $p['valor'],
        'id'    => $p['id_categoria_valores'],
    ];
}
?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> <?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erro) ?></div>
<?php endif; ?>
<?php if ($msg === 'excluido'): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> Preço removido. Esse item ficará sem valor definido para a categoria.</div>
<?php endif; ?>

<!-- Matriz de preços -->
<div class="card">
    <div class="card-title"><i class="ti ti-currency-dollar"></i> Tabela de preços — tipo de item × categoria de usuário</div>
    <p class="text-muted" style="margin-bottom:1rem">
        Clique em um valor para editar. Células vazias indicam que ainda não há preço definido para aquela combinação.
    </p>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Tipo de item</th>
                    <?php foreach ($categorias_arr as $cat): ?>
                        <th><?= htmlspecialchars($cat['descricao']) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tipos_arr as $tipo): ?>
                <tr>
                    <td><strong><?= htmlspecialchars($tipo['descricao']) ?></strong></td>
                    <?php foreach ($categorias_arr as $cat):
                        $info = $mapa_precos[$tipo['id_tipo']][$cat['id_categoria']] ?? null;
                    ?>
                        <td>
                            <button type="button" class="btn-preco"
                                    data-id-tipo="<?= $tipo['id_tipo'] ?>"
                                    data-tipo-nome="<?= htmlspecialchars($tipo['descricao']) ?>"
                                    data-id-categoria="<?= $cat['id_categoria'] ?>"
                                    data-categoria-nome="<?= htmlspecialchars($cat['descricao']) ?>"
                                    data-valor="<?= $info ? number_format($info['valor'], 2, '.', '') : '' ?>"
                                    data-id-existente="<?= $info ? $info['id'] : '' ?>"
                                    style="border:none;background:none;cursor:pointer;padding:6px 10px;
                                           border-radius:var(--radius-md);font-size:13px;font-weight:600;
                                           color:<?= $info ? 'var(--purple-600)' : 'var(--text-muted)' ?>;
                                           width:100%;text-align:left">
                                <?php if ($info): ?>
                                    R$ <?= number_format($info['valor'], 2, ',', '.') ?>
                                <?php else: ?>
                                    <i class="ti ti-plus" style="font-size:13px"></i> definir
                                <?php endif; ?>
                            </button>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Cadastro de novo tipo de item -->
<div class="card">
    <div class="card-title"><i class="ti ti-plus"></i> Novo tipo de item de cardápio</div>
    <form method="POST" class="flex gap-2" style="align-items:flex-end">
        <input type="hidden" name="action" value="novo_tipo">
        <div class="form-group" style="flex:1">
            <label>Descrição *</label>
            <input type="text" name="descricao_tipo" placeholder="Ex: Marmitex Vegano, Sobremesa">
        </div>
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy"></i> Cadastrar tipo
        </button>
    </form>
</div>

<!-- Modal de edição de preço -->
<div id="modal-preco" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,0.35);
                              z-index:200;align-items:center;justify-content:center">
    <div style="background:var(--surface);border-radius:var(--radius-lg);padding:1.5rem;width:360px;max-width:90vw">
        <div class="card-title" style="margin-bottom:1.25rem">
            <i class="ti ti-currency-dollar"></i> Definir preço
        </div>
        <form method="POST" id="form-preco">
            <input type="hidden" name="action" value="salvar_preco">
            <input type="hidden" name="id_tipo" id="modal_id_tipo">
            <input type="hidden" name="id_categoria" id="modal_id_categoria">

            <div class="form-group" style="margin-bottom:0.75rem">
                <label>Item</label>
                <input type="text" id="modal_tipo_nome" disabled style="background:var(--gray-50)">
            </div>
            <div class="form-group" style="margin-bottom:1rem">
                <label>Categoria</label>
                <input type="text" id="modal_categoria_nome" disabled style="background:var(--gray-50)">
            </div>
            <div class="form-group" style="margin-bottom:1.25rem">
                <label>Valor (R$) *</label>
                <input type="number" name="valor" id="modal_valor" min="0" step="0.01" required autofocus>
            </div>

            <div class="flex gap-2">
                <button type="submit" class="btn btn-primary"><i class="ti ti-check"></i> Salvar</button>
                <button type="button" id="btn-cancelar-modal" class="btn btn-secondary">Cancelar</button>
                <a href="#" id="btn-excluir-modal" class="btn btn-danger" style="display:none;margin-left:auto"
                   onclick="return confirm('Remover o preço definido para esta combinação?')">
                    <i class="ti ti-trash"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<script>
const modal = document.getElementById('modal-preco');

document.querySelectorAll('.btn-preco').forEach(btn => {
    btn.addEventListener('click', () => {
        document.getElementById('modal_id_tipo').value = btn.dataset.idTipo;
        document.getElementById('modal_id_categoria').value = btn.dataset.idCategoria;
        document.getElementById('modal_tipo_nome').value = btn.dataset.tipoNome;
        document.getElementById('modal_categoria_nome').value = btn.dataset.categoriaNome;
        document.getElementById('modal_valor').value = btn.dataset.valor;

        const btnExcluir = document.getElementById('btn-excluir-modal');
        if (btn.dataset.idExistente) {
            btnExcluir.style.display = 'inline-flex';
            btnExcluir.href = 'precos.php?excluir=' + btn.dataset.idExistente;
        } else {
            btnExcluir.style.display = 'none';
        }

        modal.style.display = 'flex';
    });
});

document.getElementById('btn-cancelar-modal').addEventListener('click', () => {
    modal.style.display = 'none';
});

modal.addEventListener('click', (e) => {
    if (e.target === modal) modal.style.display = 'none';
});
</script>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
