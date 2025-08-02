<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use PhpAfipWs\Exception\AutenticacionException;
use PhpAfipWs\Exception\PadronException;
use PhpAfipWs\Exception\SoapException;
use PhpAfipWs\Exception\WebServiceException;

/**
 * Clase para interactuar con el Web Service de Constancia de Inscripción de AFIP (ws_sr_constancia_inscripcion).
 *
 * Permite consultar los datos de un contribuyente a partir de su CUIT.
 * Extiende de AfipWebService y proporciona métodos específicos para consultar datos de constancia de inscripción.
 *
 * @see AfipWebService
 */
class ConstanciaInscripcion extends AfipWebService
{
    /**
     * {@inheritdoc}
     *
     * @return string Nombre del servicio AFIP para autorización (ws_sr_constancia_inscripcion).
     */
    protected ?string $nombreArchivoWSDL = 'ws_sr_constancia_inscripcion-production.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlServicio = 'https://aws.afip.gov.ar/sr-padron/webservices/constanciaInscripcion';

    /** {@inheritdoc} */
    protected ?string $nombreArchivoWSDLPrueba = 'ws_sr_constancia_inscripcion.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlServicioPrueba = 'https://awshomo.afip.gov.ar/sr-padron/webservices/constanciaInscripcion';

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

        return $this->ejecutarSolicitud('getPersona_v2', $params);
    }

    /**
     * Obtiene los datos de múltiples contribuyentes a partir de sus CUIT.
     *
     * @param  array<int>  $identificadores  Array de CUITs de los contribuyentes a consultar (sin guiones).
     * @return mixed Datos de los contribuyentes consultados.
     *
     * @throws AutenticacionException Si falla la autenticación con WSAA.
     * @throws SoapException Si ocurre un error en la comunicación SOAP.
     * @throws WebServiceException Si el web service devuelve un error.
     * @throws PadronException Si ocurre un error específico del padrón.
     */
    public function obtenerDetallesContribuyentes(array $identificadores): mixed
    {
        $tokenAutorizacion = $this->obtenerTokenAutorizacion();

        $params = [
            'token' => $tokenAutorizacion->obtenerToken(),
            'sign' => $tokenAutorizacion->obtenerFirma(),
            'cuitRepresentada' => $this->afip->obtenerCuit(),
            'idPersona' => $identificadores,
        ];

        return $this->ejecutarSolicitud('getPersonaList_v2', $params);
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

    /** {@inheritdoc} */
    protected function obtenerNombreServicio(): string
    {
        return 'ws_sr_constancia_inscripcion';
    }
}
