<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

use DateTimeImmutable;

/**
 * Interfaz para estandarizar el manejo de contexto en excepciones.
 *
 * Define los métodos que deben implementar todas las excepciones específicas
 * para proporcionar información contextual consistente.
 */
interface ContextoExceptionInterface
{
    /**
     * Obtiene el tipo de error de la excepción.
     *
     * @return string El tipo de error (ej: 'configuracion', 'validacion', etc.)
     */
    public function obtenerTipoError(): string;

    /**
     * Obtiene el contexto adicional del error.
     *
     * @return array<string, mixed> Array asociativo con información contextual específica
     */
    public function obtenerContexto(): array;

    /**
     * Obtiene la marca de tiempo cuando ocurrió el error.
     *
     * @return DateTimeImmutable Timestamp inmutable del momento del error
     */
    public function obtenerMarcaTiempo(): DateTimeImmutable;

    /**
     * Obtiene un identificador único para esta instancia de error.
     *
     * @return string Identificador único del error
     */
    public function obtenerId(): string;
}
