# Tests para PhpAfipWs

Este directorio contiene la suite de tests para la librería PhpAfipWs usando Pest 4.

## Estructura de Tests

```
tests/
├── Feature/           # Tests de integración
│   ├── AfipIntegrationTest.php
│   ├── ErrorHandlingTest.php
│   └── WebServiceAccessTest.php
├── Unit/              # Tests unitarios
│   ├── Authorization/
│   │   └── TokenAuthorizationTest.php
│   ├── Enums/
│   │   └── CodigosErrorTest.php
│   ├── Exception/
│   │   ├── AfipExceptionTest.php
│   │   ├── ConfiguracionExceptionTest.php
│   │   └── ValidacionExceptionTest.php
│   └── AfipTest.php
├── Pest.php           # Configuración de Pest
├── TestCase.php       # Clase base para tests
└── README.md          # Este archivo
```

## Ejecutar Tests

### Todos los tests

```bash
composer test:unit
# o directamente
./vendor/bin/pest
```

### Tests específicos

```bash
# Solo tests unitarios
./vendor/bin/pest tests/Unit

# Solo tests de integración
./vendor/bin/pest tests/Feature

# Test específico
./vendor/bin/pest tests/Unit/AfipTest.php
```

### Con cobertura

```bash
./vendor/bin/pest --coverage
```

### Con información detallada

```bash
./vendor/bin/pest --verbose
```

## Cobertura de Tests

Los tests cubren las siguientes áreas:

### Tests Unitarios

-   **Afip**: Clase principal, constructor, configuración, métodos públicos
-   **TokenAuthorization**: Manejo de tokens y firmas
-   **Excepciones**: Todas las excepciones personalizadas
-   **Enums**: CodigosError con sus métodos

### Tests de Integración

-   **AfipIntegration**: Configuración completa, diferentes formatos de CUIT
-   **ErrorHandling**: Manejo de errores y excepciones con contexto
-   **WebServiceAccess**: Acceso a web services y validaciones

## Helpers de Testing

La clase `TestCase` proporciona métodos útiles:

-   `createTempDirectory()`: Crea directorio temporal
-   `cleanupTempDirectory($dir)`: Limpia directorio temporal
-   `createMockCertificateFiles($dir)`: Crea archivos de certificado mock
-   `getBasicAfipOptions($resourcesDir, $taDir)`: Opciones básicas para Afip

## Configuración de Archivos Mock

Los tests crean automáticamente:

-   Directorios temporales para recursos y TA
-   Archivos de certificado y clave privada mock
-   Archivos WSDL mock cuando es necesario

## Comandos Disponibles

Definidos en `composer.json`:

```json
{
    "scripts": {
        "test:unit": "pest",
        "test:lint": "php-cs-fixer fix --allow-risky=yes --dry-run --diff",
        "test:types": "phpstan",
        "test:refactor": "rector --dry-run",
        "quality": ["@test:lint", "@test:refactor", "@test:types", "@test:unit"]
    }
}
```

## Notas Importantes

1. Los tests no requieren certificados reales de AFIP
2. Se usan directorios temporales que se limpian automáticamente
3. Los tests de WebService validan la configuración pero no hacen llamadas reales
4. Todos los tests están en español para mantener consistencia con el código

## Agregar Nuevos Tests

Para agregar nuevos tests:

1. **Tests Unitarios**: Crear en `tests/Unit/` siguiendo la estructura de directorios de `src/`
2. **Tests de Integración**: Crear en `tests/Feature/` para funcionalidad completa
3. **Usar describe/it**: Seguir el patrón de Pest con `describe()` e `it()`
4. **Cleanup**: Usar `beforeEach()` y `afterEach()` para setup/cleanup
5. **Helpers**: Usar los métodos de `TestCase` para operaciones comunes
