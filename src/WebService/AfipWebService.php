<?php

declare(strict_types=1);

namespace PhpAfipWs\WebService;

use PhpAfipWs\Afip;
use PhpAfipWs\Authorization\TokenAuthorization;
use PhpAfipWs\Enums\CodigosError;
use PhpAfipWs\Exception\ArchivoException;
use PhpAfipWs\Exception\AutenticacionException;
use PhpAfipWs\Exception\SoapException;
use PhpAfipWs\Exception\WebServiceException;
use SoapClient;
use SoapFault;

/**
 * Clase base para los Web Services de AFIP.
 *
 * Proporciona la funcionalidad común para la interacción con los servicios web de AFIP,
 * incluyendo la gestión del cliente SOAP, la obtención de la autorización de token
 * y el manejo de errores.
 */
class AfipWebService
{
    /**
     * Versión de SOAP a utilizar. Por defecto, SOAP_1_2.
     */
    protected int $versionSoap = SOAP_1_2;

    /**
     * Ruta al archivo WSDL para el entorno de producción.
     */
    protected ?string $nombreArchivoWSDL = null;

    /**
     * URL del endpoint para el entorno de producción.
     */
    protected ?string $urlServicio = null;

    /**
     * Ruta al archivo WSDL para el entorno de testing.
     */
    protected ?string $nombreArchivoWSDLPrueba = null;

    /**
     * URL del endpoint para el entorno de testing.
     */
    protected ?string $urlServicioPrueba = null;

    /**
     * Instancia del cliente SOAP.
     */
    private ?SoapClient $clienteSoap = null;

    /**
     * Constructor de la clase `AfipWebService`.
     *
     * @param  Afip  $afip  Instancia de la clase principal del SDK, que proporciona acceso a la configuración global y la gestión de TA.
     * @param  array<string, mixed>  $opciones  Opciones de configuración específicas para este Web Service.
     *                                          Puede incluir `servicio` (nombre del servicio), `WSDL`, `URL`, `WSDL_TEST`, `URL_TEST`
     *                                          y `soap_version` para servicios genéricos.
     */
    public function __construct(
        protected Afip $afip,
        private array $opciones = []
    ) {
        $this->inicializarWebService();
    }

    /**
     * Obtiene el Token de Autorización (TA) necesario para interactuar con el Web Service de AFIP.
     *
     * Este método delega la lógica de obtención y gestión del TA a la instancia de `Afip`,
     * asegurando que se utilice un TA válido y no expirado.
     *
     * @return TokenAuthorization Una instancia de `TokenAuthorization` que contiene el token y la firma digital.
     *
     * @throws AutenticacionException Si ocurre un error durante el proceso de obtención o renovación del TA
     *                                (ej. problemas con certificados, comunicación con WSAA, etc.).
     */
    public function obtenerTokenAutorizacion(): TokenAuthorization
    {
        $servicio = $this->opciones['servicio'] ?? $this->obtenerNombreServicio();

        /** @var string $servicio */
        return $this->afip->obtenerTAServicio($servicio);
    }

    /**
     * Ejecuta una operación específica en el Web Service de AFIP.
     *
     * Este método se encarga de inicializar el cliente SOAP si aún no ha sido creado,
     * enviar la solicitud a la operación deseada con los parámetros proporcionados,
     * y verificar si la respuesta contiene errores SOAP.
     *
     * @param  string  $operacion  El nombre de la operación del Web Service a invocar (ej. 'FECAESolicitar').
     * @param  array<string, mixed>  $parametros  Un array asociativo con los parámetros requeridos por la operación.
     * @return mixed Los resultados de la operación, tal como son devueltos por el Web Service.
     *
     * @throws SoapException Si la comunicación con el Web Service falla o si la respuesta es un fallo SOAP.
     * @throws WebServiceException Si el cliente SOAP no puede ser creado o si hay un problema general con el servicio.
     */
    public function ejecutarSolicitud(string $operacion, array $parametros = []): mixed
    {
        if (! $this->clienteSoap instanceof SoapClient) {
            $this->crearClienteSoap();
        }

        $resultados = $this->clienteSoap->{$operacion}($parametros);

        $this->verificarErrores($operacion, $resultados);

        return $resultados;
    }

