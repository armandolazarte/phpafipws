<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Ejemplo: Obtener tipos de concepto disponibles
 *
 * Este ejemplo muestra cómo consultar los tipos de concepto
 * que puedes usar en tus comprobantes (Productos, Servicios, etc.).
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

    echo "=== CONSULTAR TIPOS DE CONCEPTO ===\n\n";
    echo 'Versión del SDK: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo: '.($afip->esModoProduccion() ? 'Producción' : 'Homologación')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    echo "Consultando tipos de concepto disponibles...\n\n";

    // Obtener tipos de concepto
    $respuesta = $facturacionElectronica->obtenerTiposConcepto();

    echo "✅ Respuesta recibida de AFIP\n\n";

    // Procesar la respuesta
    if (isset($respuesta->FEParamGetTiposConceptoResult->ResultGet->ConceptoTipo)) {
        $tiposConcepto = $respuesta->FEParamGetTiposConceptoResult->ResultGet->ConceptoTipo;

        // Asegurar que sea un array
        if (! is_array($tiposConcepto)) {
            $tiposConcepto = [$tiposConcepto];
        }

        echo "TIPOS DE CONCEPTO DISPONIBLES:\n";
        echo str_repeat('=', 60)."\n";

        foreach ($tiposConcepto as $concepto) {
            $id = $concepto->Id ?? 'N/A';
            $desc = $concepto->Desc ?? 'Sin descripción';

            echo sprintf("• ID: %s\n", $id);
            echo sprintf("  Descripción: %s\n", $desc);

            // Información adicional según el tipo
            switch ($id) {
                case 1:
                    echo "  📦 Tipo: PRODUCTOS\n";
                    echo "  ℹ️  Uso: Venta de bienes tangibles\n";
                    echo "  📅 Fechas de servicio: NO requeridas\n";
                    break;
                case 2:
                    echo "  🔧 Tipo: SERVICIOS\n";
                    echo "  ℹ️  Uso: Prestación de servicios\n";
                    echo "  📅 Fechas de servicio: REQUERIDAS\n";
                    break;
                case 3:
                    echo "  📦🔧 Tipo: PRODUCTOS Y SERVICIOS\n";
                    echo "  ℹ️  Uso: Combinación de bienes y servicios\n";
                    echo "  📅 Fechas de servicio: REQUERIDAS\n";
                    break;
                default:
                    echo "  ℹ️  Tipo: Otro concepto\n";
                    break;
            }

            if (isset($concepto->FchDesde) && $concepto->FchDesde !== null) {
                echo sprintf("  📅 Vigente desde: %s\n", $concepto->FchDesde);
            }

            if (isset($concepto->FchHasta) && $concepto->FchHasta !== null && $concepto->FchHasta !== 'NULL') {
                echo sprintf("  📅 Vigente hasta: %s\n", $concepto->FchHasta);
            } else {
                echo "  📅 Estado: Actualmente vigente\n";
            }

            echo "\n";
        }

        echo sprintf("Total de tipos de concepto: %d\n\n", count($tiposConcepto));

    } else {
        echo "❌ No se encontraron tipos de concepto en la respuesta\n";
        echo "Respuesta completa:\n";
        print_r($respuesta);
    }

    echo "=== GUÍA DE USO EN FACTURACIÓN ===\n\n";

    echo "📋 CAMPOS REQUERIDOS SEGÚN CONCEPTO:\n\n";

    echo "CONCEPTO 1 - PRODUCTOS:\n";
    echo "• FchServDesde: NO requerido (puede ser null)\n";
    echo "• FchServHasta: NO requerido (puede ser null)\n";
    echo "• FchVtoPago: NO requerido (puede ser null)\n";
    echo "• Ejemplo: Venta de mercadería, equipos, etc.\n\n";

    echo "CONCEPTO 2 - SERVICIOS:\n";
    echo "• FchServDesde: REQUERIDO\n";
    echo "• FchServHasta: REQUERIDO\n";
    echo "• FchVtoPago: REQUERIDO\n";
    echo "• Ejemplo: Consultoría, mantenimiento, etc.\n\n";

    echo "CONCEPTO 3 - PRODUCTOS Y SERVICIOS:\n";
    echo "• FchServDesde: REQUERIDO\n";
    echo "• FchServHasta: REQUERIDO\n";
    echo "• FchVtoPago: REQUERIDO\n";
    echo "• Ejemplo: Venta de equipo + instalación\n\n";

    echo "=== EJEMPLO DE IMPLEMENTACIÓN ===\n\n";

    echo "<?php\n";
    echo "// Ejemplo de uso en tu código\n\n";

    echo "function prepararDatosComprobante(\$concepto, \$otrosDatos) {\n";
    echo "    \$datos = [\n";
    echo "        'Concepto' => \$concepto,\n";
    echo "        // ... otros campos básicos\n";
    echo "    ];\n\n";

    echo "    // Agregar campos según el concepto\n";
    echo "    if (\$concepto === 2 || \$concepto === 3) {\n";
    echo "        \$datos['FchServDesde'] = (int) date('Ymd');\n";
    echo "        \$datos['FchServHasta'] = (int) date('Ymd');\n";
    echo "        \$datos['FchVtoPago'] = (int) date('Ymd', strtotime('+30 days'));\n";
    echo "    } else {\n";
    echo "        \$datos['FchServDesde'] = null;\n";
    echo "        \$datos['FchServHasta'] = null;\n";
    echo "        \$datos['FchVtoPago'] = null;\n";
    echo "    }\n\n";

    echo "    return \$datos;\n";
    echo "}\n\n";

    echo "=== VALIDACIÓN DE FECHAS ===\n\n";

    echo "📅 FORMATO DE FECHAS:\n";
    echo "• Formato: AAAAMMDD (entero)\n";
    echo "• Ejemplo: 20240208 para 8 de febrero de 2024\n";
    echo "• FchServDesde: Fecha de inicio del servicio\n";
    echo "• FchServHasta: Fecha de fin del servicio\n";
    echo "• FchVtoPago: Fecha de vencimiento del pago\n\n";

    echo "⚠️  RESTRICCIONES:\n";
    echo "• FchServDesde debe ser <= FchServHasta\n";
    echo "• Las fechas no pueden ser muy anteriores o futuras\n";
    echo "• FchVtoPago generalmente es posterior a la fecha del comprobante\n\n";

    echo "=== CONSEJOS DE USO ===\n\n";
    echo "💡 CONSEJOS:\n";
    echo "• Elige el concepto correcto según tu actividad\n";
    echo "• Para servicios, siempre completa las fechas requeridas\n";
    echo "• El concepto afecta qué campos son obligatorios\n";
    echo "• Consulta esta tabla periódicamente por si hay cambios\n";
    echo "• En caso de duda, usa concepto 3 (más completo)\n\n";

    echo "--- RESPUESTA COMPLETA DE AFIP ---\n";
    print_r($respuesta);

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
    echo sprintf('   Tipo: %s%s', $e->obtenerTipoError(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
