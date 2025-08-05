<?php

declare(strict_types=1);

namespace PhpAfipWs\Authorization;

/**
 * Clase que encapsula el Token de Autorización (TA) otorgado por AFIP.
 *
 * Este objeto contiene el token y la firma digital necesarios para autenticar
 * las solicitudes a los Web Services de AFIP. Es un objeto inmutable, lo que
 * garantiza que sus valores no cambien una vez creado.
 *
 * @psalm-immutable
 */
class TokenAuthorization
{
    /**
     * Constructor de la clase `TokenAuthorization`.
     *
     * @param  string  $token  El token de acceso (Token) proporcionado por AFIP.
     * @param  string  $firma  La firma digital (Sign) asociada al token, también proporcionada por AFIP.
     */
    public function __construct(
        private readonly string $token,
        private readonly string $firma
    ) {}

    /**
     * Obtiene el token de acceso (Token).
     *
     * @return string El valor del token de acceso.
     */
    public function obtenerToken(): string
    {
        return $this->token;
    }

    /**
     * Obtiene la firma digital (Sign) del token.
     *
     * @return string El valor de la firma digital.
     */
    public function obtenerFirma(): string
    {
        return $this->firma;
    }
}
