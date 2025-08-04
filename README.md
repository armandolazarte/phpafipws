<div align="center">
    <img src="https://raw.githubusercontent.com/armandolazarte/phpafipws/master/docs/logo.png" height="200" alt="PhpAfipWs">

**SDK moderno para Web Services de AFIP en PHP**

[![Tests](https://github.com/armandolazarte/phpafipws/actions/workflows/tests.yml/badge.svg)](https://github.com/armandolazarte/phpafipws/actions)
[![Downloads](https://img.shields.io/packagist/dt/armandolazarte/phpafipws)](https://packagist.org/packages/armandolazarte/phpafipws)
[![Version](https://img.shields.io/packagist/v/armandolazarte/phpafipws)](https://packagist.org/packages/armandolazarte/phpafipws)
[![License](https://img.shields.io/packagist/l/armandolazarte/phpafipws)](https://packagist.org/packages/armandolazarte/phpafipws)
[![PHP Version](https://img.shields.io/packagist/php-v/armandolazarte/phpafipws)](https://packagist.org/packages/armandolazarte/phpafipws)
[![Test Coverage](https://img.shields.io/badge/tests-99%20passing-brightgreen)](https://github.com/armandolazarte/phpafipws)

</div>

---

## 📋 Descripción

PhpAfipWs es un SDK moderno y robusto para interactuar con los Web Services de AFIP (Administración Federal de Ingresos Públicos) de Argentina. Desarrollado con PHP 8.1+, ofrece una interfaz simple y elegante para la facturación electrónica y otros servicios de AFIP.

## ✨ Características

-   **Moderno**: Desarrollado con PHP 8.1+ y tipado estricto
-   **Fácil de usar**: API intuitiva y bien documentada
-   **Completo**: Soporte para múltiples Web Services de AFIP
-   **Seguro**: Manejo robusto de certificados y autenticación
-   **Confiable**: 99 tests automatizados con Pest 4
-   **Mantenido**: Actualizaciones regulares y soporte activo

### 🆕 Novedades v1.1.1

-   **Ejemplos completos**: 19 ejemplos prácticos cubriendo 100% de los métodos disponibles
-   **Cobertura total**: Todos los 17 métodos públicos de FacturacionElectronica tienen ejemplos específicos
-   **Sin warnings**: Todos los ejemplos ejecutan sin errores de PHP
-   **Tests robustos**: 99 tests con 295 assertions, incluyendo 34 tests específicos para FacturacionElectronica
-   **Funciones helper**: Código reutilizable incluido en ejemplos
-   **Casos reales**: Ejemplos basados en situaciones de uso común

## 🚀 Instalación

Instala el paquete usando Composer:

```bash
composer require armandolazarte/phpafipws
```

## 📦 Requisitos

-   PHP >= 8.1
-   Extensión SOAP
-   Extensión OpenSSL
-   Extensión SimpleXML
-   Certificado digital de AFIP
-   Clave privada correspondiente

## 🔧 Configuración Inicial

### 1. Obtener Certificados

Necesitas obtener un certificado digital de AFIP:

1. Genera una clave privada y CSR
2. Solicita el certificado en el sitio de AFIP
3. Descarga el certificado (.crt) y guarda tu clave privada (.key)

### 2. Estructura de Carpetas

```
tu-proyecto/
├── resources/
│   ├── certificado.crt
│   └── clave_privada.key
└── ta/
    └── (archivos de tickets de acceso)
```

## 💻 Uso Básico

### Inicialización

```php
<?php

use PhpAfipWs\Afip;

$afip = new Afip([
    'cuit' => 20123456789,
    'modo_produccion' => false, // true para producción
    'nombre_certificado' => 'certificado.crt',
    'nombre_clave' => 'clave_privada.key',
    'contrasena_clave' => 'tu_passphrase', // opcional
    'carpeta_recursos' => __DIR__ . '/resources/',
    'carpeta_ta' => __DIR__ . '/ta/',
]);
```

### Facturación Electrónica

#### Método Simplificado (Recomendado)

```php
// Autorizar el próximo comprobante automáticamente
$datosFactura = [
    'PtoVta' => 1,
    'CbteTipo' => 1, // Factura A
    'Concepto' => 1, // Productos
    'DocTipo' => 80, // CUIT
    'DocNro' => 33693450239,
    'CbteFch' => (int) date('Ymd'),
    'ImpTotal' => 121.00,
    'ImpNeto' => 100.00,
    'ImpIVA' => 21.00,
    'MonId' => 'PES',
    'MonCotiz' => 1,
    'Iva' => [
        [
            'Id' => 5, // 21%
            'BaseImp' => 100.00,
            'Importe' => 21.00,
        ],
    ],
];

// El SDK calcula automáticamente el próximo número de comprobante
$respuesta = $afip->FacturacionElectronica->autorizarProximoComprobante($datosFactura);

if ($respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Resultado === 'A') {
    echo "¡Factura autorizada!\n";
    echo "CAE: " . $respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAE . "\n";
    echo "Número: " . $respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CbteDesde . "\n";
}
```

#### Método Manual (Control Total)

```php
// Obtener el último número de comprobante
$ultimoNumero = $afip->FacturacionElectronica
    ->obtenerUltimoNumeroComprobante($puntoDeVenta = 1, $tipoFactura = 1);

// Crear una factura con número específico
$datosFactura = [
    'PtoVta' => 1,
    'CbteTipo' => 1, // Factura A
    'Concepto' => 1, // Productos
    'DocTipo' => 80, // CUIT
    'DocNro' => 33693450239,
    'CbteDesde' => $ultimoNumero + 1,
    'CbteHasta' => $ultimoNumero + 1,
    'CbteFch' => (int) date('Ymd'),
    'ImpTotal' => 121.00,
    'ImpNeto' => 100.00,
    'ImpIVA' => 21.00,
    'MonId' => 'PES',
    'MonCotiz' => 1,
    'Iva' => [
        [
            'Id' => 5, // 21%
            'BaseImp' => 100.00,
            'Importe' => 21.00,
        ],
    ],
];

// Autorizar la factura
$respuesta = $afip->FacturacionElectronica->autorizarComprobante([$datosFactura]);

if ($respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse->Resultado === 'A') {
    echo "¡Factura autorizada!\n";
    echo "CAE: " . $respuesta->FECAESolicitarResult->FeDetResp->FECAEDetResponse->CAE . "\n";
}
```

## 🛠️ Web Services Disponibles

-   **FacturacionElectronica**: Facturación electrónica (WSFE)
-   **PadronAlcanceCuatro**: Padrón A4
-   **PadronAlcanceCinco**: Padrón A5
-   **ConstanciaInscripcion**: Constancia de inscripción
-   **PadronAlcanceDiez**: Padrón A10
-   **PadronAlcanceTrece**: Padrón A13

## 📚 Ejemplos

El directorio `ejemplos/` contiene **19 ejemplos completos** que cubren **100% de los métodos** disponibles:

### Facturación Electrónica

-   Facturas A, B y C con ejemplos detallados
-   Notas de crédito A, B y C
-   Gestión completa de CAEA (Código de Autorización Electrónico Anticipado)
-   Consulta de información de comprobantes

### Consultas de Parámetros

-   Tipos de comprobantes, documentos y monedas
-   Tipos de concepto y alícuotas de IVA
-   Condiciones de IVA del receptor
-   Puntos de venta habilitados
-   **Tipos de datos opcionales** (CVU, CBU, Email, etc.)
-   **Tipos de tributos** (Nacionales, Provinciales, Municipales)

### Métodos Avanzados

-   Nuevos métodos simplificados v1.1.0
-   Estado del servidor y diagnósticos
-   Demostración completa de todos los métodos

Todos los ejemplos incluyen:

-   ✅ Código funcional sin warnings
-   ✅ Explicaciones detalladas de uso
-   ✅ Validaciones y mejores prácticas
-   ✅ Funciones helper reutilizables
-   ✅ Manejo robusto de errores

## 🔍 Métodos Útiles

### Información del SDK

```php
echo $afip->obtenerVersionSDK(); // Versión actual
echo $afip->obtenerCuit(); // CUIT configurado
echo $afip->esModoProduccion() ? 'Producción' : 'Homologación';
```

### Facturación Electrónica - Métodos Principales

```php
// Verificar estado del servidor AFIP
$estado = $afip->FacturacionElectronica->obtenerEstadoServidor();

// Obtener último número de comprobante (método directo)
$ultimoNumero = $afip->FacturacionElectronica
    ->obtenerUltimoNumeroComprobante($puntoVenta = 1, $tipoComprobante = 1);

// Obtener respuesta completa del último comprobante
$ultimoComprobante = $afip->FacturacionElectronica
    ->obtenerUltimoComprobante($puntoVenta = 1, $tipoComprobante = 1);

// Autorizar comprobante con número específico
$respuesta = $afip->FacturacionElectronica->autorizarComprobante([$datosComprobante]);

// Autorizar próximo comprobante automáticamente (recomendado)
$respuesta = $afip->FacturacionElectronica->autorizarProximoComprobante($datosComprobante);
```

### Consultas de Parámetros

```php
// Obtener tipos de comprobantes disponibles
$tiposComprobante = $afip->FacturacionElectronica->obtenerTiposComprobante();

// Obtener tipos de documentos
$tiposDocumento = $afip->FacturacionElectronica->obtenerTiposDocumento();

// Obtener tipos de monedas
$tiposMoneda = $afip->FacturacionElectronica->obtenerTiposMoneda();

// Obtener condiciones de IVA para el receptor
$condicionesIva = $afip->FacturacionElectronica->obtenerCondicionesIvaReceptor();
```

### Consultas de Padrón

```php
// Consultar datos de un CUIT
$datos = $afip->PadronAlcanceCuatro->obtenerPersona(20123456789);
```

## ⚙️ Configuración Avanzada

### Carpeta WSDL Personalizada

```php
$afip = new Afip([
    // ... otras opciones
    'carpeta_wsdl' => __DIR__ . '/wsdl_personalizados/',
]);
```

### Manejo de Excepciones SOAP

```php
$afip = new Afip([
    // ... otras opciones
    'manejar_excepciones_soap' => true,
]);
```

## 🚨 Manejo de Errores

El SDK utiliza excepciones específicas para diferentes tipos de errores con información contextual detallada:

```php
use PhpAfipWs\Exception\AfipException;
use PhpAfipWs\Exception\AutenticacionException;
use PhpAfipWs\Exception\ConfiguracionException;
use PhpAfipWs\Exception\ValidacionException;
use PhpAfipWs\Exception\ArchivoException;

try {
    $respuesta = $afip->FacturacionElectronica->autorizarComprobante($datos);
} catch (AutenticacionException $e) {
    echo "Error de autenticación: " . $e->getMessage();
    echo "Servicio: " . $e->obtenerServicio();
} catch (ConfiguracionException $e) {
    echo "Error de configuración: " . $e->getMessage();
    echo "Campo problemático: " . $e->obtenerCampoConfiguracion();
} catch (ValidacionException $e) {
    echo "Error de validación: " . $e->getMessage();
    echo "Campo: " . $e->obtenerCampo();
    echo "Valor: " . $e->obtenerValor();
} catch (ArchivoException $e) {
    echo "Error de archivo: " . $e->getMessage();
} catch (AfipException $e) {
    echo "Error general: " . $e->getMessage();
    echo "Código: " . $e->getCode();
    echo "Tipo: " . $e->obtenerTipoError();
}
```

### Información Contextual

Todas las excepciones incluyen:

-   **Timestamp**: Momento exacto del error
-   **ID único**: Para tracking y debugging
-   **Contexto**: Información específica del error
-   **Códigos estructurados**: Para manejo programático

## 🧪 Testing

PhpAfipWs incluye una suite completa de tests usando **Pest 4** para garantizar la calidad y confiabilidad del código.

### Ejecutar Tests

```bash
# Todos los tests
composer test:unit

# Solo tests unitarios
./vendor/bin/pest tests/Unit

# Solo tests de integración
./vendor/bin/pest tests/Feature

# Con información detallada
./vendor/bin/pest --verbose
```

### Cobertura de Tests

-   ✅ **99 tests** con **295 assertions**
-   ✅ Tests unitarios para todas las clases principales
-   ✅ Tests de integración para flujos completos
-   ✅ Validación de configuraciones y excepciones
-   ✅ Manejo de errores y casos edge
-   ✅ **Cobertura 100%** de métodos de Facturación Electrónica
-   ✅ **34 tests específicos** para FacturacionElectronica con 111 assertions
-   ✅ Tests de casos de uso reales basados en ejemplos

### Estructura de Tests

```
tests/
├── Feature/           # Tests de integración
│   ├── AfipIntegrationTest.php
│   ├── ErrorHandlingTest.php
│   └── WebServiceAccessTest.php
├── Unit/              # Tests unitarios
│   ├── Authorization/
│   ├── Enums/
│   ├── Exception/
│   └── AfipTest.php
└── TestCase.php       # Helpers para testing
```

### Calidad de Código

```bash
# Verificar todo (linting, types, refactoring, tests)
composer quality

# Comandos individuales
composer test:lint      # PHP CS Fixer
composer test:types     # PHPStan
composer test:refactor  # Rector

# Aplicar correcciones automáticas
composer lint           # Aplicar PHP CS Fixer
composer refactor       # Aplicar Rector
```

## 🔧 Desarrollo

### Configuración del Entorno de Desarrollo

```bash
# Clonar el repositorio
git clone https://github.com/armandolazarte/phpafipws.git
cd phpafipws

# Instalar dependencias
composer install

# Ejecutar tests
composer test:unit

# Verificar calidad
composer quality
```

### Debugging

Para debugging detallado, puedes acceder a información contextual de las excepciones:

```php
try {
    $afip = new Afip($opciones);
} catch (AfipException $e) {
    // Información básica
    echo "Error: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";

    // Información contextual
    echo "Tipo: " . $e->obtenerTipoError() . "\n";
    echo "ID: " . $e->obtenerId() . "\n";
    echo "Timestamp: " . $e->obtenerMarcaTiempo()->format('Y-m-d H:i:s') . "\n";

    // Contexto específico
    print_r($e->obtenerContexto());
}
```

## 🙏 Agradecimientos

Este proyecto está basado en el excelente trabajo de [AfipSDK/afip.php](https://github.com/AfipSDK/afip.php). Agradecemos enormemente a sus contribuidores por sentar las bases que hicieron posible este SDK moderno.

## 🤝 Contribuir

Las contribuciones son bienvenidas. Por favor:

1. Fork el proyecto
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Escribe tests para tu código (`composer test:unit`)
4. Verifica la calidad (`composer quality`)
5. Commit tus cambios (`git commit -am 'Agrega nueva funcionalidad'`)
6. Push a la rama (`git push origin feature/nueva-funcionalidad`)
7. Abre un Pull Request

### Guías para Contribuir

-   **Tests**: Todo código nuevo debe incluir tests
-   **Estilo**: Seguir PSR-12 y las reglas de PHP CS Fixer
-   **Tipos**: Usar tipado estricto en todo el código
-   **Documentación**: Actualizar README y docstrings según corresponda

## 📄 Licencia

Este proyecto está bajo la [Licencia MIT](LICENSE.md).

## 👨‍💻 Autor

**[Armando Lazarte](https://x.com/ArmandoLazarte)**

---

<div align="center">

**¿Te gusta este proyecto? ¡Dale una ⭐!**

</div>
