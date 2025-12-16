<?php
if (mail('tu_correo@ejemplo.com', 'Prueba de correo', 'Este es un correo de prueba.')) {
    echo 'Correo enviado correctamente.';
} else {
    echo 'Error al enviar el correo.';
}
?>