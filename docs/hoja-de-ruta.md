# Hoja de Ruta - Completar Sistema de Gestión de Ventas

**Versión:** 1.0  
**Fecha de Creación:** Enero 2025  
**Objetivo:** Completar funcionalidades principales para producción

---

## 📅 Resumen Ejecutivo

Esta hoja de ruta está diseñada para completar las funcionalidades críticas y principales del sistema, priorizando los módulos bloqueantes y dejando mejoras visuales (como imágenes) para la fase final.

**Tiempo Estimado Total:** 4-6 semanas (desarrollador full-time)  
**Fases:** 5 fases (Fase 0 opcional de refactorización + 4 fases principales)  
**Prioridad:** CRÍTICA → ALTA → MEDIA

> **Nota sobre Fase 0:** Se ha agregado una fase opcional de refactorización de nomenclatura para mejorar la semántica del código. Es altamente recomendada pero no bloqueante. Ver `docs/refactorizacion-nomenclatura.md` para detalles completos.

---

## 🎯 Fase 0: Refactorización de Nomenclatura ✅ COMPLETADA
**Duración Estimada:** 1.5 semanas  
**Prioridad:** ALTA  
**Estado:** ✅ COMPLETADA

> **Nota:** ✅ Esta fase ha sido completada exitosamente. Todos los nombres han sido actualizados a la nueva nomenclatura. Ver documento completo en `docs/refactorizacion-nomenclatura.md`.

### 0.1 Preparación y Backup
**Tiempo:** 1 día  
**Dependencias:** Ninguna

**Tareas:**
- [ ] Hacer backup completo de base de datos
- [ ] Documentar todas las relaciones actuales
- [ ] Crear script de migración de datos
- [ ] Crear branch de git para refactorización

### 0.2 Refactorización de Modelos y Tablas
**Tiempo:** 1 semana  
**Dependencias:** 0.1

**Tareas:**
- [x] Renombrar `articles` → `products` ✅
- [x] Separar `subjects` → `customers` y `suppliers` ✅
- [x] Renombrar `documents` → `sales` ✅
- [x] Renombrar `document_details` → `sale_items` ✅
- [x] Renombrar `incomes` → `purchases` ✅
- [x] Renombrar `income_details` → `purchase_items` ✅
- [x] Renombrar `taxes` → `tax_rates` ✅
- [x] Renombrar `unit_measures` → `units` ✅
- [x] Actualizar todos los modelos ✅
- [x] Actualizar todas las relaciones ✅
- [x] Actualizar migraciones ✅

### 0.3 Actualizar Recursos y Servicios
**Tiempo:** 3 días  
**Dependencias:** 0.2

**Tareas:**
- [x] Actualizar todos los recursos de Filament ✅
- [x] Actualizar todos los servicios ✅
- [x] Actualizar factories y seeders ✅
- [x] Eliminar código antiguo ✅
- [ ] Probar todas las funcionalidades (en progreso)

**Ver documento completo:** `docs/refactorizacion-nomenclatura.md`

---

## 🎯 Fase 1: Correcciones Críticas y Base Sólida
**Duración Estimada:** 1 semana  
**Prioridad:** CRÍTICA

> **Nota:** ✅ La Fase 0 (Refactorización de Nomenclatura) ha sido completada. Todos los nombres han sido actualizados a la nueva nomenclatura (products, sales, purchases, etc.).

### 1.1 Corregir Errores en Productos
**Tiempo:** 2 horas  
**Dependencias:** Ninguna

**Tareas:**
- [x] Corregir `ProductResource.php`: usar `unit_id` correctamente ✅
- [x] Crear `UnitResource.php` completo con CRUD ✅
- [x] Agregar `UnitResource` al panel administrativo ✅
- [x] Probar creación/edición de productos con unidad de medida ✅

**Archivos Modificados:**
- `app/Filament/Resources/ProductResource.php` ✅
- `app/Filament/Resources/UnitResource.php` ✅

**Criterios de Aceptación:**
- ✅ Los artículos se pueden crear con unidad de medida correctamente
- ✅ Existe panel para gestionar unidades de medida
- ✅ La relación funciona correctamente

---

### 1.2 Completar y Corregir Módulo de Ventas (SaleResource)
**Tiempo:** 3 días  
**Dependencias:** 1.1, 1.3

**Tareas:**

