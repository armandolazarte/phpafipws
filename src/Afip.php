<?php

declare(strict_types=1);

namespace PhpAfipWs;

use DateTimeImmutable;
use Exception;
use PhpAfipWs\Auth\TokenAuthorization;
use PhpAfipWs\Exception\AuthException;
use PhpAfipWs\Exception\ConfigurationException;
use PhpAfipWs\Exception\ErrorCodes;
use PhpAfipWs\Exception\WebServiceException;
use PhpAfipWs\WebService\Contracts\AfipWebServiceInterface;
use PhpAfipWs\WebService\FacturacionElectronica;
use PhpAfipWs\WebService\PadronAlcanceCinco;
use PhpAfipWs\WebService\PadronAlcanceCuatro;
use PhpAfipWs\WebService\PadronAlcanceDiez;
use PhpAfipWs\WebService\PadronAlcanceTrece;
use PhpAfipWs\WebService\PadronConstanciaInscripcion;
use SimpleXMLElement;
use SoapClient;
use SoapFault;

/**
 * Clase principal para la libreria PhpAfipWs.
 *
 * Esta clase maneja la configuración, autenticación y provee acceso
 * a los diferentes Web Services de AFIP.
 *
 * @property-read WebService\Contracts\FacturacionElectronicaInterface $FacturacionElectronica
 * @property-read WebService\Contracts\PadronAlcanceCuatroInterface $PadronAlcanceCuatro
 * @property-read WebService\Contracts\PadronAlcanceCincoInterface $PadronAlcanceCinco
 * @property-read WebService\Contracts\PadronAlcanceDiezInterface $PadronAlcanceDiez
 * @property-read WebService\Contracts\PadronAlcanceTreceInterface $PadronAlcanceTrece
 * @property-read WebService\Contracts\PadronConstanciaInscripcionInterface $PadronConstanciaInscripcion
 */
class Afip
{
    private const VERSION = '0.1.0';

    private const URL_WSAA_PRODUCCION = 'https://wsaa.afip.gov.ar/ws/services/LoginCms';

    private const URL_WSAA_PRUEBA = 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms';

    /**
     * Nombres de los Web Services implementados que pueden ser accedidos como propiedades.
     *
     * @var string[]
     */
    private const WS_IMPLEMENTADOS = [
        'FacturacionElectronica',
        'PadronAlcanceCuatro',
        'PadronAlcanceCinco',
        'PadronAlcanceDiez',
        'PadronAlcanceTrece',
        'PadronConstanciaInscripcion',
    ];

    /**
     * Opciones de configuración.
     *
     * @var array<string, mixed>
     */
    public array $opciones;

    /**
     * Ruta al archivo WSDL de WSAA.
     */
    private string $wsaaWsdl;

    /**
     * URL para el servicio WSAA.
     */
    private string $wsaaUrl;

    /**
     * Ruta al archivo de certificado.
     */
    private string $rutaCertificado;

    /**
     * Ruta al archivo de clave privada.
     */
    private string $rutaClavePrivada;

    /**
     * Contraseña para la clave privada.
     */
    private string $contraseñaClave;

    /**
     * Ruta a la carpeta de recursos.
     */
    private string $carpetaRecursos;

    /**
     * Ruta a la carpeta donde se almacenan los Tickets de Acceso (TA).
     */
    private string $carpetaTa;

    /**
     * El número de CUIT para la autenticación.
     */
    private int $cuit;

    /**
     * Caché para los clientes de Web Service instanciados.
     *
     * @var array<string, AfipWebServiceInterface>
     */
    private array $instanciasWebService = [];

    /**
     * Constructor de Afip.
     *
     * @param  array<string, mixed>  $opciones  Opciones de configuración para la libreria PhpAfipWs.
     * @param  array<string, callable(Afip): AfipWebServiceInterface>  $serviceFactories  Opcional. Permite inyectar fábricas para los Web Services.
     *
     * @throws ConfigurationException Si las opciones son inválidas o los archivos requeridos no se encuentran.
     */
    public function __construct(array $opciones, private array $serviceFactories = [])
    {
        ini_set('soap.wsdl_cache_enabled', '0');

        $this->inicializarOpciones($opciones);
        $this->configurarRutas();
        $this->validarArchivos();

        if ($this->serviceFactories === []) {
            $this->registerDefaultServiceFactories();
        }
    }

