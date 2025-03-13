<?php
namespace Core;

/**
 * Obtiene el nombre de la clase de un parámetro de reflexión
 * Compatible con PHP 7 y PHP 8
 *
 * @param \ReflectionParameter $param El parámetro a analizar
 * @return string|null El nombre de la clase o null si no hay clase
 */
function getParameterClassName(\ReflectionParameter $param) {
    if (PHP_VERSION_ID >= 80000) {
        $type = $param->getType();
        if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
            return $type->getName();
        }
        return null;
    } else {
        $class = $param->getClass();
        return $class ? $class->getName() : null;
    }
}