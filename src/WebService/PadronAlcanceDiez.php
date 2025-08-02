<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use PhpAfipWs\Exception\AutenticacionException;
use PhpAfipWs\Exception\PadronException;
use PhpAfipWs\Exception\SoapException;
use PhpAfipWs\Exception\WebServiceException;

/**
 * Clase para interactuar con el Web Service de Padrón Alcance 10 de AFIP (ws_sr_padron_a10).
 *
 * Permite consultar los datos de un contribuyente a partir de su CUIT.
 * Extiende de AfipWebService y proporciona métodos específicos para consultar datos en el padrón A10.
 *
 * @see AfipWebService
 */
class PadronAlcanceDiez extends AfipWebService
{
    /** {@inheritdoc} */
    protected ?string $nombreArchivoWSDL = 'ws_sr_padron_a10-production.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlServicio = 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA10';

    /** {@inheritdoc} */
    protected ?string $nombreArchivoWSDLPrueba = 'ws_sr_padron_a10.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlServicioPrueba = 'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA10';

    /**
     * Consulta y obtiene los datos de un contribuyente registrados en AFIP.
     *
     * @param  int  $identificador  El CUIT del contribuyente a consultar (sin guiones).
     * @return mixed Datos del contribuyente consultado.
     *
     * @throws AutenticacionException Si falla la autenticación con WSAA.
     * @throws SoapException Si ocurre un error en la comunicación SOAP.
     * @throws WebServiceException Si el web service devuelve un error.
     * @throws PadronException Si ocurre un error específico del padrón.
     */
    public function obtenerDetallesContribuyente(int $identificador): mixed
    {
        $tokenAutorizacion = $this->obtenerTokenAutorizacion();

        $params = [
            'token' => $tokenAutorizacion->obtenerToken(),
            'sign' => $tokenAutorizacion->obtenerFirma(),
            'cuitRepresentada' => $this->afip->obtenerCuit(),
            'idPersona' => $identificador,
        ];

        return $this->ejecutarSolicitud('getPersona', $params);
    }

    /**
     * Consulta el estado de los servidores de AFIP para este servicio.
     *
     * @return mixed La respuesta del servidor con el estado.
     *
     * @throws SoapException Si ocurre un error en la comunicación SOAP.
     * @throws WebServiceException Si el web service devuelve un error.
     */
    public function obtenerEstadoServidor(): mixed
    {
        return $this->ejecutarSolicitud('dummy');
    }

    /**
     * {@inheritdoc}
     *
     * @return string Nombre del servicio AFIP para autorización (ws_sr_padron_a10).
     */
    protected function obtenerNombreServicio(): string
    {
        return 'ws_sr_padron_a10';
    }
}
