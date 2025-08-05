<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Exception;
use PhpAfipWs\Enums\CodigosError;

/**
 * Excepción específica para errores de autenticación del SDK de AFIP.
 *
 * Se lanza cuando hay problemas con la autenticación, como:
 * - Token de acceso expirado
 * - Errores en la creación de TA
 * - Problemas de autenticación con WSAA
 */
class AutenticacionException extends AfipException
{
    /**
     * Constructor de AutenticacionException.
     *
     * @param  string  $mensaje  El mensaje de la excepción.
     * @param  string  $servicio  Servicio que falló la autenticación.
     * @param  string|null  $infoToken  Información del token (si disponible).
     * @param  int  $codigo  El código de la excepción (por defecto CodigosError::AUTENTICACION_GENERAL).
     * @param  Exception|null  $excepcion  La excepción anterior, si existe.
     * @param  array<string, mixed>  $contexto  Contexto adicional del error.
     */
    public function __construct(
        string $mensaje = '',
        private readonly string $servicio = '',
        private readonly ?string $infoToken = null,
        int $codigo = CodigosError::AUTENTICACION_GENERAL->value,
        ?Exception $excepcion = null,
        array $contexto = []
    ) {
        $contextoCompleto = array_merge($contexto, [
            'servicio' => $this->servicio,
            'info_token' => $this->infoToken,
        ]);

        parent::__construct(
            $mensaje,
            $codigo,
            $excepcion,
            'autenticacion',
            $contextoCompleto
        );
    }

    /**
     * Obtiene el servicio que falló la autenticación.
     *
     * @return string El nombre del servicio que falló
     */
    public function obtenerServicio(): string
    {
        return $this->servicio;
    }

    /**
     * Obtiene la información del token (si está disponible).
     *
     * @return string|null La información del token o null si no está disponible
     */
    public function obtenerInfoToken(): ?string
    {
        return $this->infoToken;
    }
}
