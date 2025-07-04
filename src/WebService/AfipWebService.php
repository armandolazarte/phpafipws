<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use Exception;
use PhpAfipWs\Afip;
use PhpAfipWs\Auth\TokenAuthorization;
use PhpAfipWs\Exception\AuthException;
use PhpAfipWs\Exception\ConfigurationException;
use PhpAfipWs\Exception\ErrorCodes; // <--- Nuevo use
use PhpAfipWs\Exception\WebServiceException;
use PhpAfipWs\WebService\Contracts\AfipWebServiceInterface;
use SoapClient;
use SoapFault;

/**
 * Clase base para los Web Services de AFIP.
 *
 * Proporciona la funcionalidad común para la interacción con los servicios web de AFIP,
 * incluyendo la gestión del cliente SOAP, la obtención de la autorización de token
 * y el manejo de errores.
 */
class AfipWebService implements AfipWebServiceInterface
{
    /**
     * Versión de SOAP a utilizar. Por defecto, SOAP_1_2.
     */
    protected int $versionSoap = SOAP_1_2;

    /**
     * Nombre del archivo WSDL para el entorno de producción.
     */
    protected ?string $nombreWsdl = null;

    /**
     * URL del endpoint para el entorno de producción.
     */
    protected ?string $urlProduccion = null;

    /**
     * Nombre del archivo WSDL para el entorno de prueba.
     */
    protected ?string $nombreWsdlPrueba = null;

    /**
     * URL del endpoint para el entorno de prueba.
     */
    protected ?string $urlPrueba = null;

    /**
     * Instancia del cliente SOAP.
     */
    private ?SoapClient $clienteSoap = null;

    /**
     * Constructor de AfipWebService.
     *
     * @param  Afip  $afip  Instancia de la clase principal del SDK.
     * @param  array<string, mixed>  $options  Opciones de configuración específicas para este Web Service.
     */
    public function __construct(
        protected Afip $afip,
        private array $opciones = []
    ) {
        $this->inicializarWebService();
    }

    /**
     * Obtiene el Token de Autorización (TA) para el Web Service actual desde el WSAA.
     *
     * Utiliza la instancia de `Afip` para gestionar la obtención o renovación del TA.
     *
     * @return TokenAuthorization El objeto TokenAuthorization que contiene el token y la firma.
     *
     * @throws AuthException Si ocurre un error al obtener o crear el Token de Autorización (TA).
     * @throws ConfigurationException Si hay problemas con rutas de archivo o permisos.
     */
    public function getTokenAutorizacion(): TokenAuthorization
    {
        $service = $this->opciones['service'] ?? $this->getNombreServicio();

        /** @var string $service */
        return $this->afip->getServiceTA($service);
    }

    /**
     * Ejecuta una operación en el Web Service de AFIP.
     *
     * Inicializa el cliente SOAP si aún no lo está y envía la solicitud a la operación especificada.
     *
     * @param  string  $operation  El nombre de la operación del Web Service a ejecutar.
     * @param  array<string, mixed>  $parametros  Los parámetros a enviar en la solicitud.
     * @return mixed Los resultados de la operación.
     *
     * @throws WebServiceException Si ocurre un error en la solicitud SOAP o en la respuesta de AFIP.
     * @throws ConfigurationException Si el cliente SOAP no puede ser inicializado.
     */
    public function ejecutar(string $operacion, array $parametros = []): mixed
    {
        try {
            if (! $this->clienteSoap instanceof SoapClient) {
                $this->crearClienteSoap();
            }

            $resultados = $this->clienteSoap->{$operacion}($parametros);

            $this->verificarErrores($operacion, $resultados);

            return $resultados;
        } catch (SoapFault $e) {
            // Si $this->afip->getManejarExcepcionesSoap() es true, SoapClient lanzará SoapFault
            throw new WebServiceException(
                sprintf('Error de comunicación SOAP al ejecutar "%s": %s', $operacion, $e->getMessage()),
                ErrorCodes::WEB_SERVICE_SOAP_FAULT,
                $e->faultcode,
                $e
            );
        } catch (WebServiceException $e) {
            // Re-lanzar excepciones ya encapsuladas desde verificarErrores()
            throw $e;
        } catch (ConfigurationException $e) {
            // Re-lanzar excepciones de configuración
            throw $e;
        } catch (Exception $e) {
            // Capturar cualquier otra excepción inesperada
            throw new WebServiceException(
                sprintf('Error inesperado al ejecutar "%s": %s', $operacion, $e->getMessage()),
                ErrorCodes::WEB_SERVICE_UNKNOWN_ERROR,
                null,
                $e
            );
        }
    }

