<?php
$conn = new mysqli(
    'localhost',
    'u657275120_adminGEF',
    'Contr@2025.',
    'u657275120_gefautomocion'
);

if ($conn->connect_error) {
    die('Error de conexión: ' . $conn->connect_error);
}

function verificarUsuario($username, $password) {
    global $conn;
    $stmt = $conn->prepare('SELECT id, rol FROM usuarios WHERE username = ? AND password = ?');
    $stmt->bind_param('ss', $username, $password);
    $stmt->execute();
    $resultado = $stmt->get_result();
    return $resultado->fetch_assoc();
}