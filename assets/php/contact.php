$message = '
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
</head>
<body style="margin:0; padding:0; background-color:#0f0f0f; font-family: Arial, Helvetica, sans-serif;">
  <table width="100%" cellpadding="0" cellspacing="0" style="padding:30px 15px;">
    <tr>
      <td align="center">
        <table width="600" cellpadding="0" cellspacing="0" style="background-color:#111111; border-radius:12px; overflow:hidden; box-shadow:0 10px 40px rgba(0,0,0,0.4);">

          <!-- LOGO -->
          <tr>
            <td style="text-align:center; padding:30px;">
              <img src="https://gef-automocion.com/assets/img/logo/Icono_GEF_SinFondo_pequeno.svg"
                   alt="GEF Automoción"
                   style="max-width:160px;">
            </td>
          </tr>

          <!-- TITULO -->
          <tr>
            <td style="padding:0 40px 20px 40px; text-align:center;">
              <h2 style="margin:0; color:#c5b993; font-size:24px; letter-spacing:1px;">
                NUEVA SOLICITUD DE CONTACTO
              </h2>
            </td>
          </tr>

          <!-- CONTENIDO -->
          <tr>
            <td style="padding:30px 40px; color:#e0e0e0; font-size:14px;">
              <p style="margin-top:0; color:#bdbdbd;">
                Has recibido un nuevo mensaje desde el formulario web de
                <strong style="color:#ffffff;">GEF Automoción</strong>.
              </p>

              <table width="100%" cellpadding="0" cellspacing="0" style="margin-top:25px;">
                <tr>
                  <td style="padding:10px 0; color:#c5b993; width:120px;"><strong>Nombre</strong></td>
                  <td style="padding:10px 0;">'.$name.'</td>
                </tr>
                <tr>
                  <td style="padding:10px 0; color:#c5b993;"><strong>Email</strong></td>
                  <td style="padding:10px 0;">'.$email.'</td>
                </tr>
                <tr>
                  <td style="padding:10px 0; color:#c5b993;"><strong>Asunto</strong></td>
                  <td style="padding:10px 0;">'.$subject.'</td>
                </tr>
              </table>

              <div style="margin:30px 0; height:1px; background-color:#2a2a2a;"></div>

              <p style="margin-bottom:10px; color:#c5b993;"><strong>Mensaje</strong></p>
              <div style="background-color:#0f0f0f; padding:20px; border-radius:8px; color:#e0e0e0; line-height:1.6;">
                '.nl2br(htmlspecialchars($contact_message)).'
              </div>
            </td>
          </tr>

          <!-- FOOTER -->
          <tr>
            <td style="background-color:#0f0f0f; padding:25px; text-align:center; font-size:12px; color:#777;">
              📍 Avilés · 📞 +34 645 952 869 · ✉️ gef.automocion@gmail.com<br><br>
              © '.date('Y').' <strong style="color:#c5b993;">GEF Automoción</strong><br>
              Importación de vehículos premium
            </td>
          </tr>

        </table>
      </td>
    </tr>
  </table>
</body>
</html>
';
