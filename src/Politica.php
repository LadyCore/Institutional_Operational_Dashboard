<?php

declare(strict_types=1);

/**
 * Reglas de acceso del prototipo.
 * La aplicación definitiva debe ejecutar estas mismas decisiones en el servidor,
 * a partir del usuario autenticado, y no solo ocultando controles.
 */
final class Politica
{
    public static function veTodo(array $usuario): bool
    {
        return $usuario['perfil'] === 'admin' || $usuario['perfil'] === 'consulta';
    }

    public static function administraOperaciones(array $usuario): bool
    {
        return $usuario['perfil'] === 'admin';
    }

    public static function veActividad(array $usuario, array $actividad): bool
    {
        if (self::veTodo($usuario)) {
            return true;
        }

        return in_array($usuario['instituto_id'], $actividad['institutos'], true);
    }

    public static function editaTarea(array $usuario, array $tarea): bool
    {
        if ($usuario['perfil'] === 'admin') {
            return true;
        }

        return $usuario['perfil'] === 'responsable'
            && $tarea['instituto_id'] === $usuario['instituto_id'];
    }

    public static function creaRequerimiento(array $usuario): bool
    {
        return $usuario['perfil'] === 'admin' || $usuario['perfil'] === 'responsable';
    }

    public static function editaRequerimiento(array $usuario, array $requerimiento): bool
    {
        if ($usuario['perfil'] === 'admin') {
            return true;
        }

        if ($usuario['perfil'] !== 'responsable') {
            return false;
        }

        return $requerimiento['instituto_destino'] === $usuario['instituto_id']
            || $requerimiento['responsable_id'] === $usuario['id'];
    }

    public static function veHistorial(array $usuario): bool
    {
        return self::administraOperaciones($usuario);
    }

    public static function veAuditoria(array $usuario, array $fila): bool
    {
        return self::veHistorial($usuario);
    }
}
