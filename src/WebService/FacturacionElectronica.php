<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use PhpAfipWs\Exception\AutenticacionException;
use PhpAfipWs\Exception\FacturacionElectronicaException;
use PhpAfipWs\Exception\SoapException;
use PhpAfipWs\Exception\WebServiceException;

/**
 * Clase para interactuar con el Web Service de Facturación Electrónica (WSFE) de AFIP.
 *
 * Esta clase extiende `AfipWebService` y proporciona métodos específicos para la gestión
 * de comprobantes electrónicos, incluyendo la verificación del estado del servicio,
 * la obtención del último número de comprobante autorizado, la autorización de CAE
 * y la consulta de tablas de parámetros (tipos de comprobantes, documentos, monedas, etc.).
 */
class FacturacionElectronica extends AfipWebService
{
    /** {@inheritdoc} */
    protected ?string $nombreArchivoWSDL = 'wsfe-production.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlServicio = 'https://servicios1.afip.gov.ar/wsfev1/service.asmx';

    /** {@inheritdoc} */
    protected ?string $nombreArchivoWSDLPrueba = 'wsfe.wsdl';

    /** {@inheritdoc} */
    protected ?string $urlServicioPrueba = 'https://wswhomo.afip.gov.ar/wsfev1/service.asmx';

    /**
     * Verifica el estado de los servidores de AFIP.
     *
     * Ejecuta la operación `FEDummy` para comprobar la disponibilidad de los
     * servidores de aplicación, base de datos y autenticación.
     *
     * @return mixed La respuesta del servidor con el estado de los componentes (AppServer, DbServer, AuthServer).
     *
     * @throws SoapException Si ocurre un error en la comunicación SOAP con el Web Service.
     * @throws WebServiceException Si hay un problema general con el servicio o su configuración.
     */
    public function obtenerEstadoServidor(): mixed
    {
        return $this->ejecutarSolicitud('FEDummy');
    }

    /**
     * Obtiene el último número de comprobante autorizado para un punto de venta y tipo de comprobante.
     *
     * @param  int  $puntoVenta  El punto de venta.
     * @param  int  $tipoComprobante  El tipo de comprobante.
     * @return mixed La respuesta del servidor con el último número de comprobante autorizado.
     *
     * @throws AutenticacionException Si ocurre un error durante la obtención o renovación del Ticket de Acceso (TA).
     * @throws SoapException Si ocurre un error en la comunicación SOAP con el Web Service.
     * @throws WebServiceException Si hay un problema general con el servicio o su configuración.
     */
    public function obtenerUltimoComprobante(int $puntoVenta, int $tipoComprobante): mixed
    {
        $tokenAutorizacion = $this->obtenerTokenAutorizacion();

        $params = [
            'Auth' => [
                'Token' => $tokenAutorizacion->obtenerToken(),
                'Sign' => $tokenAutorizacion->obtenerFirma(),
                'Cuit' => $this->afip->obtenerCuit(),
            ],
            'PtoVta' => $puntoVenta,
            'CbteTipo' => $tipoComprobante,
        ];

        return $this->ejecutarSolicitud('FECompUltimoAutorizado', $params);
    }

    /**
     * Obtiene el número del último comprobante autorizado.
     *
     * Extrae el número del comprobante de la respuesta del servicio AFIP
     * para el punto de venta y tipo de comprobante especificados.
     *
     * @param  int  $puntoVenta  El punto de venta del comprobante.
     * @param  int  $tipoComprobante  El tipo de comprobante (ej. 1, 6, 11).
     * @return int El número del último comprobante autorizado.
     *
     * @throws AutenticacionException Si ocurre un error durante la autenticación.
     * @throws SoapException Si ocurre un error en la comunicación SOAP.
     * @throws WebServiceException Si hay un problema general con el servicio.
     */
    public function obtenerUltimoNumeroComprobante(int $puntoVenta, int $tipoComprobante): int
    {
        $respuesta = $this->obtenerUltimoComprobante($puntoVenta, $tipoComprobante);

        if (! is_object($respuesta) || ! isset($respuesta->FECompUltimoAutorizadoResult) || ! is_object($respuesta->FECompUltimoAutorizadoResult) || ! isset($respuesta->FECompUltimoAutorizadoResult->CbteNro)) {
            throw new WebServiceException('La respuesta del servicio no tiene la estructura esperada');
        }

        $numeroComprobante = $respuesta->FECompUltimoAutorizadoResult->CbteNro;

        if (! is_numeric($numeroComprobante)) {
            throw new WebServiceException('El número de comprobante no es válido');
        }

        return (int) $numeroComprobante;
    }

