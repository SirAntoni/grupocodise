# Especificación — Sistema de Despachos y Facturación Electrónica

Empresa peruana que despacha materiales a clientes empresa.

## Stack técnico

- Laravel 12 + PHP 8.4, Livewire 3 (con componentes full-page), Tailwind CSS
  - Nota (2026-07-26): la spec original pedía Laravel 11, pero Composer lo bloquea por avisos de seguridad activos (Laravel 11 quedó sin soporte de seguridad en marzo 2026). Se usa Laravel 12, API equivalente para este proyecto.
- MySQL 8
- Autenticación con Laravel Breeze (stack Livewire)
- Roles y permisos con spatie/laravel-permission
- PDFs con barryvdh/laravel-dompdf
- Exportación Excel con maatwebsite/excel
- Facturación electrónica: **Greenter** (greenter/greenter) para emisión directa a SUNAT — construcción del XML UBL 2.1, firma con certificado digital y envío. Encapsular en un servicio `App\Services\FacturacionElectronica` con interfaz, para aislar Greenter del resto de la aplicación.
- Facturas/notas de crédito: envío vía SEE (endpoints beta y producción configurables por .env). Guía de Remisión Electrónica: vía el API REST de SUNAT para GRE (credenciales API + clave SOL), que es el canal vigente para guías.
- Certificado digital: en desarrollo usar el certificado de pruebas de Greenter; en producción, certificado tributario del cliente almacenado de forma segura (fuera del repositorio, ruta por .env).

## Contexto de negocio

Flujo principal: el cliente envía un requerimiento por correo → se registra en el sistema → logística genera la guía de despacho (2 copias PDF: una la firma el cliente, otra queda con la empresa) → la guía se emite electrónicamente ante SUNAT (GRE) → pagos factura una o varias guías consolidadas → la factura genera una cuenta por cobrar a 30 días → se registran pagos y se controla la cobranza con un semáforo.

Tres roles operativos: **proveedores** (registra pedidos/requerimientos), **logistica** (guías, stock, productos), **pagos** (facturas, OC, cobranzas). Más un rol **admin** con acceso total.

## Módulos del sistema (alcance completo)

### 1. Usuarios y roles
- CRUD de usuarios con roles: admin, proveedores, logistica, pagos.
- Cada rol solo accede a sus módulos (middleware + menú dinámico).
- Auditoría básica: registrar usuario y timestamp en cada operación relevante.

### 2. Productos y stock
- CRUD de productos: código, nombre, unidad de medida, stock actual. Desactivar en vez de eliminar si tienen movimientos.
- Kardex simple de movimientos de stock (entradas, salidas por guía, restituciones por anulación).
- El stock se descuenta por **cantidad despachada** al emitir guía y se restituye al anularla. No permitir despachar sobre stock negativo (alerta).

### 3. Requerimientos (pedidos)
- Registro del requerimiento que llega por correo: cliente/empresa, materiales (ítems con cantidad), jefe de cuadrilla, distrito, fecha requerida.
- Estados: pendiente → despachado → anulado. Editable solo en pendiente.
- Historial de despachos programados con filtros por cliente, fecha y estado.

### 4. Guías de despacho
- Generación de guía a partir de un requerimiento (datos precargados).
- Cada ítem guarda **cantidad solicitada** y **cantidad despachada** (pueden diferir; control interno de la diferencia).
- Numeración correlativa por serie. PDF en 2 copias (cliente firma / empresa).
- Duplicar guía (copia editable, nueva numeración, no descuenta stock hasta emitir).
- Anulación con motivo obligatorio; la guía anulada queda visible con estado.
- Estados: borrador → emitida → anulada.

### 5. Facturación electrónica (SUNAT con Greenter)
- Emisión de Guía de Remisión Electrónica (GRE) y Factura Electrónica a través del servicio `FacturacionElectronica` (implementación con Greenter).
- Manejo de respuesta de SUNAT: aceptada (guardar CDR, XML firmado y PDF) o rechazada/observada (persistir código y motivo del error, permitir corregir y reenviar). Los envíos deben ser idempotentes: nunca duplicar numeración por un reintento.
- Cola de emisión (jobs) con reintentos ante caídas de SUNAT; el comprobante queda en estado "pendiente de envío" y se procesa en background.
- Factura: se asocian una o varias guías digitando su número; los ítems se consolidan. Una guía no puede estar en dos facturas activas.
- Precio de venta editable por ítem al facturar; recálculo automático de subtotal, IGV (18%) y total.
- Línea adicional opcional de "zona lejana" con monto editable.
- Anulación de factura mediante **nota de crédito por el total** (emitida electrónicamente, con motivo, PDF descargable); las guías asociadas se liberan para refacturar.
- Modo borrador para guías y facturas: sin valor tributario, editable, con acción explícita "Emitir".

