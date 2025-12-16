<?php
session_start();
require_once 'config.php';

// Mostrar errores (útil mientras afinamos). Puedes desactivar en producción.
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Requiere login
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.html');
    exit;
}

// MySQLi estricto
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Helper de salida segura
function h($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$mensaje = '';

// ===================== SUBIR FOTO DE USUARIO (AJAX) =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subir_foto_usuario'])) {
    header('Content-Type: application/json; charset=utf-8');
    $resp = ['ok' => false];

    try {
        if (empty($_SESSION['loggedin']) || empty($_SESSION['username'])) {
            throw new Exception('Sesión no válida.');
        }
        if (!isset($_FILES['foto_usuario']) || $_FILES['foto_usuario']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Archivo no recibido o con error.');
        }

        // Validaciones básicas
        $tmp  = $_FILES['foto_usuario']['tmp_name'];
        $size = $_FILES['foto_usuario']['size'];
        if ($size > 5 * 1024 * 1024) { // 5 MB
            throw new Exception('La imagen supera el límite de 5 MB.');
        }

        $info = @getimagesize($tmp);
        if ($info === false) {
            throw new Exception('El archivo no es una imagen válida.');
        }
        $mime = $info['mime'];
        $ext  = '';
        switch ($mime) {
            case 'image/jpeg': $ext = '.jpg';  break;
            case 'image/png':  $ext = '.png';  break;
            case 'image/webp': $ext = '.webp'; break;
            case 'image/gif':  $ext = '.gif';  break;
            default: throw new Exception('Formato no permitido (usa JPG, PNG, WEBP o GIF).');
        }

        // Destino
        $dirAbs = __DIR__ . '/assets/img/account';
        if (!is_dir($dirAbs)) { mkdir($dirAbs, 0777, true); }

        $safeUser = preg_replace('/[^a-zA-Z0-9_-]/', '_', $_SESSION['username']);
        $fileName = 'u_' . $safeUser . '_' . bin2hex(random_bytes(6)) . $ext;
        $destAbs  = $dirAbs . '/' . $fileName;
        $destRel  = 'assets/img/account/' . $fileName;

        if (!is_uploaded_file($tmp) || !move_uploaded_file($tmp, $destAbs)) {
            throw new Exception('No se pudo guardar el archivo.');
        }

        // (Opcional) eliminar la imagen anterior si era del mismo directorio y no la de defecto
        if (!empty($_SESSION['foto'])
            && strpos($_SESSION['foto'], 'assets/img/account/') === 0
            && basename($_SESSION['foto']) !== 'user.jpg') {
            @unlink(__DIR__ . '/' . $_SESSION['foto']);
        }

        // Guardar en BD
        $stmt = $conn->prepare('UPDATE usuarios SET foto = ? WHERE username = ?');
        $stmt->bind_param('ss', $destRel, $_SESSION['username']);
        $stmt->execute();
        $stmt->close();

        // Actualizar sesión
        $_SESSION['foto'] = $destRel;

        $resp = ['ok' => true, 'path' => $destRel];
    } catch (Throwable $e) {
        $resp['error'] = $e->getMessage();
        error_log('Subir foto usuario: ' . $e->getMessage());
    }

    echo json_encode($resp);
    exit; // Imprescindible para no continuar con el resto del flujo
}