    /**
     * Método mágico para acceder a los clientes de Web Service como propiedades.
     *
     * @param  string  $propiedad  El nombre del Web Service o propiedad a acceder.
     * @return AfipWebServiceInterface|mixed La instancia del Web Service solicitado o el valor de la propiedad.
     *
     * @throws ConfigurationException Si la propiedad o el Web Service no existe o su clase no se encuentra.
     */
    public function __get(string $propiedad): mixed
    {
        if (in_array($propiedad, self::WS_IMPLEMENTADOS)) {
            if (! isset($this->instanciasWebService[$propiedad])) {
                if (! isset($this->serviceFactories[$propiedad])) {
                    throw new ConfigurationException(
                        sprintf('No se encontró la fábrica para el WebService %s. Asegúrese de que el nombre del servicio es correcto o de haberlo registrado en las fábricas de servicios.', $propiedad),
                        ErrorCodes::CONFIG_MISSING_SPECIFIC_SERVICE_DEFINITION
                    );
                }

                $this->instanciasWebService[$propiedad] = $this->serviceFactories[$propiedad]($this);
            }

            return $this->instanciasWebService[$propiedad];
        }

        if (property_exists($this, $propiedad)) {
            return $this->{$propiedad};
        }

        throw new ConfigurationException(
            sprintf('La propiedad %s no existe o no es un Web Service AFIP válido.', $propiedad),
            ErrorCodes::CONFIG_INVALID_OPTION
        );
    }

    /**
     * Obtiene la versión actual de la Libreria PhpAfipWs.
     *
     * @return string La versión de la Libreria PhpAfipWs.
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Obtiene el número de CUIT.
     *
     * @return int El número de CUIT.
     */
    public function getCuit(): int
    {
        return $this->cuit;
    }

    /**
     * Verifica si la libreria PhpAfipWs está en modo de producción.
     *
     * @return bool True si está en modo producción, false en caso contrario.
     */
    public function esProduccion(): bool
    {
        return (bool) $this->opciones['modo_produccion'];
    }

    /**
     * Obtiene la opción de manejar excepciones SOAP.
     */
    public function getManejarExcepcionesSoap(): bool
    {
        return (bool) ($this->opciones['manejar_excepciones_soap'] ?? false);
    }

    /**
     * Obtiene el Token de Autorización para un Web Service específico de AFIP.
     * Intentará reutilizar un Ticket de Acceso (TA) válido del sistema de archivos,
     * o creará uno nuevo si falta o ha expirado.
     *
     * @param  string  $servicio  El nombre del servicio para el cual obtener el TA (ej. 'wsfe').
     * @param  bool  $continuar  Bandera interna para prevenir bucles de recursión.
     * @return TokenAuthorization El objeto de autorización del token.
     *
     * @throws AuthException Si ocurre un error al obtener o crear el Token de Autorización (TA).
     * @throws ConfigurationException Si hay problemas con rutas de archivo o permisos al guardar el TA.
     */
    public function getServiceTA(string $servicio, bool $continuar = true): TokenAuthorization
    {
        $archivoTa = $this->getRutaArchivoTA($servicio);

        if (file_exists($archivoTa)) {
            $contenidoTa = file_get_contents($archivoTa);

            if ($contenidoTa === false) {
                throw new ConfigurationException(
                    sprintf('No se pudo leer el contenido del archivo TA: %s. Verifique permisos.', $archivoTa),
                    ErrorCodes::AUTH_TA_READ_FAILED
                );
            }

            try {
                $ta = new SimpleXMLElement($contenidoTa);
            } catch (Exception $e) {
                throw new AuthException(
                    sprintf('El archivo TA %s está corrupto o mal formado: %s. Intentando generar uno nuevo.', $archivoTa, $e->getMessage()),
                    ErrorCodes::AUTH_TA_CORRUPT,
                    $e
                );
            }

            $tiempoActual = new DateTimeImmutable(date('c', time() + 600));

            $expirationTime = (string) ($ta->header->expirationTime ?? '');

            if ($expirationTime === '' || $expirationTime === '0') {
                throw new AuthException(
                    sprintf('El elemento "expirationTime" no se encontró en el TA %s. El archivo TA puede estar corrupto.', $archivoTa),
                    ErrorCodes::AUTH_TA_CORRUPT
                );
            }

            $tiempoExpiracion = new DateTimeImmutable($expirationTime);

            if ($tiempoActual < $tiempoExpiracion) {
                return new TokenAuthorization(
                    (string) $ta->credentials->token,
                    (string) $ta->credentials->sign
                );
            }

            if (! $continuar) {
                throw new AuthException('Error al obtener el TA - El token ha expirado y no pudo ser renovado.', ErrorCodes::AUTH_TA_EXPIRED);
            }
        }

        if ($this->crearServiceTA($servicio)) {
            return $this->getServiceTA($servicio, false);
        }

        throw new AuthException('No se pudo crear el Ticket de Acceso (TA) para el servicio '.$servicio.'.', ErrorCodes::AUTH_TA_CREATION_FAILED);
    }

