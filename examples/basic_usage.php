<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;

require_once __DIR__.'/../vendor/autoload.php';

try {
    // Configuración para testing
    $afip = new Afip([
        'cuit' => 20294192345, // Reemplaza con tu CUIT
        'modo_produccion' => false,
        'ruta_certificado' => 'certificado.crt',
        'ruta_clave' => 'clave_privada.key',
        'contrasena_clave' => 'tu_passphrase', // opcional
        'carpeta_recursos' => __DIR__.'/resources/',
        'carpeta_ta' => __DIR__.'/ta/',
    ]);

    echo 'AFIP Version: '.$afip->getVersion()."\n";
    echo 'CUIT: '.$afip->getCuit()."\n";
    echo 'Modo Produccion: '.($afip->esProduccion() ? 'Yes' : 'No')."\n\n";

    // Ejemplo de uso de Electronic Billing
    $facturaElectronica = $afip->FacturacionElectronica;

    // Verificar estado del servidor
    $estadoServidor = $facturaElectronica->getEstadoServidor();
    echo "Estado del Servidor: \n";
    print_r($estadoServidor);

    // Obtener tipos de comprobantes
    $tiposComprobantes = $facturaElectronica->getTiposComprobantes();
    echo "\nTipos de Comprobantes: \n";
    print_r($tiposComprobantes);

    // Obtener último número de comprobante
    // Factura A (Tipo 1)
    // Factura B (Tipo 6)
    // Factura C (Tipo 11)
    // Factura M (Tipo 19)
    // Recibo (Tipo 99)
    // Nota de Crédito A (Tipo 2)
    // Nota de Crédito B (Tipo 7)
    // Nota de Crédito C (Tipo 12)
    // Nota de Crédito M (Tipo 20)
    // Nota de Débito A (Tipo 3)
    // Nota de Débito B (Tipo 8)
    // Nota de Débito C (Tipo 13)
    // Nota de Débito M (Tipo 21)
    $ultimoComprobante = $facturaElectronica->getUltimoComprobante(1, 11);
    echo "\nÚltimo Comprobante: \n";
    print_r($ultimoComprobante);
} catch (AfipException $e) {
    echo 'AFIP Error: '.$e->getMessage()."\n";
    echo 'Codigo de Error: '.$e->getCode()."\n";
} catch (Exception $e) {
    echo 'Error General: '.$e->getMessage()."\n";
}
