<?php
declare(strict_types=1);

/**
 * CONTACT FORM – SEGURO Y FUNCIONAL
 * UTF-8 sin BOM
 * No cerrar con ?>
 */

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
    exit;
}

/* =========================
   ANTISPAM
   ========================= */

// Honeypot
if (!empty($_POST['website'] ?? '')) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Spam detectado'
    ]);
    exit;
}

// Timestamp mínimo 3 segundos
$formTime = (int)($_POST['form_time'] ?? 0);
if ($formTime === 0 || (time() * 1000 - $formTime) < 3000) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Envío demasiado rápido'
    ]);
    exit;
}

// Verificación matemática
$a = (int)($_POST['math_a'] ?? 0);
$b = (int)($_POST['math_b'] ?? 0);
$result = (int)($_POST['math_result'] ?? -1);

if ($a + $b !== $result) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Verificación matemática incorrecta'
    ]);
    exit;
}

/* =========================
   VALIDACIÓN DE CAMPOS
   ========================= */

$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$subject = trim($_POST['subject'] ?? '');
$body    = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $subject === '' || $body === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Campos incompletos'
    ]);
    exit;
}

if (strlen($name) < 3 || strlen($body) < 10) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Datos demasiado cortos'
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Email inválido'
    ]);
    exit;
}

/* =========================
   EMAIL
   ========================= */

$logoUrl = 'https://grey-eagle-891611.hostingersite.com/assets/img/logo/Icono_GEF_Email.png';

$emailHtml = '
<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial">
<table width="100%" cellpadding="0" cellspacing="0" style="padding:30px">
<tr><td align="center">

<table width="600" style="background:#fff;border-radius:8px">
<tr>
<td style="text-align:center;padding:30px">
<img src="'.$logoUrl.'" style="max-width:150px">
</td>
</tr>

<tr>
<td style="padding:20px 40px;font-size:14px;color:#333">
<strong>Nombre:</strong> '.htmlspecialchars($name).'<br>
<strong>Email:</strong> '.htmlspecialchars($email).'<br>
<strong>Asunto:</strong> '.htmlspecialchars($subject).'<br><br>
<strong>Mensaje:</strong><br>
'.nl2br(htmlspecialchars($body)).'
</td>
</tr>

<tr>
<td style="background:#111;color:#ccc;padding:20px;text-align:center;font-size:12px">
GEF Automoción · Avilés · +34 645 952 869
</td>
</tr>

</table>

</td></tr>
</table>
</body>
</html>
';

$to   = 'p405gl@gmail.com';
$from = 'gef.automocion@gmail.com';

$headers  = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: GEF Automoción <{$from}>\r\n";
$headers .= "Reply-To: {$email}\r\n";

$sent = mail($to, $subject, $emailHtml, $headers);

echo json_encode([
    'success' => $sent,
    'message' => $sent
        ? 'Mensaje enviado correctamente'
        : 'Error al enviar el mensaje'
]);
