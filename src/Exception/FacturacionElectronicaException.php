<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Exception;
use PhpAfipWs\Enums\CodigosError;

/**
 * Excepción específica para errores del servicio de Facturación Electrónica.
 *
 * Se lanza cuando hay problemas específicos con el servicio de facturación electrónica, como:
 * - Errores en la generación de comprobantes
 * - Problemas con CAE (Código de Autorización Electrónico)
 * - Errores de validación específicos de facturación
 * - Problemas con puntos de venta
 */
class FacturacionElectronicaException extends WebServiceException
{
    /**
     * Constructor de FacturacionElectronicaException.
     *
     * @param  string  $mensaje  El mensaje de la excepción.
     * @param  string  $operacion  Operación que falló (ej: 'FECAESolicitar', 'FECompUltimoAutorizado').
     * @param  array<string, mixed>|null  $parametros  Parámetros enviados al servicio.
     * @param  string|null  $tipoComprobante  Tipo de comprobante involucrado.
     * @param  int|null  $puntoVenta  Punto de venta involucrado.
     * @param  string|null  $cae  CAE relacionado con el error (si aplica).
     * @param  int  $codigo  El código de la excepción (por defecto CodigosError::SERVICIO_WEB_FACTURACION_ERROR).
     * @param  Exception|null  $excepcion  La excepción anterior, si existe.
     * @param  array<string, mixed>  $contexto  Contexto adicional del error.
     */
    public function __construct(
        string $mensaje = '',
        string $operacion = '',
        ?array $parametros = null,
        private ?string $tipoComprobante = null,
        private ?int $puntoVenta = null,
        private ?string $cae = null,
        int $codigo = CodigosError::SERVICIO_WEB_FACTURACION_ERROR->value,
        ?Exception $excepcion = null,
        array $contexto = []
    ) {
        $contextoCompleto = array_merge($contexto, [
            'tipo_comprobante' => $this->tipoComprobante,
            'punto_venta' => $this->puntoVenta,
            'cae' => $this->cae,
        ]);

        parent::__construct(
            $mensaje,
            'FacturacionElectronica',
            $operacion,
            $parametros,
            $codigo,
            $excepcion,
            $contextoCompleto
        );
    }

    /**
     * Obtiene el tipo de comprobante involucrado en el error.
     *
     * @return string|null El tipo de comprobante o null si no aplica
     */
    public function obtenerTipoComprobante(): ?string
    {
        return $this->tipoComprobante;
    }

    /**
     * Obtiene el punto de venta involucrado en el error.
     *
     * @return int|null El punto de venta o null si no aplica
     */
    public function obtenerPuntoVenta(): ?int
    {
        return $this->puntoVenta;
    }

    /**
     * Obtiene el CAE relacionado con el error.
     *
     * @return string|null El CAE o null si no aplica
     */
    public function obtenerCae(): ?string
    {
        return $this->cae;
    }
}
