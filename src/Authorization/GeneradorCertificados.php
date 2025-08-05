<?php

declare(strict_types=1);

namespace PhpAfipWs\Authorization;

use PhpAfipWs\Enums\CodigosError;
use PhpAfipWs\Exception\CertificadoException;
use PhpAfipWs\Exception\ConfiguracionException;
use PhpAfipWs\Exception\ValidacionException;
use phpseclib3\Crypt\RSA;
use phpseclib3\File\X509;

/**
 * Autoservicio de Acceso a WebServices.
 *
 * Proporciona utilidades para la gestión de certificados y claves,
 * facilitando la preparación para la autenticación con los servicios web de AFIP.
 */
class GeneradorCertificados
{
    /**
     * Bits mínimos requeridos por AFIP para las claves privadas.
     */
    private const BITS_MINIMOS_AFIP = 2048;

    /**
     * Genera una clave privada RSA.
     *
     * AFIP exige que las claves privadas tengan al menos 2048 bits para garantizar
     * un nivel de seguridad adecuado en las comunicaciones.
     *
     * @param  int  $bits  Longitud de la clave privada en bits. Por defecto 2048.
     * @param  string|null  $fraseSecreta  Frase secreta para proteger la clave privada.
     * @return string La clave privada generada en formato PEM.
     *
     * @throws ConfiguracionException Si la librería phpseclib no está instalada.
     * @throws ValidacionException Si el número de bits es menor al mínimo requerido por AFIP.
     */
    public static function generarClavePrivada(int $bits = self::BITS_MINIMOS_AFIP, ?string $fraseSecreta = null): string
    {
        if (! class_exists('\phpseclib3\Crypt\RSA')) {
            throw new ConfiguracionException(
                'Es necesario instalar phpseclib: composer require phpseclib/phpseclib:~3.0',
                'phpseclib',
                null,
                CodigosError::CONFIGURACION_DEPENDENCIA_FALTANTE->value
            );
        }

        if ($bits < self::BITS_MINIMOS_AFIP) {
            throw new ValidacionException(
                sprintf('La clave privada debe generarse de al menos %d bits', self::BITS_MINIMOS_AFIP),
                'bits',
                $bits,
                sprintf('min:%d', self::BITS_MINIMOS_AFIP),
                CodigosError::VALIDACION_PARAMETRO_INVALIDO->value
            );
        }

        $rsa = RSA::createKey($bits);

        if ($fraseSecreta !== null && $fraseSecreta !== '' && $fraseSecreta !== '0') {
            $rsa = $rsa->withPassword($fraseSecreta);
        }

        /** @phpstan-ignore-next-line */
        $clavePrivadaString = $rsa->toString('PKCS1');

        if (! is_string($clavePrivadaString)) {
            throw new ConfiguracionException(
                'Error al generar la clave privada en formato PKCS1',
                'generar_clave_privada',
                null,
                CodigosError::CONFIGURACION_ERROR_INTERNO->value
            );
        }

        return $clavePrivadaString;
    }

    /**
     * Genera un Certificate Signing Request (CSR).
     *
     * Crea una solicitud de certificado que debe ser enviada a AFIP para obtener
     * el certificado digital necesario para la autenticación.
     *
     * @param  string|array<string, string>  $clavePrivada  Texto de la clave privada, ruta a un archivo con la clave privada,
     *                                                      o un array con la clave privada (texto o ruta) y frase secreta.
     * @param  array<string, string>  $informacionDn  Distinguished Name (DN) para el certificado.
     * @return string El Certificate Signing Request generado en formato PEM.
     *
     * @throws CertificadoException Si ocurre un error durante la generación del CSR.
     */
    public static function generarCSR(string|array $clavePrivada, array $informacionDn): string
    {
        $x509 = new X509;

        // Convert array format to string if needed
        $clavePrivadaString = is_array($clavePrivada) ? (array_values($clavePrivada)[0] ?? '') : $clavePrivada;

        if ($clavePrivadaString === '' || $clavePrivadaString === '0') {
            throw new CertificadoException(
                'La clave privada debe ser una cadena válida',
                'validar_clave_privada',
                ['clavePrivada' => $clavePrivada],
                CodigosError::CERTIFICADO_ERROR_GENERAR_CSR->value,
            );
        }

        $clavePrivadaObj = RSA::load($clavePrivadaString);

        if (! $clavePrivadaObj instanceof \phpseclib3\Crypt\Common\PrivateKey) {
            throw new CertificadoException(
                'Error al cargar la clave privada',
                'cargar_clave_privada',
                ['clavePrivada' => $clavePrivada],
                CodigosError::CERTIFICADO_ERROR_GENERAR_CSR->value,
            );
        }

        $x509->setPrivateKey($clavePrivadaObj);

        foreach ($informacionDn as $clave => $valor) {
            $x509->setDNProp($clave, $valor);
        }

        $solicitudCSR = $x509->signCSR();

        if (! is_array($solicitudCSR)) {
            throw new CertificadoException(
                'Error al generar el Certificate Signing Request (CSR) con phpseclib3',
                'generar_csr',
                ['clavePrivada' => $clavePrivada, 'informacionDn' => $informacionDn],
                CodigosError::CERTIFICADO_ERROR_GENERAR_CSR->value,
            );
        }

        $contenidoCSR = $x509->saveCSR($solicitudCSR);

        if ($contenidoCSR === '') {
            throw new CertificadoException(
                'Error al exportar el Certificate Signing Request (CSR) con phpseclib3',
                'exportar_csr',
                ['csr' => $solicitudCSR],
                CodigosError::CERTIFICADO_ERROR_EXPORTAR_CSR->value,
            );
        }

        return $contenidoCSR;
    }

