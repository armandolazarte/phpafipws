# Ejemplos de PhpAfipWs

Esta carpeta contiene ejemplos prácticos de uso del SDK PhpAfipWs para diferentes tipos de comprobantes y operaciones.

## 🆕 Nuevos métodos v1.1.0

Los ejemplos han sido actualizados para mostrar los nuevos métodos simplificados:

-   **`obtenerUltimoNumeroComprobante()`**: Obtiene directamente el número como entero
-   **`autorizarProximoComprobante()`**: Calcula automáticamente el próximo número

## 📁 Estructura de ejemplos

### Facturas

-   **`factura_A.php`** - Factura A (Responsable Inscripto a Responsable Inscripto) ✅ Actualizado
-   **`factura_B.php`** - Factura B (Responsable Inscripto a Consumidor Final) ✅ Actualizado
-   **`factura_C.php`** - Factura C (Monotributista) ✅ Actualizado

### Notas de Crédito

-   **`nota_credito_A.php`** - Nota de Crédito A (Responsable Inscripto a Responsable Inscripto) ✅ Actualizado
-   **`nota_credito_B.php`** - Nota de Crédito B (Responsable Inscripto a Consumidor Final) ✅ Actualizado
-   **`nota_credito_C.php`** - Nota de Crédito C (Monotributista) ✅ Actualizado

### Consultas

-   **`estado_servidor.php`** - Verificar estado de servidores AFIP
-   **`obtener_tipos_comprobantes.php`** - Consultar tipos de comprobantes
-   **`obtener_tipos_documento.php`** - Consultar tipos de documentos
-   **`obtener_tipos_moneda.php`** - Consultar tipos de monedas
-   **`obtener_condiciones_iva_receptor.php`** - Consultar condiciones de IVA

### Nuevos métodos

-   **`nuevos_metodos_v1_1.php`** - Demostración completa de los nuevos métodos

## 🚀 Configuración inicial

### 1. Certificados

Coloca tus certificados en la carpeta `resources/`:

-   `certificado.crt` - Tu certificado de AFIP
-   `clave_privada.key` - Tu clave privada

### 2. Configuración

Edita los ejemplos y reemplaza:

-   `20294192345` con tu CUIT real
-   `'tu_passphrase'` con la contraseña de tu clave privada (si tiene)

### 3. Modo de operación

-   `'modo_produccion' => false` para homologación (testing)
-   `'modo_produccion' => true` para producción

## 💡 Comparación de métodos

### Método anterior (v1.0.0)

```php
// Obtener último comprobante (respuesta completa)
$ultimoComprobante = $facturacionElectronica->obtenerUltimoComprobante($puntoVenta, $tipoFactura);
$numeroFactura = $ultimoComprobante->FECompUltimoAutorizadoResult->CbteNro + 1;

// Preparar datos con números calculados manualmente
$datosFactura = [
    'PtoVta' => $puntoVenta,
    'CbteTipo' => $tipoFactura,
    'CbteDesde' => $numeroFactura,
    'CbteHasta' => $numeroFactura,
    // ... otros datos
];

// Autorizar
$respuesta = $facturacionElectronica->autorizarComprobante([$datosFactura]);
```

### Método nuevo (v1.1.0)

```php
// Obtener último número directamente
$ultimoNumero = $facturacionElectronica->obtenerUltimoNumeroComprobante($puntoVenta, $tipoFactura);

// Preparar datos sin números (se calculan automáticamente)
$datosFactura = [
    'PtoVta' => $puntoVenta,
    'CbteTipo' => $tipoFactura,
    // CbteDesde y CbteHasta se agregan automáticamente
    // ... otros datos
];

// Autorizar próximo comprobante automáticamente
$respuesta = $facturacionElectronica->autorizarProximoComprobante($datosFactura);
```

## ✨ Ventajas de los nuevos métodos

### `obtenerUltimoNumeroComprobante()`

-   ✅ **Más simple**: Devuelve directamente un `int`
-   ✅ **Menos errores**: No hay que acceder a propiedades anidadas
-   ✅ **Validación automática**: Verifica la estructura de respuesta
-   ✅ **Manejo robusto**: Excepciones específicas con mensajes claros

### `autorizarProximoComprobante()`

-   ✅ **Sin cálculos manuales**: El SDK calcula el próximo número
-   ✅ **Previene errores**: Elimina problemas de numeración
-   ✅ **Código más limpio**: Menos líneas, más legible
-   ✅ **Valores por defecto**: Soporta PtoVta=1 y CbteTipo=1 por defecto

## 🔧 Ejecución

```bash
# Ejecutar un ejemplo específico
php ejemplos/factura_A.php

# Ver demostración de nuevos métodos
php ejemplos/nuevos_metodos_v1_1.php

# Verificar estado del servidor
php ejemplos/estado_servidor.php
```

## ⚠️ Notas importantes

1. **Homologación primero**: Siempre prueba en modo homologación antes de producción
2. **Certificados válidos**: Asegúrate de tener certificados válidos de AFIP
3. **Manejo de errores**: Los ejemplos incluyen manejo básico de excepciones
4. **Compatibilidad**: Los métodos anteriores siguen funcionando normalmente

## 📚 Documentación adicional

-   [README principal](../README.md)
-   [CHANGELOG](../CHANGELOG.md)
-   [Documentación de AFIP](https://www.afip.gob.ar/ws/)

## 🤝 Contribuir

Si encuentras mejoras para los ejemplos o quieres agregar nuevos casos de uso, ¡las contribuciones son bienvenidas!
