<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use PhpAfipWs\Authorization\GeneradorCertificados;
use PhpAfipWs\Exception\CertificadoException;
use PhpAfipWs\Exception\ConfiguracionException;
use PhpAfipWs\Exception\ValidacionException;

// Configuración
$alias = 'jgutierrez';
$archivoClavePrivada = 'credenciales/'.$alias.'.key';
$archivoCSR = 'credenciales/'.$alias.'.csr';

// Distinguished Name (DN) para el Certificate Signing Request (CSR)
// Los siguientes datos son de ejemplo y no concuerdan con una persona real
$dn = GeneradorCertificados::crearInformacionDN(
    cuit: '12345678901', // CUIT de 11 dígitos
    nombreOrganizacion: 'Mi Empresa S.A.',
    nombreComun: 'Mi Empresa - WS',
    provincia: 'Córdoba',
    localidad: 'Córdoba',
    pais: 'AR'
);

try {
    // Verificar que existe la clave privada
    if (! file_exists($archivoClavePrivada)) {
        echo sprintf('❌ Error: No se encontró la clave privada: %s%s', $archivoClavePrivada, PHP_EOL);
        echo "   Ejecute primero generar_clave_privada.php\n";
        exit(1);
    }

    // Leer el contenido de la clave privada
    $clavePrivada = file_get_contents($archivoClavePrivada);
    if ($clavePrivada === false) {
        echo sprintf('❌ Error: No se pudo leer la clave privada: %s%s', $archivoClavePrivada, PHP_EOL);
        exit(1);
    }

    // Lógica para crear o usar el CSR
    if (! file_exists($archivoCSR)) {
        // Si el archivo CSR no existe, lo creamos
        echo "Generando Certificate Signing Request (CSR)...\n";

        // Genera un CSR en formato PKCS#10 con la clave privada y el DN
        $csr = GeneradorCertificados::generarCSR($clavePrivada, $dn);

        // Guarda el CSR en el archivo
        file_put_contents($archivoCSR, $csr);

        echo sprintf('✓ CSR generado exitosamente: %s%s', $archivoCSR, PHP_EOL);
    } else {
        // Si el CSR ya existe, solo mostramos la advertencia
        echo sprintf('⚠ El CSR ya existe: %s%s', $archivoCSR, PHP_EOL);
    }

    // Extraemos y mostramos la información del CSR, sin importar si fue creado o ya existía
    echo "\nInformación del CSR:\n";
    $dnExtraido = GeneradorCertificados::extraerInformacionCSR($archivoCSR);
    foreach ($dnExtraido as $campo => $valor) {
        echo sprintf('   %s: %s%s', $campo, $valor, PHP_EOL);
    }

} catch (CertificadoException|ConfiguracionException|ValidacionException $e) {
    echo sprintf('❌ Error: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Campo: %s%s', $e->getCampo() ?? 'N/A', PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error inesperado: %s%s', $e->getMessage(), PHP_EOL);
}