#### 1.2.1 Limpiar y Corregir Formulario
- [ ] Eliminar código comentado y referencias a campos inexistentes
- [ ] Corregir selección de cliente (usar `customer_id` si Fase 0, o `subject_id` si no)
- [ ] Simplificar formulario eliminando campos que no existen en modelo
- [ ] Corregir generación de series y números de documento
- [ ] Implementar validación de cliente requerido

#### 1.2.2 Implementar Selección y Cálculo de Productos/Artículos
- [ ] Corregir select de productos/artículos (quitar búsqueda incorrecta)
- [ ] Implementar carga de precio de venta desde producto/artículo seleccionado
- [ ] Implementar cálculo automático de subtotal por línea (cantidad × precio)
- [ ] Agregar campo de descuento por línea (opcional)
- [ ] Calcular total por línea considerando descuento

#### 1.2.3 Integrar Sistema de Impuestos
- [ ] Agregar select de impuesto por artículo en el repeater
- [ ] Cargar impuestos disponibles desde `TaxRateResource`
- [ ] Calcular impuesto automáticamente (base × tasa)
- [ ] Mostrar base imponible, impuesto y total por línea
- [ ] Sumar totales de impuestos en el documento

#### 1.2.4 Implementar Cálculo de Totales
- [ ] Crear servicio `SaleCalculationService.php`
- [ ] Calcular subtotal base (suma de bases imponibles)
- [ ] Calcular total de impuestos
- [ ] Calcular total de descuentos
- [ ] Calcular total final (subtotal + impuestos - descuentos)
- [ ] Actualizar campos en tiempo real usando `live()` y `afterStateUpdated()`

#### 1.2.5 Validaciones de Negocio
- [ ] Validar stock disponible antes de agregar artículo
- [ ] Prevenir agregar artículo con stock 0
- [ ] Validar que cantidad no exceda stock disponible
- [ ] Validar que total sea mayor a 0
- [ ] Validar que haya al menos un artículo en la venta

#### 1.2.6 Corregir Tabla de Listado
- [ ] Mostrar columnas útiles: número de documento, cliente, fecha, total
- [ ] Agregar filtros por fecha, cliente, tipo de documento
- [ ] Agregar búsqueda por número de documento
- [ ] Formatear totales como moneda
- [ ] Agregar acción para ver detalle completo

**Archivos a Modificar:**
- `app/Filament/Resources/SaleResource.php`
- `app/Services/SaleCalculationService.php` (crear nuevo)
- `app/Filament/Resources/SaleResource/Pages/CreateSale.php`

**Criterios de Aceptación:**
- ✅ El formulario de venta funciona completamente
- ✅ Los totales se calculan automáticamente
- ✅ Los impuestos se aplican correctamente
- ✅ Se valida stock antes de vender
- ✅ La tabla muestra información útil

---

### 1.3 Integrar Descuentos en Ventas
**Tiempo:** 1 día  
**Dependencias:** 1.2

**Tareas:**
- [ ] Crear servicio `DiscountService.php` para validar y aplicar descuentos
- [ ] Agregar campo de código de descuento en formulario de venta
- [ ] Validar que descuento esté activo y en vigencia
- [ ] Validar que no exceda `max_uses`
- [ ] Validar que monto mínimo se cumpla
- [ ] Aplicar descuento al total (porcentaje o fijo)
- [ ] Incrementar contador `used` del descuento
- [ ] Mostrar descuento aplicado en resumen

**Archivos a Crear/Modificar:**
- `app/Services/DiscountService.php` (crear nuevo)
- `app/Filament/Resources/SaleResource.php`

**Criterios de Aceptación:**
- ✅ Los descuentos se pueden aplicar en ventas
- ✅ Se validan fechas y límites
- ✅ El contador de usos se actualiza
- ✅ Se muestra el descuento en el total

---

### 1.4 Corregir Errores en Enums
**Tiempo:** 30 minutos  
**Dependencias:** Ninguna

**Tareas:**
- [x] Corregir `TypeDiscount.php`: eliminar espacio en `'fixed '` → `'fixed'` ✅
- [x] Corregir `TypeContact.php`: eliminar espacio en `'phone '` → `'phone'` ✅
- [x] Verificar que no haya otros espacios en valores de enums ✅
- [x] Ejecutar migraciones si es necesario ✅

**Archivos a Modificar:**
- `app/Enums/TypeDiscount.php`
- `app/Enums/TypeContact.php`

