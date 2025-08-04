# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere al [Versionado Semántico](https://semver.org/lang/es/).

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
