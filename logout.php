<?php
session_start();
require_once 'config.php'; 

// 1) Vaciar las variables de sesión
$_SESSION = [];

// 2) Invalidar la cookie de sesión (si existe)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3) Destruir la sesión
session_destroy();

// 4) Evitar volver con el botón “Atrás”
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');

// 5) Redirigir al login (ajusta a 'login.php' si no usas rutas limpias)
header('Location: login');
exit;
