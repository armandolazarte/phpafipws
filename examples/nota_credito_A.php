<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

try {
    // Configuración para testing
    $afip = new Afip([
        'cuit' => 20294192345, // Reemplaza con tu CUIT
        'modo_produccion' => false,
        'ruta_certificado' => 'certificado.crt',
        'ruta_clave' => 'clave_privada.key',
        'contrasena_clave' => 'tu_passphrase', // opcional
        'carpeta_recursos' => __DIR__.'/resources/',
        'carpeta_ta' => __DIR__.'/ta/',
    ]);

    echo 'AFIP SDK Version: '.$afip->getVersion()."\n";
    echo 'CUIT: '.$afip->getCuit()."\n";
    echo 'Modo Produccion: '.($afip->esProduccion() ? 'Si' : 'No')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    /**
     * Numero del punto de venta
     **/
    $puntoVenta = 1;

    /**
     * Tipo de Nota de Crédito
     **/
    $tipoNota = 3; // 3 = Nota de Crédito A

    /**
     * Número de la ultima Nota de Crédito A
     **/
    $ultimoComprobante = $facturacionElectronica->getUltimoComprobante($puntoDeNota, $tipoNota);

    /**
     * Numero del punto de venta de la Factura
     * asociada a la Nota de Crédito
     **/
    $puntoFacturaAsociada = 1;

    /**
     * Tipo de Factura asociada a la Nota de Crédito
     **/
    $tipoFacturaAsociada = 1; // 1 = Factura A

    /**
     * Numero de Factura asociada a la Nota de Crédito
     **/
    $numeroFacturaAsociada = 1;

    /**
     * Concepto de la Nota de Crédito
     *
     * Opciones:
     *
     * 1 = Productos, 2 = Servicios, 3 = Productos y Servicios
     **/
    $concepto = 1;

    /**
     * Tipo de documento del comprador
     *
     * Opciones:
     *
     * 80 = CUIT, 86 = CUIL, 96 = DNI, 99 = Consumidor Final
     **/
    $tipoDocumento = 80;

    /**
     * Numero de documento del comprador (0 para consumidor final)
     **/
    $numeroDocumento = 33693450239;

    /**
     * Numero de Nota de Crédito
     **/
    $numeroComprobante = $ultimoComprobante->FECompUltimoAutorizadoResult->CbteNro + 1;

    /**
     * Fecha de la Nota de Crédito en formato aaaa-mm-dd (hasta 10 dias antes y 10 dias despues)
     **/
    $fechaComprobante = date('Y-m-d');

    /**
     * Importe sujeto al IVA (sin incluir IVA)
     **/
    $importeNetoGravado = 100;

    /**
     * Importe exento al IVA
     **/
    $importeExentoIVA = 0;

    /**
     * Importe de IVA
     **/
    $importeIVA = 21;

    /**
     * Condición frente al IVA del receptor
     *
     * Opciones:
     *
     * 1 = IVA Responsable Inscripto, 4 = IVA Sujeto Exento, 5 = Consumidor Final,
     * 6 = Responsable Monotributo, 7 = Sujeto No Categorizado, 8 = Proveedor del Exterior,
     * 9 = Cliente del Exterior, 10 = IVA Liberado – Ley N° 19.640, 13 = Monotributista Social,
     * 15 = IVA No Alcanzado, 16 = Monotributo Trabajador Independiente Promovido
     **/
    $condicionIVAReceptor = 1;

    /**
     * Los siguientes campos solo son obligatorios para los conceptos 2 y 3
     **/
    if ($concepto === 2 || $concepto === 3) {
        /**
         * Fecha de inicio de servicio en formato aaaammdd
         **/
        $fechaServicioDesde = (int) (date('Ymd'));

        /**
         * Fecha de fin de servicio en formato aaaammdd
         **/
        $fechaServicioHasta = (int) (date('Ymd'));

        /**
         * Fecha de vencimiento del pago en formato aaaammdd
         **/
        $fechaVencimientoPago = (int) (date('Ymd'));
    } else {
        $fechaServicioDesde = null;
        $fechaServicioHasta = null;
        $fechaVencimientoPago = null;
    }

    $datosComprobante = [
        'PtoVta' => $puntoVenta,
        'CbteTipo' => $tipoNota,
        'Concepto' => $concepto,
        'DocTipo' => $tipoDocumento,
        'DocNro' => $numeroDocumento,
        'CbteDesde' => $numeroComprobante,
        'CbteHasta' => $numeroComprobante,
        'CbteFch' => (int) (str_replace('-', '', $fechaComprobante)),
        'FchServDesde' => $fechaServicioDesde,
        'FchServHasta' => $fechaServicioHasta,
        'FchVtoPago' => $fechaVencimientoPago,
        'ImpTotal' => $importeNetoGravado + $importeIVA + $importeExentoIVA,
        'ImpTotConc' => 0, // Importe neto no gravado
        'ImpNeto' => $importeNetoGravado,
        'ImpOpEx' => $importeExentoIVA,
        'ImpIVA' => $importeIVA,
        'ImpTrib' => 0, // Importe total de tributos
        'MonId' => 'PES', // Tipo de moneda usada en la Nota de Crédito ('PES' = pesos argentinos)
        'MonCotiz' => 1, // Cotización de la moneda usada (1 para pesos argentinos)
        'CondicionIVAReceptorId' => $condicionIVAReceptor,
        'CbtesAsoc' => [ // Factura asociada
            [
                'Tipo' => $tipoFacturaAsociada,
                'PtoVta' => $puntoFacturaAsociada,
                'Nro' => $numeroFacturaAsociada,
            ],
        ],
        'Iva' => [ // Alícuotas asociadas a la Nota de Crédito
            [
                'Id' => 5, // Id del tipo de IVA (5 = 21%)
                'BaseImp' => $importeNetoGravado,
                'Importe' => $importeIVA,
            ],
        ],
    ];

    /**
     * Creamos la Nota de Crédito
     **/
    $respuesta = $facturacionElectronica->autorizarComprobante([$datosComprobante]);

    /**
     * Mostramos por pantalla los datos de la nueva Nota de Crédito
     **/
    echo "Respuesta de AFIP:\n";
    print_r($respuesta);

    // Ejemplo de cómo acceder a los datos si la autorización fue exitosa
    if (isset($respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse)) {
        // Aseguramos que FECAEDetResponse sea siempre un array para un acceso consistente
        $feDetResp = $respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse;
        $feDetRespArray = is_array($feDetResp) ? $feDetResp : [$feDetResp];

        if (! empty($feDetRespArray)) {
            $resultadoComprobante = $feDetRespArray[0]; // Accedemos al primer (y único en este caso) comprobante
            if ($resultadoComprobante->Resultado === 'A') {
                echo "\n¡Nota de Crédito autorizada con éxito!\n";
                echo 'CAE: '.$resultadoComprobante->CAE."\n";
                echo 'Vencimiento CAE: '.$resultadoComprobante->CAEFchVto."\n";
            } else {
                echo "\nError al autorizar la Nota de Crédito:\n";
                if (isset($resultadoComprobante->Observaciones)) {
                    print_r($resultadoComprobante->Observaciones);
                }
            }
        }
    }
} catch (AfipException $e) {
    echo 'AFIP Error: '.$e->getMessage()."\n";
    echo 'Codigo de Error: '.$e->getCode()."\n";
} catch (Exception $e) {
    echo 'General Error: '.$e->getMessage()."\n";
}
