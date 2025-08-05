<?php

declare(strict_types=1);

require_once __DIR__.'/../../vendor/autoload.php';

use PhpAfipWs\Authorization\GeneradorCertificados;
use PhpAfipWs\Exception\ValidacionException;

try {
    // Crear un DN para AFIP
    $dn = GeneradorCertificados::crearInformacionDN(
        cuit: '12345678901', // CUIT de 11 dígitos
        nombreOrganizacion: 'Mi Empresa S.A.',
        nombreComun: 'Mi Empresa - WS',
        provincia: 'Córdoba',
        localidad: 'Córdoba',
        pais: 'AR'
    );

    print_r($dn);
} catch (ValidacionException $validacionException) {
    echo 'Error: '.$validacionException->getMessage()."\n";
    echo 'Código: '.$validacionException->getCode()."\n";
}
