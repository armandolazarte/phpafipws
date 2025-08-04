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
     * OBTENER COTIZACIÓN DE MONEDA
     *
     * Este método permite consultar la cotización oficial de una moneda específica
     * según los valores que maneja AFIP para la facturación electrónica.
     *
     * Es útil para:
     * - Obtener la cotización oficial para facturar en moneda extranjera
     * - Validar que la cotización usada en comprobantes sea la correcta
     * - Automatizar la conversión de importes en facturas
     */
    echo "=== OBTENER COTIZACIÓN DE MONEDA ===\n\n";

    // Monedas comunes para consultar
    $monedasConsultar = ['DOL', 'EUR', 'BRL', 'PES'];

    foreach ($monedasConsultar as $monedaId) {
        echo sprintf('Consultando cotización de: %s%s', $monedaId, PHP_EOL);

        try {
            $respuesta = $facturacionElectronica->obtenerCotizacionMoneda($monedaId);

            echo "Respuesta de AFIP para {$monedaId}:\n";
            print_r($respuesta);

            // Procesar la respuesta
            if (isset($respuesta->FEParamGetCotizacionResult->ResultGet)) {
                $cotizacion = $respuesta->FEParamGetCotizacionResult->ResultGet;

                echo "✅ Cotización obtenida:\n";
                echo sprintf('  - Moneda: %s%s', $cotizacion->MonId, PHP_EOL);
                echo sprintf('  - Cotización: %s%s', $cotizacion->MonCotiz, PHP_EOL);
                echo sprintf('  - Fecha: %s%s', $cotizacion->FchCotiz, PHP_EOL);

                // Formatear fecha para mejor legibilidad
                $fechaFormateada = DateTimeImmutable::createFromFormat('Ymd', $cotizacion->FchCotiz);
                if ($fechaFormateada) {
                    echo '  - Fecha formateada: '.$fechaFormateada->format('d/m/Y')."\n";
                }

                // Ejemplo de cálculo
                if ($monedaId !== 'PES' && $cotizacion->MonCotiz > 0) {
                    $importeEnMonedaExtranjera = 100; // USD/EUR/etc
                    $importeEnPesos = $importeEnMonedaExtranjera * $cotizacion->MonCotiz;
                    echo "  - Ejemplo: {$importeEnMonedaExtranjera} {$monedaId} = \${$importeEnPesos} ARS\n";
                }

            } else {
                echo sprintf('❌ No se pudo obtener la cotización para %s%s', $monedaId, PHP_EOL);
            }

        } catch (Exception $e) {
            echo sprintf('❌ Error al consultar %s: %s%s', $monedaId, $e->getMessage(), PHP_EOL);
        }

        echo "\n".str_repeat('-', 50)."\n\n";
    }

    echo "=== FUNCIÓN HELPER PARA COTIZACIONES ===\n";

    // Función helper para obtener cotización con manejo de errores
    function obtenerCotizacionSegura($facturacionElectronica, string $monedaId): ?array
    {
        try {
            $respuesta = $facturacionElectronica->obtenerCotizacionMoneda($monedaId);

            if (isset($respuesta->FEParamGetCotizacionResult->ResultGet)) {
                $cotizacion = $respuesta->FEParamGetCotizacionResult->ResultGet;

                return [
                    'moneda' => $cotizacion->MonId,
                    'cotizacion' => (float) $cotizacion->MonCotiz,
                    'fecha' => $cotizacion->FchCotiz,
                    'fecha_formateada' => DateTimeImmutable::createFromFormat('Ymd', $cotizacion->FchCotiz)?->format('d/m/Y'),
                ];
            }

            return null;
        } catch (Exception $exception) {
            echo sprintf('Error al obtener cotización de %s: %s%s', $monedaId, $exception->getMessage(), PHP_EOL);

            return null;
        }
    }

    // Función para convertir importes usando cotización oficial
    function convertirImporte(float $importe, string $monedaOrigen, string $monedaDestino, $facturacionElectronica): ?float
    {
        // Si es la misma moneda, no hay conversión
        if ($monedaOrigen === $monedaDestino) {
            return $importe;
        }

        // Si convertimos a pesos argentinos
        if ($monedaDestino === 'PES') {
            $cotizacion = obtenerCotizacionSegura($facturacionElectronica, $monedaOrigen);
            if ($cotizacion && $cotizacion['cotizacion'] > 0) {
                return $importe * $cotizacion['cotizacion'];
            }
        }

        // Si convertimos desde pesos argentinos
        if ($monedaOrigen === 'PES') {
            $cotizacion = obtenerCotizacionSegura($facturacionElectronica, $monedaDestino);
            if ($cotizacion && $cotizacion['cotizacion'] > 0) {
                return $importe / $cotizacion['cotizacion'];
            }
        }

        // Para conversiones entre monedas extranjeras, convertir via pesos
        $cotizacionOrigen = obtenerCotizacionSegura($facturacionElectronica, $monedaOrigen);
        $cotizacionDestino = obtenerCotizacionSegura($facturacionElectronica, $monedaDestino);

        if ($cotizacionOrigen && $cotizacionDestino &&
            $cotizacionOrigen['cotizacion'] > 0 && $cotizacionDestino['cotizacion'] > 0) {
            $importeEnPesos = $importe * $cotizacionOrigen['cotizacion'];

            return $importeEnPesos / $cotizacionDestino['cotizacion'];
        }

        return null;
    }

    // Ejemplos de uso de las funciones helper
    echo "Ejemplos de uso de funciones helper:\n\n";

    // Obtener cotización del dólar
    $cotizacionDolar = obtenerCotizacionSegura($facturacionElectronica, 'DOL');
    if ($cotizacionDolar !== null && $cotizacionDolar !== []) {
        echo "✅ Cotización USD: \${$cotizacionDolar['cotizacion']} (fecha: {$cotizacionDolar['fecha_formateada']})\n";

        // Ejemplo de conversión
        $importeUSD = 100;
        $importeARS = convertirImporte($importeUSD, 'DOL', 'PES', $facturacionElectronica);
        if ($importeARS) {
            echo sprintf('   Conversión: USD %d = ARS $%s%s', $importeUSD, $importeARS, PHP_EOL);
        }
    }

    // Obtener cotización del euro
    $cotizacionEuro = obtenerCotizacionSegura($facturacionElectronica, 'EUR');
    if ($cotizacionEuro !== null && $cotizacionEuro !== []) {
        echo "✅ Cotización EUR: \${$cotizacionEuro['cotizacion']} (fecha: {$cotizacionEuro['fecha_formateada']})\n";
    }

    echo "\n=== EJEMPLO DE USO EN FACTURACIÓN ===\n";

    // Ejemplo de cómo usar la cotización en un comprobante
    $monedaFactura = 'DOL';
    $importeOriginal = 500.00; // USD

    $cotizacion = obtenerCotizacionSegura($facturacionElectronica, $monedaFactura);
    if ($cotizacion !== null && $cotizacion !== []) {
        echo "Datos para factura en {$monedaFactura}:\n";
        echo sprintf('- Importe original: %s %s%s', $importeOriginal, $monedaFactura, PHP_EOL);
        echo sprintf('- Cotización AFIP: %s%s', $cotizacion['cotizacion'], PHP_EOL);
        echo sprintf('- Fecha cotización: %s%s', $cotizacion['fecha_formateada'], PHP_EOL);

        $importeEnPesos = $importeOriginal * $cotizacion['cotizacion'];
        echo sprintf('- Equivalente en ARS: $%s%s', $importeEnPesos, PHP_EOL);

        echo "\nEstructura para comprobante:\n";
        echo "[\n";
        echo "    'MonId' => '{$monedaFactura}',\n";
        echo "    'MonCotiz' => {$cotizacion['cotizacion']},\n";
        echo "    'ImpTotal' => {$importeOriginal}, // En moneda original\n";
        echo "    // ... otros campos\n";
        echo "]\n";
    }

    echo "\n=== INFORMACIÓN ADICIONAL ===\n";
    echo "• Las cotizaciones son las oficiales de AFIP para facturación\n";
    echo "• Se actualizan diariamente (días hábiles)\n";
    echo "• Para PES (pesos argentinos) la cotización siempre es 1\n";
    echo "• Use estas cotizaciones en el campo 'MonCotiz' de los comprobantes\n";
    echo "• La fecha indica cuándo fue actualizada la cotización\n";
    echo "• Monedas comunes: DOL (USD), EUR (Euro), BRL (Real), PES (Peso Argentino)\n";

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
