<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

/**
 * Ejemplo de uso de los nuevos métodos agregados en v1.1.0
 *
 * - obtenerUltimoNumeroComprobante(): Obtiene directamente el número como entero
 * - autorizarProximoComprobante(): Calcula automáticamente el próximo número
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

    echo "=== DEMOSTRACIÓN DE NUEVOS MÉTODOS v1.1.0 ===\n\n";
    echo 'Versión del SDK: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo: '.($afip->esModoProduccion() ? 'Producción' : 'Homologación')."\n\n";

    $facturacionElectronica = $afip->FacturacionElectronica;

    // ========================================
    // 1. MÉTODO NUEVO: obtenerUltimoNumeroComprobante()
    // ========================================
    echo "1. MÉTODO NUEVO: obtenerUltimoNumeroComprobante()\n";
    echo "   Obtiene directamente el número como entero (más simple)\n\n";

    $puntoVenta = 1;
    $tipoFacturaA = 1;  // Factura A
    $tipoFacturaB = 6;  // Factura B
    $tipoFacturaC = 11; // Factura C

    // Obtener últimos números de diferentes tipos de comprobantes
    $ultimoNumeroA = $facturacionElectronica->obtenerUltimoNumeroComprobante($puntoVenta, $tipoFacturaA);
    $ultimoNumeroB = $facturacionElectronica->obtenerUltimoNumeroComprobante($puntoVenta, $tipoFacturaB);
    $ultimoNumeroC = $facturacionElectronica->obtenerUltimoNumeroComprobante($puntoVenta, $tipoFacturaC);

    echo sprintf('   • Último número Factura A (tipo 1): %d%s', $ultimoNumeroA, PHP_EOL);
    echo sprintf('   • Último número Factura B (tipo 6): %d%s', $ultimoNumeroB, PHP_EOL);
    echo "   • Último número Factura C (tipo 11): {$ultimoNumeroC}\n\n";

    // ========================================
    // 2. COMPARACIÓN: Método anterior vs nuevo
    // ========================================
    echo "2. COMPARACIÓN: Método anterior vs nuevo\n\n";

    // Método anterior (aún funciona)
    echo "   Método anterior (obtenerUltimoComprobante):\n";
    $respuestaCompleta = $facturacionElectronica->obtenerUltimoComprobante($puntoVenta, $tipoFacturaB);
    $numeroAnterior = $respuestaCompleta->FECompUltimoAutorizadoResult->CbteNro;
    echo "   • Requiere acceso a propiedades: \$respuesta->FECompUltimoAutorizadoResult->CbteNro\n";
    echo "   • Resultado: {$numeroAnterior}\n\n";

    // Método nuevo
    echo "   Método nuevo (obtenerUltimoNumeroComprobante):\n";
    $numeroNuevo = $facturacionElectronica->obtenerUltimoNumeroComprobante($puntoVenta, $tipoFacturaB);
    echo "   • Devuelve directamente el número como entero\n";
    echo sprintf('   • Resultado: %d%s', $numeroNuevo, PHP_EOL);
    echo '   • ✅ Ambos métodos devuelven el mismo resultado: '.($numeroAnterior === $numeroNuevo ? 'SÍ' : 'NO')."\n\n";

    // ========================================
    // 3. MÉTODO NUEVO: autorizarProximoComprobante()
    // ========================================
    echo "3. MÉTODO NUEVO: autorizarProximoComprobante()\n";
    echo "   Calcula automáticamente el próximo número y autoriza\n\n";

    // Datos de ejemplo para una Factura B
    $datosFactura = [
        'PtoVta' => $puntoVenta,
        'CbteTipo' => $tipoFacturaB, // Factura B
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

    echo "   Datos del comprobante preparados (sin CbteDesde/CbteHasta)\n";
    echo sprintf('   • Punto de venta: %d%s', $datosFactura['PtoVta'], PHP_EOL);
    echo "   • Tipo: {$datosFactura['CbteTipo']} (Factura B)\n";
    echo "   • Importe total: \${$datosFactura['ImpTotal']}\n\n";

    // SIMULACIÓN: En un entorno real, descomenta la siguiente línea
    // $respuesta = $facturacionElectronica->autorizarProximoComprobante($datosFactura);

    echo "   🔄 SIMULACIÓN DE AUTORIZACIÓN:\n";
    echo sprintf('   • El método obtendría automáticamente el último número: %d%s', $ultimoNumeroB, PHP_EOL);
    echo '   • Calcularía el próximo número: '.($ultimoNumeroB + 1)."\n";
    echo "   • Agregaría CbteDesde y CbteHasta automáticamente\n";
    echo "   • Enviaría la solicitud a AFIP\n\n";

    echo "   ⚠️  Para ejecutar realmente, descomenta la línea de autorización\n\n";

    // ========================================
    // 4. VENTAJAS DE LOS NUEVOS MÉTODOS
    // ========================================
    echo "4. VENTAJAS DE LOS NUEVOS MÉTODOS\n\n";

    echo "   obtenerUltimoNumeroComprobante():\n";
    echo "   ✅ Más simple: devuelve directamente un entero\n";
    echo "   ✅ Menos propenso a errores de acceso a propiedades\n";
    echo "   ✅ Validación automática de estructura de respuesta\n";
    echo "   ✅ Manejo robusto de errores\n\n";

    echo "   autorizarProximoComprobante():\n";
    echo "   ✅ Elimina cálculos manuales de números de comprobante\n";
    echo "   ✅ Previene errores de numeración\n";
    echo "   ✅ Código más limpio y legible\n";
    echo "   ✅ Validación de tipos de entrada\n";
    echo "   ✅ Soporte para valores por defecto\n\n";

    // ========================================
    // 5. COMPATIBILIDAD
    // ========================================
    echo "5. COMPATIBILIDAD\n\n";
    echo "   • Los métodos anteriores siguen funcionando normalmente\n";
    echo "   • Puedes migrar gradualmente a los nuevos métodos\n";
    echo "   • No hay cambios breaking en la API existente\n";
    echo "   • Versión: 1.0.0 → 1.1.0 (MINOR update según SemVer)\n\n";

    echo "=== FIN DE LA DEMOSTRACIÓN ===\n";

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
    echo sprintf('   Tipo: %s%s', $e->obtenerTipoError(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
