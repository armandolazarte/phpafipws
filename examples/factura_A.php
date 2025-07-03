<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

try {
    $afip = new Afip([
        'cuit' => 20294192345,
        'modo_produccion' => false,
        'ruta_certificado' => 'certificado.crt',
        'ruta_clave' => 'clave_privada.key',
        'contrasena_clave' => 'tu_passphrase',
        'carpeta_recursos' => __DIR__.'/resources/',
        'carpeta_ta' => __DIR__.'/ta/',
    ]);

    echo 'AFIP Version: '.$afip->getVersion()."\n";
    echo 'CUIT: '.$afip->getCuit()."\n";
    echo 'Modo Produccion: '.($afip->esProduccion() ? 'Si' : 'No')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    $puntoVenta = 1;

    $tipoComprobante = 1; // 1 = Factura A

    $ultimoComprobante = $facturacionElectronica->getUltimoComprobante($puntoVenta, $tipoComprobante);

    $concepto = 1; // 1 = Productos, 2 = Servicios, 3 = Productos y Servicios

    $tipoDocumento = 80; // 80 = CUIT, 86 = CUIL, 96 = DNI, 99 = Consumidor Final

    $numeroDocumento = 33693450239; // 0 para consumidor final

    $numeroComprobante = $ultimoComprobante->FECompUltimoAutorizadoResult->CbteNro + 1;

    $fechaComprobante = date('Y-m-d'); // formato aaaa-mm-dd (hasta 10 dias antes y 10 dias despues)

    $importeNetoGravado = 100; // sin incluir IVA

    $importeExentoIVA = 0;

    $importeIVA = 21;

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
    $condicionIVAReceptor = 1;

    /**
     * Los siguientes campos solo son obligatorios para los conceptos 2 y 3
     **/
    if ($concepto === 2 || $concepto === 3) {
        $fechaServicioDesde = (int) (date('Ymd'));
        $fechaServicioHasta = (int) (date('Ymd'));
        $fechaVencimientoPago = (int) (date('Ymd'));
    } else {
        $fechaServicioDesde = null;
        $fechaServicioHasta = null;
        $fechaVencimientoPago = null;
    }

    $datosComprobante = [
        'CantReg' => 1, // Cantidad de comprobantes a registrar
        'PtoVta' => $puntoVenta,
        'CbteTipo' => $tipoComprobante,
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
        'MonId' => 'PES', // Tipo de moneda usada en el comprobante ('PES' = pesos argentinos)
        'MonCotiz' => 1, // Cotización de la moneda usada (1 para pesos argentinos)
        'CondicionIVAReceptorId' => $condicionIVAReceptor, // Requerido por RG 5616
        'Iva' => [// Alícuotas asociadas al comprobante
            [
                'Id' => 5, // Id del tipo de IVA (5 = 21%)
                'BaseImp' => $importeNetoGravado,
                'Importe' => $importeIVA,
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
    echo 'AFIP Error: '.$e->getMessage()."\n";
    echo 'Codigo de Error: '.$e->getCode()."\n";
} catch (Exception $e) {
    echo 'Error General: '.$e->getMessage()."\n";
}
