<?php 

$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$subject = isset($_POST['subject']) ? trim($_POST['subject']) : 'Nuevo mensaje de contacto';
$contact_message = isset($_POST['message']) ? trim($_POST['message']) : '';

// Validacion basica
if(empty($name) || empty($email) || empty($contact_message)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Por favor completa todos los campos']);
    exit;
}

// Construir el cuerpo del email HTML
$message = '<html><body>';
$message .= '<div style="font-family: Arial, sans-serif; background-color: #f5f5f5; padding: 20px;">';
$message .= '<div style="background-color: white; border-radius: 12px; padding: 30px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 15px rgba(0,0,0,0.1);">';
$message .= '<div style="background: linear-gradient(135deg, #c5b993 0%, #a89367 100%); color: white; padding: 30px; text-align: center; border-radius: 8px; margin-bottom: 20px;">';
$message .= '<h1 style="margin: 0; font-size: 28px;">Nueva Solicitud de Contacto</h1>';
$message .= '</div>';

$message .= '<div style="color: #757F95; font-size: 14px; margin-bottom: 20px;">';
$message .= '<p>Has recibido un nuevo mensaje a traves del formulario de contacto de GEF Automocion.</p>';
$message .= '</div>';

$message .= '<div style="background: #f9f9f9; border-left: 4px solid #c5b993; padding: 15px; margin: 15px 0; border-radius: 4px;">';
$message .= '<strong style="color: #111111; display: block; margin-bottom: 5px;">Nombre:</strong>';
$message .= '<p style="margin: 0; color: #757F95;">' . htmlspecialchars($name) . '</p>';
$message .= '</div>';

$message .= '<div style="background: #f9f9f9; border-left: 4px solid #c5b993; padding: 15px; margin: 15px 0; border-radius: 4px;">';
$message .= '<strong style="color: #111111; display: block; margin-bottom: 5px;">Email:</strong>';
$message .= '<p style="margin: 0; color: #757F95;"><a href="mailto:' . htmlspecialchars($email) . '" style="color: #c5b993; text-decoration: none;">' . htmlspecialchars($email) . '</a></p>';
$message .= '</div>';

$message .= '<div style="background: #f9f9f9; border-left: 4px solid #c5b993; padding: 15px; margin: 15px 0; border-radius: 4px;">';
$message .= '<strong style="color: #111111; display: block; margin-bottom: 5px;">Asunto:</strong>';
$message .= '<p style="margin: 0; color: #757F95;">' . htmlspecialchars($subject) . '</p>';
$message .= '</div>';

$message .= '<div style="background: #ffffff; border: 1px solid #e0e0e0; padding: 20px; margin: 20px 0; border-radius: 8px;">';
$message .= '<strong style="color: #111111; display: block; margin-bottom: 10px;">Mensaje:</strong>';
$message .= '<p style="color: #111111; line-height: 1.6; margin: 0; white-space: pre-wrap;">' . htmlspecialchars($contact_message) . '</p>';
$message .= '</div>';

$message .= '<div style="color: #757F95; font-size: 12px; border-top: 1px solid #e0e0e0; padding-top: 20px; margin-top: 20px;">';
$message .= '<p>Este mensaje fue enviado desde el formulario de contacto de <strong>GEF Automocion</strong>.<br>';
$message .= 'Responde directamente a <strong>' . htmlspecialchars($email) . '</strong> para contactar con el solicitante.</p>';
$message .= '</div>';

$message .= '<div style="background: #111111; color: white; text-align: center; padding: 20px; border-radius: 0 0 8px 8px; font-size: 12px;">';
$message .= '<p style="margin: 0;">&copy; 2025 GEF Automocion. Todos los derechos reservados.</p>';
$message .= '<p style="margin: 5px 0 0 0;"><a href="https://gefautomocion.com" style="color: #c5b993; text-decoration: none;">www.gefautomocion.com</a></p>';
$message .= '</div>';

$message .= '</div></div></body></html>';

$to = 'p405gl@gmail.com';
$headers = "MIME-Version: 1.0\r\n";
$headers .= "Content-type: text/html; charset=UTF-8\r\n";
$headers .= "From: info@sch-soluciones.com\r\n";
$headers .= "Reply-To: " . htmlspecialchars($email) . "\r\n";

$mail_send = mail($to, $subject, $message, $headers);

header('Content-Type: application/json');
if($mail_send) {
    echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente']);
} else {
    echo json_encode(['success' => false, 'message' => 'Error al enviar el mensaje']);
}

?>