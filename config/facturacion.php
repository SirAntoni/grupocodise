<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Empresa emisora
    |--------------------------------------------------------------------------
    | Datos tributarios del emisor. En desarrollo se usan el RUC y las
    | credenciales de prueba de SUNAT (MODDATOS) que Greenter acepta en beta.
    */

    'company' => [
        'ruc' => env('FACT_RUC', '20600896190'),
        'razon_social' => env('FACT_RAZON_SOCIAL', 'GRUPO CODISE S.A.C.'),
        'nombre_comercial' => env('FACT_NOMBRE_COMERCIAL', 'GRUPO CODISE'),
        'address' => [
            'direccion' => env('FACT_DIRECCION', 'AV. LOS INDUSTRIALES 123'),
            'ubigeo' => env('FACT_UBIGEO', '150101'),
            'distrito' => env('FACT_DISTRITO', 'LIMA'),
            'provincia' => env('FACT_PROVINCIA', 'LIMA'),
            'departamento' => env('FACT_DEPARTAMENTO', 'LIMA'),
        ],
        'telefono' => env('FACT_TELEFONO'),
        'email' => env('FACT_EMAIL'),

        // Logo para los PDFs: ruta relativa a public/. Si el archivo no existe
        // se imprime solo la razón social, sin romper nada.
        'logo' => env('FACT_LOGO', 'images/logo-empresa.png'),

        /*
         * Cuentas para el pie de la factura. Se imprimen solo las que tengan
         * número, así que mientras estén vacías el bloque no aparece.
         */
        'cuentas' => [
            ['banco' => 'BCP', 'tipo' => 'Cuenta corriente soles', 'numero' => env('FACT_CTA_BCP'), 'cci' => env('FACT_CCI_BCP')],
            ['banco' => 'BBVA', 'tipo' => 'Cuenta de ahorro soles', 'numero' => env('FACT_CTA_BBVA'), 'cci' => env('FACT_CCI_BBVA')],
        ],
        'yape' => env('FACT_YAPE'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Parámetros tributarios
    |--------------------------------------------------------------------------
    */

    'igv_rate' => (float) env('FACT_IGV_RATE', 0.18),
    'payment_due_days' => (int) env('FACT_DUE_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Ambiente y credenciales SUNAT
    |--------------------------------------------------------------------------
    | environment: beta | produccion
    | SEE (facturas y notas de crédito): SOAP con clave SOL.
    | GRE (guías de remisión): API REST con client_id/client_secret + clave SOL.
    */

    'environment' => env('FACT_ENV', 'beta'),

    // greenter = envío real a SUNAT; fake = acepta todo sin salir (tests/demo).
    'driver' => env('FACT_DRIVER', 'greenter'),

    'sol' => [
        'user' => env('FACT_SOL_USER', 'MODDATOS'),
        'password' => env('FACT_SOL_PASS', 'moddatos'),
    ],

    'gre_api' => [
        'client_id' => env('FACT_GRE_CLIENT_ID') ?: 'test-85e5b0ae-255c-4891-a595-0b98c65c9854',
        'client_secret' => env('FACT_GRE_CLIENT_SECRET') ?: 'test-Hty/M6QshYvPgItX2P0+Kw==',
    ],

    'endpoints' => [
        'beta' => [
            'see' => env('FACT_SEE_BETA', 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService'),
            'gre_auth' => env('FACT_GRE_AUTH_BETA', 'https://gre-test.nubefact.com/v1'),
            'gre_cpe' => env('FACT_GRE_CPE_BETA', 'https://gre-test.nubefact.com/v1'),
        ],
        'produccion' => [
            'see' => env('FACT_SEE_PROD', 'https://e-factura.sunat.gob.pe/ol-ti-itcpfegem/billService'),
            'gre_auth' => env('FACT_GRE_AUTH_PROD', 'https://api-seguridad.sunat.gob.pe/v1'),
            'gre_cpe' => env('FACT_GRE_CPE_PROD', 'https://api-cpe.sunat.gob.pe/v1'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Certificado digital
    |--------------------------------------------------------------------------
    | En desarrollo se genera/usa un certificado de pruebas (ver
    | FacturacionServiceProvider). En producción apuntar FACT_CERT_PATH a la
    | ruta del certificado tributario, FUERA del repositorio.
    */

    'certificate' => [
        'path' => env('FACT_CERT_PATH') ?: storage_path('app/certs/certificado.pem'),
        'password' => env('FACT_CERT_PASS') ?: '',
    ],

];
