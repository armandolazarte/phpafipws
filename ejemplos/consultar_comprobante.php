<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Ejemplo: Consultar información de un comprobante específico
 *
 * Este ejemplo muestra cómo consultar los detalles de un comprobante
 * ya autorizado utilizando su número, punto de venta y tipo.
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

    echo "=== CONSULTAR INFORMACIÓN DE COMPROBANTE ===\n\n";
    echo 'Versión del SDK: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo: '.($afip->esModoProduccion() ? 'Producción' : 'Homologación')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    // Parámetros del comprobante a consultar
    $numeroComprobante = 1; // Número del comprobante
    $puntoVenta = 1;        // Punto de venta
    $tipoComprobante = 11;  // Tipo de comprobante (11 = Factura C)

    echo "Consultando comprobante:\n";
    echo sprintf('• Número: %d%s', $numeroComprobante, PHP_EOL);
    echo sprintf('• Punto de venta: %d%s', $puntoVenta, PHP_EOL);
    echo "• Tipo: {$tipoComprobante} (Factura C)\n\n";

    // Consultar información del comprobante
    $informacionComprobante = $facturacionElectronica->obtenerInformacionComprobante(
        $numeroComprobante,
        $puntoVenta,
        $tipoComprobante
    );

    if ($informacionComprobante === null) {
        echo "❌ El comprobante no fue encontrado.\n";
        echo "   Verifica que el número, punto de venta y tipo sean correctos.\n\n";
    } else {
        echo "✅ Comprobante encontrado:\n\n";

        // Mostrar información básica
        echo "INFORMACIÓN BÁSICA:\n";
        $numero = $informacionComprobante->CbteNro ?? $informacionComprobante->CbteDesde ?? 'N/A';
        echo sprintf('• Número: %s%s', $numero, PHP_EOL);
        echo sprintf('• Punto de venta: %s%s', $informacionComprobante->PtoVta, PHP_EOL);
        echo sprintf('• Tipo: %s%s', $informacionComprobante->CbteTipo, PHP_EOL);
        echo sprintf('• Fecha: %s%s', $informacionComprobante->CbteFch, PHP_EOL);

        // Mostrar información de autorización
        $cae = $informacionComprobante->CAE ?? $informacionComprobante->CodAutorizacion ?? null;
        if ($cae !== null) {
            echo "\nAUTORIZACIÓN:\n";
            echo sprintf('• CAE: %s%s', $cae, PHP_EOL);
            $vencimiento = $informacionComprobante->CAEFchVto ?? $informacionComprobante->FchVto ?? 'N/A';
            echo sprintf('• Vencimiento CAE: %s%s', $vencimiento, PHP_EOL);
            echo sprintf('• Resultado: %s%s', $informacionComprobante->Resultado, PHP_EOL);
        }

        // Mostrar información del receptor
        if (isset($informacionComprobante->DocTipo)) {
            echo "\nRECEPTOR:\n";
            echo sprintf('• Tipo documento: %s%s', $informacionComprobante->DocTipo, PHP_EOL);
            echo sprintf('• Número documento: %s%s', $informacionComprobante->DocNro, PHP_EOL);
        }

        // Mostrar importes
        if (isset($informacionComprobante->ImpTotal)) {
            echo "\nIMPORTES:\n";
            echo sprintf('• Total: $%s%s', $informacionComprobante->ImpTotal, PHP_EOL);

            if (isset($informacionComprobante->ImpNeto)) {
                echo sprintf('• Neto: $%s%s', $informacionComprobante->ImpNeto, PHP_EOL);
            }

            if (isset($informacionComprobante->ImpIVA)) {
                echo sprintf('• IVA: $%s%s', $informacionComprobante->ImpIVA, PHP_EOL);
            }
        }

        // Mostrar moneda
        if (isset($informacionComprobante->MonId)) {
            echo "\nMONEDA:\n";
            echo sprintf('• Tipo: %s%s', $informacionComprobante->MonId, PHP_EOL);
            echo sprintf('• Cotización: %s%s', $informacionComprobante->MonCotiz, PHP_EOL);
        }

        echo "\n--- RESPUESTA COMPLETA ---\n";
        print_r($informacionComprobante);
    }

    echo "\n=== EJEMPLO DE BÚSQUEDA MÚLTIPLE ===\n\n";

    // Ejemplo: buscar varios comprobantes
    $comprobantesABuscar = [
        ['numero' => 1, 'tipo' => 11, 'descripcion' => 'Factura C'],
        ['numero' => 2, 'tipo' => 11, 'descripcion' => 'Factura C'],
        ['numero' => 1, 'tipo' => 6, 'descripcion' => 'Factura B'],
    ];

    echo "Buscando múltiples comprobantes:\n\n";

    foreach ($comprobantesABuscar as $comprobante) {
        echo sprintf('Consultando %s N° %s... ', $comprobante['descripcion'], $comprobante['numero']);

        $info = $facturacionElectronica->obtenerInformacionComprobante(
            $comprobante['numero'],
            $puntoVenta,
            $comprobante['tipo']
        );

        if ($info === null) {
            echo "❌ No encontrado\n";
        } else {
            $cae = $info->CAE ?? $info->CodAutorizacion ?? 'N/A';
            echo sprintf('✅ Encontrado - CAE: %s%s', $cae, PHP_EOL);
        }
    }

    echo "\n=== CONSEJOS DE USO ===\n\n";
    echo "💡 CONSEJOS:\n";
    echo "• Usa este método para verificar el estado de comprobantes autorizados\n";
    echo "• Útil para auditorías y reconciliaciones\n";
    echo "• El método devuelve null si el comprobante no existe\n";
    echo "• Puedes consultar comprobantes de cualquier tipo (A, B, C, etc.)\n";
    echo "• La información incluye CAE, fechas, importes y datos del receptor\n\n";

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
    echo sprintf('   Tipo: %s%s', $e->obtenerTipoError(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
