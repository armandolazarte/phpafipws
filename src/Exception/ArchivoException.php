<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Exception;
use PhpAfipWs\Enums\CodigosError;

/**
 * Excepción específica para errores de archivos del SDK de AFIP.
 *
 * Se lanza cuando hay problemas con operaciones de archivos, como:
 * - Archivos de certificado no encontrados
 * - Errores de lectura/escritura de archivos TA
 * - Problemas con archivos WSDL
 */
class ArchivoException extends AfipException
{
    /**
     * Constructor de ArchivoException.
     *
     * @param  string  $mensaje  El mensaje de la excepción.
     * @param  string  $rutaArchivo  Ruta del archivo problemático.
     * @param  string  $operacion  Operación que falló (read, write, etc.).
     * @param  int  $codigo  El código de la excepción (por defecto CodigosError::ARCHIVO_GENERAL).
     * @param  Exception|null  $excepcion  La excepción anterior, si existe.
     * @param  array<string, mixed>  $contexto  Contexto adicional del error.
     */
    public function __construct(
        string $mensaje = '',
        private string $rutaArchivo = '',
        private string $operacion = '',
        int $codigo = CodigosError::ARCHIVO_GENERAL->value,
        ?Exception $excepcion = null,
        array $contexto = []
    ) {
        $contextoCompleto = array_merge($contexto, [
            'ruta_archivo' => $this->rutaArchivo,
            'operacion' => $this->operacion,
        ]);

        parent::__construct(
            $mensaje,
            $codigo,
            $excepcion,
            'archivo',
            $contextoCompleto
        );
    }

    /**
     * Obtiene la ruta del archivo que causó el error.
     *
     * @return string La ruta del archivo problemático
     */
    public function obtenerRutaArchivo(): string
    {
        return $this->rutaArchivo;
    }

    /**
     * Obtiene la operación que falló.
     *
     * @return string La operación que falló (read, write, etc.)
     */
    public function obtenerOperacion(): string
    {
        return $this->operacion;
    }
}
