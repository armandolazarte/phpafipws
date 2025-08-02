<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

try {
    $afip = new Afip([
        'cuit' => 20294192345, // Reemplaza con tu CUIT
        'modo_produccion' => false,
        'nombre_certificado' => 'certificado.crt',
        'nombre_clave' => 'clave_privada.key',
        'contrasena_clave' => 'tu_passphrase', // opcional
        'carpeta_recursos' => __DIR__.'/resources/',
        'carpeta_ta' => __DIR__.'/ta/',
    ]);

    echo 'Versión del SDK de AFIP: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo Producción: '.($afip->esModoProduccion() ? 'Sí' : 'No')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    $puntoDeVenta = 1;

    $tipoDeFactura = 1; // 1 = Factura A

    $ultimoComprobante = $facturacionElectronica->obtenerUltimoComprobante($puntoDeVenta, $tipoDeFactura);

    $concepto = 1; // 1 = Productos, 2 = Servicios, 3 = Productos y Servicios

    $tipoDeDocumento = 80; // 80 = CUIT, 86 = CUIL, 96 = DNI, 99 = Consumidor Final

    $numeroDeDocumento = 33693450239; // 0 para consumidor final

    $numeroDeFactura = $ultimoComprobante->FECompUltimoAutorizadoResult->CbteNro + 1;

    $fecha = date('Y-m-d'); // formato aaaa-mm-dd (hasta 10 dias antes y 10 dias despues)

    $importeGravado = 100; // sin incluir IVA

    $importeExentoIva = 0;

    $importeIva = 21;

    /**
     * Condición frente al IVA del receptor.
     * Obligatorio a partir de la Resolución General N° 5616.
     *
     * Opciones comunes:
     * 1 = IVA Responsable Inscripto (para Factura A)
     * 4 = IVA Sujeto Exento
     * 5 = Consumidor Final
     * 6 = Responsable Monotributo
     * 7 = Sujeto No Categorizado
     * 8 = Proveedor del Exterior
     * 9 = Cliente del Exterior
     * 10 = IVA Liberado – Ley N° 19.640
     * 13 = Monotributista Social
     * 15 = IVA No Alcanzado
     * 16 = Monotributo Trabajador Independiente Promovido
     *
     * Para Factura A, el receptor suele ser Responsable Inscripto (1).
     **/
    $condicionIvaReceptor = 1;

    /**
     * Los siguientes campos solo son obligatorios para los conceptos 2 y 3
     **/
    if ($concepto === 3) {
        $fechaServicioDesde = (int) (date('Ymd'));
        $fechaServicioHasta = (int) (date('Ymd'));
        $fechaVencimientoPago = (int) (date('Ymd'));
    } else {
        $fechaServicioDesde = null;
        $fechaServicioHasta = null;
        $fechaVencimientoPago = null;
    }

    $datosComprobante = [
        'CantReg' => 1, // Cantidad de facturas a registrar
        'PtoVta' => $puntoDeVenta,
        'CbteTipo' => $tipoDeFactura,
        'Concepto' => $concepto,
        'DocTipo' => $tipoDeDocumento,
        'DocNro' => $numeroDeDocumento,
        'CbteDesde' => $numeroDeFactura,
        'CbteHasta' => $numeroDeFactura,
        'CbteFch' => (int) (str_replace('-', '', $fecha)),
        'FchServDesde' => $fechaServicioDesde,
        'FchServHasta' => $fechaServicioHasta,
        'FchVtoPago' => $fechaVencimientoPago,
        'ImpTotal' => $importeGravado + $importeIva,
        'ImpTotConc' => 0, // Importe neto no gravado
        'ImpNeto' => $importeGravado,
        'ImpOpEx' => $importeExentoIva,
        'ImpIVA' => $importeIva,
        'ImpTrib' => 0, // Importe total de tributos
        'MonId' => 'PES', // Tipo de moneda usada en la factura ('PES' = pesos argentinos)
        'MonCotiz' => 1, // Cotización de la moneda usada (1 para pesos argentinos)
        'CondicionIVAReceptorId' => $condicionIvaReceptor, // Requerido por RG 5616
        'Iva' => [// Alícuotas asociadas a la factura
            [
                'Id' => 5, // Id del tipo de IVA (5 = 21%)
                'BaseImp' => $importeGravado,
                'Importe' => $importeIva,
            ],
        ],
    ];

    $respuesta = $facturacionElectronica->autorizarComprobante([$datosComprobante]);

    echo "Respuesta de AFIP:\n";
    print_r($respuesta);

    // Ejemplo de cómo acceder a los datos si la autorización fue exitosa
    if (isset($respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse)) {
        // Aseguramos que FECAEDetResponse sea siempre un array para un acceso consistente
        $feDetResp = $respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse;
        $feDetRespArray = is_array($feDetResp) ? $feDetResp : [$feDetResp];

        if ($feDetRespArray !== []) {
            $resultadoComprobante = $feDetRespArray[0]; // Accedemos al primer (y único en este caso) comprobante

            if ($resultadoComprobante->Resultado === 'A') {
                echo "\n¡Factura autorizada con éxito!\n";
                echo 'CAE: '.$resultadoComprobante->CAE."\n";
                echo 'Vencimiento CAE: '.$resultadoComprobante->CAEFchVto."\n";
            } else {
                echo "\nError al autorizar la factura:\n";
                if (isset($resultadoComprobante->Observaciones)) {
                    print_r($resultadoComprobante->Observaciones);
                }
            }
        }
    }
} catch (AfipException $e) {
    echo 'ARCA Error: '.$e->getMessage()."\n";
    echo 'Error Code: '.$e->getCode()."\n";
} catch (Exception $e) {
    echo 'General Error: '.$e->getMessage()."\n";
}