---

## 🚀 Fase 2: Funcionalidades Esenciales de Producción
**Duración Estimada:** 1.5 semanas  
**Prioridad:** ALTA

### 2.1 Generación de PDFs para Documentos
**Tiempo:** 2 días  
**Dependencias:** 1.2

**Tareas:**
- [ ] Instalar paquete `barryvdh/laravel-dompdf` o similar
- [ ] Crear vista Blade para factura (`resources/views/pdf/invoice.blade.php`)
- [ ] Crear vista Blade para ticket (`resources/views/pdf/ticket.blade.php`)
- [ ] Crear servicio `PdfService.php` para generar PDFs
- [ ] Agregar acción "Descargar PDF" en tabla de documentos
- [ ] Agregar acción "Imprimir" que abra PDF en nueva ventana
- [ ] Incluir datos de empresa (configurables)
- [ ] Formatear números y fechas correctamente
- [ ] Incluir todos los detalles de la venta

**Archivos a Crear:**
- `app/Services/PdfService.php`
- `resources/views/pdf/invoice.blade.php`
- `resources/views/pdf/ticket.blade.php`

**Archivos a Modificar:**
- `app/Filament/Resources/SaleResource.php`
- `composer.json` (agregar dependencia)

**Criterios de Aceptación:**
- ✅ Se pueden generar PDFs de facturas y tickets
- ✅ Los PDFs tienen formato profesional
- ✅ Se pueden descargar e imprimir
- ✅ Incluyen toda la información necesaria

---

### 2.2 Mejorar Módulo de Compras (Ingresos) ✅
**Tiempo:** 1 día  
**Dependencias:** Ninguna

**Tareas:**
- [x] Implementar cálculo automático de totales en formulario ✅
- [x] Validar números de comprobante únicos por proveedor ✅
- [x] Agregar validación de fechas (no futuras) ✅
- [x] Mejorar tabla de listado con filtros y búsqueda ✅
- [ ] Agregar generación de PDF para comprobantes de compra (Pendiente Fase 2.1)
- [x] Mostrar resumen de totales en formulario ✅

**Archivos Modificados:**
- `app/Filament/Resources/PurchaseResource.php` ✅
- `app/Filament/Resources/PurchaseResource/Pages/CreatePurchase.php` ✅

**Criterios de Aceptación:**
- ✅ Los totales se calculan automáticamente
- ✅ Se validan comprobantes duplicados
- ✅ Se pueden generar PDFs de comprobantes

---

### 2.3 Dashboard con Métricas ✅
**Tiempo:** 2 días  
**Dependencias:** 1.2, 2.1

**Tareas:**
- [x] Crear widget de ventas del día ✅
- [x] Crear widget de ventas del mes ✅
- [x] Crear widget de productos con stock bajo ✅
- [x] Crear widget de clientes más importantes (top 5) ✅
- [x] Crear widget de productos más vendidos ✅
- [x] Agregar gráfico de ventas por día (últimos 7 días) ✅
- [x] Agregar gráfico de ventas por mes (últimos 6 meses) ✅
- [x] Mostrar totales formateados como moneda ✅

**Archivos Creados:**
- `app/Filament/Widgets/StatsOverview.php` ✅
- `app/Filament/Widgets/LowStockWidget.php` ✅
- `app/Filament/Widgets/TopCustomers.php` ✅
- `app/Filament/Widgets/TopProducts.php` ✅
- `app/Filament/Widgets/SalesChart.php` ✅
- `app/Filament/Widgets/MonthlySalesChart.php` ✅

**Archivos Modificados:**
- `app/Providers/Filament/AdminPanelProvider.php` ✅

**Criterios de Aceptación:**
- ✅ El dashboard muestra métricas útiles
- ✅ Los gráficos se actualizan correctamente
- ✅ Las métricas son precisas

---

### 2.4 Historial de Movimientos de Inventario (Kardex) ✅
**Tiempo:** 1 día  
**Dependencias:** 1.2, 2.2

**Tareas:**
- [x] Crear migración para tabla `inventory_movements` ✅
- [x] Crear modelo `InventoryMovement` ✅
- [x] Crear servicio para registrar movimientos (Integrado en `InventoryService`) ✅
- [x] Registrar movimientos al crear ingresos (entrada) ✅
- [x] Registrar movimientos al crear ventas (salida) ✅
- [x] Crear recurso Filament para ver historial (Kardex) ✅
- [x] Agregar filtros por artículo, fecha, tipo de movimiento ✅
- [x] Mostrar stock antes y después del movimiento ✅

