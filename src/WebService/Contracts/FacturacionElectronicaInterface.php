<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService\Contracts;

use PhpAfipWs\Exception\AfipException;

/**
 * Interfaz para el Web Service de Facturación Electrónica de AFIP (wsfe).
 */
interface FacturacionElectronicaInterface extends AfipWebServiceInterface
{
    /**
     * Verifica el estado de los servidores de AFIP.
     *
     * @return mixed La respuesta del servidor con el estado.
     *
     * @throws AfipException Si ocurre un error en la comunicación SOAP.
     */
    public function getEstadoServidor(): mixed;

    /**
     * Obtiene el último número de comprobante autorizado para un punto de venta y tipo de comprobante.
     *
     * @param  int  $puntoVenta  El punto de venta.
     * @param  int  $tipoComprobante  El tipo de comprobante.
     * @return mixed La respuesta del servidor con el último número de comprobante.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getUltimoComprobante(int $puntoVenta, int $tipoComprobante): mixed;

    /**
     * Solicita la autorización (CAE) para uno o más comprobantes.
     *
     * @param  array<int, array<string, mixed>>  $comprobantes  Array de comprobantes a autorizar.
     * @return mixed La respuesta del servidor con el resultado de la autorización.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function autorizarComprobante(array $comprobantes): mixed;

    /**
     * Obtiene los tipos de comprobantes disponibles en el servicio.
     *
     * @return mixed La respuesta del servidor con el listado de tipos de comprobantes.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getTiposComprobantes(): mixed;

    /**
     * Obtiene los tipos de documentos disponibles en el servicio.
     *
     * @return mixed La respuesta del servidor con el listado de tipos de documentos.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getTiposDocumentos(): mixed;

    /**
     * Obtiene los tipos de monedas disponibles en el servicio.
     *
     * @return mixed La respuesta del servidor con el listado de tipos de monedas.
     *
     * @throws AfipException Si ocurre un error al obtener el TA o en la comunicación SOAP.
     */
    public function getTiposMonedas(): mixed;
}
