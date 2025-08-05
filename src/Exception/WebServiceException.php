<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Exception;
use PhpAfipWs\Enums\CodigosError;

/**
 * Excepción específica para errores de servicios web del SDK de AFIP.
 *
 * Se lanza cuando hay problemas específicos con servicios web, como:
 * - Errores específicos de servicios web
 * - Respuestas inesperadas de AFIP
 * - Problemas de configuración de servicios
 */
class WebServiceException extends AfipException
{
    /**
     * Constructor de ServicioWebException.
     *
     * @param  string  $mensaje  El mensaje de la excepción.
     * @param  string  $nombreServicio  Nombre del servicio web.
     * @param  string  $operacion  Operación que falló.
     * @param  array<string, mixed>|null  $parametros  Parámetros enviados al servicio.
     * @param  int  $codigo  El código de la excepción (por defecto CodigosError::SERVICIO_WEB_GENERAL).
     * @param  Exception|null  $excepcion  La excepción anterior, si existe.
     * @param  array<string, mixed>  $contexto  Contexto adicional del error.
     */
    public function __construct(
        string $mensaje = '',
        private readonly string $nombreServicio = '',
        private readonly string $operacion = '',
        private readonly ?array $parametros = null,
        int $codigo = CodigosError::SERVICIO_WEB_GENERAL->value,
        ?Exception $excepcion = null,
        array $contexto = []
    ) {
        $contextoCompleto = array_merge($contexto, [
            'nombre_servicio' => $this->nombreServicio,
            'operacion' => $this->operacion,
            'parametros' => $this->parametros,
        ]);

        parent::__construct(
            $mensaje,
            $codigo,
            $excepcion,
            'servicio_web',
            $contextoCompleto
        );
    }

    /**
     * Obtiene el nombre del servicio web.
     *
     * @return string El nombre del servicio web
     */
    public function obtenerNombreServicio(): string
    {
        return $this->nombreServicio;
    }

    /**
     * Obtiene la operación que falló.
     *
     * @return string El nombre de la operación que causó el error
     */
    public function obtenerOperacion(): string
    {
        return $this->operacion;
    }

    /**
     * Obtiene los parámetros enviados al servicio.
     *
     * @return array<string, mixed>|null Los parámetros enviados o null si no hay
     */
    public function obtenerParametros(): ?array
    {
        return $this->parametros;
    }
}
