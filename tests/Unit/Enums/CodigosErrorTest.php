<?php

declare(strict_types=1);

use PhpAfipWs\Enums\CodigosError;

describe('CodigosError', function (): void {
    it('tiene códigos de error de configuración', function (): void {
        expect(CodigosError::CONFIGURACION_GENERAL->value)->toBe(1000);
        expect(CodigosError::CONFIGURACION_CAMPO_REQUERIDO->value)->toBe(1001);
        expect(CodigosError::CONFIGURACION_VALOR_INVALIDO->value)->toBe(1002);
    });

    it('tiene códigos de error de validación', function (): void {
        expect(CodigosError::VALIDACION_GENERAL->value)->toBe(2000);
        expect(CodigosError::VALIDACION_CUIT_INVALIDO->value)->toBe(2001);
        expect(CodigosError::VALIDACION_FECHA_INVALIDA->value)->toBe(2002);
    });

    it('tiene códigos de error de archivos', function (): void {
        expect(CodigosError::ARCHIVO_GENERAL->value)->toBe(3000);
        expect(CodigosError::ARCHIVO_NO_ENCONTRADO->value)->toBe(3001);
        expect(CodigosError::ARCHIVO_ERROR_LECTURA->value)->toBe(3002);
    });

    it('tiene códigos de error de autenticación', function (): void {
        expect(CodigosError::AUTENTICACION_GENERAL->value)->toBe(4000);
        expect(CodigosError::AUTENTICACION_TOKEN_EXPIRADO->value)->toBe(4001);
        expect(CodigosError::AUTENTICACION_TOKEN_INVALIDO->value)->toBe(4002);
    });

    it('tiene códigos de error SOAP', function (): void {
        expect(CodigosError::SOAP_GENERAL->value)->toBe(5000);
        expect(CodigosError::SOAP_FALLO_COMUNICACION->value)->toBe(5001);
        expect(CodigosError::SOAP_RESPUESTA_INVALIDA->value)->toBe(5002);
    });

    it('tiene códigos de error de servicios web', function (): void {
        expect(CodigosError::SERVICIO_WEB_GENERAL->value)->toBe(6000);
        expect(CodigosError::SERVICIO_WEB_CLASE_NO_ENCONTRADA->value)->toBe(6001);
        expect(CodigosError::SERVICIO_WEB_METODO_NO_ENCONTRADO->value)->toBe(6002);
    });

    describe('obtenerDescripcion', function (): void {
        it('devuelve descripción para errores de configuración', function (): void {
            expect(CodigosError::CONFIGURACION_GENERAL->obtenerDescripcion())
                ->toBe('Error genérico de configuración');

            expect(CodigosError::CONFIGURACION_CAMPO_REQUERIDO->obtenerDescripcion())
                ->toBe('Campo de configuración requerido faltante');
        });

        it('devuelve descripción para errores de validación', function (): void {
            expect(CodigosError::VALIDACION_CUIT_INVALIDO->obtenerDescripcion())
                ->toBe('CUIT inválido');

            expect(CodigosError::VALIDACION_FECHA_INVALIDA->obtenerDescripcion())
                ->toBe('Formato de fecha inválido');
        });

        it('devuelve descripción para errores de archivos', function (): void {
            expect(CodigosError::ARCHIVO_NO_ENCONTRADO->obtenerDescripcion())
                ->toBe('Archivo no encontrado');

            expect(CodigosError::ARCHIVO_ERROR_LECTURA->obtenerDescripcion())
                ->toBe('Error de lectura de archivo');
        });
    });

    describe('obtenerCategoria', function (): void {
        it('devuelve categoría correcta para cada tipo de error', function (): void {
            expect(CodigosError::CONFIGURACION_GENERAL->obtenerCategoria())
                ->toBe('configuracion');

            expect(CodigosError::VALIDACION_GENERAL->obtenerCategoria())
                ->toBe('validacion');

            expect(CodigosError::ARCHIVO_GENERAL->obtenerCategoria())
                ->toBe('archivo');

            expect(CodigosError::AUTENTICACION_GENERAL->obtenerCategoria())
                ->toBe('autenticacion');

            expect(CodigosError::SOAP_GENERAL->obtenerCategoria())
                ->toBe('soap');

            expect(CodigosError::SERVICIO_WEB_GENERAL->obtenerCategoria())
                ->toBe('servicio_web');
        });
    });

    it('mantiene consistencia en rangos de códigos', function (): void {
        // Configuración: 1xxx
        expect(CodigosError::CONFIGURACION_GENERAL->value)->toBeGreaterThanOrEqual(1000);
        expect(CodigosError::CONFIGURACION_ENTORNO_INVALIDO->value)->toBeLessThan(2000);

        // Validación: 2xxx
        expect(CodigosError::VALIDACION_GENERAL->value)->toBeGreaterThanOrEqual(2000);
        expect(CodigosError::VALIDACION_LONGITUD_INVALIDA->value)->toBeLessThan(3000);

        // Archivos: 3xxx
        expect(CodigosError::ARCHIVO_GENERAL->value)->toBeGreaterThanOrEqual(3000);
        expect(CodigosError::ARCHIVO_CLAVE_PRIVADA_INVALIDA->value)->toBeLessThan(4000);

        // Autenticación: 4xxx
        expect(CodigosError::AUTENTICACION_GENERAL->value)->toBeGreaterThanOrEqual(4000);
        expect(CodigosError::AUTENTICACION_ERROR_WSAA->value)->toBeLessThan(5000);

        // SOAP: 5xxx
        expect(CodigosError::SOAP_GENERAL->value)->toBeGreaterThanOrEqual(5000);
        expect(CodigosError::SOAP_OPERACION_NO_ENCONTRADA->value)->toBeLessThan(6000);

        // Servicios Web: 6xxx
        expect(CodigosError::SERVICIO_WEB_GENERAL->value)->toBeGreaterThanOrEqual(6000);
    });
});
