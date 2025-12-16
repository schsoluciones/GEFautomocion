<?php
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'];
    $newPassword = $_POST['new_password'];

    // Verificar si el token es válido y no ha expirado
    $stmt = $pdo->prepare("SELECT email FROM usuarios WHERE reset_token = ? AND reset_expiration > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch();

    if ($user) {
        // Actualizar la contraseña del usuario
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $pdo->prepare("UPDATE usuarios SET password = ?, reset_token = NULL, reset_expiration = NULL WHERE reset_token = ?");
        if ($updateStmt->execute([$hashedPassword, $token])) {
            $mensaje = "Tu contraseña ha sido restablecida con éxito.";
        } else {
            $error = "Hubo un error al actualizar tu contraseña. Por favor, inténtalo de nuevo.";
        }
    } else {
        $error = "El enlace de restablecimiento no es válido o ha expirado.";
    }
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <!-- meta tags -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="keywords" content="">

    <!-- title -->
    <title>GEF Automoción</title>

    <!-- favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/logo/favicon.ico">

    <!-- css -->
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/all-fontawesome.min.css">
    <link rel="stylesheet" href="assets/css/flaticon.css">
    <link rel="stylesheet" href="assets/css/animate.min.css">
    <link rel="stylesheet" href="assets/css/magnific-popup.min.css">
    <link rel="stylesheet" href="assets/css/owl.carousel.min.css">
    <link rel="stylesheet" href="assets/css/jquery-ui.min.css">
    <link rel="stylesheet" href="assets/css/nice-select.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
    <!-- preloader -->
    <div class="preloader">
        <div class="loader-ripple">
            <div></div>
            <div></div>
        </div>
    </div>
    <!-- preloader end -->

    <!-- header area -->
    <header class="header">
        <!-- top header -->
        <div class="header-top">
            <div class="container">
                <div class="header-top-wrapper">
                    <div class="header-top-left">
                        <div class="header-top-contact">
                            <ul>
                                <li><a href="mailto:gef.automocion@gmail.com"><i class="far fa-envelopes"></i>
                                        gef.automocion@gmail.com</a></li>
                                <li><a href="tel:+34645952869"><i class="far fa-phone-volume"></i> +34 645 952 869</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="header-top-right">
                        <div class="header-top-social">
                            <span>Síguenos: </span>
                            <a href="#"><i class="fab fa-facebook"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-instagram"></i></a>
                            <a href="#"><i class="fab fa-linkedin"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="main-navigation">
            <nav class="navbar navbar-expand-lg">
                <div class="container position-relative">
                    <a class="navbar-brand" href="index">
                        <img src="assets/img/logo/Icono_GEF_FondoBlanco_pequeño.svg" alt="logo">
                    </a>
                    <div class="mobile-menu-right">
                        <div class="search-btn">
                            <button type="button" class="nav-right-link"><i class="far fa-search"></i></button>
                        </div>
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                            data-bs-target="#main_nav" aria-expanded="false" aria-label="Toggle navigation">
                            <span class="navbar-toggler-mobile-icon"><i class="far fa-bars"></i></span>
                        </button>
                    </div>
                    <div class="collapse navbar-collapse" id="main_nav">
                        <ul class="navbar-nav">
                            <li class="nav-item"><a class="nav-link" href="index">Inicio</a></li>
                            <li class="nav-item"><a class="nav-link" href="nosotros">Sobre nosotros</a></li>
                            <li class="nav-item"><a class="nav-link" href="vehiculos">Vehículos</a></li>
                            <li class="nav-item"><a class="nav-link" href="contacto">Contacto</a></li>
                        </ul>

                        <div class="nav-right">
                            <div class="search-btn">
                                <button type="button" class="nav-right-link"><i class="far fa-search"></i></button>
                            </div>
                            <div class="sidebar-btn">
                                <button type="button" class="nav-right-link"><i class="far fa-bars-sort"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>
        </div>
    </header>
    <!-- header area end -->

    <main>
        <div class="container">
            <h2>Restablecer Contraseña</h2>
            <?php if (isset($mensaje)) { echo "<p style='color:green;'>$mensaje</p>"; } ?>
            <?php if (isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                <div class="mb-3">
                    <label for="new_password" class="form-label">Nueva Contraseña:</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary">Restablecer Contraseña</button>
            </form>
        </div>
    </main>

    <!-- Modal Financiación -->
    <div class="modal fade" id="financiacionModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 15px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);">
          <div class="modal-header" style="border-bottom: 2px solid #c5b993; background: linear-gradient(135deg, #f9f9f9 0%, #ffffff 100%); border-radius: 15px 15px 0 0;">
            <div class="d-flex align-items-center gap-3">
              <div style="font-size: 28px; color: #c5b993;"><i class="flaticon-money-transfer"></i></div>
              <h5 class="modal-title" style="color: #111111; font-weight: 700; font-size: 24px; margin: 0;">Financiación</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body" style="padding: 40px; text-align: center;">
            <p style="font-size: 18px; color: #757F95; line-height: 1.8; margin: 0;">Te ayudamos en la solicitud de la financiación con las mejores condiciones.</p>
          </div>
          <div class="modal-footer" style="border-top: none; padding: 20px 40px; background: #f9f9f9; border-radius: 0 0 15px 15px;">
            <button type="button" class="theme-btn" data-bs-dismiss="modal">Entendido<i class="fas fa-arrow-right-long"></i></button>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal Garantía -->
    <div class="modal fade" id="garantiaModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border: none; border-radius: 15px; box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);">
          <div class="modal-header" style="border-bottom: 2px solid #c5b993; background: linear-gradient(135deg, #f9f9f9 0%, #ffffff 100%); border-radius: 15px 15px 0 0;">
            <div class="d-flex align-items-center gap-3">
              <div style="font-size: 28px; color: #c5b993;"><i class="flaticon-shield"></i></div>
              <h5 class="modal-title" style="color: #111111; font-weight: 700; font-size: 24px; margin: 0;">Garantía</h5>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body" style="padding: 40px; text-align: center;">
            <p style="font-size: 18px; color: #757F95; line-height: 1.8; margin: 0;">Con garantía que hace que la compra sea lo más segura posible</p>
          </div>
          <div class="modal-footer" style="border-top: none; padding: 20px 40px; background: #f9f9f9; border-radius: 0 0 15px 15px;">
            <button type="button" class="theme-btn" data-bs-dismiss="modal">Entendido<i class="fas fa-arrow-right-long"></i></button>
          </div>
        </div>
      </div>
    </div>
        </div>


    <!-- footer area -->
    <footer class="footer-area">
        <div class="footer-widget">
            <div class="container">
                <div class="row footer-widget-wrapper pt-60 pb-40 align-items-start">
                    <!-- Logo + Texto -->
                    <div class="col-md-12 col-lg-3 text-center text-lg-start">
                        <div class="footer-widget-box about-us">
                            <a href="#" class="footer-logo d-block">
                                <img src="assets/img/logo/Icono_GEF_SinFondo_pequeno.svg" alt="GEF Automoción" style="max-width: 160px;">
                            </a>
                        </div>
                    </div>
                    
                    <!-- Contacto -->
                    <div class="col-md-6 col-lg-3 text-center text-lg-start">
                        <div class="footer-widget-box">
                            <h4 class="footer-widget-title mb-3">Contacto</h4>
                            <ul class="footer-contact">
                                <li class="mb-2"><a href="tel:+34645952869"><i class="far fa-phone"></i>+34 645 952 869</a></li>
                                <li class="mb-0"><a href="mailto:gef.automocion@gmail.com"><i class="far fa-envelope"></i>gef.automocion@gmail.com</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Enlaces Rápidos -->
                    <div class="col-md-6 col-lg-3 text-center text-lg-start">
                        <div class="footer-widget-box list">
                            <h4 class="footer-widget-title mb-3">Enlaces Rápidos</h4>
                            <ul class="footer-list">
                                <li><a href="nosotros"><i class="fas fa-caret-right"></i> Sobre Nosotros</a></li>
                                <li><a href="vehiculos"><i class="fas fa-caret-right"></i> Nuestros Coches</a></li>
                                <li><a href="contacto"><i class="fas fa-caret-right"></i> Contacto</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Servicios -->
                    <div class="col-md-12 col-lg-3 text-center text-lg-start">
                        <div class="footer-widget-box list">
                            <h4 class="footer-widget-title mb-3">Servicios</h4>
                            <ul class="footer-list">
                                <li><a href="#" data-bs-toggle="modal" data-bs-target="#financiacionModal"><i class="fas fa-caret-right"></i> Financiación</a></li>
                                <li><a href="#" data-bs-toggle="modal" data-bs-target="#garantiaModal"><i class="fas fa-caret-right"></i> Garantía</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="copyright">
            <div class="container">
                <div class="row">
                    <div class="col-md-6 align-self-center">
                        <p class="copyright-text">
                            &copy; Copyright <span id="date"></span> <a href="#"> GEF Automoción </a> Todos los derechos reservados.
                        </p>
                    </div>
                    <div class="col-md-6 align-self-center">
                        <ul class="footer-social">
                            <li><a href="#"><i class="fab fa-facebook-f"></i></a></li>
                            <li><a href="#"><i class="fab fa-twitter"></i></a></li>
                            <li><a href="#"><i class="fab fa-linkedin-in"></i></a></li>
                            <li><a href="#"><i class="fab fa-youtube"></i></a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- footer area end -->




    <!-- scroll-top -->
    <a href="#" id="scroll-top"><i class="far fa-arrow-up"></i></a>
    <!-- scroll-top end -->


    <!-- js -->
    <script src="assets/js/jquery-3.6.0.min.js"></script>
    <script src="assets/js/modernizr.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/imagesloaded.pkgd.min.js"></script>
    <script src="assets/js/jquery.magnific-popup.min.js"></script>
    <script src="assets/js/isotope.pkgd.min.js"></script>
    <script src="assets/js/jquery.appear.min.js"></script>
    <script src="assets/js/jquery.easing.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/counter-up.js"></script>
    <script src="assets/js/jquery-ui.min.js"></script>
    <script src="assets/js/jquery.nice-select.min.js"></script>
    <script src="assets/js/wow.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js"></script>
    <script>
    (function () {
      const root = document.documentElement;
    
      function setHeaderHeight() {
        const header = document.querySelector('.header');
        const h = header ? Math.ceil(header.getBoundingClientRect().height) : 0;
        root.style.setProperty('--header-h', h + 'px');
      }
    
      // Recalcular al cargar, redimensionar y cuando se abre/cierra el menú mobile
      window.addEventListener('load', setHeaderHeight);
      window.addEventListener('resize', setHeaderHeight);
    
      // Eventos de Bootstrap collapse para el menú
      document.addEventListener('shown.bs.collapse', setHeaderHeight);
      document.addEventListener('hidden.bs.collapse', setHeaderHeight);
    
      // Si cambia el header por cualquier motivo (sticky, logos, etc.)
      const header = document.querySelector('.header');
      if ('ResizeObserver' in window && header) {
        const ro = new ResizeObserver(setHeaderHeight);
        ro.observe(header);
      }
    
      // Primera llamada inmediata
      setHeaderHeight();
    })();
    </script>
    <script>
      // Autoplay al abrir y limpiar al cerrar
      document.addEventListener('DOMContentLoaded', function () {
        const modalEl = document.getElementById('videoModal');
        const iframe = document.getElementById('videoFrame');
    
        // Cuando se va a abrir la modal
        modalEl.addEventListener('show.bs.modal', function (event) {
          const trigger = event.relatedTarget; // el botón que abrió la modal
          if (!trigger) return;
        
          const videoId = trigger.getAttribute('data-video-id');
          const params = new URLSearchParams({
            autoplay: 1,
            rel: 0,
            modestbranding: 1,
            playsinline: 1
          });
          iframe.src = `https://www.youtube.com/embed/${videoId}?${params.toString()}`;
        });
    
        // Al cerrar la modal, limpia el src para detener la reproducción
        modalEl.addEventListener('hidden.bs.modal', function () {
          iframe.src = '';
        });
      });
    </script>

</body>

</html>