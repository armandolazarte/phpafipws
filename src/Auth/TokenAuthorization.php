<?php

declare(strict_types=1);

namespace PhpAfipWs\Auth;

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
     * Constructor de TokenAuthorization.
     *
     * @param  string  $token  El token de acceso.
     * @param  string  $sign  La firma del token.
     */
    public function __construct(
        private string $token,
        private string $sign
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
        return $this->sign;
    }
}