**Archivos Creados:**
- `app/Models/InventoryMovement.php` ✅
- `app/Filament/Resources/InventoryMovementResource.php` ✅
- `database/migrations/2025_12_23_205413_create_inventory_movements_table.php` ✅

**Archivos Modificados:**
- `app/Services/InventoryService.php` ✅
- `app/Filament/Resources/PurchaseResource/Pages/CreatePurchase.php` ✅
- `app/Filament/Resources/SaleResource/Pages/CreateSale.php` ✅

**Criterios de Aceptación:**
- ✅ Se registran todos los movimientos de inventario
- ✅ Se puede consultar historial completo
- ✅ Los movimientos muestran información relevante

---

### 2.5 Mejorar Gestión de Clientes ✅
**Tiempo:** 1 día  
**Dependencias:** 1.2

**Tareas:**
- [x] Agregar relación con documentos (ventas) en modelo ✅
- [x] Crear relación manager para ver ventas del cliente ✅
- [x] Agregar campo de crédito/límite de crédito ✅
- [x] Agregar campo de notas/observaciones ✅
- [x] Mejorar tabla con más información útil ✅
- [x] Agregar filtros y búsqueda avanzada ✅
- [x] Mostrar total de compras del cliente ✅

**Archivos a Modificar:**
- `app/Models/Customer.php` ✅
- `app/Filament/Resources/CustomerResource.php` ✅
- `app/Filament/Resources/CustomerResource/RelationManagers/SalesRelationManager.php` ✅

**Criterios de Aceptación:**
- ✅ Se puede ver historial de compras por cliente
- ✅ Se puede agregar información adicional del cliente
- ✅ La búsqueda funciona correctamente

---

## 🔧 Fase 3: Validaciones y Seguridad
**Duración Estimada:** 1 semana  
**Prioridad:** ALTA

### 3.1 Validaciones de Negocio Completas
**Tiempo:** 2 días  
**Dependencias:** 1.2, 2.2

**Tareas:**
- [ ] Crear Form Requests para validaciones complejas
  - `app/Http/Requests/StoreSaleRequest.php`
  - `app/Http/Requests/StorePurchaseRequest.php`
  - `app/Http/Requests/StoreProductRequest.php`
- [ ] Validar stock disponible antes de confirmar venta
- [ ] Validar que precios sean positivos
- [ ] Validar que cantidades sean positivas
- [ ] Validar fechas (no futuras en compras, no pasadas muy antiguas)
- [ ] Validar números de documento únicos
- [ ] Validar que descuentos no excedan el total
- [ ] Agregar mensajes de error personalizados

**Archivos a Crear:**
- `app/Http/Requests/StoreSaleRequest.php`
- `app/Http/Requests/UpdateSaleRequest.php`
- `app/Http/Requests/StorePurchaseRequest.php`
- `app/Http/Requests/StoreProductRequest.php`

**Criterios de Aceptación:**
- ✅ Todas las validaciones funcionan correctamente
- ✅ Los mensajes de error son claros
- ✅ Se previenen errores de negocio

---

### 3.2 Sistema de Alertas de Stock Bajo
**Tiempo:** 1 día  
**Dependencias:** 3.1

**Tareas:**
- [ ] Agregar campo `min_stock` a tabla `articles`
- [ ] Crear migración para agregar campo
- [ ] Agregar campo al formulario de artículos
- [ ] Crear widget de alertas de stock bajo
- [ ] Crear notificación cuando stock esté por debajo del mínimo
- [ ] Agregar indicador visual en tabla de artículos (badge rojo)
- [ ] Filtrar artículos con stock bajo

**Archivos a Modificar:**
- `database/migrations/XXXX_add_min_stock_to_products_table.php`
- `app/Filament/Resources/ProductResource.php`
- `app/Filament/Widgets/LowStockWidget.php`

**Criterios de Aceptación:**
- ✅ Se puede configurar stock mínimo por artículo
- ✅ Se muestran alertas cuando stock está bajo
- ✅ El widget muestra artículos con stock bajo

---

### 3.3 Gestión de Pagos en Ventas
**Tiempo:** 2 días  
**Dependencias:** 1.2

