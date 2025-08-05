# Generador de Certificados para AFIP

La clase `GeneradorCertificados` proporciona métodos estáticos para generar claves privadas RSA, Certificate Signing Requests (CSR), y gestionar certificados digitales necesarios para la autenticación con los Web Services de AFIP usando la librería phpseclib3.

## Requisitos

Esta funcionalidad utiliza:

-   **phpseclib/phpseclib:~3.0**: Librería criptográfica moderna para PHP
-   **PHP >= 8.1**: Con tipado estricto y características modernas
-   No requiere extensiones OpenSSL del sistema operativo

## Uso Básico

### 1. Generar Clave Privada

```php
use PhpAfipWs\Authorization\GeneradorCertificados;

// Generar clave privada de 2048 bits (mínimo requerido por AFIP)
$clavePrivada = GeneradorCertificados::generarClavePrivada();

// Generar clave privada con tamaño personalizado y frase secreta
$clavePrivada = GeneradorCertificados::generarClavePrivada(4096, 'mi_frase_secreta');

// Guardar en archivo
file_put_contents('mi_clave.key', $clavePrivada);
```

### 2. Generar Certificate Signing Request (CSR)

```php
// Distinguished Name para el certificado
$dn = [
    'countryName' => 'AR',
    'stateOrProvinceName' => 'Buenos Aires',
    'localityName' => 'Ciudad Autónoma de Buenos Aires',
    'organizationName' => 'Mi Empresa S.A.',
    'commonName' => 'mi_empresa',
    'serialNumber' => 'CUIT 30123456789'
];

// Generar CSR usando la clave privada
$csr = GeneradorCertificados::generarCSR('mi_clave.key', $dn);

// Guardar CSR en archivo
file_put_contents('mi_certificado.csr', $csr);
```

### 3. Crear Distinguished Name (Recomendado)

```php
// Crear DN válido para AFIP automáticamente
$dn = GeneradorCertificados::crearInformacionDN(
    cuit: '20123456789',
    nombreOrganizacion: 'Mi Empresa S.A.',
    nombreComun: 'mi_empresa',
    provincia: 'Buenos Aires',
    localidad: 'Ciudad Autónoma de Buenos Aires'
);

// Validar DN antes de usar
GeneradorCertificados::validarInformacionDN($dn);
```

### 4. Extraer Información de CSR

```php
// Extraer Distinguished Name de un CSR
$dn = GeneradorCertificados::extraerInformacionCSR('mi_certificado.csr');

foreach ($dn as $campo => $valor) {
    echo "{$campo}: {$valor}\n";
}
```

### 5. Analizar Certificado X.509

```php
// Leer certificado obtenido de AFIP
$certificado = file_get_contents('mi_certificado.pem');

// Extraer información del certificado
$info = GeneradorCertificados::extraerInformacionCertificado($certificado);

echo "Válido desde: " . date('Y-m-d H:i:s', $info['validFrom_time_t']) . "\n";
echo "Válido hasta: " . date('Y-m-d H:i:s', $info['validTo_time_t']) . "\n";
```

## Métodos Disponibles

### `generarClavePrivada(int $bits = 2048, ?string $fraseSecreta = null): string`

Genera una clave privada RSA con phpseclib3.

**Parámetros:**

-   `$bits`: Tamaño de la clave en bits (mínimo 2048 requerido por AFIP)
-   `$fraseSecreta`: Frase secreta opcional para proteger la clave

**Retorna:** String con la clave privada en formato PEM

**Excepciones:**

-   `ConfiguracionException`: Si phpseclib3 no está instalada
-   `ValidacionException`: Si el tamaño de bits es menor a 2048

### `generarCSR(string|array $clavePrivada, array $informacionDn): string`

Genera un Certificate Signing Request usando phpseclib3.

**Parámetros:**

-   `$clavePrivada`: Clave privada (texto, ruta de archivo, o array con clave y frase)
-   `$informacionDn`: Array asociativo con el Distinguished Name

**Retorna:** String con el CSR en formato PEM

**Excepciones:**

-   `CertificadoException`: Si no se puede generar o exportar el CSR

### `extraerInformacionCSR(string $solicitudCSR): array`

Extrae el Distinguished Name de un CSR usando phpseclib3.

**Parámetros:**

-   `$solicitudCSR`: CSR en formato PEM o ruta al archivo

**Retorna:** Array asociativo con los campos del DN

**Excepciones:**

-   `CertificadoException`: Si no se puede leer o procesar el CSR

### `extraerInformacionCertificado(string $certificadoPem): array`

Extrae información completa de un certificado X.509 usando phpseclib3.

**Parámetros:**

-   `$certificadoPem`: Certificado en formato PEM o ruta al archivo

