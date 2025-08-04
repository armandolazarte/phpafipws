<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Ejemplo: Obtener tipos de alícuotas de IVA disponibles
 *
 * Este ejemplo muestra cómo consultar todas las alícuotas de IVA
 * que puedes usar en tus comprobantes (0%, 10.5%, 21%, 27%, etc.).
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

    echo "=== CONSULTAR TIPOS DE ALÍCUOTAS DE IVA ===\n\n";
    echo 'Versión del SDK: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo: '.($afip->esModoProduccion() ? 'Producción' : 'Homologación')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    echo "Consultando tipos de alícuotas de IVA disponibles...\n\n";

    // Obtener tipos de alícuotas
    $respuesta = $facturacionElectronica->obtenerTiposAlicuota();

    echo "✅ Respuesta recibida de AFIP\n\n";

    // Procesar la respuesta
    if (isset($respuesta->FEParamGetTiposIvaResult->ResultGet->IvaTipo)) {
        $tiposAlicuota = $respuesta->FEParamGetTiposIvaResult->ResultGet->IvaTipo;

        // Asegurar que sea un array
        if (! is_array($tiposAlicuota)) {
            $tiposAlicuota = [$tiposAlicuota];
        }

        echo "TIPOS DE ALÍCUOTAS DE IVA DISPONIBLES:\n";
        echo str_repeat('=', 70)."\n";

        // Ordenar por ID para mejor visualización
        usort($tiposAlicuota, fn ($a, $b) => $a->Id <=> $b->Id);

        foreach ($tiposAlicuota as $alicuota) {
            $id = $alicuota->Id ?? 'N/A';
            $desc = $alicuota->Desc ?? 'Sin descripción';

            echo sprintf("• ID: %s\n", $id);
            echo sprintf("  Descripción: %s\n", $desc);

            // Información adicional según el tipo
            switch ($id) {
                case 3:
                    echo "  💰 Alícuota: 0%\n";
                    echo "  📝 Uso: Productos exentos de IVA\n";
                    break;
                case 4:
                    echo "  💰 Alícuota: 10.5%\n";
                    echo "  📝 Uso: Alícuota reducida (libros, medicamentos, etc.)\n";
                    break;
                case 5:
                    echo "  💰 Alícuota: 21%\n";
                    echo "  📝 Uso: Alícuota general (mayoría de productos/servicios)\n";
                    break;
                case 6:
                    echo "  💰 Alícuota: 27%\n";
                    echo "  📝 Uso: Alícuota especial (servicios específicos)\n";
                    break;
                case 8:
                    echo "  💰 Alícuota: 5%\n";
                    echo "  📝 Uso: Alícuota reducida especial\n";
                    break;
                case 9:
                    echo "  💰 Alícuota: 2.5%\n";
                    echo "  📝 Uso: Alícuota mínima especial\n";
                    break;
                default:
                    echo "  💰 Alícuota: Ver descripción\n";
                    break;
            }

            if (isset($alicuota->FchDesde) && $alicuota->FchDesde !== null) {
                echo sprintf("  📅 Vigente desde: %s\n", $alicuota->FchDesde);
            }

            if (isset($alicuota->FchHasta) && $alicuota->FchHasta !== null && $alicuota->FchHasta !== 'NULL') {
                echo sprintf("  📅 Vigente hasta: %s\n", $alicuota->FchHasta);
            } else {
                echo "  📅 Vigente: Actualmente vigente\n";
            }

            echo "\n";
        }

        echo sprintf("Total de tipos de alícuota: %d\n\n", count($tiposAlicuota));

        // Mostrar las más comunes
        $alicuotasComunes = array_filter($tiposAlicuota, fn ($a) => isset($a->Id) && in_array($a->Id, [3, 4, 5, 6]));

        if (! empty($alicuotasComunes)) {
            echo "ALÍCUOTAS MÁS UTILIZADAS:\n";
            echo str_repeat('-', 40)."\n";
            foreach ($alicuotasComunes as $alicuota) {
                $id = $alicuota->Id ?? 'N/A';
                $desc = $alicuota->Desc ?? 'Sin descripción';
                echo sprintf("• ID %s: %s\n", $id, $desc);
            }
            echo "\n";
        }

    } else {
        echo "❌ No se encontraron tipos de alícuota en la respuesta\n";
        echo "Respuesta completa:\n";
        print_r($respuesta);
    }

    echo "=== GUÍA DE USO EN FACTURACIÓN ===\n\n";

    echo "📋 ESTRUCTURA DEL ARRAY IVA EN COMPROBANTES:\n\n";

    echo "<?php\n";
    echo "// Ejemplo de estructura para el campo 'Iva' en comprobantes\n";
    echo "\$datosComprobante = [\n";
    echo "    // ... otros campos\n";
    echo "    'Iva' => [\n";
    echo "        [\n";
    echo "            'Id' => 5,        // ID de la alícuota (21%)\n";
    echo "            'BaseImp' => 100, // Base imponible\n";
    echo "            'Importe' => 21,  // Importe del IVA\n";
    echo "        ],\n";
    echo "        // Puedes agregar más alícuotas si es necesario\n";
    echo "    ],\n";
    echo "];\n\n";

    echo "=== EJEMPLOS PRÁCTICOS ===\n\n";

    echo "EJEMPLO 1 - Factura con IVA 21%:\n";
    echo "• Base imponible: \$100\n";
    echo "• IVA (21%): \$21\n";
    echo "• Total: \$121\n";
    echo "• Código alícuota: 5\n\n";

    echo "<?php\n";
    echo "\$iva21 = [\n";
    echo "    'Id' => 5,\n";
    echo "    'BaseImp' => 100.00,\n";
    echo "    'Importe' => 21.00,\n";
    echo "];\n\n";

    echo "EJEMPLO 2 - Factura con IVA 10.5%:\n";
    echo "• Base imponible: \$200\n";
    echo "• IVA (10.5%): \$21\n";
    echo "• Total: \$221\n";
    echo "• Código alícuota: 4\n\n";

    echo "<?php\n";
    echo "\$iva10_5 = [\n";
    echo "    'Id' => 4,\n";
    echo "    'BaseImp' => 200.00,\n";
    echo "    'Importe' => 21.00,\n";
    echo "];\n\n";

    echo "EJEMPLO 3 - Factura con múltiples alícuotas:\n";
    echo "• Productos con IVA 21%: \$100 + \$21 = \$121\n";
    echo "• Libros con IVA 10.5%: \$50 + \$5.25 = \$55.25\n";
    echo "• Total: \$176.25\n\n";

    echo "<?php\n";
    echo "\$ivaMultiple = [\n";
    echo "    [\n";
    echo "        'Id' => 5,        // 21%\n";
    echo "        'BaseImp' => 100.00,\n";
    echo "        'Importe' => 21.00,\n";
    echo "    ],\n";
    echo "    [\n";
    echo "        'Id' => 4,        // 10.5%\n";
    echo "        'BaseImp' => 50.00,\n";
    echo "        'Importe' => 5.25,\n";
    echo "    ],\n";
    echo "];\n\n";

    echo "=== VALIDACIONES IMPORTANTES ===\n\n";

    echo "⚠️  VALIDACIONES:\n";
    echo "• La suma de BaseImp debe coincidir con ImpNeto del comprobante\n";
    echo "• La suma de Importe debe coincidir con ImpIVA del comprobante\n";
    echo "• Usa solo IDs de alícuotas válidas (consulta esta tabla)\n";
    echo "• El cálculo del IVA debe ser exacto: BaseImp * (Alícuota/100)\n";
    echo "• Para productos exentos, usa alícuota ID 3 (0%)\n\n";

    echo "=== FUNCIÓN HELPER PARA CÁLCULOS ===\n\n";

    echo "<?php\n";
    echo "function calcularIVA(\$baseImponible, \$alicuotaId) {\n";
    echo "    \$porcentajes = [\n";
    echo "        3 => 0,      // 0%\n";
    echo "        4 => 10.5,   // 10.5%\n";
    echo "        5 => 21,     // 21%\n";
    echo "        6 => 27,     // 27%\n";
    echo "        8 => 5,      // 5%\n";
    echo "        9 => 2.5,    // 2.5%\n";
    echo "    ];\n\n";
    echo "    \$porcentaje = \$porcentajes[\$alicuotaId] ?? 0;\n";
    echo "    return round(\$baseImponible * (\$porcentaje / 100), 2);\n";
    echo "}\n\n";

    echo "// Uso:\n";
    echo "\$base = 100;\n";
    echo "\$iva = calcularIVA(\$base, 5); // \$21.00\n\n";

    echo "=== CONSEJOS DE USO ===\n\n";
    echo "💡 CONSEJOS:\n";
    echo "• Consulta esta tabla antes de implementar cálculos de IVA\n";
    echo "• Guarda los códigos más usados en tu configuración\n";
    echo "• Valida siempre que los cálculos sean exactos\n";
    echo "• Para exportaciones, generalmente se usa alícuota 0%\n";
    echo "• Algunos productos tienen alícuotas especiales (medicamentos, libros)\n";
    echo "• En caso de duda, consulta con un contador\n\n";

    echo "--- RESPUESTA COMPLETA DE AFIP ---\n";
    print_r($respuesta);

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
    echo sprintf('   Tipo: %s%s', $e->obtenerTipoError(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
