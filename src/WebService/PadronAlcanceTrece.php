<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use PhpAfipWs\Exception\AfipException;
use PhpAfipWs\WebService\Contracts\PadronAlcanceTreceInterface;

/**
 * Clase para interactuar con el Web Service de Padrón Alcance 13 de AFIP (ws_sr_padron_a13).
 *
 * Permite consultar los datos de un contribuyente a partir de su CUIT o buscar un CUIT a partir de un número de documento.
 */
class PadronAlcanceTrece extends AfipWebService implements PadronAlcanceTreceInterface
{
    /** {@inheritdoc} */ // Nombre del archivo WSDL para el entorno de producción.
    protected ?string $nombreWsdl = 'ws_sr_padron_a13-production.wsdl';

    /** {@inheritdoc} */ // URL del endpoint para el entorno de producción.
    protected ?string $urlProduccion = 'https://aws.afip.gov.ar/sr-padron/webservices/personaServiceA13';

    /** {@inheritdoc} */ // Nombre del archivo WSDL para el entorno de prueba.
    protected ?string $nombreWsdlPrueba = 'ws_sr_padron_a13.wsdl';

    /** {@inheritdoc} */ // URL del endpoint para el entorno de prueba.
    protected ?string $urlPrueba = 'https://awshomo.afip.gov.ar/sr-padron/webservices/personaServiceA13';

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
        $tokenAuthorization = $this->getTokenAutorizacion();

        $parametros = [
            'token' => $tokenAuthorization->getToken(),
            'sign' => $tokenAuthorization->getSign(),
            'cuitRepresentada' => $this->afip->getCuit(),
            'idPersona' => $identificador,
        ];

        return $this->ejecutar('getPersona', $parametros);
    }

    /**
     * Obtiene el CUIT de una persona a partir de su número de documento (DNI, LC, LE, etc.).
     *
     * @param  string  $numeroDocumento  El número de documento a consultar.
     * @return mixed La respuesta del servidor con el/los CUIT encontrados.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getCuitPorDocumento(string $numeroDocumento): mixed
    {
        $tokenAuthorization = $this->getTokenAutorizacion();

        $parametros = [
            'token' => $tokenAuthorization->getToken(),
            'sign' => $tokenAuthorization->getSign(),
            'cuitRepresentada' => $this->afip->getCuit(),
            'documento' => $numeroDocumento,
        ];

        return $this->ejecutar('getIdPersonaListByDocumento', $parametros);
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
    public function getNombreServicio(): string
    {
        return 'ws_sr_padron_a13';
    }
}