**Retorna:** Array asociativo con información completa del certificado:

-   `version`: Versión del certificado
-   `serialNumber`: Número de serie
-   `issuer`: Información del emisor
-   `subject`: Información del sujeto
-   `validFrom_time_t`: Timestamp de inicio de validez
-   `validTo_time_t`: Timestamp de fin de validez
-   `signatureType`: Tipo de algoritmo de firma

**Excepciones:**

-   `CertificadoException`: Si no se puede leer o procesar el certificado

### `crearInformacionDN(string $cuit, string $nombreOrganizacion, string $nombreComun, ...): array`

Crea un Distinguished Name válido para AFIP con validación automática.

**Parámetros:**

-   `$cuit`: CUIT del contribuyente (11 dígitos sin guiones)
-   `$nombreOrganizacion`: Nombre de la organización o persona
-   `$nombreComun`: Nombre común (generalmente el alias)
-   `$provincia`: Provincia (por defecto 'Buenos Aires')
-   `$localidad`: Localidad (por defecto 'Ciudad Autónoma de Buenos Aires')
-   `$pais`: Código del país (por defecto 'AR')

**Retorna:** Array asociativo con el DN estructurado

**Excepciones:**

-   `ValidacionException`: Si el CUIT no tiene formato correcto

### `validarInformacionDN(array $informacionDn): bool`

Valida que un Distinguished Name contenga todos los campos requeridos por AFIP.

**Parámetros:**

-   `$informacionDn`: Array con la información del DN

**Retorna:** `true` si el DN es válido

**Excepciones:**

-   `ValidacionException`: Si faltan campos requeridos o el formato es incorrecto

### `guardarArchivo(string $contenido, string $ruta): bool`

Guarda contenido PEM en un archivo.

**Parámetros:**

-   `$contenido`: Contenido en formato PEM
-   `$ruta`: Ruta completa del archivo

**Retorna:** `true` si se guardó correctamente, `false` en caso contrario

### `cargarArchivo(string $ruta): string|false`

Carga el contenido de un archivo PEM.

**Parámetros:**

-   `$ruta`: Ruta completa del archivo

**Retorna:** Contenido del archivo o `false` si falla

## Flujo Completo para AFIP

1. **Generar clave privada** con al menos 2048 bits
2. **Crear CSR** con los datos de su organización
3. **Subir CSR** al sitio de AFIP (Administrador de Relaciones de Clave Fiscal)
4. **Completar validación** en AFIP
5. **Descargar certificado** (.pem) generado por AFIP
6. **Usar certificado y clave** con la librería PhpAfipWs

## Instalación de phpseclib3

Para usar GeneradorCertificados, instale phpseclib3:

```bash
composer require phpseclib/phpseclib:~3.0
```

## Ejemplos Completos

Consulte los archivos de ejemplo en la carpeta `ejemplos/generador_certificados/`:

-   **`1_generar_clave_privada.php`**: Generación de claves privadas RSA

    -   Diferentes tamaños de clave (2048, 4096 bits)
    -   Con y sin frases secretas
    -   Guardado seguro en archivos

-   **`2_crear_informacion_distinguida.php`**: Creación de Distinguished Names

    -   Información DN válida para AFIP
    -   Validación de campos requeridos
    -   Ejemplos para diferentes tipos de contribuyentes

-   **`3_generar_csr_nueva.php`**: Generación de Certificate Signing Requests

    -   Proceso completo desde clave privada hasta CSR
    -   Manejo de claves protegidas con frase secreta
    -   Guardado de CSR para envío a AFIP

-   **`4_extraer_dn_csr.php`**: Extracción de información de CSRs

    -   Lectura de CSRs existentes
    -   Verificación de información antes de envío
    -   Comparación con datos originales

-   **`5_validar_informacion_dn.php`**: Validación de Distinguished Names

    -   Verificación de campos requeridos
    -   Validación de formato de CUIT
    -   Manejo de errores de validación

-   **`6_extraer_informacion_certificado.php`**: Análisis de certificados X.509
    -   Extracción de información completa
    -   Verificación de fechas de validez
    -   Análisis de emisor y sujeto

## Flujo Completo Recomendado

