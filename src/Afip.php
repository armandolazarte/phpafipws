<?php

declare(strict_types=1);

namespace PhpAfipWs;

use DateTimeImmutable;
use PhpAfipWs\Authorization\TokenAuthorization;
use PhpAfipWs\Enums\CodigosError;
use PhpAfipWs\Exception\ArchivoException;
use PhpAfipWs\Exception\AutenticacionException;
use PhpAfipWs\Exception\ConfiguracionException;
use PhpAfipWs\Exception\ValidacionException;
use PhpAfipWs\Exception\WebServiceException;
use PhpAfipWs\WebService\AfipWebService;
use SimpleXMLElement;
use SoapClient;
use SoapFault;

/**
 * Clase principal para el SDK de ARCA.
 *
 * Esta clase maneja la configuración, autenticación y provee acceso
 * a los diferentes Web Services de AFIP.
 *
 * @property-read WebService\FacturacionElectronica $FacturacionElectronica
 * @property-read WebService\PadronAlcanceCuatro $PadronAlcanceCuatro
 * @property-read WebService\PadronAlcanceCinco $PadronAlcanceCinco
 * @property-read WebService\ConstanciaInscripcion $ConstanciaInscripcion
 * @property-read WebService\PadronAlcanceDiez $PadronAlcanceDiez
 * @property-read WebService\PadronAlcanceTrece $PadronAlcanceTrece
 */
class Afip
{
    /**
     * Versión actual del SDK.
     */
    private const VERSION_SDK = '1.1.1';

    /**
     * URL del servicio WSAA para el entorno de producción.
     */
    private const URL_WSAA_PRODUCCION = 'https://wsaa.afip.gov.ar/ws/services/LoginCms';

    /**
     * URL del servicio WSAA para el entorno de pruebas (homologación).
     */
    private const URL_WSAA_PRUEBAS = 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms';

    /**
     * Lista de Web Services implementados que pueden ser accedidos como propiedades.
     *
     * @var string[]
     */
    private const WS_IMPLEMENTADOS = [
        'FacturacionElectronica',
        'PadronAlcanceCuatro',
        'PadronAlcanceCinco',
        'ConstanciaInscripcion',
        'PadronAlcanceDiez',
        'PadronAlcanceTrece',
    ];

    /**
     * El número de CUIT para la autenticación.
     */
    /**
     * El número de CUIT para la autenticación.
     */
    private int $cuit;

    /**
     * Ruta al archivo WSDL de WSAA.
     */
    private string $wsaaWsdl;

    /**
     * URL para el servicio WSAA.
     */
    private string $urlWsaa;

    /**
     * Ruta al archivo de certificado.
     */
    private string $nombreCertificado;

    /**
     * Ruta al archivo de clave privada.
     */
    private string $nombreClavePrivada;

    /**
     * Contraseña para la clave privada.
     */
    private string $fraseClave;

    /**
     * Ruta a la carpeta de recursos.
     */
    private string $carpetaRecursos;

    /**
     * Ruta a la carpeta donde se almacenan los Tickets de Acceso (TA).
     */
    private string $carpetaTA;

    /**
     * Ruta a la carpeta de WSDLs personalizada.
     */
    private ?string $carpetaWsdl = null;

    /**
     * Opciones de configuración del SDK.
     *
     * @var array<string, mixed>
     */
    private array $opciones;

    /**
     * Caché para los clientes de Web Service instanciados.
     *
     * @var array<string, AfipWebService>
     */
    private array $instanciasWebService = [];