// ===================== ALTA DE VEHÍCULO =====================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['eliminar_id']) && !isset($_POST['editar_id'])) {

    // Recoger campos
    $marca            = $_POST['marca'] ?? '';
    $modelo           = $_POST['modelo'] ?? '';
    $precio           = (int)($_POST['precio'] ?? 0);
    $tipo_coche       = $_POST['tipo_coche'] ?? '';
    $kilometros       = (int)($_POST['kilometros'] ?? 0);
    $transmision      = $_POST['transmision'] ?? '';
    $anio             = (int)($_POST['anio'] ?? 0);
    $tipo_combustible = $_POST['tipo_combustible'] ?? '';
    $color            = $_POST['color'] ?? '';
    $numero_puertas   = $_POST['numero_puertas'] ?? '';
    $cilindrada       = (int)($_POST['cilindrada'] ?? 0);
    $cv               = (int)($_POST['cv'] ?? 0);
    $descripcion      = $_POST['descripcion'] ?? '';

    // Forzar estado por defecto
    $estado = 'activo';

    // Validar transmisión
    $valores_transmision = ['automatica', 'manual'];
    if (!in_array($transmision, $valores_transmision, true)) {
        $mensaje = 'Error: Valor inválido para el campo transmisión.';
    }

    // Generar código de identificación único-ish
    $identificacion = strtoupper(substr($marca, 0, 2) . substr($modelo, 0, 2) . str_pad((string)rand(0, 9999), 4, '0', STR_PAD_LEFT));

    // Subida de foto principal
    $foto = null;
    if (isset($_FILES['foto']) && isset($_FILES['foto']['error']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        if (!is_dir('assets/img/uploads')) {
            mkdir('assets/img/uploads', 0777, true);
        }
        $nombreArchivo = basename($_FILES['foto']['name']);
        $rutaDestino   = 'assets/img/uploads/' . uniqid('', true) . '_' . $nombreArchivo;

        if (is_uploaded_file($_FILES['foto']['tmp_name']) && move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)) {
            $foto = $rutaDestino;
        }
    }

    // Si no hubo error de validación, insertar
    if ($mensaje === '') {
        try {
            // INSERT con descripcion
            $sql = 'INSERT INTO vehiculos (
                        identificacion, marca, modelo, precio, tipo_coche, kilometros, transmision, anio,
                        tipo_combustible, color, numero_puertas, cilindrada, cv, estado, foto, descripcion
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)';

            $stmt = $conn->prepare($sql);
            // Tipos: s,s,s,i,s,i,s,i,s,s,s,i,i,s,s,s => 'sssisisisssiisss'
            $stmt->bind_param(
                'sssisisisssiisss',
                $identificacion, $marca, $modelo, $precio, $tipo_coche, $kilometros,
                $transmision, $anio, $tipo_combustible, $color, $numero_puertas,
                $cilindrada, $cv, $estado, $foto, $descripcion
            );
            $stmt->execute();
            $vehiculo_id = $stmt->insert_id;
            $stmt->close();

            // Subir fotos adicionales
            if (isset($_FILES['fotos']) && is_array($_FILES['fotos']['tmp_name'])) {
                $carpetaVehiculo = 'assets/img/uploads/' . $vehiculo_id;
                if (!is_dir($carpetaVehiculo)) {
                    mkdir($carpetaVehiculo, 0777, true);
                }

                $stmtFoto = $conn->prepare('INSERT INTO vehiculo_fotos (vehiculo_id, ruta_foto) VALUES (?, ?)');
                foreach ($_FILES['fotos']['tmp_name'] as $i => $tmpName) {
                    if (!isset($_FILES['fotos']['error'][$i]) || $_FILES['fotos']['error'][$i] !== UPLOAD_ERR_OK) {
                        continue;
                    }
                    $nombreArchivo = basename($_FILES['fotos']['name'][$i]);
                    $rutaDestino   = $carpetaVehiculo . '/' . uniqid('', true) . '_' . $nombreArchivo;

                    if (is_uploaded_file($tmpName) && move_uploaded_file($tmpName, $rutaDestino)) {
                        $stmtFoto->bind_param('is', $vehiculo_id, $rutaDestino);
                        $stmtFoto->execute();
                    }
                }
                $stmtFoto->close();
            }

            $mensaje = 'Vehículo añadido correctamente.';
        } catch (Throwable $e) {
            error_log('Error INSERT vehiculo: ' . $e->getMessage());
            $mensaje = 'Error al añadir el vehículo.';
        }
    }
}

