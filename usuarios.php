<?php
require_once 'includes/db.php';
require_once 'includes/header.php';

$conn = getConnection();

// DELETE
if (isset($_GET['excluir'])) {
    $id = (int) $_GET['excluir'];
    $conn->query("DELETE FROM usuario WHERE id_usuario = $id");
    header("Location: usuarios.php?msg=excluido");
    exit;
}

$msg = $_GET['msg'] ?? '';

$usuarios = $conn->query("
    SELECT u.id_usuario, u.matricula, u.nome_completo,
           c.descricao AS categoria,
           cd.saldo
    FROM usuario u
    JOIN categoria_usuario c ON c.id_categoria = u.id_categoria
    LEFT JOIN carteira_digital cd ON cd.id_usuario = u.id_usuario
    ORDER BY u.nome_completo
");
?>

<?php if ($msg === 'excluido'): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> Usuário excluído com sucesso.</div>
<?php endif; ?>
<?php if ($msg === 'salvo'): ?>
    <div class="alert alert-success"><i class="ti ti-check"></i> Usuário atualizado com sucesso.</div>
<?php endif; ?>

<div class="card">
    <div class="card-title" style="justify-content:space-between">
        <span><i class="ti ti-users"></i> Usuários cadastrados</span>
        <a href="cadastro.php" class="btn btn-primary btn-sm">
            <i class="ti ti-plus"></i> Novo usuário
        </a>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Matrícula</th>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Saldo carteira</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($u = $usuarios->fetch_assoc()): ?>
                <tr>
                    <td><?= htmlspecialchars($u['matricula']) ?></td>
                    <td><?= htmlspecialchars($u['nome_completo']) ?></td>
                    <td>
                        <?php
                        $cat = $u['categoria'];
                        $cls = match($cat) {
                            'Aluno'     => 'pill-purple',
                            'Bolsista'  => 'pill-green',
                            'Servidor'  => 'pill-amber',
                            'Visitante' => 'pill-gray',
                            default     => 'pill-gray'
                        };
                        ?>
                        <span class="pill <?= $cls ?>"><?= htmlspecialchars($cat) ?></span>
                    </td>
                    <td>R$ <?= number_format($u['saldo'] ?? 0, 2, ',', '.') ?></td>
                    <td class="flex gap-2">
                        <a href="editar_usuario.php?id=<?= $u['id_usuario'] ?>" class="btn btn-secondary btn-sm">
                            <i class="ti ti-edit"></i> Editar
                        </a>
                        <a href="usuarios.php?excluir=<?= $u['id_usuario'] ?>"
                           class="btn btn-danger btn-sm"
                           onclick="return confirm('Excluir este usuário?')">
                            <i class="ti ti-trash"></i> Excluir
                        </a>
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
