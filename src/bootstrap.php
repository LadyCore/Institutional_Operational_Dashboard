<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));

error_reporting(E_ALL);
ini_set('display_errors', '1');

if (!date_default_timezone_set('America/Argentina/Buenos_Aires')) {
    date_default_timezone_set('UTC');
}

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

require ROOT . '/src/funciones.php';
require ROOT . '/src/Politica.php';
require ROOT . '/src/RepositorioOperativo.php';
require ROOT . '/src/ServicioOperativo.php';

function servicio(): ServicioOperativo
{
    static $servicio = null;
    if (!$servicio instanceof ServicioOperativo) {
        $servicio = new ServicioOperativo(new RepositorioFicticio());
    }

    return $servicio;
}
