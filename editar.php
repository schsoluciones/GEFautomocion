<?php
session_start();
require_once 'config.php';

// Solo usuarios logueados
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.html');
    exit;
}

// MySQLi estricto + charset
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if (function_exists('mysqli_set_charset')) { @mysqli_set_charset($conn, 'utf8mb4'); }

// Helpers
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

// Cargar vehículo por POST (editar_id) o GET (id)
$vehiculo = null;

function cargarVehiculoPorId(mysqli $conn, int $id): ?array {
    $stmt = $conn->prepare('SELECT * FROM vehiculos WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $r ?: null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['editar_id']) && !isset($_POST['guardar'])) {
    // 1ª carga desde menú (botón Editar)
    $editar_id = (int)$_POST['editar_id'];
    $vehiculo = cargarVehiculoPorId($conn, $editar_id);
    if (!$vehiculo) { echo 'Vehículo no encontrado'; exit; }
}

// Guardar cambios
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar'])) {
    try {
        $editar_id        = (int)($_POST['editar_id'] ?? 0);
        if ($editar_id <= 0) { throw new Exception('ID inválido'); }

        // Cargar registro actual para valores por defecto (foto, identificacion…)
        $vehiculoActual = cargarVehiculoPorId($conn, $editar_id);
        if (!$vehiculoActual) { throw new Exception('Vehículo no encontrado'); }

        // Campos
        // Si quieres permitir editar la identificacion, cambia la línea siguiente por $_POST['identificacion'] saneado.
        $identificacion   = trim((string)($vehiculoActual['identificacion'] ?? '')); // no se modifica por defecto
        $marca            = trim((string)($_POST['marca'] ?? ''));
        $modelo           = trim((string)($_POST['modelo'] ?? ''));
        $precio           = ($_POST['precio'] === '' || $_POST['precio'] === null) ? 0 : (int)$_POST['precio'];
        $tipo_coche       = trim((string)($_POST['tipo_coche'] ?? ''));
        $kilometros       = ($_POST['kilometros'] === '' || $_POST['kilometros'] === null) ? 0 : (int)$_POST['kilometros'];
        $transmision      = trim((string)($_POST['transmision'] ?? ''));
        $anio             = ($_POST['anio'] === '' || $_POST['anio'] === null) ? 0 : (int)$_POST['anio'];
        $tipo_combustible = trim((string)($_POST['tipo_combustible'] ?? ''));
        $color            = trim((string)($_POST['color'] ?? ''));
        $numero_puertas   = trim((string)($_POST['numero_puertas'] ?? ''));
        $cilindrada       = ($_POST['cilindrada'] === '' || $_POST['cilindrada'] === null) ? 0 : (int)$_POST['cilindrada'];
        $cv               = ($_POST['cv'] === '' || $_POST['cv'] === null) ? 0 : (int)$_POST['cv'];
        $estado           = trim((string)($_POST['estado'] ?? ''));
        $descripcion      = trim((string)($_POST['descripcion'] ?? ''));

        // Validaciones básicas
        $transValid = ['automatica','manual'];
        if ($transmision && !in_array($transmision, $transValid, true)) {
            throw new Exception('Transmisión inválida');
        }

        // Asegurar carpetas
        $baseUploads = 'assets/img/uploads';
        if (!is_dir($baseUploads)) { @mkdir($baseUploads, 0777, true); }
        $carpetaVehiculo = $baseUploads . '/' . $editar_id;
        if (!is_dir($carpetaVehiculo)) { @mkdir($carpetaVehiculo, 0777, true); }

        // Foto principal (si no subes, se conserva)
        $foto = $vehiculoActual['foto'] ?? null;
        if (isset($_FILES['foto']) && is_array($_FILES['foto']) && ($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK) {
            if (is_uploaded_file($_FILES['foto']['tmp_name'])) {
                $nombreArchivo = basename($_FILES['foto']['name']);
                $ext  = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
                $base = pathinfo($nombreArchivo, PATHINFO_FILENAME);
                $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', $base);
                $nombreSeguro = $base . '_' . uniqid('', true) . ($ext ? '.' . $ext : '');
                $rutaDestino = $carpetaVehiculo . '/' . $nombreSeguro;
                if (!move_uploaded_file($_FILES['foto']['tmp_name'], $rutaDestino)) {
                    throw new Exception('No se pudo mover la foto principal');
                }
                $foto = $rutaDestino;
            }
        }

        // Transacción
        $conn->begin_transaction();

        // UPDATE con descripcion y estado (varchar), tipos correctos
        $sql = 'UPDATE vehiculos
                   SET identificacion = ?,
                       marca = ?,
                       modelo = ?,
                       precio = ?,
                       estado = ?,
                       tipo_coche = ?,
                       kilometros = ?,
                       transmision = ?,
                       anio = ?,
                       tipo_combustible = ?,
                       color = ?,
                       numero_puertas = ?,
                       cilindrada = ?,
                       cv = ?,
                       descripcion = ?,
                       foto = ?
                 WHERE id = ?';

        // Tipos (17 params): s s s i s s i s i s s s i i s s i
        $types = 'sssissisisssiissi';
        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            $types,
            $identificacion,
            $marca,
            $modelo,
            $precio,
            $estado,
            $tipo_coche,
            $kilometros,
            $transmision,
            $anio,
            $tipo_combustible,
            $color,
            $numero_puertas,
            $cilindrada,
            $cv,
            $descripcion,
            $foto,
            $editar_id
        );
        $stmt->execute();
        $stmt->close();

        // Fotos adicionales
        if (isset($_FILES['fotos']) && is_array($_FILES['fotos']['tmp_name'])) {
            $sqlFoto = 'INSERT INTO vehiculo_fotos (vehiculo_id, ruta_foto) VALUES (?, ?)';
            $stmtFoto = $conn->prepare($sqlFoto);

            foreach ($_FILES['fotos']['tmp_name'] as $idx => $tmpName) {
                if (!isset($_FILES['fotos']['error'][$idx]) || $_FILES['fotos']['error'][$idx] !== UPLOAD_ERR_OK) continue;
                if (!is_uploaded_file($tmpName)) continue;

                $nombreArchivo = basename($_FILES['fotos']['name'][$idx]);
                $ext  = pathinfo($nombreArchivo, PATHINFO_EXTENSION);
                $base = pathinfo($nombreArchivo, PATHINFO_FILENAME);
                $base = preg_replace('/[^a-zA-Z0-9_-]/', '_', $base);
                $nombreSeguro = $base . '_' . uniqid('', true) . ($ext ? '.' . $ext : '');
                $rutaDestino = $carpetaVehiculo . '/' . $nombreSeguro;

                if (move_uploaded_file($tmpName, $rutaDestino)) {
                    $stmtFoto->bind_param('is', $editar_id, $rutaDestino);
                    $stmtFoto->execute();
                }
            }
            $stmtFoto->close();
        }

        $conn->commit();

        header('Location: menu.php');
        exit;

    } catch (Throwable $e) {
        if ($conn->errno === 0) { // evitar warnings si no hay tx abierta
            try { $conn->rollback(); } catch (Throwable $ignored) {}
        } else {
            try { $conn->rollback(); } catch (Throwable $ignored) {}
        }
        error_log('Editar ERROR: '.$e->getMessage());
        echo 'No se pudo guardar los cambios.';
        exit;
    }
}

