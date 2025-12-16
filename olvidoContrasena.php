<?php
error_log("Correo proporcionado: $email");
session_start();
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    // Validar el correo electrónico
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        error_log("Correo electrónico válido: $email");

        // Generar un token único para el restablecimiento de contraseña
        $token = bin2hex(random_bytes(16));
        $expiration = date('Y-m-d H:i:s', strtotime('+1 hour'));
        error_log("Token generado: $token");

        // Guardar el token en la base de datos
        $stmt = $pdo->prepare("UPDATE usuarios SET reset_token = ?, reset_expiration = ? WHERE email = ?");
        if ($stmt->execute([$token, $expiration, $email])) {
            error_log("Token guardado en la base de datos para: $email");

            // Enviar el correo electrónico con el enlace de restablecimiento
            $resetLink = "https://40890533.servicio-online.net/restablecer.php?token=$token";
            $subject = "Restablecimiento de Contraseña";
            $message = "Hola,\n\nHemos recibido una solicitud para restablecer tu contraseña. Haz clic en el siguiente enlace para restablecerla:\n$resetLink\n\nSi no solicitaste este cambio, ignora este correo.";
            $headers = "From: no-reply@gefautomocion.com";

            if (mail($email, $subject, $message, $headers)) {
                error_log("Correo enviado a: $email");
                $mensaje = "Se ha enviado un enlace de restablecimiento a tu correo electrónico.";
            } else {
                error_log("Error al enviar el correo a: $email");
                $error = "Hubo un error al enviar el correo. Por favor, inténtalo de nuevo más tarde.";
            }
        } else {
            error_log("Error al guardar el token en la base de datos para: $email");
            $error = "Hubo un error al procesar tu solicitud. Por favor, inténtalo de nuevo más tarde.";
        }
    } else {
        error_log("Correo electrónico inválido: $email");
        $error = "Por favor, introduce un correo electrónico válido.";
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
                                <li><a href="mailto:info@gefautomocion.com"><i class="far fa-envelopes"></i>
                                        info@gefautomocion.com</a></li>
                                <li><a href="tel:+34123456789"><i class="far fa-phone-volume"></i> +34 123 456 789</a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    <div class="header-top-right">
                        <!-- <div class="header-top-link">
                            <a href="#"><i class="far fa-arrow-right-to-arc"></i> Login</a>
                            <a href="#"><i class="far fa-user-vneck"></i> Register</a>
                        </div> -->
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
                    <!-- search area -->
                    <div class="search-area">
                        <form action="#">
                            <div class="form-group">
                                <input type="text" class="form-control" placeholder="Type Keyword...">
                                <button type="submit" class="search-icon-btn"><i class="far fa-search"></i></button>
                            </div>
                        </form>
                    </div>
                    <!-- search area end -->
                </div>
            </nav>
        </div>
    </header>
    <!-- header area end -->


    <!-- sidebar-popup -->
    <div class="sidebar-popup">
        <div class="sidebar-wrapper">
            <div class="sidebar-content">
                <button type="button" class="close-sidebar-popup"><i class="far fa-xmark"></i></button>
                <div class="sidebar-logo">
                    <img src="assets/img/logo/Icono_GEF_FondoBlanco_pequeño.svg" alt="">
                </div>
                <div class="sidebar-about">
                    <h4>Sobre nosotros</h4>
                    <p>🚗✨ Tu coche soñado, directo a tus manos ✨🚗
                    En GEF Automoción importamos vehículos con la mejor calidad y al mejor precio. ¿Buscas algo exclusivo? Lo traemos para ti, tal y como lo imaginas. Nos encargamos de todo, para que tú solo disfrutes de tu nuevo coche.</p>
                </div>
                <div class="sidebar-contact">
                    <h4>Información de contacto</h4>
                    <ul>
                        <li>
                            <h6>Email</h6>
                            <a href="mailto:info@gefautomocion.com"><i class="far fa-envelope"></i>info@gefautomocion.com</a>
                        </li>
                        <li>
                            <h6>Teléfono</h6>
                            <a href="tel:+21236547898"><i class="far fa-phone"></i>+34 123 456 789</a>
                        </li>
                    </ul>
                </div>
                <div class="sidebar-social">
                    <h4>Síguenos</h4>
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-linkedin"></i></a>
                </div>
                <div class="sidebar-private-menu">
                    <a href="login.php" class="theme-btn">Acceder al Menú Privado</a>
                </div>
            </div>
        </div>
    </div>
    <!-- sidebar-popup end -->


    <main class="main">

        <!-- breadcrumb -->
        <div class="site-breadcrumb" style="background: url(assets/img/breadcrumb/01.jpg)">
            <div class="container">
                <h2 class="breadcrumb-title">Acceso privado</h2>
            </div>
        </div>
        <!-- breadcrumb end -->


        <!-- forgot password -->
        <div class="login-area py-120">
            <div class="container">
                <div class="col-md-5 mx-auto">
                    <div class="login-form">
                        <div class="login-header">
                            <img src="assets/img/logo/Icono_GEF_FondoBlanco_pequeño.svg" alt="">
                            <p>Resetea tu contraseña</p>
                        </div>
                        <form method="POST" action="">
                            <div class="form-group">
                                <label for="email">Correo Electrónico:</label>
                                <input type="email" class="form-control" placeholder="Tu Correo Electrónico" id="email" name="email" required>
                            </div>
                            <div class="d-flex align-items-center">
                                <button type="submit" class="theme-btn"><i class="far fa-key"></i> Enviar Enlace de
                                    Restablecimiento</button>
                            </div>
                        </form>
                        <?php if (isset($mensaje)) { echo "<p style='color:green;'>$mensaje</p>"; } ?>
                        <?php if (isset($error)) { echo "<p style='color:red;'>$error</p>"; } ?>
                    </div>
                </div>
            </div>
        </div>
        <!-- forgot password end -->


    </main>



    <!-- footer area -->
    <footer class="footer-area">
        <div class="footer-widget">
            <div class="container">
                <div class="row footer-widget-wrapper pt-60 pb-40 align-items-start">
                    <!-- Logo + Texto -->
                    <div class="col-md-12 col-lg-3 text-center text-lg-start">
                        <div class="footer-widget-box about-us">
                            <a href="#" class="footer-logo d-block">
                                <img src="assets/img/logo/Icono_GEF_SinFondo_pequeño.svg" alt="GEF Automoción" style="max-width: 160px;">
                            </a>
                        </div>
                    </div>
                    
                    <!-- Contacto -->
                    <div class="col-md-6 col-lg-3 text-center text-lg-start">
                        <div class="footer-widget-box">
                            <h4 class="footer-widget-title mb-3">Contacto</h4>
                            <ul class="footer-contact">
                                <li class="mb-2"><a href="tel:+34985123456"><i class="far fa-phone"></i>+34 985 123 456</a></li>
                                <li class="mb-0"><a href="mailto:info@gefautomocion.com"><i class="far fa-envelope"></i>info@gefautomocion.com</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Enlaces Rápidos -->
                    <div class="col-md-6 col-lg-3 text-center text-lg-start">
                        <div class="footer-widget-box list">
                            <h4 class="footer-widget-title mb-3">Enlaces Rápidos</h4>
                            <ul class="footer-list">
                                <li><a href="#"><i class="fas fa-caret-right"></i> Sobre Nosotros</a></li>
                                <li><a href="#"><i class="fas fa-caret-right"></i> Nuestros Coches</a></li>
                                <li><a href="#"><i class="fas fa-caret-right"></i> Testimonios</a></li>
                                <li><a href="#"><i class="fas fa-caret-right"></i> Contacto</a></li>
                            </ul>
                        </div>
                    </div>
                    
                    <!-- Servicios -->
                    <div class="col-md-12 col-lg-3 text-center text-lg-start">
                        <div class="footer-widget-box list">
                            <h4 class="footer-widget-title mb-3">Servicios</h4>
                            <ul class="footer-list">
                                <li><a href="#"><i class="fas fa-caret-right"></i> Importación</a></li>
                                <li><a href="#"><i class="fas fa-caret-right"></i> Venta de Coches</a></li>
                                <li><a href="#"><i class="fas fa-caret-right"></i> Financiación</a></li>
                                <li><a href="#"><i class="fas fa-caret-right"></i> Garantía</a></li>
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

</body>

</html>