    /**
     * Extrae el Distinguished Name (DN) de un Certificate Signing Request.
     *
     * @param  string  $solicitudCSR  CSR en formato texto o ubicación del archivo .csr.
     * @return array<string, string> El Distinguished Name (DN) extraído del CSR.
     *
     * @throws CertificadoException Si no se puede leer o procesar el CSR.
     */
    public static function extraerInformacionCSR(string $solicitudCSR): array
    {
        $x509 = new X509;
        $contenidoCSR = file_get_contents($solicitudCSR);

        if ($contenidoCSR === false) {
            throw new CertificadoException(
                'Error al leer el archivo CSR',
                'leer_archivo_csr',
                ['archivo' => $solicitudCSR],
                CodigosError::CERTIFICADO_ERROR_LEER_CSR->value
            );
        }

        $informacion = $x509->loadCSR($contenidoCSR);

        if (! is_array($informacion)) {
            throw new CertificadoException(
                'Error al extraer la información del Certificate Signing Request (CSR) con phpseclib3',
                'extraer_informacion_csr',
                ['csr' => $solicitudCSR],
                CodigosError::CERTIFICADO_ERROR_LEER_CSR->value
            );
        }

        if (isset($informacion['certificationRequestInfo']) && is_array($informacion['certificationRequestInfo']) &&
            isset($informacion['certificationRequestInfo']['subject']) && is_array($informacion['certificationRequestInfo']['subject']) &&
            isset($informacion['certificationRequestInfo']['subject']['rdnSequence']) &&
            is_array($informacion['certificationRequestInfo']['subject']['rdnSequence'])) {
            $asunto = [];

            $mapaOid = [
                'id-at-countryName' => 'countryName',
                'id-at-stateOrProvinceName' => 'stateOrProvinceName',
                'id-at-localityName' => 'localityName',
                'id-at-organizationName' => 'organizationName',
                'id-at-commonName' => 'commonName',
                'id-at-serialNumber' => 'serialNumber',
            ];

            foreach ($informacion['certificationRequestInfo']['subject']['rdnSequence'] as $rdn) {
                if (is_array($rdn) && isset($rdn[0]) && is_array($rdn[0]) &&
                    isset($rdn[0]['type']) && isset($rdn[0]['value']) && is_array($rdn[0]['value']) &&
                    isset($rdn[0]['value']['utf8String'])) {
                    $oid = $rdn[0]['type'];
                    $valor = $rdn[0]['value']['utf8String'];

                    if (is_string($oid) && is_string($valor)) {
                        if (isset($mapaOid[$oid])) {
                            $asunto[$mapaOid[$oid]] = $valor;
                        } else {
                            $asunto[$oid] = $valor;
                        }
                    }
                }
            }

            return $asunto;
        }

        throw new CertificadoException(
            'No se pudo encontrar la información del sujeto en el CSR con phpseclib3',
            'extraer_informacion_csr',
            ['csr' => $solicitudCSR],
            CodigosError::CERTIFICADO_ERROR_LEER_CSR->value
        );
    }

