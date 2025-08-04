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
     * CONSULTAR CAEA SIN MOVIMIENTO
     *
     * Este método permite consultar si un CAEA (Código de Autorización
     * Electrónico Anticipado) fue previamente informado como "sin movimiento"
     * para un punto de venta específico.
     *
     * Útil para:
     * - Verificar si ya se informó un CAEA como sin movimiento
     * - Auditar el estado de CAEAs no utilizados
     * - Evitar informar duplicadamente el mismo CAEA
     */
    $puntoVenta = 1;
    $caea = 21234567890123; // CAEA a consultar

    echo "=== CONSULTAR CAEA SIN MOVIMIENTO ===\n";
    echo sprintf('Punto de Venta: %d%s', $puntoVenta, PHP_EOL);
    echo "CAEA: {$caea}\n\n";

    // Consultar si el CAEA fue informado como sin movimiento
    echo "Consultando estado de CAEA sin movimiento...\n";
    $respuesta = $facturacionElectronica->consultarCAEASinMovimiento($puntoVenta, $caea);

    echo "Respuesta de AFIP:\n";
    print_r($respuesta);

    // Verificar el resultado
    if (isset($respuesta->FECAEASinMovimientoConsultarResult)) {
        $resultado = $respuesta->FECAEASinMovimientoConsultarResult;

        if (isset($resultado->Resultado) && $resultado->Resultado === 'A') {
            echo "\n✅ Consulta exitosa\n";
            echo sprintf('CAEA: %s%s', $resultado->CAEA, PHP_EOL);
            echo sprintf('Punto de Venta: %s%s', $resultado->PtoVta, PHP_EOL);

            if (isset($resultado->FchInformado) && ! empty($resultado->FchInformado)) {
                echo "✅ CAEA fue informado como sin movimiento\n";
                echo sprintf('Fecha informado: %s%s', $resultado->FchInformado, PHP_EOL);
            } else {
                echo "ℹ️  CAEA NO fue informado como sin movimiento\n";
            }
        } else {
            echo "\n❌ Error en la consulta\n";
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

    echo "\n=== EJEMPLO DE USO PRÁCTICO ===\n";

    // Función helper para verificar estado de CAEA
    function verificarEstadoCAEA($facturacionElectronica, $puntoVenta, $caea): array
    {
        try {
            $respuesta = $facturacionElectronica->consultarCAEASinMovimiento($puntoVenta, $caea);

            if (isset($respuesta->FECAEASinMovimientoConsultarResult)) {
                $resultado = $respuesta->FECAEASinMovimientoConsultarResult;

                // Verificar si hay errores
                if (isset($resultado->Errors)) {
                    return ['exitoso' => false, 'error' => 'CAEA no encontrado o error en consulta'];
                }

                return [
                    'exitoso' => isset($resultado->Resultado) && $resultado->Resultado === 'A',
                    'informado' => isset($resultado->FchInformado) && ! empty($resultado->FchInformado),
                    'fecha_informado' => $resultado->FchInformado ?? null,
                    'caea' => $resultado->CAEA ?? null,
                    'punto_venta' => $resultado->PtoVta ?? null,
                ];
            }

            return ['exitoso' => false, 'error' => 'Respuesta inválida'];
        } catch (Exception $exception) {
            return ['exitoso' => false, 'error' => $exception->getMessage()];
        }
    }

    // Ejemplo de uso de la función helper
    $estado = verificarEstadoCAEA($facturacionElectronica, $puntoVenta, $caea);

    if ($estado['exitoso']) {
        if ($estado['informado']) {
            echo sprintf('✅ El CAEA %d YA fue informado como sin movimiento el %s%s', $caea, $estado['fecha_informado'], PHP_EOL);
        } else {
            echo "ℹ️  El CAEA {$caea} NO ha sido informado como sin movimiento\n";
            echo "   Puede informarlo usando informarCAEASinMovimiento() si corresponde\n";
        }
    } else {
        echo sprintf('❌ Error al consultar: %s%s', $estado['error'], PHP_EOL);
    }

    echo "\n=== INFORMACIÓN ADICIONAL ===\n";
    echo "• Use este método antes de informar un CAEA como sin movimiento\n";
    echo "• Evita errores por informar duplicadamente el mismo CAEA\n";
    echo "• Útil para auditorías y control de CAEAs no utilizados\n";
    echo "• La fecha informado se muestra en formato AAAAMMDD\n";

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
