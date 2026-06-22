<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

// --- Adicionar usuário à fila ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'adicionar') {
    $id_usuario = (int) $_POST['id_usuario'];
    $id_fila    = (int) $_POST['id_fila'];
    if ($id_usuario && $id_fila) {
        // Verifica se já está em espera
        $ja = $conn->query("SELECT id_itens_fila FROM itens_fila WHERE id_usuario = $id_usuario AND situacao = 'em espera'")->num_rows;
        if ($ja === 0) {
            $conn->query("INSERT INTO itens_fila (id_usuario, id_fila, horario_inscricao, situacao)
                          VALUES ($id_usuario, $id_fila, NOW(), 'em espera')");
        }
    }
    header("Location: fila.php");
    exit;
}

// --- Remover da fila ---
if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    $conn->query("DELETE FROM itens_fila WHERE id_itens_fila = $id");
    header("Location: fila.php");
    exit;
}

// --- Avançar para registro de refeição (muda estado para 'atendendo') ---
if (isset($_GET['atender'])) {
    $id = (int) $_GET['atender'];
    $conn->query("UPDATE itens_fila SET situacao = 'atendendo' WHERE id_itens_fila = $id");
    header("Location: registrar_refeicao.php?id_fila=$id");
    exit;
}

// --- Dados para o formulário de adicionar ---
$usuarios = $conn->query("SELECT id_usuario, nome_completo, matricula FROM usuario ORDER BY nome_completo");
$tipos_fila = $conn->query("SELECT id_fila, tipo_fila FROM fila ORDER BY id_fila");

// --- Fila atual ---
$fila = $conn->query("
    SELECT i.id_itens_fila, i.id_usuario, u.nome_completo, u.matricula,
           cat.descricao AS categoria,
           f.tipo_fila, i.horario_inscricao, i.situacao
    FROM itens_fila i
    JOIN usuario u ON u.id_usuario = i.id_usuario
    JOIN categoria_usuario cat ON cat.id_categoria = u.id_categoria
    JOIN fila f ON f.id_fila = i.id_fila
    ORDER BY FIELD(i.situacao, 'atendendo', 'em espera', 'finalizado'), i.horario_inscricao
");

$contadores = $conn->query("SELECT situacao, COUNT(*) AS n FROM itens_fila GROUP BY situacao")->fetch_all(MYSQLI_ASSOC);
$cont = array_column($contadores, 'n', 'situacao');
?>

<!-- Métricas -->
<div class="metric-grid">
    <div class="metric-card accent">
        <div class="metric-label">Em espera</div>
        <div class="metric-value"><?= $cont['em espera'] ?? 0 ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Em atendimento</div>
        <div class="metric-value"><?= $cont['atendendo'] ?? 0 ?></div>
    </div>
    <div class="metric-card">
        <div class="metric-label">Finalizados hoje</div>
        <div class="metric-value"><?= $cont['finalizado'] ?? 0 ?></div>
    </div>
</div>

<!-- Formulário: adicionar à fila -->
<div class="card">
    <div class="card-title"><i class="ti ti-user-plus"></i> Adicionar usuário à fila</div>
    <form method="POST">
        <input type="hidden" name="action" value="adicionar">
        <div class="form-grid">
            <div class="form-group">
                <label>Usuário *</label>
                <select name="id_usuario" required>
                    <option value="">Selecione o usuário...</option>
                    <?php while ($u = $usuarios->fetch_assoc()): ?>
                        <option value="<?= $u['id_usuario'] ?>">
                            <?= htmlspecialchars($u['nome_completo']) ?> — <?= $u['matricula'] ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Tipo de fila *</label>
                <select name="id_fila" required>
                    <option value="">Selecione...</option>
                    <?php while ($f = $tipos_fila->fetch_assoc()): ?>
                        <option value="<?= $f['id_fila'] ?>"><?= htmlspecialchars($f['tipo_fila']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
        </div>
        <div style="margin-top:1rem">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-plus"></i> Adicionar à fila
            </button>
        </div>
    </form>
</div>

<!-- Tabela da fila -->
<div class="card">
    <div class="card-title"><i class="ti ti-list-numbers"></i> Fila virtual — situação atual</div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Pos.</th>
                    <th>Usuário</th>
                    <th>Categoria</th>
                    <th>Tipo de fila</th>
                    <th>Entrada</th>
                    <th>Situação</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $pos = 1;
                while ($r = $fila->fetch_assoc()):
                    $em_espera  = $r['situacao'] === 'em espera';
                    $atendendo  = $r['situacao'] === 'atendendo';
                    $finalizado = $r['situacao'] === 'finalizado';
                    $cat = $r['categoria'];
                    $cls = match($cat) {
                        'Aluno'    => 'pill-purple',
                        'Bolsista' => 'pill-green',
                        'Servidor' => 'pill-amber',
                        default    => 'pill-gray'
                    };
                ?>
                <tr>
                    <td><?= ($em_espera || $atendendo) ? $pos++ : '—' ?></td>
                    <td>
                        <strong><?= htmlspecialchars($r['nome_completo']) ?></strong><br>
                        <span class="text-muted"><?= $r['matricula'] ?></span>
                    </td>
                    <td><span class="pill <?= $cls ?>"><?= htmlspecialchars($cat) ?></span></td>
                    <td><?= htmlspecialchars($r['tipo_fila']) ?></td>
                    <td><?= date('H:i', strtotime($r['horario_inscricao'])) ?></td>
                    <td>
                        <?php if ($em_espera): ?>
                            <span class="pill pill-amber">Em espera</span>
                        <?php elseif ($atendendo): ?>
                            <span class="pill pill-teal">Atendendo</span>
                        <?php else: ?>
                            <span class="pill pill-green">Finalizado</span>
                        <?php endif; ?>
                    </td>
                    <td class="flex gap-2">
                        <?php if ($em_espera): ?>
                            <a href="fila.php?atender=<?= $r['id_itens_fila'] ?>"
                               class="btn btn-primary btn-sm">
                                <i class="ti ti-check"></i> Atender
                            </a>
                        <?php endif; ?>
                        <?php if ($atendendo): ?>
                            <a href="registrar_refeicao.php?id_fila=<?= $r['id_itens_fila'] ?>"
                               class="btn btn-primary btn-sm">
                                <i class="ti ti-tools-kitchen-2"></i> Registrar refeição
                            </a>
                        <?php endif; ?>
                        <?php if (!$finalizado): ?>
                            <a href="fila.php?excluir=<?= $r['id_itens_fila'] ?>"
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Remover da fila?')">
                                <i class="ti ti-trash"></i>
                            </a>
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
