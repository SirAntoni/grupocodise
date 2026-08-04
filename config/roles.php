<?php

/*
|--------------------------------------------------------------------------
| Catálogo de roles
|--------------------------------------------------------------------------
|
| Solo etiquetas y descripciones para la interfaz: el acceso real lo definen
| los permisos que asigna RolesAndPermissionsSeeder, nunca este archivo.
| El orden de las claves es el orden en que se ofrecen al crear un usuario.
|
*/

return [
    'operaciones' => [
        'label' => 'Operaciones',
        'help' => 'Todo el ciclo del negocio: requerimientos, guías, productos, stock, cotizaciones, órdenes de compra, facturas, cobranzas y reportes.',
    ],
    'admin' => [
        'label' => 'Administrador',
        'help' => 'Todo lo de Operaciones más la administración: usuarios y series de numeración.',
    ],
    'logistica' => [
        'label' => 'Logística (acceso limitado)',
        'help' => 'Solo productos, stock, guías y reportes.',
    ],
    'pagos' => [
        'label' => 'Pagos (acceso limitado)',
        'help' => 'Solo cotizaciones, órdenes de compra, facturas, cobranzas y reportes.',
    ],
    'proveedores' => [
        'label' => 'Requerimientos (acceso limitado)',
        'help' => 'Solo clientes y requerimientos.',
    ],
];
