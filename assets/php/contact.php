<?php 

 //contact form info
 $name=  $_POST['name'];
 $email=  $_POST['email'];
 $subject = $_POST['subject'];
 $contact_message = $_POST['message'];


 $message = "<html><body style='font-family: Arial, sans-serif; background-color: #f5f5f5; margin: 0; padding: 20px;'>";
 $message .= "<div style='background-color: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.1); max-width: 600px; margin: 0 auto;'>";
 
 $message .= "<div style='background: linear-gradient(135deg, #c5b993 0%, #a89367 100%); color: white; padding: 30px; text-align: center;'>";
 $message .= "<h1 style='margin: 0; font-size: 28px; font-weight: 700;'>Nueva Solicitud de Contacto</h1>";
 $message .= "</div>";
 
 $message .= "<div style='padding: 30px;'>";
 
 $message .= "<div style='background: #f9f9f9; border-left: 4px solid #c5b993; padding: 15px; margin: 15px 0; border-radius: 4px;'>";
 $message .= "<strong style='color: #111111; display: block; margin-bottom: 5px;'>Nombre:</strong>";
 $message .= "<p style='margin: 0; color: #757F95;'>".$name."</p>";
 $message .= "</div>";
 
 $message .= "<div style='background: #f9f9f9; border-left: 4px solid #c5b993; padding: 15px; margin: 15px 0; border-radius: 4px;'>";
 $message .= "<strong style='color: #111111; display: block; margin-bottom: 5px;'>Email:</strong>";
 $message .= "<p style='margin: 0; color: #757F95;'>".$email."</p>";
 $message .= "</div>";
 
 $message .= "<div style='background: #ffffff; border: 1px solid #e0e0e0; padding: 20px; margin: 20px 0; border-radius: 8px;'>";
 $message .= "<strong style='color: #111111; display: block; margin-bottom: 10px;'>Mensaje:</strong>";
 $message .= "<p style='color: #111111; line-height: 1.6; margin: 0;'>".$contact_message."</p>";
 $message .= "</div>";
 
 $message .= "<div style='color: #757F95; font-size: 12px; border-top: 1px solid #e0e0e0; padding-top: 15px; margin-top: 15px;'>";
 $message .= "<p style='margin: 0;'>Responde directamente a <strong>".$email."</strong> para contactar con el solicitante.</p>";
 $message .= "</div>";
 
 $message .= "</div>";
 
 $message .= "<div style='background: #111111; color: white; text-align: center; padding: 20px; font-size: 12px;'>";
 $message .= "<p style='margin: 0;'>&copy; 2025 GEF Automocion. Todos los derechos reservados.</p>";
 $message .= "</div>";
 
 $message .= "</div></body></html>";
            
            
 $to = "p405gl@gmail.com";

 $header = "From:info@sch-soluciones.com \r\n";
 $header .= "MIME-Version: 1.0\r\n";
 $header .= "Content-type: text/html\r\n";
 
 $mail_send = mail ($to,$subject,$message,$header);
 
 header('Content-Type: application/json');
 echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente']);

?>