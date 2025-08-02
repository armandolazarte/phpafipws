<?php

declare(strict_types=1);

use PhpAfipWs\Afip;
use PhpAfipWs\Exception\AfipException;
use PhpAfipWs\Exception\AutenticacionException;
use PhpAfipWs\Exception\ConfiguracionException;
use PhpAfipWs\Exception\SoapException;
use PhpAfipWs\Exception\ValidacionException;

require_once __DIR__.'/../vendor/autoload.php';

try {
    // Configuración para testing
    $afip = new Afip([
        'cuit' => 20294192345, // Reemplaza con tu CUIT
        'modo_produccion' => false,
        'nombre_certificado' => 'certificado.crt',
        'nombre_clave' => 'clave_privada.key',
        'contrasena_clave' => 'tu_passphrase', // opcional
        'carpeta_recursos' => __DIR__.'/resources/',
        'carpeta_ta' => __DIR__.'/ta/',
    ]);

    echo 'Versión del SDK de AFIP: '.$afip->obtenerVersionSdk()."\n";
    echo 'CUIT: '.$afip->obtenerCuit()."\n";
    echo 'Modo Producción: '.($afip->esModoProduccion() ? 'Sí' : 'No')."\n\n";

    // Ejemplo de uso de Electronic Billing
    $facturacionElectronica = $afip->FacturacionElectronica;

    // Obtener tipos de documentos
    $tiposDocumento = $facturacionElectronica->obtenerTiposDocumento();
    echo "\nTipos de Documento: \n";
    print_r($tiposDocumento);

} catch (ConfiguracionException $e) {
    echo "❌ Error de Configuración:\n";
    echo sprintf('   Mensaje: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Campo problemático: %s%s', $e->obtenerCampoConfiguracion(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
} catch (ValidacionException $e) {
    echo "❌ Error de Validación:\n";
    echo sprintf('   Mensaje: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Campo: %s%s', $e->obtenerCampo(), PHP_EOL);
    echo sprintf('   Valor: %s%s', $e->obtenerValor(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
} catch (AutenticacionException $e) {
    echo "❌ Error de Autenticación:\n";
    echo sprintf('   Mensaje: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Servicio: %s%s', $e->obtenerServicio(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
} catch (SoapException $e) {
    echo "❌ Error SOAP:\n";
    echo sprintf('   Mensaje: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código SOAP: %s%s', $e->obtenerCodigoFalloSoap(), PHP_EOL);
    echo sprintf('   Operación: %s%s', $e->obtenerOperacion(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
} catch (AfipException $e) {
    echo "❌ Error General del SDK:\n";
    echo sprintf('   Mensaje: %s%s', $e->getMessage(), PHP_EOL);
    echo sprintf('   Código: %d%s', $e->getCode(), PHP_EOL);
} catch (Exception $e) {
    echo "❌ Error General:\n";
    echo sprintf('   Mensaje: %s%s', $e->getMessage(), PHP_EOL);
}
