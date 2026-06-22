<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

$id_fila = (int) ($_GET['id_fila'] ?? 0);
if (!$id_fila) { header("Location: fila.php"); exit; }

// Buscar dados do item na fila + usuário
$item = $conn->query("
    SELECT i.id_itens_fila, i.id_usuario,
           u.nome_completo, u.matricula, u.id_categoria,
           cat.descricao AS categoria,
           cd.saldo, cd.id_carteira,
           f.tipo_fila
    FROM itens_fila i
    JOIN usuario u ON u.id_usuario = i.id_usuario
    JOIN categoria_usuario cat ON cat.id_categoria = u.id_categoria
    LEFT JOIN carteira_digital cd ON cd.id_usuario = u.id_usuario
    JOIN fila f ON f.id_fila = i.id_fila
    WHERE i.id_itens_fila = $id_fila
")->fetch_assoc();

if (!$item) { header("Location: fila.php"); exit; }

// Cardápio do dia
$cardapios = $conn->query("
    SELECT ca.id_cardapio, ca.turno, ic.id_itens_cardapio,
           ict.descricao AS tipo_item, ict.id_tipo,
           ic.descricao AS descricao_item,
           COALESCE(v.valor, 0) AS valor
    FROM cardapio ca
    JOIN itens_cardapio ic ON ic.id_cardapio = ca.id_cardapio
    JOIN itens_cardapio_tipo ict ON ict.id_tipo = ic.id_tipo
    LEFT JOIN itens_cardapio_tipo_categorias_valores v
        ON v.id_tipo = ict.id_tipo AND v.id_categoria = {$item['id_categoria']}
    WHERE DATE(ca.data_servico) = CURDATE()
    ORDER BY ca.turno, ict.descricao
");

$erro  = '';
$sucesso = false;

// --- Registrar refeição ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_itens_cardapio   = (int) $_POST['id_itens_cardapio'];
    $id_categoria_valores = (int) $_POST['id_categoria_valores'];
    $valor               = (float) str_replace(',', '.', $_POST['valor']);
    $id_usuario          = $item['id_usuario'];

    if (!$id_itens_cardapio || !$id_categoria_valores || $valor <= 0) {
        $erro = 'Selecione um item do cardápio válido.';
    } else {
        // Verifica saldo
        if ($item['saldo'] < $valor) {
            $erro = 'Saldo insuficiente na carteira (R$ ' . number_format($item['saldo'], 2, ',', '.') . ').';
        } else {
            // INSERT refeição
            $conn->query("
                INSERT INTO refeicao (id_usuario, id_itens_cardapio, id_categoria_valores, valor, horario_entrada)
                VALUES ($id_usuario, $id_itens_cardapio, $id_categoria_valores, $valor, NOW())
            ");

            // Desconta saldo da carteira
            $conn->query("
                UPDATE carteira_digital SET saldo = saldo - $valor
                WHERE id_usuario = $id_usuario
            ");

            // Finaliza na fila
            $conn->query("UPDATE itens_fila SET situacao = 'finalizado' WHERE id_itens_fila = $id_fila");

            $sucesso = true;
        }
    }
}

// Recarregar saldo após possível desconto
$saldo_atual = $conn->query("SELECT saldo FROM carteira_digital WHERE id_usuario = {$item['id_usuario']}")->fetch_assoc()['saldo'] ?? 0;
?>

<?php if ($sucesso): ?>
    <div class="alert alert-success">
        <i class="ti ti-check"></i>
        Refeição registrada com sucesso! Caixa do dia atualizado.
        <a href="fila.php" style="margin-left:8px;font-weight:500">Voltar à fila</a>
    </div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<!-- Info do usuário -->
<div class="card">
    <div class="card-title"><i class="ti ti-user"></i> Usuário em atendimento</div>
    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem">
        <div>
            <div class="text-muted">Nome</div>
            <strong><?= htmlspecialchars($item['nome_completo']) ?></strong>
        </div>
        <div>
            <div class="text-muted">Matrícula</div>
            <strong><?= htmlspecialchars($item['matricula']) ?></strong>
        </div>
        <div>
            <div class="text-muted">Categoria</div>
            <strong><?= htmlspecialchars($item['categoria']) ?></strong>
        </div>
        <div>
            <div class="text-muted">Tipo de fila</div>
            <strong><?= htmlspecialchars($item['tipo_fila']) ?></strong>
        </div>
        <div>
            <div class="text-muted">Saldo na carteira</div>
            <strong style="color:<?= $saldo_atual < 10 ? '#A32D2D' : '#27500A' ?>">
                R$ <?= number_format($saldo_atual, 2, ',', '.') ?>
            </strong>
        </div>
    </div>
</div>

<!-- Seleção da refeição -->
<?php if (!$sucesso): ?>
<div class="card">
    <div class="card-title"><i class="ti ti-tools-kitchen-2"></i> Selecione o item do cardápio</div>

    <?php if ($cardapios->num_rows === 0): ?>
        <div class="alert alert-error">
            <i class="ti ti-alert-circle"></i>
            Nenhum cardápio cadastrado para hoje. Cadastre um cardápio antes de registrar refeições.
        </div>
    <?php else: ?>

    <form method="POST" id="form-refeicao">
        <input type="hidden" name="id_itens_cardapio" id="campo_id_itens_cardapio">
        <input type="hidden" name="id_categoria_valores" id="campo_id_categoria_valores">
        <input type="hidden" name="valor" id="campo_valor">

        <div style="display:grid;gap:0.75rem;margin-bottom:1.25rem">
            <?php
            $turno_atual = '';
            $cardapios->data_seek(0);
            while ($c = $cardapios->fetch_assoc()):
                if ($c['turno'] !== $turno_atual):
                    $turno_atual = $c['turno'];
            ?>
                <div style="font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;
                            color:var(--text-muted);margin-top:0.5rem;padding-bottom:4px;
                            border-bottom:1px solid var(--border)">
                    <?= htmlspecialchars($turno_atual) ?>
                </div>
            <?php endif; ?>

            <label class="opcao-refeicao" data-id="<?= $c['id_itens_cardapio'] ?>"
                   data-valor="<?= $c['valor'] ?>"
                   data-catval="<?= $c['id_tipo'] ?>"
                   style="display:flex;align-items:center;justify-content:space-between;
                          padding:12px 16px;border:1.5px solid var(--border);border-radius:var(--radius-md);
                          cursor:pointer;transition:border-color 0.15s,background 0.15s">
                <div>
                    <div style="font-weight:500;font-size:14px"><?= htmlspecialchars($c['tipo_item']) ?></div>
                    <?php if ($c['descricao_item']): ?>
                        <div class="text-muted" style="margin-top:2px"><?= htmlspecialchars($c['descricao_item']) ?></div>
                    <?php endif; ?>
                </div>
                <div style="font-size:16px;font-weight:600;color:var(--purple-600)">
                    <?php if ($c['valor'] > 0): ?>
                        R$ <?= number_format($c['valor'], 2, ',', '.') ?>
                    <?php else: ?>
                        <span class="pill pill-amber">Sem preço</span>
                    <?php endif; ?>
                </div>
            </label>

            <?php endwhile; ?>
        </div>

        <!-- Resumo selecionado -->
        <div id="resumo" style="display:none;background:var(--purple-50);border:1.5px solid var(--purple-200);
                                 border-radius:var(--radius-md);padding:1rem 1.25rem;margin-bottom:1rem">
            <div style="font-size:13px;color:var(--purple-800)">Item selecionado</div>
            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px">
                <strong id="resumo-item" style="font-size:15px"></strong>
                <strong id="resumo-valor" style="font-size:18px;color:var(--purple-600)"></strong>
            </div>
        </div>

        <div class="flex gap-2">
            <button type="submit" id="btn-confirmar" class="btn btn-primary" disabled>
                <i class="ti ti-check"></i> Confirmar refeição
            </button>
            <a href="fila.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>

    <script>
    // Buscar id_categoria_valores real via fetch ao selecionar
    const opcoes = document.querySelectorAll('.opcao-refeicao');
    let selecionado = null;

    opcoes.forEach(el => {
        el.addEventListener('click', async () => {
            opcoes.forEach(o => {
                o.style.borderColor = 'var(--border)';
                o.style.background = '';
            });
            el.style.borderColor = 'var(--purple-600)';
            el.style.background  = 'var(--purple-50)';

            const idItens = el.dataset.id;
            const valor   = parseFloat(el.dataset.valor);
            const idTipo  = el.dataset.catval;
            const label   = el.querySelector('div > div:first-child').textContent.trim();

            document.getElementById('campo_id_itens_cardapio').value = idItens;
            document.getElementById('campo_valor').value = valor;

            // Buscar id_categoria_valores pelo tipo + categoria do usuário
            const resp = await fetch(`get_categoria_valor.php?id_tipo=${idTipo}&id_categoria=<?= $item['id_categoria'] ?>`);
            const data = await resp.json();
            document.getElementById('campo_id_categoria_valores').value = data.id_categoria_valores ?? 0;

            document.getElementById('resumo').style.display = 'block';
            document.getElementById('resumo-item').textContent = label;
            document.getElementById('resumo-valor').textContent =
                'R$ ' + valor.toFixed(2).replace('.', ',');

            document.getElementById('btn-confirmar').disabled = (valor <= 0 || !data.id_categoria_valores);
        });
    });
    </script>

    <?php endif; ?>
</div>
<?php endif; ?>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
