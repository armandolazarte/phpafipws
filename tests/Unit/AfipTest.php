<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\ArchivoException;
use PhpAfipWs\Exception\ConfiguracionException;
use PhpAfipWs\Exception\ValidacionException;
use PhpAfipWs\Exception\WebServiceException;

describe('Afip', function (): void {
    beforeEach(function (): void {
        $this->tempDir = $this->createTempDirectory();
        $this->taDir = $this->createTempDirectory();
        $this->createMockCertificateFiles($this->tempDir);

        // Copiar el archivo WSAA WSDL necesario
        $wsaaSource = __DIR__.'/../../src/Resources/wsaa.wsdl';
        $wsaaTarget = $this->tempDir.DIRECTORY_SEPARATOR.'wsaa.wsdl';
        if (file_exists($wsaaSource)) {
            copy($wsaaSource, $wsaaTarget);
        }
    });

    afterEach(function (): void {
        $this->cleanupTempDirectory($this->tempDir);
        $this->cleanupTempDirectory($this->taDir);
    });

    describe('constructor', function (): void {
        it('puede ser instanciado con opciones válidas', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);

            $afip = new Afip($opciones);

            expect($afip)->toBeInstanceOf(Afip::class);
            expect($afip->obtenerCuit())->toBe(20123456789);
            expect($afip->esModoProduccion())->toBeFalse();
        });

        it('lanza excepción si falta el CUIT', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            unset($opciones['cuit']);

            expect(fn (): Afip => new Afip($opciones))
                ->toThrow(ConfiguracionException::class, 'El campo "cuit" es requerido en el array de opciones');
        });

        it('lanza excepción si falta carpeta_recursos', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            unset($opciones['carpeta_recursos']);

            expect(fn (): Afip => new Afip($opciones))
                ->toThrow(ConfiguracionException::class, 'El campo "carpeta_recursos" es requerido en el array de opciones');
        });

        it('lanza excepción si falta carpeta_ta', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            unset($opciones['carpeta_ta']);

            expect(fn (): Afip => new Afip($opciones))
                ->toThrow(ConfiguracionException::class, 'El campo "carpeta_ta" es requerido en el array de opciones');
        });

        it('lanza excepción si el CUIT no es numérico', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            $opciones['cuit'] = 'invalid_cuit';

            expect(fn (): Afip => new Afip($opciones))
                ->toThrow(ValidacionException::class, 'El cuit debe ser numérico');
        });

        it('lanza excepción si no encuentra el archivo de certificado', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            unlink($this->tempDir.DIRECTORY_SEPARATOR.'cert');

            expect(fn (): Afip => new Afip($opciones))
                ->toThrow(ArchivoException::class, 'Archivo de certificado no encontrado');
        });

        it('lanza excepción si no encuentra el archivo de clave privada', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            unlink($this->tempDir.DIRECTORY_SEPARATOR.'key');

            expect(fn (): Afip => new Afip($opciones))
                ->toThrow(ArchivoException::class, 'Archivo de clave privada no encontrado');
        });

        it('puede configurarse en modo producción', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            $opciones['modo_produccion'] = true;

            $afip = new Afip($opciones);

            expect($afip->esModoProduccion())->toBeTrue();
        });

        it('puede configurar nombres personalizados de certificado y clave', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            $opciones['nombre_certificado'] = 'mi_cert';
            $opciones['nombre_clave'] = 'mi_key';

            // Crear archivos con nombres personalizados
            file_put_contents($this->tempDir.DIRECTORY_SEPARATOR.'mi_cert', 'cert content');
            file_put_contents($this->tempDir.DIRECTORY_SEPARATOR.'mi_key', 'key content');

            $afip = new Afip($opciones);

            expect($afip)->toBeInstanceOf(Afip::class);
        });
    });

    describe('métodos públicos', function (): void {
        beforeEach(function (): void {
            $this->opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            $this->afip = new Afip($this->opciones);
        });

        it('obtiene la versión del SDK', function (): void {
            $version = $this->afip->obtenerVersionSDK();

            expect($version)->toBeString();
            expect($version)->toMatch('/^\d+\.\d+\.\d+$/');
        });

        it('obtiene el CUIT configurado', function (): void {
            $cuit = $this->afip->obtenerCuit();

            expect($cuit)->toBe(20123456789);
        });

        it('puede configurar carpeta WSDL personalizada', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            $opciones['carpeta_wsdl'] = '/custom/wsdl/path';

            $afip = new Afip($opciones);

            expect($afip->getCarpetaWsdl())->toBe('/custom/wsdl/path');
        });

        it('devuelve null si no hay carpeta WSDL personalizada', function (): void {
            expect($this->afip->getCarpetaWsdl())->toBeNull();
        });
    });

    describe('acceso a Web Services', function (): void {
        beforeEach(function (): void {
            $this->opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            $this->afip = new Afip($this->opciones);
        });

        it('lanza excepción al acceder a Web Service no implementado', function (): void {
            expect(fn () => $this->afip->WebServiceInexistente)
                ->toThrow(WebServiceException::class, 'La propiedad WebServiceInexistente no existe');
        });

        it('lanza excepción al acceder a propiedad inexistente', function (): void {
            expect(fn () => $this->afip->propiedadInexistente)
                ->toThrow(WebServiceException::class, 'La propiedad propiedadInexistente no existe');
        });
    });

    describe('conversión de tipos', function (): void {
        it('convierte CUIT string a entero', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            $opciones['cuit'] = '20123456789';

            $afip = new Afip($opciones);

            expect($afip->obtenerCuit())->toBe(20123456789);
            expect($afip->obtenerCuit())->toBeInt();
        });

        it('maneja contraseña de clave como string', function (): void {
            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
            $opciones['contrasena_clave'] = 123456;

            $afip = new Afip($opciones);

            expect($afip)->toBeInstanceOf(Afip::class);
        });
    });
});