**Tareas:**
- [ ] Crear migración para tabla `payments`
- [ ] Crear modelo `Payment`
- [ ] Agregar relación entre `Sale` y `Payment`
- [ ] Implementar registro de pagos en formulario de venta
- [ ] Permitir pagos parciales
- [ ] Calcular saldo pendiente
- [ ] Mostrar estado de pago (pagado/parcial/pendiente)
- [ ] Agregar filtros por estado de pago
- [ ] Crear relación manager para ver pagos de un documento

**Archivos a Crear:**
- `database/migrations/XXXX_create_payments_table.php`
- `app/Models/Payment.php`
- `app/Filament/Resources/SaleResource/RelationManagers/PaymentsRelationManager.php`

**Archivos a Modificar:**
- `app/Models/Sale.php`
- `app/Filament/Resources/SaleResource.php`

**Criterios de Aceptación:**
- ✅ Se pueden registrar pagos en ventas
- ✅ Se pueden hacer pagos parciales
- ✅ Se muestra saldo pendiente
- ✅ Se puede filtrar por estado de pago

---

### 3.4 Exportación de Datos
**Tiempo:** 1 día  
**Dependencias:** Ninguna

**Tareas:**
- [ ] Agregar exportación a Excel para artículos
- [ ] Agregar exportación a Excel para ventas
- [ ] Agregar exportación a Excel para clientes
- [ ] Agregar exportación a Excel para compras
- [ ] Usar paquete `maatwebsite/excel` o similar
- [ ] Formatear datos correctamente (fechas, monedas)
- [ ] Agregar acciones de exportación en tablas

**Archivos a Modificar:**
- `app/Filament/Resources/ProductResource.php`
- `app/Filament/Resources/SaleResource.php`
- `app/Filament/Resources/CustomerResource.php`
- `app/Filament/Resources/PurchaseResource.php`
- `composer.json` (agregar dependencia)

**Criterios de Aceptación:**
- ✅ Se pueden exportar datos a Excel
- ✅ Los datos están formateados correctamente
- ✅ Las exportaciones incluyen información relevante

---

### 3.5 Auditoría de Cambios (Spatie Activity Log)
**Tiempo:** 1 día  
**Dependencias:** Ninguna

**Tareas:**
- [ ] Instalar paquete `spatie/laravel-activitylog`
- [ ] Configurar logs para modelos principales (Product, Sale, Purchase, Customer, Supplier)
- [ ] Crear recurso Filament para visualizar logs de auditoría
- [ ] Implementar limpieza automática de logs antiguos

**Criterios de Aceptación:**
- ✅ Se registran cambios en campos críticos
- ✅ Se identifica al usuario que realizó el cambio
- ✅ Existe un panel de auditoría para el administrador

---

## 📊 Fase 4: Mejoras y Optimizaciones
**Duración Estimada:** 1 semana  
**Prioridad:** MEDIA

### 4.1 Mejoras en Búsqueda y Filtros
**Tiempo:** 1 día  
**Dependencias:** Ninguna

**Tareas:**
- [ ] Agregar búsqueda global en todas las tablas principales
- [ ] Agregar filtros por fecha en ventas y compras
- [ ] Agregar filtros por categoría en artículos
- [ ] Agregar filtros por tipo de documento en ventas
- [ ] Agregar filtros por estado de pago en ventas
- [ ] Agregar filtros guardados (si Filament lo soporta)
- [ ] Mejorar performance de búsquedas con índices

**Archivos a Modificar:**
- `app/Filament/Resources/ProductResource.php`
- `app/Filament/Resources/SaleResource.php`
- `app/Filament/Resources/PurchaseResource.php`
- `app/Filament/Resources/CustomerResource.php`

**Criterios de Aceptación:**
- ✅ Las búsquedas son rápidas y precisas
- ✅ Los filtros funcionan correctamente
- ✅ Se pueden combinar múltiples filtros

---

### 4.2 Optimización de Base de Datos
**Tiempo:** 1 día  
**Dependencias:** Ninguna

**Tareas:**
- [ ] Agregar índices a campos de búsqueda frecuente
  - `products.code`
  - `products.name`
  - `customers.document`
  - `suppliers.document`
  - `customers.name`
  - `suppliers.name`
  - `sales.invoice_number`
- [ ] Agregar índices a foreign keys
- [ ] Optimizar consultas N+1 usando eager loading
- [ ] Agregar soft deletes a tablas críticas
- [ ] Crear migraciones para índices

