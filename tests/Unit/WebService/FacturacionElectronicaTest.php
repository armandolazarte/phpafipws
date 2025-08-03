<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\FacturacionElectronicaException;
use PhpAfipWs\Exception\WebServiceException;
use PhpAfipWs\WebService\FacturacionElectronica;

describe('FacturacionElectronica', function (): void {
    beforeEach(function (): void {
        $this->tempDir = $this->createTempDirectory();
        $this->taDir = $this->createTempDirectory();
        $this->createMockCertificateFiles($this->tempDir);

        // Copiar el archivo WSAA WSDL necesario
        $wsaaSource = __DIR__.'/../../../src/Resources/wsaa.wsdl';
        $wsaaTarget = $this->tempDir.DIRECTORY_SEPARATOR.'wsaa.wsdl';
        if (file_exists($wsaaSource)) {
            copy($wsaaSource, $wsaaTarget);
        }

        $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);
        $this->afip = new Afip($opciones);
        $this->facturacionElectronica = new FacturacionElectronica($this->afip);
    });

    afterEach(function (): void {
        $this->cleanupTempDirectory($this->tempDir);
        $this->cleanupTempDirectory($this->taDir);
    });

    describe('constructor y configuración', function (): void {
        it('puede ser instanciado correctamente', function (): void {
            expect($this->facturacionElectronica)->toBeInstanceOf(FacturacionElectronica::class);
        });

        it('devuelve el nombre correcto del servicio', function (): void {
            $reflection = new ReflectionClass($this->facturacionElectronica);
            $method = $reflection->getMethod('obtenerNombreServicio');
            $method->setAccessible(true);

            $nombreServicio = $method->invoke($this->facturacionElectronica);

            expect($nombreServicio)->toBe('wsfe');
        });
    });

    describe('obtenerUltimoNumeroComprobante', function (): void {
        it('lanza excepción si la respuesta no tiene la estructura esperada', function (): void {
            // Crear una clase stub que simule una respuesta inválida
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerUltimoComprobante(int $puntoVenta, int $tipoComprobante): mixed
                {
                    return (object) ['invalid' => 'structure'];
                }
            };

            expect(fn (): int => $facturacionElectronicaStub->obtenerUltimoNumeroComprobante(1, 11))
                ->toThrow(WebServiceException::class, 'La respuesta del servicio no tiene la estructura esperada');
        });

        it('lanza excepción si el número de comprobante no es válido', function (): void {
            // Crear una clase stub que simule una respuesta con número inválido
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerUltimoComprobante(int $puntoVenta, int $tipoComprobante): mixed
                {
                    return (object) [
                        'FECompUltimoAutorizadoResult' => (object) [
                            'CbteNro' => 'invalid_number',
                            'PtoVta' => 1,
                            'CbteTipo' => 11,
                        ],
                    ];
                }
            };

            expect(fn (): int => $facturacionElectronicaStub->obtenerUltimoNumeroComprobante(1, 11))
                ->toThrow(WebServiceException::class, 'El número de comprobante no es válido');
        });

        it('extrae correctamente el número del último comprobante', function (): void {
            // Crear una clase stub que simule una respuesta válida
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerUltimoComprobante(int $puntoVenta, int $tipoComprobante): mixed
                {
                    return (object) [
                        'FECompUltimoAutorizadoResult' => (object) [
                            'CbteNro' => 123,
                            'PtoVta' => 1,
                            'CbteTipo' => 11,
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerUltimoNumeroComprobante(1, 11);

            expect($resultado)->toBe(123);
        });
    });

    describe('autorizarProximoComprobante', function (): void {
        it('lanza excepción si el punto de venta no es numérico', function (): void {
            $datosComprobante = [
                'PtoVta' => 'invalid',
                'CbteTipo' => 11,
                'ImpTotal' => 121.00,
            ];

            expect(fn () => $this->facturacionElectronica->autorizarProximoComprobante($datosComprobante))
                ->toThrow(FacturacionElectronicaException::class, 'El punto de venta debe ser un valor numérico');
        });

        it('lanza excepción si el tipo de comprobante no es numérico', function (): void {
            $datosComprobante = [
                'PtoVta' => 1,
                'CbteTipo' => 'invalid',
                'ImpTotal' => 121.00,
            ];

            expect(fn () => $this->facturacionElectronica->autorizarProximoComprobante($datosComprobante))
                ->toThrow(FacturacionElectronicaException::class, 'El tipo de comprobante debe ser un valor numérico');
        });

        it('calcula correctamente el próximo número de comprobante', function (): void {
            // Crear una clase stub que simule el comportamiento esperado
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerUltimoNumeroComprobante(int $puntoVenta, int $tipoComprobante): int
                {
                    return 5; // Simular que el último número es 5
                }

                public function autorizarComprobante(array $comprobantes): mixed
                {
                    // Verificar que se calculó correctamente el próximo número
                    expect($comprobantes[0]['CbteDesde'])->toBe(6);
                    expect($comprobantes[0]['CbteHasta'])->toBe(6);

                    return (object) [
                        'FECAESolicitarResult' => (object) [
                            'CAE' => '12345678901234',
                            'Vencimiento' => '20241231',
                        ],
                    ];
                }
            };

            $datosComprobante = [
                'PtoVta' => 1,
                'CbteTipo' => 11,
                'ImpTotal' => 121.00,
            ];

            $resultado = $facturacionElectronicaStub->autorizarProximoComprobante($datosComprobante);

            expect($resultado)->toBeObject();
        });

        it('usa valores por defecto cuando no se especifican PtoVta y CbteTipo', function (): void {
            // Crear una clase stub que verifique los valores por defecto
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerUltimoNumeroComprobante(int $puntoVenta, int $tipoComprobante): int
                {
                    // Verificar que se usan los valores por defecto
                    expect($puntoVenta)->toBe(1);
                    expect($tipoComprobante)->toBe(1);

                    return 0;
                }

                public function autorizarComprobante(array $comprobantes): mixed
                {
                    return (object) [
                        'FECAESolicitarResult' => (object) [
                            'CAE' => '12345678901234',
                            'Vencimiento' => '20241231',
                        ],
                    ];
                }
            };

            $datosComprobante = [
                'ImpTotal' => 121.00,
            ];

            $resultado = $facturacionElectronicaStub->autorizarProximoComprobante($datosComprobante);

            expect($resultado)->toBeObject();
        });
    });

    describe('validación de tipos de datos', function (): void {
        it('valida que PtoVta sea numérico en autorizarProximoComprobante', function (): void {
            $datosComprobante = [
                'PtoVta' => 'texto',
                'CbteTipo' => 11,
            ];

            expect(fn () => $this->facturacionElectronica->autorizarProximoComprobante($datosComprobante))
                ->toThrow(FacturacionElectronicaException::class, 'El punto de venta debe ser un valor numérico');
        });

        it('valida que CbteTipo sea numérico en autorizarProximoComprobante', function (): void {
            $datosComprobante = [
                'PtoVta' => 1,
                'CbteTipo' => 'texto',
            ];

            expect(fn () => $this->facturacionElectronica->autorizarProximoComprobante($datosComprobante))
                ->toThrow(FacturacionElectronicaException::class, 'El tipo de comprobante debe ser un valor numérico');
        });

        it('acepta valores numéricos como string en autorizarProximoComprobante', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerUltimoNumeroComprobante(int $puntoVenta, int $tipoComprobante): int
                {
                    expect($puntoVenta)->toBe(1);
                    expect($tipoComprobante)->toBe(11);

                    return 0;
                }

                public function autorizarComprobante(array $comprobantes): mixed
                {
                    return (object) ['result' => 'ok'];
                }
            };

            $datosComprobante = [
                'PtoVta' => '1',  // String numérico
                'CbteTipo' => '11', // String numérico
            ];

            $resultado = $facturacionElectronicaStub->autorizarProximoComprobante($datosComprobante);

            expect($resultado)->toBeObject();
        });
    });

    describe('estructura de respuestas', function (): void {
        it('valida estructura de respuesta en obtenerUltimoNumeroComprobante', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerUltimoComprobante(int $puntoVenta, int $tipoComprobante): mixed
                {
                    return null; // Respuesta inválida
                }
            };

            expect(fn (): int => $facturacionElectronicaStub->obtenerUltimoNumeroComprobante(1, 11))
                ->toThrow(WebServiceException::class, 'La respuesta del servicio no tiene la estructura esperada');
        });

        it('valida que CbteNro sea numérico en obtenerUltimoNumeroComprobante', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerUltimoComprobante(int $puntoVenta, int $tipoComprobante): mixed
                {
                    return (object) [
                        'FECompUltimoAutorizadoResult' => (object) [
                            'CbteNro' => 'texto_no_numerico', // Valor no numérico pero que pase la validación de estructura
                        ],
                    ];
                }
            };

            expect(fn (): int => $facturacionElectronicaStub->obtenerUltimoNumeroComprobante(1, 11))
                ->toThrow(WebServiceException::class, 'El número de comprobante no es válido');
        });
    });
});
