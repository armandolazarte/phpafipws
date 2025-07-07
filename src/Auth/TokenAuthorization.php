<?php

declare(strict_types=1);

namespace PhpAfipWs\Auth;

use DateTimeImmutable;

/**
 * Clase que representa el Token de Autorización (TA) de AFIP.
 *
 * Contiene el token y la firma necesarios para autenticar las solicitudes
 * a los Web Services. Es un objeto inmutable.
 *
 * @psalm-immutable
 */
class TokenAuthorization
{
    /**
     * Constructor de TokenAutorizacion.
     *
     * @param  string  $token  El token de acceso.
     * @param  string  $firma  La firma del token.
     * @param  DateTimeImmutable  $tiempoExpiracion  El momento en que expira el token.
     */
    public function __construct(
        private string $token,
        private string $firma,
        private DateTimeImmutable $tiempoExpiracion
    ) {}

    /**
     * Obtiene el token de acceso.
     *
     * @return string El token.
     */
    public function getToken(): string
    {
        return $this->token;
    }

    /**
     * Obtiene la firma del token.
     *
     * @return string La firma.
     */
    public function getSign(): string
    {
        return $this->firma;
    }

    /**
     * Obtiene el momento de expiración del token.
     *
     * @return DateTimeImmutable El momento de expiración.
     */
    public function obtenerTiempoExpiracion(): DateTimeImmutable
    {
        return $this->tiempoExpiracion;
    }

    /**
     * Verifica si el token ha expirado.
     *
     * @param  int  $margenSegundos  Margen de segundos antes de la expiración real para considerarlo expirado.
     * @return bool True si el token ha expirado o está próximo a expirar dentro del margen.
     */
    public function estaExpirado(int $margenSegundos = 0): bool
    {
        $tiempoActual = new DateTimeImmutable();

        return $tiempoActual->getTimestamp() + $margenSegundos >= $this->tiempoExpiracion->getTimestamp();
    }
}
