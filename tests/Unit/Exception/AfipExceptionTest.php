<?php

declare(strict_types=1);

use PhpAfipWs\Exception\AfipException;

describe('AfipException', function (): void {
    it('puede ser instanciada con parámetros básicos', function (): void {
        $mensaje = 'Error de prueba';
        $codigo = 123;

        $exception = new AfipException($mensaje, $codigo);

        expect($exception->getMessage())->toBe($mensaje);
        expect($exception->getCode())->toBe($codigo);
        expect($exception->obtenerTipoError())->toBe('general');
        expect($exception->obtenerContexto())->toBe([]);
    });

    it('puede ser instanciada con tipo de error personalizado', function (): void {
        $tipoError = 'custom_error';
        $contexto = ['key' => 'value'];

        $exception = new AfipException('mensaje', 0, null, $tipoError, $contexto);

        expect($exception->obtenerTipoError())->toBe($tipoError);
        expect($exception->obtenerContexto())->toBe($contexto);
    });

    it('genera un ID único para cada instancia', function (): void {
        $exception1 = new AfipException('mensaje 1');
        $exception2 = new AfipException('mensaje 2');

        expect($exception1->obtenerId())->not->toBe($exception2->obtenerId());
        expect($exception1->obtenerId())->toStartWith('general_');
        expect($exception2->obtenerId())->toStartWith('general_');
    });

    it('incluye marca de tiempo', function (): void {
        $antes = new DateTimeImmutable;
        $exception = new AfipException('mensaje');
        $despues = new DateTimeImmutable;

        $marcaTiempo = $exception->obtenerMarcaTiempo();

        expect($marcaTiempo)->toBeInstanceOf(DateTimeImmutable::class);
        expect($marcaTiempo >= $antes)->toBeTrue();
        expect($marcaTiempo <= $despues)->toBeTrue();
    });

    it('puede encadenar excepciones', function (): void {
        $excepcionAnterior = new Exception('Excepción anterior');
        $exception = new AfipException('Nueva excepción', 0, $excepcionAnterior);

        expect($exception->getPrevious())->toBe($excepcionAnterior);
    });
});