    /**
     * Solicita la autorización (CAE) para uno o más comprobantes.
     *
     * @param  array<array<string, mixed>>  $comprobantes  Array de comprobantes a autorizar.
     *                                                     Cada comprobante es un array asociativo con los datos requeridos por AFIP.
     * @return mixed La respuesta del servidor con el resultado de la autorización (CAE).
     *
     * @throws AutenticacionException Si ocurre un error durante la obtención o renovación del Ticket de Acceso (TA).
     * @throws SoapException Si ocurre un error en la comunicación SOAP con el Web Service.
     * @throws WebServiceException Si hay un problema general con el servicio o su configuración.
     * @throws FacturacionElectronicaException Si los datos de los comprobantes son inválidos o incompletos.
     */
    public function autorizarComprobante(array $comprobantes): mixed
    {
        $tokenAutorizacion = $this->obtenerTokenAutorizacion();

        $params = [
            'Auth' => [
                'Token' => $tokenAutorizacion->obtenerToken(),
                'Sign' => $tokenAutorizacion->obtenerFirma(),
                'Cuit' => $this->afip->obtenerCuit(),
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

        return $this->ejecutarSolicitud('FECAESolicitar', $params);
    }

    /**
     * Autoriza el próximo comprobante consecutivo para un tipo y punto de venta dados.
     *
     * Combina la obtención del último comprobante con la autorización de uno nuevo.
     * Este método se encarga de calcular automáticamente el número de comprobante.
     *
     * @param  array<string, mixed>  $datosComprobante  Los datos del comprobante a autorizar, sin incluir 'CbteDesde' y 'CbteHasta'.
     * @return mixed La respuesta completa del servicio de AFIP.
     *
     * @throws AutenticacionException Si ocurre un error durante la autenticación.
     * @throws SoapException Si ocurre un error en la comunicación SOAP.
     * @throws WebServiceException Si hay un problema general con el servicio.
     * @throws FacturacionElectronicaException Si los datos del comprobante son inválidos.
     */
    public function autorizarProximoComprobante(array $datosComprobante): mixed
    {
        $puntoDeVentaRaw = $datosComprobante['PtoVta'] ?? 1;
        $tipoDeComprobanteRaw = $datosComprobante['CbteTipo'] ?? 1;

        if (! is_numeric($puntoDeVentaRaw)) {
            throw new FacturacionElectronicaException('El punto de venta debe ser un valor numérico');
        }

        if (! is_numeric($tipoDeComprobanteRaw)) {
            throw new FacturacionElectronicaException('El tipo de comprobante debe ser un valor numérico');
        }

        $puntoDeVenta = (int) $puntoDeVentaRaw;
        $tipoDeComprobante = (int) $tipoDeComprobanteRaw;

        $ultimoNumero = $this->obtenerUltimoNumeroComprobante($puntoDeVenta, $tipoDeComprobante);
        $proximoNumero = $ultimoNumero + 1;

        $datosComprobante['CbteDesde'] = $proximoNumero;
        $datosComprobante['CbteHasta'] = $proximoNumero;

        return $this->autorizarComprobante([$datosComprobante]);
    }

    /**
     * Obtiene los tipos de comprobantes disponibles en el servicio.
     *
     * @return mixed La respuesta del servidor con el listado de tipos de comprobantes disponibles.
     *
     * @throws AutenticacionException Si ocurre un error durante la obtención o renovación del Ticket de Acceso (TA).
     * @throws SoapException Si ocurre un error en la comunicación SOAP con el Web Service.
     * @throws WebServiceException Si hay un problema general con el servicio o su configuración.
     */
    public function obtenerTiposComprobante(): mixed
    {
        $tokenAutorizacion = $this->obtenerTokenAutorizacion();

        $params = [
            'Auth' => [
                'Token' => $tokenAutorizacion->obtenerToken(),
                'Sign' => $tokenAutorizacion->obtenerFirma(),
                'Cuit' => $this->afip->obtenerCuit(),
            ],
        ];

        return $this->ejecutarSolicitud('FEParamGetTiposCbte', $params);
    }

    /**
     * Obtiene los tipos de documentos disponibles en el servicio.
     *
     * @return mixed La respuesta del servidor con el listado de tipos de documentos disponibles.
     *
     * @throws AutenticacionException Si ocurre un error durante la obtención o renovación del Ticket de Acceso (TA).
     * @throws SoapException Si ocurre un error en la comunicación SOAP con el Web Service.
     * @throws WebServiceException Si hay un problema general con el servicio o su configuración.
     */
    public function obtenerTiposDocumento(): mixed
    {
        $tokenAutorizacion = $this->obtenerTokenAutorizacion();

        $params = [
            'Auth' => [
                'Token' => $tokenAutorizacion->obtenerToken(),
                'Sign' => $tokenAutorizacion->obtenerFirma(),
                'Cuit' => $this->afip->obtenerCuit(),
            ],
        ];

        return $this->ejecutarSolicitud('FEParamGetTiposDoc', $params);
    }

    /**
     * Obtiene los tipos de monedas disponibles en el servicio.
     *
     * @return mixed La respuesta del servidor con el listado de tipos de monedas disponibles.
     *
     * @throws AutenticacionException Si ocurre un error durante la obtención o renovación del Ticket de Acceso (TA).
     * @throws SoapException Si ocurre un error en la comunicación SOAP con el Web Service.
     * @throws WebServiceException Si hay un problema general con el servicio o su configuración.
     */
    public function obtenerTiposMoneda(): mixed
    {
        $tokenAutorizacion = $this->obtenerTokenAutorizacion();

        $params = [
            'Auth' => [
                'Token' => $tokenAutorizacion->obtenerToken(),
                'Sign' => $tokenAutorizacion->obtenerFirma(),
                'Cuit' => $this->afip->obtenerCuit(),
            ],
        ];

        return $this->ejecutarSolicitud('FEParamGetTiposMonedas', $params);
    }

    /**
     * Obtiene las condiciones de IVA para el receptor.
     *
     * @return mixed La respuesta del servidor con las condiciones de IVA para el receptor.
     *
     * @throws AutenticacionException Si ocurre un error durante la obtención o renovación del Ticket de Acceso (TA).
     * @throws SoapException Si ocurre un error en la comunicación SOAP con el Web Service.
     * @throws WebServiceException Si hay un problema general con el servicio o su configuración.
     */
    public function obtenerCondicionesIvaReceptor(): mixed
    {
        $tokenAutorizacion = $this->obtenerTokenAutorizacion();

        $params = [
            'Auth' => [
                'Token' => $tokenAutorizacion->obtenerToken(),
                'Sign' => $tokenAutorizacion->obtenerFirma(),
                'Cuit' => $this->afip->obtenerCuit(),
            ],
        ];

        return $this->ejecutarSolicitud('FEParamGetCondicionIvaReceptor', $params);
    }

    /**
     * {@inheritdoc}
     *
     * Devuelve el nombre del servicio AFIP asociado a esta instancia, que es 'wsfe'.
     *
     * @return string El nombre del servicio 'wsfe'.
     */
    protected function obtenerNombreServicio(): string
    {
        return 'wsfe';
    }
}
