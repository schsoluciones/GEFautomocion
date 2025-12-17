<?php
declare(strict_types=1);

/**
 * IMPORTANTE:
 * - No espacios antes de <?php
 * - Archivo en UTF-8 sin BOM
 * - No cerrar con ?>
 */

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['success' => false, 'message' => 'Método no permitido']);
  exit;
}

// Sanitizar entradas
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

/* =========================
   CUERPO DEL EMAIL (HTML)
   ========================= */

$logoUrl = 'https://grey-eagle-891611.hostingersite.com/assets/img/logo/Icono_GEF_SinFondo_pequeno.svg';

$emailHtml = '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 15px">
<tr>
<td align="center">

<table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 5px 20px rgba(0,0,0,0.08)">

<!-- LOGO -->
<tr>
<td style="text-align:center;padding:30px 20px 20px">
<img src="'.$logoUrl.'" alt="GEF Automoción" style="max-width:150px">
</td>
</tr>

<!-- TÍTULO -->
<tr>
<td style="padding:0 40px 20px;text-align:center">
<h2 style="margin:0;color:#111111;font-size:22px;letter-spacing:1px">
Nueva solicitud de contacto
</h2>
</td>
</tr>

<!-- DATOS -->
<tr>
<td style="padding:20px 40px;color:#333333;font-size:14px">
<table width="100%" cellpadding="0" cellspacing="0">
<tr>
<td style="padding:8px 0;width:110px;color:#111111"><strong>Nombre</strong></td>
<td style="padding:8px 0">'.htmlspecialchars($name).'</td>
</tr>
<tr>
<td style="padding:8px 0;color:#111111"><strong>Email</strong></td>
<td style="padding:8px 0">'.htmlspecialchars($email).'</td>
</tr>
<tr>
<td style="padding:8px 0;color:#111111"><strong>Asunto</strong></td>
<td style="padding:8px 0">'.htmlspecialchars($subject).'</td>
</tr>
</table>

<div style="margin:25px 0;height:1px;background:#e0e0e0"></div>

<p style="margin:0 0 10px;color:#111111"><strong>Mensaje</strong></p>
<div style="background:#f9f9f9;padding:18px;border-radius:6px;line-height:1.6;color:#333333">
'.nl2br(htmlspecialchars($body)).'
</div>
</td>
</tr>

<!-- FOOTER NEGRO -->
<tr>
<td style="background:#111111;padding:20px;text-align:center;font-size:12px;color:#cccccc">
<strong style="color:#ffffff">GEF Automoción</strong><br>
Avilés · +34 645 952 869 · gef.automocion@gmail.com
</td>
</tr>

</table>

</td>
</tr>
</table>
</body>
</html>
';

/* =========================
   ENVÍO
   ========================= */

$to = 'p405gl@gmail.com';
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