    /**
     * Obtiene el nombre del servicio para la autorización del token.
     *
     * Este método debe ser sobrescrito por las clases hijas para devolver
     * el nombre específico del servicio (ej. 'wsfe', 'ws_sr_padron_a4').
     * Si no se especifica en las opciones, devuelve un valor por defecto.
     *
     * @return string El nombre del servicio.
     */
    public function getNombreServicio(): string
    {
        return (string) ($this->opciones['service'] ?? 'ws_sr_padron_a5');
    }

    /**
     * Inicializa la configuración del Web Service (WSDL y URL)
     * basándose en si es un servicio genérico o específico y el entorno (producción/testing).
     *
     * @throws ConfigurationException Si las rutas del WSDL no son válidas.
     */
    private function inicializarWebService(): void
    {
        if (isset($this->opciones['generic']) && $this->opciones['generic'] === true) {
            $this->inicializarWebServiceGenerico();
        } else {
            $this->inicializarWebServiceEspecifico();
        }

        // Asegurarse de que el WSDL existe y es legible después de la inicialización
        if ($this->nombreWsdl === null) {
            throw new ConfigurationException('La ruta del WSDL es nula después de la inicialización. Esto no debería ocurrir.', ErrorCodes::CONFIG_INVALID_WSDL_PATH);
        }

        if (! file_exists($this->nombreWsdl)) {
            throw new ConfigurationException('No se pudo abrir el archivo WSDL: '.$this->nombreWsdl.'. Verifique la ruta y permisos.', ErrorCodes::CONFIG_FILE_NOT_FOUND);
        }
    }

    /**
     * Inicializa las propiedades WSDL y URL para un Web Service genérico.
     *
     * Requiere que se pasen opciones específicas como 'WSDL', 'URL', 'WSDL_PRUEBA', 'URL_PRUEBA' y 'service'.
     *
     * @throws ConfigurationException Si faltan opciones requeridas.
     */
    private function inicializarWebServiceGenerico(): void
    {
        $opcionesRequeridas = ['WSDL', 'URL', 'WSDL_PRUEBA', 'URL_PRUEBA', 'service'];

        foreach ($opcionesRequeridas as $opcionRequerida) {
            if (! isset($this->opciones[$opcionRequerida])) {
                throw new ConfigurationException(
                    sprintf('El campo %s es requerido en las opciones para un web service genérico', $opcionRequerida),
                    ErrorCodes::CONFIG_MISSING_GENERIC_SERVICE_OPTION
                );
            }
        }

        $baseWsdlPath = __DIR__.'/../Resources/'; // Asumiendo que WSDLs genéricos también están en Resources

        if ($this->afip->esProduccion()) {
            $this->nombreWsdl = $baseWsdlPath.$this->opciones['WSDL']; // Concatena con base path
            $this->urlProduccion = (string) ($this->opciones['URL']);
        } else {
            $this->nombreWsdl = $baseWsdlPath.$this->opciones['WSDL_PRUEBA']; // Concatena con base path
            $this->urlProduccion = (string) ($this->opciones['URL_PRUEBA']);
        }

        $this->versionSoap = (int) ($this->opciones['soap_version'] ?? SOAP_1_2);

        // Validar que el WSDL genérico existe en la ruta construida
        if (! file_exists($this->nombreWsdl)) {
            throw new ConfigurationException(
                sprintf('El WSDL genérico especificado no se encontró en: %s. Verifique la opción "WSDL" o "WSDL_PRUEBA".', $this->nombreWsdl),
                ErrorCodes::CONFIG_FILE_NOT_FOUND
            );
        }
    }

