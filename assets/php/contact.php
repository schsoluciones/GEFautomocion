<?php 

 //contact form info
 $name=  $_POST['name'];
 $email=  $_POST['email'];
 $subject = $_POST['subject'];
 $contact_message = $_POST['message'];


 $message = "<b>Solicitud de información:</b><br>";
 $message .= "<b>Nombre:</b> ".$name."<br>";
 $message .= "<b>Email:</b> ".$email."<br>";
 $message .= "<b>Asunto:</b> ".$subject."<br><br>";
 $message .= "<b>Mensaje:</b><br>";
 $message .= $contact_message;
            
            
 $to = "p405gl@gmail.com";

 $header = "From:info@sch-soluciones.com \r\n";
 $header .= "MIME-Version: 1.0\r\n";
 $header .= "Content-type: text/html\r\n";
 
 $mail_send = mail ($to,$subject,$message,$header);
 
 header('Content-Type: application/json');
 echo json_encode(['success' => true, 'message' => 'Mensaje enviado correctamente']);

?>