# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere al [Versionado Semántico](https://semver.org/lang/es/).

## [1.2.0] - 2025-05-08

### ✨ Agregado

#### Nueva Clase GeneradorCertificados

Se agregó una nueva clase `GeneradorCertificados` que proporciona utilidades completas para la gestión de certificados digitales y claves privadas, facilitando el proceso de autenticación con AFIP.

**Métodos principales:**

-   **`generarClavePrivada(int $bits = 2048, ?string $fraseSecreta = null): string`**

    -   Genera claves privadas RSA con el tamaño mínimo requerido por AFIP (2048 bits)
    -   Soporte opcional para frases secretas de protección
    -   Validación automática de requisitos de seguridad

-   **`generarCSR(string|array $clavePrivada, array $informacionDn): string`**

    -   Crea Certificate Signing Requests válidos para AFIP
    -   Acepta claves privadas como texto o rutas de archivo
    -   Soporte para claves protegidas con frase secreta

-   **`extraerInformacionCSR(string $solicitudCSR): array`**

    -   Extrae el Distinguished Name de CSRs existentes
    -   Útil para verificar información antes de enviar a AFIP
    -   Manejo robusto de diferentes formatos de CSR

-   **`extraerInformacionCertificado(string $certificadoPem): array`**

    -   Analiza certificados X.509 y extrae información completa
    -   Incluye fechas de validez, emisor, sujeto y número de serie
    -   Compatible con certificados descargados de AFIP

-   **`crearInformacionDN(string $cuit, string $nombreOrganizacion, string $nombreComun, ...): array`**

    -   Crea Distinguished Names válidos para AFIP
    -   Validación automática de formato de CUIT
    -   Valores por defecto para Argentina

-   **`validarInformacionDN(array $informacionDn): bool`**
    -   Valida que el DN contenga todos los campos requeridos por AFIP
    -   Verificación de formato de CUIT en serialNumber
    -   Mensajes de error específicos para cada campo

#### Nueva Excepción CertificadoException

-   **`CertificadoException`** - Excepción específica para errores de certificados
    -   Información contextual sobre la operación que falló
    -   Detalles del certificado o CSR problemático
    -   Códigos de error específicos para diferentes tipos de problemas

#### Nuevos Códigos de Error

Se agregaron códigos de error específicos en el enum `CodigosError`:

-   `CERTIFICADO_ERROR_GENERAR_CSR` - Error al generar CSR
-   `CERTIFICADO_ERROR_EXPORTAR_CSR` - Error al exportar CSR
-   `CERTIFICADO_ERROR_LEER_CSR` - Error al leer CSR
-   `CERTIFICADO_ERROR_LEER_CERTIFICADO` - Error al leer certificado
-   `VALIDACION_DN_INCOMPLETO` - DN incompleto o inválido
-   `VALIDACION_CUIT_INVALIDO` - CUIT con formato incorrecto
-   `VALIDACION_FORMATO_CUIT` - Formato de CUIT en serialNumber incorrecto

#### Nuevos Ejemplos Completos

Se agregaron **6 ejemplos** en la carpeta `ejemplos/generador_certificados/`:

-   **`1_generar_clave_privada.php`** - Generación de claves privadas RSA

    -   Diferentes tamaños de clave (2048, 4096 bits)
    -   Con y sin frases secretas
    -   Guardado seguro en archivos

-   **`2_crear_informacion_distinguida.php`** - Creación de Distinguished Names

    -   Información DN válida para AFIP
    -   Validación de campos requeridos
    -   Ejemplos para diferentes tipos de contribuyentes

-   **`3_generar_csr_nueva.php`** - Generación de Certificate Signing Requests

    -   Proceso completo desde clave privada hasta CSR
    -   Manejo de claves protegidas con frase secreta
    -   Guardado de CSR para envío a AFIP

-   **`4_extraer_dn_csr.php`** - Extracción de información de CSRs

    -   Lectura de CSRs existentes
    -   Verificación de información antes de envío
    -   Comparación con datos originales

