<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Ejemplo: Obtener tipos de tributos
 *
 * Este ejemplo muestra cómo consultar los tipos de tributos
 * que se pueden aplicar a los comprobantes electrónicos.
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

    echo "=== CONSULTAR TIPOS DE TRIBUTOS ===\n\n";
    echo 'Versión del SDK: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo: '.($afip->esModoProduccion() ? 'Producción' : 'Homologación')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    echo "Consultando tipos de tributos disponibles...\n\n";

    // Obtener tipos de tributos
    $respuesta = $facturacionElectronica->obtenerTiposTributo();

    echo "✅ Respuesta recibida de AFIP\n\n";

    // Procesar la respuesta
    if (isset($respuesta->FEParamGetTiposTributosResult->ResultGet->TributoTipo)) {
        $tiposTributo = $respuesta->FEParamGetTiposTributosResult->ResultGet->TributoTipo;

        // Asegurar que sea un array
        if (! is_array($tiposTributo)) {
            $tiposTributo = [$tiposTributo];
        }

        echo "TIPOS DE TRIBUTOS DISPONIBLES:\n";
        echo str_repeat('=', 70)."\n";

        // Ordenar por ID para mejor visualización
        usort($tiposTributo, fn ($a, $b) => ($a->Id ?? 0) <=> ($b->Id ?? 0));

        foreach ($tiposTributo as $tributo) {
            $id = $tributo->Id ?? 'N/A';
            $desc = $tributo->Desc ?? 'Sin descripción';

            echo sprintf("• ID: %s\n", $id);
            echo sprintf("  Descripción: %s\n", $desc);

            // Información adicional según el tipo
            switch ($id) {
                case 1:
                    echo "  🏛️ Tipo: IMPUESTOS NACIONALES\n";
                    echo "  ℹ️  Uso: Impuestos administrados por AFIP\n";
                    echo "  📝 Ejemplos: Impuesto a las Ganancias, Bienes Personales\n";
                    break;
                case 2:
                    echo "  🏢 Tipo: IMPUESTOS PROVINCIALES\n";
                    echo "  ℹ️  Uso: Impuestos de cada provincia\n";
                    echo "  📝 Ejemplos: Ingresos Brutos, Sellos\n";
                    break;
                case 3:
                    echo "  🏘️ Tipo: IMPUESTOS MUNICIPALES\n";
                    echo "  ℹ️  Uso: Impuestos de municipios/comunas\n";
                    echo "  📝 Ejemplos: Tasas municipales, Habilitaciones\n";
                    break;
                case 4:
                    echo "  🍷 Tipo: IMPUESTOS INTERNOS\n";
                    echo "  ℹ️  Uso: Impuestos específicos sobre productos\n";
                    echo "  📝 Ejemplos: Combustibles, Bebidas alcohólicas, Tabacos\n";
                    break;
                case 5:
                    echo "  💰 Tipo: PERCEPCIONES\n";
                    echo "  ℹ️  Uso: Retenciones y percepciones\n";
                    echo "  📝 Ejemplos: Percepciones de IVA, Ganancias\n";
                    break;
                case 6:
                    echo "  📊 Tipo: OTROS TRIBUTOS\n";
                    echo "  ℹ️  Uso: Tributos especiales o específicos\n";
                    echo "  📝 Ejemplos: Contribuciones especiales\n";
                    break;
                default:
                    echo "  ℹ️  Tipo: Otro tributo\n";
                    break;
            }

            if (isset($tributo->FchDesde) && $tributo->FchDesde !== null) {
                echo sprintf("  📅 Vigente desde: %s\n", $tributo->FchDesde);
            }

            if (isset($tributo->FchHasta) && $tributo->FchHasta !== null && $tributo->FchHasta !== 'NULL') {
                echo sprintf("  📅 Vigente hasta: %s\n", $tributo->FchHasta);
            } else {
                echo "  📅 Estado: Actualmente vigente\n";
            }

            echo "\n";
        }

        echo sprintf("Total de tipos de tributos: %d\n\n", count($tiposTributo));

        // Mostrar los más comunes
        $tributosComunes = array_filter($tiposTributo, fn ($t) => isset($t->Id) && in_array($t->Id, [1, 2, 3, 4, 5]));

        if (! empty($tributosComunes)) {
            echo "TRIBUTOS MÁS UTILIZADOS:\n";
            echo str_repeat('-', 40)."\n";
            foreach ($tributosComunes as $tributo) {
                $id = $tributo->Id ?? 'N/A';
                $desc = $tributo->Desc ?? 'Sin descripción';
                echo sprintf("• ID %s: %s\n", $id, $desc);
            }
            echo "\n";
        }

    } else {
        // Verificar si hay error en la respuesta
        if (isset($respuesta->FEParamGetTiposTributosResult->Errors->Err->Code)) {
            $error = $respuesta->FEParamGetTiposTributosResult->Errors->Err;
            echo "ℹ️  INFORMACIÓN SOBRE ERROR:\n";
            echo "• Código: {$error->Code}\n";
            echo "• Mensaje: {$error->Msg}\n\n";

            if ($error->Code === 602) {
                echo "📋 EXPLICACIÓN:\n";
                echo "• Este error indica que no hay tipos de tributos disponibles\n";
                echo "• Es normal en algunos entornos de homologación\n";
                echo "• En producción, consulta con AFIP sobre disponibilidad\n\n";
            }
        } else {
            echo "❌ No se encontraron tipos de tributos en la respuesta\n";
        }

        echo "Respuesta completa:\n";
        print_r($respuesta);
    }

    echo "\n=== GUÍA DE USO EN FACTURACIÓN ===\n\n";

    echo "📋 ESTRUCTURA DE TRIBUTOS EN COMPROBANTES:\n\n";

    echo "<?php\n";
    echo "// Ejemplo de estructura para el campo 'Tributos' en comprobantes\n";
    echo "\$datosComprobante = [\n";
    echo "    // ... otros campos\n";
    echo "    'Tributos' => [\n";
    echo "        [\n";
    echo "            'Id' => 2,           // ID del tipo de tributo (Imp. Provinciales)\n";
    echo "            'Desc' => 'Ingresos Brutos', // Descripción del tributo\n";
    echo "            'BaseImp' => 1000.00,        // Base imponible\n";
    echo "            'Alic' => 3.5,               // Alícuota (%)\n";
    echo "            'Importe' => 35.00,          // Importe del tributo\n";
    echo "        ],\n";
    echo "        // Puedes agregar más tributos si es necesario\n";
    echo "    ],\n";
    echo "    'ImpTrib' => 35.00, // Suma total de todos los tributos\n";
    echo "];\n\n";

    echo "=== EJEMPLOS PRÁCTICOS ===\n\n";

    echo "EJEMPLO 1 - Factura con Ingresos Brutos:\n";
    echo "• Tributo provincial más común\n";
    echo "• Varía según la provincia y actividad\n\n";

    echo "<?php\n";
    echo "\$tributoIIBB = [\n";
    echo "    'Id' => 2,                    // Impuestos Provinciales\n";
    echo "    'Desc' => 'Ingresos Brutos',\n";
    echo "    'BaseImp' => 1000.00,        // Base sobre la que se calcula\n";
    echo "    'Alic' => 3.5,               // 3.5% de alícuota\n";
    echo "    'Importe' => 35.00,          // 1000 * 3.5% = 35\n";
    echo "];\n\n";

    echo "EJEMPLO 2 - Factura con Impuestos Internos:\n";
    echo "• Para productos específicos (combustibles, bebidas, etc.)\n";
    echo "• Alícuotas fijas por unidad o porcentuales\n\n";

    echo "<?php\n";
    echo "\$tributoInternos = [\n";
    echo "    'Id' => 4,                    // Impuestos Internos\n";
    echo "    'Desc' => 'Combustibles',\n";
    echo "    'BaseImp' => 500.00,\n";
    echo "    'Alic' => 15.0,              // 15% para combustibles\n";
    echo "    'Importe' => 75.00,\n";
    echo "];\n\n";

    echo "EJEMPLO 3 - Factura con múltiples tributos:\n";
    echo "• Combinación de tributos nacionales y provinciales\n";
    echo "• Común en empresas con actividades complejas\n\n";

    echo "<?php\n";
    echo "\$tributosMultiples = [\n";
    echo "    [\n";
    echo "        'Id' => 2,               // Ingresos Brutos\n";
    echo "        'Desc' => 'IIBB CABA',\n";
    echo "        'BaseImp' => 1000.00,\n";
    echo "        'Alic' => 3.0,\n";
    echo "        'Importe' => 30.00,\n";
    echo "    ],\n";
    echo "    [\n";
    echo "        'Id' => 3,               // Impuestos Municipales\n";
    echo "        'Desc' => 'Tasa Municipal',\n";
    echo "        'BaseImp' => 1000.00,\n";
    echo "        'Alic' => 1.2,\n";
    echo "        'Importe' => 12.00,\n";
    echo "    ],\n";
    echo "];\n";
    echo "// ImpTrib total = 30.00 + 12.00 = 42.00\n\n";

    echo "EJEMPLO 4 - Percepciones:\n";
    echo "• Retenciones aplicadas por el comprador\n";
    echo "• Común en operaciones B2B\n\n";

    echo "<?php\n";
    echo "\$percepcion = [\n";
    echo "    'Id' => 5,                   // Percepciones\n";
    echo "    'Desc' => 'Percepción IVA',\n";
    echo "    'BaseImp' => 1000.00,\n";
    echo "    'Alic' => 2.0,              // 2% de percepción\n";
    echo "    'Importe' => 20.00,\n";
    echo "];\n\n";

    echo "=== CÁLCULOS Y VALIDACIONES ===\n\n";

    echo "⚠️  VALIDACIONES IMPORTANTES:\n";
    echo "• La suma de todos los importes de tributos debe coincidir con ImpTrib\n";
    echo "• BaseImp generalmente coincide con ImpNeto del comprobante\n";
    echo "• Alícuota debe ser coherente: Importe = BaseImp * (Alic/100)\n";
    echo "• Usa solo IDs de tributos válidos (consulta esta tabla)\n";
    echo "• Descripción debe ser clara y específica\n";
    echo "• Algunos tributos son excluyentes entre sí\n\n";

    echo "=== FUNCIÓN HELPER PARA CÁLCULOS ===\n\n";

    echo "<?php\n";
    echo "function calcularTributo(\$baseImponible, \$alicuota) {\n";
    echo "    return round(\$baseImponible * (\$alicuota / 100), 2);\n";
    echo "}\n\n";

    echo "function validarTributos(\$tributos) {\n";
    echo "    \$totalCalculado = 0;\n";
    echo "    \n";
    echo "    foreach (\$tributos as \$tributo) {\n";
    echo "        // Validar cálculo\n";
    echo "        \$importeCalculado = calcularTributo(\$tributo['BaseImp'], \$tributo['Alic']);\n";
    echo "        if (abs(\$importeCalculado - \$tributo['Importe']) > 0.01) {\n";
    echo "            throw new Exception('Error en cálculo de tributo: ' . \$tributo['Desc']);\n";
    echo "        }\n";
    echo "        \n";
    echo "        \$totalCalculado += \$tributo['Importe'];\n";
    echo "    }\n";
    echo "    \n";
    echo "    return \$totalCalculado;\n";
    echo "}\n\n";

    echo "// Uso:\n";
    echo "\$importe = calcularTributo(1000, 3.5); // \$35.00\n";
    echo "\$totalTributos = validarTributos(\$tributosArray);\n\n";

    echo "=== CONSIDERACIONES POR JURISDICCIÓN ===\n\n";

    echo "🗺️ TRIBUTOS POR JURISDICCIÓN:\n\n";

    echo "NACIONALES (ID 1):\n";
    echo "• Impuesto a las Ganancias\n";
    echo "• Impuesto a los Bienes Personales\n";
    echo "• Impuesto al Valor Agregado (IVA) - se maneja por separado\n";
    echo "• Monotributo\n\n";

    echo "PROVINCIALES (ID 2):\n";
    echo "• Ingresos Brutos (varía por provincia)\n";
    echo "• Impuesto de Sellos\n";
    echo "• Impuesto Inmobiliario\n";
    echo "• Patente de Vehículos\n\n";

    echo "MUNICIPALES (ID 3):\n";
    echo "• Tasas por servicios municipales\n";
    echo "• Derechos de oficina\n";
    echo "• Habilitaciones comerciales\n";
    echo "• Inspección de seguridad\n\n";

    echo "INTERNOS (ID 4):\n";
    echo "• Combustibles líquidos\n";
    echo "• Bebidas alcohólicas\n";
    echo "• Productos de tabaco\n";
    echo "• Neumáticos\n\n";

    echo "=== CONSEJOS DE USO ===\n\n";

    echo "💡 CONSEJOS:\n";
    echo "• Consulta con tu contador sobre qué tributos aplicar\n";
    echo "• Ingresos Brutos es el tributo más común en facturas B2B\n";
    echo "• Verifica las alícuotas vigentes en cada jurisdicción\n";
    echo "• Algunos tributos son específicos por actividad\n";
    echo "• Mantén actualizada la tabla de tributos\n";
    echo "• En caso de duda, es mejor no incluir el tributo\n";
    echo "• Los tributos incorrectos pueden generar rechazos de AFIP\n\n";

    echo "⚠️  IMPORTANTE:\n";
    echo "• Los tributos son adicionales al IVA\n";
    echo "• No todos los comprobantes requieren tributos\n";
    echo "• La responsabilidad de aplicar tributos es del emisor\n";
    echo "• Consulta la normativa específica de cada jurisdicción\n\n";

    echo "--- RESPUESTA COMPLETA DE AFIP ---\n";
    print_r($respuesta);

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
    echo sprintf('   Tipo: %s%s', $e->obtenerTipoError(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
