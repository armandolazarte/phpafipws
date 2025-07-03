<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use PhpAfipWs\Exception\AfipException;

/**
 * Clase para interactuar con el Web Service de Constancia de Inscripción de AFIP (ws_sr_constancia_inscripcion).
 *
 * Permite consultar los datos de un contribuyente a partir de su CUIT.
 */
class PadronConstanciaInscripcion extends AfipWebService
{
    /** {@inheritdoc} */
    protected ?string $nombreWsdl = 'ws_sr_constancia_inscripcion-production.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlProduccion = 'https://aws.afip.gov.ar/sr-padron/webservices/constanciaInscripcion';

    /** {@inheritdoc} */
    protected ?string $nombreWsdlPrueba = 'ws_sr_constancia_inscripcion.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlPrueba = 'https://awshomo.afip.gov.ar/sr-padron/webservices/constanciaInscripcion';

    /**
     * Consulta y obtiene los datos de un contribuyente registrados en AFIP.
     *
     * @param  int  $identifier  El CUIT del contribuyente a consultar.
     * @return mixed La respuesta del servidor con los datos del contribuyente.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getDatosContribuyente(int $identificador): mixed
    {
        $tokenAutorizacion = $this->getTokenAutorizacion();

        $parametros = [
            'token' => $tokenAutorizacion->getToken(),
            'sign' => $tokenAutorizacion->getSign(),
            'cuitRepresentada' => $this->afip->getCuit(),
            'idPersona' => $identificador,
        ];

        return $this->ejecutar('getPersona_v2', $parametros);
    }

    /**
     * Obtiene los datos de multiples contribuyentes a partir de sus CUIT.
     *
     * @param  array<int>  $identifiers  Array de CUIT de los contribuyentes a consultar.
     * @return mixed La respuesta del servidor con los datos de los contribuyentes.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getTaxpayersDetails(array $identifiers): mixed
    {
        $tokenAutorizacion = $this->getTokenAutorizacion();

        $parametros = [
            'token' => $tokenAutorizacion->getToken(),
            'sign' => $tokenAutorizacion->getSign(),
            'cuitRepresentada' => $this->afip->getCuit(),
            'idPersona' => $identifiers,
        ];

        return $this->ejecutar('getPersonaList_v2', $parametros);
    }

    /**
     * Consulta el estado de los servidores de AFIP para este servicio.
     *
     * @return mixed La respuesta del servidor con el estado.
     *
     * @throws AfipException Si ocurre un error en la comunicación SOAP.
     */
    public function getEstadoServidor(): mixed
    {
        return $this->ejecutar('dummy');
    }

    /** {@inheritdoc} */
    protected function getNombreServicio(): string
    {
        return 'ws_sr_constancia_inscripcion';
    }
}