-   **`5_validar_informacion_dn.php`** - Validación de Distinguished Names

    -   Verificación de campos requeridos
    -   Validación de formato de CUIT
    -   Manejo de errores de validación

-   **`6_extraer_informacion_certificado.php`** - Análisis de certificados X.509
    -   Extracción de información completa
    -   Verificación de fechas de validez
    -   Análisis de emisor y sujeto

#### Integración con phpseclib3

-   **Dependencia opcional**: `phpseclib/phpseclib:~3.0`
-   **Operaciones nativas**: Sin dependencias de OpenSSL del sistema
-   **Compatibilidad**: Funciona en cualquier entorno PHP con las extensiones básicas
-   **Seguridad**: Implementación robusta de operaciones criptográficas

#### Documentación Especializada

-   **`docs/GeneradorCertificados.md`** - Guía completa de la nueva clase
    -   Ejemplos de uso para cada método
    -   Flujo completo para obtener certificados de AFIP
    -   Consideraciones de seguridad
    -   Códigos de error y troubleshooting

### 🔧 Mejorado

#### Manejo de Errores

-   **Excepciones específicas**: Mejor categorización de errores de certificados
-   **Información contextual**: Detalles sobre la operación que falló
-   **Códigos estructurados**: Identificación programática de tipos de error
-   **Mensajes descriptivos**: Explicaciones claras para debugging

#### Validaciones

-   **Requisitos AFIP**: Validación automática de requisitos mínimos
-   **Formato CUIT**: Verificación de formato en Distinguished Names
-   **Tamaño de claves**: Validación de bits mínimos (2048)
-   **Campos requeridos**: Verificación de DN completos

#### Documentación

-   **README actualizado**: Información sobre GeneradorCertificados
-   **Ejemplos prácticos**: 6 nuevos ejemplos paso a paso
-   **Guía especializada**: Documentación completa en docs/
-   **Casos de uso**: Ejemplos para diferentes escenarios

### 📊 Estadísticas Actuales

-   **30 ejemplos totales** (24 anteriores + 6 nuevos)
-   **100% cobertura** de funcionalidad de certificados
-   **Nueva clase utilitaria** para gestión de certificados
-   **Integración completa** con el ecosistema existente

### 🛠️ Casos de Uso Cubiertos

#### Generación Completa de Certificados

-   Crear claves privadas con diferentes niveles de seguridad
-   Generar CSRs válidos para AFIP
-   Validar información antes del envío
-   Analizar certificados recibidos de AFIP

#### Gestión de Certificados Existentes

-   Extraer información de certificados en uso
-   Verificar fechas de vencimiento
-   Validar estructura de CSRs
-   Migrar entre diferentes formatos

#### Automatización de Procesos

-   Scripts para renovación de certificados
-   Validación automática de requisitos
-   Generación masiva para múltiples entidades
-   Integración con sistemas de gestión

## [1.1.2] - 2025-04-08

### ✨ Agregado

#### Nuevos Métodos en FacturacionElectronica

Se agregaron 5 nuevos métodos para completar la funcionalidad del Web Service de Facturación Electrónica:

-   **`informarCAEASinMovimiento(int $puntoVenta, int $caea): mixed`**

    -   Informa a AFIP que un CAEA no ha tenido movimiento en un punto de venta específico
    -   Obligatorio para evitar observaciones por CAEAs no utilizados
    -   Debe informarse antes del vencimiento del CAEA
    -   Solo se puede informar una vez por CAEA y punto de venta

-   **`consultarCAEASinMovimiento(int $puntoVenta, int $caea): mixed`**

    -   Consulta si un CAEA fue previamente informado como "sin movimiento"
    -   Útil para verificar estado antes de informar
    -   Evita errores por informar duplicadamente el mismo CAEA
    -   Incluye fecha de cuando fue informado

-   **`registrarComprobantesConCAEA(array $comprobantes): mixed`**

    -   Informa los comprobantes emitidos con un CAEA ya otorgado
    -   Proceso de facturación diferida con CAEA
    -   Debe registrarse dentro del plazo establecido por AFIP
    -   Soporta múltiples comprobantes en una sola operación