// Si no venimos de POST guardar ni de primera carga, intentar GET id
if (!$vehiculo) {
    if (isset($_GET['id'])) {
        $vehiculo = cargarVehiculoPorId($conn, (int)$_GET['id']);
    }
    if (!$vehiculo) { echo 'Solicitud no válida'; exit; }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Vehículo</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body>
<div class="container mt-5 mb-5">
    <h1 class="mb-4">Editar Vehículo</h1>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="editar_id" value="<?php echo h($vehiculo['id']); ?>">

        <!-- Si quieres permitir editar la identificación, descomenta estas líneas
        <div class="mb-3">
            <label for="identificacion" class="form-label">Identificación</label>
            <input type="text" class="form-control" id="identificacion" name="identificacion"
                   value="<?php echo h($vehiculo['identificacion'] ?? ''); ?>">
        </div>
        -->

        <div class="mb-3">
            <label for="marca" class="form-label">Marca</label>
            <input type="text" class="form-control" id="marca" name="marca" value="<?php echo h($vehiculo['marca'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="modelo" class="form-label">Modelo</label>
            <input type="text" class="form-control" id="modelo" name="modelo" value="<?php echo h($vehiculo['modelo'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="precio" class="form-label">Precio (€)</label>
            <input type="number" step="1" class="form-control" id="precio" name="precio" value="<?php echo h($vehiculo['precio'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="tipo_coche" class="form-label">Tipo de Coche</label>
            <input type="text" class="form-control" id="tipo_coche" name="tipo_coche" value="<?php echo h($vehiculo['tipo_coche'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="kilometros" class="form-label">Kilómetros</label>
            <input type="number" class="form-control" id="kilometros" name="kilometros" value="<?php echo h($vehiculo['kilometros'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="transmision" class="form-label">Transmisión</label>
            <select class="form-control" id="transmision" name="transmision">
                <option value="automatica" <?php echo (($vehiculo['transmision'] ?? '')==='automatica')?'selected':''; ?>>Automática</option>
                <option value="manual"     <?php echo (($vehiculo['transmision'] ?? '')==='manual')?'selected':''; ?>>Manual</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="anio" class="form-label">Año</label>
            <input type="number" class="form-control" id="anio" name="anio" value="<?php echo h($vehiculo['anio'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="tipo_combustible" class="form-label">Tipo de Combustible</label>
            <select class="form-control" id="tipo_combustible" name="tipo_combustible">
                <option value="gasolina"  <?php echo (($vehiculo['tipo_combustible'] ?? '')==='gasolina') ? 'selected' : ''; ?>>Gasolina</option>
                <option value="diesel"    <?php echo (($vehiculo['tipo_combustible'] ?? '')==='diesel')   ? 'selected' : ''; ?>>Diésel</option>
                <option value="hibrido"   <?php echo (($vehiculo['tipo_combustible'] ?? '')==='hibrido')  ? 'selected' : ''; ?>>Híbrido</option>
                <option value="electrico" <?php echo (($vehiculo['tipo_combustible'] ?? '')==='electrico')? 'selected' : ''; ?>>Eléctrico</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="color" class="form-label">Color</label>
            <input type="text" class="form-control" id="color" name="color" value="<?php echo h($vehiculo['color'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="numero_puertas" class="form-label">Número de Puertas</label>
            <select class="form-control" id="numero_puertas" name="numero_puertas">
                <?php
                $opts = ['2 puertas','3 puertas','4 puertas','5 puertas'];
                $valActual = $vehiculo['numero_puertas'] ?? '';
                foreach ($opts as $opt) {
                    $sel = ($valActual === $opt) ? 'selected' : '';
                    echo '<option value="'.h($opt).'" '.$sel.'>'.h(ucfirst($opt)).'</option>';
                }
                ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="cilindrada" class="form-label">Cilindrada (cc)</label>
            <input type="number" class="form-control" id="cilindrada" name="cilindrada" value="<?php echo h($vehiculo['cilindrada'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="cv" class="form-label">CV</label>
            <input type="number" class="form-control" id="cv" name="cv" value="<?php echo h($vehiculo['cv'] ?? ''); ?>">
        </div>

        <div class="mb-3">
            <label for="estado" class="form-label">Estado</label>
            <select class="form-control" id="estado" name="estado">
                <option value="activo"      <?php echo (($vehiculo['estado'] ?? '')==='activo')      ? 'selected' : ''; ?>>Activo</option>
                <option value="reservado"   <?php echo (($vehiculo['estado'] ?? '')==='reservado')   ? 'selected' : ''; ?>>Reservado</option>
                <option value="vendido"     <?php echo (($vehiculo['estado'] ?? '')==='vendido')     ? 'selected' : ''; ?>>Vendido</option>
                <option value="desactivado" <?php echo (($vehiculo['estado'] ?? '')==='desactivado') ? 'selected' : ''; ?>>Desactivado</option>
            </select>
        </div>

        <div class="mb-3">
            <label for="descripcion" class="form-label">Descripción</label>
            <textarea class="form-control" id="descripcion" name="descripcion" rows="5"><?php echo h($vehiculo['descripcion'] ?? ''); ?></textarea>
        </div>

        <div class="mb-3">
            <label for="foto" class="form-label">Foto principal</label>
            <input type="file" class="form-control" id="foto" name="foto" accept="image/*">
            <?php if (!empty($vehiculo['foto'])): ?>
                <img src="<?php echo h($vehiculo['foto']); ?>" alt="Foto del vehículo" class="img-thumbnail mt-2" style="max-width: 220px; height:auto;">
            <?php endif; ?>
        </div>

        <div class="mb-3">
            <label for="fotos" class="form-label">Fotos adicionales</label>
            <input type="file" class="form-control" id="fotos" name="fotos[]" multiple accept="image/*">
            <div class="form-text">Puedes seleccionar varias imágenes.</div>
        </div>

        <button type="submit" name="guardar" class="btn btn-success">Guardar Cambios</button>
        <a href="menu.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>
</body>
</html>