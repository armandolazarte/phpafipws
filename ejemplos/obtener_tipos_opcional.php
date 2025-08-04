<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Ejemplo: Obtener tipos de datos opcionales
 *
 * Este ejemplo muestra cómo consultar los tipos de datos opcionales
 * que se pueden incluir en los comprobantes electrónicos.
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

    echo "=== CONSULTAR TIPOS DE DATOS OPCIONALES ===\n\n";
    echo 'Versión del SDK: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo: '.($afip->esModoProduccion() ? 'Producción' : 'Homologación')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    echo "Consultando tipos de datos opcionales disponibles...\n\n";

    // Obtener tipos opcionales
    $respuesta = $facturacionElectronica->obtenerTiposOpcional();

    echo "✅ Respuesta recibida de AFIP\n\n";

    // Procesar la respuesta
    if (isset($respuesta->FEParamGetTiposOpcionalResult->ResultGet->OpcionalTipo)) {
        $tiposOpcional = $respuesta->FEParamGetTiposOpcionalResult->ResultGet->OpcionalTipo;

        // Asegurar que sea un array
        if (! is_array($tiposOpcional)) {
            $tiposOpcional = [$tiposOpcional];
        }

        echo "TIPOS DE DATOS OPCIONALES DISPONIBLES:\n";
        echo str_repeat('=', 70)."\n";

        foreach ($tiposOpcional as $opcional) {
            $id = $opcional->Id ?? 'N/A';
            $desc = $opcional->Desc ?? 'Sin descripción';

            echo sprintf("• ID: %s\n", $id);
            echo sprintf("  Descripción: %s\n", $desc);

            // Información adicional según el tipo
            switch ($id) {
                case 1:
                    echo "  💳 Tipo: CVU (Clave Virtual Uniforme)\n";
                    echo "  ℹ️  Uso: Para transferencias bancarias digitales\n";
                    echo "  📝 Formato: 22 dígitos numéricos\n";
                    break;
                case 2:
                    echo "  🏦 Tipo: CBU (Clave Bancaria Uniforme)\n";
                    echo "  ℹ️  Uso: Para transferencias bancarias tradicionales\n";
                    echo "  📝 Formato: 22 dígitos numéricos\n";
                    break;
                case 3:
                    echo "  🔤 Tipo: Alias CBU\n";
                    echo "  ℹ️  Uso: Alias alfanumérico del CBU\n";
                    echo "  📝 Formato: Texto alfanumérico\n";
                    break;
                case 4:
                    echo "  📧 Tipo: Email\n";
                    echo "  ℹ️  Uso: Dirección de correo electrónico\n";
                    echo "  📝 Formato: email@dominio.com\n";
                    break;
                case 5:
                    echo "  📱 Tipo: Teléfono\n";
                    echo "  ℹ️  Uso: Número de teléfono de contacto\n";
                    echo "  📝 Formato: Numérico con código de área\n";
                    break;
                default:
                    echo "  ℹ️  Tipo: Otro dato opcional\n";
                    break;
            }

            if (isset($opcional->FchDesde) && $opcional->FchDesde !== null) {
                echo sprintf("  📅 Vigente desde: %s\n", $opcional->FchDesde);
            }

            if (isset($opcional->FchHasta) && $opcional->FchHasta !== null && $opcional->FchHasta !== 'NULL') {
                echo sprintf("  📅 Vigente hasta: %s\n", $opcional->FchHasta);
            } else {
                echo "  📅 Estado: Actualmente vigente\n";
            }

            echo "\n";
        }

        echo sprintf("Total de tipos opcionales: %d\n\n", count($tiposOpcional));

        // Mostrar los más comunes
        $opcionalesComunes = array_filter($tiposOpcional, fn ($o): bool => isset($o->Id) && in_array($o->Id, [1, 2, 3, 4, 5]));

        if ($opcionalesComunes !== []) {
            echo "TIPOS MÁS UTILIZADOS:\n";
            echo str_repeat('-', 40)."\n";
            foreach ($opcionalesComunes as $opcional) {
                $id = $opcional->Id ?? 'N/A';
                $desc = $opcional->Desc ?? 'Sin descripción';
                echo sprintf("• ID %s: %s\n", $id, $desc);
            }

            echo "\n";
        }

    } else {
        // Verificar si hay error en la respuesta
        if (isset($respuesta->FEParamGetTiposOpcionalResult->Errors->Err->Code)) {
            $error = $respuesta->FEParamGetTiposOpcionalResult->Errors->Err;
            echo "ℹ️  INFORMACIÓN SOBRE ERROR:\n";
            echo sprintf('• Código: %s%s', $error->Code, PHP_EOL);
            echo "• Mensaje: {$error->Msg}\n\n";

            if ($error->Code === 602) {
                echo "📋 EXPLICACIÓN:\n";
                echo "• Este error indica que no hay tipos opcionales disponibles\n";
                echo "• Es normal en algunos entornos de homologación\n";
                echo "• En producción, consulta con AFIP sobre disponibilidad\n\n";
            }
        } else {
            echo "❌ No se encontraron tipos opcionales en la respuesta\n";
        }

        echo "Respuesta completa:\n";
        print_r($respuesta);
    }

    echo "\n=== GUÍA DE USO EN FACTURACIÓN ===\n\n";

    echo "📋 ESTRUCTURA DE DATOS OPCIONALES EN COMPROBANTES:\n\n";

    echo "<?php\n";
    echo "// Ejemplo de estructura para el campo 'Opcionales' en comprobantes\n";
    echo "\$datosComprobante = [\n";
    echo "    // ... otros campos\n";
    echo "    'Opcionales' => [\n";
    echo "        [\n";
    echo "            'Id' => 1,                    // ID del tipo opcional (CVU)\n";
    echo "            'Valor' => '1234567890123456789012', // Valor del CVU\n";
    echo "        ],\n";
    echo "        [\n";
    echo "            'Id' => 4,                    // ID del tipo opcional (Email)\n";
    echo "            'Valor' => 'cliente@email.com',      // Email del cliente\n";
    echo "        ],\n";
    echo "        // Puedes agregar más datos opcionales si es necesario\n";
    echo "    ],\n";
    echo "];\n\n";

    echo "=== EJEMPLOS PRÁCTICOS ===\n\n";

    echo "EJEMPLO 1 - Factura con CVU para cobro:\n";
    echo "• Incluir CVU del emisor para facilitar el pago\n";
    echo "• Útil para facturas B2B\n\n";

    echo "<?php\n";
    echo "\$opcionalCVU = [\n";
    echo "    'Id' => 1,\n";
    echo "    'Valor' => '1234567890123456789012', // CVU de 22 dígitos\n";
    echo "];\n\n";

    echo "EJEMPLO 2 - Factura con CBU tradicional:\n";
    echo "• Para clientes que prefieren transferencias bancarias\n";
    echo "• Más común en empresas tradicionales\n\n";

    echo "<?php\n";
    echo "\$opcionalCBU = [\n";
    echo "    'Id' => 2,\n";
    echo "    'Valor' => '1234567890123456789012', // CBU de 22 dígitos\n";
    echo "];\n\n";

    echo "EJEMPLO 3 - Factura con alias CBU:\n";
    echo "• Más fácil de recordar que el CBU numérico\n";
    echo "• Ideal para facturas a consumidores finales\n\n";

    echo "<?php\n";
    echo "\$opcionalAlias = [\n";
    echo "    'Id' => 3,\n";
    echo "    'Valor' => 'MI.EMPRESA.COBROS', // Alias alfanumérico\n";
    echo "];\n\n";

    echo "EJEMPLO 4 - Múltiples datos opcionales:\n";
    echo "• Combinar varios tipos para mayor información\n";
    echo "• Email + CVU para facilitar comunicación y pago\n\n";

    echo "<?php\n";
    echo "\$opcionalesMultiples = [\n";
    echo "    [\n";
    echo "        'Id' => 1,                        // CVU\n";
    echo "        'Valor' => '1234567890123456789012',\n";
    echo "    ],\n";
    echo "    [\n";
    echo "        'Id' => 4,                        // Email\n";
    echo "        'Valor' => 'cobranzas@empresa.com',\n";
    echo "    ],\n";
    echo "    [\n";
    echo "        'Id' => 5,                        // Teléfono\n";
    echo "        'Valor' => '1145678900',\n";
    echo "    ],\n";
    echo "];\n\n";

    echo "=== VALIDACIONES IMPORTANTES ===\n\n";

    echo "⚠️  VALIDACIONES:\n";
    echo "• CVU y CBU deben tener exactamente 22 dígitos\n";
    echo "• Email debe tener formato válido\n";
    echo "• Teléfono debe incluir código de área\n";
    echo "• Alias CBU debe ser alfanumérico (sin espacios)\n";
    echo "• Usa solo IDs de tipos válidos (consulta esta tabla)\n";
    echo "• Los datos opcionales no son obligatorios, pero si se incluyen deben ser válidos\n\n";

    echo "=== FUNCIÓN HELPER PARA VALIDACIONES ===\n\n";

    echo "<?php\n";
    echo "function validarDatoOpcional(\$tipoId, \$valor) {\n";
    echo "    switch (\$tipoId) {\n";
    echo "        case 1: // CVU\n";
    echo "        case 2: // CBU\n";
    echo "            return preg_match('/^\\d{22}$/', \$valor);\n";
    echo "        case 3: // Alias CBU\n";
    echo "            return preg_match('/^[A-Z0-9.]{6,20}$/', \$valor);\n";
    echo "        case 4: // Email\n";
    echo "            return filter_var(\$valor, FILTER_VALIDATE_EMAIL) !== false;\n";
    echo "        case 5: // Teléfono\n";
    echo "            return preg_match('/^\\d{8,15}$/', \$valor);\n";
    echo "        default:\n";
    echo "            return true; // Para tipos desconocidos, asumir válido\n";
    echo "    }\n";
    echo "}\n\n";

    echo "// Uso:\n";
    echo "\$esValido = validarDatoOpcional(1, '1234567890123456789012'); // true para CVU válido\n\n";

    echo "=== CONSEJOS DE USO ===\n\n";

    echo "💡 CONSEJOS:\n";
    echo "• Incluye datos opcionales que faciliten el pago o contacto\n";
    echo "• CVU es más moderno y rápido que CBU tradicional\n";
    echo "• Email es útil para envío automático de comprobantes\n";
    echo "• No abuses de los datos opcionales, incluye solo los necesarios\n";
    echo "• Valida siempre el formato antes de enviar a AFIP\n";
    echo "• En facturas B2C, considera incluir alias CBU por simplicidad\n";
    echo "• En facturas B2B, CVU + email suele ser la mejor combinación\n\n";

    echo "--- RESPUESTA COMPLETA DE AFIP ---\n";
    print_r($respuesta);

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
    echo sprintf('   Tipo: %s%s', $e->obtenerTipoError(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
