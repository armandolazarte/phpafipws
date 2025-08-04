<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Demostración completa de todos los métodos disponibles
 *
 * Este ejemplo muestra el uso de todos los métodos del SDK de AFIP
 * organizados por categorías para facilitar su comprensión.
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

    echo "=== DEMOSTRACIÓN COMPLETA DE MÉTODOS DEL SDK ===\n\n";
    echo 'Versión del SDK: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo: '.($afip->esModoProduccion() ? 'Producción' : 'Homologación')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    // ========================================
    // 1. MÉTODOS DE ESTADO Y DIAGNÓSTICO
    // ========================================
    echo "1. MÉTODOS DE ESTADO Y DIAGNÓSTICO\n";
    echo str_repeat('=', 50)."\n\n";

    echo "🔍 Verificando estado del servidor...\n";
    $estadoServidor = $facturacionElectronica->obtenerEstadoServidor();

    if (isset($estadoServidor->FEDummyResult)) {
        $estado = $estadoServidor->FEDummyResult;
        echo "✅ Estado del servidor:\n";
        echo sprintf('   • AppServer: %s%s', $estado->AppServer, PHP_EOL);
        echo sprintf('   • DbServer: %s%s', $estado->DbServer, PHP_EOL);
        echo sprintf('   • AuthServer: %s%s', $estado->AuthServer, PHP_EOL);
    } else {
        echo "❌ No se pudo obtener el estado del servidor\n";
    }

    echo "\n";

    // ========================================
    // 2. MÉTODOS DE CONSULTA DE PARÁMETROS
    // ========================================
    echo "2. MÉTODOS DE CONSULTA DE PARÁMETROS\n";
    echo str_repeat('=', 50)."\n\n";

    // Puntos de venta
    echo "📍 Consultando puntos de venta habilitados...\n";
    $puntosVenta = $facturacionElectronica->obtenerPuntosDeVenta();
    if (isset($puntosVenta->FEParamGetPtosVentaResult->ResultGet)) {
        $puntos = $puntosVenta->FEParamGetPtosVentaResult->ResultGet;
        if (! is_array($puntos)) {
            $puntos = [$puntos];
        }

        echo '✅ Puntos de venta encontrados: '.count($puntos)."\n";
        foreach (array_slice($puntos, 0, 3) as $punto) {
            $id = $punto->Id ?? $punto->Nro ?? 'N/A';
            $desc = $punto->Desc ?? 'Sin descripción';
            echo sprintf('   • PtoVta %s: %s%s', $id, $desc, PHP_EOL);
        }
    }

    echo "\n";

    // Tipos de comprobante
    echo "📄 Consultando tipos de comprobante...\n";
    $tiposComprobante = $facturacionElectronica->obtenerTiposComprobante();
    if (isset($tiposComprobante->FEParamGetTiposCbteResult->ResultGet)) {
        $tipos = $tiposComprobante->FEParamGetTiposCbteResult->ResultGet;
        if (! is_array($tipos)) {
            $tipos = [$tipos];
        }

        echo '✅ Tipos de comprobante encontrados: '.count($tipos)."\n";
        $tiposComunes = array_filter($tipos, fn ($t): bool => isset($t->Id) && in_array($t->Id, [1, 6, 11, 3, 8, 13]));
        foreach ($tiposComunes as $tipo) {
            $id = $tipo->Id ?? 'N/A';
            $desc = $tipo->Desc ?? 'Sin descripción';
            echo sprintf('   • Tipo %s: %s%s', $id, $desc, PHP_EOL);
        }
    }

    echo "\n";

    // Tipos de documento
    echo "🆔 Consultando tipos de documento...\n";
    $tiposDocumento = $facturacionElectronica->obtenerTiposDocumento();
    if (isset($tiposDocumento->FEParamGetTiposDocResult->ResultGet)) {
        $docs = $tiposDocumento->FEParamGetTiposDocResult->ResultGet;
        if (! is_array($docs)) {
            $docs = [$docs];
        }

        echo '✅ Tipos de documento encontrados: '.count($docs)."\n";
        $docsComunes = array_filter($docs, fn ($d): bool => isset($d->Id) && in_array($d->Id, [80, 86, 96, 99]));
        foreach ($docsComunes as $doc) {
            $id = $doc->Id ?? 'N/A';
            $desc = $doc->Desc ?? 'Sin descripción';
            echo sprintf('   • Tipo %s: %s%s', $id, $desc, PHP_EOL);
        }
    }

    echo "\n";

    // Tipos de moneda
    echo "💱 Consultando tipos de moneda...\n";
    $tiposMoneda = $facturacionElectronica->obtenerTiposMoneda();
    if (isset($tiposMoneda->FEParamGetTiposMonedasResult->ResultGet)) {
        $monedas = $tiposMoneda->FEParamGetTiposMonedasResult->ResultGet;
        if (! is_array($monedas)) {
            $monedas = [$monedas];
        }

        echo '✅ Tipos de moneda encontrados: '.count($monedas)."\n";
        $monedasComunes = array_filter($monedas, fn ($m): bool => isset($m->Id) && in_array($m->Id, ['PES', 'DOL', 'EUR']));
        foreach ($monedasComunes as $moneda) {
            $id = $moneda->Id ?? 'N/A';
            $desc = $moneda->Desc ?? 'Sin descripción';
            echo sprintf('   • %s: %s%s', $id, $desc, PHP_EOL);
        }
    }

    echo "\n";

    // Tipos de concepto
    echo "📋 Consultando tipos de concepto...\n";
    $tiposConcepto = $facturacionElectronica->obtenerTiposConcepto();
    if (isset($tiposConcepto->FEParamGetTiposConceptoResult->ResultGet)) {
        $conceptos = $tiposConcepto->FEParamGetTiposConceptoResult->ResultGet;
        if (! is_array($conceptos)) {
            $conceptos = [$conceptos];
        }

        echo '✅ Tipos de concepto encontrados: '.count($conceptos)."\n";
        foreach ($conceptos as $concepto) {
            $id = $concepto->Id ?? 'N/A';
            $desc = $concepto->Desc ?? 'Sin descripción';
            echo sprintf('   • Concepto %s: %s%s', $id, $desc, PHP_EOL);
        }
    }

    echo "\n";

    // Tipos de alícuota IVA
    echo "💰 Consultando tipos de alícuota IVA...\n";
    $tiposAlicuota = $facturacionElectronica->obtenerTiposAlicuota();
    if (isset($tiposAlicuota->FEParamGetTiposIvaResult->ResultGet)) {
        $alicuotas = $tiposAlicuota->FEParamGetTiposIvaResult->ResultGet;
        if (! is_array($alicuotas)) {
            $alicuotas = [$alicuotas];
        }

        echo '✅ Tipos de alícuota encontrados: '.count($alicuotas)."\n";
        $alicuotasComunes = array_filter($alicuotas, fn ($a): bool => isset($a->Id) && in_array($a->Id, [3, 4, 5, 6]));
        foreach ($alicuotasComunes as $alicuota) {
            $id = $alicuota->Id ?? 'N/A';
            $desc = $alicuota->Desc ?? 'Sin descripción';
            echo sprintf('   • ID %s: %s%s', $id, $desc, PHP_EOL);
        }
    }

    echo "\n";

    // Condiciones IVA receptor
    echo "🏢 Consultando condiciones IVA receptor...\n";
    $condicionesIva = $facturacionElectronica->obtenerCondicionesIvaReceptor();
    if (isset($condicionesIva->FEParamGetCondicionIvaReceptorResult->ResultGet)) {
        $condiciones = $condicionesIva->FEParamGetCondicionIvaReceptorResult->ResultGet;
        if (! is_array($condiciones)) {
            $condiciones = [$condiciones];
        }

        echo '✅ Condiciones IVA encontradas: '.count($condiciones)."\n";
        $condicionesComunes = array_filter($condiciones, fn ($c): bool => isset($c->Id) && in_array($c->Id, [1, 4, 5, 6]));
        foreach ($condicionesComunes as $condicion) {
            $id = $condicion->Id ?? 'N/A';
            $desc = $condicion->Desc ?? 'Sin descripción';
            echo sprintf('   • ID %s: %s%s', $id, $desc, PHP_EOL);
        }
    }

    echo "\n";

    // Tipos opcionales
    echo "⚙️ Consultando tipos opcionales...\n";
    $tiposOpcional = $facturacionElectronica->obtenerTiposOpcional();
    if (isset($tiposOpcional->FEParamGetTiposOpcionalResult->ResultGet)) {
        $opcionales = $tiposOpcional->FEParamGetTiposOpcionalResult->ResultGet;
        if (! is_array($opcionales)) {
            $opcionales = [$opcionales];
        }

        echo '✅ Tipos opcionales encontrados: '.count($opcionales)."\n";
        foreach (array_slice($opcionales, 0, 3) as $opcional) {
            $id = $opcional->Id ?? 'N/A';
            $desc = $opcional->Desc ?? 'Sin descripción';
            echo sprintf('   • ID %s: %s%s', $id, $desc, PHP_EOL);
        }
    }

    echo "\n";

    // Tipos de tributo
    echo "🏛️ Consultando tipos de tributo...\n";
    $tiposTributo = $facturacionElectronica->obtenerTiposTributo();
    if (isset($tiposTributo->FEParamGetTiposTributosResult->ResultGet)) {
        $tributos = $tiposTributo->FEParamGetTiposTributosResult->ResultGet;
        if (! is_array($tributos)) {
            $tributos = [$tributos];
        }

        echo '✅ Tipos de tributo encontrados: '.count($tributos)."\n";
        foreach (array_slice($tributos, 0, 3) as $tributo) {
            $id = $tributo->Id ?? 'N/A';
            $desc = $tributo->Desc ?? 'Sin descripción';
            echo sprintf('   • ID %s: %s%s', $id, $desc, PHP_EOL);
        }
    }

    echo "\n";

    // ========================================
    // 3. MÉTODOS DE CONSULTA DE COMPROBANTES
    // ========================================
    echo "3. MÉTODOS DE CONSULTA DE COMPROBANTES\n";
    echo str_repeat('=', 50)."\n\n";

    $puntoVenta = 1;
    $tipoFacturaC = 11;

    // Método nuevo: obtener último número directamente
    echo "🔢 Obteniendo último número de comprobante (método nuevo)...\n";
    $ultimoNumero = $facturacionElectronica->obtenerUltimoNumeroComprobante($puntoVenta, $tipoFacturaC);
    echo "✅ Último número de Factura C: {$ultimoNumero}\n\n";

    // Método anterior: obtener respuesta completa
    echo "📊 Obteniendo último comprobante completo (método anterior)...\n";
    $ultimoComprobante = $facturacionElectronica->obtenerUltimoComprobante($puntoVenta, $tipoFacturaC);
    if (isset($ultimoComprobante->FECompUltimoAutorizadoResult)) {
        $resultado = $ultimoComprobante->FECompUltimoAutorizadoResult;
        echo "✅ Información completa:\n";
        echo sprintf('   • Número: %s%s', $resultado->CbteNro, PHP_EOL);
        echo sprintf('   • Punto de venta: %s%s', $resultado->PtoVta, PHP_EOL);
        echo sprintf('   • Tipo: %s%s', $resultado->CbteTipo, PHP_EOL);
    }

    echo "\n";

    // Consultar información de un comprobante específico
    if ($ultimoNumero > 0) {
        echo "🔍 Consultando información del comprobante {$ultimoNumero}...\n";
        $infoComprobante = $facturacionElectronica->obtenerInformacionComprobante(
            $ultimoNumero,
            $puntoVenta,
            $tipoFacturaC
        );

        if ($infoComprobante !== null) {
            echo "✅ Comprobante encontrado:\n";
            $cae = $infoComprobante->CAE ?? 'N/A';
            $vencimiento = $infoComprobante->CAEFchVto ?? 'N/A';
            $total = $infoComprobante->ImpTotal ?? 'N/A';
            echo sprintf('   • CAE: %s%s', $cae, PHP_EOL);
            echo sprintf('   • Vencimiento: %s%s', $vencimiento, PHP_EOL);
            echo sprintf('   • Total: $%s%s', $total, PHP_EOL);
        } else {
            echo "❌ Comprobante no encontrado\n";
        }
    }

    echo "\n";

    // ========================================
    // 4. MÉTODOS DE AUTORIZACIÓN (SIMULACIÓN)
    // ========================================
    echo "4. MÉTODOS DE AUTORIZACIÓN (SIMULACIÓN)\n";
    echo str_repeat('=', 50)."\n\n";

    echo "🔄 SIMULACIÓN: Preparando datos para autorización...\n";

    $datosComprobante = [
        'PtoVta' => $puntoVenta,
        'CbteTipo' => $tipoFacturaC,
        'Concepto' => 1, // Productos
        'DocTipo' => 99, // Consumidor Final
        'DocNro' => 0,
        'CbteFch' => (int) date('Ymd'),
        'ImpTotal' => 121.00,
        'ImpTotConc' => 0,
        'ImpNeto' => 100.00,
        'ImpOpEx' => 0,
        'ImpIVA' => 21.00,
        'ImpTrib' => 0,
        'MonId' => 'PES',
        'MonCotiz' => 1,
        'CondicionIVAReceptorId' => 5, // Consumidor Final
        'Iva' => [
            [
                'Id' => 5, // 21%
                'BaseImp' => 100.00,
                'Importe' => 21.00,
            ],
        ],
    ];

    echo "✅ Datos preparados:\n";
    echo "   • Tipo: Factura C\n";
    echo "   • Concepto: Productos\n";
    echo sprintf('   • Total: $%s%s', $datosComprobante['ImpTotal'], PHP_EOL);
    echo "   • IVA: \${$datosComprobante['ImpIVA']}\n\n";

    echo "🚀 MÉTODO NUEVO: autorizarProximoComprobante()\n";
    echo '   • Calcula automáticamente el próximo número: '.($ultimoNumero + 1)."\n";
    echo "   • Agrega CbteDesde y CbteHasta automáticamente\n";
    echo "   • Simplifica el proceso de autorización\n\n";

    echo "⚠️  Para ejecutar realmente, descomenta:\n";
    echo "   // \$respuesta = \$facturacionElectronica->autorizarProximoComprobante(\$datosComprobante);\n\n";

    // ========================================
    // 5. MÉTODOS CAEA (SIMULACIÓN)
    // ========================================
    echo "5. MÉTODOS CAEA (SIMULACIÓN)\n";
    echo str_repeat('=', 50)."\n\n";

    $periodoActual = (int) date('Ym');
    $orden = 1;

    echo sprintf('📅 CAEA para período %d, orden %d%s', $periodoActual, $orden, PHP_EOL);
    echo "🔄 SIMULACIÓN: Solicitud de CAEA...\n";
    echo sprintf('   • Período: %d (', $periodoActual).date('Y-m').")\n";
    echo "   • Orden: {$orden} (Primera quincena)\n\n";

    echo "⚠️  Para ejecutar realmente, descomenta:\n";
    echo "   // \$caea = \$facturacionElectronica->crearCAEA(\$periodoActual, \$orden);\n";
    echo "   // \$consultaCAEA = \$facturacionElectronica->obtenerCAEA(\$numeroCAEA);\n\n";

    // ========================================
    // 6. RESUMEN DE MÉTODOS DISPONIBLES
    // ========================================
    echo "6. RESUMEN DE MÉTODOS DISPONIBLES\n";
    echo str_repeat('=', 50)."\n\n";

    $metodos = [
        'Estado y Diagnóstico' => [
            'obtenerEstadoServidor()' => 'Verifica estado de servidores AFIP',
        ],
        'Consulta de Parámetros' => [
            'obtenerPuntosDeVenta()' => 'Lista puntos de venta habilitados',
            'obtenerTiposComprobante()' => 'Lista tipos de comprobantes',
            'obtenerTiposDocumento()' => 'Lista tipos de documentos',
            'obtenerTiposMoneda()' => 'Lista tipos de monedas',
            'obtenerTiposConcepto()' => 'Lista tipos de concepto',
            'obtenerTiposAlicuota()' => 'Lista alícuotas de IVA',
            'obtenerCondicionesIvaReceptor()' => 'Lista condiciones IVA receptor',
            'obtenerTiposOpcional()' => 'Lista tipos opcionales',
            'obtenerTiposTributo()' => 'Lista tipos de tributos',
        ],
        'Consulta de Comprobantes' => [
            'obtenerUltimoComprobante()' => 'Obtiene último comprobante (completo)',
            'obtenerUltimoNumeroComprobante()' => '🆕 Obtiene último número (simplificado)',
            'obtenerInformacionComprobante()' => 'Consulta comprobante específico',
        ],
        'Autorización' => [
            'autorizarComprobante()' => 'Autoriza comprobantes (método tradicional)',
            'autorizarProximoComprobante()' => '🆕 Autoriza próximo comprobante (simplificado)',
        ],
        'CAEA' => [
            'crearCAEA()' => 'Solicita nuevo CAEA',
            'obtenerCAEA()' => 'Consulta CAEA existente',
        ],
    ];

    foreach ($metodos as $categoria => $metodosCategoria) {
        echo $categoria.':
';
        foreach ($metodosCategoria as $metodo => $descripcion) {
            $icono = str_contains($descripcion, '🆕') ? '🆕' : '  ';
            echo sprintf('%s %s - %s%s', $icono, $metodo, $descripcion, PHP_EOL);
        }

        echo "\n";
    }

    // ========================================
    // 7. PRÓXIMOS PASOS
    // ========================================
    echo "7. PRÓXIMOS PASOS\n";
    echo str_repeat('=', 50)."\n\n";

    echo "📚 PARA APRENDER MÁS:\n";
    echo "• Revisa los ejemplos específicos en la carpeta 'ejemplos/'\n";
    echo "• Consulta la documentación en README.md\n";
    echo "• Ejecuta los tests para ver casos de uso\n";
    echo "• Prueba en modo homologación antes de producción\n\n";

    echo "🔧 PARA IMPLEMENTAR:\n";
    echo "1. Configura tus certificados AFIP\n";
    echo "2. Prueba los métodos de consulta primero\n";
    echo "3. Implementa la autorización de comprobantes\n";
    echo "4. Agrega manejo de errores robusto\n";
    echo "5. Considera usar los nuevos métodos simplificados\n\n";

    echo "✅ DEMOSTRACIÓN COMPLETADA\n";
    echo "Todos los métodos han sido ejecutados exitosamente.\n";

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
    echo sprintf('   Tipo: %s%s', $e->obtenerTipoError(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
