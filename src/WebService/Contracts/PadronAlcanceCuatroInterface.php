<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService\Contracts;

use PhpAfipWs\Exception\AfipException;

/**
 * Interfaz para el Web Service de Padrón Alcance 4 de AFIP (ws_sr_padron_a4).
 */
interface PadronAlcanceCuatroInterface extends AfipWebServiceInterface
{
    /**
     * Consulta y obtiene los datos de una persona (física o jurídica) registrados en AFIP.
     *
     * @param  int  $identificador  El CUIT/CUIL de la persona a consultar.
     * @return mixed La respuesta del servidor con los datos de la persona.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getDatosPersona(int $identificador): mixed;

    /**
     * Obtiene los datos de múltiples personas a partir de sus CUIT/CUIL.
     *
     * @param  array<int>  $identificadores  Array de CUIT/CUIL de las personas a consultar.
     * @return mixed La respuesta del servidor con los datos de las personas.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getDatosPersonas(array $identificadores): mixed;
}
