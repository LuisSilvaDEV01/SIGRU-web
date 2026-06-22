<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();
$id = (int) ($_GET['id'] ?? 0);

if (!$id) { header("Location: usuarios.php"); exit; }

$usuario = $conn->query("SELECT * FROM usuario WHERE id_usuario = $id")->fetch_assoc();
if (!$usuario) { header("Location: usuarios.php"); exit; }

$categorias = $conn->query("SELECT id_categoria, descricao FROM categoria_usuario ORDER BY descricao");
$bairros    = $conn->query("
    SELECT b.id_bairro, b.descricao AS bairro, c.descricao AS cidade
    FROM bairro b JOIN cidade c ON c.id_cidade = b.id_cidade
    ORDER BY c.descricao, b.descricao
");

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula    = (int) $_POST['matricula'];
    $nome         = $conn->real_escape_string(trim($_POST['nome_completo']));
    $id_categoria = (int) $_POST['id_categoria'];
    $id_bairro    = (int) $_POST['id_bairro'];
    $endereco     = $conn->real_escape_string(trim($_POST['endereco']));

    if (!$matricula || !$nome || !$id_categoria || !$id_bairro || !$endereco) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        $conn->query("
            UPDATE usuario
            SET matricula = $matricula,
                nome_completo = '$nome',
                id_categoria = $id_categoria,
                id_bairro = $id_bairro,
                endereco = '$endereco'
            WHERE id_usuario = $id
        ");
        header("Location: usuarios.php?msg=salvo");
        exit;
    }
}

$dados = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $usuario;
?>

<?php if ($erro): ?>
    <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-title"><i class="ti ti-edit"></i> Editar usuário</div>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Nome completo *</label>
                <input type="text" name="nome_completo" value="<?= htmlspecialchars($dados['nome_completo']) ?>">
            </div>
            <div class="form-group">
                <label>Matrícula / SIAPE *</label>
                <input type="number" name="matricula" value="<?= htmlspecialchars($dados['matricula']) ?>">
            </div>
            <div class="form-group">
                <label>Categoria *</label>
                <select name="id_categoria">
                    <?php
                    $categorias->data_seek(0);
                    while ($c = $categorias->fetch_assoc()):
                    ?>
                        <option value="<?= $c['id_categoria'] ?>"
                            <?= ($dados['id_categoria'] == $c['id_categoria']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['descricao']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Bairro *</label>
                <select name="id_bairro">
                    <?php while ($b = $bairros->fetch_assoc()): ?>
                        <option value="<?= $b['id_bairro'] ?>"
                            <?= ($dados['id_bairro'] == $b['id_bairro']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['bairro']) ?> — <?= htmlspecialchars($b['cidade']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>Endereço *</label>
                <input type="text" name="endereco" value="<?= htmlspecialchars($dados['endereco']) ?>">
            </div>
        </div>
        <div class="flex gap-2" style="margin-top:1.25rem">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy"></i> Salvar alterações
            </button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