-   **`obtenerCotizacionMoneda(string $monedaId): mixed`**

    -   Obtiene la cotización oficial de una moneda específica según AFIP
    -   Esencial para facturación en moneda extranjera
    -   Cotizaciones actualizadas diariamente (días hábiles)
    -   Incluye fecha de la cotización

-   **`obtenerActividades(): mixed`**
    -   Obtiene las actividades económicas vigentes del emisor del comprobante
    -   Útil para validar que se puede facturar para cierta actividad
    -   Incluye códigos y descripciones de actividades habilitadas
    -   Información de vigencia por actividad

#### Nuevos Ejemplos Completos

-   **`informar_caea_sin_movimiento.php`** - Ejemplo completo para informar CAEA sin movimiento

    -   Explicación del proceso y cuándo es obligatorio
    -   Manejo de respuestas y validación de resultados
    -   Información sobre plazos y restricciones

-   **`consultar_caea_sin_movimiento.php`** - Ejemplo para consultar estado de CAEA sin movimiento

    -   Verificación de estado antes de informar
    -   Función helper para validación automática
    -   Manejo de casos donde el CAEA no fue informado

-   **`registrar_comprobantes_con_caea.php`** - Ejemplo completo para registro de comprobantes con CAEA

    -   Proceso completo de facturación diferida
    -   Validación de vigencia de CAEA antes de registrar
    -   Manejo de múltiples comprobantes
    -   Funciones helper para validación de fechas

-   **`obtener_cotizacion_moneda.php`** - Ejemplo para consultar cotizaciones de monedas

    -   Consulta de múltiples monedas (DOL, EUR, BRL, PES)
    -   Funciones helper para conversión de importes
    -   Ejemplo de uso en facturación con moneda extranjera
    -   Cálculos automáticos y formateo de fechas

-   **`obtener_actividades.php`** - Ejemplo para consultar actividades económicas
    -   Lista completa de actividades del emisor
    -   Funciones helper para búsqueda y filtrado
    -   Validación de vigencia de actividades
    -   Estadísticas por sector económico

#### Tests Expandidos

-   **5 nuevos tests** para los métodos agregados con cobertura completa
-   Tests de validación de parámetros y respuestas
-   Simulación de casos de éxito y error
-   Verificación de estructura de datos

### 🔧 Mejorado

#### Cobertura Completa

-   **22/22 métodos** de FacturacionElectronica ahora tienen ejemplos específicos
-   **24 ejemplos totales** cubriendo 100% de la funcionalidad disponible
-   **Documentación actualizada** con información de todos los métodos
-   **README de ejemplos** actualizado con nueva estructura

#### Funcionalidad CAEA Completa

-   Gestión completa del ciclo de vida de CAEA
-   Desde solicitud hasta registro de comprobantes
-   Manejo de casos sin movimiento
-   Validaciones y controles de fechas

#### Mejoras en Documentación

-   **README principal** actualizado con información de nuevos métodos
-   **CHANGELOG** con documentación detallada de cambios
-   **Ejemplos** con explicaciones paso a paso
-   **Funciones helper** reutilizables en todos los ejemplos

### 📊 Estadísticas Actuales

-   **104 tests** pasando exitosamente con **320+ assertions**
-   **39 tests específicos** para FacturacionElectronica
-   **24 ejemplos** cubriendo 100% de los 22 métodos públicos
-   **0 errores** en análisis estático con PHPStan

### 🛠️ Casos de Uso Cubiertos

#### Gestión CAEA Completa

-   Solicitar CAEA para períodos específicos
-   Consultar estado y vigencia de CAEA
-   Informar CAEA sin movimiento cuando corresponde
-   Registrar comprobantes emitidos con CAEA

#### Facturación en Moneda Extranjera

-   Obtener cotizaciones oficiales de AFIP
-   Convertir importes automáticamente
-   Validar cotizaciones para comprobantes

#### Validación de Actividades

-   Verificar actividades habilitadas para facturar
-   Filtrar por sector económico
-   Validar vigencia de actividades

