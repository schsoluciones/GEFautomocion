<?php
declare(strict_types=1);

/**
 * CONTACT FORM - DEFINITIVO (ESTABLE)
 * PHP 8.2
 * Hostinger OK
 */

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false]);
    exit;
}

/* =================================================
   1️⃣ ANTI-BOT (MÍNIMO Y SEGURO)
   ================================================= */

// Honeypot
if (!empty($_POST['website'])) {
    echo json_encode(['success' => false]);
    exit;
}

// Tiempo mínimo: 100 ms
$formTime = $_POST['form_time'] ?? null;
if ($formTime !== null && is_numeric($formTime)) {
    $elapsed = (microtime(true) * 1000) - (float)$formTime;
    if ($elapsed < 100) {
        echo json_encode(['success' => false]);
        exit;
    }
}

// Cuenta matemática (robusta)
if (
    !isset($_POST['math_a'], $_POST['math_b'], $_POST['math_result'])
) {
    echo json_encode(['success' => false]);
    exit;
}

$mathA = (int) trim((string)$_POST['math_a']);
$mathB = (int) trim((string)$_POST['math_b']);
$mathR = (int) trim((string)$_POST['math_result']);

if (($mathA + $mathB) !== $mathR) {
    echo json_encode(['success' => false, 'message' => 'Verificación incorrecta']);
    exit;
}

/* =================================================
   2️⃣ VALIDACIÓN DATOS (SIN CAMBIOS)
   ================================================= */

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$body    = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $subject === '' || $body === '') {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false]);
    exit;
}

/* =================================================
   3️⃣ EMAIL
   ================================================= */

$logoUrl = 'https://grey-eagle-891611.hostingersite.com/assets/img/logo/Icono_GEF_Email.png';

$emailHtml = '
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f4f4f4;padding:30px">
<table width="600" align="center" style="background:#ffffff;border-radius:8px">
<tr><td style="text-align:center;padding:30px">
<img src="'.$logoUrl.'" style="max-width:150px">
</td></tr>
<tr><td style="padding:20px;font-size:14px;color:#333">
<strong>Nombre:</strong> '.htmlspecialchars($name).'<br>
<strong>Email:</strong> '.htmlspecialchars($email).'<br>
<strong>Asunto:</strong> '.htmlspecialchars($subject).'<br><br>
<strong>Mensaje:</strong><br>
'.nl2br(htmlspecialchars($body)).'
</td></tr>
<tr><td style="background:#111;color:#ccc;text-align:center;padding:15px;font-size:12px">
GEF Automoción · Avilés · +34 645 952 869
</td></tr>
</table>
</body>
</html>
';

/* =================================================
   4️⃣ ENVÍO
   ================================================= */

$to   = 'p405gl@gmail.com';
$from = 'info@sch-soluciones.com';

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: GEF Automoción <{$from}>\r\n";
$headers .= "Reply-To: {$email}\r\n";

$sent = mail($to, $subject, $emailHtml, $headers);

echo json_encode([
    'success' => $sent,
    'message' => $sent ? 'Mensaje enviado correctamente' : 'Error al enviar'
]);
