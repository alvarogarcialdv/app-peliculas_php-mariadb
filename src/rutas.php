<?php
declare(strict_types=1);

function atenderPeticion(mixed $ruta): void
{
    $metodo = $_SERVER['REQUEST_METHOD'];
    if (!is_string($ruta)) {
        mostrar('error', ['titulo' => 'Recurso no encontrado', 'mensaje' => 'La página solicitada no existe.'], 404);
        return;
    }
    if ($metodo === 'GET' && $ruta === '/') {
        redirigir('/peliculas');
        return;
    }
    $partes = [];
    $coincide = preg_match('#^/peliculas/([1-9][0-9]{0,9})(/editar|/eliminar)?$#D', $ruta, $partes);
    $id = $coincide ? $partes[1] : null;
    $sufijo = $partes[2] ?? '';
    $crear = $metodo === 'POST' && $ruta === '/peliculas';
    $listar = $metodo === 'GET' && $ruta === '/peliculas';
    $nueva = $metodo === 'GET' && $ruta === '/peliculas/nueva';
    $rutaValida = $coincide && (($metodo === 'GET' && in_array($sufijo, ['', '/editar'], true))
        || ($metodo === 'POST' && in_array($sufijo, ['', '/eliminar'], true)));
    if (!$crear && !$listar && !$nueva && !$rutaValida) {
        mostrar('error', ['titulo' => 'Recurso no encontrado', 'mensaje' => 'La página solicitada no existe.'], 404);
        return;
    }
    if ($metodo === 'POST') {
        $token = $_POST['csrf'] ?? null;
        if (!is_string($token) || !hash_equals($_SESSION['csrf'], $token)) {
            mostrar('error', ['titulo' => 'Formulario caducado', 'mensaje' => 'Vuelve a abrir el formulario e inténtalo de nuevo.'], 403);
            return;
        }
    }
    if ($nueva) {
        mostrar('formulario', ['titulo' => 'Nueva película', 'pelicula' => [], 'errores' => [], 'accion' => '/peliculas']);
        return;
    }
    if ($listar) {
        $busqueda = $_GET['busqueda'] ?? '';
        if (!is_string($busqueda) || !esUtf8Valido($busqueda)) {
            mostrar('error', ['titulo' => 'Búsqueda inválida', 'mensaje' => 'Introduce un texto de búsqueda válido.'], 422);
            return;
        }
        $busqueda = trim($busqueda);
        mostrar('listado', ['titulo' => 'Películas', 'peliculas' => listarPeliculas(conexion(), $busqueda), 'busqueda' => $busqueda]);
        return;
    }
    $bd = conexion();
    $pelicula = $id === null ? [] : obtenerPelicula($bd, $id);
    if ($pelicula === false) {
        mostrar('error', ['titulo' => 'Película no encontrada', 'mensaje' => 'La película solicitada no existe.'], 404);
        return;
    }
    if ($metodo === 'POST' && $sufijo === '/eliminar') {
        eliminarPelicula($bd, $id);
        $_SESSION['mensaje'] = 'Película eliminada correctamente.';
        redirigir('/peliculas');
        return;
    }
    if ($metodo === 'POST') {
        [$datos, $errores] = validarPelicula($_POST);
        if ($errores !== []) {
            mostrar('formulario', ['titulo' => $crear ? 'Nueva película' : 'Editar película', 'pelicula' => $datos,
                'errores' => $errores, 'accion' => $crear ? '/peliculas' : '/peliculas/' . $id], 422);
            return;
        }
        $id = guardarPelicula($bd, $datos, $id);
        $_SESSION['mensaje'] = $crear ? 'Película creada correctamente.' : 'Película actualizada correctamente.';
        redirigir('/peliculas/' . $id);
        return;
    }
    if ($sufijo === '/editar') {
        mostrar('formulario', ['titulo' => 'Editar película', 'pelicula' => $pelicula, 'errores' => [], 'accion' => '/peliculas/' . $id]);
    } else {
        mostrar('detalle', ['titulo' => $pelicula['titulo'], 'pelicula' => $pelicula]);
    }
}