## [1.1.1] - 2025-04-08

### ✨ Agregado

#### Métodos Adicionales en FacturacionElectronica

Los siguientes métodos estaban disponibles pero no habían sido documentados completamente en versiones anteriores:

-   **`obtenerInformacionComprobante(int $numero, int $puntoVenta, int $tipoComprobante): mixed`**

    -   Consulta información completa de un comprobante específico
    -   Incluye CAE, fechas, importes y datos del receptor
    -   Devuelve `null` si el comprobante no se encuentra
    -   Manejo específico del error 602 (comprobante no encontrado)

-   **`crearCAEA(int $periodo, int $orden): mixed`**

    -   Solicita un Código de Autorización Electrónico Anticipado
    -   Permite facturación diferida para grandes volúmenes
    -   Parámetros: período (AAAAMM) y orden (1 o 2)

-   **`obtenerCAEA(int $caea): mixed`**

    -   Consulta el estado de un CAEA existente
    -   Devuelve información de vigencia y fechas límite
    -   Útil para verificar validez antes de usar

-   **`obtenerPuntosDeVenta(): mixed`**

    -   Lista todos los puntos de venta habilitados
    -   Incluye estado (activo/inactivo) y descripciones
    -   Esencial para validar puntos de venta antes de facturar

-   **`obtenerTiposConcepto(): mixed`**

    -   Lista tipos de concepto disponibles (Productos, Servicios, Productos y Servicios)
    -   Determina qué campos son requeridos en comprobantes
    -   Incluye información sobre fechas de servicio obligatorias

-   **`obtenerTiposAlicuota(): mixed`**

    -   Lista todas las alícuotas de IVA disponibles (0%, 10.5%, 21%, 27%, etc.)
    -   Incluye códigos necesarios para el campo `Iva` en comprobantes
    -   Información de vigencia de cada alícuota

-   **`obtenerTiposOpcional(): mixed`**

    -   Lista tipos de datos opcionales para comprobantes
    -   Incluye códigos para CVU, CBU, Email, etc.
    -   Útil para campos adicionales en facturación

-   **`obtenerTiposTributo(): mixed`**

    -   Lista tipos de tributos disponibles
    -   Incluye tributos nacionales, provinciales y municipales
    -   Necesario para el campo `Tributos` en comprobantes

-   **`obtenerCondicionesIvaReceptor(): mixed`**
    -   Lista condiciones de IVA para receptores
    -   Incluye códigos para Responsable Inscripto, Monotributista, Consumidor Final, etc.
    -   Requerido para validar el campo receptor en comprobantes

> **Nota**: Estos métodos estaban disponibles en versiones anteriores pero no habían sido documentados completamente en el changelog. Esta versión incluye documentación completa y ejemplos prácticos para todos ellos.

#### Nuevos Ejemplos Completos

-   **`consultar_comprobante.php`** - Ejemplo completo para consultar información de comprobantes específicos
    -   Incluye manejo de comprobantes no encontrados
    -   Ejemplos de búsqueda múltiple
    -   Validación de respuestas de AFIP
-   **`demo_completa_metodos.php`** - Demostración exhaustiva de todos los métodos disponibles del SDK
    -   Cobertura de todos los 17 métodos públicos de FacturacionElectronica
    -   Ejemplos organizados por categorías (Estado, Parámetros, Consultas, Autorización, CAEA)
    -   Simulaciones para métodos de autorización
-   **`gestion_caea.php`** - Ejemplo completo para gestión de CAEA (Código de Autorización Electrónico Anticipado)
    -   Explicación detallada del proceso CAEA
    -   Cálculo de períodos y órdenes
    -   Funciones helper para fechas
-   **`obtener_puntos_venta.php`** - Ejemplo para consultar puntos de venta habilitados
    -   Manejo del error 602 común en homologación
    -   Estadísticas de puntos activos/inactivos
    -   Integración con consulta de comprobantes
