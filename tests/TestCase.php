<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Crea un directorio temporal para tests
     */
    protected function createTempDirectory(): string
    {
        $tempDir = sys_get_temp_dir().'/phpafipws_test_'.uniqid();
        if (! is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        return $tempDir;
    }

    /**
     * Limpia un directorio temporal
     */
    protected function cleanupTempDirectory(string $dir): void
    {
        if (is_dir($dir)) {
            $files = array_diff(scandir($dir), ['.', '..']);
            foreach ($files as $file) {
                $filePath = $dir.DIRECTORY_SEPARATOR.$file;
                if (is_file($filePath)) {
                    unlink($filePath);
                }
            }

            rmdir($dir);
        }
    }

    /**
     * Crea archivos de certificado y clave mock para testing
     */
    protected function createMockCertificateFiles(string $dir): array
    {
        $certFile = $dir.DIRECTORY_SEPARATOR.'cert';
        $keyFile = $dir.DIRECTORY_SEPARATOR.'key';

        // Crear archivos mock (contenido no importa para la mayoría de tests)
        file_put_contents($certFile, 'mock certificate content');
        file_put_contents($keyFile, 'mock private key content');

        return [
            'cert' => $certFile,
            'key' => $keyFile,
        ];
    }

    /**
     * Obtiene opciones básicas para instanciar Afip en tests
     */
    protected function getBasicAfipOptions(string $resourcesDir, string $taDir): array
    {
        return [
            'cuit' => 20123456789,
            'carpeta_recursos' => $resourcesDir,
            'carpeta_ta' => $taDir,
            'modo_produccion' => false,
            'nombre_certificado' => 'cert',
            'nombre_clave' => 'key',
            'contrasena_clave' => '',
        ];
    }
}
