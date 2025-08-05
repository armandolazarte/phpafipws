<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use Exception;
use PhpAfipWs\Enums\CodigosError;

/**
 * Excepción específica para errores de los servicios de Padrón.
 *
 * Se lanza cuando hay problemas específicos con servicios de padrón, como:
 * - Errores en consultas de contribuyentes
 * - Problemas con CUIT/CUIL inválidos
 * - Errores de alcance de padrón
 * - Problemas con datos de contribuyentes
 */
class PadronException extends WebServiceException
{
    /**
     * Constructor de PadronException.
     *
     * @param  string  $mensaje  El mensaje de la excepción.
     * @param  string  $operacion  Operación que falló (ej: 'getPersona', 'getPersonaList').
     * @param  array<string, mixed>|null  $parametros  Parámetros enviados al servicio.
     * @param  string|null  $cuit  CUIT/CUIL involucrado en el error.
     * @param  int|null  $alcance  Alcance del padrón (4, 5, 10, 13).
     * @param  string|null  $tipoConsulta  Tipo de consulta realizada.
     * @param  int  $codigo  El código de la excepción (por defecto CodigosError::SERVICIO_WEB_PADRON_ERROR).
     * @param  Exception|null  $excepcion  La excepción anterior, si existe.
     * @param  array<string, mixed>  $contexto  Contexto adicional del error.
     */
    public function __construct(
        string $mensaje = '',
        string $operacion = '',
        ?array $parametros = null,
        private readonly ?string $cuit = null,
        private readonly ?int $alcance = null,
        private readonly ?string $tipoConsulta = null,
        int $codigo = CodigosError::SERVICIO_WEB_PADRON_ERROR->value,
        ?Exception $excepcion = null,
        array $contexto = []
    ) {
        $nombreServicio = $this->determinarNombreServicio($alcance);

        $contextoCompleto = array_merge($contexto, [
            'cuit' => $this->cuit,
            'alcance' => $this->alcance,
            'tipo_consulta' => $this->tipoConsulta,
        ]);

        parent::__construct(
            $mensaje,
            $nombreServicio,
            $operacion,
            $parametros,
            $codigo,
            $excepcion,
            $contextoCompleto
        );
    }

    /**
     * Obtiene el CUIT/CUIL involucrado en el error.
     *
     * @return string|null El CUIT/CUIL o null si no aplica
     */
    public function obtenerCuit(): ?string
    {
        return $this->cuit;
    }

    /**
     * Obtiene el alcance del padrón.
     *
     * @return int|null El alcance del padrón o null si no aplica
     */
    public function obtenerAlcance(): ?int
    {
        return $this->alcance;
    }

    /**
     * Obtiene el tipo de consulta realizada.
     *
     * @return string|null El tipo de consulta o null si no aplica
     */
    public function obtenerTipoConsulta(): ?string
    {
        return $this->tipoConsulta;
    }

    /**
     * Determina el nombre del servicio basado en el alcance.
     *
     * @param  int|null  $alcance  El alcance del padrón
     * @return string El nombre del servicio
     */
    private function determinarNombreServicio(?int $alcance): string
    {
        return match ($alcance) {
            4 => 'PadronAlcanceCuatro',
            5 => 'PadronAlcanceCinco',
            10 => 'PadronAlcanceDiez',
            13 => 'PadronAlcanceTrece',
            default => 'Padron',
        };
    }
}
