<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use PhpAfipWs\Authorization\GeneradorCertificados;
use PhpAfipWs\Exception\CertificadoException;
use PhpAfipWs\Exception\ValidacionException;

// Configuración
$alias = 'jgutierrez';
$archivoCertificado = 'credenciales/'.$alias.'.pem';

try {
    // Verificar que existe el certificado
    if (! file_exists($archivoCertificado)) {
        echo sprintf('❌ Error: No se encontró el certificado: %s%s', $archivoCertificado, PHP_EOL);
        echo "   Debe obtener el certificado desde AFIP después de generar el CSR\n";
        exit(1);
    }

    echo "Extrayendo información del certificado X.509...\n";

    // Leer el contenido del certificado
    $contenidoCertificado = file_get_contents($archivoCertificado);
    if ($contenidoCertificado === false) {
        echo sprintf('❌ Error: No se pudo leer el archivo del certificado: %s%s', $archivoCertificado, PHP_EOL);
        exit(1);
    }

    // Extraer información del certificado
    $informacion = GeneradorCertificados::extraerInformacionCertificado($contenidoCertificado);

    echo "✓ Información del certificado extraída exitosamente:\n\n";

    // Mostrar información relevante
    echo "Información del Sujeto (Subject):\n";
    if (isset($informacion['subject'])) {
        foreach ($informacion['subject'] as $campo => $valor) {
            echo sprintf('  %s: %s%s', $campo, $valor, PHP_EOL);
        }
    }

    echo "\nInformación del Emisor (Issuer):\n";
    if (isset($informacion['issuer'])) {
        foreach ($informacion['issuer'] as $campo => $valor) {
            echo sprintf('  %s: %s%s', $campo, $valor, PHP_EOL);
        }
    }

    echo "\nValidez del Certificado:\n";
    if (isset($informacion['validFrom_time_t']) && isset($informacion['validTo_time_t'])) {
        echo '  Válido desde: '.date('Y-m-d H:i:s', $informacion['validFrom_time_t'])."\n";
        echo '  Válido hasta: '.date('Y-m-d H:i:s', $informacion['validTo_time_t'])."\n";

        // Verificar si el certificado está vigente
        $ahora = time();
        if ($ahora < $informacion['validFrom_time_t']) {
            echo "  Estado: ⚠ Certificado aún no válido\n";
        } elseif ($ahora > $informacion['validTo_time_t']) {
            echo "  Estado: ❌ Certificado expirado\n";
        } else {
            echo "  Estado: ✓ Certificado vigente\n";
        }
    }

    echo "\nInformación Técnica:\n";
    if (isset($informacion['serialNumber'])) {
        echo '  Número de serie: '.$informacion['serialNumber']."\n";
    }

    if (isset($informacion['version'])) {
        echo '  Versión: '.$informacion['version']."\n";
    }

    if (isset($informacion['signatureTypeSN'])) {
        echo '  Algoritmo de firma: '.$informacion['signatureTypeSN']."\n";
    }
} catch (CertificadoException|ValidacionException $e) {
    echo sprintf('❌ Error: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Campo: %s%s', $e->getCampo() ?? 'N/A', PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error inesperado: %s%s', $e->getMessage(), PHP_EOL);
}