-   **`obtener_tipos_alicuota.php`** - Ejemplo para consultar tipos de alícuotas de IVA
    -   Guía completa de alícuotas (0%, 10.5%, 21%, 27%)
    -   Ejemplos de cálculo de IVA
    -   Función helper para cálculos automáticos
-   **`obtener_tipos_concepto.php`** - Ejemplo para consultar tipos de concepto (productos, servicios, etc.)
    -   Explicación de campos requeridos por concepto
    -   Validación de fechas de servicio
    -   Ejemplos de implementación práctica
-   **`obtener_tipos_opcional.php`** - Ejemplo completo para tipos de datos opcionales (CVU, CBU, Email, etc.)
    -   Guía completa de campos opcionales disponibles
    -   Ejemplos de uso en comprobantes
    -   Validación de formatos específicos
-   **`obtener_tipos_tributo.php`** - Ejemplo completo para tipos de tributos (Nacionales, Provinciales, Municipales)
    -   Lista completa de tributos por jurisdicción
    -   Ejemplos de cálculo de tributos
    -   Integración con estructura de comprobantes

#### Mejoras en Documentación

-   **Ejemplos prácticos**: Cada ejemplo incluye casos de uso reales y manejo de errores
-   **Guías de implementación**: Código de ejemplo listo para usar en producción
-   **Funciones helper**: Utilidades reutilizables incluidas en los ejemplos
-   **Validaciones robustas**: Manejo defensivo de respuestas de AFIP
-   **README de ejemplos**: Actualizado con información de los nuevos ejemplos
-   **README de tests**: Actualizado con estadísticas actuales y guías de ejecución

### 🔧 Mejorado

#### Robustez del SDK

-   **Manejo de errores**: Mejores validaciones en métodos de FacturacionElectronica
    -   Validación de estructura de respuestas en `obtenerUltimoNumeroComprobante()`
    -   Verificación de tipos numéricos en `autorizarProximoComprobante()`
    -   Manejo robusto de respuestas nulas en `obtenerInformacionComprobante()`
-   **Documentación PHPDoc**: Comentarios más detallados en todos los métodos
    -   Documentación completa de parámetros y tipos de retorno
    -   Ejemplos de uso en comentarios
    -   Descripción de excepciones posibles
-   **Validación de tipos**: Verificaciones más estrictas de parámetros de entrada
    -   Validación de tipos en métodos de autorización
    -   Verificación de estructura de datos antes de procesamiento
-   **Versión del SDK**: Actualizada a 1.1.2 en la clase principal Afip

#### Cobertura de Ejemplos

-   **8 nuevos ejemplos** agregados para completar la cobertura
-   **Casos de uso reales**: Ejemplos basados en situaciones comunes de facturación
-   **Manejo de excepciones**: Cada ejemplo incluye manejo robusto de errores
-   **Documentación inline**: Explicaciones detalladas en cada ejemplo

#### Limpieza de Archivos

-   **TESTING_SUMMARY.md**: Archivo eliminado para simplificar la documentación
-   **Consolidación**: Información de tests integrada en README.md

### 🧪 Tests y Calidad

#### Mejoras en Tests

-   **Tests actualizados**: Suite de tests mejorada para mayor cobertura
-   **Validaciones robustas**: Tests adicionales para casos edge
-   **Manejo de errores**: Tests específicos para validación de excepciones
-   **Documentación de tests**: README de tests actualizado con información actual

#### Calidad de Código

-   **PHPStan**: Análisis estático sin errores
-   **Pest 4**: Framework de testing moderno
-   **Cobertura completa**: Todos los métodos públicos cubiertos por tests

### 📚 Documentación

-   **27 ejemplos totales** cubriendo 100% de la funcionalidad disponible
-   **Guías paso a paso** para implementación en producción
-   **Casos de error comunes** y sus soluciones
-   **Mejores prácticas** incluidas en cada ejemplo
-   **README actualizado**: Información actualizada sobre ejemplos y funcionalidades

### 📊 Estadísticas Actuales

