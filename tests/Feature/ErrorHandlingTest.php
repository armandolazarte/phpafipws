<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Enums\CodigosError;
use PhpAfipWs\Exception\ArchivoException;
use PhpAfipWs\Exception\ConfiguracionException;
use PhpAfipWs\Exception\ValidacionException;

describe('Error Handling', function (): void {
    beforeEach(function (): void {
        $this->tempDir = $this->createTempDirectory();
        $this->taDir = $this->createTempDirectory();
    });

    afterEach(function (): void {
        $this->cleanupTempDirectory($this->tempDir);
        $this->cleanupTempDirectory($this->taDir);
    });

    describe('configuración inválida', function (): void {
        it('lanza ConfiguracionException con código correcto para CUIT faltante', function (): void {
            $opciones = [
                'carpeta_recursos' => $this->tempDir,
                'carpeta_ta' => $this->taDir,
            ];

            try {
                new Afip($opciones);
                expect(false)->toBeTrue('Debería haber lanzado una excepción');
            } catch (ConfiguracionException $configuracionException) {
                expect($configuracionException->getCode())->toBe(CodigosError::CONFIGURACION_CAMPO_REQUERIDO->value);
                expect($configuracionException->obtenerCampoConfiguracion())->toBe('cuit');
                expect($configuracionException->obtenerTipoError())->toBe('configuracion');
            }
        });

        it('lanza ConfiguracionException para carpeta_recursos faltante', function (): void {
            $opciones = [
                'cuit' => 20123456789,
                'carpeta_ta' => $this->taDir,
            ];

            try {
                new Afip($opciones);
                expect(false)->toBeTrue('Debería haber lanzado una excepción');
            } catch (ConfiguracionException $configuracionException) {
                expect($configuracionException->getCode())->toBe(CodigosError::CONFIGURACION_CAMPO_REQUERIDO->value);
                expect($configuracionException->obtenerCampoConfiguracion())->toBe('carpeta_recursos');
            }
        });

        it('lanza ConfiguracionException para carpeta_ta faltante', function (): void {
            $opciones = [
                'cuit' => 20123456789,
                'carpeta_recursos' => $this->tempDir,
            ];

            try {
                new Afip($opciones);
                expect(false)->toBeTrue('Debería haber lanzado una excepción');
            } catch (ConfiguracionException $configuracionException) {
                expect($configuracionException->getCode())->toBe(CodigosError::CONFIGURACION_CAMPO_REQUERIDO->value);
                expect($configuracionException->obtenerCampoConfiguracion())->toBe('carpeta_ta');
            }
        });
    });

    describe('validación de datos', function (): void {
        it('lanza ValidacionException para CUIT no numérico', function (): void {
            $opciones = [
                'cuit' => 'invalid_cuit',
                'carpeta_recursos' => $this->tempDir,
                'carpeta_ta' => $this->taDir,
            ];

            try {
                new Afip($opciones);
                expect(false)->toBeTrue('Debería haber lanzado una excepción');
            } catch (ValidacionException $validacionException) {
                expect($validacionException->getCode())->toBe(CodigosError::VALIDACION_CUIT_INVALIDO->value);
                expect($validacionException->obtenerCampo())->toBe('cuit');
                expect($validacionException->obtenerValor())->toBe('invalid_cuit');
                expect($validacionException->obtenerRegla())->toBe('numeric');
                expect($validacionException->obtenerTipoError())->toBe('validacion');
            }
        });

        it('incluye contexto detallado en ValidacionException', function (): void {
            $opciones = [
                'cuit' => 'abc123',
                'carpeta_recursos' => $this->tempDir,
                'carpeta_ta' => $this->taDir,
            ];

            try {
                new Afip($opciones);
                expect(false)->toBeTrue('Debería haber lanzado una excepción');
            } catch (ValidacionException $validacionException) {
                $contexto = $validacionException->obtenerContexto();

                expect($contexto)->toHaveKey('campo');
                expect($contexto)->toHaveKey('valor');
                expect($contexto)->toHaveKey('regla');
                expect($contexto['campo'])->toBe('cuit');
                expect($contexto['valor'])->toBe('abc123');
                expect($contexto['regla'])->toBe('numeric');
            }
        });
    });

    describe('errores de archivos', function (): void {
        it('lanza ArchivoException para certificado faltante', function (): void {
            $this->createMockCertificateFiles($this->tempDir);
            unlink($this->tempDir.DIRECTORY_SEPARATOR.'cert');

            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);

            try {
                new Afip($opciones);
                expect(false)->toBeTrue('Debería haber lanzado una excepción');
            } catch (ArchivoException $archivoException) {
                expect($archivoException->getCode())->toBe(CodigosError::ARCHIVO_NO_ENCONTRADO->value);
                expect($archivoException->getMessage())->toContain('Archivo de certificado no encontrado');
                expect($archivoException->obtenerTipoError())->toBe('archivo');
            }
        });

        it('lanza ArchivoException para clave privada faltante', function (): void {
            $this->createMockCertificateFiles($this->tempDir);
            unlink($this->tempDir.DIRECTORY_SEPARATOR.'key');

            $opciones = $this->getBasicAfipOptions($this->tempDir, $this->taDir);

            try {
                new Afip($opciones);
                expect(false)->toBeTrue('Debería haber lanzado una excepción');
            } catch (ArchivoException $archivoException) {
                expect($archivoException->getCode())->toBe(CodigosError::ARCHIVO_NO_ENCONTRADO->value);
                expect($archivoException->getMessage())->toContain('Archivo de clave privada no encontrado');
            }
        });
    });

    describe('información contextual de excepciones', function (): void {
        it('incluye marca de tiempo en todas las excepciones', function (): void {
            $antes = new DateTimeImmutable;

            try {
                new Afip(['cuit' => 'invalid']);
            } catch (ConfiguracionException $configuracionException) {
                $marcaTiempo = $configuracionException->obtenerMarcaTiempo();
                $despues = new DateTimeImmutable;

                expect($marcaTiempo)->toBeInstanceOf(DateTimeImmutable::class);
                expect($marcaTiempo >= $antes)->toBeTrue();
                expect($marcaTiempo <= $despues)->toBeTrue();
            }
        });

        it('genera IDs únicos para cada excepción', function (): void {
            $ids = [];

            for ($i = 0; $i < 3; $i++) {
                try {
                    new Afip(['cuit' => 'invalid'.$i]);
                } catch (ConfiguracionException $e) {
                    $ids[] = $e->obtenerId();
                }
            }

            expect(count($ids))->toBe(3);
            expect(count(array_unique($ids)))->toBe(3); // Todos los IDs deben ser únicos

            foreach ($ids as $id) {
                expect($id)->toStartWith('configuracion_');
            }
        });
    });
});
