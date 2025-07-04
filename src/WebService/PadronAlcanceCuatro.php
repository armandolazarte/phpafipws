<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use PhpAfipWs\Exception\AfipException;
use PhpAfipWs\WebService\Contracts\PadronAlcanceCuatroInterface;

/**
 * Clase para interactuar con el Web Service de Padrón Alcance 4 de AFIP (ws_sr_padron_a4).
 *
 * Permite consultar los datos de una persona (física o jurídica) a partir de su CUIT/CUIL.
 */
class PadronAlcanceCuatro extends AfipWebService implements PadronAlcanceCuatroInterface
{
    /** {@inheritdoc} */
    protected ?string $nombreWsdl = 'ws_sr_padron_a4-production.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlProduccion = 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA4';

    /** {@inheritdoc} */
    protected ?string $nombreWsdlPrueba = 'ws_sr_padron_a4.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlPrueba = 'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA4';

    /**
     * Consulta y obtiene los datos de una persona (física o jurídica) registrados en AFIP.
     *
     * @param  int  $identificador  El CUIT/CUIL de la persona a consultar.
     * @return mixed La respuesta del servidor con los datos de la persona.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getDatosPersona(int $identificador): mixed
    {
        $tokenAutorizacion = $this->getTokenAutorizacion();

        $parametros = [
            'token' => $tokenAutorizacion->getToken(),
            'sign' => $tokenAutorizacion->getSign(),
            'cuitRepresentada' => $this->afip->getCuit(),
            'idPersona' => $identificador,
        ];

        return $this->ejecutar('getPersona', $parametros);
    }

    /**
     * Obtiene los datos de múltiples personas a partir de sus CUIT/CUIL.
     *
     * @param  array<int>  $identificadores  Array de CUIT/CUIL de las personas a consultar.
     * @return mixed La respuesta del servidor con los datos de las personas.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getDatosPersonas(array $identificadores): mixed
    {
        $tokenAutorizacion = $this->getTokenAutorizacion();

        $parametros = [
            'token' => $tokenAutorizacion->getToken(),
            'sign' => $tokenAutorizacion->getSign(),
            'cuitRepresentada' => $this->afip->getCuit(),
            'idPersona' => $identificadores,
        ];

        return $this->ejecutar('getPersonaList', $parametros);
    }

    /**
     * {@inheritdoc}
     *
     * Devuelve el nombre del servicio para la autorización del token.
     */
    public function getNombreServicio(): string
    {
        return 'ws_sr_padron_a4';
    }
}