    /**
     * Inicializa las propiedades WSDL y URL para un Web Service específico.
     *
     * Utiliza las propiedades protegidas `$nombreWsdl`, `$urlProduccion`, `$nombreWsdlPrueba` y `$urlPrueba` definidas en la clase hija.
     * Asegura que estas propiedades (que son los nombres de archivo/URL base) no sean nulas.
     *
     * @throws ConfigurationException Si el nombre de archivo WSDL o la URL del endpoint no están definidos en la clase hija.
     */
    private function inicializarWebServiceEspecifico(): void
    {
        $nombreArchivoWsdlBase = $this->afip->esProduccion() ? $this->nombreWsdl : $this->nombreWsdlPrueba;
        $urlEndpoint = $this->afip->esProduccion() ? $this->urlProduccion : $this->urlPrueba;

        if ($nombreArchivoWsdlBase === null) {
            throw new ConfigurationException('El nombre de archivo WSDL no está definido para este servicio web. Verifique las propiedades de la clase hija.', ErrorCodes::CONFIG_MISSING_SPECIFIC_SERVICE_DEFINITION);
        }

        if ($urlEndpoint === null) {
            throw new ConfigurationException('La URL del endpoint no está definida para este servicio web. Verifique las propiedades de la clase hija.', ErrorCodes::CONFIG_MISSING_SPECIFIC_SERVICE_DEFINITION);
        }

        // Construir la ruta completa al WSDL
        $this->nombreWsdl = __DIR__.'/../Resources/'.$nombreArchivoWsdlBase;
        $this->urlProduccion = $urlEndpoint;
    }

    /**
     * Crea y configura una nueva instancia de SoapClient.
     *
     * Configura la versión de SOAP, la ubicación del servicio, el rastreo, el manejo de excepciones y el contexto del stream.
     *
     * @throws ConfigurationException Si el WSDL o la URL no están definidos después de la inicialización.
     */
    private function crearClienteSoap(): void
    {
        if ($this->nombreWsdl === null || $this->urlProduccion === null) {
            throw new ConfigurationException('WSDL o URL no están configurados para la inicialización de SoapClient. Esto no debería ocurrir.', ErrorCodes::CONFIG_INVALID_WSDL_PATH);
        }

        try {
            $this->clienteSoap = new SoapClient($this->nombreWsdl, [
                'soap_version' => $this->versionSoap,
                'location' => $this->urlProduccion,
                'trace' => 1,
                'exceptions' => $this->afip->getManejarExcepcionesSoap(),
                'stream_context' => stream_context_create([
                    'ssl' => [
                        'ciphers' => 'AES256-SHA',
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                    ],
                ]),
            ]);
        } catch (SoapFault $soapFault) {
            // Esto ocurre si el WSDL no se puede cargar o hay un problema de conexión al endpoint del WSDL
            throw new ConfigurationException(
                sprintf('No se pudo inicializar SoapClient con el WSDL %s. Posible problema de red o WSDL inválido. Error: %s', $this->nombreWsdl, $soapFault->getMessage()),
                ErrorCodes::WEB_SERVICE_CLIENT_INIT_FAILED,
                $soapFault
            );
        }
    }

    /**
     * Verifica si la respuesta de una solicitud al Web Service contiene errores SOAP.
     *
     * Este método se invoca después de cada llamada a una operación del Web Service
     * para detectar y lanzar excepciones si la respuesta indica un fallo SOAP.
     *
     * @param  string  $operation  El nombre de la operación que se ejecutó.
     * @param  mixed  $results  La respuesta recibida del Web Service de AFIP.
     *
     * @throws WebServiceException Si la respuesta es un fallo SOAP, encapsulando el código y mensaje del fallo.
     */
    private function verificarErrores(string $operacion, mixed $resultados): void
    {
        if (is_soap_fault($resultados)) {
            /** @var SoapFault $resultados */
            throw new WebServiceException(
                sprintf(
                    'Fallo SOAP en la operación "%s": %s%s%s',
                    $operacion,
                    (string) ($resultados->faultcode),
                    PHP_EOL,
                    (string) ($resultados->faultstring)
                ),
                ErrorCodes::WEB_SERVICE_SOAP_FAULT,
                $resultados->faultcode,
                new Exception($resultados->faultstring, (int) $resultados->faultcode)
            );
        }
    }
}
