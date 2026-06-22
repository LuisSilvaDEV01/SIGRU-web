<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sigru');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
$conn->set_charset('utf8mb4');

if ($conn->connect_error) {
    die('<div style="font-family:sans-serif;padding:2rem;color:#A32D2D;">
        <strong>Erro de conexão:</strong> ' . $conn->connect_error . '
        <br><small>Verifique se o XAMPP está rodando e o banco <em>sigru</em> foi criado.</small>
    </div>');
}
?>
