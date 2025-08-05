<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Exception;
use PhpAfipWs\Enums\CodigosError;

/**
 * Excepción específica para errores de comunicación SOAP del SDK de AFIP.
 *
 * Se lanza cuando hay problemas con la comunicación SOAP, como:
 * - Fallos SOAP de servicios web
 * - Errores de comunicación con AFIP
 * - Problemas de red
 */
class SoapException extends AfipException
{
    /**
     * Constructor de SoapException.
     *
     * @param  string  $mensaje  El mensaje de la excepción.
     * @param  string  $codigoFalloSoap  Código de fallo SOAP.
     * @param  string  $mensajeFalloSoap  Mensaje de fallo SOAP.
     * @param  string  $operacion  Operación SOAP que falló.
     * @param  int  $codigo  El código de la excepción (por defecto CodigosError::SOAP_GENERAL).
     * @param  Exception|null  $excepcion  La excepción anterior, si existe.
     * @param  array<string, mixed>  $contexto  Contexto adicional del error.
     */
    public function __construct(
        string $mensaje = '',
        private readonly string $codigoFalloSoap = '',
        private readonly string $mensajeFalloSoap = '',
        private readonly string $operacion = '',
        int $codigo = CodigosError::SOAP_GENERAL->value,
        ?Exception $excepcion = null,
        array $contexto = []
    ) {
        $contextoCompleto = array_merge($contexto, [
            'codigo_fallo_soap' => $this->codigoFalloSoap,
            'mensaje_fallo_soap' => $this->mensajeFalloSoap,
            'operacion' => $this->operacion,
        ]);

        parent::__construct(
            $mensaje,
            $codigo,
            $excepcion,
            'soap',
            $contextoCompleto
        );
    }

    /**
     * Obtiene el código de fallo SOAP.
     *
     * @return string El código de fallo SOAP
     */
    public function obtenerCodigoFalloSoap(): string
    {
        return $this->codigoFalloSoap;
    }

    /**
     * Obtiene el mensaje de fallo SOAP.
     *
     * @return string El mensaje de fallo SOAP original
     */
    public function obtenerMensajeFalloSoap(): string
    {
        return $this->mensajeFalloSoap;
    }

    /**
     * Obtiene la operación SOAP que falló.
     *
     * @return string El nombre de la operación SOAP que causó el error
     */
    public function obtenerOperacion(): string
    {
        return $this->operacion;
    }
}
