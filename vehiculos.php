<?php
require_once 'config.php';
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

/* ====== Parámetros (GET) ====== */
$perPage = 9;
$page    = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset  = ($page - 1) * $perPage;

$q       = isset($_GET['q']) ? trim($_GET['q']) : '';
$marca   = isset($_GET['marca']) ? (array)$_GET['marca'] : [];
$trans   = isset($_GET['transmision']) ? (array)$_GET['transmision'] : [];
$comb    = isset($_GET['combustible']) ? (array)$_GET['combustible'] : [];
$precioMin = isset($_GET['pmin']) && $_GET['pmin'] !== '' ? max(0, (int)$_GET['pmin']) : null;
$precioMax = isset($_GET['pmax']) && $_GET['pmax'] !== '' ? max(0, (int)$_GET['pmax']) : null;
$anioMin   = isset($_GET['ymin']) && $_GET['ymin'] !== '' ? (int)$_GET['ymin'] : null;
$anioMax   = isset($_GET['ymax']) && $_GET['ymax'] !== '' ? (int)$_GET['ymax'] : null;
$order  = isset($_GET['order']) ? $_GET['order'] : 'recent'; // recent|price_asc|price_desc

/* ====== Marcas dinámicas ====== */
$marcasDisponibles = [];
try {
    $rsBrands = $conn->query("SELECT DISTINCT marca FROM vehiculos WHERE marca <> '' ORDER BY marca ASC");
    if ($rsBrands) { $marcasDisponibles = array_column($rsBrands->fetch_all(MYSQLI_ASSOC), 'marca'); }
} catch (Throwable $e) { error_log($e->getMessage()); }

/* ====== WHERE ====== */
$where = ["1=1"];

// EXCLUIR desactivados
$where[] = "estado <> 'desactivado'";

if ($q !== '') {
    $safe = $conn->real_escape_string($q);
    $where[] = "(marca LIKE '%$safe%' OR modelo LIKE '%$safe%' OR identificacion LIKE '%$safe%')";
}
$whitelistTrans = ['automatica','manual'];
$whitelistComb  = ['gasolina','diesel','hibrido','electrico'];
if (!empty($trans)) {
    $t = array_values(array_intersect($trans, $whitelistTrans));
    if ($t) { $where[] = "transmision IN ('" . implode("','", array_map([$conn,'real_escape_string'],$t)) . "')"; }
}
if (!empty($comb)) {
    $c = array_values(array_intersect($comb, $whitelistComb));
    if ($c) { $where[] = "tipo_combustible IN ('" . implode("','", array_map([$conn,'real_escape_string'],$c)) . "')"; }
}
if (!empty($marca)) {
    $m = array_values(array_intersect($marca, $marcasDisponibles));
    if ($m) { $where[] = "marca IN ('" . implode("','", array_map([$conn,'real_escape_string'],$m)) . "')"; }
}
if ($precioMin !== null) { $where[] = "precio >= " . (int)$precioMin; }
if ($precioMax !== null) { $where[] = "precio <= " . (int)$precioMax; }
if ($anioMin !== null)   { $where[] = "anio >= " . (int)$anioMin; }
if ($anioMax !== null)   { $where[] = "anio <= " . (int)$anioMax; }

$whereSql = implode(' AND ', $where);

/* ====== Orden ====== */
switch ($order) {
    case 'price_asc':  $orderSql = "precio ASC, id DESC"; break;
    case 'price_desc': $orderSql = "precio DESC, id DESC"; break;
    default:           $orderSql = "id DESC"; break;
}

/* ====== Conteo total ====== */
$total = 0;
try {
    $rsCount = $conn->query("SELECT COUNT(*) AS c FROM vehiculos WHERE $whereSql");
    if ($rsCount && ($row = $rsCount->fetch_assoc())) { $total = (int)$row['c']; }
} catch (Throwable $e) { error_log($e->getMessage()); }

$from = $total ? ($offset + 1) : 0;
$to   = min($offset + $perPage, $total);
$totalPages = max(1, (int)ceil($total / $perPage));

