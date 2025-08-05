<?php

declare(strict_types=1);

use PhpAfipWs\Authorization\GeneradorCertificados;
use PhpAfipWs\Enums\CodigosError;
use PhpAfipWs\Exception\ValidacionException;

describe('GeneradorCertificados - Tests Adicionales', function (): void {
    describe('validación de parámetros', function (): void {
        it('valida que el CUIT tenga exactamente 11 dígitos', function (): void {
            $cuitCorto = '1234567890'; // 10 dígitos
            $cuitLargo = '123456789012'; // 12 dígitos

            expect(fn (): array => GeneradorCertificados::crearInformacionDN($cuitCorto, 'Empresa', 'empresa'))
                ->toThrow(ValidacionException::class);

            expect(fn (): array => GeneradorCertificados::crearInformacionDN($cuitLargo, 'Empresa', 'empresa'))
                ->toThrow(ValidacionException::class);
        });

        it('valida que el CUIT contenga solo números', function (): void {
            $cuitConLetras = '2012345678A';
            $cuitConEspacios = '20 123456789';
            $cuitConGuiones = '20-12345678-9';

            expect(fn (): array => GeneradorCertificados::crearInformacionDN($cuitConLetras, 'Empresa', 'empresa'))
                ->toThrow(ValidacionException::class);

            expect(fn (): array => GeneradorCertificados::crearInformacionDN($cuitConEspacios, 'Empresa', 'empresa'))
                ->toThrow(ValidacionException::class);

            expect(fn (): array => GeneradorCertificados::crearInformacionDN($cuitConGuiones, 'Empresa', 'empresa'))
                ->toThrow(ValidacionException::class);
        });
    });

    describe('validación de DN completo', function (): void {
        it('valida todos los campos requeridos del DN', function (): void {
            $camposRequeridos = [
                'countryName',
                'stateOrProvinceName',
                'localityName',
                'organizationName',
                'commonName',
                'serialNumber',
            ];

            foreach ($camposRequeridos as $campoFaltante) {
                $dnIncompleto = [
                    'countryName' => 'AR',
                    'stateOrProvinceName' => 'Buenos Aires',
                    'localityName' => 'Ciudad Autónoma de Buenos Aires',
                    'organizationName' => 'Mi Empresa S.A.',
                    'commonName' => 'mi_empresa',
                    'serialNumber' => 'CUIT 30123456789',
                ];

                unset($dnIncompleto[$campoFaltante]);

                expect(fn (): bool => GeneradorCertificados::validarInformacionDN($dnIncompleto))
                    ->toThrow(ValidacionException::class)
                    ->and(fn (): bool => GeneradorCertificados::validarInformacionDN($dnIncompleto))
                    ->toThrow(ValidacionException::class, sprintf('El campo "%s" es requerido en el Distinguished Name', $campoFaltante));
            }
        });

        it('valida campos vacíos en el DN', function (): void {
            $dnConCampoVacio = [
                'countryName' => 'AR',
                'stateOrProvinceName' => '', // Campo vacío
                'localityName' => 'Ciudad Autónoma de Buenos Aires',
                'organizationName' => 'Mi Empresa S.A.',
                'commonName' => 'mi_empresa',
                'serialNumber' => 'CUIT 30123456789',
            ];

            expect(fn (): bool => GeneradorCertificados::validarInformacionDN($dnConCampoVacio))
                ->toThrow(ValidacionException::class, 'El campo "stateOrProvinceName" es requerido en el Distinguished Name');
        });
    });

    describe('manejo de errores específicos', function (): void {
        it('lanza excepción con código correcto para DN incompleto', function (): void {
            $dnIncompleto = [
                'countryName' => 'AR',
                // Faltan campos requeridos
            ];

            try {
                GeneradorCertificados::validarInformacionDN($dnIncompleto);
            } catch (ValidacionException $validacionException) {
                expect($validacionException->getCode())->toBe(CodigosError::VALIDACION_DN_INCOMPLETO->value);
                expect($validacionException->obtenerCampo())->toBe('distinguished_name');
                expect($validacionException->obtenerValor())->toBe($dnIncompleto);
                expect($validacionException->obtenerRegla())->toBe('required');
            }
        });

        it('lanza excepción con código correcto para formato de CUIT inválido', function (): void {
            $dnConCuitMalFormateado = [
                'countryName' => 'AR',
                'stateOrProvinceName' => 'Buenos Aires',
                'localityName' => 'Ciudad Autónoma de Buenos Aires',
                'organizationName' => 'Mi Empresa S.A.',
                'commonName' => 'mi_empresa',
                'serialNumber' => 'CUIT_SIN_FORMATO_CORRECTO',
            ];

            try {
                GeneradorCertificados::validarInformacionDN($dnConCuitMalFormateado);
            } catch (ValidacionException $validacionException) {
                expect($validacionException->getCode())->toBe(CodigosError::VALIDACION_FORMATO_CUIT->value);
                expect($validacionException->obtenerCampo())->toBe('serialNumber');
                expect($validacionException->obtenerValor())->toBe('CUIT_SIN_FORMATO_CORRECTO');
                expect($validacionException->obtenerRegla())->toBe('format:CUIT_XXXXXXX');
            }
        });

        it('lanza excepción con código correcto para CUIT inválido en crearInformacionDN', function (): void {
            try {
                GeneradorCertificados::crearInformacionDN('CUIT_INVALIDO', 'Empresa', 'empresa');
            } catch (ValidacionException $validacionException) {
                expect($validacionException->getCode())->toBe(CodigosError::VALIDACION_CUIT_INVALIDO->value);
                expect($validacionException->obtenerCampo())->toBe('cuit');
                expect($validacionException->obtenerValor())->toBe('CUIT_INVALIDO');
                expect($validacionException->obtenerRegla())->toBe('numeric|size:11');
            }
        });
    });

    describe('casos límite', function (): void {
        it('maneja correctamente strings vacíos en parámetros opcionales', function (): void {
            // Frase secreta vacía debería ser tratada como null
            $clavePrivada = GeneradorCertificados::generarClavePrivada(2048, '');

            expect($clavePrivada)
                ->toBeString()
                ->toContain('-----BEGIN RSA PRIVATE KEY-----')
                ->not->toContain('Proc-Type: 4,ENCRYPTED');
        });

        it('maneja correctamente frase secreta con valor "0"', function (): void {
            // Frase secreta "0" debería ser tratada como null
            $clavePrivada = GeneradorCertificados::generarClavePrivada(2048, '0');

            expect($clavePrivada)
                ->toBeString()
                ->toContain('-----BEGIN RSA PRIVATE KEY-----')
                ->not->toContain('Proc-Type: 4,ENCRYPTED');
        });

        it('acepta CUIT válidos con diferentes prefijos', function (): void {
            $cuits = [
                '20123456789', // Persona física
                '30123456789', // Persona jurídica
                '27123456789', // Persona física femenina
                '23123456789', // Persona física masculina
            ];

            foreach ($cuits as $cuit) {
                $dn = GeneradorCertificados::crearInformacionDN($cuit, 'Empresa', 'empresa');
                expect($dn['serialNumber'])->toBe('CUIT '.$cuit);
            }
        });
    });

    describe('integración con archivos reales', function (): void {
        beforeEach(function (): void {
            $this->directorioTemporal = sys_get_temp_dir().'/phpafipws_test_'.uniqid();
            mkdir($this->directorioTemporal, 0755, true);
        });

        afterEach(function (): void {
            // Limpiar directorio temporal
            if (is_dir($this->directorioTemporal)) {
                $archivos = glob($this->directorioTemporal.'/*');
                foreach ($archivos as $archivo) {
                    if (is_file($archivo)) {
                        unlink($archivo);
                    }
                }

                rmdir($this->directorioTemporal);
            }
        });

        it('puede guardar y cargar una clave privada completa', function (): void {
            $clavePrivada = GeneradorCertificados::generarClavePrivada(2048);
            $rutaClave = $this->directorioTemporal.'/clave_privada.key';

            // Guardar clave
            $guardadoExitoso = GeneradorCertificados::guardarArchivo($clavePrivada, $rutaClave);
            expect($guardadoExitoso)->toBeTrue();
            expect(file_exists($rutaClave))->toBeTrue();

            // Cargar clave
            $claveCargada = GeneradorCertificados::cargarArchivo($rutaClave);
            expect($claveCargada)->toBe($clavePrivada);

            // Verificar que la clave cargada funciona para generar CSR
            $informacionDn = GeneradorCertificados::crearInformacionDN(
                '30123456789',
                'Empresa de Prueba',
                'empresa_prueba'
            );

            $csr = GeneradorCertificados::generarCSR($claveCargada, $informacionDn);
            expect($csr)->toBeString()->toContain('-----BEGIN CERTIFICATE REQUEST-----');
        });

        it('puede guardar y cargar un CSR completo', function (): void {
            $clavePrivada = GeneradorCertificados::generarClavePrivada(2048);
            $informacionDn = GeneradorCertificados::crearInformacionDN(
                '30123456789',
                'Empresa de Prueba',
                'empresa_prueba'
            );

            $csr = GeneradorCertificados::generarCSR($clavePrivada, $informacionDn);
            $rutaCsr = $this->directorioTemporal.'/solicitud.csr';

            // Guardar CSR
            $guardadoExitoso = GeneradorCertificados::guardarArchivo($csr, $rutaCsr);
            expect($guardadoExitoso)->toBeTrue();
            expect(file_exists($rutaCsr))->toBeTrue();

            // Cargar CSR
            $csrCargado = GeneradorCertificados::cargarArchivo($rutaCsr);
            expect($csrCargado)->toBe($csr);

            // Verificar que el CSR cargado se puede procesar
            $informacionExtraida = GeneradorCertificados::extraerInformacionCSR($rutaCsr);
            expect($informacionExtraida)
                ->toBeArray()
                ->toHaveKey('organizationName', 'Empresa de Prueba')
                ->toHaveKey('serialNumber', 'CUIT 30123456789');
        });
    });

    describe('validación de formatos', function (): void {
        it('valida que el serialNumber tenga el formato exacto CUIT + espacio + 11 dígitos', function (): void {
            $formatosInvalidos = [
                'CUIT30123456789',      // Sin espacio
                'cuit 30123456789',     // Minúsculas
                'CUIT  30123456789',    // Doble espacio
                'CUIT 3012345678',      // 10 dígitos
                'CUIT 301234567890',    // 12 dígitos
                'CUIL 30123456789',     // CUIL en lugar de CUIT
                '30123456789',          // Sin prefijo CUIT
            ];

            foreach ($formatosInvalidos as $formatoInvalido) {
                $dnConFormatoInvalido = [
                    'countryName' => 'AR',
                    'stateOrProvinceName' => 'Buenos Aires',
                    'localityName' => 'Ciudad Autónoma de Buenos Aires',
                    'organizationName' => 'Mi Empresa S.A.',
                    'commonName' => 'mi_empresa',
                    'serialNumber' => $formatoInvalido,
                ];

                expect(fn (): bool => GeneradorCertificados::validarInformacionDN($dnConFormatoInvalido))
                    ->toThrow(ValidacionException::class, 'El serialNumber debe tener el formato "CUIT XXXXXXXXXXX"');
            }
        });

        it('acepta el formato correcto de serialNumber', function (): void {
            $formatosValidos = [
                'CUIT 20123456789',
                'CUIT 30987654321',
                'CUIT 27555666777',
                'CUIT 23111222333',
            ];

            foreach ($formatosValidos as $formatoValido) {
                $dnConFormatoValido = [
                    'countryName' => 'AR',
                    'stateOrProvinceName' => 'Buenos Aires',
                    'localityName' => 'Ciudad Autónoma de Buenos Aires',
                    'organizationName' => 'Mi Empresa S.A.',
                    'commonName' => 'mi_empresa',
                    'serialNumber' => $formatoValido,
                ];

                $resultado = GeneradorCertificados::validarInformacionDN($dnConFormatoValido);
                expect($resultado)->toBeTrue();
            }
        });
    });
});
