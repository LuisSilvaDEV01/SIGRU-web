<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'sigru');

function getConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
    if ($conn->connect_error) {
        die('<div style="padding:2rem;color:#A32D2D;font-family:sans-serif;">
            <strong>Erro de conexão:</strong> ' . $conn->connect_error . '<br><br>
            Verifique se o XAMPP está rodando e se o banco <strong>sigru</strong> foi criado.
        </div>');
    }
    return $conn;
}
?>