/* ====== Página de resultados ====== */
$vehiculos = [];
try {
    $stmt = $conn->prepare("
        SELECT id, identificacion, marca, modelo, precio, transmision, anio, tipo_combustible, kilometros, estado, foto
        FROM vehiculos
        WHERE $whereSql
        ORDER BY $orderSql
        LIMIT ? OFFSET ?
    ");
    $stmt->bind_param('ii', $perPage, $offset);
    $stmt->execute();
    $vehiculos = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Throwable $e) { error_log($e->getMessage()); }

/* Helpers */
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function precio_es($n){ return number_format((float)$n, 0, ',', '.') . ' €'; } // € al final
function km_es($n){ return number_format((int)$n, 0, ',', '.') . ' km'; }

/* Fallback imagen */
$placeholderImg = 'assets/img/car/01.jpg';

/* Mantener valores en filtros */
function checked($cond){ return $cond ? 'checked' : ''; }
function selected($cond){ return $cond ? 'selected' : ''; }
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
    <style>
        body { background: url('assets/img/breadcrumb/01.jpg') no-repeat center center fixed; background-size: cover; }
        .car-img img { width: 100%; height: 220px; object-fit: cover; }

        /* Línea bajo los títulos de los filtros */

        .car-widget-title {        
            position: relative;        
            padding-bottom: 12px;        
            margin-bottom: 15px; /* para que no se pegue al contenido */        
        }
        .car-widget-title::before,
        .car-widget-title::after {        
            all: unset;        
        }

        .car-widget-title {
            display: inline-block;
            border-bottom: 3px solid #C5B993; /* Color y grosor */
            width: 140px; /* Longitud fija para todas las cajas */
            padding-bottom: 5px; /* Espaciado inferior */
            text-align: left; /* Alineación del texto */
        }
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
                                <li><a href="mailto:info@gefautomocion.com"><i class="far fa-envelopes"></i> info@gefautomocion.com</a></li>
                                <li><a href="tel:+34123456789"><i class="far fa-phone-volume"></i> +34 123 456 789</a></li>
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
                        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#main_nav" aria-expanded="false" aria-label="Toggle navigation">
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
                        <form method="get" action="vehiculos.php">
                            <div class="form-group">
                                <input type="text" name="q" class="form-control" placeholder="Buscar marca / modelo" value="<?php echo h($q); ?>">
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
                        <li><h6>Email</h6><a href="mailto:info@gefautomocion.com"><i class="far fa-envelope"></i>info@gefautomocion.com</a></li>
                        <li><h6>Teléfono</h6><a href="tel:+21236547898"><i class="far fa-phone"></i>+34 123 456 789</a></li>
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
            <div class="container"><h2 class="breadcrumb-title">Vehículos</h2></div>
        </div>
        <!-- breadcrumb end -->

        <!-- car area -->
        <div class="car-area grid bg py-120">
            <div class="container">
                <div class="row">
                    <!-- Sidebar filtros -->
                    <div class="col-lg-3">
                        <!-- Botón para colapsar/expandir filtros en móvil -->
                        <div class="d-lg-none text-center mb-3">
                            <button class="btn btn-outline-secondary w-100 mt-2" type="button" data-bs-toggle="collapse" data-bs-target="#filtrosColapsados" aria-expanded="false" aria-controls="filtrosColapsados" style="max-width: 200px; border-color: #dcdcdc;">
                                Mostrar/Ocultar Filtros
                            </button>
                        </div>

                        <!-- Contenedor colapsable para los filtros -->
                        <div class="collapse d-lg-block" id="filtrosColapsados">
                            <div class="car-sidebar">
                                <!-- Buscar -->
                                <div class="car-widget">
                                    <div class="car-search-form">
                                        <h4 class="car-widget-title">Buscar</h4>
                                        <form method="get" action="vehiculos.php">
                                            <div class="form-group">
                                                <input type="text" name="q" class="form-control" placeholder="Buscar" value="<?php echo h($q); ?>">
                                                <button type="search"><i class="far fa-search"></i></button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Marcas -->
                                <div class="car-widget">
                                    <h4 class="car-widget-title">Marcas</h4>
                                    <form id="filtros" method="get" action="vehiculos.php">
                                        <input type="hidden" name="q" value="<?php echo h($q); ?>">
                                        <input type="hidden" name="order" value="<?php echo h($order); ?>">

                                        <ul style="max-height:220px; overflow:auto; padding-right:6px;">
                                            <li>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="brand_all" onclick="toggleAllBrands(this)">
                                                    <label class="form-check-label" for="brand_all"> Todas las marcas</label>
                                                </div>
                                            </li>
                                            <?php foreach ($marcasDisponibles as $m): 
                                                $isChecked = in_array($m, $marca);
                                            ?>
                                            <li>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="marca[]" value="<?php echo h($m); ?>" id="brand_<?php echo h($m); ?>" <?php echo checked($isChecked); ?>>
                                                    <label class="form-check-label" for="brand_<?php echo h($m); ?>"> <?php echo h($m); ?></label>
                                                </div>
                                            </li>
                                            <?php endforeach; ?>
                                        </ul>
                                </div>

                                <!-- Rango de precios -->
                                <div class="car-widget">
                                    <h4 class="car-widget-title">Rango de precios (€)</h4>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="filter-label">Min</label>
                                            <input type="number" class="form-control" name="pmin" min="0" step="500" value="<?php echo $precioMin!==null?(int)$precioMin:''; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="filter-label">Max</label>
                                            <input type="number" class="form-control" name="pmax" min="0" step="500" value="<?php echo $precioMax!==null?(int)$precioMax:''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Transmisión -->
                                <div class="car-widget">
                                    <h4 class="car-widget-title">Transmisión</h4>
                                    <ul>
                                        <li>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="transmision[]" value="automatica" id="tran1" <?php echo checked(in_array('automatica',$trans)); ?>>
                                                <label class="form-check-label" for="tran1"> Automática</label>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="transmision[]" value="manual" id="tran2" <?php echo checked(in_array('manual',$trans)); ?>>
                                                <label class="form-check-label" for="tran2"> Manual</label>
                                            </div>
                                        </li>
                                    </ul>
                                </div>

                                <!-- Combustible -->
                                <div class="car-widget">
                                    <h4 class="car-widget-title">Tipo de combustible</h4>
                                    <ul>
                                        <?php 
                                        $combOpts = ['gasolina'=>'Gasolina','diesel'=>'Diésel','hibrido'=>'Híbrido','electrico'=>'Eléctrico'];
                                        foreach ($combOpts as $key=>$lbl): ?>
                                        <li>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="combustible[]" value="<?php echo h($key); ?>" id="fuel_<?php echo h($key); ?>" <?php echo checked(in_array($key,$comb)); ?>>
                                                <label class="form-check-label" for="fuel_<?php echo h($key); ?>"> <?php echo h($lbl); ?></label>
                                            </div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>

                                <!-- Años -->
                                <div class="car-widget">
                                    <h4 class="car-widget-title">Año</h4>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="filter-label">Desde</label>
                                            <input type="number" class="form-control" name="ymin" min="1950" max="<?php echo date('Y'); ?>" value="<?php echo $anioMin!==null?(int)$anioMin:''; ?>">
                                        </div>
                                        <div class="col-6">
                                            <label class="filter-label">Hasta</label>
                                            <input type="number" class="form-control" name="ymax" min="1950" max="<?php echo date('Y'); ?>" value="<?php echo $anioMax!==null?(int)$anioMax:''; ?>">
                                        </div>
                                    </div>
                                </div>

                                <div class="car-widget">
                                    <button type="submit" class="theme-btn w-100"><i class="far fa-filter"></i> Aplicar filtros</button>
                                    <a href="vehiculos.php" class="btn btn-outline-secondary w-100 mt-2">Limpiar</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Grid -->
                    <div class="col-lg-9">
                        <div class="col-md-12">
                            <div class="car-sort">
                                <h6>
                                    <?php if ($total): ?>
                                        Mostrando <?php echo $from; ?>–<?php echo $to; ?> de <?php echo $total; ?> resultados
                                    <?php else: ?>
                                        No hay resultados
                                    <?php endif; ?>
                                </h6>
                                <div class="col-md-3 car-sort-box">
                                    <form method="get" action="vehiculos.php" id="ordenForm">
                                        <?php
                                        $hidden = $_GET; unset($hidden['order']);
                                        foreach ($hidden as $k=>$v){
                                            if (is_array($v)){
                                                foreach($v as $vv){
                                                    echo '<input type="hidden" name="'.h($k).'[]" value="'.h($vv).'">';
                                                }
                                            } else {
                                                echo '<input type="hidden" name="'.h($k).'" value="'.h($v).'">';
                                            }
                                        }
                                        ?>
                                        <select class="select" name="order" onchange="document.getElementById('ordenForm').submit()">
                                            <option value="recent"     <?php echo selected($order==='recent'); ?>>Ordenar por más reciente</option>
                                            <option value="price_asc"  <?php echo selected($order==='price_asc'); ?>>Ordenar por precio bajo</option>
                                            <option value="price_desc" <?php echo selected($order==='price_desc'); ?>>Ordenar por precio alto</option>
                                        </select>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <?php if (!empty($vehiculos)): ?>
                                <?php foreach ($vehiculos as $v): 
                                    $img = !empty($v['foto']) ? $v['foto'] : $placeholderImg;
                                    $title = trim(($v['marca'] ?? '').' '.($v['modelo'] ?? ''));
                                    $estado = $v['estado'] ?? '';
                                    $statusClass = ($estado==='nuevo' || $estado==='New') ? 'status-2' : 'status-1';
                                    $detalleUrl = 'vehiculoDetalle?identificacion=' . rawurlencode($v['identificacion']);
                                ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="car-item">
                                        <div class="car-img">
                                            <?php if ($estado !== ''): ?>
                                                <span class="car-status <?php echo h($statusClass); ?>"><?php echo h(ucfirst($estado)); ?></span>
                                            <?php endif; ?>
                                            <img src="<?php echo h($img); ?>" alt="<?php echo h($title); ?>" loading="lazy">
                                            <!-- Eliminados: favoritos y recarga -->
                                        </div>
                                        <div class="car-content">
                                            <div class="car-top">
                                                <h4><a href="<?php echo h($detalleUrl); ?>"><?php echo h($title); ?></a></h4>
                                                <!-- sin estrellas -->
                                            </div>
                                            <ul class="car-list">
                                                <li><i class="far fa-steering-wheel"></i><?php echo h($v['transmision']); ?></li>
                                                <li><i class="far fa-road"></i><?php echo km_es($v['kilometros']); ?></li>
                                                <li><i class="fa-light fa-calendar-days"></i><?php echo h($v['anio']); ?></li>
                                                <li><i class="far fa-gas-pump"></i><?php echo h($v['tipo_combustible']); ?></li>
                                            </ul>
                                            <div class="car-footer">
                                                <span class="car-price"><?php echo precio_es($v['precio']); ?></span>
                                                <a href="<?php echo h($detalleUrl); ?>" class="theme-btn"><span class="far fa-eye"></span>Detalles</a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="col-12"><div class="alert alert-info">No hay vehículos disponibles con los filtros seleccionados.</div></div>
                            <?php endif; ?>
                        </div>

                        <!-- pagination -->
                        <?php if ($totalPages > 1): ?>
                        <div class="pagination-area">
                            <div aria-label="Page navigation example">
                                <ul class="pagination">
                                    <?php
                                    function qp($page){
                                        $params = $_GET; $params['page']=$page;
                                        return '?' . http_build_query($params);
                                    }
                                    ?>
                                    <li class="page-item <?php echo $page<=1?'disabled':''; ?>">
                                        <a class="page-link" href="<?php echo $page<=1?'#':qp($page-1); ?>" aria-label="Previous">
                                            <span aria-hidden="true"><i class="far fa-arrow-left"></i></span>
                                        </a>
                                    </li>
                                    <?php
                                    $start = max(1, $page-2);
                                    $end   = min($totalPages, $page+2);
                                    for ($p=$start; $p<=$end; $p++): ?>
                                        <li class="page-item <?php echo $p===$page?'active':''; ?>">
                                            <a class="page-link" href="<?php echo qp($p); ?>"><?php echo $p; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo $page>=$totalPages?'disabled':''; ?>">
                                        <a class="page-link" href="<?php echo $page>=$totalPages?'#':qp($page+1); ?>" aria-label="Next">
                                            <span aria-hidden="true"><i class="far fa-arrow-right"></i></span>
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <?php endif; ?>
                        <!-- pagination end -->
                    </div>
                </div>
            </div>
        </div>
        <!-- car area end -->
    </main>

    <!-- footer area -->
    <footer class="footer-area">
        <div class="footer-widget">
            <div class="container">
                <div class="row footer-widget-wrapper pt-60 pb-40 align-items-start">
                    <div class="col-md-12 col-lg-3 text-center text-lg-start">
                        <div class="footer-widget-box about-us">
                            <a href="#" class="footer-logo d-block">
                                <img src="assets/img/logo/Icono_GEF_SinFondo_pequeno.svg" alt="GEF Automoción" style="max-width: 160px;">
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-3 text-center text-lg-start">
                        <div class="footer-widget-box">
                            <h4 class="footer-widget-title mb-3">Contacto</h4>
                            <ul class="footer-contact">
                                <li class="mb-2"><a href="tel:+34985123456"><i class="far fa-phone"></i>+34 985 123 456</a></li>
                                <li class="mb-0"><a href="mailto:info@gefautomocion.com"><i class="far fa-envelope"></i>info@gefautomocion.com</a></li>
                            </ul>
                        </div>
                    </div>
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
                        <p class="copyright-text">&copy; Copyright <span id="date"></span> <a href="#"> GEF Automoción </a> Todos los derechos reservados.</p>
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
    <script>
        function toggleAllBrands(cb){
            const checks = document.querySelectorAll('input[name="marca[]"]');
            checks.forEach(el => el.checked = cb.checked);
        }
    </script>
</body>
</html>