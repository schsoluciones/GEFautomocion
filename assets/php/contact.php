<?php
declare(strict_types=1);

/**
 * CONTACT FORM - VERSIÓN ESTABLE SIN TIEMPO
 * PHP 8.2 compatible
 * Hostinger compatible
 *
 * IMPORTANTE:
 * - No espacios antes de <?php
 * - UTF-8 sin BOM
 * - No cerrar con ?>
 */

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

/* =================================================
   ANTI-BOT (SEGURO Y SIMPLE)
   ================================================= */

// Honeypot
if (!empty($_POST['website'])) {
    echo json_encode(['success' => false]);
    exit;
}

// Cuenta matemática (OBLIGATORIA)
$mathA = isset($_POST['math_a']) ? (int)$_POST['math_a'] : null;
$mathB = isset($_POST['math_b']) ? (int)$_POST['math_b'] : null;
$mathR = isset($_POST['math_result']) ? (int)$_POST['math_result'] : null;

if ($mathA === null || $mathB === null || $mathR === null) {
    echo json_encode(['success' => false]);
    exit;
}

if (($mathA + $mathB) !== $mathR) {
    echo json_encode(['success' => false, 'message' => 'Verificación incorrecta']);
    exit;
}

/* =================================================
   VALIDACIÓN DE DATOS (TU CÓDIGO ORIGINAL)
   ================================================= */

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$body    = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $subject === '' || $body === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Campos incompletos']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

/* =================================================
   EMAIL HTML
   ================================================= */

$logoUrl = 'https://grey-eagle-891611.hostingersite.com/assets/img/logo/Icono_GEF_Email.png';

$emailHtml = '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 15px">
<tr><td align="center">

<table width="600" cellpadding="0" cellspacing="0"
style="background:#ffffff;border-radius:8px;overflow:hidden;
box-shadow:0 5px 20px rgba(0,0,0,0.08)">

<tr>
<td style="text-align:center;padding:30px 20px 20px">
<img src="'.$logoUrl.'" alt="GEF Automoción" style="max-width:150px">
</td>
</tr>

<tr>
<td style="padding:0 40px 20px;text-align:center">
<h2 style="margin:0;color:#111111;font-size:22px">
Nueva solicitud de contacto
</h2>
</td>
</tr>

<tr>
<td style="padding:20px 40px;color:#333333;font-size:14px">
<strong>Nombre:</strong> '.htmlspecialchars($name).'<br>
<strong>Email:</strong> '.htmlspecialchars($email).'<br>
<strong>Asunto:</strong> '.htmlspecialchars($subject).'<br><br>
<strong>Mensaje:</strong><br>
'.nl2br(htmlspecialchars($body)).'
</td>
</tr>

<tr>
<td style="background:#111111;padding:20px;text-align:center;font-size:12px;color:#cccccc">
<strong style="color:#ffffff">GEF Automoción</strong><br>
Avilés · +34 645 952 869 · gef.automocion@gmail.com
</td>
</tr>

</table>

</td></tr>
</table>
</body>
</html>
';

/* =================================================
   ENVÍO
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
