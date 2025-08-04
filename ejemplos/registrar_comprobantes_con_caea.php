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

    /**
     * REGISTRAR COMPROBANTES CON CAEA
     *
     * Este método se utiliza para informar a AFIP los comprobantes que fueron
     * emitidos utilizando un CAEA (Código de Autorización Electrónico Anticipado).
     *
     * Proceso CAEA:
     * 1. Solicitar CAEA con crearCAEA()
     * 2. Emitir comprobantes offline usando el CAEA
     * 3. Registrar los comprobantes emitidos con este método (dentro del plazo)
     *
     * IMPORTANTE: Los comprobantes deben registrarse dentro del plazo establecido
     * por AFIP (generalmente 10 días después del vencimiento del CAEA).
     */
    $caea = '21234567890123'; // CAEA previamente solicitado
    $puntoVenta = 1;

    echo "=== REGISTRAR COMPROBANTES CON CAEA ===\n";
    echo sprintf('CAEA: %s%s', $caea, PHP_EOL);
    echo "Punto de Venta: {$puntoVenta}\n\n";

    // Datos de los comprobantes emitidos con CAEA
    $comprobantes = [
        [
            'CAEA' => $caea,
            'PtoVta' => $puntoVenta,
            'CbteTipo' => 1, // Factura A
            'CbteDesde' => 1,
            'CbteHasta' => 1,
            'Concepto' => 1, // Productos
            'DocTipo' => 80, // CUIT
            'DocNro' => 33693450239,
            'CbteFch' => (int) date('Ymd'), // Fecha de emisión
            'ImpTotal' => 121.00,
            'ImpTotConc' => 0,
            'ImpNeto' => 100.00,
            'ImpOpEx' => 0,
            'ImpIVA' => 21.00,
            'ImpTrib' => 0,
            'MonId' => 'PES',
            'MonCotiz' => 1,
            'CondicionIVAReceptorId' => 1, // Responsable Inscripto
            'Iva' => [
                [
                    'Id' => 5, // 21%
                    'BaseImp' => 100.00,
                    'Importe' => 21.00,
                ],
            ],
        ],
        // Segundo comprobante (ejemplo)
        [
            'CAEA' => $caea,
            'PtoVta' => $puntoVenta,
            'CbteTipo' => 1, // Factura A
            'CbteDesde' => 2,
            'CbteHasta' => 2,
            'Concepto' => 1, // Productos
            'DocTipo' => 80, // CUIT
            'DocNro' => 20123456789,
            'CbteFch' => (int) date('Ymd'),
            'ImpTotal' => 242.00,
            'ImpTotConc' => 0,
            'ImpNeto' => 200.00,
            'ImpOpEx' => 0,
            'ImpIVA' => 42.00,
            'ImpTrib' => 0,
            'MonId' => 'PES',
            'MonCotiz' => 1,
            'CondicionIVAReceptorId' => 1, // Responsable Inscripto
            'Iva' => [
                [
                    'Id' => 5, // 21%
                    'BaseImp' => 200.00,
                    'Importe' => 42.00,
                ],
            ],
        ],
    ];

    echo 'Registrando '.count($comprobantes)." comprobantes con CAEA...\n\n";

    // Mostrar resumen de comprobantes a registrar
    foreach ($comprobantes as $index => $comprobante) {
        echo 'Comprobante '.($index + 1).":\n";
        echo "  - Tipo: {$comprobante['CbteTipo']} (Factura A)\n";
        echo sprintf('  - Número: %s%s', $comprobante['CbteDesde'], PHP_EOL);
        echo sprintf('  - CUIT Receptor: %s%s', $comprobante['DocNro'], PHP_EOL);
        echo sprintf('  - Importe Total: $%s%s', $comprobante['ImpTotal'], PHP_EOL);
        echo "  - Fecha: {$comprobante['CbteFch']}\n\n";
    }

    // Registrar los comprobantes
    echo "Enviando registro a AFIP...\n";
    $respuesta = $facturacionElectronica->registrarComprobantesConCAEA($comprobantes);

    echo "Respuesta de AFIP:\n";
    print_r($respuesta);

    // Procesar la respuesta
    if (isset($respuesta->FECAEARegInformativoResult)) {
        $resultado = $respuesta->FECAEARegInformativoResult;

        // Verificar cabecera
        if (isset($resultado->FeCabResp)) {
            $cabecera = $resultado->FeCabResp;
            echo "\n=== RESULTADO DE LA CABECERA ===\n";
            echo sprintf('CAEA: %s%s', $cabecera->CAEA, PHP_EOL);
            echo sprintf('Punto de Venta: %s%s', $cabecera->PtoVta, PHP_EOL);
            echo sprintf('Resultado: %s%s', $cabecera->Resultado, PHP_EOL);

            if ($cabecera->Resultado === 'A') {
                echo "✅ Cabecera procesada correctamente\n";
            } else {
                echo "❌ Error en cabecera\n";
            }
        }

        // Verificar detalles de cada comprobante
        if (isset($resultado->FeDetResp)) {
            $detalles = is_array($resultado->FeDetResp) ? $resultado->FeDetResp : [$resultado->FeDetResp];

            echo "\n=== RESULTADO DE COMPROBANTES ===\n";
            foreach ($detalles as $index => $detalle) {
                echo 'Comprobante '.($index + 1).":\n";
                echo sprintf('  - Desde: %s%s', $detalle->CbteDesde, PHP_EOL);
                echo sprintf('  - Hasta: %s%s', $detalle->CbteHasta, PHP_EOL);
                echo sprintf('  - Resultado: %s%s', $detalle->Resultado, PHP_EOL);

                if ($detalle->Resultado === 'A') {
                    echo "  ✅ Registrado correctamente\n";
                } else {
                    echo "  ❌ Error en registro\n";
                    if (isset($detalle->Observaciones)) {
                        echo "  Observaciones:\n";
                        print_r($detalle->Observaciones);
                    }
                }

                echo "\n";
            }
        }

        // Verificar errores generales
        if (isset($resultado->Errors) && ! empty($resultado->Errors)) {
            echo "❌ Errores generales:\n";
            print_r($resultado->Errors);
        }

        // Verificar eventos
        if (isset($resultado->Events) && ! empty($resultado->Events)) {
            echo "ℹ️  Eventos:\n";
            print_r($resultado->Events);
        }
    }

    echo "\n=== FUNCIÓN HELPER PARA VALIDAR CAEA ===\n";

    // Función helper para validar que un CAEA esté vigente
    function validarCAEAVigente($facturacionElectronica, $caea): array
    {
        try {
            $respuesta = $facturacionElectronica->obtenerCAEA($caea);

            if (isset($respuesta->FECAEAConsultarResult)) {
                $resultado = $respuesta->FECAEAConsultarResult;
                $fechaHoy = (int) date('Ymd');
                $fechaVigHasta = (int) $resultado->FchVigHasta;
                $fechaTopeInf = (int) $resultado->FchTopeInf;

                return [
                    'existe' => true,
                    'vigente' => $fechaHoy <= $fechaVigHasta,
                    'puede_informar' => $fechaHoy <= $fechaTopeInf,
                    'fecha_vig_hasta' => $resultado->FchVigHasta,
                    'fecha_tope_inf' => $resultado->FchTopeInf,
                ];
            }

            return ['existe' => false];
        } catch (Exception $exception) {
            return ['existe' => false, 'error' => $exception->getMessage()];
        }
    }

    // Ejemplo de uso de la función helper
    $estadoCAEA = validarCAEAVigente($facturacionElectronica, $caea);

    if ($estadoCAEA['existe']) {
        if ($estadoCAEA['puede_informar']) {
            echo "✅ El CAEA {$caea} puede ser usado para registrar comprobantes\n";
            echo sprintf('   Fecha límite para informar: %s%s', $estadoCAEA['fecha_tope_inf'], PHP_EOL);
        } else {
            echo "❌ El CAEA {$caea} ya no puede ser usado (venció el plazo para informar)\n";
        }
    } else {
        echo "❌ El CAEA {$caea} no existe o no es válido\n";
    }

    echo "\n=== INFORMACIÓN ADICIONAL ===\n";
    echo "• Los comprobantes deben registrarse dentro del plazo establecido\n";
    echo "• Todos los comprobantes deben usar el mismo CAEA y punto de venta\n";
    echo "• Los números de comprobante deben ser consecutivos\n";
    echo "• Este método es para registro informativo, no autoriza nuevos comprobantes\n";
    echo "• Use obtenerCAEA() para verificar vigencia antes de registrar\n";

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