    /**
     * Extrae la información de un certificado X.509.
     *
     * @param  string  $certificadoPem  Contenido del certificado PEM o ubicación del archivo .pem.
     * @return array<string, mixed> Un array con la información del certificado, incluyendo el emisor,
     *                              asunto y fechas de validez.
     *
     * @throws CertificadoException Si no se puede leer o procesar el certificado.
     */
    public static function extraerInformacionCertificado(string $certificadoPem): array
    {
        $x509 = new X509;
        $exitoCarga = $x509->loadX509($certificadoPem);

        if (! is_array($exitoCarga)) {
            throw new CertificadoException(
                'Error al extraer la información del certificado X.509 con phpseclib3',
                'extraer_informacion_certificado',
                ['certificado_pem' => $certificadoPem],
                CodigosError::CERTIFICADO_ERROR_LEER_CERTIFICADO->value
            );
        }

        $informacionCertificado = $x509->getCurrentCert();

        if (! is_array($informacionCertificado)) {
            throw new CertificadoException(
                'Error al obtener la información del certificado actual',
                'obtener_certificado_actual',
                ['certificado_pem' => $certificadoPem],
                CodigosError::CERTIFICADO_ERROR_LEER_CERTIFICADO->value
            );
        }

        $version = 1;
        if (isset($informacionCertificado['tbsCertificate']) &&
            is_array($informacionCertificado['tbsCertificate']) &&
            isset($informacionCertificado['tbsCertificate']['version']) &&
            is_numeric($informacionCertificado['tbsCertificate']['version'])) {
            $version = (int) $informacionCertificado['tbsCertificate']['version'] + 1;
        }

        $serialNumber = '';
        if (isset($informacionCertificado['tbsCertificate']) && is_array($informacionCertificado['tbsCertificate']) &&
            isset($informacionCertificado['tbsCertificate']['serialNumber']) &&
            is_object($informacionCertificado['tbsCertificate']['serialNumber']) &&
            method_exists($informacionCertificado['tbsCertificate']['serialNumber'], 'toString')) {
            $serialNumber = $informacionCertificado['tbsCertificate']['serialNumber']->toString();
        }

        $validFromTimeT = 0;
        if (isset($informacionCertificado['tbsCertificate']) && is_array($informacionCertificado['tbsCertificate']) &&
            isset($informacionCertificado['tbsCertificate']['validity']) && is_array($informacionCertificado['tbsCertificate']['validity']) &&
            isset($informacionCertificado['tbsCertificate']['validity']['notBefore']) && is_array($informacionCertificado['tbsCertificate']['validity']['notBefore']) &&
            isset($informacionCertificado['tbsCertificate']['validity']['notBefore']['utcTime'])) {
            $utcTime = $informacionCertificado['tbsCertificate']['validity']['notBefore']['utcTime'];
            if (is_string($utcTime) || is_numeric($utcTime)) {
                $validFromTimeT = strtotime((string) $utcTime) ?: 0;
            }
        }

        $validToTimeT = 0;
        if (isset($informacionCertificado['tbsCertificate']) && is_array($informacionCertificado['tbsCertificate']) &&
            isset($informacionCertificado['tbsCertificate']['validity']) && is_array($informacionCertificado['tbsCertificate']['validity']) &&
            isset($informacionCertificado['tbsCertificate']['validity']['notAfter']) && is_array($informacionCertificado['tbsCertificate']['validity']['notAfter']) &&
            isset($informacionCertificado['tbsCertificate']['validity']['notAfter']['utcTime'])) {
            $utcTime = $informacionCertificado['tbsCertificate']['validity']['notAfter']['utcTime'];
            if (is_string($utcTime) || is_numeric($utcTime)) {
                $validToTimeT = strtotime((string) $utcTime) ?: 0;
            }
        }

        $signatureType = '';
        if (isset($informacionCertificado['signatureAlgorithm']) && is_array($informacionCertificado['signatureAlgorithm']) &&
            isset($informacionCertificado['signatureAlgorithm']['algorithm'])) {
            $algorithm = $informacionCertificado['signatureAlgorithm']['algorithm'];
            if (is_string($algorithm) || is_numeric($algorithm)) {
                $signatureType = (string) $algorithm;
            }
        }

        return [
            'version' => $version,
            'serialNumber' => $serialNumber,
            'issuer' => $x509->getIssuerDN(X509::DN_STRING),
            'subject' => $x509->getSubjectDN(X509::DN_STRING),
            'validFrom_time_t' => $validFromTimeT,
            'validTo_time_t' => $validToTimeT,
            'signatureType' => $signatureType,
        ];
    }

