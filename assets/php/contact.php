<?php 

 //contact form info
 $name=  $_POST['name'];
 $email=  $_POST['email'];
 $subject = $_POST['subject'];
 $contact_message = $_POST['message'];


 $message = "<html><head><style>body{font-family:Arial,sans-serif;background:#f5f5f5;margin:0;padding:20px}table{width:100%;max-width:600px;margin:0 auto;background:#fff;border-radius:8px;overflow:hidden}td{padding:20px}.header{background:#c5b993;color:#fff;text-align:center;padding:30px 20px}.title{font-size:24px;font-weight:bold;margin:0}.info{background:#f9f9f9;border-left:4px solid #c5b993;padding:15px;margin:10px 0}.label{font-weight:bold;color:#111}.value{color:#757F95;margin:5px 0}.msg-box{background:#fff;border:1px solid #e0e0e0;padding:15px;margin:15px 0}.footer{background:#111;color:#fff;text-align:center;font-size:12px;padding:15px}</style></head><body>";
 $message .= "<table><tr><td class='header'><p class='title'>Nueva Solicitud de Contacto</p></td></tr>";
 $message .= "<tr><td>";
 
 $message .= "<div class='info'>";
 $message .= "<div class='label'>Nombre:</div>";
 $message .= "<div class='value'>".$name."</div>";
 $message .= "</div>";
 
 $message .= "<div class='info'>";
 $message .= "<div class='label'>Email:</div>";
 $message .= "<div class='value'>".$email."</div>";
 $message .= "</div>";
 
 $message .= "<div class='info'>";
 $message .= "<div class='label'>Asunto:</div>";
 $message .= "<div class='value'>".$subject."</div>";
 $message .= "</div>";
 
 $message .= "<div class='msg-box'>";
 $message .= "<div class='label' style='margin-bottom:10px'>Mensaje:</div>";
 $message .= "<div class='value' style='white-space:pre-wrap'>".$contact_message."</div>";
 $message .= "</div>";
 
 $message .= "</td></tr><tr><td class='footer'>";
 $message .= "<p style='margin:0'>&copy; 2025 GEF Automocion</p>";
 $message .= "</td></tr></table></body></html>";
            
            
 $to = "p405gl@gmail.com";

 $header = "From:info@sch-soluciones.com \r\n";
 $header .= "MIME-Version: 1.0\r\n";
 $header .= "Content-type: text/html\r\n";
 
 $mail_send = mail ($to,$subject,$message,$header);
 
 header('Content-Type: application/json');
 echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente']);

?>