<?php

declare(strict_types=1);

namespace PhpAfipWs\Enums;

/**
 * Códigos de error estandarizados del SDK de AFIP.
 *
 * Utiliza un Enum para representar un conjunto fijo de errores,
 * lo que proporciona seguridad de tipos y una mejor organización.
 */
enum CodigosError: int
{
    // ========================================
    // ERRORES DE CONFIGURACIÓN (1xxx)
    // ========================================
    /** Error genérico de configuración. */
    case CONFIGURACION_GENERAL = 1000;

    /** Campo de configuración requerido faltante. */
    case CONFIGURACION_CAMPO_REQUERIDO = 1001;

    /** Valor de configuración inválido. */
    case CONFIGURACION_VALOR_INVALIDO = 1002;

    /** Archivo de configuración no encontrado. */
    case CONFIGURACION_ARCHIVO_NO_ENCONTRADO = 1003;

    /** Formato de configuración inválido. */
    case CONFIGURACION_FORMATO_INVALIDO = 1004;

    /** Configuración de entorno inválida. */
    case CONFIGURACION_ENTORNO_INVALIDO = 1005;

    /** Dependencia de configuración faltante. */
    case CONFIGURACION_DEPENDENCIA_FALTANTE = 1006;

    /** Error interno de configuración. */
    case CONFIGURACION_ERROR_INTERNO = 1007;

    // ========================================
    // ERRORES DE VALIDACIÓN (2xxx)
    // ========================================
    /** Error genérico de validación. */
    case VALIDACION_GENERAL = 2000;

    /** CUIT inválido. */
    case VALIDACION_CUIT_INVALIDO = 2001;

    /** Formato de fecha inválido. */
    case VALIDACION_FECHA_INVALIDA = 2002;

    /** Parámetro requerido faltante. */
    case VALIDACION_PARAMETRO_REQUERIDO = 2003;

    /** Tipo de dato incorrecto. */
    case VALIDACION_TIPO_INCORRECTO = 2004;

    /** Valor fuera de rango permitido. */
    case VALIDACION_FUERA_DE_RANGO = 2005;

    /** Formato de string inválido. */
    case VALIDACION_FORMATO_STRING = 2006;

    /** Longitud de campo inválida. */
    case VALIDACION_LONGITUD_INVALIDA = 2007;

    /** DN (Distinguished Name) incompleto. */
    case VALIDACION_DN_INCOMPLETO = 2008;

    /** Formato de CUIT inválido. */
    case VALIDACION_FORMATO_CUIT = 2009;

    /** Parámetro inválido. */
    case VALIDACION_PARAMETRO_INVALIDO = 2010;

    // ========================================
    // ERRORES DE ARCHIVOS (3xxx)
    // ========================================
    /** Error genérico de archivo. */
    case ARCHIVO_GENERAL = 3000;

    /** Archivo no encontrado. */
    case ARCHIVO_NO_ENCONTRADO = 3001;

    /** Error de lectura de archivo. */
    case ARCHIVO_ERROR_LECTURA = 3002;

    /** Error de escritura de archivo. */
    case ARCHIVO_ERROR_ESCRITURA = 3003;

    /** Permisos insuficientes para archivo. */
    case ARCHIVO_PERMISOS_INSUFICIENTES = 3004;

    /** Formato de archivo inválido. */
    case ARCHIVO_FORMATO_INVALIDO = 3005;

    /** Archivo de certificado inválido. */
    case ARCHIVO_CERTIFICADO_INVALIDO = 3006;

    /** Archivo de clave privada inválido. */
    case ARCHIVO_CLAVE_PRIVADA_INVALIDA = 3007;

    // ========================================
    // ERRORES DE AUTENTICACIÓN (4xxx)
    // ========================================
    /** Error genérico de autenticación. */
    case AUTENTICACION_GENERAL = 4000;

    /** Token de acceso expirado. */
    case AUTENTICACION_TOKEN_EXPIRADO = 4001;

    /** Token de acceso inválido. */
    case AUTENTICACION_TOKEN_INVALIDO = 4002;

    /** Error al crear ticket de acceso (TA). */
    case AUTENTICACION_ERROR_CREAR_TA = 4003;

    /** Error al firmar ticket de requerimiento de acceso (TRA). */
    case AUTENTICACION_ERROR_FIRMAR_TRA = 4004;

    /** Certificado expirado. */
    case AUTENTICACION_CERTIFICADO_EXPIRADO = 4005;

    /** Certificado inválido. */
    case AUTENTICACION_CERTIFICADO_INVALIDO = 4006;

    /** Error de comunicación con WSAA. */
    case AUTENTICACION_ERROR_WSAA = 4007;

    // ========================================
    // ERRORES SOAP (5xxx)
    // ========================================
    /** Error genérico SOAP. */
    case SOAP_GENERAL = 5000;

    /** Fallo en comunicación SOAP. */
    case SOAP_FALLO_COMUNICACION = 5001;

    /** Respuesta SOAP inválida. */
    case SOAP_RESPUESTA_INVALIDA = 5002;

