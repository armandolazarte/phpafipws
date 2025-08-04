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
     * OBTENER ACTIVIDADES ECONÓMICAS
     *
     * Este método permite consultar las actividades económicas vigentes
     * del emisor del comprobante (el CUIT configurado en el SDK).
     *
     * Es útil para:
     * - Conocer qué actividades están habilitadas para facturar
     * - Validar que se puede emitir comprobantes para cierta actividad
     * - Obtener información para completar campos específicos en comprobantes
     * - Auditar las actividades registradas en AFIP
     */
    echo "=== OBTENER ACTIVIDADES ECONÓMICAS ===\n\n";

    echo "Consultando actividades económicas vigentes...\n";
    $respuesta = $facturacionElectronica->obtenerActividades();

    echo "Respuesta de AFIP:\n";
    print_r($respuesta);

    // Procesar la respuesta
    if (isset($respuesta->FEParamGetActividadesResult->ResultGet->ActividadesTipo)) {
        $actividades = $respuesta->FEParamGetActividadesResult->ResultGet->ActividadesTipo;

        // Asegurar que sea un array
        $actividadesArray = is_array($actividades) ? $actividades : [$actividades];

        echo "\n✅ Actividades económicas encontradas: ".count($actividadesArray)."\n\n";

        foreach ($actividadesArray as $index => $actividad) {
            echo 'Actividad '.($index + 1).":\n";
            echo sprintf('  - Código: %s%s', $actividad->Id, PHP_EOL);
            echo sprintf('  - Descripción: %s%s', $actividad->Desc, PHP_EOL);
            echo sprintf('  - Orden: %s%s', $actividad->Orden, PHP_EOL);

            // Información adicional si está disponible
            if (isset($actividad->FchDesde)) {
                echo sprintf('  - Vigente desde: %s%s', $actividad->FchDesde, PHP_EOL);
            }

            if (isset($actividad->FchHasta)) {
                echo sprintf('  - Vigente hasta: %s%s', $actividad->FchHasta, PHP_EOL);
            }

            echo "\n";
        }

        // Estadísticas
        echo "=== ESTADÍSTICAS ===\n";
        echo 'Total de actividades: '.count($actividadesArray)."\n";

        // Agrupar por sector (primeros 2 dígitos del código)
        $sectores = [];
        foreach ($actividadesArray as $actividad) {
            $sector = mb_substr((string) $actividad->Id, 0, 2);
            if (! isset($sectores[$sector])) {
                $sectores[$sector] = 0;
            }

            $sectores[$sector]++;
        }

        echo 'Sectores representados: '.count($sectores)."\n";
        foreach ($sectores as $sector => $cantidad) {
            echo "  - Sector {$sector}: {$cantidad} actividad(es)\n";
        }

    } else {
        echo "❌ No se pudieron obtener las actividades económicas\n";

        // Verificar si hay errores en la respuesta
        if (isset($respuesta->Errors)) {
            echo "Errores:\n";
            print_r($respuesta->Errors);
        }
    }

    echo "\n=== FUNCIÓN HELPER PARA ACTIVIDADES ===\n";

    // Función helper para obtener actividades con manejo de errores
    function obtenerActividadesSeguras($facturacionElectronica): ?array
    {
        try {
            $respuesta = $facturacionElectronica->obtenerActividades();

            if (isset($respuesta->FEParamGetActividadesResult->ResultGet->ActividadesTipo)) {
                $actividades = $respuesta->FEParamGetActividadesResult->ResultGet->ActividadesTipo;
                $actividadesArray = is_array($actividades) ? $actividades : [$actividades];

                $resultado = [];
                foreach ($actividadesArray as $actividad) {
                    $resultado[] = [
                        'codigo' => $actividad->Id,
                        'descripcion' => $actividad->Desc,
                        'orden' => $actividad->Orden,
                        'fecha_desde' => $actividad->FchDesde ?? null,
                        'fecha_hasta' => $actividad->FchHasta ?? null,
                    ];
                }

                return $resultado;
            }

            return null;
        } catch (Exception $exception) {
            echo sprintf('Error al obtener actividades: %s%s', $exception->getMessage(), PHP_EOL);

            return null;
        }
    }

    // Función para buscar una actividad específica
    function buscarActividad(array $actividades, int $codigo): ?array
    {
        foreach ($actividades as $actividad) {
            if ($actividad['codigo'] === $codigo) {
                return $actividad;
            }
        }

        return null;
    }

    // Función para obtener actividades por sector
    function obtenerActividadesPorSector(array $actividades, string $sector): array
    {
        $resultado = [];
        foreach ($actividades as $actividad) {
            if (str_starts_with((string) $actividad['codigo'], $sector)) {
                $resultado[] = $actividad;
            }
        }

        return $resultado;
    }

    // Ejemplos de uso de las funciones helper
    echo "Ejemplos de uso de funciones helper:\n\n";

    $actividades = obtenerActividadesSeguras($facturacionElectronica);
    if ($actividades !== null && $actividades !== []) {
        echo '✅ Se obtuvieron '.count($actividades)." actividades\n\n";

        // Buscar una actividad específica (ejemplo: programación informática)
        $actividadBuscada = buscarActividad($actividades, 620100);
        if ($actividadBuscada !== null && $actividadBuscada !== []) {
            echo "✅ Actividad encontrada:\n";
            echo sprintf('   Código: %s%s', $actividadBuscada['codigo'], PHP_EOL);
            echo sprintf('   Descripción: %s%s', $actividadBuscada['descripcion'], PHP_EOL);
        } else {
            echo "ℹ️  Actividad 620100 no encontrada\n";
        }

        // Obtener actividades del sector 62 (Informática)
        $actividadesInformatica = obtenerActividadesPorSector($actividades, '62');
        if ($actividadesInformatica !== []) {
            echo "\n✅ Actividades del sector informática (62): ".count($actividadesInformatica)."\n";
            foreach ($actividadesInformatica as $actividad) {
                echo sprintf('   - %s: %s%s', $actividad['codigo'], $actividad['descripcion'], PHP_EOL);
            }
        }

        // Mostrar las primeras 5 actividades
        echo "\n✅ Primeras 5 actividades:\n";
        $primeras5 = array_slice($actividades, 0, 5);
        foreach ($primeras5 as $actividad) {
            echo sprintf('   - %s: %s%s', $actividad['codigo'], $actividad['descripcion'], PHP_EOL);
        }
    }

    echo "\n=== CÓDIGOS DE ACTIVIDAD COMUNES ===\n";

    // Algunos códigos de actividad comunes para referencia
    $actividadesComunes = [
        '620100' => 'Programación informática',
        '620200' => 'Consultoría informática',
        '471110' => 'Venta al por menor en hipermercados',
        '471120' => 'Venta al por menor en supermercados',
        '522010' => 'Servicios de comidas y bebidas en restaurantes',
        '691010' => 'Servicios jurídicos',
        '692010' => 'Servicios de contabilidad',
        '702010' => 'Servicios de consultoría en gestión empresarial',
        '711010' => 'Servicios de arquitectura',
        '712010' => 'Servicios de ingeniería',
    ];

    echo "Actividades económicas frecuentes:\n";
    foreach ($actividadesComunes as $codigo => $descripcion) {
        echo sprintf('  - %s: %s%s', $codigo, $descripcion, PHP_EOL);
    }

    echo "\n=== EJEMPLO DE VALIDACIÓN ===\n";

    // Función para validar si se puede facturar para una actividad
    function puedeFacturarActividad($facturacionElectronica, int $codigoActividad): bool
    {
        $actividades = obtenerActividadesSeguras($facturacionElectronica);
        if ($actividades === null || $actividades === []) {
            return false;
        }

        $actividad = buscarActividad($actividades, $codigoActividad);
        if ($actividad === null || $actividad === []) {
            return false;
        }

        // Verificar si la actividad está vigente
        $fechaHoy = date('Ymd');

        // Si tiene fecha desde, verificar que ya esté vigente
        if ($actividad['fecha_desde'] && $fechaHoy < $actividad['fecha_desde']) {
            return false;
        }

        // Si tiene fecha hasta, verificar que no haya vencido
        return ! ($actividad['fecha_hasta'] && $fechaHoy > $actividad['fecha_hasta']);
    }

    // Ejemplo de validación
    $codigoAValidar = 620100; // Programación informática
    if (puedeFacturarActividad($facturacionElectronica, $codigoAValidar)) {
        echo sprintf('✅ Puede facturar para la actividad %d%s', $codigoAValidar, PHP_EOL);
    } else {
        echo sprintf('❌ No puede facturar para la actividad %d%s', $codigoAValidar, PHP_EOL);
    }

    echo "\n=== INFORMACIÓN ADICIONAL ===\n";
    echo "• Este método devuelve solo las actividades del CUIT emisor\n";
    echo "• Las actividades deben estar vigentes en AFIP para poder facturar\n";
    echo "• El código de actividad puede ser requerido en algunos comprobantes\n";
    echo "• Use este método para validar antes de emitir comprobantes\n";
    echo "• Las actividades se basan en el Clasificador de Actividades Económicas\n";
    echo "• El campo 'Orden' indica la prioridad de la actividad\n";

} catch (AfipException $e) {
    echo sprintf('❌ Error de AFIP: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error general: %s%s', $e->getMessage(), PHP_EOL);
}
