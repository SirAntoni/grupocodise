<?php

namespace App\Services\Facturacion;

/**
 * Resultado normalizado de un envío a SUNAT, independiente del canal
 * (SEE SOAP o API REST de GRE).
 */
class SunatResult
{
    public function __construct(
        public readonly bool $success,
        public readonly bool $accepted = false,
        public readonly bool $hasObservations = false,
        public readonly ?string $code = null,
        public readonly ?string $message = null,
        public readonly ?string $signedXml = null,
        public readonly ?string $cdrZip = null,
        public readonly ?string $hash = null,
        public readonly ?string $ticket = null,
        /** true si el envío quedó en proceso (GRE con ticket pendiente). */
        public readonly bool $inProgress = false,
    ) {}

    public static function accepted(?string $code, ?string $message, ?string $xml, ?string $cdr, ?string $hash, bool $observations = false): self
    {
        return new self(
            success: true,
            accepted: true,
            hasObservations: $observations,
            code: $code,
            message: $message,
            signedXml: $xml,
            cdrZip: $cdr,
            hash: $hash,
        );
    }

    public static function rejected(?string $code, ?string $message, ?string $xml = null, ?string $cdr = null): self
    {
        return new self(success: true, accepted: false, code: $code, message: $message, signedXml: $xml, cdrZip: $cdr);
    }

    public static function pending(string $ticket, ?string $xml, ?string $hash): self
    {
        return new self(success: true, signedXml: $xml, hash: $hash, ticket: $ticket, inProgress: true);
    }

    public static function error(?string $code, ?string $message, ?string $xml = null): self
    {
        return new self(success: false, code: $code, message: $message, signedXml: $xml);
    }
}