    /** Timeout en operación SOAP. */
    case SOAP_TIMEOUT = 5003;

    /** Error de conexión SOAP. */
    case SOAP_ERROR_CONEXION = 5004;

    /** Archivo WSDL no encontrado. */
    case SOAP_WSDL_NO_ENCONTRADO = 5005;

    /** Archivo WSDL inválido. */
    case SOAP_WSDL_INVALIDO = 5006;

    /** Operación SOAP no encontrada. */
    case SOAP_OPERACION_NO_ENCONTRADA = 5007;

    // ========================================
    // ERRORES DE SERVICIOS WEB (6xxx)
    // ========================================
    /** Error genérico de servicio web. */
    case SERVICIO_WEB_GENERAL = 6000;

    /** Clase de servicio web no encontrada. */
    case SERVICIO_WEB_CLASE_NO_ENCONTRADA = 6001;

    /** Método de servicio web no encontrado. */
    case SERVICIO_WEB_METODO_NO_ENCONTRADO = 6002;

    /** Parámetros de servicio web inválidos. */
    case SERVICIO_WEB_PARAMETROS_INVALIDOS = 6003;

    /** Respuesta de servicio web inválida. */
    case SERVICIO_WEB_RESPUESTA_INVALIDA = 6004;

    /** Servicio web no disponible. */
    case SERVICIO_WEB_NO_DISPONIBLE = 6005;

    /** Error específico de facturación electrónica. */
    case SERVICIO_WEB_FACTURACION_ERROR = 6100;

    /** Error específico de padrón. */
    case SERVICIO_WEB_PADRON_ERROR = 6200;

    /** Error específico de constancia de inscripción. */
    case SERVICIO_WEB_CONSTANCIA_ERROR = 6300;

    // ========================================
    // ERRORES DE CERTIFICADOS Y CSR (7xxx)
    // ========================================
    /** Error al generar CSR. */
    case CERTIFICADO_ERROR_GENERAR_CSR = 7001;

    /** Error al exportar CSR. */
    case CERTIFICADO_ERROR_EXPORTAR_CSR = 7002;

    /** Error al leer CSR. */
    case CERTIFICADO_ERROR_LEER_CSR = 7003;

    /** Error al leer certificado. */
    case CERTIFICADO_ERROR_LEER_CERTIFICADO = 7004;

    /** Error al generar clave. */
    case CERTIFICADO_ERROR_GENERAR_CLAVE = 7005;

    /** Error al exportar clave. */
    case CERTIFICADO_ERROR_EXPORTAR_CLAVE = 7006;

    /** Clave inválida. */
    case CERTIFICADO_CLAVE_INVALIDA = 7007;

    /** Tipo de clave inválido. */
    case CERTIFICADO_TIPO_CLAVE_INVALIDO = 7008;

    /** Tamaño de clave insuficiente. */
    case CERTIFICADO_TAMAÑO_CLAVE_INSUFICIENTE = 7009;

    /** Clave pública inválida. */
    case CERTIFICADO_CLAVE_PUBLICA_INVALIDA = 7010;

    /** Error al firmar certificado. */
    case CERTIFICADO_ERROR_FIRMAR = 7011;

    /** Error al verificar certificado. */
    case CERTIFICADO_ERROR_VERIFICAR = 7012;

    // ----------------------------------------
    // MÉTODOS DEL ENUM
    // ----------------------------------------

