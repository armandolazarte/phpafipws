<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Ejemplo: Gestión de CAEA (Código de Autorización Electrónico Anticipado)
 *
 * Este ejemplo muestra cómo solicitar y consultar CAEA para facturación
 * de gran volumen de comprobantes de forma diferida.
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

    echo "=== GESTIÓN DE CAEA (Código de Autorización Electrónico Anticipado) ===\n\n";
    echo 'Versión del SDK: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo: '.($afip->esModoProduccion() ? 'Producción' : 'Homologación')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    echo "=== ¿QUÉ ES UN CAEA? ===\n\n";
    echo "📋 DEFINICIÓN:\n";
    echo "• CAEA = Código de Autorización Electrónico Anticipado\n";
    echo "• Permite autorizar comprobantes de forma diferida\n";
    echo "• Útil para grandes volúmenes de facturación\n";
    echo "• Se solicita por períodos quincenales\n";
    echo "• Cada período tiene 2 órdenes (1ra y 2da quincena)\n\n";

    echo "=== SOLICITAR NUEVO CAEA ===\n\n";

    // Calcular período actual (formato AAAAMM)
    $periodoActual = (int) date('Ym');
    $orden = 1; // 1 = Primera quincena, 2 = Segunda quincena

    echo "Solicitando CAEA para:\n";
    echo "• Período: {$periodoActual} (".date('Y-m').")\n";
    echo "• Orden: {$orden} (Primera quincena)\n\n";

    // SIMULACIÓN: En un entorno real, descomenta las siguientes líneas
    /*
    echo "Enviando solicitud a AFIP...\n";
    $respuestaCAEA = $facturacionElectronica->crearCAEA($periodoActual, $orden);

    echo "✅ CAEA solicitado exitosamente\n\n";

    if (isset($respuestaCAEA->FECAEASolicitarResult->ResultGet)) {
        $caea = $respuestaCAEA->FECAEASolicitarResult->ResultGet;

        echo "INFORMACIÓN DEL CAEA:\n";
        echo "• CAEA: {$caea->CAEA}\n";
        echo "• Período: {$caea->Periodo}\n";
        echo "• Orden: {$caea->Orden}\n";
        echo "• Vigente desde: {$caea->FchVigDesde}\n";
        echo "• Vigente hasta: {$caea->FchVigHasta}\n";

        $numeroCAEA = $caea->CAEA;
    } else {
        echo "❌ Error al solicitar CAEA\n";
        print_r($respuestaCAEA);
        $numeroCAEA = null;
    }
    */

    // Para la demostración, usamos un CAEA simulado
    $numeroCAEA = 21234567890123;
    echo "🔄 SIMULACIÓN: Usando CAEA de ejemplo: {$numeroCAEA}\n\n";

    echo "=== CONSULTAR CAEA EXISTENTE ===\n\n";

    echo "Consultando información del CAEA {$numeroCAEA}...\n\n";

    // SIMULACIÓN: En un entorno real, descomenta las siguientes líneas
    /*
    $consultaCAEA = $facturacionElectronica->obtenerCAEA($numeroCAEA);

    if (isset($consultaCAEA->FECAEAConsultarResult->ResultGet)) {
        $infoCAEA = $consultaCAEA->FECAEAConsultarResult->ResultGet;

        echo "✅ CAEA encontrado:\n\n";
        echo "INFORMACIÓN DETALLADA:\n";
        echo "• CAEA: {$infoCAEA->CAEA}\n";
        echo "• Período: {$infoCAEA->Periodo}\n";
        echo "• Orden: {$infoCAEA->Orden}\n";
        echo "• Vigente desde: {$infoCAEA->FchVigDesde}\n";
        echo "• Vigente hasta: {$infoCAEA->FchVigHasta}\n";
        echo "• Fecha tope informar: {$infoCAEA->FchTopeInf}\n";
        echo "• Fecha proceso: {$infoCAEA->FchProceso}\n";

        // Verificar estado
        $hoy = date('Ymd');
        $vigente = ($hoy >= $infoCAEA->FchVigDesde && $hoy <= $infoCAEA->FchVigHasta);
        echo "• Estado: " . ($vigente ? "✅ VIGENTE" : "❌ NO VIGENTE") . "\n";

    } else {
        echo "❌ CAEA no encontrado o error en la consulta\n";
        print_r($consultaCAEA);
    }
    */

    // Para la demostración, simulamos la respuesta
    echo "🔄 SIMULACIÓN: Información del CAEA:\n\n";
    echo "INFORMACIÓN DETALLADA:\n";
    echo "• CAEA: {$numeroCAEA}\n";
    echo "• Período: {$periodoActual}\n";
    echo "• Orden: {$orden}\n";
    $primerDia = strtotime('first day of this month');
    $dia15 = strtotime(date('Y-m-15'));
    $dia16 = strtotime(date('Y-m-16'));

    echo '• Vigente desde: '.($primerDia ? date('Ymd', $primerDia) : date('Ym01'))."\n";
    echo '• Vigente hasta: '.($dia15 ? date('Ymd', $dia15) : date('Ym15'))."\n";
    echo '• Fecha tope informar: '.($dia16 ? date('Ymd', $dia16) : date('Ym16'))."\n";
    echo "• Estado: ✅ VIGENTE (simulado)\n\n";

    echo "=== USO DEL CAEA EN FACTURACIÓN ===\n\n";

    echo "📝 PROCESO CON CAEA:\n";
    echo "1. Solicitar CAEA para el período\n";
    echo "2. Emitir comprobantes usando el CAEA\n";
    echo "3. Informar los comprobantes a AFIP dentro del plazo\n";
    echo "4. AFIP valida y asigna CAE definitivos\n\n";

    echo "<?php\n";
    echo "// Ejemplo de uso del CAEA en un comprobante\n";
    echo "\$datosComprobante = [\n";
    echo "    'PtoVta' => 1,\n";
    echo "    'CbteTipo' => 11,\n";
    echo "    'Concepto' => 1,\n";
    echo "    // ... otros campos normales\n";
    echo "    \n";
    echo "    // Campos específicos para CAEA\n";
    echo "    'CAEA' => '{$numeroCAEA}',\n";
    echo "    'CbteFch' => (int) date('Ymd'),\n";
    echo "];\n\n";

    echo "// Autorizar usando CAEA (método diferente al CAE normal)\n";
    echo "// \$respuesta = \$facturacionElectronica->autorizarComprobanteCAEA(\$datosComprobante);\n\n";

    echo "=== VENTAJAS Y DESVENTAJAS ===\n\n";

    echo "✅ VENTAJAS DEL CAEA:\n";
    echo "• Permite facturación offline\n";
    echo "• Ideal para grandes volúmenes\n";
    echo "• Reduce la dependencia de conectividad\n";
    echo "• Agiliza el proceso de facturación masiva\n\n";

    echo "❌ DESVENTAJAS DEL CAEA:\n";
    echo "• Proceso más complejo\n";
    echo "• Requiere informar comprobantes posteriormente\n";
    echo "• Plazos estrictos para informar\n";
    echo "• Mayor responsabilidad en el control\n\n";

    echo "=== PERÍODOS Y ÓRDENES ===\n\n";

    echo "📅 ESTRUCTURA DE PERÍODOS:\n";
    echo "• Formato: AAAAMM (ejemplo: 202402 = Febrero 2024)\n";
    echo "• Orden 1: Primera quincena (días 1-15)\n";
    echo "• Orden 2: Segunda quincena (días 16-fin de mes)\n\n";

    echo "EJEMPLOS DE PERÍODOS:\n";
    $ejemplosPeriodos = [
        ['periodo' => 202401, 'orden' => 1, 'descripcion' => 'Enero 2024 - Primera quincena'],
        ['periodo' => 202401, 'orden' => 2, 'descripcion' => 'Enero 2024 - Segunda quincena'],
        ['periodo' => 202402, 'orden' => 1, 'descripcion' => 'Febrero 2024 - Primera quincena'],
        ['periodo' => 202402, 'orden' => 2, 'descripcion' => 'Febrero 2024 - Segunda quincena'],
    ];

    foreach ($ejemplosPeriodos as $ejemplo) {
        echo sprintf("• Período %d, Orden %d: %s\n",
            $ejemplo['periodo'],
            $ejemplo['orden'],
            $ejemplo['descripcion']
        );
    }

    echo "\n=== FUNCIÓN HELPER PARA PERÍODOS ===\n\n";

    echo "<?php\n";
    echo "function calcularPeriodoCAEA(\$fecha = null) {\n";
    echo "    \$fecha = \$fecha ?: date('Y-m-d');\n";
    echo "    \$timestamp = strtotime(\$fecha);\n";
    echo "    \n";
    echo "    \$periodo = (int) date('Ym', \$timestamp);\n";
    echo "    \$dia = (int) date('d', \$timestamp);\n";
    echo "    \$orden = (\$dia <= 15) ? 1 : 2;\n";
    echo "    \n";
    echo "    return ['periodo' => \$periodo, 'orden' => \$orden];\n";
    echo "}\n\n";

    echo "// Uso:\n";
    echo "\$info = calcularPeriodoCAEA(); // Período actual\n";
    echo "// \$info = ['periodo' => {$periodoActual}, 'orden' => {$orden}]\n\n";

    echo "=== CONSEJOS DE USO ===\n\n";
    echo "💡 CONSEJOS:\n";
    echo "• Solicita CAEA solo si manejas grandes volúmenes\n";
    echo "• Para pocos comprobantes, usa CAE normal\n";
    echo "• Planifica con anticipación los períodos\n";
    echo "• Mantén control estricto de los comprobantes emitidos\n";
    echo "• Respeta los plazos para informar a AFIP\n";
    echo "• Implementa validaciones de fechas de vigencia\n";
    echo "• Considera tener un sistema de respaldo\n\n";

    echo "⚠️  IMPORTANTE:\n";
    echo "• El CAEA tiene fechas de vigencia estrictas\n";
    echo "• Debes informar los comprobantes dentro del plazo\n";
    echo "• No informar a tiempo puede generar multas\n";
    echo "• Consulta con tu contador antes de implementar\n\n";

    echo "=== PRÓXIMOS PASOS ===\n\n";
    echo "Para implementar CAEA en tu sistema:\n";
    echo "1. Evalúa si realmente necesitas CAEA\n";
    echo "2. Implementa la lógica de períodos y órdenes\n";
    echo "3. Crea un sistema de control de comprobantes\n";
    echo "4. Implementa la informacion posterior a AFIP\n";
    echo "5. Prueba exhaustivamente en homologación\n\n";

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
    echo sprintf('   Tipo: %s%s', $e->obtenerTipoError(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
