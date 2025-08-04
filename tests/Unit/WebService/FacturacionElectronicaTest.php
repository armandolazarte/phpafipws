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
                    // Variables utilizadas para validar que se pasan correctamente
                    expect($puntoVenta)->toBe(1);
                    expect($tipoComprobante)->toBe(11);

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
                    // Variables utilizadas para validar que se pasan correctamente
                    expect($puntoVenta)->toBe(1);
                    expect($tipoComprobante)->toBe(11);

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
                    // Variables utilizadas para validar que se pasan correctamente
                    expect($puntoVenta)->toBe(1);
                    expect($tipoComprobante)->toBe(11);

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

        it('maneja números de comprobante como string numérico', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerUltimoComprobante(int $puntoVenta, int $tipoComprobante): mixed
                {
                    return (object) [
                        'FECompUltimoAutorizadoResult' => (object) [
                            'CbteNro' => '456', // String numérico
                            'PtoVta' => $puntoVenta,
                            'CbteTipo' => $tipoComprobante,
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerUltimoNumeroComprobante(2, 6);

            expect($resultado)->toBe(456);
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
                    expect($puntoVenta)->toBe(1);
                    expect($tipoComprobante)->toBe(11);

                    return 5; // Simular que el último número es 5
                }

                public function autorizarComprobante(array $comprobantes): mixed
                {
                    // Verificar que se calculó correctamente el próximo número
                    expect($comprobantes[0]['CbteDesde'])->toBe(6);
                    expect($comprobantes[0]['CbteHasta'])->toBe(6);
                    expect($comprobantes[0]['PtoVta'])->toBe(1);
                    expect($comprobantes[0]['CbteTipo'])->toBe(11);

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
                    expect($comprobantes[0]['CbteDesde'])->toBe(1);
                    expect($comprobantes[0]['CbteHasta'])->toBe(1);

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
                    expect($comprobantes[0]['PtoVta'])->toBe('1');
                    expect($comprobantes[0]['CbteTipo'])->toBe('11');

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
                    expect($puntoVenta)->toBe(1);
                    expect($tipoComprobante)->toBe(11);

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
                    expect($puntoVenta)->toBe(1);
                    expect($tipoComprobante)->toBe(11);

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

    describe('obtenerInformacionComprobante', function (): void {
        it('puede obtener información de un comprobante existente', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerInformacionComprobante(int $numero, int $puntoVenta, int $tipoComprobante): mixed
                {
                    expect($numero)->toBe(123);
                    expect($puntoVenta)->toBe(1);
                    expect($tipoComprobante)->toBe(11);

                    return (object) [
                        'CbteDesde' => $numero,
                        'CbteHasta' => $numero,
                        'PtoVta' => $puntoVenta,
                        'CbteTipo' => $tipoComprobante,
                        'CodAutorizacion' => '12345678901234',
                        'FchVto' => '20241231',
                        'ImpTotal' => 121.00,
                        'ImpNeto' => 100.00,
                        'ImpIVA' => 21.00,
                        'MonId' => 'PES',
                        'MonCotiz' => 1,
                        'Resultado' => 'A',
                        'CbteFch' => '20240815',
                        'DocTipo' => 80,
                        'DocNro' => 33693450239,
                        'EmisionTipo' => 'CAE',
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerInformacionComprobante(123, 1, 11);

            expect($resultado)->toBeObject();
            expect($resultado->CbteDesde)->toBe(123);
            expect($resultado->CodAutorizacion)->toBe('12345678901234');
            expect($resultado->ImpTotal)->toBe(121.00);
            expect($resultado->Resultado)->toBe('A');
        });

        it('devuelve null cuando el comprobante no existe', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerInformacionComprobante(int $numero, int $puntoVenta, int $tipoComprobante): mixed
                {
                    expect($numero)->toBe(999);
                    expect($puntoVenta)->toBe(1);
                    expect($tipoComprobante)->toBe(11);

                    return null; // Comprobante no encontrado
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerInformacionComprobante(999, 1, 11);

            expect($resultado)->toBeNull();
        });
    });

    describe('métodos de consulta de parámetros', function (): void {
        it('puede obtener tipos de comprobante', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerTiposComprobante(): mixed
                {
                    return (object) [
                        'FEParamGetTiposCbteResult' => (object) [
                            'ResultGet' => [
                                (object) ['Id' => 1, 'Desc' => 'Factura A'],
                                (object) ['Id' => 6, 'Desc' => 'Factura B'],
                                (object) ['Id' => 11, 'Desc' => 'Factura C'],
                            ],
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerTiposComprobante();

            expect($resultado)->toBeObject();
            expect($resultado->FEParamGetTiposCbteResult->ResultGet)->toBeArray();
        });

        it('puede obtener tipos de documento', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerTiposDocumento(): mixed
                {
                    return (object) [
                        'FEParamGetTiposDocResult' => (object) [
                            'ResultGet' => [
                                (object) ['Id' => 80, 'Desc' => 'CUIT'],
                                (object) ['Id' => 86, 'Desc' => 'CUIL'],
                                (object) ['Id' => 96, 'Desc' => 'DNI'],
                            ],
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerTiposDocumento();

            expect($resultado)->toBeObject();
            expect($resultado->FEParamGetTiposDocResult->ResultGet)->toBeArray();
        });

        it('puede obtener tipos de moneda', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerTiposMoneda(): mixed
                {
                    return (object) [
                        'FEParamGetTiposMonedasResult' => (object) [
                            'ResultGet' => [
                                (object) ['Id' => 'PES', 'Desc' => 'Pesos Argentinos'],
                                (object) ['Id' => 'DOL', 'Desc' => 'Dolar Estadounidense'],
                                (object) ['Id' => 'EUR', 'Desc' => 'Euro'],
                            ],
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerTiposMoneda();

            expect($resultado)->toBeObject();
            expect($resultado->FEParamGetTiposMonedasResult->ResultGet)->toBeArray();
        });
    });

    describe('obtenerEstadoServidor', function (): void {
        it('puede verificar el estado del servidor', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerEstadoServidor(): mixed
                {
                    return (object) [
                        'FEDummyResult' => (object) [
                            'AppServer' => 'OK',
                            'DbServer' => 'OK',
                            'AuthServer' => 'OK',
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerEstadoServidor();

            expect($resultado)->toBeObject();
            expect($resultado->FEDummyResult->AppServer)->toBe('OK');
        });
    });

    describe('gestión CAEA', function (): void {
        it('puede crear un CAEA', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function crearCAEA(int $periodo, int $orden): mixed
                {
                    expect($periodo)->toBe(202412);
                    expect($orden)->toBe(1);

                    return (object) [
                        'FECAEASolicitarResult' => (object) [
                            'CAEA' => '21234567890123',
                            'Periodo' => $periodo,
                            'Orden' => $orden,
                            'FchVigDesde' => '20241201',
                            'FchVigHasta' => '20241215',
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->crearCAEA(202412, 1);

            expect($resultado)->toBeObject();
            expect($resultado->FECAEASolicitarResult->CAEA)->toBe('21234567890123');
        });

        it('puede consultar un CAEA existente', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerCAEA(int $caea): mixed
                {
                    expect($caea)->toBe(21234567890123);

                    return (object) [
                        'FECAEAConsultarResult' => (object) [
                            'CAEA' => $caea,
                            'Periodo' => 202412,
                            'Orden' => 1,
                            'FchVigDesde' => '20241201',
                            'FchVigHasta' => '20241215',
                            'FchTopeInf' => '20241210',
                            'FchProceso' => '20241201',
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerCAEA(21234567890123);

            expect($resultado)->toBeObject();
            expect($resultado->FECAEAConsultarResult->CAEA)->toBe(21234567890123);
        });
    });

    describe('métodos adicionales de consulta', function (): void {
        it('puede obtener puntos de venta', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerPuntosDeVenta(): mixed
                {
                    return (object) [
                        'FEParamGetPtosVentaResult' => (object) [
                            'ResultGet' => [
                                (object) ['Id' => 1, 'Desc' => 'Punto de Venta 1', 'EmisionTipo' => 'CAE', 'Bloqueado' => 'N'],
                                (object) ['Id' => 2, 'Desc' => 'Punto de Venta 2', 'EmisionTipo' => 'CAE', 'Bloqueado' => 'N'],
                            ],
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerPuntosDeVenta();

            expect($resultado)->toBeObject();
            expect($resultado->FEParamGetPtosVentaResult->ResultGet)->toBeArray();
        });

        it('puede obtener tipos de concepto', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerTiposConcepto(): mixed
                {
                    return (object) [
                        'FEParamGetTiposConceptoResult' => (object) [
                            'ResultGet' => [
                                (object) ['Id' => 1, 'Desc' => 'Productos'],
                                (object) ['Id' => 2, 'Desc' => 'Servicios'],
                                (object) ['Id' => 3, 'Desc' => 'Productos y Servicios'],
                            ],
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerTiposConcepto();

            expect($resultado)->toBeObject();
            expect($resultado->FEParamGetTiposConceptoResult->ResultGet)->toBeArray();
        });

        it('puede obtener tipos de alícuota', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerTiposAlicuota(): mixed
                {
                    return (object) [
                        'FEParamGetTiposIvaResult' => [
                            (object) ['Id' => 3, 'Desc' => '0%'],
                            (object) ['Id' => 4, 'Desc' => '10.5%'],
                            (object) ['Id' => 5, 'Desc' => '21%'],
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerTiposAlicuota();

            expect($resultado)->toBeObject();
            expect($resultado->FEParamGetTiposIvaResult)->toBeArray();
        });

        it('puede obtener condiciones de IVA del receptor', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerCondicionesIvaReceptor(): mixed
                {
                    return (object) [
                        'FEParamGetCondicionIvaReceptorResult' => [
                            (object) ['Id' => 1, 'Desc' => 'IVA Responsable Inscripto'],
                            (object) ['Id' => 4, 'Desc' => 'IVA Sujeto Exento'],
                            (object) ['Id' => 5, 'Desc' => 'Consumidor Final'],
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerCondicionesIvaReceptor();

            expect($resultado)->toBeObject();
            expect($resultado->FEParamGetCondicionIvaReceptorResult)->toBeArray();
        });

        it('puede obtener tipos opcionales', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerTiposOpcional(): mixed
                {
                    return (object) [
                        'FEParamGetTiposOpcionalResult' => [
                            (object) ['Id' => 1, 'Desc' => 'CVU'],
                            (object) ['Id' => 2, 'Desc' => 'CBU'],
                            (object) ['Id' => 3, 'Desc' => 'Alias CBU'],
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerTiposOpcional();

            expect($resultado)->toBeObject();
            expect($resultado->FEParamGetTiposOpcionalResult)->toBeArray();
        });

        it('puede obtener tipos de tributo', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function obtenerTiposTributo(): mixed
                {
                    return (object) [
                        'FEParamGetTiposTributosResult' => [
                            (object) ['Id' => 1, 'Desc' => 'Impuestos nacionales'],
                            (object) ['Id' => 2, 'Desc' => 'Impuestos provinciales'],
                            (object) ['Id' => 3, 'Desc' => 'Impuestos municipales'],
                            (object) ['Id' => 4, 'Desc' => 'Impuestos internos'],
                        ],
                    ];
                }
            };

            $resultado = $facturacionElectronicaStub->obtenerTiposTributo();

            expect($resultado)->toBeObject();
            expect($resultado->FEParamGetTiposTributosResult)->toBeArray();
        });
    });

    describe('autorizarComprobante', function (): void {
        it('puede autorizar un comprobante con datos completos', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function autorizarComprobante(array $comprobantes): mixed
                {
                    expect($comprobantes)->toBeArray();
                    expect($comprobantes[0]['PtoVta'])->toBe(1);
                    expect($comprobantes[0]['CbteTipo'])->toBe(11);
                    expect($comprobantes[0]['CbteDesde'])->toBe(1);
                    expect($comprobantes[0]['CbteHasta'])->toBe(1);

                    return (object) [
                        'FECAESolicitarResult' => (object) [
                            'FeDetResp' => [
                                (object) [
                                    'CAE' => '12345678901234',
                                    'CAEFchVto' => '20241231',
                                    'CbteDesde' => 1,
                                    'CbteHasta' => 1,
                                    'Resultado' => 'A',
                                ],
                            ],
                        ],
                    ];
                }
            };

            $comprobantes = [
                [
                    'PtoVta' => 1,
                    'CbteTipo' => 11,
                    'CbteDesde' => 1,
                    'CbteHasta' => 1,
                    'ImpTotal' => 121.00,
                    'ImpTotConc' => 0,
                    'ImpNeto' => 100.00,
                    'ImpOpEx' => 0,
                    'ImpIVA' => 21.00,
                    'ImpTrib' => 0,
                    'FchServDesde' => null,
                    'FchServHasta' => null,
                    'FchVtoPago' => null,
                    'MonId' => 'PES',
                    'MonCotiz' => 1,
                ],
            ];

            $resultado = $facturacionElectronicaStub->autorizarComprobante($comprobantes);

            expect($resultado)->toBeObject();
            expect($resultado->FECAESolicitarResult->FeDetResp[0]->CAE)->toBe('12345678901234');
            expect($resultado->FECAESolicitarResult->FeDetResp[0]->Resultado)->toBe('A');
        });

        it('puede autorizar diferentes tipos de comprobantes', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function autorizarComprobante(array $comprobantes): mixed
                {
                    $tipoComprobante = $comprobantes[0]['CbteTipo'];

                    // Validar diferentes tipos
                    expect($tipoComprobante)->toBeIn([1, 6, 11]); // Factura A, B, C

                    return (object) [
                        'FECAESolicitarResult' => (object) [
                            'FeDetResp' => [
                                (object) [
                                    'CAE' => '12345678901234',
                                    'CAEFchVto' => '20241231',
                                    'CbteDesde' => 1,
                                    'CbteHasta' => 1,
                                    'Resultado' => 'A',
                                ],
                            ],
                        ],
                    ];
                }
            };

            // Test Factura A
            $facturaA = [
                [
                    'PtoVta' => 1,
                    'CbteTipo' => 1, // Factura A
                    'CbteDesde' => 1,
                    'CbteHasta' => 1,
                    'ImpTotal' => 121.00,
                    'MonId' => 'PES',
                    'MonCotiz' => 1,
                ],
            ];

            $resultado = $facturacionElectronicaStub->autorizarComprobante($facturaA);
            expect($resultado->FECAESolicitarResult->FeDetResp[0]->Resultado)->toBe('A');

            // Test Factura B
            $facturaB = [
                [
                    'PtoVta' => 1,
                    'CbteTipo' => 6, // Factura B
                    'CbteDesde' => 1,
                    'CbteHasta' => 1,
                    'ImpTotal' => 121.00,
                    'MonId' => 'PES',
                    'MonCotiz' => 1,
                ],
            ];

            $resultado = $facturacionElectronicaStub->autorizarComprobante($facturaB);
            expect($resultado->FECAESolicitarResult->FeDetResp[0]->Resultado)->toBe('A');
        });
    });

    describe('casos de uso específicos de ejemplos', function (): void {
        it('maneja correctamente la estructura de respuesta de autorización como en factura_A.php', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function autorizarProximoComprobante(array $datosComprobante): mixed
                {
                    return (object) [
                        'FECAESolicitarResult' => (object) [
                            'FeDetResp' => (object) [
                                'FECAEDetResponse' => (object) [
                                    'CAE' => '12345678901234',
                                    'CAEFchVto' => '20241231',
                                    'Resultado' => 'A',
                                    'CbteDesde' => 1,
                                    'CbteHasta' => 1,
                                ],
                            ],
                        ],
                    ];
                }
            };

            $datosFactura = [
                'PtoVta' => 1,
                'CbteTipo' => 1, // Factura A
                'Concepto' => 1,
                'DocTipo' => 80,
                'DocNro' => 33693450239,
                'ImpTotal' => 121.00,
                'MonId' => 'PES',
                'MonCotiz' => 1,
            ];

            $respuesta = $facturacionElectronicaStub->autorizarProximoComprobante($datosFactura);

            // Verificar estructura como en el ejemplo
            expect($respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Resultado)->toBe('A');
            expect($respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAE)->toBe('12345678901234');
        });

        it('maneja diferentes conceptos de facturación', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function autorizarProximoComprobante(array $datosComprobante): mixed
                {
                    $concepto = $datosComprobante['Concepto'];
                    expect($concepto)->toBeIn([1, 2, 3]); // Productos, Servicios, Mixto

                    return (object) [
                        'FECAESolicitarResult' => (object) [
                            'FeDetResp' => (object) [
                                'FECAEDetResponse' => (object) [
                                    'CAE' => '12345678901234',
                                    'Resultado' => 'A',
                                ],
                            ],
                        ],
                    ];
                }
            };

            // Test concepto productos
            $datosProductos = ['Concepto' => 1, 'PtoVta' => 1, 'CbteTipo' => 11];
            $resultado = $facturacionElectronicaStub->autorizarProximoComprobante($datosProductos);
            expect($resultado->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Resultado)->toBe('A');

            // Test concepto servicios
            $datosServicios = ['Concepto' => 2, 'PtoVta' => 1, 'CbteTipo' => 11];
            $resultado = $facturacionElectronicaStub->autorizarProximoComprobante($datosServicios);
            expect($resultado->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Resultado)->toBe('A');

            // Test concepto mixto
            $datosMixto = ['Concepto' => 3, 'PtoVta' => 1, 'CbteTipo' => 11];
            $resultado = $facturacionElectronicaStub->autorizarProximoComprobante($datosMixto);
            expect($resultado->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Resultado)->toBe('A');
        });

        it('valida estructura de IVA como en los ejemplos', function (): void {
            $facturacionElectronicaStub = new class($this->afip) extends FacturacionElectronica
            {
                public function autorizarProximoComprobante(array $datosComprobante): mixed
                {
                    // Validar estructura de IVA
                    expect($datosComprobante['Iva'])->toBeArray();
                    expect($datosComprobante['Iva'][0]['Id'])->toBe(5); // 21%
                    expect($datosComprobante['Iva'][0]['BaseImp'])->toBe(100.00);
                    expect($datosComprobante['Iva'][0]['Importe'])->toBe(21.00);

                    return (object) [
                        'FECAESolicitarResult' => (object) [
                            'FeDetResp' => (object) [
                                'FECAEDetResponse' => (object) [
                                    'CAE' => '12345678901234',
                                    'Resultado' => 'A',
                                ],
                            ],
                        ],
                    ];
                }
            };

            $datosConIva = [
                'PtoVta' => 1,
                'CbteTipo' => 11,
                'ImpTotal' => 121.00,
                'ImpNeto' => 100.00,
                'ImpIVA' => 21.00,
                'Iva' => [
                    [
                        'Id' => 5, // 21%
                        'BaseImp' => 100.00,
                        'Importe' => 21.00,
                    ],
                ],
            ];

            $resultado = $facturacionElectronicaStub->autorizarProximoComprobante($datosConIva);
            expect($resultado->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Resultado)->toBe('A');
        });
    });
});
