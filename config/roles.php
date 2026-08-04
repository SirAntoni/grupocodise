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
    'usuario' => [
        'label' => 'Usuario',
        'help' => 'Todo el trabajo diario: requerimientos, guías, productos y stock, clientes, cotizaciones, órdenes de compra, facturas y reportes. No ve cobranzas ni el resumen del panel.',
    ],
    'admin' => [
        'label' => 'Administrador',
        'help' => 'Todo lo anterior más cobranzas, el resumen del panel y la administración: usuarios y series de numeración.',
    ],
];
