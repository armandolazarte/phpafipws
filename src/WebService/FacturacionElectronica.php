<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use PhpAfipWs\Exception\AfipException;

/**
 * Clase para interactuar con el Web Service de Facturación Electrónica de AFIP (wsfe).
 *
 * Permite realizar operaciones como verificar el estado del servidor, obtener el último
 * número de comprobante, autorizar comprobantes y consultar datos de parámetros.
 */
class FacturacionElectronica extends AfipWebService
{
    /** {@inheritdoc} */
    protected ?string $nombreWsdl = 'wsfe-production.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlProduccion = 'https://servicios1.afip.gov.ar/wsfev1/service.asmx';

    /** {@inheritdoc} */
    protected ?string $nombreWsdlPrueba = 'wsfe.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlPrueba = 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx';

    /**
     * Verifica el estado de los servidores de AFIP.
     *
     * Ejecuta la operación `FEDummy` para comprobar la disponibilidad de los
     * servidores de aplicación, base de datos y autenticación.
     *
     * @return mixed La respuesta del servidor con el estado.
     *
     * @throws AfipException Si ocurre un error en la comunicación SOAP.
     */
    public function getEstadoServidor(): mixed
    {
        return $this->ejecutar('FEDummy');
    }

    /**
     * Obtiene el último número de comprobante autorizado para un punto de venta y tipo de comprobante.
     *
     * @param  int  $puntoVenta  El punto de venta.
     * @param  int  $tipoComprobante  El tipo de comprobante.
     * @return mixed La respuesta del servidor con el último número de comprobante.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getUltimoComprobante(int $puntoVenta, int $tipoComprobante): mixed
    {
        $tokenAutorizacion = $this->getTokenAutorizacion();

        $parametros = [
            'Auth' => [
                'Token' => $tokenAutorizacion->getToken(),
                'Sign' => $tokenAutorizacion->getSign(),
                'Cuit' => $this->afip->getCuit(),
            ],
            'PtoVta' => $puntoVenta,
            'CbteTipo' => $tipoComprobante,
        ];

        return $this->ejecutar('FECompUltimoAutorizado', $parametros);
    }

    /**
     * Solicita la autorización (CAE) para uno o más comprobantes.
     *
     * @param  array<int, array<string, mixed>>  $comprobantes  Array de comprobantes a autorizar.
     *                                                          Cada comprobante es un array asociativo con los datos requeridos por AFIP.
     * @return mixed La respuesta del servidor con el resultado de la autorización.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function autorizarComprobante(array $comprobantes): mixed
    {
        $tokenAutorizacion = $this->getTokenAutorizacion();

        $parametros = [
            'Auth' => [
                'Token' => $tokenAutorizacion->getToken(),
                'Sign' => $tokenAutorizacion->getSign(),
                'Cuit' => $this->afip->getCuit(),
            ],
            'FeCAEReq' => [
                'FeCabReq' => [
                    'CantReg' => count($comprobantes),
                    'PtoVta' => $comprobantes[0]['PtoVta'] ?? 1,
                    'CbteTipo' => $comprobantes[0]['CbteTipo'] ?? 11,
                ],
                'FeDetReq' => $comprobantes,
            ],
        ];

        return $this->ejecutar('FECAESolicitar', $parametros);
    }

    /**
     * Obtiene los tipos de comprobantes disponibles en el servicio.
     *
     * @return mixed La respuesta del servidor con el listado de tipos de comprobantes.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getTiposComprobantes(): mixed
    {
        $tokenAutorizacion = $this->getTokenAutorizacion();

        $parametros = [
            'Auth' => [
                'Token' => $tokenAutorizacion->getToken(),
                'Sign' => $tokenAutorizacion->getSign(),
                'Cuit' => $this->afip->getCuit(),
            ],
        ];

        return $this->ejecutar('FEParamGetTiposCbte', $parametros);
    }

    /**
     * Obtiene los tipos de documentos disponibles en el servicio.
     *
     * @return mixed La respuesta del servidor con el listado de tipos de documentos.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getTiposDocumentos(): mixed
    {
        $tokenAutorizacion = $this->getTokenAutorizacion();

        $parametros = [
            'Auth' => [
                'Token' => $tokenAutorizacion->getToken(),
                'Sign' => $tokenAutorizacion->getSign(),
                'Cuit' => $this->afip->getCuit(),
            ],
        ];

        return $this->ejecutar('FEParamGetTiposDoc', $parametros);
    }

    /**
     * Obtiene los tipos de monedas disponibles en el servicio.
     *
     * @return mixed La respuesta del servidor con el listado de tipos de monedas.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getTiposMonedas(): mixed
    {
        $tokenAutorizacion = $this->getTokenAutorizacion();

        $parametros = [
            'Auth' => [
                'Token' => $tokenAutorizacion->getToken(),
                'Sign' => $tokenAutorizacion->getSign(),
                'Cuit' => $this->afip->getCuit(),
            ],
        ];

        return $this->ejecutar('FEParamGetTiposMonedas', $parametros);
    }

    /**
     * {@inheritdoc}
     *
     * Devuelve el nombre del servicio para la autorización del token.
     */
    protected function getNombreServicio(): string
    {
        return 'wsfe';
    }
}
