<?php 

 //contact form info
 $name=  $_POST['name'];
 $email=  $_POST['email'];
 $subject = $_POST['subject'];
 $contact_message = $_POST['message'];

 // HTML template for modern email
 $message = "
 <!DOCTYPE html>
 <html lang='es'>
 <head>
     <meta charset='UTF-8'>
     <style>
         body { font-family: 'Montserrat', Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
         .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
         .header { background: linear-gradient(135deg, #c5b993 0%, #a89367 100%); color: white; padding: 30px 20px; text-align: center; }
         .header h1 { margin: 0; font-size: 28px; font-weight: 700; }
         .content { padding: 30px 20px; }
         .info-box { background: #f9f9f9; border-left: 4px solid #c5b993; padding: 15px; margin: 15px 0; border-radius: 4px; }
         .info-box strong { color: #111111; display: block; margin-bottom: 5px; }
         .info-box p { margin: 0; color: #757F95; }
         .message-box { background: #ffffff; border: 1px solid #e0e0e0; padding: 20px; margin: 20px 0; border-radius: 8px; }
         .message-box p { color: #111111; line-height: 1.6; margin: 0; }
         .footer { background: #111111; color: white; text-align: center; padding: 20px; font-size: 12px; }
         .footer a { color: #c5b993; text-decoration: none; }
     </style>
 </head>
 <body>
     <div class='container'>
         <div class='header'>
             <h1>🚗 Nueva Solicitud de Contacto</h1>
         </div>
         <div class='content'>
             <p style='color: #757F95; font-size: 14px;'>Has recibido un nuevo mensaje a través del formulario de contacto de GEF Automoción:</p>
             
             <div class='info-box'>
                 <strong>👤 Nombre del solicitante:</strong>
                 <p>".htmlspecialchars($name)."</p>
             </div>
             
             <div class='info-box'>
                 <strong>📧 Email de contacto:</strong>
                 <p><a href='mailto:".htmlspecialchars($email)."' style='color: #c5b993; text-decoration: none;'>".htmlspecialchars($email)."</a></p>
             </div>
             
             <div class='info-box'>
                 <strong>📝 Asunto:</strong>
                 <p>".htmlspecialchars($subject)."</p>
             </div>
             
             <div class='message-box'>
                 <strong style='color: #111111; display: block; margin-bottom: 10px;'>💬 Mensaje:</strong>
                 <p>".nl2br(htmlspecialchars($contact_message))."</p>
             </div>
             
             <p style='color: #757F95; font-size: 12px; border-top: 1px solid #e0e0e0; padding-top: 20px; margin-top: 20px;'>
                 Este mensaje fue enviado desde el formulario de contacto de <strong>GEF Automoción</strong>.<br>
                 Responde directamente a <strong>".htmlspecialchars($email)."</strong> para contactar con el solicitante.
             </p>
         </div>
         <div class='footer'>
             <p>&copy; 2025 GEF Automoción. Todos los derechos reservados.</p>
             <p><a href='https://gefautomocion.com'>www.gefautomocion.com</a></p>
         </div>
     </div>
 </body>
 </html>
 ";
            
 $to = "p405gl@gmail.com";
 $header = "From:info@sch-soluciones.com \r\n";
 $header .= "MIME-Version: 1.0\r\n";
 $header .= "Content-type: text/html; charset=UTF-8\r\n";
 
 $mail_send = mail($to, $subject, $message, $header);
 
 // Return JSON response for AJAX
 header('Content-Type: application/json');
 if($mail_send) {
     echo json_encode(['success' => true, 'message' => '✅ Mensaje enviado correctamente']);
 } else {
     echo json_encode(['success' => false, 'message' => '❌ Error al enviar el mensaje']);
 }
 
?>