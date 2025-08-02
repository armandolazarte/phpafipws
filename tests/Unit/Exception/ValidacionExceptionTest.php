<?php

declare(strict_types=1);

use PhpAfipWs\Enums\CodigosError;
use PhpAfipWs\Exception\ValidacionException;

describe('ValidacionException', function (): void {
    it('puede ser instanciada con parámetros básicos', function (): void {
        $mensaje = 'Error de validación';
        $campo = 'cuit';
        $valor = 'invalid_cuit';
        $regla = 'numeric';

        $exception = new ValidacionException($mensaje, $campo, $valor, $regla);

        expect($exception->getMessage())->toBe($mensaje);
        expect($exception->obtenerCampo())->toBe($campo);
        expect($exception->obtenerValor())->toBe($valor);
        expect($exception->obtenerRegla())->toBe($regla);
        expect($exception->obtenerTipoError())->toBe('validacion');
        expect($exception->getCode())->toBe(CodigosError::VALIDACION_GENERAL->value);
    });

    it('incluye información contextual', function (): void {
        $campo = 'email';
        $valor = 'invalid-email';
        $regla = 'email';

        $exception = new ValidacionException('mensaje', $campo, $valor, $regla);
        $contexto = $exception->obtenerContexto();

        expect($contexto)->toHaveKey('campo', $campo);
        expect($contexto)->toHaveKey('valor', $valor);
        expect($contexto)->toHaveKey('regla', $regla);
    });

    it('puede manejar diferentes tipos de valores', function (): void {
        $valorNumerico = 123;
        $valorArray = ['test'];
        $valorBoolean = false;

        $exception1 = new ValidacionException('mensaje', 'campo', $valorNumerico, 'regla');
        $exception2 = new ValidacionException('mensaje', 'campo', $valorArray, 'regla');
        $exception3 = new ValidacionException('mensaje', 'campo', $valorBoolean, 'regla');

        expect($exception1->obtenerValor())->toBe($valorNumerico);
        expect($exception2->obtenerValor())->toBe($valorArray);
        expect($exception3->obtenerValor())->toBe($valorBoolean);
    });

    it('puede incluir contexto adicional', function (): void {
        $contextoAdicional = ['expected_format' => 'XX-XXXXXXXX-X'];

        $exception = new ValidacionException(
            'mensaje',
            'cuit',
            'invalid',
            'format',
            CodigosError::VALIDACION_GENERAL->value,
            null,
            $contextoAdicional
        );

        $contexto = $exception->obtenerContexto();
        expect($contexto)->toHaveKey('expected_format', 'XX-XXXXXXXX-X');
        expect($contexto)->toHaveKey('campo');
        expect($contexto)->toHaveKey('valor');
        expect($contexto)->toHaveKey('regla');
    });
});