    /**
     * Constructor de la clase Afip.
     *
     * @param  array<string, mixed>  $opciones  Opciones de configuración para el SDK.
     *                                          - `cuit` (int|string): Número de CUIT. Requerido.
     *                                          - `modo_produccion` (bool): Indica si se usará el entorno de producción. Por defecto, `false`.
     *                                          - `nombre_certificado` (string): Nombre del archivo de certificado (si está en `carpeta_recursos`). Por defecto, 'cert'.
     *                                          - `nombre_clave` (string): Nombre del archivo de clave privada (si está en `carpeta_recursos`). Por defecto, 'key'.
     *                                          - `contrasena_clave` (string): Contraseña para la clave privada. Por defecto, ''.
     *                                          - `carpeta_recursos` (string): Ruta a la carpeta donde se encuentran los certificados y claves. Requerido.
     *                                          - `carpeta_ta` (string): Ruta a la carpeta donde se almacenarán los Tickets de Acceso (TA). Requerido.
     *                                          - `carpeta_wsdl` (string|null): Ruta a una carpeta personalizada de WSDLs. Por defecto, `null`.
     *                                          - `manejar_excepciones_soap` (bool): Si se deben manejar las excepciones SOAP internamente. Por defecto, `false`.
     *
     * @throws ConfiguracionException Si las opciones de configuración son inválidas o faltan.
     * @throws ValidacionException Si los datos de configuración no son válidos (ej. CUIT no numérico).
     * @throws ArchivoException Si los archivos de certificado o clave privada no se encuentran.
     */
    public function __construct(array $opciones)
    {
        ini_set('soap.wsdl_cache_enabled', '0');

        $this->inicializarOpciones($opciones);
        $this->configurarRutas();
        $this->validarArchivos();
    }

    /**
     * Permite acceder a las instancias de los Web Services de AFIP como propiedades de la clase.
     *
     * Este método mágico se invoca cuando se intenta leer una propiedad inaccesible.
     * Si la propiedad corresponde a un Web Service implementado, se devuelve una instancia de ese servicio.
     * De lo contrario, si la propiedad existe en la clase, se devuelve su valor.
     *
     * @param  string  $propiedad  El nombre de la propiedad o del Web Service al que se intenta acceder.
     * @return AfipWebService|mixed La instancia del Web Service solicitado o el valor de la propiedad interna.
     *
     * @throws WebServiceException Si la propiedad no corresponde a un Web Service implementado,
     *                             si la clase del Web Service no se encuentra, o si la propiedad no existe.
     */
    public function __get(string $propiedad): mixed
    {
        if (in_array($propiedad, self::WS_IMPLEMENTADOS)) {
            if (! isset($this->instanciasWebService[$propiedad])) {
                $nombreClase = 'PhpAfipWs\WebService\\'.$propiedad;

                if (! class_exists($nombreClase)) {
                    throw new WebServiceException(
                        sprintf('La clase del WebService %s no fue encontrada', $nombreClase),
                        $propiedad,
                        'instanciar_clase',
                        null,
                        CodigosError::SERVICIO_WEB_CLASE_NO_ENCONTRADA->value
                    );
                }

                $this->instanciasWebService[$propiedad] = new $nombreClase($this);
            }

            return $this->instanciasWebService[$propiedad];
        }

        if (property_exists($this, $propiedad)) {
            return $this->{$propiedad};
        }

        throw new WebServiceException(
            sprintf('La propiedad %s no existe', $propiedad),
            '',
            'acceder_propiedad',
            ['propiedad' => $propiedad],
            CodigosError::SERVICIO_WEB_METODO_NO_ENCONTRADO->value
        );
    }

    /**
     * Obtiene la versión actual del SDK.
     *
     * @return string La versión del SDK.
     */
    public function obtenerVersionSDK(): string
    {
        return self::VERSION_SDK;
    }

    /**
     * Obtiene el número de CUIT.
     *
     * @return int El número de CUIT.
     */
    public function obtenerCuit(): int
    {
        return $this->cuit;
    }

    /**
     * Obtiene la ruta a la carpeta de WSDLs personalizada.
     *
     * @return string|null La ruta de la carpeta, o null si no se ha configurado.
     */
    public function getCarpetaWsdl(): ?string
    {
        return $this->carpetaWsdl;
    }

    /**
     * Verifica si el SDK está en modo de producción.
     *
     * @return bool True si está en modo producción, false en caso contrario.
     */
    public function esModoProduccion(): bool
    {
        return (bool) $this->opciones['modo_produccion'];
    }