    /**
     * Obtiene el nombre del servicio AFIP asociado a esta instancia de `AfipWebService`.
     *
     * Este nombre se utiliza principalmente para la gestión del Ticket de Acceso (TA).
     * Las clases hijas deben sobrescribir este método para devolver el nombre específico
     * del servicio (ej. 'wsfe', 'ws_sr_padron_a4'). Si no se define en las opciones,
     * se devuelve un valor por defecto ('ws_sr_padron_a5').
     *
     * @return string El nombre del servicio AFIP.
     */
    protected function obtenerNombreServicio(): string
    {
        $servicio = $this->opciones['servicio'] ?? 'ws_sr_padron_a5';

        assert(is_string($servicio) || is_numeric($servicio));

        return (string) $servicio;
    }

    /**
     * Inicializa la configuración del Web Service (WSDL y URL del endpoint) para la instancia actual.
     *
     * Este método determina si el servicio es genérico o específico y carga las rutas WSDL y URLs
     * correspondientes al entorno (producción o testing).
     *
     * @throws WebServiceException Si la ruta del WSDL es nula después de la inicialización.
     * @throws ArchivoException Si el archivo WSDL no se encuentra en la ruta especificada.
     */
    private function inicializarWebService(): void
    {
        if (isset($this->opciones['generico']) && $this->opciones['generico'] === true) {
            $this->inicializarWebServiceGenerico();
        } else {
            $this->inicializarWebServiceEspecifico();
        }

        if ($this->nombreArchivoWSDL === null) {
            throw new WebServiceException(
                'La ruta del WSDL es nula después de la inicialización. Esto no debería ocurrir.',
                $this->obtenerNombreServicio(),
                'inicializar_wsdl',
                null,
                CodigosError::SERVICIO_WEB_GENERAL->value
            );
        }

        if (! file_exists($this->nombreArchivoWSDL)) {
            throw new ArchivoException(
                'No se pudo abrir el archivo WSDL: '.$this->nombreArchivoWSDL,
                $this->nombreArchivoWSDL,
                'read',
                CodigosError::SOAP_WSDL_NO_ENCONTRADO->value
            );
        }
    }

    /**
     * Inicializa las propiedades WSDL y URL para un Web Service genérico.
     *
     * Requiere que se pasen opciones específicas como 'WSDL', 'URL', 'WSDL_TEST', 'URL_TEST' y 'servicio'.
     *
     * @throws WebServiceException Si alguna de las opciones requeridas no está presente.
     */
    private function inicializarWebServiceGenerico(): void
    {
        $opcionesRequeridas = ['WSDL', 'URL', 'WSDL_TEST', 'URL_TEST', 'servicio'];

        foreach ($opcionesRequeridas as $opcionRequerida) {
            if (! isset($this->opciones[$opcionRequerida])) {
                throw new WebServiceException(
                    sprintf('El campo %s es requerido en las opciones para un web service genérico', $opcionRequerida),
                    $this->obtenerNombreServicio(),
                    'configurar_servicio_generico',
                    $this->opciones,
                    CodigosError::SERVICIO_WEB_PARAMETROS_INVALIDOS->value
                );
            }
        }

        if ($this->afip->esModoProduccion()) {
            $wsdl = $this->opciones['WSDL'];
            $url = $this->opciones['URL'];

            assert(is_string($wsdl) || is_numeric($wsdl));
            assert(is_string($url) || is_numeric($url));

            $this->nombreArchivoWSDL = (string) $wsdl;
            $this->urlServicio = (string) $url;
        } else {
            $wsdlTest = $this->opciones['WSDL_TEST'];
            $urlTest = $this->opciones['URL_TEST'];

            assert(is_string($wsdlTest) || is_numeric($wsdlTest));
            assert(is_string($urlTest) || is_numeric($urlTest));

            $this->nombreArchivoWSDL = (string) $wsdlTest;
            $this->urlServicio = (string) $urlTest;
        }

        $versionSoap = $this->opciones['soap_version'] ?? SOAP_1_2;

        assert(is_int($versionSoap) || is_numeric($versionSoap));

        $this->versionSoap = (int) $versionSoap;
    }

