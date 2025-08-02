<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use DateTimeImmutable;
use Exception;

/**
 * Excepción personalizada para el SDK de AFIP.
 *
 * Se utiliza para lanzar errores específicos de la librería, facilitando
 * la captura y manejo de errores por parte del consumidor del SDK.
 * Implementa la interfaz ContextoExceptionInterface para proporcionar información
 * contextual consistente.
 */
class AfipException extends Exception implements ContextoExceptionInterface
{
    /**
     * Marca de tiempo cuando ocurrió el error.
     */
    /**
     * Marca de tiempo cuando ocurrió el error.
     */
    protected DateTimeImmutable $marcaTiempo;

    /**
     * Identificador único del error.
     */
    protected string $id;

    /**
     * Constructor de AfipExcepcion.
     *
     * @param  string  $mensaje  El mensaje de la excepción.
     * @param  int  $codigo  El código de la excepción.
     * @param  Exception|null  $excepcion  La excepción anterior, si existe, para encadenamiento de excepciones.
     * @param  string  $tipoError  El tipo de error (por defecto 'general').
     * @param  array<string, mixed>  $contexto  Contexto adicional del error.
     */
    public function __construct(
        string $mensaje = '',
        int $codigo = 0,
        ?Exception $excepcion = null,
        protected string $tipoError = 'general',
        protected array $contexto = []
    ) {
        parent::__construct($mensaje, $codigo, $excepcion);
        $this->marcaTiempo = new DateTimeImmutable;
        $this->id = $this->generarId();
    }

    /**
     * Obtiene el tipo de error de la excepción.
     *
     * @return string El tipo de error
     */
    public function obtenerTipoError(): string
    {
        return $this->tipoError;
    }

    /**
     * Obtiene el contexto adicional del error.
     *
     * @return array<string, mixed> Array asociativo con información contextual específica
     */
    public function obtenerContexto(): array
    {
        return $this->contexto;
    }

    /**
     * Obtiene la marca de tiempo cuando ocurrió el error.
     *
     * @return DateTimeImmutable Timestamp inmutable del momento del error
     */
    public function obtenerMarcaTiempo(): DateTimeImmutable
    {
        return $this->marcaTiempo;
    }

    /**
     * Obtiene un identificador único para esta instancia de error.
     *
     * @return string Identificador único del error
     */
    public function obtenerId(): string
    {
        return $this->id;
    }

    /**
     * Genera un identificador único para el error.
     *
     * @return string Identificador único basado en timestamp y random
     */
    private function generarId(): string
    {
        return uniqid($this->tipoError.'_', true);
    }
}