-   **99 tests** pasando exitosamente con **295 assertions**
-   **34 tests específicos** para FacturacionElectronica con **111 assertions**
-   **27 ejemplos** cubriendo 100% de los 17 métodos públicos de FacturacionElectronica
-   **0 errores** en análisis estático con PHPStan
-   **Tiempo de ejecución**: ~0.78 segundos

## [1.1.0] - 2025-02-08

### ✨ Nueva funcionalidad

Agregados nuevos métodos para simplificar el trabajo con Facturación Electrónica.

### ✨ Agregado

#### Nuevos métodos en FacturacionElectronica

-   **`obtenerUltimoNumeroComprobante(int $puntoVenta, int $tipoComprobante): int`**

    -   Extrae directamente el número del último comprobante como entero
    -   Incluye validación robusta de estructura de respuesta
    -   Manejo de errores con excepciones específicas

-   **`autorizarProximoComprobante(array $datosComprobante): mixed`**
    -   Calcula automáticamente el próximo número de comprobante
    -   Simplifica el proceso de autorización
    -   Valida tipos de datos de entrada
    -   Soporta valores por defecto para PtoVta y CbteTipo

#### Mejoras en validación y tipos

-   Validación de tipos estricta en todos los métodos nuevos
-   Verificación de estructura de respuestas de AFIP
-   Manejo robusto de errores con mensajes descriptivos
-   Documentación PHPDoc completa con tipos específicos

#### Tests y calidad

-   **14 nuevos tests** para los métodos agregados
-   Cobertura completa de casos de éxito y error
-   Tests de validación de tipos y estructura
-   Uso de stubs para simulación de comportamientos

### 🔧 Mejorado

-   **Manejo de errores**: Excepciones más específicas con contexto detallado
-   **Validación de datos**: Verificación de tipos antes de procesamiento
-   **Documentación**: README actualizado con ejemplos de los nuevos métodos
-   **Calidad de código**: Cumple con PHPStan nivel máximo sin errores

### 📊 Estadísticas

-   **99 tests** con **295 assertions** (anteriormente 65 tests con 184 assertions)
-   **0 errores** en análisis estático con PHPStan
-   **Cobertura completa** de métodos de Facturación Electrónica

### 🛠️ Características principales

-   **SDK completo** para Web Services de AFIP
-   **PHP 8.1+** con tipado estricto
-   **Manejo de certificados** y autenticación segura
-   **API intuitiva** y bien documentada
-   **Tests automatizados** con Pest 4
-   **Calidad de código** verificada con herramientas modernas

### 📚 Documentación

-   README actualizado con ejemplos de uso
-   Documentación de métodos nuevos
-   Guías de contribución y desarrollo
-   Ejemplos de manejo de errores

### 🔗 Web Services soportados

-   FacturacionElectronica (WSFE)
-   PadronAlcanceCuatro (A4)
-   PadronAlcanceCinco (A5)
-   ConstanciaInscripcion
-   PadronAlcanceDiez (A10)
-   PadronAlcanceTrece (A13)

---

## [1.0.0] - 2025-01-15

### 🎉 Primera versión estable

-   **SDK completo** para Web Services de AFIP
-   **PHP 8.1+** con tipado estricto
-   **Manejo de certificados** y autenticación segura
-   **API intuitiva** y bien documentada
-   **65 tests** automatizados con Pest 4
-   **Web Services soportados**: FacturacionElectronica, PadronAlcanceCuatro, PadronAlcanceCinco, ConstanciaInscripcion, PadronAlcanceDiez, PadronAlcanceTrece

---

## Formato de versiones

-   **[MAJOR.MINOR.PATCH]** - Fecha en formato YYYY-MM-DD
-   **MAJOR**: Cambios incompatibles en la API
-   **MINOR**: Funcionalidad agregada de manera compatible
-   **PATCH**: Correcciones de bugs compatibles

## Enlaces

-   [Repositorio](https://github.com/armandolazarte/phpafipws)
-   [Packagist](https://packagist.org/packages/armandolazarte/phpafipws)
-   [Issues](https://github.com/armandolazarte/phpafipws/issues)
-   [Releases](https://github.com/armandolazarte/phpafipws/releases)