    /**
     * Obtiene un Ticket de Acceso (TA) para un servicio específico de AFIP.
     *
     * Este método intenta primero cargar un TA válido desde el sistema de archivos.
     * Si el TA no existe o ha expirado, se intentará crear uno nuevo.
     *
     * @param  string  $servicio  El nombre del servicio para el cual se solicita el TA (ej. 'wsfe').
     * @param  bool  $continuar  Indica si se debe intentar crear un nuevo TA si el existente ha expirado o no se encuentra.
     *                           Este parámetro es utilizado internamente para controlar la recursión.
     * @return TokenAuthorization Una instancia de `TokenAuthorization` que contiene el token y la firma.
     *
     * @throws AutenticacionException Si el TA ha expirado y no puede ser renovado, o si falla la creación de un nuevo TA.
     */
    public function obtenerTAServicio(string $servicio, bool $continuar = true): TokenAuthorization
    {
        $archivoTA = $this->obtenerRutaArchivoTA($servicio);

        if (file_exists($archivoTA)) {
            $contenidoTA = file_get_contents($archivoTA);

            if ($contenidoTA !== false) {
                $ta = new SimpleXMLElement($contenidoTA);

                $tiempoActual = new DateTimeImmutable(date('c', time() + 600));
                $tiempoExpiracion = new DateTimeImmutable((string) $ta->header->expirationTime);

                if ($tiempoActual < $tiempoExpiracion) {
                    return new TokenAuthorization(
                        (string) $ta->credentials->token,
                        (string) $ta->credentials->sign
                    );
                }

                if (! $continuar) {
                    throw new AutenticacionException(
                        'Error al obtener el TA - El token ha expirado',
                        $servicio,
                        'token_expirado',
                        CodigosError::AUTENTICACION_TOKEN_EXPIRADO->value
                    );
                }
            }
        }

        if ($this->crearTAServicio($servicio)) {
            return $this->obtenerTAServicio($servicio, false);
        }

        throw new AutenticacionException(
            'No se pudo crear el TA del servicio',
            $servicio,
            null,
            CodigosError::AUTENTICACION_ERROR_CREAR_TA->value
        );
    }

    /**
     * Crea y devuelve una instancia de un cliente de Web Service genérico.
     *
     * Este método es útil para interactuar con Web Services de AFIP que no tienen una implementación
     * específica en el SDK, permitiendo una configuración flexible.
     *
     * @param  string  $servicio  El nombre del servicio AFIP al que se desea conectar (ej. 'ws_sr_padron_a4').
     * @param  array<string, mixed>  $opciones  Un array asociativo de opciones de configuración específicas para este servicio genérico.
     *                                          Puede incluir, por ejemplo, la ruta al archivo WSDL (`wsdl`).
     * @return AfipWebService Una instancia del cliente de Web Service genérico configurado.
     *
     * @throws WebServiceException Si ocurre un error durante la instanciación o configuración del cliente.
     */
    public function webService(string $servicio, array $opciones): AfipWebService
    {
        $opciones['servicio'] = $servicio;
        $opciones['generico'] = true;

        return new AfipWebService($this, $opciones);
    }

    /**
     * Crea un nuevo Ticket de Acceso (TA) para un servicio específico, autenticándose con el WSAA.
     *
     * Este proceso implica la creación de un Ticket de Requerimiento de Acceso (TRA),
     * la firma de este TRA con el certificado y clave privada, y finalmente la solicitud
     * del TA al Web Service de Autenticación y Autorización (WSAA) de AFIP.
     *
     * @param  string  $servicio  El nombre del servicio AFIP para el cual se generará el TA.
     * @return bool `true` si el TA fue creado y guardado exitosamente.
     *
     * @throws AutenticacionException Si ocurre un error durante la generación del TRA, la firma digital,
     *                                o la comunicación con el WSAA.
     * @throws ArchivoException Si hay problemas al leer o escribir archivos (TRA, TA, certificado, clave).
     */
    protected function crearTAServicio(string $servicio): bool
    {
        $archivoTRA = $this->crearTRA($servicio);
        $traFirmado = $this->firmarTRA($archivoTRA, $servicio);
        $ta = $this->solicitarTADeWSAA($traFirmado);

        return $this->guardarArchivoTA($servicio, $ta);
    }

