<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\ArchivoException;

describe('Afip Integration', function (): void {
    beforeEach(function (): void {
        $this->tempDir = $this->createTempDirectory();
        $this->taDir = $this->createTempDirectory();
        $this->createMockCertificateFiles($this->tempDir);
    });

    afterEach(function (): void {
        $this->cleanupTempDirectory($this->tempDir);
        $this->cleanupTempDirectory($this->taDir);
    });

    it('puede instanciarse con configuración completa', function (): void {
        $opciones = [
            'cuit' => 20123456789,
            'carpeta_recursos' => $this->tempDir,
            'carpeta_ta' => $this->taDir,
            'modo_produccion' => false,
            'nombre_certificado' => 'cert',
            'nombre_clave' => 'key',
            'contrasena_clave' => 'test_password',
            'carpeta_wsdl' => $this->tempDir,
            'manejar_excepciones_soap' => true,
        ];

        $afip = new Afip($opciones);

        expect($afip->obtenerCuit())->toBe(20123456789);
        expect($afip->esModoProduccion())->toBeFalse();
        expect($afip->getCarpetaWsdl())->toBe($this->tempDir);
        expect($afip->obtenerVersionSDK())->toMatch('/^\d+\.\d+\.\d+$/');
    });

    it('maneja diferentes formatos de CUIT', function (): void {
        $testCases = [
            ['cuit' => 20123456789, 'expected' => 20123456789],
            ['cuit' => '20123456789', 'expected' => 20123456789],
            ['cuit' => 27123456789, 'expected' => 27123456789],
        ];

        foreach ($testCases as $case) {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            $opciones['cuit'] = $case['cuit'];

            $afip = new Afip($opciones);
            expect($afip->obtenerCuit())->toBe($case['expected']);
        }
    });

    it('valida la estructura de directorios requerida', function (): void {
        $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);

        // Crear subdirectorios para simular estructura real
        $resourcesSubdir = $this->tempDir.DIRECTORY_SEPARATOR.'subdir';
        mkdir($resourcesSubdir);

        $this->createMockCertificateFiles($resourcesSubdir);
        $opciones['carpeta_recursos'] = $resourcesSubdir;

        $afip = new Afip($opciones);
        expect($afip)->toBeInstanceOf(Afip::class);
    });

    it('maneja rutas con diferentes separadores', function (): void {
        $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);

        // Normalizar rutas con diferentes separadores
        $opciones['carpeta_recursos'] = mb_rtrim($this->tempDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;
        $opciones['carpeta_ta'] = mb_rtrim($this->taDir, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR;

        $afip = new Afip($opciones);
        expect($afip)->toBeInstanceOf(Afip::class);
    });

    it('preserva configuración entre instancias', function (): void {
        $opciones1 = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
        $opciones1['modo_produccion'] = false;

        $opciones2 = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
        $opciones2['modo_produccion'] = true;

        $afip1 = new Afip($opciones1);
        $afip2 = new Afip($opciones2);

        expect($afip1->esModoProduccion())->toBeFalse();
        expect($afip2->esModoProduccion())->toBeTrue();
    });

    it('maneja configuraciones con valores por defecto', function (): void {
        $opcionesMinimas = [
            'cuit' => 20123456789,
            'carpeta_recursos' => $this->tempDir,
            'carpeta_ta' => $this->taDir,
        ];

        $afip = new Afip($opcionesMinimas);

        expect($afip->esModoProduccion())->toBeFalse();
        expect($afip->getCarpetaWsdl())->toBeNull();
    });

    it('valida que los archivos de certificado existan al momento de construcción', function (): void {
        $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);

        // Eliminar archivo de certificado después de crear las opciones
        unlink($this->tempDir.DIRECTORY_SEPARATOR.'cert');

        expect(fn (): Afip => new Afip($opciones))
            ->toThrow(ArchivoException::class);
    });

    it('permite configurar nombres de archivos personalizados', function (): void {
        $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
        $opciones['nombre_certificado'] = 'mi_certificado.pem';
        $opciones['nombre_clave'] = 'mi_clave.key';

        // Crear archivos con nombres personalizados
        file_put_contents($this->tempDir.DIRECTORY_SEPARATOR.'mi_certificado.pem', 'cert content');
        file_put_contents($this->tempDir.DIRECTORY_SEPARATOR.'mi_clave.key', 'key content');

        $afip = new Afip($opciones);
        expect($afip)->toBeInstanceOf(Afip::class);
    });
});