    /**
     * Crea un cliente de Web Service genérico para servicios no implementados explícitamente.
     *
     * @param  string  $servicio  El nombre del servicio.
     * @param  array<string, mixed>  $opciones  Opciones de configuración para el servicio genérico.
     * @return AfipWebServiceInterface Una instancia del cliente de web service genérico.
     *
     * @throws ConfigurationException si ocurre un error en la configuración.
     */
    public function webService(string $servicio, array $opciones): AfipWebServiceInterface
    {
        $opciones['service'] = $servicio;
        $opciones['generic'] = true;

        return new WebService\AfipWebService($this, $opciones);
    }

    /**
     * Crea un nuevo Ticket de Acceso (TA) autenticándose con el WSAA.
     *
     * @param  string  $servicio  El nombre del servicio para el cual crear el TA.
     * @return bool True en caso de éxito.
     *
     * @throws AuthException Si ocurre un error durante el proceso de creación (generación de TRA, firma o solicitud al WSAA).
     * @throws ConfigurationException Si hay problemas con rutas de archivo o permisos.
     */
    protected function crearServiceTA(string $servicio): bool
    {
        if (! is_dir($this->carpetaTa) && ! mkdir($this->carpetaTa, 0777, true)) {
            throw new ConfigurationException(
                sprintf('No se pudo crear la carpeta para los TA: %s. Verifique permisos.', $this->carpetaTa),
                ErrorCodes::CONFIG_DIRECTORY_CREATE_FAILED
            );
        }

        $archivoTra = $this->crearTRA($servicio);
        $traFirmado = $this->firmarTRA($archivoTra, $servicio);
        $ta = $this->solicitarTADeWSAA($traFirmado);

        return $this->guardarArchivoTA($servicio, $ta);
    }

    /**
     * Registra las fábricas de servicios por defecto.
     * Esto acopla la clase Afip con las implementaciones concretas de los servicios,
     * pero es la opción por defecto si no se usa un contenedor externo.
     */
    private function registerDefaultServiceFactories(): void
    {
        $this->serviceFactories = [
            'FacturacionElectronica' => fn (Afip $afip): FacturacionElectronica => new FacturacionElectronica($afip),
            'PadronAlcanceCuatro' => fn (Afip $afip): PadronAlcanceCuatro => new PadronAlcanceCuatro($afip),
            'PadronAlcanceCinco' => fn (Afip $afip): PadronAlcanceCinco => new PadronAlcanceCinco($afip),
            'PadronAlcanceDiez' => fn (Afip $afip): PadronAlcanceDiez => new PadronAlcanceDiez($afip),
            'PadronAlcanceTrece' => fn (Afip $afip): PadronAlcanceTrece => new PadronAlcanceTrece($afip),
            'PadronConstanciaInscripcion' => fn (Afip $afip): PadronConstanciaInscripcion => new PadronConstanciaInscripcion($afip),
        ];
    }

