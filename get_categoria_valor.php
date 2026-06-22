<?php
require_once 'includes/db.php';
header('Content-Type: application/json');

$conn = getConnection();
$id_tipo      = (int) ($_GET['id_tipo']      ?? 0);
$id_categoria = (int) ($_GET['id_categoria'] ?? 0);

if (!$id_tipo || !$id_categoria) {
    echo json_encode(['id_categoria_valores' => null]);
    exit;
}

$r = $conn->query("
    SELECT id_categoria_valores, valor
    FROM itens_cardapio_tipo_categorias_valores
    WHERE id_tipo = $id_tipo AND id_categoria = $id_categoria
    LIMIT 1
")->fetch_assoc();

echo json_encode($r ?? ['id_categoria_valores' => null]);
$conn->close();
?>
