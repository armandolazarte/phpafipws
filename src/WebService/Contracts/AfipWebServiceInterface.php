<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService\Contracts;

use PhpAfipWs\Auth\TokenAuthorization;
use PhpAfipWs\Exception\AfipException;

/**
 * Interfaz base para todos los Web Services de AFIP.
 * Define los métodos comunes para la interacción con los servicios.
 */
interface AfipWebServiceInterface
{
    /**
     * Obtiene el Token de Autorización (TA) para el Web Service actual desde el WSAA.
     *
     * @return TokenAuthorization El objeto TokenAuthorization que contiene el token y la firma.
     *
     * @throws AfipException Si ocurre un error al obtener o crear el Token de Autorización (TA).
     */
    public function getTokenAutorizacion(): TokenAuthorization;

    /**
     * Ejecuta una operación en el Web Service de AFIP.
     *
     * @param  string  $operation  El nombre de la operación del Web Service a ejecutar.
     * @param  array<string, mixed>  $parametros  Los parámetros a enviar en la solicitud.
     * @return mixed Los resultados de la operación.
     *
     * @throws AfipException Si ocurre un error en la solicitud SOAP o en la respuesta de AFIP.
     */
    public function ejecutar(string $operation, array $parametros = []): mixed;

    /**
     * Obtiene el nombre del servicio para la autorización del token.
     *
     * @return string El nombre del servicio (ej. 'wsfe', 'ws_sr_padron_a4').
     */
    public function getNombreServicio(): string;
}