    /**
     * Inicializa las propiedades desde el array de opciones.
     *
     * @param  array<string, mixed>  $opciones  Las opciones de configuración.
     *
     * @throws ConfigurationException
     */
    private function inicializarOpciones(array $opciones): void
    {
        if (! isset($opciones['cuit'])) {
            throw new ConfigurationException('El campo CUIT es requerido en el array de opciones', ErrorCodes::CONFIG_INVALID_OPTION);
        }

        if (! is_numeric($opciones['cuit'])) {
            throw new ConfigurationException('El CUIT debe ser numérico', ErrorCodes::CONFIG_INVALID_OPTION);
        }

        $this->cuit = (int) $opciones['cuit'];

        $this->opciones = array_merge([
            'modo_produccion' => false,
            'contrasena_clave' => '',
            'manejar_excepciones_soap' => false,
            'ruta_certificado' => 'cert',
            'ruta_clave' => 'key',
            'carpeta_recursos' => null,
            'carpeta_ta' => null,
        ], $opciones);

        $this->contraseñaClave = (string) ($this->opciones['contrasena_clave']);
    }

    /**
     * Configura las rutas para los recursos, certificados y archivos WSDL.
     *
     * @throws ConfigurationException Si las rutas no son válidas o no se pueden resolver.
     */
    private function configurarRutas(): void
    {
        $carpetaDefault = __DIR__.'/Resources/';

        $this->carpetaRecursos = mb_rtrim((string) ($this->opciones['carpeta_recursos'] ?? $carpetaDefault), '/').'/';
        $this->carpetaTa = mb_rtrim((string) ($this->opciones['carpeta_ta'] ?? $carpetaDefault), '/').'/';

        $resolvedCertPath = realpath($this->carpetaRecursos.$this->opciones['ruta_certificado']);

        if ($resolvedCertPath === false) {
            throw new ConfigurationException(
                sprintf('Archivo de certificado no encontrado: %s%s', $this->carpetaRecursos, $this->opciones['ruta_certificado']),
                ErrorCodes::CONFIG_FILE_NOT_FOUND
            );
        }

        $this->rutaCertificado = $resolvedCertPath;

        $resolvedKeyPath = realpath($this->carpetaRecursos.$this->opciones['ruta_clave']);

        if ($resolvedKeyPath === false) {
            throw new ConfigurationException(
                sprintf('Archivo de clave privada no encontrado: %s%s', $this->carpetaRecursos, $this->opciones['ruta_clave']),
                ErrorCodes::CONFIG_FILE_NOT_FOUND
            );
        }

        $this->rutaClavePrivada = $resolvedKeyPath;

        $resolvedWsaaWsdlPath = realpath(__DIR__.'/Resources/wsaa.wsdl');

        if ($resolvedWsaaWsdlPath === false) {
            throw new ConfigurationException(
                sprintf('Archivo WSDL de WSAA no encontrado: %s', __DIR__.'/Resources/wsaa.wsdl'),
                ErrorCodes::CONFIG_FILE_NOT_FOUND
            );
        }

        $this->wsaaWsdl = $resolvedWsaaWsdlPath;

        $this->wsaaUrl = $this->opciones['modo_produccion']
            ? self::URL_WSAA_PRODUCCION
            : self::URL_WSAA_PRUEBA;
    }

    /**
     * Valida que todos los archivos requeridos existan.
     * (Este método se ha vuelto redundante con la mejora de configurarRutas)
     *
     * @throws ConfigurationException Si no se encuentra un archivo requerido.
     */
    private function validarArchivos(): void
    {
        if (! is_file($this->rutaCertificado)) {
            throw new ConfigurationException('Archivo de certificado no encontrado: '.$this->rutaCertificado, ErrorCodes::CONFIG_FILE_NOT_FOUND);
        }

        if (! is_file($this->rutaClavePrivada)) {
            throw new ConfigurationException('Archivo de clave privada no encontrado: '.$this->rutaClavePrivada, ErrorCodes::CONFIG_FILE_NOT_FOUND);
        }

        if (! is_file($this->wsaaWsdl)) {
            throw new ConfigurationException('Archivo WSDL de WSAA no encontrado: '.$this->wsaaWsdl, ErrorCodes::CONFIG_FILE_NOT_FOUND);
        }
    }