    /**
     * Valida la estructura de un Distinguished Name (DN).
     *
     * Verifica que el DN contenga los campos mínimos requeridos por AFIP.
     *
     * @param  array<string, string>  $informacionDn  El DN a validar.
     * @return bool True si el DN es válido.
     *
     * @throws ValidacionException Si el DN no contiene los campos requeridos o el formato es incorrecto.
     */
    public static function validarInformacionDN(array $informacionDn): bool
    {
        $camposRequeridos = [
            'countryName',
            'stateOrProvinceName',
            'localityName',
            'organizationName',
            'commonName',
            'serialNumber',
        ];

        foreach ($camposRequeridos as $campo) {
            if (empty($informacionDn[$campo])) {
                throw new ValidacionException(
                    sprintf('El campo "%s" es requerido en el Distinguished Name', $campo),
                    'distinguished_name',
                    $informacionDn,
                    'required',
                    CodigosError::VALIDACION_DN_INCOMPLETO->value
                );
            }
        }

        if (isset($informacionDn['serialNumber'])) {
            $numeroSerie = $informacionDn['serialNumber'];

            if (in_array(preg_match('/^CUIT \d{11}$/', $numeroSerie), [0, false], true)) {
                throw new ValidacionException(
                    'El serialNumber debe tener el formato "CUIT XXXXXXXXXXX"',
                    'serialNumber',
                    $numeroSerie,
                    'format:CUIT_XXXXXXX',
                    CodigosError::VALIDACION_FORMATO_CUIT->value
                );
            }
        }

        return true;
    }

    /**
     * Crea un Distinguished Name (DN) básico para AFIP.
     *
     * Genera una estructura DN con los campos mínimos necesarios para AFIP.
     *
     * @param  string  $cuit  CUIT del contribuyente (sin guiones).
     * @param  string  $nombreOrganizacion  Nombre de la organización o persona.
     * @param  string  $nombreComun  Nombre común (generalmente el alias).
     * @param  string  $provincia  Provincia donde se encuentra el contribuyente.
     * @param  string  $localidad  Localidad donde se encuentra el contribuyente.
     * @param  string  $pais  Código del país (por defecto 'AR' para Argentina).
     * @return array<string, string> El DN estructurado para usar con generarCSR().
     *
     * @throws ValidacionException Si el CUIT no tiene el formato correcto.
     */
    public static function crearInformacionDN(
        string $cuit,
        string $nombreOrganizacion,
        string $nombreComun,
        string $provincia = 'Buenos Aires',
        string $localidad = 'Ciudad Autónoma de Buenos Aires',
        string $pais = 'AR'
    ): array {
        if (in_array(preg_match('/^\d{11}$/', $cuit), [0, false], true)) {
            throw new ValidacionException(
                'El CUIT debe contener exactamente 11 dígitos numéricos',
                'cuit',
                $cuit,
                'numeric|size:11',
                CodigosError::VALIDACION_CUIT_INVALIDO->value
            );
        }

        $informacionDn = [
            'countryName' => $pais,
            'stateOrProvinceName' => $provincia,
            'localityName' => $localidad,
            'organizationName' => $nombreOrganizacion,
            'commonName' => $nombreComun,
            'serialNumber' => 'CUIT '.$cuit,
        ];

        self::validarInformacionDN($informacionDn);

        return $informacionDn;
    }

    /**
     * Guarda el contenido de un certificado, clave o CSR en un archivo.
     *
     * @param  string  $contenido  El contenido en formato de texto (PEM).
     * @param  string  $ruta  La ruta completa del archivo donde se guardará.
     * @return bool Retorna true si se guardó correctamente, false en caso contrario.
     */
    public static function guardarArchivo(string $contenido, string $ruta): bool
    {
        return file_put_contents($ruta, $contenido) !== false;
    }

    /**
     * Carga el contenido de un archivo de certificado, clave o CSR.
     *
     * @param  string  $ruta  La ruta completa del archivo que se va a leer.
     * @return string|false Retorna el contenido del archivo si se lee correctamente, o false si falla.
     */
    public static function cargarArchivo(string $ruta): string|false
    {
        return file_get_contents($ruta);
    }
}
