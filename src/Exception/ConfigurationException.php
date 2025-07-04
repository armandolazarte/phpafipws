<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

/**
 * Excepción para errores relacionados con la configuración de la librería,
 * como archivos de certificado o clave no encontrados, o parámetros de configuración inválidos.
 */
class ConfigurationException extends AfipException {}