**Archivos a Crear:**
- `database/migrations/XXXX_add_indexes_to_tables.php`

**Criterios de Aceptación:**
- ✅ Las consultas son más rápidas
- ✅ No hay problemas de N+1
- ✅ Los índices mejoran el performance

---

### 4.3 Sistema de Devoluciones
**Tiempo:** 2 días  
**Dependencias:** 1.2, 2.1

**Tareas:**
- [ ] Crear migración para tabla `returns`
- [ ] Crear modelo `Return` (o `SaleReturn`)
- [ ] Crear recurso Filament para devoluciones
- [ ] Permitir devolver productos de una venta
- [ ] Reintegrar stock al hacer devolución
- [ ] Generar PDF de nota de crédito/devolución
- [ ] Relacionar devolución con venta original
- [ ] Validar que no se devuelva más de lo vendido

**Archivos a Crear:**
- `database/migrations/XXXX_create_returns_table.php`
- `app/Models/Return.php` (o `SaleReturn.php`)
- `app/Filament/Resources/ReturnResource.php`

**Criterios de Aceptación:**
- ✅ Se pueden registrar devoluciones
- ✅ El stock se reintegra correctamente
- ✅ Se generan documentos de devolución

---

### 4.4 Mejoras en UI/UX
**Tiempo:** 1 día  
**Dependencias:** Todas las fases anteriores

**Tareas:**
- [ ] Mejorar mensajes de éxito/error
- [ ] Agregar confirmaciones para acciones destructivas
- [ ] Mejorar layout de formularios complejos
- [ ] Agregar tooltips donde sea necesario
- [ ] Mejorar colores y estilos consistentes
- [ ] Agregar breadcrumbs si es necesario
- [ ] Mejorar responsive design

**Archivos a Modificar:**
- Todos los recursos de Filament
- `resources/css/app.css` (si es necesario)

**Criterios de Aceptación:**
- ✅ La interfaz es más intuitiva
- ✅ Los mensajes son claros
- ✅ El diseño es consistente

---

### 4.5 Configuración del Sistema
**Tiempo:** 1 día  
**Dependencias:** 2.1

**Tareas:**
- [ ] Crear migración para tabla `settings` o `company_settings`
- [ ] Crear modelo `Setting` o `CompanySetting`
- [ ] Crear recurso Filament para configuración
- [ ] Agregar campos: nombre empresa, RUC/NIF, dirección, teléfono, email
- [ ] Agregar logo de empresa (para PDFs)
- [ ] Agregar configuración de series y numeración
- [ ] Usar configuración en PDFs generados

**Archivos a Crear:**
- `database/migrations/XXXX_create_settings_table.php`
- `app/Models/Setting.php`
- `app/Filament/Resources/SettingResource.php`

**Criterios de Aceptación:**
- ✅ Se puede configurar información de la empresa
- ✅ Los PDFs usan la configuración
- ✅ La configuración es persistente

---

## 🎨 Fase 5: Imágenes y Mejoras Visuales (Final)
**Duración Estimada:** 3-4 días  
**Prioridad:** BAJA (según solicitud del usuario)

### 5.1 Gestión de Imágenes para Productos
**Tiempo:** 2 días  
**Dependencias:** Todas las fases anteriores

**Tareas:**
- [ ] Crear migración para agregar campo `image` a `articles`
- [ ] Configurar almacenamiento de archivos (local/S3)
- [ ] Agregar componente de upload de imagen en formulario
- [ ] Implementar redimensionamiento automático de imágenes
- [ ] Mostrar imagen en tabla de artículos (thumbnail)
- [ ] Mostrar imagen en detalle de artículo
- [ ] Permitir múltiples imágenes (galería)
- [ ] Agregar validación de tipo y tamaño de archivo

**Archivos a Modificar:**
- `database/migrations/XXXX_add_image_to_products_table.php`
- `app/Filament/Resources/ProductResource.php`
- `config/filesystems.php`

**Criterios de Aceptación:**
- ✅ Se pueden subir imágenes de productos
- ✅ Las imágenes se muestran correctamente
- ✅ Se valida tipo y tamaño

---

### 5.2 Imágenes para Categorías
**Tiempo:** 1 día  
**Dependencias:** 5.1

