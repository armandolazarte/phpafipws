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

// Crear directorio si no existe
if (! is_dir('credenciales') && ! mkdir('credenciales', 0755, true)) {
    echo "❌ Error: No se pudo crear el directorio 'credenciales'\n";
    exit(1);
}

try {
    // Verificar permisos de escritura
    if (! is_writable(dirname($archivoClavePrivada))) {
        echo sprintf('❌ Error: El directorio %s no tiene permisos de escritura%s', dirname($archivoClavePrivada), PHP_EOL);
        exit(1);
    }

    // CUIDADO: No reescribir la clave privada si ya existe
    if (! file_exists($archivoClavePrivada)) {
        echo "Generando clave privada de 2048 bits...\n";

        // Genera una clave privada de 2048 bits sin frase secreta
        $clavePrivada = GeneradorCertificados::generarClavePrivada();

        // Guarda la clave privada en el archivo
        if (file_put_contents($archivoClavePrivada, $clavePrivada) === false) {
            echo sprintf('❌ Error: No se pudo escribir la clave privada en %s%s', $archivoClavePrivada, PHP_EOL);
            exit(1);
        }

        echo sprintf('✓ Clave privada generada exitosamente: %s%s', $archivoClavePrivada, PHP_EOL);
    } else {
        echo sprintf('⚠ La clave privada ya existe: %s%s', $archivoClavePrivada, PHP_EOL);
    }
} catch (CertificadoException|ConfiguracionException|ValidacionException $e) {
    echo sprintf('❌ Error: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo sprintf('❌ Error inesperado: %s%s', $e->getMessage(), PHP_EOL);
}