    /**
     * Obtiene la descripción de este código de error.
     */
    public function obtenerDescripcion(): string
    {
        return match ($this) {
            self::CONFIGURACION_GENERAL => 'Error genérico de configuración',
            self::CONFIGURACION_CAMPO_REQUERIDO => 'Campo de configuración requerido faltante',
            self::CONFIGURACION_VALOR_INVALIDO => 'Valor de configuración inválido',
            self::CONFIGURACION_ARCHIVO_NO_ENCONTRADO => 'Archivo de configuración no encontrado',
            self::CONFIGURACION_FORMATO_INVALIDO => 'Formato de configuración inválido',
            self::CONFIGURACION_ENTORNO_INVALIDO => 'Configuración de entorno inválida',
            self::CONFIGURACION_DEPENDENCIA_FALTANTE => 'Dependencia de configuración faltante',
            self::CONFIGURACION_ERROR_INTERNO => 'Error interno de configuración',
            self::VALIDACION_GENERAL => 'Error genérico de validación',
            self::VALIDACION_CUIT_INVALIDO => 'CUIT inválido',
            self::VALIDACION_FECHA_INVALIDA => 'Formato de fecha inválido',
            self::VALIDACION_PARAMETRO_REQUERIDO => 'Parámetro requerido faltante',
            self::VALIDACION_TIPO_INCORRECTO => 'Tipo de dato incorrecto',
            self::VALIDACION_FUERA_DE_RANGO => 'Valor fuera de rango permitido',
            self::VALIDACION_FORMATO_STRING => 'Formato de string inválido',
            self::VALIDACION_LONGITUD_INVALIDA => 'Longitud de campo inválida',
            self::VALIDACION_DN_INCOMPLETO => 'DN (Distinguished Name) incompleto',
            self::VALIDACION_FORMATO_CUIT => 'Formato de CUIT inválido',
            self::VALIDACION_PARAMETRO_INVALIDO => 'Parámetro inválido',
            self::ARCHIVO_GENERAL => 'Error genérico de archivo',
            self::ARCHIVO_NO_ENCONTRADO => 'Archivo no encontrado',
            self::ARCHIVO_ERROR_LECTURA => 'Error de lectura de archivo',
            self::ARCHIVO_ERROR_ESCRITURA => 'Error de escritura de archivo',
            self::ARCHIVO_PERMISOS_INSUFICIENTES => 'Permisos insuficientes para archivo',
            self::ARCHIVO_FORMATO_INVALIDO => 'Formato de archivo inválido',
            self::ARCHIVO_CERTIFICADO_INVALIDO => 'Archivo de certificado inválido',
            self::ARCHIVO_CLAVE_PRIVADA_INVALIDA => 'Archivo de clave privada inválido',
            self::AUTENTICACION_GENERAL => 'Error genérico de autenticación',
            self::AUTENTICACION_TOKEN_EXPIRADO => 'Token de acceso expirado',
            self::AUTENTICACION_TOKEN_INVALIDO => 'Token de acceso inválido',
            self::AUTENTICACION_ERROR_CREAR_TA => 'Error al crear ticket de acceso (TA)',
            self::AUTENTICACION_ERROR_FIRMAR_TRA => 'Error al firmar ticket de requerimiento de acceso (TRA)',
            self::AUTENTICACION_CERTIFICADO_EXPIRADO => 'Certificado expirado',
            self::AUTENTICACION_CERTIFICADO_INVALIDO => 'Certificado inválido',
            self::AUTENTICACION_ERROR_WSAA => 'Error de comunicación con WSAA',
            self::SOAP_GENERAL => 'Error genérico SOAP',
            self::SOAP_FALLO_COMUNICACION => 'Fallo en comunicación SOAP',
            self::SOAP_RESPUESTA_INVALIDA => 'Respuesta SOAP inválida',
            self::SOAP_TIMEOUT => 'Timeout en operación SOAP',
            self::SOAP_ERROR_CONEXION => 'Error de conexión SOAP',
            self::SOAP_WSDL_NO_ENCONTRADO => 'Archivo WSDL no encontrado',
            self::SOAP_WSDL_INVALIDO => 'Archivo WSDL inválido',
            self::SOAP_OPERACION_NO_ENCONTRADA => 'Operación SOAP no encontrada',
            self::SERVICIO_WEB_GENERAL => 'Error genérico de servicio web',
            self::SERVICIO_WEB_CLASE_NO_ENCONTRADA => 'Clase de servicio web no encontrada',
            self::SERVICIO_WEB_METODO_NO_ENCONTRADO => 'Método de servicio web no encontrado',
            self::SERVICIO_WEB_PARAMETROS_INVALIDOS => 'Parámetros de servicio web inválidos',
            self::SERVICIO_WEB_RESPUESTA_INVALIDA => 'Respuesta de servicio web inválida',
            self::SERVICIO_WEB_NO_DISPONIBLE => 'Servicio web no disponible',
            self::SERVICIO_WEB_FACTURACION_ERROR => 'Error específico de facturación electrónica',
            self::SERVICIO_WEB_PADRON_ERROR => 'Error específico de padrón',
            self::SERVICIO_WEB_CONSTANCIA_ERROR => 'Error específico de constancia de inscripción',
            self::CERTIFICADO_ERROR_GENERAR_CSR => 'Error al generar CSR',
            self::CERTIFICADO_ERROR_EXPORTAR_CSR => 'Error al exportar CSR',
            self::CERTIFICADO_ERROR_LEER_CSR => 'Error al leer CSR',
            self::CERTIFICADO_ERROR_LEER_CERTIFICADO => 'Error al leer certificado',
            self::CERTIFICADO_ERROR_GENERAR_CLAVE => 'Error al generar clave',
            self::CERTIFICADO_ERROR_EXPORTAR_CLAVE => 'Error al exportar clave',
            self::CERTIFICADO_CLAVE_INVALIDA => 'Clave inválida',
            self::CERTIFICADO_TIPO_CLAVE_INVALIDO => 'Tipo de clave inválido',
            self::CERTIFICADO_TAMAÑO_CLAVE_INSUFICIENTE => 'Tamaño de clave insuficiente',
            self::CERTIFICADO_CLAVE_PUBLICA_INVALIDA => 'Clave pública inválida',
            self::CERTIFICADO_ERROR_FIRMAR => 'Error al firmar certificado',
            self::CERTIFICADO_ERROR_VERIFICAR => 'Error al verificar certificado',
        };
    }

    /**
     * Obtiene la categoría de este código de error.
     */
    public function obtenerCategoria(): string
    {
        return match ((int) ($this->value / 1000)) {
            1 => 'configuracion',
            2 => 'validacion',
            3 => 'archivo',
            4 => 'autenticacion',
            5 => 'soap',
            7 => 'certificado',
            default => 'servicio_web',
        };
    }
}
