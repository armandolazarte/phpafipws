<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use PhpAfipWs\Exception\AutenticacionException;
use PhpAfipWs\Exception\PadronException;
use PhpAfipWs\Exception\SoapException;
use PhpAfipWs\Exception\WebServiceException;

/**
 * Clase para interactuar con el Web Service de Padrón Alcance 4 de AFIP (ws_sr_padron_a4).
 *
 * Permite consultar los datos de una persona (física o jurídica) a partir de su CUIT/CUIL.
 * Extiende de AfipWebService y proporciona métodos específicos para consultar datos en el padrón A4.
 *
 * @see AfipWebService
 */
class PadronAlcanceCuatro extends AfipWebService
{
    /** {@inheritdoc} */
    protected ?string $nombreArchivoWSDL = 'ws_sr_padron_a4-production.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlServicio = 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA4';

    /** {@inheritdoc} */
    protected ?string $nombreArchivoWSDLPrueba = 'ws_sr_padron_a4.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlServicioPrueba = 'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA4';

    /**
     * Consulta y obtiene los datos de una persona (física o jurídica) registrados en AFIP.
     *
     * @param  int  $identificador  El CUIT/CUIL de la persona a consultar (sin guiones).
     * @return mixed Datos de la persona consultada.
     *
     * @throws AutenticacionException Si falla la autenticación con WSAA.
     * @throws SoapException Si ocurre un error en la comunicación SOAP.
     * @throws WebServiceException Si el web service devuelve un error.
     * @throws PadronException Si ocurre un error específico del padrón.
     */
    public function obtenerDatosPersona(int $identificador): mixed
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
     * Consulta y obtiene los datos de varias personas (físicas o jurídicas) registrados en AFIP.
     *
     * @param  array<int>  $identificadores  Array de CUITs/CUILs de las personas a consultar (sin guiones).
     * @return mixed Datos de las personas consultadas.
     *
     * @throws AutenticacionException Si falla la autenticación con WSAA.
     * @throws SoapException Si ocurre un error en la comunicación SOAP.
     * @throws WebServiceException Si el web service devuelve un error.
     * @throws PadronException Si ocurre un error específico del padrón.
     */
    public function obtenerDatosPersonas(array $identificadores): mixed
    {
        $tokenAutorizacion = $this->obtenerTokenAutorizacion();

        $params = [
            'token' => $tokenAutorizacion->obtenerToken(),
            'sign' => $tokenAutorizacion->obtenerFirma(),
            'cuitRepresentada' => $this->afip->obtenerCuit(),
            'idPersona' => $identificadores,
        ];

        return $this->ejecutarSolicitud('getPersonaList', $params);
    }

    /**
     * {@inheritdoc}
     *
     * @return string Nombre del servicio AFIP para autorización (ws_sr_padron_a4).
     */
    protected function obtenerNombreServicio(): string
    {
        return 'ws_sr_padron_a4';
    }
}
