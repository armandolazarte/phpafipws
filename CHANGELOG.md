# Changelog

Todos los cambios notables de este proyecto serán documentados en este archivo.

El formato está basado en [Keep a Changelog](https://keepachangelog.com/es-ES/1.0.0/),
y este proyecto adhiere al [Versionado Semántico](https://semver.org/lang/es/).

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

-   **79 tests** con **212 assertions** (anteriormente 65 tests con 184 assertions)
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
