<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use PhpAfipWs\Authorization\GeneradorCertificados;
use PhpAfipWs\Exception\ValidacionException;

try {
    // Definir un Distinguished Name (DN) válido
    $dnValido = [
        'countryName' => 'AR',
        'stateOrProvinceName' => 'Córdoba',
        'localityName' => 'Córdoba',
        'organizationName' => 'Mi Empresa S.A.',
        'commonName' => 'Mi Empresa - WS',
        'serialNumber' => 'CUIT 12345678901',
    ];

    echo "Validando un DN con campos correctos...\n";

    // Intentar validar el DN
    GeneradorCertificados::validarInformacionDN($dnValido);

    echo "✓ DN validado exitosamente. Todos los campos requeridos están presentes y con el formato correcto.\n\n";

    // --- Ejemplo de un DN inválido (falta el campo 'localityName') ---
    $dnInvalido = [
        'countryName' => 'AR',
        'stateOrProvinceName' => 'Buenos Aires',
        'organizationName' => 'Otra Empresa S.A.',
        'commonName' => 'Otra Empresa - WS',
        'serialNumber' => 'CUIT 20304050607',
    ];

    echo "Validando un DN inválido (falta un campo)...\n";

    // Esto generará una ValidacionException
    GeneradorCertificados::validarInformacionDN($dnInvalido);

} catch (ValidacionException $e) {
    echo sprintf('❌ Error de validación: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Campo: %s%s', $e->obtenerCampo() ?? 'N/A', PHP_EOL);
    echo sprintf('   Valor: %s%s', is_array($e->obtenerValor()) ? json_encode($e->obtenerValor()) : $e->obtenerValor() ?? 'N/A', PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error inesperado: %s%s', $e->getMessage(), PHP_EOL);
}
