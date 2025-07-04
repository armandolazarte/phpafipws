<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService\Contracts;

use PhpAfipWs\Exception\AfipException;

/**
 * Interfaz para el Web Service de Padrón Alcance 10 de AFIP (ws_sr_padron_a10).
 */
interface PadronAlcanceDiezInterface extends AfipWebServiceInterface
{
    /**
     * Consulta y obtiene los datos de un contribuyente registrados en AFIP.
     *
     * @param  int  $identificador  El CUIT del contribuyente a consultar.
     * @return mixed La respuesta del servidor con los datos del contribuyente.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getDatosContribuyente(int $identificador): mixed;

    /**
     * Consulta el estado de los servidores de AFIP para este servicio.
     *
     * @return mixed La respuesta del servidor con el estado.
     *
     * @throws AfipException Si ocurre un error en la comunicación SOAP.
     */
    public function getEstadoServidor(): mixed;
}
