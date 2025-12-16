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
                                <li><a href="mailto:info@gefautomocion.com"><i class="far fa-envelopes"></i>
                                        info@gefautomocion.com</a></li>
                                <li><a href="tel:+34123456789"><i class="far fa-phone-volume"></i> +34 123 456 789</a>
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

    <!-- footer area -->
    <footer class="footer">
        <div class="container">
            <div class="footer-top">
                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-widget">
                            <h4 class="footer-title">Sobre nosotros</h4>
                            <div class="footer-about">
                                <p>Información sobre la empresa.</p>
                                <a href="nosotros" class="footer-link">Leer más</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-widget">
                            <h4 class="footer-title">Servicios</h4>
                            <ul class="footer-services-list">
                                <li><a href="vehiculos" class="footer-services-link">Venta de vehículos</a></li>
                                <li><a href="financiacion" class="footer-services-link">Financiación</a></li>
                                <li><a href="seguros" class="footer-services-link">Seguros</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="footer-widget">
                            <h4 class="footer-title">Contacto</h4>
                            <div class="footer-contact">
                                <p><i class="far fa-map-marker-alt"></i> Dirección de la empresa</p>
                                <p><i class="far fa-envelope"></i> <a href="mailto:info@gefautomocion.com">info@gefautomocion.com</a></p>
                                <p><i class="far fa-phone"></i> <a href="tel:+34123456789">+34 123 456 789</a></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <div class="row">
                    <div class="col-md-6">
                        <div class="footer-copyright">
                            <p>&copy; 2023 GEF Automoción. Todos los derechos reservados.</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="footer-menu">
                            <ul>
                                <li><a href="index">Inicio</a></li>
                                <li><a href="nosotros">Sobre nosotros</a></li>
                                <li><a href="vehiculos">Vehículos</a></li>
                                <li><a href="contacto">Contacto</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>
    <!-- footer area end -->

    <!-- js -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/owl.carousel.min.js"></script>
    <script src="assets/js/jquery.magnific-popup.min.js"></script>
    <script src="assets/js/jquery-ui.min.js"></script>
    <script src="assets/js/nice-select.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>

</html>