### 6. Cotizaciones y órdenes de compra
- Registro de cotizaciones: cliente, ítems, precios, vigencia. Estados: enviada → aceptada → rechazada.
- Generar orden de compra a partir de una cotización aceptada (datos precargados).
- Registro de OC recibidas del cliente: número, fecha, monto, PDF adjunto; vinculables a guías y facturas.

### 7. Cuentas por cobrar
- Cada factura emitida genera una cuenta por cobrar con vencimiento a 30 días (fecha emisión + 30).
- Registro de pagos parciales o totales: fecha, monto, medio de pago. Saldo pendiente visible.
- **Semáforo de cobranza**: verde (en plazo), amarillo (≤ 5 días para vencer), rojo (vencida). Tablero filtrable por cliente y estado, cálculo diario automático (scheduler).
- Historial de pagos filtrable por cliente, factura y rango de fechas.

### 8. Reportes
- Reporte quincenal de guías por empresa (cortes: 1–15 y 16–fin de mes), exportable a Excel. Incluye emitidas, excluye anuladas (con toggle para mostrarlas).
- Reporte de diferencias solicitado vs. despachado por periodo.

## Convenciones

- Todo en español: rutas, vistas, mensajes. Código (clases, variables) en inglés.
- Form Requests para validación, Policies para autorización, Services para lógica de negocio compleja (facturación, stock).
- Migraciones con claves foráneas e índices apropiados. Soft deletes donde aplique.
- Componentes Livewire por módulo con tablas paginadas, búsqueda y filtros.
- Tests de features críticos con Pest: descuento/restitución de stock, consolidación de guías en factura, cálculo del semáforo.

## Empresa emisora

- **GRUPO CODISE S.A.C.** — RUC **20600896190** (datos en `config/facturacion.php` y `.env`).
- En desarrollo, `FACT_RUC` usa el RUC del ambiente de pruebas GRE (20161515648); en producción cambiar a 20600896190.
- Identidad visual: paleta `brand` (azul corporativo) + acento ámbar, tipografía Inter, layout con sidebar oscuro; componentes compartidos en `resources/views/components/`.

## Desarrollo (estado: las 8 iteraciones implementadas + rediseño frontend)

- URL local (Herd): https://ferreteria.test — MySQL 8 local, BD `ferreteria` (root sin contraseña).
- Usuarios seed (contraseña `password`): admin@ferreteria.test, proveedores@…, logistica@…, pagos@… (dominio ferreteria.test).
- `php artisan migrate:fresh --seed` regenera todo (roles, usuarios, series beta T001/F001/FC01, clientes y productos demo).
- Cola de emisión SUNAT: `php artisan queue:work` (o el scheduler la drena cada minuto); semáforo de cobranza: `php artisan cobranzas:actualizar-semaforo` (programado a diario).
- Certificado de pruebas: `php artisan facturacion:certificado-prueba`. Ambiente beta ya probado: factura y NC aceptadas por el SEE beta de SUNAT; GRE aceptada por el API de pruebas (RUC 20161515648 + MODDATOS/MODDATOS + credenciales test del API GRE).
- `FACT_DRIVER=fake` desactiva el envío real (lo usan los tests); producción: FACT_ENV=produccion, certificado real vía FACT_CERT_PATH, credenciales reales del API GRE, y sembrar series de producción (las beta se desactivan en Administración → Series).
- Tests: `php artisan test` (Pest, SQLite en memoria; 44 tests).
- Numeración: serie/número se asignan al Emitir (los borradores no consumen correlativo); los envíos a SUNAT son idempotentes (reintentos jamás renumeran).

## Plan de implementación progresiva

1. **Iteración 1:** scaffold del proyecto, autenticación, roles y permisos, layout base con menú por rol, migraciones completas de TODO el modelo de datos.
2. **Iteración 2:** productos + stock + kardex.
3. **Iteración 3:** requerimientos + historial.
4. **Iteración 4:** guías (generación, PDF 2 copias, duplicar, anular) — aún sin SUNAT.
5. **Iteración 5:** integración Greenter (factura + nota de crédito vía SEE, GRE vía API REST de SUNAT) con modo borrador, ambiente beta primero.
6. **Iteración 6:** cotizaciones + órdenes de compra.
7. **Iteración 7:** cuentas por cobrar + semáforo + registro de pagos.
8. **Iteración 8:** reportes y exportación Excel.
