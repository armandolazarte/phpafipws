<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\WebServiceException;
use PhpAfipWs\WebService\AfipWebService;

describe('WebService Access', function (): void {
    beforeEach(function (): void {
        $this->tempDir = $this->createTempDirectory();
        $this->taDir = $this->createTempDirectory();
        $this->createMockCertificateFiles($this->tempDir);

        $this->opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
        $this->afip = new Afip($this->opciones);
    });

    afterEach(function (): void {
        $this->cleanupTempDirectory($this->tempDir);
        $this->cleanupTempDirectory($this->taDir);
    });

    it('lanza excepción al acceder a web service no implementado', function (): void {
        expect(fn () => $this->afip->ServicioInexistente)
            ->toThrow(WebServiceException::class, 'La propiedad ServicioInexistente no existe');
    });

    it('lanza excepción al acceder a propiedad inexistente', function (): void {
        expect(fn () => $this->afip->propiedadInexistente)
            ->toThrow(WebServiceException::class, 'La propiedad propiedadInexistente no existe');
    });

    it('valida opciones requeridas para web service genérico', function (): void {
        expect(fn () => $this->afip->webService('test_service', []))
            ->toThrow(WebServiceException::class, 'El campo WSDL es requerido en las opciones para un web service genérico');
    });

    it('valida que todas las opciones requeridas estén presentes', function (): void {
        $opcionesIncompletas = [
            'WSDL' => 'test.wsdl',
            'URL' => 'https://test.url.com',
            // Faltan WSDL_TEST, URL_TEST, servicio
        ];

        expect(fn () => $this->afip->webService('test_service', $opcionesIncompletas))
            ->toThrow(WebServiceException::class, 'El campo WSDL_TEST es requerido en las opciones para un web service genérico');
    });

    it('puede instanciar web service con todas las opciones requeridas', function (): void {
        // Crear archivos WSDL mock
        $wsdlContent = '<?xml version="1.0" encoding="UTF-8"?><definitions xmlns="http://schemas.xmlsoap.org/wsdl/"></definitions>';
        file_put_contents($this->tempDir.DIRECTORY_SEPARATOR.'test.wsdl', $wsdlContent);
        file_put_contents($this->tempDir.DIRECTORY_SEPARATOR.'test-test.wsdl', $wsdlContent);

        $opciones = [
            'WSDL' => $this->tempDir.DIRECTORY_SEPARATOR.'test.wsdl',
            'URL' => 'https://test.url.com',
            'WSDL_TEST' => $this->tempDir.DIRECTORY_SEPARATOR.'test-test.wsdl',
            'URL_TEST' => 'https://test-test.url.com',
            'servicio' => 'test_service',
        ];

        $webService = $this->afip->webService('test_service', $opciones);

        expect($webService)->toBeInstanceOf(AfipWebService::class);
    });
});