    /**
     * Inicializa las propiedades internas de la clase a partir del array de opciones proporcionado.
     *
     * Este método valida la presencia de opciones requeridas y establece valores por defecto
     * para las opciones opcionales.
     *
     * @param  array<string, mixed>  $opciones  Un array asociativo con las opciones de configuración del SDK.
     *
     * @throws ConfiguracionException Si alguna de las opciones requeridas (`cuit`, `carpeta_recursos`, `carpeta_ta`) no está presente o está vacía.
     * @throws ValidacionException Si el valor de `cuit` no es numérico.
     */
    private function inicializarOpciones(array $opciones): void
    {
        $opcionesRequeridas = ['cuit', 'carpeta_recursos', 'carpeta_ta'];

        foreach ($opcionesRequeridas as $opcion) {
            if (empty($opciones[$opcion])) {
                throw new ConfiguracionException(
                    sprintf('El campo "%s" es requerido en el array de opciones', $opcion),
                    $opcion,
                    null,
                    CodigosError::CONFIGURACION_CAMPO_REQUERIDO->value
                );
            }
        }

        if (! is_numeric($opciones['cuit'])) {
            throw new ValidacionException(
                'El cuit debe ser numérico',
                'cuit',
                $opciones['cuit'],
                'numeric',
                CodigosError::VALIDACION_CUIT_INVALIDO->value
            );
        }

        $this->cuit = (int) $opciones['cuit'];

        $this->opciones = array_merge([
            'modo_produccion' => false,
            'nombre_certificado' => 'cert',
            'nombre_clave' => 'key',
            'contrasena_clave' => '',
            'carpeta_wsdl' => null,
            'manejar_excepciones_soap' => false,
        ], $opciones);

        $contrasena = $this->opciones['contrasena_clave'] ?? '';
        $carpetaRecursos = $this->opciones['carpeta_recursos'];
        $carpetaTA = $this->opciones['carpeta_ta'];

        assert(is_string($contrasena) || is_numeric($contrasena));
        assert(is_string($carpetaRecursos) || is_numeric($carpetaRecursos));
        assert(is_string($carpetaTA) || is_numeric($carpetaTA));

        $this->fraseClave = (string) $contrasena;
        $this->carpetaRecursos = (string) $carpetaRecursos;
        $this->carpetaTA = (string) $carpetaTA;
    }

