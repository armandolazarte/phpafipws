<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Exception;
use PhpAfipWs\Enums\CodigosError;

/**
 * Excepción específica para errores de configuración del SDK de AFIP.
 *
 * Se lanza cuando hay problemas con la configuración del SDK, como:
 * - Opciones de configuración faltantes o inválidas
 * - Archivos de configuración no encontrados
 * - Valores de configuración incorrectos
 */
class ConfiguracionException extends AfipException
{
    /**
     * Constructor de ConfiguracionException.
     *
     * @param  string  $mensaje  El mensaje de la excepción.
     * @param  string  $campoConfiguracion  Campo de configuración problemático.
     * @param  mixed  $valorProporcionado  Valor proporcionado (si aplica).
     * @param  int  $codigo  El código de la excepción (por defecto CodigosError::CONFIGURACION_GENERAL).
     * @param  Exception|null  $excepcion  La excepción anterior, si existe.
     * @param  array<string, mixed>  $contexto  Contexto adicional del error.
     */
    public function __construct(
        string $mensaje = '',
        private readonly string $campoConfiguracion = '',
        private readonly mixed $valorProporcionado = null,
        int $codigo = CodigosError::CONFIGURACION_GENERAL->value,
        ?Exception $excepcion = null,
        array $contexto = []
    ) {
        $contextoCompleto = array_merge($contexto, [
            'campo_configuracion' => $this->campoConfiguracion,
            'valor_proporcionado' => $this->valorProporcionado,
        ]);

        parent::__construct(
            $mensaje,
            $codigo,
            $excepcion,
            'configuracion',
            $contextoCompleto
        );
    }

    /**
     * Obtiene el campo de configuración que causó el error.
     *
     * @return string El nombre del campo de configuración problemático
     */
    public function obtenerCampoConfiguracion(): string
    {
        return $this->campoConfiguracion;
    }

    /**
     * Obtiene el valor que fue proporcionado para el campo de configuración.
     *
     * @return mixed El valor proporcionado (puede ser null si no se proporcionó)
     */
    public function obtenerValorProporcionado(): mixed
    {
        return $this->valorProporcionado;
    }
}
