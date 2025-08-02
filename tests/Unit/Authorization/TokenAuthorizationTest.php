<?php

declare(strict_types=1);

use PhpAfipWs\Authorization\TokenAuthorization;

describe('TokenAuthorization', function (): void {
    it('puede ser instanciado con token y firma', function (): void {
        $token = 'test_token_123';
        $sign = 'test_signature_456';

        $tokenAuth = new TokenAuthorization($token, $sign);

        expect($tokenAuth->obtenerToken())->toBe($token);
        expect($tokenAuth->obtenerFirma())->toBe($sign);
    });

    it('maneja tokens y firmas vacías', function (): void {
        $tokenAuth = new TokenAuthorization('', '');

        expect($tokenAuth->obtenerToken())->toBe('');
        expect($tokenAuth->obtenerFirma())->toBe('');
    });

    it('maneja tokens y firmas con caracteres especiales', function (): void {
        $token = 'token_with_special_chars_!@#$%^&*()';
        $sign = 'signature_with_special_chars_!@#$%^&*()';

        $tokenAuth = new TokenAuthorization($token, $sign);

        expect($tokenAuth->obtenerToken())->toBe($token);
        expect($tokenAuth->obtenerFirma())->toBe($sign);
    });
});
