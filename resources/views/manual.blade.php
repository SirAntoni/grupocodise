<x-app-layout>
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
        <div class="mb-6">
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Manual de usuario</h1>
            <p class="mt-1 text-sm text-slate-500">
                Guía paso a paso del sistema de despachos y facturación de GRUPO CODISE.
                Solo ves las secciones de los módulos a los que tu rol tiene acceso.
            </p>
        </div>

        <div class="grid gap-8 lg:grid-cols-[15rem,1fr]">
            {{-- Índice --}}
            <aside class="hidden lg:block">
                <nav class="sticky top-24 space-y-1 text-sm">
                    <p class="px-3 pb-2 text-[11px] font-semibold uppercase tracking-widest text-slate-400">Contenido</p>
                    <a href="#inicio" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">1 · Primeros pasos</a>
                    <a href="#estados" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">2 · Estados y colores</a>
                    @can('requirements.view')
                        <a href="#requerimientos" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">3 · Requerimientos</a>
                    @endcan
                    @can('guides.view')
                        <a href="#guias" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">4 · Guías de despacho</a>
                    @endcan
                    @can('products.view')
                        <a href="#inventario" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">5 · Productos y kardex</a>
                    @endcan
                    @can('clients.view')
                        <a href="#clientes" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">6 · Clientes</a>
                    @endcan
                    @can('quotations.view')
                        <a href="#cotizaciones" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">7 · Cotizaciones y OC</a>
                    @endcan
                    @can('invoices.view')
                        <a href="#facturas" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">8 · Facturación</a>
                    @endcan
                    @can('receivables.view')
                        <a href="#cobranzas" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">9 · Cobranzas</a>
                    @endcan
                    @can('reports.view')
                        <a href="#reportes" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">10 · Reportes</a>
                    @endcan
                    @can('users.manage')
                        <a href="#administracion" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">11 · Administración</a>
                    @endcan
                    <a href="#casos" class="block rounded-lg px-3 py-1.5 text-slate-600 hover:bg-white hover:text-brand-700">12 · Casos frecuentes</a>
                </nav>
            </aside>

            {{-- Contenido --}}
            <div class="min-w-0 space-y-6">

                <section id="inicio" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">1 · Primeros pasos</h2>
                    <ol class="mt-4 list-decimal space-y-2 ps-5 text-sm leading-relaxed text-slate-700">
                        <li>Entra a <span class="font-mono text-brand-700">app.grupocodise.pe</span> con el correo y la contraseña que te entregó el administrador.</li>
                        <li>La primera vez, ve a tu avatar (arriba a la derecha) → <strong>Mi perfil</strong> y cambia tu contraseña.</li>
                        <li>El <strong>menú lateral</strong> muestra los módulos de tu rol. Con el rol <em>Usuario</em> tienes el trabajo diario: requerimientos, guías, productos y stock, clientes, cotizaciones, órdenes de compra, facturas y reportes. Las cobranzas y la administración (usuarios y series) son del <em>Administrador</em>.</li>
                        <li>En celular, abre el menú con el botón <strong>☰</strong> de la esquina superior izquierda.</li>
                    </ol>
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <strong>Ojo:</strong> si tu cuenta es desactivada por administración, la sesión se cierra automáticamente en tu siguiente acción.
                    </div>
                </section>

                <section id="estados" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">2 · Estados y colores del sistema</h2>
                    <p class="mt-2 text-sm text-slate-600">Todas las pantallas usan las mismas etiquetas de color:</p>
                    <div class="mt-4 overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-sm">
                            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                <tr><th class="px-4 py-2">Etiqueta</th><th class="px-4 py-2">Significado</th></tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr><td class="px-4 py-2"><span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">Borrador</span></td><td class="px-4 py-2">Documento editable, sin valor tributario y sin número asignado.</td></tr>
                                <tr><td class="px-4 py-2"><span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-medium text-green-800">Emitida / Aceptado</span></td><td class="px-4 py-2">Documento con numeración oficial; en SUNAT, aceptado con CDR.</td></tr>
                                <tr><td class="px-4 py-2"><span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Pendiente de envío</span></td><td class="px-4 py-2">En cola hacia SUNAT; se envía solo en segundos/minutos.</td></tr>
                                <tr><td class="px-4 py-2"><span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Rechazado</span></td><td class="px-4 py-2">SUNAT lo observó: el motivo aparece en la pantalla del documento; corrige y usa <strong>Reenviar a SUNAT</strong>.</td></tr>
                                <tr><td class="px-4 py-2"><span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Anulada</span></td><td class="px-4 py-2">Guía anulada (stock restituido) o factura anulada por nota de crédito.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                @can('requirements.view')
                <section id="requerimientos" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">3 · Requerimientos (pedidos del cliente)</h2>
                        <span class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800">usuario</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-600">El flujo empieza cuando el cliente envía su pedido por correo. Ese correo se registra aquí.</p>
                    <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Registrar un requerimiento</h3>
                    <ol class="mt-2 list-decimal space-y-2 ps-5 text-sm leading-relaxed text-slate-700">
                        <li>Menú <strong>Requerimientos</strong> → botón <strong>Nuevo requerimiento</strong>.</li>
                        <li>Completa con los datos del correo: <strong>cliente</strong>, <strong>jefe de cuadrilla</strong>, <strong>distrito</strong>, <strong>fecha requerida</strong> y, si la tienes, la dirección de entrega (precarga la guía).</li>
                        <li>En <strong>Materiales</strong>, agrega cada producto con su cantidad (botón <strong>Agregar ítem</strong>).</li>
                        <li><strong>Guardar requerimiento</strong>. Queda en estado <em>Pendiente</em> con un código correlativo (REQ-00001…).</li>
                    </ol>
                    <ul class="mt-3 space-y-1 text-sm text-slate-700">
                        <li>• Solo se puede <strong>editar o anular</strong> mientras está <em>Pendiente</em>; al emitirse su primera guía pasa a <em>Despachado</em>.</li>
                        <li>• El índice es el <strong>historial de despachos programados</strong>: filtra por cliente, estado y rango de fecha requerida.</li>
                    </ul>
                </section>
                @endcan

                @can('guides.view')
                <section id="guias" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">4 · Guías de despacho</h2>
                        <span class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800">usuario</span>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Generar y emitir una guía</h3>
                    <ol class="mt-2 list-decimal space-y-2 ps-5 text-sm leading-relaxed text-slate-700">
                        <li>Desde el requerimiento (índice o detalle) pulsa <strong>Generar guía</strong>: se crea un <em>borrador</em> con el cliente, los materiales y la dirección precargados.</li>
                        <li>Completa los <strong>datos del traslado</strong>: fecha, peso bruto total y el punto de llegada con su distrito.</li>
                        <li>En <strong>Transporte</strong> elige la modalidad:
                            <em>público</em> pide RUC y razón social del transportista (usa <strong>Buscar</strong> para autocompletar por RUC);
                            <em>privado</em> pide placa y datos del conductor (con DNI el botón <strong>Buscar</strong> trae nombres y apellidos — verifica la separación).</li>
                        <li>Revisa por ítem la <strong>cantidad solicitada</strong> y la <strong>cantidad despachada</strong> (pueden diferir; ver sección Casos).</li>
                        <li>Pulsa <strong>Emitir guía</strong> y confirma: recién ahí se asigna el número correlativo (T001-…), se descuenta el stock por la cantidad <em>despachada</em> y la guía entra a la cola de envío a SUNAT (GRE).</li>
                    </ol>
                    <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-slate-500">PDF en 2 copias</h3>
                    <p class="mt-2 text-sm text-slate-700">En el detalle de una guía emitida: <strong>PDF 2 copias</strong> imprime la <em>copia cliente</em> (para que firme "Recibí conforme") y la <em>copia empresa</em>. También puedes descargar cada copia por separado.</p>
                    <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Duplicar, anular y reenviar</h3>
                    <ul class="mt-2 space-y-2 text-sm leading-relaxed text-slate-700">
                        <li>• <strong>Duplicar</strong> crea una copia en borrador, editable; no descuenta stock ni consume número hasta que la emitas.</li>
                        <li>• <strong>Anular</strong> (solo guías emitidas) exige un <em>motivo obligatorio</em> (ej. paro, sindicatos) y <strong>restituye automáticamente el stock</strong>. La guía queda visible con estado <em>Anulada</em>. Si la guía está en una factura activa, primero anula la factura.</li>
                        <li>• Si SUNAT rechazó el envío, corrige lo indicado y usa <strong>Reenviar a SUNAT</strong> (mismo número, sin duplicados).</li>
                    </ul>
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        <strong>Ojo:</strong> el sistema bloquea la emisión si un ítem dejaría el stock en negativo; verás una alerta con el faltante exacto.
                    </div>
                </section>
                @endcan

                @can('products.view')
                <section id="inventario" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">5 · Productos y kardex de stock</h2>
                        <span class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800">usuario</span>
                    </div>
                    <ol class="mt-4 list-decimal space-y-2 ps-5 text-sm leading-relaxed text-slate-700">
                        <li><strong>Productos → Nuevo producto</strong>: código, nombre y unidad de medida. El <em>stock inicial</em> opcional se registra como entrada en el kardex.</li>
                        <li>El stock <strong>no se edita a mano</strong>: cambia por entradas, ajustes, salidas por guía y restituciones por anulación — todo queda en el <strong>Kardex</strong> con saldo anterior/posterior, usuario y referencia.</li>
                        <li><strong>Kardex → Registrar entrada</strong> cuando llega mercadería; <strong>Registrar ajuste</strong> para mermas o correcciones (usa cantidad negativa para disminuir).</li>
                        <li>Un producto con movimientos <strong>no se elimina</strong>: usa <strong>Desactivar</strong> para que ya no aparezca en nuevos documentos.</li>
                    </ol>
                </section>
                @endcan

                @can('clients.view')
                <section id="clientes" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">6 · Clientes</h2>
                        <span class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800">usuario</span>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">admin</span>
                    </div>
                    <ol class="mt-4 list-decimal space-y-2 ps-5 text-sm leading-relaxed text-slate-700">
                        <li><strong>Clientes → Nuevo cliente</strong>: digita el <strong>RUC</strong> y pulsa <strong>Buscar</strong> — el padrón SUNAT llena razón social, dirección, distrito y ubigeo automáticamente.</li>
                        <li>Fíjate en los chips: <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">ACTIVO</span> <span class="inline-flex items-center rounded-full bg-green-100 px-2 py-0.5 text-xs font-medium text-green-800">HABIDO</span> significa que puedes facturarle con tranquilidad; en rojo o ámbar, consúltalo antes con pagos.</li>
                        <li>El <strong>ubigeo</strong> se usa en la guía electrónica (GRE); si la búsqueda no lo trajo, complétalo (6 dígitos).</li>
                        <li>Si el RUC ya existe, el sistema te avisa con el nombre del cliente registrado — no se duplican clientes.</li>
                    </ol>
                </section>
                @endcan

                @can('quotations.view')
                <section id="cotizaciones" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">7 · Cotizaciones y órdenes de compra</h2>
                        <span class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800">usuario</span>
                    </div>
                    <ol class="mt-4 list-decimal space-y-2 ps-5 text-sm leading-relaxed text-slate-700">
                        <li><strong>Cotizaciones → Nueva cotización</strong>: cliente, vigencia, <strong>vendedor</strong> e ítems con precio (sin IGV; los totales con IGV se calculan solos). Nace en estado <em>Enviada</em>.</li>
                        <li>Desde el detalle (o el listado) tienes <strong>Ver PDF</strong> y <strong>Descargar</strong>: es el documento que le mandas al cliente, con tu logo, el vendedor y las cuentas para el pago.</li>
                        <li>Cuando el cliente responda, márcala <strong>Aceptada</strong> o <strong>Rechazada</strong> (editable solo mientras está <em>Enviada</em>).</li>
                        <li>De una cotización aceptada pulsa <strong>Generar orden de compra</strong>: crea la OC con los datos precargados, enlazada a la cotización.</li>
                        <li>Si el cliente envía su propia OC: <strong>Órdenes de compra → Registrar OC recibida</strong> con número, fecha, monto y el <strong>PDF adjunto</strong>. Luego se puede vincular a la guía (campo "Orden de compra" del borrador de guía) y la factura la hereda.</li>
                    </ol>
                </section>
                @endcan

                @can('invoices.view')
                <section id="facturas" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">8 · Facturación electrónica</h2>
                        <span class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800">usuario</span>
                    </div>
                    <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Facturar una o varias guías</h3>
                    <ol class="mt-2 list-decimal space-y-2 ps-5 text-sm leading-relaxed text-slate-700">
                        <li>Elige primero el <strong>tipo de comprobante</strong>: <em>factura</em> para empresas con RUC o <em>boleta de venta</em> para personas. Cada uno lleva su propia numeración (F001-… y B001-…). Una factura solo puede emitirse a un cliente con RUC; si es una persona, el sistema te pedirá boleta.</li>
                        <li>Para la <strong>boleta de mostrador</strong> usa el cliente <em>CLIENTES VARIOS</em>: sirve mientras la venta no pase de S/ 700. Por encima de ese monto SUNAT exige el DNI del comprador, así que hay que registrarlo como cliente.</li>
                        <li><strong>Facturas → Nueva factura</strong>: elige la <strong>empresa</strong> y aparecerán todas sus guías emitidas que aún no están facturadas. <strong>Marca</strong> las que quieras incluir y pulsa <strong>Crear borrador</strong>. Si son muchas, usa el buscador por número.</li>
                        <li>Elige el <strong>vendedor</strong> (quien hizo la venta): se propone tu usuario, pero puedes cambiarlo. Sale impreso en la factura.</li>
                        <li>Dentro del borrador puedes <strong>agregar más guías</strong> de la misma empresa con el mismo selector; los ítems se <strong>consolidan</strong> sumando cantidades por producto. Una guía no puede estar en dos facturas activas — el sistema no la ofrece.</li>
                        <li>Digita el <strong>precio de venta</strong> de cada ítem (valor sin IGV): el subtotal, el IGV y el total se recalculan <strong>mientras escribes</strong>, sin esperar a guardar.</li>
                        <li>En <strong>Condiciones de pago</strong> eliges si la venta es al <em>contado</em> o al <em>crédito</em> —y a cuántos días—, la <strong>orden de compra</strong> del cliente si corresponde, y si la operación está <strong>sujeta a detracción</strong>.</li>
                        <li>Al marcar la detracción eliges el bien o servicio del catálogo de SUNAT y el sistema calcula el monto (por ejemplo 12 % para servicios), redondeado a soles enteros como exige el banco. Abajo verás el <strong>neto a pagar</strong>, que es lo que el cliente te transfiere: el resto lo deposita en la cuenta de detracciones.</li>
                        <li>Si aplica, marca <strong>Agregar línea de zona lejana</strong> y pon el monto.</li>
                        <li><strong>Emitir factura</strong>: asigna el número (F001-…), fija el vencimiento según los días de crédito de esa factura —al contado, el mismo día— y la envía a SUNAT en segundo plano. La detracción viaja declarada en el comprobante electrónico y sale impresa en el PDF con la cuenta del Banco de la Nación.</li>
                    </ol>
                    <h3 class="mt-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Después de emitir</h3>
                    <ul class="mt-2 space-y-2 text-sm leading-relaxed text-slate-700">
                        <li>• En el detalle ves las <strong>guías asociadas</strong>, el estado SUNAT y la cuenta por cobrar con su saldo. <strong>Las facturas al contado no generan cuenta por cobrar</strong>: el tablero de cobranza es solo para lo que está al crédito. <strong>Ver PDF</strong> lo abre en el navegador y <strong>Descargar</strong> lo baja como archivo; también tienes ambos desde el listado de facturas.</li>
                        <li>• Si SUNAT la <em>rechaza</em>: el motivo queda visible; corrige y pulsa <strong>Reenviar a SUNAT</strong> (misma numeración). <strong>Procesar envío ahora</strong> fuerza el envío sin esperar la cola.</li>
                        <li>• <strong>Anular con NC</strong> (facturas aceptadas): emite una <strong>nota de crédito por el total</strong> con motivo, descargable en PDF. Al aceptarla SUNAT, la factura queda <em>Anulada</em>, su cuenta por cobrar también, y las guías quedan <strong>libres para refacturar</strong>.</li>
                    </ul>
                </section>
                @endcan

                @can('receivables.view')
                <section id="cobranzas" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">9 · Cobranzas y semáforo</h2>
                        <span class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800">usuario</span>
                    </div>
                    <p class="mt-2 text-sm text-slate-700">Cada factura aceptada genera automáticamente una <strong>cuenta por cobrar a 30 días</strong>.</p>
                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm"><span class="font-semibold text-green-800">🟢 Verde</span><br><span class="text-green-700">Sigue en plazo.</span></div>
                        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm"><span class="font-semibold text-amber-800">🟡 Amarillo</span><br><span class="text-amber-700">Faltan 5 días o menos para vencer.</span></div>
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm"><span class="font-semibold text-red-800">🔴 Rojo</span><br><span class="text-red-700">Venció — priorizar cobro.</span></div>
                    </div>
                    <ol class="mt-4 list-decimal space-y-2 ps-5 text-sm leading-relaxed text-slate-700">
                        <li>El <strong>Tablero de cobranza</strong> se filtra por cliente, semáforo y estado; el color se recalcula solo cada madrugada.</li>
                        <li><strong>Registrar pago</strong> en la fila: fecha, monto (parcial o total), medio de pago y n° de operación. El saldo y el estado (<em>Pendiente / Pago parcial / Pagada</em>) se actualizan al instante.</li>
                        <li>El <strong>Historial de pagos</strong> lista todo lo cobrado, filtrable por cliente, factura y rango de fechas; un pago mal registrado se <strong>anula con motivo</strong> y el saldo se recalcula.</li>
                    </ol>
                </section>
                @endcan

                @can('reports.view')
                <section id="reportes" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">10 · Reportes</h2>
                        <span class="inline-flex items-center rounded-full bg-brand-100 px-2.5 py-0.5 text-xs font-medium text-brand-800">usuario</span>
                    </div>
                    <ul class="mt-4 space-y-2 text-sm leading-relaxed text-slate-700">
                        <li>• <strong>Facturas por periodo</strong>: mismas opciones de corte (semanal, quincenal, mensual o rango de fechas) sobre las facturas emitidas, con tarjetas de <em>total facturado</em> y <em>saldo por cobrar</em>, el vendedor de cada una y su Excel. Los borradores no aparecen porque todavía no tienen fecha de emisión.</li>
                        <li>• <strong>Guías por periodo</strong>: elige la empresa y el corte que necesites — <em>semanal</em> (lunes a domingo), <em>quincenal</em> (1–15 o 16–fin de mes), <em>mensual</em> o un <em>rango de fechas</em> cualquiera. Por defecto muestra emitidas; activa <em>Mostrar anuladas</em> si las necesitas. <strong>Exportar a Excel</strong> genera el archivo para enviar al cliente, con el periodo en el nombre.</li>
                        <li>• <strong>Diferencias sol./desp.</strong>: por periodo, compara lo <em>solicitado</em> contra lo <em>despachado</em> por producto — el control interno de esas diferencias. También exporta a Excel.</li>
                    </ul>
                </section>
                @endcan

                @can('users.manage')
                <section id="administracion" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <div class="flex flex-wrap items-center gap-2">
                        <h2 class="text-lg font-bold text-slate-900">11 · Administración</h2>
                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">admin</span>
                    </div>
                    <ul class="mt-4 space-y-2 text-sm leading-relaxed text-slate-700">
                        <li>• <strong>Usuarios</strong>: crea cada cuenta con su rol: <em>Usuario</em> para el trabajo diario o <em>Administrador</em> para quien además lleva cobranzas, series y usuarios. <strong>Desactivar</strong> corta el acceso de inmediato sin borrar su historial.</li>
                        <li>• <strong>Series de comprobantes</strong>: administra las series de guías (T…), facturas (F…) y notas de crédito. El correlativo avanza solo al emitir y nunca se edita. Para el pase a producción de SUNAT se registran series nuevas y se desactivan las de prueba.</li>
                        <li>• No hay registro público de cuentas: todos los usuarios se crean desde aquí.</li>
                    </ul>
                </section>
                @endcan

                <section id="casos" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
                    <h2 class="text-lg font-bold text-slate-900">12 · Casos frecuentes</h2>
                    <div class="mt-4 space-y-4 text-sm leading-relaxed text-slate-700">
                        <div>
                            <p class="font-semibold text-slate-900">«Pidieron 5 pero el técnico despachó 3»</p>
                            <p>En el borrador de la guía deja <em>solicitada = 5</em> y <em>despachada = 3</em>: el stock se descuenta por 3 y la diferencia queda registrada para el reporte de <strong>Diferencias sol./desp.</strong></p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">«Hay que anular una guía por un paro / sindicato»</p>
                            <p>Detalle de la guía → <strong>Anular</strong> → escribe el motivo. El stock vuelve solo al almacén y la guía queda visible como <em>Anulada</em> para el historial.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">«Necesito repetir un despacho parecido»</p>
                            <p><strong>Duplicar</strong> en la guía crea un borrador editable con los mismos datos; revisa cantidades y emite: recibirá su propio número y recién ahí toca el stock.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">«Facturé mal y el cliente pide corregir»</p>
                            <p>Si la factura está <em>aceptada</em>: <strong>Anular con NC</strong> (nota de crédito por el total, con motivo y PDF). Las guías quedan libres y puedes <strong>refacturar</strong> con los datos correctos. Si aún está en <em>borrador</em>, simplemente edítala o elimínala.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">«SUNAT rechazó el comprobante»</p>
                            <p>El código y motivo del rechazo aparecen en el detalle del documento. Corrige lo observado y pulsa <strong>Reenviar a SUNAT</strong> — el número se conserva y el reenvío nunca duplica.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">«¿Cuándo llamo al cliente para cobrar?»</p>
                            <p>Mira el <strong>Tablero de cobranza</strong>: trabaja primero los 🔴 (vencidos) y luego los 🟡 (vencen en ≤ 5 días). Cada pago que registres actualiza el saldo al instante.</p>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-900">«El RUC/DNI no aparece con el botón Buscar»</p>
                            <p>Puede ser un documento nuevo o una caída del servicio: digita los datos manualmente y continúa — nada se bloquea por la búsqueda.</p>
                        </div>
                    </div>
                </section>

                <p class="pb-4 text-center text-xs text-slate-400">GRUPO CODISE S.A.C. · Sistema de Despachos y Facturación · Manual v1</p>
            </div>
        </div>
    </div>
</x-app-layout>
