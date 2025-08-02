<?php

declare(strict_types=1);

use PhpAfipWs\Enums\CodigosError;
use PhpAfipWs\Exception\ConfiguracionException;

describe('ConfiguracionException', function (): void {
    it('puede ser instanciada con parámetros básicos', function (): void {
        $mensaje = 'Error de configuración';
        $campo = 'cuit';
        $valor = 'invalid_cuit';

        $exception = new ConfiguracionException($mensaje, $campo, $valor);

        expect($exception->getMessage())->toBe($mensaje);
        expect($exception->obtenerCampoConfiguracion())->toBe($campo);
        expect($exception->obtenerValorProporcionado())->toBe($valor);
        expect($exception->obtenerTipoError())->toBe('configuracion');
        expect($exception->getCode())->toBe(CodigosError::CONFIGURACION_GENERAL->value);
    });

    it('incluye información contextual', function (): void {
        $campo = 'carpeta_recursos';
        $valor = '/path/invalid';

        $exception = new ConfiguracionException('mensaje', $campo, $valor);
        $contexto = $exception->obtenerContexto();

        expect($contexto)->toHaveKey('campo_configuracion', $campo);
        expect($contexto)->toHaveKey('valor_proporcionado', $valor);
    });

    it('puede manejar valores null', function (): void {
        $exception = new ConfiguracionException('mensaje', 'campo', null);

        expect($exception->obtenerValorProporcionado())->toBeNull();
    });

    it('puede incluir contexto adicional', function (): void {
        $contextoAdicional = ['extra' => 'info'];

        $exception = new ConfiguracionException(
            'mensaje',
            'campo',
            'valor',
            CodigosError::CONFIGURACION_GENERAL->value,
            null,
            $contextoAdicional
        );

        $contexto = $exception->obtenerContexto();
        expect($contexto)->toHaveKey('extra', 'info');
        expect($contexto)->toHaveKey('campo_configuracion');
        expect($contexto)->toHaveKey('valor_proporcionado');
    });
});