```php
use PhpAfipWs\Authorization\GeneradorCertificados;

try {
    // 1. Generar clave privada
    $clavePrivada = GeneradorCertificados::generarClavePrivada(2048, 'mi_frase_secreta');
    GeneradorCertificados::guardarArchivo($clavePrivada, 'clave_privada.key');

    // 2. Crear información DN
    $dn = GeneradorCertificados::crearInformacionDN(
        cuit: '20123456789',
        nombreOrganizacion: 'Mi Empresa S.A.',
        nombreComun: 'mi_empresa'
    );

    // 3. Validar DN
    GeneradorCertificados::validarInformacionDN($dn);

    // 4. Generar CSR
    $csr = GeneradorCertificados::generarCSR($clavePrivada, $dn);
    GeneradorCertificados::guardarArchivo($csr, 'certificado.csr');

    // 5. Verificar CSR generado
    $dnExtraido = GeneradorCertificados::extraerInformacionCSR('certificado.csr');

    echo "CSR generado exitosamente\n";
    echo "Organización: " . $dnExtraido['organizationName'] . "\n";
    echo "CUIT: " . $dnExtraido['serialNumber'] . "\n";

    // 6. Después de obtener certificado de AFIP
    // $certificado = file_get_contents('certificado_afip.pem');
    // $info = GeneradorCertificados::extraerInformacionCertificado($certificado);
    // echo "Válido hasta: " . date('Y-m-d', $info['validTo_time_t']) . "\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
```

## Integración con PhpAfipWs

Una vez generados los certificados, úselos con el SDK principal:

```php
use PhpAfipWs\Afip;
use PhpAfipWs\Authorization\GeneradorCertificados;

// 1. Generar certificados (una sola vez)
$clavePrivada = GeneradorCertificados::generarClavePrivada(2048, 'mi_frase');
$dn = GeneradorCertificados::crearInformacionDN('20123456789', 'Mi Empresa', 'empresa');
$csr = GeneradorCertificados::generarCSR($clavePrivada, $dn);

// Guardar archivos
file_put_contents('resources/clave_privada.key', $clavePrivada);
file_put_contents('resources/certificado.csr', $csr);

// 2. Después de obtener certificado de AFIP, usar con el SDK
$afip = new Afip([
    'cuit' => 20123456789,
    'modo_produccion' => false,
    'nombre_certificado' => 'certificado.crt',  // Descargado de AFIP
    'nombre_clave' => 'clave_privada.key',      // Generado con GeneradorCertificados
    'contrasena_clave' => 'mi_frase',           // Frase secreta usada
    'carpeta_recursos' => __DIR__ . '/resources/',
    'carpeta_ta' => __DIR__ . '/ta/',
]);

// 3. Usar normalmente
$respuesta = $afip->FacturacionElectronica->obtenerEstadoServidor();
```

## Consideraciones de Seguridad

-   **Proteja su clave privada**: Nunca la comparta ni la incluya en repositorios públicos
-   **Use frases secretas**: Para mayor seguridad, proteja su clave privada con una frase secreta robusta
-   **Permisos de archivos**: Configure permisos restrictivos (600) para archivos de claves
-   **Respaldo seguro**: Mantenga copias de seguridad de sus claves en ubicaciones seguras
-   **Rotación de certificados**: Renueve certificados antes de su vencimiento
-   **Validación regular**: Verifique periódicamente la validez de sus certificados

### Ejemplo de Permisos Seguros

```bash
# Linux/macOS
chmod 600 resources/clave_privada.key
chmod 644 resources/certificado.crt

# Verificar permisos
ls -la resources/
```

## Manejo de Excepciones

La clase utiliza excepciones específicas para diferentes tipos de errores:

### CertificadoException

Excepción específica para errores de certificados y CSRs:

```php
use PhpAfipWs\Exception\CertificadoException;

try {
    $csr = GeneradorCertificados::generarCSR($clavePrivada, $dn);
} catch (CertificadoException $e) {
    echo "Error de certificado: " . $e->getMessage() . "\n";
    echo "Operación: " . $e->obtenerOperacion() . "\n";
    echo "Info adicional: " . print_r($e->obtenerInfoCertificado(), true) . "\n";
}
```

### ConfiguracionException

Para errores de configuración (ej. phpseclib3 no instalada):

```php
use PhpAfipWs\Exception\ConfiguracionException;

try {
    $clave = GeneradorCertificados::generarClavePrivada();
} catch (ConfiguracionException $e) {
    echo "Error de configuración: " . $e->getMessage() . "\n";
    // Instalar: composer require phpseclib/phpseclib:~3.0
}
```

### ValidacionException

Para errores de validación de datos:

```php
use PhpAfipWs\Exception\ValidacionException;

try {
    GeneradorCertificados::validarInformacionDN($dn);
} catch (ValidacionException $e) {
    echo "Error de validación: " . $e->getMessage() . "\n";
    echo "Campo: " . $e->obtenerCampo() . "\n";
    echo "Valor: " . $e->obtenerValor() . "\n";
}
```

## Códigos de Error

La clase utiliza códigos de error estandarizados del enum `CodigosError`:

### Errores de Certificados

