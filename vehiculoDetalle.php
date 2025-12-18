<?php
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function precio_es($n){ return number_format((float)$n, 0, ',', '.') . ' €'; }
function km_es($n){ return number_format((int)$n, 0, ',', '.') . ' km'; }

$ident = isset($_GET['identificacion']) ? trim($_GET['identificacion']) : '';
$vehiculo = null;
$fotos = [];
$placeholder = 'assets/img/car/01.jpg';
$relacionados = [];

if ($ident !== '') {
    try {
        // Buscar vehículo por identificacion, excluyendo desactivados
        $stmt = $conn->prepare("SELECT * FROM vehiculos WHERE identificacion = ? AND estado <> 'desactivado' LIMIT 1");
        $stmt->bind_param('s', $ident);
        $stmt->execute();
        $vehiculo = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($vehiculo) {
            // Foto principal
            $principal = !empty($vehiculo['foto']) ? $vehiculo['foto'] : $placeholder;
            $fotos[] = $principal;

            // Fotos adicionales
            $stmt2 = $conn->prepare("SELECT ruta_foto FROM vehiculo_fotos WHERE vehiculo_id = ? ORDER BY id ASC");
            $stmt2->bind_param('i', $vehiculo['id']);
            $stmt2->execute();
            $rs = $stmt2->get_result();
            while ($row = $rs->fetch_assoc()) {
                if (!empty($row['ruta_foto'])) { $fotos[] = $row['ruta_foto']; }
            }
            $stmt2->close();

            // Quitar duplicados manteniendo orden
            $fotos = array_values(array_unique($fotos));

            // -------- Relacionados (misma marca o tipo_coche) --------
            $stmtR = $conn->prepare("
                SELECT id, identificacion, marca, modelo, precio, tipo_coche, kilometros, transmision, anio, tipo_combustible, foto
                FROM vehiculos
                WHERE estado <> 'desactivado'
                  AND id <> ?
                  AND (marca = ? OR tipo_coche = ?)
                ORDER BY id DESC
                LIMIT 8
            ");
            $stmtR->bind_param(
                'iss',
                $vehiculo['id'],
                $vehiculo['marca'],
                $vehiculo['tipo_coche']
            );
            $stmtR->execute();
            $relacionados = $stmtR->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmtR->close();

            // Si no hay relacionados, carga los últimos activos (fallback)
            if (!$relacionados) {
                $stmtRF = $conn->prepare("
                    SELECT id, identificacion, marca, modelo, precio, tipo_coche, kilometros, transmision, anio, tipo_combustible, foto
                    FROM vehiculos
                    WHERE estado <> 'desactivado' AND id <> ?
                    ORDER BY id DESC
                    LIMIT 8
                ");
                $stmtRF->bind_param('i', $vehiculo['id']);
                $stmtRF->execute();
                $relacionados = $stmtRF->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmtRF->close();
            }
        }
    } catch (Throwable $e) {
        error_log('vehiculoDetalle error: '.$e->getMessage());
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
    <link rel="stylesheet" href="assets/css/flex-slider.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .item-gallery .slides img { width: 100%; height: 480px; object-fit: cover; }
        .flex-control-thumbs img { height: 90px; object-fit: cover; }
        .car-single-title { display:flex; align-items:center; gap:12px; flex-wrap:wrap; }
        .price-badge {
            background:#222; color:#fff; padding:6px 12px; border-radius:20px;
            font-weight:600; font-size:1rem;
        }
        .badge-estado {
            display:inline-block; padding:6px 10px; border-radius:12px; color:#fff; font-weight:600; text-transform:capitalize;
        }
        .estado-activo { background: #2e7d32; }
        .estado-reservado { background: #ef6c00; }
        .estado-vendido { background: #c62828; }
        .estado-otro { background: #607d8b; }
        .gallery-wrap { background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 8px 24px rgba(0,0,0,.08); }
        /* Tarjeta relacionada: mantener estilo del tema y quitar botones extra */
        .car-item .car-img .car-btns { display:none; }
        .car-item .car-rate { display:none; }
    </style>
</head>
<body>

    <!-- preloader -->
    <div class="preloader">
        <div class="loader-ripple"><div></div><div></div></div>
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
                                <li><a href="mailto:gef.automocion@gmail.com"><i class="far fa-envelopes"></i> gef.automocion@gmail.com</a></li>
                                <li><a href="tel:++34645952869"><i class="far fa-phone-volume"></i> +34 645 952 869</a></li>
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
                        <img src="assets/img/logo/Icono_GEF_FondoBlanco_pequeno.svg" alt="logo">
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
                            <li class="nav-item"><a class="nav-link active" href="vehiculos">Vehículos</a></li>
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
                        <form action="vehiculos.php" method="get">
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
                    <img src="assets/img/logo/Icono_GEF_FondoBlanco_pequeno.svg" alt="">
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
                            <a href="mailto:gef.automocion@gmail.com"><i class="far fa-envelope"></i>gef.automocion@gmail.com</a>
                        </li>
                        <li>
                            <h6>Teléfono</h6>
                            <a href="tel:+21236547898"><i class="far fa-phone"></i>+34 645 952 869</a>
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
            </div>
        </div>
    </div>
    <!-- sidebar-popup end -->

    <main class="main">
        <!-- breadcrumb -->
        <div class="site-breadcrumb" style="background: url(assets/img/breadcrumb/01.jpg)">
            <div class="container">
                <h2 class="breadcrumb-title">Detalles del vehículo</h2>
            </div>
        </div>
        <!-- breadcrumb end -->

        <!-- car single -->
        <div class="car-item-single bg py-120">
            <div class="container">
                <?php if (!$vehiculo): ?>
                    <div class="alert alert-warning">
                        No se encontró el vehículo solicitado o está desactivado.
                        <a href="vehiculos.php" class="alert-link">Volver al listado</a>.
                    </div>
                <?php else: ?>
                <div class="car-single-wrapper">
                    <div class="row">
                        <div class="col-lg-8">
                            <div class="car-single-details">
                                <div class="car-single-widget">
                                    <div class="car-single-top">
                                        <?php
                                            $estado = strtolower($vehiculo['estado'] ?? '');
                                            $estadoClass = 'estado-otro';
                                            if ($estado === 'activo') $estadoClass = 'estado-activo';
                                            elseif ($estado === 'reservado') $estadoClass = 'estado-reservado';
                                            elseif ($estado === 'vendido') $estadoClass = 'estado-vendido';
                                        ?>
                                        <span class="badge-estado <?php echo h($estadoClass); ?>">
                                            <?php echo h(ucfirst($estado ?: '')); ?>
                                        </span>
                                        <h3 class="car-single-title">
                                            <?php echo h(($vehiculo['marca'] ?? '').' '.($vehiculo['modelo'] ?? '')); ?>
                                            <span class="price-badge"><?php echo precio_es($vehiculo['precio']); ?></span>
                                        </h3>
                                        <ul class="car-single-meta">
                                            <li><i class="far fa-id-card"></i> ID: <?php echo h($vehiculo['identificacion']); ?></li>
                                            <li><i class="far fa-road"></i> Kilómetros: <?php echo km_es($vehiculo['kilometros']); ?></li>
                                            <li><i class="far fa-calendar"></i> Año: <?php echo h($vehiculo['anio']); ?></li>
                                        </ul>
                                    </div>

                                    <!-- Galería -->
                                    <div class="car-single-slider gallery-wrap">
                                        <div class="item-gallery">
                                            <div class="flexslider-thumbnails">
                                                <ul class="slides">
                                                    <?php
                                                    if (empty($fotos)) { $fotos = [$placeholder]; }
                                                    foreach ($fotos as $src):
                                                        $srcSafe = h($src);
                                                    ?>
                                                    <li data-thumb="<?php echo $srcSafe; ?>">
                                                        <img src="<?php echo $srcSafe; ?>" alt="<?php echo h(($vehiculo['marca'] ?? '').' '.($vehiculo['modelo'] ?? '')); ?>">
                                                    </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Información clave -->
                                <div class="car-single-widget">
                                    <h4 class="mb-4">Información clave</h4>
                                    <div class="car-key-info">
                                        <div class="row">
                                            <div class="col-lg-3 col-md-4 col-6">
                                                <div class="car-key-item">
                                                    <div class="car-key-icon"><i class="flaticon-drive"></i></div>
                                                    <div class="car-key-content">
                                                        <span>Tipo carrocería</span>
                                                        <h6><?php echo h($vehiculo['tipo_coche']); ?></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-4 col-6">
                                                <div class="car-key-item">
                                                    <div class="car-key-icon"><i class="flaticon-speedometer"></i></div>
                                                    <div class="car-key-content">
                                                        <span>Kilómetros</span>
                                                        <h6><?php echo km_es($vehiculo['kilometros']); ?></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-4 col-6">
                                                <div class="car-key-item">
                                                    <div class="car-key-icon"><i class="flaticon-settings"></i></div>
                                                    <div class="car-key-content">
                                                        <span>Transmisión</span>
                                                        <h6><?php echo h($vehiculo['transmision']); ?></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-4 col-6">
                                                <div class="car-key-item">
                                                    <div class="car-key-icon"><i class="flaticon-drive"></i></div>
                                                    <div class="car-key-content">
                                                        <span>Año</span>
                                                        <h6><?php echo h($vehiculo['anio']); ?></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-4 col-6">
                                                <div class="car-key-item">
                                                    <div class="car-key-icon"><i class="flaticon-gas-station"></i></div>
                                                    <div class="car-key-content">
                                                        <span>Combustible</span>
                                                        <h6><?php echo h($vehiculo['tipo_combustible']); ?></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-4 col-6">
                                                <div class="car-key-item">
                                                    <div class="car-key-icon"><i class="flaticon-drive"></i></div>
                                                    <div class="car-key-content">
                                                        <span>Color</span>
                                                        <h6><?php echo h($vehiculo['color']); ?></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-4 col-6">
                                                <div class="car-key-item">
                                                    <div class="car-key-icon"><i class="flaticon-drive"></i></div>
                                                    <div class="car-key-content">
                                                        <span>Puertas</span>
                                                        <h6><?php echo h($vehiculo['numero_puertas']); ?></h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-4 col-6">
                                                <div class="car-key-item">
                                                    <div class="car-key-icon"><i class="flaticon-drive"></i></div>
                                                    <div class="car-key-content">
                                                        <span>Cilindrada</span>
                                                        <h6><?php echo number_format((int)$vehiculo['cilindrada'], 0, ',', '.'); ?> cc</h6>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-3 col-md-4 col-6">
                                                <div class="car-key-item">
                                                    <div class="car-key-icon"><i class="flaticon-drive"></i></div>
                                                    <div class="car-key-content">
                                                        <span>Potencia</span>
                                                        <h6><?php echo number_format((int)$vehiculo['cv'], 0, ',', '.'); ?> CV</h6>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Descripción (dinámica desde BBDD) -->
                                <div class="car-single-widget">
                                    <div class="car-single-overview">
                                        <h4 class="mb-3">Descripción</h4>
                                        <div class="mb-4">
                                            <?php
                                            $desc = trim((string)($vehiculo['descripcion'] ?? ''));
                                            if ($desc === '') {
                                                echo '<p>No hay descripción disponible para este vehículo.</p>';
                                            } else {
                                                echo '<p>'.nl2br(h($desc)).'</p>';
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Lateral -->
                        <div class="col-lg-4">
                            <div class="car-single-widget">
                                <h5 class="mb-3">Contactar</h5>
                                <div class="car-single-form">
                                    <form action="assets/php/contact.php"
      method="post"
      id="vehicle-contact-form">

    <!-- Honeypot -->
    <div style="display:none;">
        <input type="text" name="website" autocomplete="off">
    </div>

    <!-- Timestamp -->
    <input type="hidden" name="form_time" id="v_form_time">

    <!-- Verificación matemática -->
    <input type="hidden" name="math_a" id="v_math_a">
    <input type="hidden" name="math_b" id="v_math_b">

    <!-- Identificación del vehículo -->
    <input type="hidden"
           name="subject"
           value="Consulta vehículo <?php echo h($vehiculo['identificacion']); ?>">

    <div class="form-group">
        <input type="text"
               class="form-control"
               name="name"
               placeholder="Nombre"
               required>
    </div>

    <div class="form-group">
        <input type="email"
               class="form-control"
               name="email"
               placeholder="Email"
               required>
    </div>

    <div class="form-group">
        <textarea class="form-control"
                  name="message"
                  rows="3"
                  required>Hola, me interesa el <?php
echo h(($vehiculo['marca'] ?? '').' '.($vehiculo['modelo'] ?? ''));
?> (<?php echo h($vehiculo['identificacion']); ?>).</textarea>
    </div>

    <!-- Verificación visible -->
    <div class="form-group">
        <label id="v-math-label"
               style="font-weight:600;color:#c5b993;display:block;margin-bottom:6px;">
        </label>
        <input type="number"
               class="form-control"
               name="math_result"
               placeholder="Resultado"
               required>
    </div>

    <div class="form-group">
        <button type="submit" class="theme-btn">
            Enviar ahora <i class="fas fa-arrow-right-long"></i>
        </button>
    </div>
</form>
                                </div>
                            </div>

                            <div class="car-single-widget">
                                <h5 class="mb-3">Resumen</h5>
                                <ul class="list-group">
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Precio <span><?php echo precio_es($vehiculo['precio']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Kilómetros <span><?php echo km_es($vehiculo['kilometros']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Año <span><?php echo h($vehiculo['anio']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Combustible <span><?php echo h($vehiculo['tipo_combustible']); ?></span>
                                    </li>
                                    <li class="list-group-item d-flex justify-content-between align-items-center">
                                        Transmisión <span><?php echo h($vehiculo['transmision']); ?></span>
                                    </li>
                                </ul>
                                <a href="vehiculos.php" class="btn btn-outline-secondary w-100 mt-3">← Volver al listado</a>
                            </div>
                        </div>
                    </div>

                    <!-- Relacionados desde BBDD -->
                    <div class="car-single-related mt-5">
                        <h3 class="mb-30">Vehículos Relacionados</h3>
                        <div class="row">
                            <?php if ($relacionados): ?>
                                <?php foreach ($relacionados as $r):
                                     $img = !empty($r['foto']) ? $r['foto'] : $placeholder;
                                ?>
                                <div class="col-md-6 col-lg-4 col-xl-3">
                                    <div class="car-item">
                                        <div class="car-img">
                                            <img src="<?php echo h($img); ?>" alt="<?php echo h($r['marca'].' '.$r['modelo']); ?>">
                                        </div>
                                        <div class="car-content">
                                            <div class="car-top">
                                                <h4>
                                                    <a href="vehiculoDetalle?identificacion=<?php echo urlencode($r['identificacion']); ?>">
                                                        <?php echo h($r['marca'].' '.$r['modelo']); ?>
                                                    </a>
                                                </h4>
                                            </div>
                                            <ul class="car-list">
                                                <li><i class="far fa-steering-wheel"></i><?php echo h(ucfirst($r['transmision'])); ?></li>
                                                <li><i class="far fa-road"></i><?php echo km_es($r['kilometros']); ?></li>
                                                <li><i class="far fa-car"></i>Año: <?php echo h($r['anio']); ?></li>
                                                <li><i class="far fa-gas-pump"></i><?php echo h(ucfirst($r['tipo_combustible'])); ?></li>
                                            </ul>
                                            <div class="car-footer">
                                                <span class="car-price"><?php echo precio_es($r['precio']); ?></span>
                                                <a href="vehiculoDetalle?identificacion=<?php echo urlencode($r['identificacion']); ?>" class="theme-btn">
                                                    <span class="far fa-eye"></span>Detalles
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12">
                                    <div class="alert alert-info mb-0">No hay vehículos relacionados para mostrar ahora.</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- car single end -->
    </main>
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
    <script src="assets/js/flex-slider.js"></script>
    <script src="assets/js/main.js"></script>

    <script>
        // Iniciar Flexslider cuando las imágenes estén listas
        $(window).on('load', function(){
            if ($('.flexslider-thumbnails').length) {
                $('.flexslider-thumbnails').flexslider({
                    animation: "slide",
                    controlNav: "thumbnails",
                    slideshow: false,
                    smoothHeight: true
                });
            }
        });
    </script>
<script>
document.addEventListener('DOMContentLoaded', function () {

    const form = document.getElementById('vehicle-contact-form');
    if (!form) return;

    // Timestamp
    document.getElementById('v_form_time').value = Date.now();

    // Generar verificación matemática
    const a = Math.floor(Math.random() * 5) + 1;
    const b = Math.floor(Math.random() * 5) + 1;

    document.getElementById('v_math_a').value = a;
    document.getElementById('v_math_b').value = b;

    document.getElementById('v-math-label').textContent =
        `Verificación: ¿cuánto es ${a} + ${b}?`;

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const formData = new FormData(form);

        fetch(form.action, {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            if (!data.success) {
                alert(data.message);
                return;
            }

            alert('Mensaje enviado correctamente');
            form.reset();
        })
        .catch(() => {
            alert('Error al enviar el mensaje');
        });
    });
});
</script>
</body>
</html>