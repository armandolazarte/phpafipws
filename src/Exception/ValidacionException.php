<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Exception;
use PhpAfipWs\Enums\CodigosError;

/**
 * Excepción específica para errores de validación del SDK de AFIP.
 *
 * Se lanza cuando hay problemas con la validación de datos, como:
 * - CUIT inválido
 * - Parámetros de entrada incorrectos
 * - Datos que no cumplen con el formato esperado
 */
class ValidacionException extends AfipException
{
    /**
     * Constructor de ValidacionException.
     *
     * @param  string  $mensaje  El mensaje de la excepción.
     * @param  string  $campo  Campo que falló la validación.
     * @param  mixed  $valor  Valor que falló la validación.
     * @param  string  $regla  Regla de validación que falló.
     * @param  int  $codigo  El código de la excepción (por defecto CodigosError::VALIDACION_GENERAL).
     * @param  Exception|null  $excepcion  La excepción anterior, si existe.
     * @param  array<string, mixed>  $contexto  Contexto adicional del error.
     */
    public function __construct(
        string $mensaje = '',
        private string $campo = '',
        private mixed $valor = null,
        private string $regla = '',
        int $codigo = CodigosError::VALIDACION_GENERAL->value,
        ?Exception $excepcion = null,
        array $contexto = []
    ) {
        $contextoCompleto = array_merge($contexto, [
            'campo' => $this->campo,
            'valor' => $this->valor,
            'regla' => $this->regla,
        ]);

        parent::__construct(
            $mensaje,
            $codigo,
            $excepcion,
            'validacion',
            $contextoCompleto
        );
    }

    /**
     * Obtiene el campo que falló la validación.
     *
     * @return string El nombre del campo que falló la validación
     */
    public function obtenerCampo(): string
    {
        return $this->campo;
    }

    /**
     * Obtiene el valor que falló la validación.
     *
     * @return mixed El valor que falló la validación
     */
    public function obtenerValor(): mixed
    {
        return $this->valor;
    }

    /**
     * Obtiene la regla de validación que falló.
     *
     * @return string La regla de validación que falló
     */
    public function obtenerRegla(): string
    {
        return $this->regla;
    }
}