-   `CERTIFICADO_ERROR_GENERAR_CSR` (3001): Error al generar CSR
-   `CERTIFICADO_ERROR_EXPORTAR_CSR` (3002): Error al exportar CSR
-   `CERTIFICADO_ERROR_LEER_CSR` (3003): Error al leer CSR
-   `CERTIFICADO_ERROR_LEER_CERTIFICADO` (3004): Error al leer certificado

### Errores de Validación

-   `VALIDACION_PARAMETRO_INVALIDO` (2008): Parámetros inválidos
-   `VALIDACION_DN_INCOMPLETO` (2010): DN incompleto o inválido
-   `VALIDACION_CUIT_INVALIDO` (2011): CUIT con formato incorrecto
-   `VALIDACION_FORMATO_CUIT` (2012): Formato de CUIT en serialNumber incorrecto

### Errores de Configuración

-   `CONFIGURACION_DEPENDENCIA_FALTANTE` (1003): phpseclib3 no instalada
-   `CONFIGURACION_ERROR_INTERNO` (1004): Error interno de configuración

## Campos Requeridos para DN

Para que un Distinguished Name sea válido para AFIP, debe contener:

-   **countryName**: Código del país (ej. 'AR')
-   **stateOrProvinceName**: Provincia o estado
-   **localityName**: Localidad o ciudad
-   **organizationName**: Nombre de la organización
-   **commonName**: Nombre común o alias
-   **serialNumber**: CUIT en formato 'CUIT XXXXXXXXXXX'

## Troubleshooting

### Error: phpseclib no encontrada

```bash
composer require phpseclib/phpseclib:~3.0
```

### Error: CUIT inválido

El CUIT debe tener exactamente 11 dígitos numéricos:

-   ✅ Correcto: `'20123456789'`
-   ❌ Incorrecto: `'20-12345678-9'` (con guiones)
-   ❌ Incorrecto: `'123456789'` (menos de 11 dígitos)

### Error: Clave privada muy pequeña

AFIP requiere claves de al menos 2048 bits:

-   ✅ Correcto: `generarClavePrivada(2048)`
-   ✅ Correcto: `generarClavePrivada(4096)`
-   ❌ Incorrecto: `generarClavePrivada(1024)`

### Error: DN incompleto

Asegúrese de incluir todos los campos requeridos:

```php
$dn = [
    'countryName' => 'AR',
    'stateOrProvinceName' => 'Buenos Aires',
    'localityName' => 'Ciudad Autónoma de Buenos Aires',
    'organizationName' => 'Mi Empresa S.A.',
    'commonName' => 'mi_empresa',
    'serialNumber' => 'CUIT 20123456789'  // Formato específico
];
```

## Preguntas Frecuentes

### ¿Puedo usar certificados generados con otras herramientas?

Sí, GeneradorCertificados es compatible con certificados generados por OpenSSL u otras herramientas. Solo asegúrese de que cumplan los requisitos de AFIP.

### ¿Es seguro generar claves privadas con PHP?

Sí, phpseclib3 es una librería criptográfica robusta y ampliamente utilizada. Genera claves con la misma seguridad que herramientas nativas del sistema.

### ¿Qué tamaño de clave debo usar?

-   **2048 bits**: Mínimo requerido por AFIP, adecuado para la mayoría de casos
-   **4096 bits**: Mayor seguridad, recomendado para entornos críticos

### ¿Puedo cambiar la frase secreta de una clave existente?

No directamente. Debe generar una nueva clave privada con la nueva frase secreta y solicitar un nuevo certificado a AFIP.

### ¿Cómo verifico que mi certificado está próximo a vencer?

```php
$info = GeneradorCertificados::extraerInformacionCertificado($certificado);
$diasRestantes = ($info['validTo_time_t'] - time()) / (60 * 60 * 24);

if ($diasRestantes < 30) {
    echo "¡Certificado vence en {$diasRestantes} días!\n";
}
```

### ¿Qué hago si mi CSR es rechazado por AFIP?

1. Verifique que el CUIT en serialNumber sea correcto
2. Confirme que todos los campos requeridos estén presentes
3. Use `validarInformacionDN()` antes de generar el CSR
4. Consulte la documentación oficial de AFIP

## Recursos Adicionales

-   [Documentación oficial de AFIP](https://www.afip.gob.ar/ws/)
-   [Administrador de Relaciones de Clave Fiscal](https://auth.afip.gob.ar/contribuyente_/login.xhtml)
-   [phpseclib3 Documentation](https://phpseclib.com/)
-   [Ejemplos del SDK](../ejemplos/generador_certificados/)

---

**Versión**: 1.2.0
**Última actualización**: 2025-05-08
**Compatibilidad**: PHP 8.1+ con phpseclib3
