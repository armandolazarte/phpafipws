<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Ejemplo: Obtener puntos de venta habilitados
 *
 * Este ejemplo muestra cómo consultar todos los puntos de venta
 * que tienes habilitados en AFIP para facturación electrónica.
 */
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

    echo "=== CONSULTAR PUNTOS DE VENTA HABILITADOS ===\n\n";
    echo 'Versión del SDK: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo: '.($afip->esModoProduccion() ? 'Producción' : 'Homologación')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    echo "Consultando puntos de venta habilitados...\n\n";

    // Obtener puntos de venta
    $respuesta = $facturacionElectronica->obtenerPuntosDeVenta();

    echo "✅ Respuesta recibida de AFIP\n\n";

    // Procesar la respuesta
    if (isset($respuesta->FEParamGetPtosVentaResult->ResultGet)) {
        $puntosVenta = $respuesta->FEParamGetPtosVentaResult->ResultGet;

        // Asegurar que sea un array
        if (! is_array($puntosVenta)) {
            $puntosVenta = [$puntosVenta];
        }

        echo "PUNTOS DE VENTA HABILITADOS:\n";
        echo str_repeat('=', 50)."\n";

        foreach ($puntosVenta as $puntoVenta) {
            echo sprintf("• Punto de Venta: %d\n", $puntoVenta->Id);
            echo sprintf("  Descripción: %s\n", $puntoVenta->Desc ?? 'Sin descripción');

            if (isset($puntoVenta->FchBaja)) {
                echo sprintf("  Estado: INACTIVO (Baja: %s)\n", $puntoVenta->FchBaja);
            } else {
                echo "  Estado: ACTIVO\n";
            }

            echo "\n";
        }

        echo sprintf("Total de puntos de venta: %d\n\n", count($puntosVenta));

        // Mostrar estadísticas
        $activos = array_filter($puntosVenta, fn ($pv): bool => ! isset($pv->FchBaja));
        $inactivos = array_filter($puntosVenta, fn ($pv): bool => isset($pv->FchBaja));

        echo "ESTADÍSTICAS:\n";
        echo sprintf("• Puntos activos: %d\n", count($activos));
        echo sprintf("• Puntos inactivos: %d\n", count($inactivos));

        // Mostrar puntos activos para usar en facturación
        if ($activos !== []) {
            echo "\nPUNTOS ACTIVOS PARA FACTURACIÓN:\n";
            foreach ($activos as $puntoActivo) {
                echo sprintf("• PtoVta: %d - %s\n",
                    $puntoActivo->Id,
                    $puntoActivo->Desc ?? 'Sin descripción'
                );
            }
        }

    } else {
        // Verificar si es el error común 602 en homologación
        if (isset($respuesta->FEParamGetPtosVentaResult->Errors->Err->Code) &&
            $respuesta->FEParamGetPtosVentaResult->Errors->Err->Code === 602) {

            echo "ℹ️  INFORMACIÓN SOBRE ERROR 602:\n";
            echo sprintf('• Código: %d%s', $respuesta->FEParamGetPtosVentaResult->Errors->Err->Code, PHP_EOL);
            echo "• Mensaje: {$respuesta->FEParamGetPtosVentaResult->Errors->Err->Msg}\n\n";

            echo "📋 EXPLICACIÓN:\n";
            echo "• Este error es común en el entorno de homologación\n";
            echo "• Significa que no hay puntos de venta configurados para este CUIT\n";
            echo "• En homologación, generalmente puedes usar el punto de venta 1\n";
            echo "• En producción, necesitas habilitar puntos de venta en AFIP\n\n";

            echo "🔧 SOLUCIONES:\n";
            echo "• Para homologación: usa PtoVta = 1 en tus comprobantes\n";
            echo "• Para producción: solicita habilitación de puntos de venta en AFIP\n";
            echo "• Contacta a tu contador para gestionar puntos de venta\n\n";

        } else {
            echo "❌ No se encontraron puntos de venta en la respuesta\n";
        }

        echo "Respuesta completa:\n";
        print_r($respuesta);
    }

    echo "\n=== EJEMPLO DE USO EN FACTURACIÓN ===\n\n";

    // Ejemplo práctico: usar el primer punto de venta activo
    if (isset($puntosVenta) && ! empty($puntosVenta)) {
        $primerPuntoActivo = null;

        foreach ($puntosVenta as $pv) {
            if (! isset($pv->FchBaja)) {
                $primerPuntoActivo = $pv;
                break;
            }
        }

        if ($primerPuntoActivo) {
            echo "Usando punto de venta {$primerPuntoActivo->Id} para obtener último comprobante:\n\n";

            // Obtener último número de diferentes tipos de comprobante
            $tiposComprobante = [
                1 => 'Factura A',
                6 => 'Factura B',
                11 => 'Factura C',
                3 => 'Nota de Crédito A',
                8 => 'Nota de Crédito B',
                13 => 'Nota de Crédito C',
            ];

            foreach ($tiposComprobante as $tipo => $descripcion) {
                try {
                    $ultimoNumero = $facturacionElectronica->obtenerUltimoNumeroComprobante(
                        $primerPuntoActivo->Id,
                        $tipo
                    );

                    echo sprintf("• %s (tipo %d): último N° %d\n",
                        $descripcion,
                        $tipo,
                        $ultimoNumero
                    );
                } catch (Exception $e) {
                    echo sprintf("• %s (tipo %d): Error - %s\n",
                        $descripcion,
                        $tipo,
                        $e->getMessage()
                    );
                }
            }
        }
    } else {
        // Si no hay puntos de venta disponibles, mostrar ejemplo con PtoVta = 1
        echo "Como no hay puntos de venta disponibles, mostrando ejemplo con PtoVta = 1:\n\n";

        $tiposComprobante = [
            1 => 'Factura A',
            6 => 'Factura B',
            11 => 'Factura C',
        ];

        foreach ($tiposComprobante as $tipo => $descripcion) {
            try {
                $ultimoNumero = $facturacionElectronica->obtenerUltimoNumeroComprobante(1, $tipo);
                echo sprintf("• %s (tipo %d): último N° %d\n", $descripcion, $tipo, $ultimoNumero);
            } catch (Exception $e) {
                echo sprintf("• %s (tipo %d): Error - %s\n", $descripcion, $tipo, $e->getMessage());
            }
        }

        echo "\n💡 NOTA: En homologación, PtoVta = 1 suele funcionar aunque no aparezca en la lista.\n";
    }

    echo "\n=== CONSEJOS DE USO ===\n\n";
    echo "💡 CONSEJOS:\n";
    echo "• Consulta tus puntos de venta antes de emitir comprobantes\n";
    echo "• Solo usa puntos de venta activos (sin fecha de baja)\n";
    echo "• Cada punto de venta tiene numeración independiente\n";
    echo "• Guarda esta información para validaciones en tu sistema\n";
    echo "• En homologación, generalmente tienes el punto de venta 1\n";
    echo "• En producción, puedes tener múltiples puntos según tu habilitación\n\n";

    echo "--- RESPUESTA COMPLETA DE AFIP ---\n";
    print_r($respuesta);

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
    echo sprintf('   Tipo: %s%s', $e->obtenerTipoError(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
