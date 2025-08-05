<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use PhpAfipWs\Authorization\GeneradorCertificados;
use PhpAfipWs\Exception\CertificadoException;
use PhpAfipWs\Exception\ValidacionException;

// Configuración
$alias = 'jgutierrez';
$archivoCSR = 'credenciales/'.$alias.'.csr';

try {
    // Verificar que existe el CSR
    if (! file_exists($archivoCSR)) {
        echo sprintf('❌ Error: No se encontró el CSR: %s%s', $archivoCSR, PHP_EOL);
        echo "   Ejecute primero generar_csr_nueva.php\n";
        exit(1);
    }

    echo "Extrayendo Distinguished Name (DN) del CSR...\n";

    // Extraer el DN del CSR usando la ruta del archivo
    $dn = GeneradorCertificados::extraerInformacionCSR($archivoCSR);

    echo "✓ Distinguished Name extraído exitosamente:\n\n";

    // Mostrar el DN de forma organizada
    $camposTraducidos = [
        'countryName' => 'País (Country)',
        'stateOrProvinceName' => 'Estado/Provincia (State)',
        'localityName' => 'Localidad (Locality)',
        'organizationName' => 'Organización (Organization)',
        'commonName' => 'Nombre Común (Common Name)',
        'serialNumber' => 'Número de Serie (Serial Number)',
        'emailAddress' => 'Correo Electrónico (Email)',
        'organizationalUnitName' => 'Unidad Organizacional (Organizational Unit)',
    ];

    foreach ($dn as $campo => $valor) {
        $nombreCampo = $camposTraducidos[$campo] ?? $campo;
        echo sprintf('  %s: %s%s', $nombreCampo, $valor, PHP_EOL);
    }

    echo "\nEste DN será incluido en el certificado que genere AFIP.\n";
    echo "Verifique que todos los datos sean correctos antes de enviar el CSR.\n";
} catch (CertificadoException|ValidacionException $e) {
    echo sprintf('❌ Error: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Campo: %s%s', $e->getCampo() ?? 'N/A', PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error inesperado: %s%s', $e->getMessage(), PHP_EOL);
}
