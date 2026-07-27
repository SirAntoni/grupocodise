<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateTestCertificate extends Command
{
    protected $signature = 'facturacion:certificado-prueba {--force : Sobrescribir si ya existe}';

    protected $description = 'Genera un certificado autofirmado para firmar comprobantes en el ambiente beta de SUNAT';

    public function handle(): int
    {
        $path = config('facturacion.certificate.path');

        if (File::exists($path) && ! $this->option('force')) {
            $this->info("Ya existe un certificado en {$path} (usa --force para regenerarlo).");

            return self::SUCCESS;
        }

        $dn = [
            'countryName' => 'PE',
            'stateOrProvinceName' => 'LIMA',
            'localityName' => 'LIMA',
            'organizationName' => config('facturacion.company.razon_social'),
            'commonName' => config('facturacion.company.ruc'),
        ];

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        $csr = openssl_csr_new($dn, $key, ['digest_alg' => 'sha256']);
        $cert = openssl_csr_sign($csr, null, $key, 3650, ['digest_alg' => 'sha256']);

        openssl_x509_export($cert, $certPem);
        openssl_pkey_export($key, $keyPem);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $certPem.$keyPem);

        $this->info("Certificado de pruebas generado en {$path}.");
        $this->warn('Solo para el ambiente beta: en producción usa el certificado tributario real (FACT_CERT_PATH).');

        return self::SUCCESS;
    }
}