    /**
     * Inicializa las propiedades WSDL y URL para un Web Service específico.
     *
     * Utiliza las propiedades protegidas `$nombreArchivoWSDL`, `$urlServicio`, `$nombreArchivoWSDLPrueba` y `$urlServicioPrueba` definidas en la clase hija.
     * Asegura que estas propiedades (que son los nombres de archivo/URL base) no sean nulas.
     *
     * @throws WebServiceException Si el nombre de archivo WSDL o la URL del endpoint no están definidos en la clase hija
     *                             o si el archivo WSDL no se encuentra en las rutas configuradas.
     */
    private function inicializarWebServiceEspecifico(): void
    {
        $nombreArchivoWSDL = $this->afip->esModoProduccion() ? $this->nombreArchivoWSDL : $this->nombreArchivoWSDLPrueba;
        $urlServicio = $this->afip->esModoProduccion() ? $this->urlServicio : $this->urlServicioPrueba;

        if ($nombreArchivoWSDL === null) {
            throw new WebServiceException(
                'El nombre de archivo WSDL no está definido para este servicio web. Verifique las propiedades de la clase hija.',
                $this->obtenerNombreServicio(),
                'configurar_wsdl',
                null,
                CodigosError::SERVICIO_WEB_GENERAL->value
            );
        }

        if ($urlServicio === null) {
            throw new WebServiceException(
                'La URL del endpoint no está definida para este servicio web. Verifique las propiedades de la clase hija.',
                $this->obtenerNombreServicio(),
                'configurar_endpoint',
                null,
                CodigosError::SERVICIO_WEB_GENERAL->value
            );
        }

        $rutaWsdlPersonalizada = ($this->afip->getCarpetaWsdl() !== null
            && $this->afip->getCarpetaWsdl() !== ''
            && $this->afip->getCarpetaWsdl() !== '0')
            ? mb_rtrim($this->afip->getCarpetaWsdl(), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$nombreArchivoWSDL
            : null;

        $rutaWsdlPorDefecto = __DIR__.DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'Resources'.DIRECTORY_SEPARATOR.$nombreArchivoWSDL;

        if ($rutaWsdlPersonalizada !== null && file_exists($rutaWsdlPersonalizada)) {
            $this->nombreArchivoWSDL = $rutaWsdlPersonalizada;
        } elseif (file_exists($rutaWsdlPorDefecto)) {
            $this->nombreArchivoWSDL = $rutaWsdlPorDefecto;
        } else {
            throw new WebServiceException(
                sprintf('El archivo WSDL "%s" no fue encontrado en ninguna de las rutas configuradas.', $nombreArchivoWSDL),
                $this->obtenerNombreServicio(),
                'configurar_wsdl',
                ['ruta_personalizada' => $rutaWsdlPersonalizada, 'ruta_por_defecto' => $rutaWsdlPorDefecto],
                CodigosError::SOAP_WSDL_NO_ENCONTRADO->value
            );
        }

        $this->urlServicio = $urlServicio;
    }

    /**
     * Crea y configura una nueva instancia de SoapClient.
     *
     * Configura la versión de SOAP, la ubicación del servicio, el rastreo y el manejo de excepciones.
     *
     * @throws WebServiceException Si el WSDL o la URL no están definidos después de la inicialización.
     */
    private function crearClienteSoap(): void
    {
        if ($this->nombreArchivoWSDL === null || $this->urlServicio === null) {
            throw new WebServiceException(
                'WSDL o URL no están configurados para la inicialización de SoapClient.',
                $this->obtenerNombreServicio(),
                'crear_cliente_soap',
                ['wsdl' => $this->nombreArchivoWSDL, 'url' => $this->urlServicio],
                CodigosError::SERVICIO_WEB_GENERAL->value
            );
        }

        $this->clienteSoap = new SoapClient($this->nombreArchivoWSDL, [
            'soap_version' => $this->versionSoap,
            'location' => $this->urlServicio,
            'trace' => 1,
            'exceptions' => $this->afip->opciones['manejar_Exceptiones_soap'] ?? false,
            'stream_context' => stream_context_create([
                'ssl' => [
                    'ciphers' => 'AES256-SHA',
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                ],
            ]),
        ]);
    }

    /**
     * Verifica si la respuesta de una solicitud al Web Service contiene errores SOAP.
     *
     * Este método se invoca después de cada llamada a una operación del Web Service
     * para detectar y lanzar excepciones si la respuesta indica un fallo SOAP.
     *
     * @param  string  $operacion  El nombre de la operación que se ejecutó.
     * @param  mixed  $resultados  La respuesta recibida del Web Service de AFIP.
     *
     * @throws SoapException Si la respuesta es un fallo SOAP, encapsulando el código y mensaje del fallo.
     */
    private function verificarErrores(string $operacion, mixed $resultados): void
    {
        if (is_soap_fault($resultados)) {
            /** @var SoapFault $resultados */
            throw new SoapException(
                sprintf('Fallo SOAP en %s: %s%s%s', $operacion, (string) $resultados->faultcode, PHP_EOL, (string) $resultados->faultstring),
                (string) $resultados->faultcode,
                (string) $resultados->faultstring,
                $operacion,
                CodigosError::SOAP_FALLO_COMUNICACION->value
            );
        }
    }
}
