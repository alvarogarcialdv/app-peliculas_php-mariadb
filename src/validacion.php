<?php
declare(strict_types=1);

// PCRE comprueba que toda la cadena sea UTF-8 válido.
function esUtf8Valido(string $texto): bool
{
    $resultado = preg_match('//u', $texto);
    return $resultado === 1;
}

// Cuenta puntos de código Unicode, incluidos los saltos de línea.
function contarCaracteresUnicode(string $texto): ?int
{
    $resultado = preg_match_all('/./us', $texto);
    return $resultado === false ? null : $resultado;
}

function validarPelicula(array $entrada): array
{
    $datos = [];
    $errores = [];
    foreach (['titulo' => 200, 'director' => 120, 'genero' => 80, 'sinopsis' => 2000] as $campo => $limite) {
        $valor = $entrada[$campo] ?? '';
        $datos[$campo] = is_string($valor) ? trim($valor) : '';
        if (!is_string($valor) || !esUtf8Valido($datos[$campo])) {
            $errores[$campo] = 'Introduce un texto válido.';
        } elseif ($campo !== 'sinopsis' && $datos[$campo] === '') {
            $errores[$campo] = 'Este campo es obligatorio.';
        } else {
            $longitud = contarCaracteresUnicode($datos[$campo]);
            if ($longitud === null) {
                $errores[$campo] = 'Introduce un texto válido.';
            } elseif ($longitud > $limite) {
                $errores[$campo] = "No puede superar $limite caracteres.";
            }
        }
    }
    $datos['anio'] = is_string($entrada['anio'] ?? null) ? trim($entrada['anio']) : '';
    $maximo = (int) date('Y') + 1;
    if (!preg_match('/^[0-9]{4}$/D', $datos['anio']) || (int) $datos['anio'] < 1888 || (int) $datos['anio'] > $maximo) {
        $errores['anio'] = "Introduce un año entero entre 1888 y $maximo.";
    }
    return [$datos, $errores];
}