**Tareas:**
- [ ] Agregar campo `image` a categorías
- [ ] Agregar upload de imagen en formulario de categorías
- [ ] Mostrar imagen en tabla y detalle
- [ ] Usar imágenes como iconos en navegación (opcional)

**Archivos a Modificar:**
- `database/migrations/XXXX_add_image_to_categories_table.php`
- `app/Filament/Resources/CategoryResource.php`

---

### 5.3 Mejoras Visuales Finales
**Tiempo:** 1 día  
**Dependencias:** Todas las fases anteriores

**Tareas:**
- [ ] Revisar y mejorar diseño general
- [ ] Agregar iconos apropiados a recursos
- [ ] Mejorar colores del panel (personalización)
- [ ] Agregar favicon personalizado
- [ ] Revisar y mejorar mensajes en español
- [ ] Documentar funcionalidades principales

---

## 📋 Checklist de Entrega Final

Antes de considerar el sistema listo para producción, verificar:

### Funcionalidad
- [ ] Todos los módulos principales funcionan correctamente
- [ ] No hay errores críticos en el código
- [ ] Las validaciones funcionan como se espera
- [ ] Los cálculos son precisos

### Seguridad
- [ ] Todas las rutas están protegidas
- [ ] Las validaciones previenen inyecciones
- [ ] Los archivos subidos están validados
- [ ] No hay información sensible expuesta

### Performance
- [ ] Las consultas están optimizadas
- [ ] Los índices están creados
- [ ] No hay problemas de N+1
- [ ] El sistema responde en tiempo razonable

### Documentación
- [ ] README actualizado
- [ ] Manual de usuario básico
- [ ] Guía de instalación
- [ ] Documentación de API (si aplica)

### Testing
- [ ] Tests básicos de funcionalidad crítica
- [ ] Pruebas manuales completas
- [ ] Pruebas en entorno similar a producción

---

## 📈 Métricas de Progreso

### Por Fase
- **Fase 1:** 0% → 100% (Correcciones Críticas)
- **Fase 2:** 0% → 100% (Funcionalidades Esenciales)
- **Fase 3:** 0% → 100% (Validaciones y Seguridad)
- **Fase 4:** 0% → 100% (Mejoras y Optimizaciones)
- **Fase 5:** 0% → 100% (Imágenes y Visuales)

### Por Módulo
- **Productos/Artículos:** 80% → 100%
- **Ventas:** 30% → 100%
- **Compras:** 70% → 100%
- **Clientes:** 80% → 100%
- **Descuentos:** 70% → 100%
- **Impuestos:** 60% → 100%

### Por Fase de Refactorización
- **Fase 0:** ✅ 100% COMPLETADA (Refactorización de Nomenclatura)

---

## 🎯 Priorización de Tareas

### Semana 0 (Opcional - Solo si se hace refactorización)
1. Preparación y backup
2. Inicio de refactorización de nomenclatura

### Semana 1 (o Semana 2 si se hizo refactorización)
1. Correcciones críticas (Fase 1 completa)
2. Inicio de Fase 2 (PDFs)

### Semana 2
1. Completar Fase 2
2. Inicio de Fase 3

### Semana 3
1. Completar Fase 3
2. Inicio de Fase 4

### Semana 4
1. Completar Fase 4
2. Testing y correcciones

### Semana 5-6 (Opcional)
1. Fase 5 (Imágenes)
2. Mejoras adicionales
3. Documentación final

---

## 📝 Notas Importantes

1. **Dependencias entre tareas:** Algunas tareas dependen de otras. Revisar la sección "Dependencias" antes de comenzar.

2. **Testing continuo:** Probar cada funcionalidad después de implementarla, no esperar al final.

3. **Commits frecuentes:** Hacer commits pequeños y frecuentes con mensajes descriptivos.

4. **Backup:** Hacer backup de la base de datos antes de ejecutar migraciones importantes.

5. **Comunicación:** Si hay dudas sobre requerimientos, aclarar con el cliente antes de implementar.

6. **Documentación:** Documentar decisiones importantes y configuraciones especiales.

---

## 🔄 Actualizaciones de la Hoja de Ruta

**Versión 1.0** - Enero 2025
- Hoja de ruta inicial
- 5 fases definidas
- Tareas priorizadas

---

**Este documento es un plan de trabajo vivo. Debe actualizarse conforme se avance en el desarrollo y se identifiquen nuevas necesidades.**