// ===================== ELIMINAR VEHÍCULO =====================
if (isset($_POST['eliminar_id'])) {
    $eliminar_id = (int)$_POST['eliminar_id'];
    try {
        $stmt = $conn->prepare('DELETE FROM vehiculos WHERE id = ?');
        $stmt->bind_param('i', $eliminar_id);
        $stmt->execute();
        $stmt->close();
        echo '<script>alert("Vehículo eliminado correctamente"); window.location.href = "menu.php";</script>';
        exit;
    } catch (Throwable $e) {
        error_log('Error al eliminar: ' . $e->getMessage());
        echo '<script>alert("Error al eliminar el vehículo"); window.location.href = "menu.php";</script>';
        exit;
    }
}

// ===================== LISTAR VEHÍCULOS PARA LA TABLA =====================
$vehiculos = [];
try {
    $result = $conn->query('SELECT id, identificacion, marca, modelo, precio, tipo_coche, kilometros, transmision, anio, tipo_combustible, color, numero_puertas, cilindrada, cv, estado, foto FROM vehiculos');
    if ($result) {
        $vehiculos = $result->fetch_all(MYSQLI_ASSOC);
    }
} catch (Throwable $e) {
    error_log('Error listando vehículos: ' . $e->getMessage());
}



?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- meta tags -->
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GEF Automoción - Menú</title>

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
    <style>
        .estado{padding:5px 10px;border-radius:12px;color:#fff;font-weight:700;text-transform:capitalize}
        .estado-verde{background:#2e7d32}
        .estado-naranja{background:#ef6c00}
        .estado-rojo{background:#c62828}
        .estado-gris{background:#607d8b}
        .table td, .table th{vertical-align:middle}
    </style>
</head>
<body>

<!-- preloader -->
<div class="preloader">
    <div class="loader-ripple"><div></div><div></div></div>
</div>
<!-- preloader end -->

<header class="header">
    <div class="header-top">
        <div class="container">
            <div class="header-top-wrapper">
                <div class="header-top-left">
                    <div class="header-top-contact">
                        <ul>
                            <li><a href="mailto:gef.automocion@gmail.com"><i class="far fa-envelopes"></i> gef.automocion@gmail.com</a></li>
                            <li><a href="tel:+34645952869"><i class="far fa-phone-volume"></i> +34 645 952 869</a></li>
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

<div class="sidebar-popup">
    <div class="sidebar-wrapper">
        <div class="sidebar-content">
            <button type="button" class="close-sidebar-popup"><i class="far fa-xmark"></i></button>
            <div class="sidebar-logo">
                <img src="assets/img/logo/Icono_GEF_FondoBlanco_pequeno.svg" alt="">
            </div>
            <div class="sidebar-about">
                <h4>Sobre nosotros</h4>
                <p>Tu coche soñado, directo a tus manos. En GEF Automoción importamos vehículos con la mejor calidad y al mejor precio.</p>
            </div>
            <div class="sidebar-contact">
                <h4>Información de contacto</h4>
                <ul>
                    <li><h6>Email</h6><a href="mailto:gef.automocion@gmail.com"><i class="far fa-envelope"></i>gef.automocion@gmail.com</a></li>
                    <li><h6>Teléfono</h6><a href="tel:+34645952869"><i class="far fa-phone"></i>+34 645 952 869</a></li>
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
                <a href="login.html" class="theme-btn">Acceder al Menú Privado</a>
            </div>
        </div>
    </div>
</div>

<main class="main">
    <div class="site-breadcrumb" style="background: url(assets/img/breadcrumb/01.jpg)">
        <div class="container">
            <h2 class="breadcrumb-title">Menú</h2>
        </div>
    </div>

    <div class="user-profile py-120">
        <div class="container">
            <?php if ($mensaje): ?>
                <div class="alert alert-info"><?php echo h($mensaje); ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-3">
                    <div class="user-profile-sidebar">
                        <div class="user-profile-sidebar-top">
                            <div class="user2-profile-img avatar-100">
                                <img src="<?php echo h($_SESSION['foto'] ?? 'assets/img/account/user.jpg'); ?>" alt="">
                                <button type="button" class="profile-img-btn"><i class="far fa-camera"></i></button>
                                <input type="file" class="profile-img-file" name="foto_usuario" accept="image/*">
                            </div>                            
                            <h5><?php echo h($_SESSION['username'] ?? 'Usuario'); ?></h5>
                            <p><?php echo h($_SESSION['email'] ?? 'privado@gefautomocion.com'); ?></p>
                        </div>
                        <ul class="user-profile-sidebar-list">
                            <li><a href="#ver-vehiculos" class="active"><i class="far fa-car"></i> Ver Vehículos</a></li>
                            <li><a href="#cargar-vehiculo" class="active"><i class="far fa-plus-circle"></i> Cargar Nuevo Vehículo</a></li>
                            <li><a href="logout"><i class="far fa-sign-out"></i> Cerrar sesión</a></li>
                        </ul>
                    </div>
                </div>

                <div class="col-lg-9">
                    <div class="user-profile-wrapper">

                        <!-- Listado -->
                        <div class="row" id="ver-vehiculos">
                            <div class="col-lg-12">
                                <div class="user-profile-card">
                                    <h4 class="user-profile-card-title">Vehículos Existentes</h4>
                                    <div class="table-responsive">
                                        <table class="table text-nowrap">
                                            <thead>
                                            <tr>
                                                <th>Identificación</th>
                                                <th>Foto</th>
                                                <th>Marca</th>
                                                <th>Modelo</th>
                                                <th>Precio</th>
                                                <th>Tipo de Coche</th>
                                                <th>Kilómetros</th>
                                                <th>Transmisión</th>
                                                <th>Año</th>
                                                <th>Combustible</th>
                                                <th>Color</th>
                                                <th>Nº Puertas</th>
                                                <th>Cilindrada</th>
                                                <th>CV</th>
                                                <th>Estado</th>
                                                <th>Acciones</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php foreach ($vehiculos as $vehiculo): ?>
                                                <tr>
                                                    <td><?php echo h($vehiculo['identificacion']); ?></td>
                                                     <td>
                                                        <?php if (!empty($vehiculo['foto'])): ?>
                                                            <img src="<?php echo h($vehiculo['foto']); ?>" alt="Foto del vehículo"
                                                                 style="max-width:100px; max-height:80px; object-fit:cover;">
                                                        <?php else: ?>
                                                            <span class="text-muted">Sin foto</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo h($vehiculo['marca']); ?></td>
                                                    <td><?php echo h($vehiculo['modelo']); ?></td>
                                                    <td><?php echo h($vehiculo['precio']); ?></td>
                                                    <td><?php echo h($vehiculo['tipo_coche']); ?></td>
                                                    <td><?php echo h($vehiculo['kilometros']); ?></td>
                                                    <td><?php echo h($vehiculo['transmision']); ?></td>
                                                    <td><?php echo h($vehiculo['anio']); ?></td>
                                                    <td><?php echo h($vehiculo['tipo_combustible']); ?></td>
                                                    <td><?php echo h($vehiculo['color']); ?></td>
                                                    <td><?php echo h($vehiculo['numero_puertas']); ?></td>
                                                    <td><?php echo h($vehiculo['cilindrada']); ?></td>
                                                    <td><?php echo h($vehiculo['cv']); ?></td>
                                                    <td>
                                                        <?php
                                                        $estado = $vehiculo['estado'];
                                                        $clase_estado = 'estado-gris';
                                                        if ($estado === 'activo')     $clase_estado = 'estado-verde';
                                                        elseif ($estado === 'reservado') $clase_estado = 'estado-naranja';
                                                        elseif ($estado === 'vendido')   $clase_estado = 'estado-rojo';
                                                        ?>
                                                        <span class="estado <?php echo $clase_estado; ?>"><?php echo h($estado); ?></span>
                                                    </td>                                                   
                                                    <td>
                                                        <form method="POST" style="display:inline;">
                                                            <input type="hidden" name="eliminar_id" value="<?php echo (int)$vehiculo['id']; ?>">
                                                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Eliminar este vehículo?');">Eliminar</button>
                                                        </form>
                                                        <form method="POST" action="editar.php" style="display:inline;">
                                                            <input type="hidden" name="editar_id" value="<?php echo (int)$vehiculo['id']; ?>">
                                                            <button type="submit" class="btn btn-primary btn-sm">Editar</button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Alta -->
                        <div class="user-profile-content" id="cargar-vehiculo">
                            <h3>Añadir Nuevo Vehículo</h3>
                            <form method="POST" action="menu.php" enctype="multipart/form-data">
                                <div class="form-group">
                                    <label for="marca">Marca:</label>
                                    <input type="text" id="marca" name="marca" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="modelo">Modelo:</label>
                                    <input type="text" id="modelo" name="modelo" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="precio">Precio:</label>
                                    <input type="number" id="precio" name="precio" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="tipo_coche">Tipo de Coche:</label>
                                    <select id="tipo_coche" name="tipo_coche" class="form-control" required>
                                        <option value="coupe">Coupé</option>
                                        <option value="berlina">Berlina</option>
                                        <option value="SUV">SUV</option>
                                        <option value="monovolumen">Monovolumen</option>
                                        <option value="furgoneta">Furgoneta</option>
                                        <option value="descapotable">Descapotable</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="kilometros">Kilómetros:</label>
                                    <input type="number" id="kilometros" name="kilometros" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="transmision">Transmisión:</label>
                                    <select id="transmision" name="transmision" class="form-control" required>
                                        <option value="automatica">Automática</option>
                                        <option value="manual">Manual</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="anio">Año:</label>
                                    <input type="number" id="anio" name="anio" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="tipo_combustible">Tipo de Combustible:</label>
                                    <select id="tipo_combustible" name="tipo_combustible" class="form-control" required>
                                        <option value="gasolina">Gasolina</option>
                                        <option value="diesel">Diésel</option>
                                        <option value="hibrido">Híbrido</option>
                                        <option value="electrico">Eléctrico</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="color">Color:</label>
                                    <input type="text" id="color" name="color" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="numero_puertas">Número de Puertas:</label>
                                    <select id="numero_puertas" name="numero_puertas" class="form-control" required>
                                        <option value="2 puertas">2 Puertas</option>
                                        <option value="3 puertas">3 Puertas</option>
                                        <option value="4 puertas">4 Puertas</option>
                                        <option value="5 puertas">5 Puertas</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="cilindrada">Cilindrada (cc):</label>
                                    <input type="number" id="cilindrada" name="cilindrada" class="form-control" required>
                                </div>
                                <div class="form-group">
                                    <label for="cv">CV:</label>
                                    <input type="number" id="cv" name="cv" class="form-control" required>
                                </div>

                                <div class="form-group">
                                    <label for="descripcion">Descripción:</label>
                                    <textarea id="descripcion" name="descripcion" class="form-control" rows="5" placeholder="Describe el vehículo (equipamiento, historial, etc.)"></textarea>
                                </div>

                                <div class="form-group">
                                    <label for="foto">Foto Principal:</label>
                                    <input type="file" id="foto" name="foto" class="form-control" accept="image/*">
                                </div>
                                <div class="form-group">
                                    <label for="fotos">Fotos Adicionales:</label>
                                    <input type="file" id="fotos" name="fotos[]" class="form-control" accept="image/*" multiple>
                                </div>
                                <button type="submit" class="theme-btn">Añadir Vehículo</button>
                            </form>
                        </div>

                    </div>
                </div>
            </div>

        </div>
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
            <div style="position: absolute; inset: 0; background: rgba(40, 40, 40, 0.102);"></div>
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