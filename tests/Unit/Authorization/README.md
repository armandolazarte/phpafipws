# Tests para GeneradorCertificados

Este directorio contiene tests completos para la clase `GeneradorCertificados` usando Pest 4 en español.

## Archivos de Test

### `GeneradorCertificadosTest.php`

Tests principales que cubren toda la funcionalidad básica de la clase:

-   **Generación de claves privadas**: Tests para diferentes tamaños de bits y frases secretas
-   **Generación de CSR**: Tests para crear Certificate Signing Requests
-   **Extracción de información**: Tests para extraer datos de CSR y certificados
-   **Validación de DN**: Tests para validar Distinguished Names
-   **Creación de DN**: Tests para crear DN con diferentes parámetros
-   **Manejo de archivos**: Tests para guardar y cargar archivos
-   **Integración completa**: Test de flujo completo desde clave hasta CSR

### `GeneradorCertificadosTestAdicional.php`

Tests adicionales que cubren casos específicos y límite:

-   **Validación de parámetros**: Tests específicos para validación de CUIT
-   **Validación de DN completo**: Tests exhaustivos para todos los campos requeridos
-   **Manejo de errores específicos**: Tests para códigos de error específicos
-   **Casos límite**: Tests para situaciones especiales (strings vacíos, etc.)
-   **Integración con archivos reales**: Tests con directorios temporales reales
-   **Validación de formatos**: Tests específicos para formatos de serialNumber

## Ejecutar los Tests

### Ejecutar todos los tests de GeneradorCertificados

```bash
php vendor/bin/pest tests/Unit/Authorization/
```

### Ejecutar solo los tests principales

```bash
php vendor/bin/pest tests/Unit/Authorization/GeneradorCertificadosTest.php
```

### Ejecutar solo los tests adicionales

```bash
php vendor/bin/pest tests/Unit/Authorization/GeneradorCertificadosTestAdicional.php
```

### Ejecutar con información detallada

```bash
php vendor/bin/pest tests/Unit/Authorization/ --coverage
```

## Cobertura de Tests

Los tests cubren:

✅ **Generación de claves privadas**

-   Configuración por defecto (2048 bits)
-   Tamaños personalizados (4096 bits)
-   Claves protegidas con frase secreta
-   Validación de tamaño mínimo requerido por AFIP

✅ **Generación de CSR**

-   CSR con clave privada como string
-   CSR con información DN completa
-   Validación de formato de salida

✅ **Extracción de información**

-   Extracción de DN desde CSR
-   Manejo de archivos temporales
-   Validación de CSR inválidos

✅ **Validación de DN**

-   Validación de campos requeridos
-   Validación de formato de CUIT
-   Manejo de campos vacíos

✅ **Creación de DN**

-   DN con parámetros mínimos
-   DN con parámetros personalizados
-   Validación de CUIT (formato y longitud)

✅ **Manejo de archivos**

-   Guardar contenido en archivos
-   Cargar contenido desde archivos
-   Manejo de errores de E/S

✅ **Casos límite y errores**

-   Códigos de error específicos
-   Validación exhaustiva de formatos
-   Manejo de strings vacíos y valores especiales

## Notas Importantes

### Tests Saltados

-   **CSR con clave protegida por frase secreta**: Saltado debido a un problema conocido con phpseclib3 y el manejo de claves protegidas por contraseña.

### Warnings Esperados

-   **Tests de archivos inexistentes**: Los warnings sobre archivos inexistentes son esperados y forman parte del comportamiento normal de los tests que verifican el manejo de errores.

### Dependencias

Los tests requieren:

-   PHP 8.1+
-   Pest 4.0+
-   phpseclib/phpseclib 3.0+
-   Extensiones PHP: openssl, simplexml

## Estructura de Tests

Los tests siguen la estructura de Pest con:

-   `describe()` para agrupar tests relacionados
-   `it()` para tests individuales
-   `beforeEach()` y `afterEach()` para setup y cleanup
-   `expect()` para aserciones fluidas
-   Manejo adecuado de archivos temporales

## Ejemplos de Uso

### Test básico de generación de clave

```php
it('genera una clave privada con configuración por defecto', function () {
    $clavePrivada = GeneradorCertificados::generarClavePrivada();

    expect($clavePrivada)
        ->toBeString()
        ->toContain('-----BEGIN RSA PRIVATE KEY-----')
        ->toContain('-----END RSA PRIVATE KEY-----');
});
```

### Test de validación con excepción esperada

```php
it('lanza excepción cuando el tamaño de bits es menor al mínimo requerido', function () {
    expect(fn () => GeneradorCertificados::generarClavePrivada(1024))
        ->toThrow(ValidacionException::class, 'La clave privada debe generarse de al menos 2048 bits');
});
```

### Test de integración completa

```php
it('puede generar clave, crear CSR y extraer información en flujo completo', function () {
    // Generar clave privada
    $clavePrivada = GeneradorCertificados::generarClavePrivada(2048);
    expect($clavePrivada)->toBeString();

    // Crear información DN
    $informacionDn = GeneradorCertificados::crearInformacionDN(
        '30123456789',
        'Empresa de Prueba S.A.',
        'empresa_prueba'
    );
    expect($informacionDn)->toBeArray();

    // Generar CSR
    $csr = GeneradorCertificados::generarCSR($clavePrivada, $informacionDn);
    expect($csr)->toBeString();

    // Verificar información extraída
    $archivoTemp = tempnam(sys_get_temp_dir(), 'test_csr_');
    file_put_contents($archivoTemp, $csr);

    $informacionExtraida = GeneradorCertificados::extraerInformacionCSR($archivoTemp);
    expect($informacionExtraida)
        ->toBeArray()
        ->toHaveKey('organizationName', 'Empresa de Prueba S.A.')
        ->toHaveKey('serialNumber', 'CUIT 30123456789');

    unlink($archivoTemp);
});
```

## Contribuir

Al agregar nuevos tests:

1. Usa nombres descriptivos en español
2. Agrupa tests relacionados con `describe()`
3. Limpia recursos temporales en `afterEach()`
4. Usa `expect()` para aserciones fluidas
5. Documenta casos especiales o limitaciones
6. Verifica códigos de error específicos cuando sea relevante
