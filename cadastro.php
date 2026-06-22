<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

$categorias = $conn->query("SELECT id_categoria, descricao FROM categoria_usuario ORDER BY descricao");
$bairros    = $conn->query("
    SELECT b.id_bairro, b.descricao AS bairro, c.descricao AS cidade
    FROM bairro b JOIN cidade c ON c.id_cidade = b.id_cidade
    ORDER BY c.descricao, b.descricao
");

$erro = '';
$sucesso = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $matricula    = (int) $_POST['matricula'];
    $nome         = $conn->real_escape_string(trim($_POST['nome_completo']));
    $id_categoria = (int) $_POST['id_categoria'];
    $id_bairro    = (int) $_POST['id_bairro'];
    $endereco     = $conn->real_escape_string(trim($_POST['endereco']));

    if (!$matricula || !$nome || !$id_categoria || !$id_bairro || !$endereco) {
        $erro = 'Preencha todos os campos obrigatórios.';
    } else {
        $insert = $conn->query("
            INSERT INTO usuario (matricula, nome_completo, id_categoria, id_bairro, endereco)
            VALUES ($matricula, '$nome', $id_categoria, $id_bairro, '$endereco')
        ");

        if ($insert) {
            $novo_id = $conn->insert_id;
            $conn->query("INSERT INTO carteira_digital (id_usuario, saldo) VALUES ($novo_id, 0.00)");
            $sucesso = true;
        } else {
            $erro = 'Erro ao salvar: ' . $conn->error;
        }
    }
}
?>

<?php if ($sucesso): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> Usuário cadastrado com sucesso! <a href="usuarios.php">Ver lista</a></div>
<?php endif; ?>
<?php if ($erro): ?>
    <div class="alert alert-error"><i class="ti ti-alert-circle"></i> <?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-title"><i class="ti ti-user-plus"></i> Novo usuário</div>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Nome completo *</label>
                <input type="text" name="nome_completo" placeholder="Ex: João da Silva"
                       value="<?= htmlspecialchars($_POST['nome_completo'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Matrícula / SIAPE *</label>
                <input type="number" name="matricula" placeholder="Ex: 202410099"
                       value="<?= htmlspecialchars($_POST['matricula'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label>Categoria *</label>
                <select name="id_categoria">
                    <option value="">Selecione...</option>
                    <?php
                    $categorias->data_seek(0);
                    while ($c = $categorias->fetch_assoc()):
                    ?>
                        <option value="<?= $c['id_categoria'] ?>"
                            <?= (($_POST['id_categoria'] ?? '') == $c['id_categoria']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['descricao']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group">
                <label>Bairro *</label>
                <select name="id_bairro">
                    <option value="">Selecione...</option>
                    <?php while ($b = $bairros->fetch_assoc()): ?>
                        <option value="<?= $b['id_bairro'] ?>"
                            <?= (($_POST['id_bairro'] ?? '') == $b['id_bairro']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($b['bairro']) ?> — <?= htmlspecialchars($b['cidade']) ?>
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-group full">
                <label>Endereço *</label>
                <input type="text" name="endereco" placeholder="Rua, número, complemento"
                       value="<?= htmlspecialchars($_POST['endereco'] ?? '') ?>">
            </div>
        </div>
        <div class="flex gap-2" style="margin-top:1.25rem">
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy"></i> Salvar usuário
            </button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php
$conn->close();
require_once 'includes/footer.php';
?>