    /**
     * Construye la ruta del archivo para el Ticket de Acceso (TA) de un servicio dado.
     *
     * @param  string  $nombreServicio  El nombre del servicio.
     * @return string La ruta completa al archivo TA.
     */
    private function getRutaArchivoTA(string $nombreServicio): string
    {
        $sufijo = $this->opciones['modo_produccion'] ? '-production' : '';

        return $this->carpetaTa.sprintf('TA-%d-%s%s.xml', $this->cuit, $nombreServicio, $sufijo);
    }

    /**
     * Crea un archivo de Solicitud de Ticket de Acceso (TRA).
     *
     * @param  string  $servicio  El nombre del servicio para el TRA.
     * @return string La ruta al archivo TRA creado.
     *
     * @throws AuthException Si no se puede generar el TRA.
     * @throws ConfigurationException Si hay problemas de escritura de archivos.
     */
    private function crearTRA(string $servicio): string
    {
        $tra = new SimpleXMLElement(
            '<?xml version="1.0" encoding="utf-8"?>'.
            '<loginTicketRequest version="1.0">'.
            '</loginTicketRequest>'
        );

        $tra->addChild('header');
        $tra->header->addChild('uniqueId', (string) time());
        $tra->header->addChild('generationTime', date('c', time() - 600));
        $tra->header->addChild('expirationTime', date('c', time() + 600));
        $tra->addChild('service', $servicio);

        $archivoTra = $this->carpetaTa.sprintf('TRA-%d-%s.xml', $this->cuit, $servicio);

        if ($tra->asXML($archivoTra) === false) {
            throw new ConfigurationException('No se pudo guardar el archivo TRA: '.$archivoTra, ErrorCodes::CONFIG_FILE_WRITE_FAILED);
        }

        return $archivoTra;
    }

    /**
     * Firma un archivo de Solicitud de Ticket de Acceso (TRA) usando OpenSSL.
     *
     * @param  string  $archivoTra  La ruta al archivo TRA.
     * @param  string  $servicio  El nombre del servicio.
     * @return string La firma CMS (Cryptographic Message Syntax).
     *
     * @throws AuthException Si la firma falla.
     * @throws ConfigurationException Si hay problemas con archivos temporales.
     */
    private function firmarTRA(string $archivoTra, string $servicio): string
    {
        $archivoTemp = $this->carpetaTa.sprintf('TRA-%d-%s.tmp', $this->cuit, $servicio);

        if (! is_file($this->rutaCertificado) || ! is_file($this->rutaClavePrivada)) {
            throw new ConfigurationException('Certificado o clave privada no accesibles para la firma. Verifique rutas y permisos.', ErrorCodes::CONFIG_FILE_NOT_FOUND);
        }

        $estado = openssl_pkcs7_sign(
            $archivoTra,
            $archivoTemp,
            'file://'.$this->rutaCertificado,
            ['file://'.$this->rutaClavePrivada, $this->contraseñaClave],
            [],
            0
        );

        if (! $estado) {
            $sslError = openssl_error_string();

            throw new AuthException('Error al firmar el TRA con OpenSSL. '.($sslError ? 'Detalle: '.$sslError : ''), ErrorCodes::AUTH_TRA_SIGNING_FAILED);
        }

        $cms = $this->extraerCMSDeArchivoFirmado($archivoTemp);

        if (file_exists($archivoTra)) {
            unlink($archivoTra);
        }

        if (file_exists($archivoTemp)) {
            unlink($archivoTemp);
        }

        return $cms;
    }

    /**
     * Extrae el contenido CMS de un archivo firmado.
     *
     * @param  string  $archivoTemp  La ruta al archivo temporal firmado.
     * @return string El contenido CMS extraído.
     *
     * @throws AuthException Si el archivo no puede ser leído o el CMS no puede ser extraído.
     */
    private function extraerCMSDeArchivoFirmado(string $archivoTemp): string
    {
        $contenidoArchivo = file_get_contents($archivoTemp);

        if ($contenidoArchivo === false) {
            throw new ConfigurationException('No se puede leer el archivo TRA firmado: '.$archivoTemp, ErrorCodes::CONFIG_FILE_READ_FAILED);
        }

        if (preg_match('/-----BEGIN PKCS7-----\s*(.*?)\s*-----END PKCS7-----/s', $contenidoArchivo, $matches)) {
            $cms = preg_replace('/\s+/', '', $matches[1]);

            if ($cms === null) {
                throw new AuthException('Fallo al extraer el CMS del archivo firmado. Error de procesamiento interno.', ErrorCodes::AUTH_CMS_EXTRACTION_FAILED);
            }

            return $cms;
        }

        $partes = preg_split('/(\r\n\r\n|\n\n)/', $contenidoArchivo, 2);

        if (isset($partes[1])) {
            $cms = preg_replace('/\s+/', '', $partes[1]);

            if ($cms !== null && ! empty($cms)) {
                return $cms;
            }
        }

        throw new AuthException('Fallo al extraer el CMS del archivo firmado. Formato PKCS7 inválido o inesperado.', ErrorCodes::AUTH_CMS_EXTRACTION_FAILED);
    }

