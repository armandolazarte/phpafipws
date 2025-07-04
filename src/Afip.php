<?php

declare(strict_types=1);

namespace PhpAfipWs;

use DateTimeImmutable;
use PhpAfipWs\Auth\TokenAuthorization;
use PhpAfipWs\Exception\AfipException;
use PhpAfipWs\WebService\Contracts\AfipWebServiceInterface;
use PhpAfipWs\WebService\Contracts\FacturacionElectronicaInterface;
use PhpAfipWs\WebService\Contracts\PadronAlcanceCincoInterface;
use PhpAfipWs\WebService\Contracts\PadronAlcanceCuatroInterface;
use PhpAfipWs\WebService\Contracts\PadronAlcanceDiezInterface;
use PhpAfipWs\WebService\Contracts\PadronAlcanceTreceInterface;
use PhpAfipWs\WebService\Contracts\PadronConstanciaInscripcionInterface;
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
 * @property-read FacturacionElectronicaInterface $FacturacionElectronica
 * @property-read PadronAlcanceCuatroInterface $PadronAlcanceCuatro
 * @property-read PadronAlcanceCincoInterface $PadronAlcanceCinco
 * @property-read PadronAlcanceDiezInterface $PadronAlcanceDiez
 * @property-read PadronAlcanceTreceInterface $PadronAlcanceTrece
 * @property-read PadronConstanciaInscripcionInterface $PadronConstanciaInscripcion
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
     * Fábricas para instanciar los servicios web.
     *
     * @var array<string, callable(Afip): AfipWebServiceInterface>
     */
    private array $serviceFactories;

    /**
     * Constructor de Afip.
     *
     * @param  array<string, mixed>  $opciones  Opciones de configuración para la libreria PhpAfipWs.
     * @param  array<string, callable(Afip): AfipWebServiceInterface>  $serviceFactories  Opcional. Permite inyectar fábricas para los Web Services.
     *
     * @throws AfipException Si las opciones son inválidas o los archivos requeridos no se encuentran.
     */
    public function __construct(array $opciones, array $serviceFactories = [])
    {
        ini_set('soap.wsdl_cache_enabled', '0');

        $this->inicializarOpciones($opciones);
        $this->configurarRutas();
        $this->validarArchivos();

        $this->serviceFactories = $serviceFactories;

        if (empty($this->serviceFactories)) {
            $this->registerDefaultServiceFactories();
        }
    }

    /**
     * Método mágico para acceder a los clientes de Web Service como propiedades.
     *
     * @param  string  $propiedad  El nombre del Web Service o propiedad a acceder.
     * @return AfipWebServiceInterface|mixed La instancia del Web Service solicitado o el valor de la propiedad.
     *
     * @throws AfipException Si la propiedad o el Web Service no existe o su clase no se encuentra.
     */
    public function __get(string $propiedad): mixed
    {
        if (in_array($propiedad, self::WS_IMPLEMENTADOS)) {
            if (! isset($this->instanciasWebService[$propiedad])) {
                if (! isset($this->serviceFactories[$propiedad])) {
                    throw new AfipException(sprintf('No se encontró la fábrica para el WebService %s', $propiedad), 1);
                }

                $this->instanciasWebService[$propiedad] = $this->serviceFactories[$propiedad]($this);
            }

            return $this->instanciasWebService[$propiedad];
        }

        if (property_exists($this, $propiedad)) {
            return $this->{$propiedad};
        }

        throw new AfipException(sprintf('La propiedad %s no existe', $propiedad), 2);
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
     * @throws AfipException Si el TA ha expirado y no puede ser renovado, o si no se puede crear uno nuevo.
     */
    public function getServiceTA(string $servicio, bool $continuar = true): TokenAuthorization
    {
        $archivoTa = $this->getRutaArchivoTA($servicio);

        if (file_exists($archivoTa)) {
            $contenidoTa = file_get_contents($archivoTa);

            if ($contenidoTa !== false) {
                $ta = new SimpleXMLElement($contenidoTa);

                $tiempoActual = new DateTimeImmutable(date('c', time() + 600));
                $tiempoExpiracion = new DateTimeImmutable((string) $ta->header->expirationTime);

                if ($tiempoActual < $tiempoExpiracion) {
                    return new TokenAuthorization(
                        (string) $ta->credentials->token,
                        (string) $ta->credentials->sign
                    );
                }

                if (! $continuar) {
                    throw new AfipException('Error al obtener el TA - El token ha expirado', 5);
                }
            }
        }

        if ($this->crearServiceTA($servicio)) {
            return $this->getServiceTA($servicio, false);
        }

        throw new AfipException('No se pudo crear el TA del servicio', 6);
    }

    /**
     * Crea un cliente de Web Service genérico para servicios no implementados explícitamente.
     *
     * @param  string  $servicio  El nombre del servicio.
     * @param  array<string, mixed>  $opciones  Opciones de configuración para el servicio genérico.
     * @return AfipWebServiceInterface Una instancia del cliente de web service genérico.
     *
     * @throws AfipException si ocurre un error.
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
     * @throws AfipException Si ocurre un error durante el proceso de creación (generación de TRA, firma o solicitud al WSAA).
     */
    protected function crearServiceTA(string $servicio): bool
    {
        if (! is_dir($this->carpetaTa) && ! mkdir($this->carpetaTa, 0777, true)) {
            throw new AfipException(sprintf('No se pudo crear la carpeta para los TA: %s', $this->carpetaTa));
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
            'FacturacionElectronica' => fn (Afip $afip) => new FacturacionElectronica($afip),
            'PadronAlcanceCuatro' => fn (Afip $afip) => new PadronAlcanceCuatro($afip),
            'PadronAlcanceCinco' => fn (Afip $afip) => new PadronAlcanceCinco($afip),
            'PadronAlcanceDiez' => fn (Afip $afip) => new PadronAlcanceDiez($afip),
            'PadronAlcanceTrece' => fn (Afip $afip) => new PadronAlcanceTrece($afip),
            'PadronConstanciaInscripcion' => fn (Afip $afip) => new PadronConstanciaInscripcion($afip),
        ];
    }

    /**
     * Inicializa las propiedades desde el array de opciones.
     *
     * @param  array<string, mixed>  $opciones  Las opciones de configuración.
     *
     * @throws AfipException
     */
    private function inicializarOpciones(array $opciones): void
    {
        if (! isset($opciones['cuit'])) {
            throw new AfipException('El campo CUIT es requerido en el array de opciones');
        }

        if (! is_numeric($opciones['cuit'])) {
            throw new AfipException('El CUIT debe ser numérico');
        }

        $this->cuit = (int) $opciones['cuit'];

        $this->opciones = array_merge([
            'modo_produccion' => false,
            'contrasena_clave' => 'xxxxx',
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
     */
    private function configurarRutas(): void
    {
        $carpetaDefault = __DIR__.'/Resources/';

        $this->carpetaRecursos = (string) ($this->opciones['carpeta_recursos'] ?? $carpetaDefault);
        $this->carpetaTa = (string) ($this->opciones['carpeta_ta'] ?? $carpetaDefault);

        $this->rutaCertificado = $this->carpetaRecursos.$this->opciones['ruta_certificado'];
        $this->rutaClavePrivada = $this->carpetaRecursos.$this->opciones['ruta_clave'];
        $this->wsaaWsdl = __DIR__.'/Resources/wsaa.wsdl';

        $this->wsaaUrl = $this->opciones['modo_produccion']
            ? self::URL_WSAA_PRODUCCION
            : self::URL_WSAA_PRUEBA;
    }

    /**
     * Valida que todos los archivos requeridos existan.
     *
     * @throws AfipException Si no se encuentra un archivo requerido.
     */
    private function validarArchivos(): void
    {
        $archivos = [
            $this->rutaCertificado => 'Archivo de certificado no encontrado',
            $this->rutaClavePrivada => 'Archivo de clave privada no encontrado',
            $this->wsaaWsdl => 'Archivo WSDL de WSAA no encontrado',
        ];

        foreach ($archivos as $archivo => $mensaje) {
            if (! file_exists($archivo)) {
                throw new AfipException(sprintf('%s: %s', $mensaje, $archivo));
            }
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
        $tra->asXML($archivoTra);

        return $archivoTra;
    }

    /**
     * Firma un archivo de Solicitud de Ticket de Acceso (TRA) usando OpenSSL.
     *
     * @param  string  $archivoTra  La ruta al archivo TRA.
     * @param  string  $servicio  El nombre del servicio.
     * @return string La firma CMS (Cryptographic Message Syntax).
     *
     * @throws AfipException Si la firma falla.
     */
    private function firmarTRA(string $archivoTra, string $servicio): string
    {
        $archivoTemp = $this->carpetaTa.sprintf('TRA-%d-%s.tmp', $this->cuit, $servicio);

        $estado = openssl_pkcs7_sign(
            $archivoTra,
            $archivoTemp,
            'file://'.$this->rutaCertificado,
            ['file://'.$this->rutaClavePrivada, $this->contraseñaClave],
            [],
            0
        );

        if (! $estado) {
            throw new AfipException('Error al firmar el TRA');
        }

        $cms = $this->extraerCMSDeArchivoFirmado($archivoTemp);

        unlink($archivoTra);
        unlink($archivoTemp);

        return $cms;
    }

    /**
     * Extrae el contenido CMS de un archivo firmado.
     *
     * @param  string  $archivoTemp  La ruta al archivo temporal firmado.
     * @return string El contenido CMS extraído.
     *
     * @throws AfipException Si el archivo no puede ser leído o el CMS no puede ser extraído.
     */
    private function extraerCMSDeArchivoFirmado(string $archivoTemp): string
    {
        $contenidoArchivo = file_get_contents($archivoTemp);

        if ($contenidoArchivo === false) {
            throw new AfipException('No se puede leer el archivo TRA firmado');
        }

        if (preg_match('/-----BEGIN PKCS7-----\s*(.*?)\s*-----END PKCS7-----/s', $contenidoArchivo, $matches)) {
            $cms = preg_replace('/\s+/', '', $matches[1]);

            if ($cms === null) {
                throw new AfipException('Fallo al extraer el CMS del archivo firmado. Error en preg_replace.');
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

        throw new AfipException('Fallo al extraer el CMS del archivo firmado. Formato PKCS7 inválido.');
    }

    /**
     * Solicita el Ticket de Acceso (TA) al servicio WSAA.
     *
     * @param  string  $cmsFirmado  La solicitud CMS firmada.
     * @return string La respuesta XML del WSAA que contiene el TA.
     *
     * @throws AfipException Si ocurre un fallo de SOAP.
     */
    private function solicitarTADeWSAA(string $cmsFirmado): string
    {
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

        if ($resultados instanceof SoapFault) {
            throw new AfipException(
                sprintf('Fallo SOAP: %s%s%s', (string) ($resultados->faultcode), PHP_EOL, $resultados->faultstring),
                4
            );
        }

        if (is_object($resultados) && property_exists($resultados, 'loginCmsReturn')) {
            return (string) ($resultados->loginCmsReturn);
        }

        throw new AfipException('Respuesta inválida del WSAA: no se encontró loginCmsReturn.');
    }

    /**
     * Guarda el Ticket de Acceso (TA) en un archivo.
     *
     * @param  string  $servicio  El nombre del servicio.
     * @param  string  $ta  El contenido XML del TA.
     * @return bool True en caso de éxito.
     */
    private function guardarArchivoTA(string $servicio, string $ta): bool
    {
        $archivoTa = $this->getRutaArchivoTA($servicio);

        if (file_put_contents($archivoTa, $ta) === false) {
            throw new AfipException('Error al escribir el archivo TA: '.$archivoTa, 5);
        }

        return true;
    }
}
