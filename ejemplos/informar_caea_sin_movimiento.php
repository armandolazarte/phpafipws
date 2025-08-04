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
     * INFORMAR CAEA SIN MOVIMIENTO
     *
     * Este método se utiliza para informar a AFIP que un CAEA (Código de Autorización
     * Electrónico Anticipado) no ha tenido movimiento en un punto de venta específico.
     *
     * Es obligatorio informar cuando:
     * - Se solicitó un CAEA pero no se emitieron comprobantes con él
     * - El punto de venta no tuvo actividad durante el período del CAEA
     * - Se quiere evitar observaciones por CAEAs no utilizados
     */
    $puntoVenta = 1;
    $caea = 21234567890123; // CAEA previamente solicitado

    echo "=== INFORMAR CAEA SIN MOVIMIENTO ===\n";
    echo sprintf('Punto de Venta: %d%s', $puntoVenta, PHP_EOL);
    echo "CAEA: {$caea}\n\n";

    // Informar que el CAEA no tuvo movimiento
    echo "Informando CAEA sin movimiento...\n";
    $respuesta = $facturacionElectronica->informarCAEASinMovimiento($puntoVenta, $caea);

    echo "Respuesta de AFIP:\n";
    print_r($respuesta);

    // Verificar el resultado
    if (isset($respuesta->FECAEASinMovimientoInformarResult)) {
        $resultado = $respuesta->FECAEASinMovimientoInformarResult;

        if (isset($resultado->Resultado) && $resultado->Resultado === 'A') {
            echo "\n✅ CAEA sin movimiento informado correctamente\n";
            echo sprintf('CAEA: %s%s', $resultado->CAEA, PHP_EOL);
            echo sprintf('Punto de Venta: %s%s', $resultado->PtoVta, PHP_EOL);
            echo sprintf('Fecha de Proceso: %s%s', $resultado->FchProceso, PHP_EOL);
        } else {
            echo "\n❌ Error al informar CAEA sin movimiento\n";
            if (isset($resultado->Observaciones)) {
                echo "Observaciones:\n";
                print_r($resultado->Observaciones);
            }

            if (isset($resultado->Errores)) {
                echo "Errores:\n";
                print_r($resultado->Errores);
            }
        }
    }

    echo "\n=== INFORMACIÓN ADICIONAL ===\n";
    echo "• Este método debe usarse cuando un CAEA no fue utilizado\n";
    echo "• Es obligatorio para evitar observaciones de AFIP\n";
    echo "• Debe informarse antes del vencimiento del CAEA\n";
    echo "• Solo se puede informar una vez por CAEA y punto de venta\n";

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