    /**
     * Solicita el Ticket de Acceso (TA) al servicio WSAA.
     *
     * @param  string  $cmsFirmado  La solicitud CMS firmada.
     * @return string La respuesta XML del WSAA que contiene el TA.
     *
     * @throws WebServiceException Si ocurre un fallo de SOAP o la respuesta es inválida.
     * @throws AuthException Si la respuesta del WSAA no contiene un TA válido.
     */
    private function solicitarTADeWSAA(string $cmsFirmado): string
    {
        try {
            $clienteSoap = new SoapClient($this->wsaaWsdl, [
                'soap_version' => SOAP_1_2,
                'location' => $this->wsaaUrl,
                'trace' => 1,
                'exceptions' => $this->getManejarExcepcionesSoap(),
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'ciphers' => 'AES256-SHA',
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]),
            ]);

            $resultados = $clienteSoap->loginCms(['in0' => $cmsFirmado]);

            if (is_soap_fault($resultados)) {
                /** @var SoapFault $resultados */
                throw new WebServiceException(
                    sprintf(
                        'Fallo SOAP del WSAA: %s%s%s',
                        (string) ($resultados->faultcode),
                        PHP_EOL,
                        (string) ($resultados->faultstring)
                    ),
                    ErrorCodes::AUTH_WSAA_SOAP_ERROR,
                    $resultados->faultcode,
                    new Exception($resultados->faultstring, (int) $resultados->faultcode)
                );
            }

            if (is_object($resultados) && property_exists($resultados, 'loginCmsReturn')) {
                return (string) ($resultados->loginCmsReturn);
            }

            throw new AuthException('Respuesta inválida del WSAA: no se encontró loginCmsReturn.', ErrorCodes::AUTH_WSAA_INVALID_RESPONSE);
        } catch (SoapFault $e) {
            throw new WebServiceException(
                sprintf('Error de comunicación con WSAA (SOAP): %s', $e->getMessage()),
                ErrorCodes::AUTH_WSAA_SOAP_ERROR,
                $e->faultcode,
                $e
            );
        } catch (Exception $e) {
            throw new WebServiceException(
                sprintf('Error inesperado al comunicarse con WSAA: %s', $e->getMessage()),
                ErrorCodes::WEB_SERVICE_UNKNOWN_ERROR,
                null,
                $e
            );
        }
    }

    /**
     * Guarda el Ticket de Acceso (TA) en un archivo.
     *
     * @param  string  $servicio  El nombre del servicio.
     * @param  string  $ta  El contenido XML del TA.
     * @return bool True en caso de éxito.
     *
     * @throws ConfigurationException Si no se puede escribir en el archivo.
     */
    private function guardarArchivoTA(string $servicio, string $ta): bool
    {
        $archivoTa = $this->getRutaArchivoTA($servicio);

        $dir = dirname($archivoTa);

        if (! is_dir($dir) && ! mkdir($dir, 0777, true)) {
            throw new ConfigurationException(
                sprintf('No se pudo crear el directorio para el TA: %s. Verifique permisos.', $dir),
                ErrorCodes::CONFIG_DIRECTORY_CREATE_FAILED
            );
        }

        if (file_put_contents($archivoTa, $ta) === false) {
            throw new ConfigurationException('Error al escribir el archivo TA: '.$archivoTa.'. Verifique permisos.', ErrorCodes::CONFIG_FILE_WRITE_FAILED);
        }

        return true;
    }
}
