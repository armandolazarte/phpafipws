<?php

declare(strict_types=1);

namespace PhpAfipWs\Exception;

/**
 * Define códigos de error consistentes para las excepciones de la librería PhpAfipWs.
 */
class ErrorCodes
{
    // Códigos de error de Configuración (rango 1xx)
    public const CONFIG_INVALID_OPTION = 100;

    public const CONFIG_FILE_NOT_FOUND = 101;

    public const CONFIG_DIRECTORY_CREATE_FAILED = 102;

    public const CONFIG_FILE_WRITE_FAILED = 103;

    public const CONFIG_INVALID_WSDL_PATH = 104;

    public const CONFIG_MISSING_GENERIC_SERVICE_OPTION = 105;

    public const CONFIG_MISSING_SPECIFIC_SERVICE_DEFINITION = 106;

    public const CONFIG_INVALID_CURL_INIT = 107; // Si en algún momento se usa cURL para algo

    public const CONFIG_FILE_READ_FAILED = 108; // Para errores al leer archivos.

    // Códigos de error de Autenticación (rango 2xx)
    public const AUTH_TA_EXPIRED = 200;

    public const AUTH_TA_CORRUPT = 201;

    public const AUTH_TRA_GENERATION_FAILED = 202;

    public const AUTH_TRA_SIGNING_FAILED = 203;

    public const AUTH_CMS_EXTRACTION_FAILED = 204;

    public const AUTH_WSAA_SOAP_ERROR = 205; // Fallo SOAP específico de WSAA

    public const AUTH_WSAA_INVALID_RESPONSE = 206; // Respuesta WSAA no esperada

    public const AUTH_TA_READ_FAILED = 207;

    public const AUTH_TA_CREATION_FAILED = 208; // Código para el fallo general en la creación del TA.

    // Códigos de error de Web Service (rango 3xx)
    public const WEB_SERVICE_SOAP_FAULT = 300; // Fallo SOAP general en operaciones de WS (no WSAA)

    public const WEB_SERVICE_UNEXPECTED_RESPONSE = 301;

    public const WEB_SERVICE_CLIENT_INIT_FAILED = 302; // Fallo al inicializar SoapClient (WSDL/conexión)

    public const WEB_SERVICE_UNKNOWN_ERROR = 399; // Para errores no categorizados

    // Códigos de error de Validación (rango 4xx) - A usar en el futuro
    public const VALIDATION_INVALID_DATA = 400;

    public const VALIDATION_AFIP_ERROR = 401; // Errores de negocio específicos devueltos por AFIP (ej. errores de campos)
}