    /**
     * Configura las rutas absolutas para los archivos de certificado, clave privada y el WSDL de WSAA.
     *
     * También determina la URL del servicio WSAA basándose en el modo de producción.
     * Si se especifica una carpeta de WSDLs personalizada, la establece.
     */
    private function configurarRutas(): void
    {
        $nombreCertificado = $this->opciones['nombre_certificado'] ?? 'cert';
        $nombreClave = $this->opciones['nombre_clave'] ?? 'key';

        assert(is_string($nombreCertificado) || is_numeric($nombreCertificado));
        assert(is_string($nombreClave) || is_numeric($nombreClave));

        $this->nombreCertificado = mb_rtrim($this->carpetaRecursos, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$nombreCertificado;
        $this->nombreClavePrivada = mb_rtrim($this->carpetaRecursos, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$nombreClave;
        $this->wsaaWsdl = __DIR__.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.'wsaa.wsdl'; // La ruta de WSAA WSDL no se modifica.

        $this->urlWsaa = $this->opciones['modo_produccion']
            ? self::URL_WSAA_PRODUCCION
            : self::URL_WSAA_PRUEBAS;

        if (isset($this->opciones['carpeta_wsdl'])) {
            $carpetaWsdl = $this->opciones['carpeta_wsdl'];

            assert(is_string($carpetaWsdl) || is_numeric($carpetaWsdl));

            $this->carpetaWsdl = (string) $carpetaWsdl;
        }
    }

    /**
     * Valida la existencia de los archivos esenciales para la operación del SDK.
     *
     * Se verifica que el archivo de certificado, la clave privada y el WSDL de WSAA existan
     * en las rutas configuradas.
     *
     * @throws ArchivoException Si alguno de los archivos requeridos no se encuentra en la ruta especificada.
     */
    private function validarArchivos(): void
    {
        $archivos = [
            $this->nombreCertificado => 'Archivo de certificado no encontrado',
            $this->nombreClavePrivada => 'Archivo de clave privada no encontrado',
            $this->wsaaWsdl => 'Archivo WSDL de WSAA no encontrado',
        ];

        foreach ($archivos as $archivo => $mensaje) {
            if (! file_exists($archivo)) {
                throw new ArchivoException(
                    sprintf('%s: %s', $mensaje, $archivo),
                    $archivo,
                    'read',
                    CodigosError::ARCHIVO_NO_ENCONTRADO->value
                );
            }
        }
    }

    /**
     * Construye la ruta completa del archivo donde se almacenará o leerá el Ticket de Acceso (TA)
     * para un servicio específico.
     *
     * La ruta incluye el CUIT, el nombre del servicio y un sufijo '-production' si el SDK
     * está configurado en modo de producción.
     *
     * @param  string  $servicio  El nombre del servicio AFIP (ej. 'wsfe', 'ws_sr_padron_a4').
     * @return string La ruta absoluta al archivo XML del TA.
     */
    private function obtenerRutaArchivoTA(string $servicio): string
    {
        $sufijo = $this->opciones['modo_produccion'] ? '-production' : '';

        return $this->carpetaTA.DIRECTORY_SEPARATOR.sprintf('TA-%d-%s%s.xml', $this->cuit, $servicio, $sufijo);
    }

    /**
     * Crea un archivo XML de Solicitud de Ticket de Acceso (TRA - Ticket Request Access).
     *
     * Este archivo contiene la información necesaria para solicitar un TA al WSAA, incluyendo
     * un ID único, tiempos de generación y expiración, y el nombre del servicio.
     *
     * @param  string  $servicio  El nombre del servicio AFIP para el cual se genera el TRA.
     * @return string La ruta absoluta al archivo TRA XML recién creado.
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

        $archivoTRA = $this->carpetaTA.DIRECTORY_SEPARATOR.sprintf('TRA-%d-%s.xml', $this->cuit, $servicio);
        $tra->asXML($archivoTRA);

        return $archivoTRA;
    }

    /**
     * Firma digitalmente un archivo TRA utilizando OpenSSL y el certificado/clave privada configurados.
     *
     * El resultado es una firma CMS (Cryptographic Message Syntax) que se utiliza para autenticarse
     * ante el WSAA.
     *
     * @param  string  $archivoTRA  La ruta absoluta al archivo TRA que se va a firmar.
     * @param  string  $servicio  El nombre del servicio AFIP asociado al TRA.
     * @return string La firma CMS codificada en base64.
     *
     * @throws AutenticacionException Si el proceso de firma con OpenSSL falla por cualquier razón.
     */
    private function firmarTRA(string $archivoTRA, string $servicio): string
    {
        $archivoTemporal = $this->carpetaTA.DIRECTORY_SEPARATOR.sprintf('TRA-%d-%s.tmp', $this->cuit, $servicio);

        $resultado = openssl_pkcs7_sign(
            $archivoTRA,
            $archivoTemporal,
            'file://'.$this->nombreCertificado,
            ['file://'.$this->nombreClavePrivada, $this->fraseClave],
            [],
            0
        );

        if (! $resultado) {
            throw new AutenticacionException(
                'Error al firmar el TRA',
                $servicio,
                null,
                CodigosError::AUTENTICACION_ERROR_FIRMAR_TRA->value
            );
        }

        $cms = $this->extraerCMSDeArchivoFirmado($archivoTemporal);

        unlink($archivoTRA);
        unlink($archivoTemporal);

        return $cms;
    }

    /**
     * Extrae el contenido CMS (Cryptographic Message Syntax) de un archivo firmado digitalmente.
     *
     * Este método busca el bloque PKCS7 dentro del archivo temporal generado por `openssl_pkcs7_sign`
     * y devuelve su contenido, eliminando los encabezados y saltos de línea.
     *
     * @param  string  $archivoTemporal  La ruta absoluta al archivo temporal que contiene la firma CMS.
     * @return string El contenido CMS extraído, listo para ser enviado al WSAA.
     *
     * @throws ArchivoException Si el archivo temporal no puede ser leído.
     * @throws AutenticacionException Si el formato del archivo firmado es inválido o el CMS no puede ser extraído.
     */
    private function extraerCMSDeArchivoFirmado(string $archivoTemporal): string
    {
        $contenidoArchivo = file_get_contents($archivoTemporal);

        if ($contenidoArchivo === false) {
            throw new ArchivoException(
                'No se puede leer el archivo TRA firmado',
                $archivoTemporal,
                'read',
                CodigosError::ARCHIVO_ERROR_LECTURA->value
            );
        }

        if (preg_match('/-----BEGIN PKCS7-----\s*(.*?)\s*-----END PKCS7-----/s', $contenidoArchivo, $matches)) {
            $cms = preg_replace('/\s+/', '', $matches[1]);

            if ($cms === null) {
                throw new AutenticacionException(
                    'Fallo al extraer el CMS del archivo firmado. Error en preg_replace.',
                    '',
                    null,
                    CodigosError::AUTENTICACION_GENERAL->value
                );
            }

            return $cms;
        }

        $segmentos = preg_split('/(\r\n\r\n|\n\n)/', $contenidoArchivo, 2);

        if (isset($segmentos[1])) {
            $cms = preg_replace('/\s+/', '', $segmentos[1]);

            if ($cms !== null && ! empty($cms)) {
                return $cms;
            }
        }

        throw new AutenticacionException(
            'Fallo al extraer el CMS del archivo firmado. Formato PKCS7 inválido.',
            '',
            null,
            CodigosError::AUTENTICACION_GENERAL->value
        );
    }

    /**
     * Realiza una solicitud al Web Service de Autenticación y Autorización (WSAA) de AFIP
     * para obtener el Ticket de Acceso (TA).
     *
     * Utiliza el CMS firmado previamente para autenticar la solicitud.
     *
     * @param  string  $cms  La firma CMS (Cryptographic Message Syntax) obtenida del TRA firmado.
     * @return string La respuesta XML completa del WSAA, que contiene el TA.
     *
     * @throws AutenticacionException Si ocurre un error durante la comunicación SOAP con el WSAA,
     *                                o si la respuesta del WSAA es inválida o no contiene el TA.
     */
    private function solicitarTADeWSAA(string $cms): string
    {
        $clienteSoap = new SoapClient($this->wsaaWsdl, [
            'soap_version' => SOAP_1_2,
            'location' => $this->urlWsaa,
            'trace' => 1,
            'exceptions' => $this->opciones['manejar_excepciones_soap'],
            'stream_context' => stream_context_create([
                'ssl' => [
                    'ciphers' => 'AES256-SHA',
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]),
        ]);

        $resultados = $clienteSoap->loginCms(['in0' => $cms]);

        if ($resultados instanceof SoapFault) {
            throw new AutenticacionException(
                sprintf('Fallo SOAP: %s%s%s', (string) $resultados->faultcode, PHP_EOL, $resultados->faultstring),
                'wsaa',
                null,
                CodigosError::AUTENTICACION_ERROR_WSAA->value
            );
        }

        if (is_object($resultados) && property_exists($resultados, 'loginCmsReturn')) {
            $loginCmsReturn = $resultados->loginCmsReturn;

            assert(is_string($loginCmsReturn) || is_numeric($loginCmsReturn));

            return (string) $loginCmsReturn;
        }

        throw new AutenticacionException(
            'Respuesta inválida del WSAA: no se encontró loginCmsReturn.',
            'wsaa',
            null,
            CodigosError::AUTENTICACION_ERROR_WSAA->value
        );
    }

    /**
     * Guarda el contenido XML de un Ticket de Acceso (TA) en un archivo en la carpeta configurada.
     *
     * El nombre del archivo se construye utilizando el CUIT, el nombre del servicio y el modo de producción.
     *
     * @param  string  $servicio  El nombre del servicio AFIP al que pertenece el TA.
     * @param  string  $ta  El contenido XML del Ticket de Acceso a guardar.
     * @return bool `true` si el archivo TA fue guardado exitosamente.
     *
     * @throws ArchivoException Si ocurre un error al intentar escribir el archivo TA en el disco.
     */
    private function guardarArchivoTA(string $servicio, string $ta): bool
    {
        $archivoTA = $this->obtenerRutaArchivoTA($servicio);

        if (in_array(file_put_contents($archivoTA, $ta), [0, false], true)) {
            throw new ArchivoException(
                'Error al escribir el archivo TA: '.$archivoTA,
                $archivoTA,
                'write',
                CodigosError::ARCHIVO_ERROR_ESCRITURA->value
            );
        }

        return true;
    }
}
