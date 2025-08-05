<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Exception;
use PhpAfipWs\Enums\CodigosError;

/**
 * Excepción específica para errores relacionados con certificados digitales y CSR.
 *
 * Esta excepción se lanza cuando ocurren problemas durante la generación, lectura,
 * validación o procesamiento de certificados X.509 y Certificate Signing Requests (CSR).
 */
class CertificadoException extends AfipException
{
    /**
     * Constructor de CertificadoException.
     *
     * @param  string  $mensaje  Mensaje descriptivo del error.
     * @param  string  $operacion  La operación que se estaba realizando cuando ocurrió el error.
     * @param  mixed  $infoCertificado  Información adicional sobre el certificado o CSR (si disponible).
     * @param  int  $codigo  Código de error específico (por defecto CodigosError::CERTIFICADO_ERROR_GENERAR_CSR).
     * @param  Exception|null  $excepcion  La excepción anterior, si existe.
     * @param  array<string, mixed>  $contexto  Contexto adicional del error.
     */
    public function __construct(
        string $mensaje = '',
        private readonly string $operacion = '',
        private readonly mixed $infoCertificado = null,
        int $codigo = CodigosError::CERTIFICADO_ERROR_GENERAR_CSR->value,
        ?Exception $excepcion = null,
        array $contexto = []
    ) {
        $contextoCompleto = array_merge($contexto, [
            'operacion' => $this->operacion,
            'info_certificado' => $this->infoCertificado,
        ]);

        parent::__construct(
            $mensaje,
            $codigo,
            $excepcion,
            'certificado',
            $contextoCompleto
        );
    }

    /**
     * Obtiene la operación que se estaba realizando cuando ocurrió el error.
     *
     * @return string La operación que se estaba realizando.
     */
    public function obtenerOperacion(): string
    {
        return $this->operacion;
    }

    /**
     * Obtiene información adicional sobre el certificado o CSR.
     *
     * @return mixed Información adicional sobre el certificado o CSR.
     */
    public function obtenerInfoCertificado(): mixed
    {
        return $this->infoCertificado;
    }

    /**
     * Obtiene el campo específico que causó el error de validación, si está disponible.
     *
     * @return string|null El nombre del campo o null si no está definido.
     */
    public function getCampo(): ?string
    {
        $campo = $this->contexto['campo'] ?? null;

        return is_string($campo) ? $campo : null;
    }
}
