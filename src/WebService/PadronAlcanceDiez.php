<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use PhpAfipWs\Exception\AfipException;
use PhpAfipWs\WebService\Contracts\PadronAlcanceDiezInterface;

/**
 * Clase para interactuar con el Web Service de Padrón Alcance 10 de AFIP (ws_sr_padron_a10).
 *
 * Permite consultar los datos de un contribuyente a partir de su CUIT.
 */
class PadronAlcanceDiez extends AfipWebService implements PadronAlcanceDiezInterface
{
    /** {@inheritdoc} */
    protected ?string $nombreWsdl = 'ws_sr_padron_a10-production.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlProduccion = 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA10';

    /** {@inheritdoc} */
    protected ?string $nombreWsdlPrueba = 'ws_sr_padron_a10.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlPrueba = 'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA10';

    /**
     * Consulta y obtiene los datos de un contribuyente registrados en AFIP.
     *
     * @param  int  $identificador  El CUIT del contribuyente a consultar.
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

        return $this->ejecutar('getPersona', $parametros);
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

    /**
     * {@inheritdoc}
     *
     * Devuelve el nombre del servicio para la autorización del token.
     */
    public function getNombreServicio(): string
    {
        return 'ws_sr_padron_a10';
    }
}
