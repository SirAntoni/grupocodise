<?php

namespace App\Services;

use App\Models\DocumentLookup;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

/**
 * Consulta RUC (padrón SUNAT) y DNI vía el API de Migo (api.migo.pe),
 * con padrón local en la tabla document_lookups: cada documento consume
 * crédito del API una sola vez y luego se sirve desde la base de datos,
 * refrescándose solo cuando envejece. A prueba de fallos: cualquier error
 * devuelve null (o el dato local, aunque esté viejo) y el usuario digita
 * los datos manualmente.
 */
class MigoService
{
    /** Días que un acierto se sirve desde la tabla antes de refrescar. */
    public const HIT_TTL_DAYS = 30;

    public function isConfigured(): bool
    {
        return (string) config('services.migo.token') !== '';
    }

    /**
     * @return array{razon_social: string, direccion: ?string, distrito: ?string,
     *               provincia: ?string, departamento: ?string, ubigeo: ?string,
     *               estado: ?string, condicion: ?string}|null
     */
    public function lookupRuc(string $ruc): ?array
    {
        $ruc = preg_replace('/\D/', '', $ruc);

        if (strlen($ruc) !== 11) {
            return null;
        }

        return $this->cachedLookup('ruc', $ruc, "ruc/{$ruc}", function (array $data) {
            if (empty($data['nombre_o_razon_social'])) {
                return false;
            }

            $direccion = ! empty($data['direccion']) ? $data['direccion'] : ($data['direccion_simple'] ?? null);

            return [
                'razon_social' => $data['nombre_o_razon_social'],
                'direccion' => $direccion ?: null,
                'distrito' => $data['distrito'] ?? null,
                'provincia' => $data['provincia'] ?? null,
                'departamento' => $data['departamento'] ?? null,
                'ubigeo' => $data['ubigeo'] ?? null,
                'estado' => $data['estado_del_contribuyente'] ?? null,
                'condicion' => $data['condicion_de_domicilio'] ?? null,
            ];
        });
    }

    /**
     * @return array{nombre: string, nombres: string, apellidos: string}|null
     */
    public function lookupDni(string $dni): ?array
    {
        $dni = preg_replace('/\D/', '', $dni);

        if (strlen($dni) !== 8) {
            return null;
        }

        return $this->cachedLookup('dni', $dni, "dni/{$dni}", function (array $data) {
            if (empty($data['nombre'])) {
                return false;
            }

            [$nombres, $apellidos] = $this->splitName($data);

            return [
                'nombre' => $data['nombre'],
                'nombres' => $nombres,
                'apellidos' => $apellidos,
            ];
        });
    }

    /**
     * Padrón local primero: si el documento está en document_lookups y sigue
     * fresco (≤ 30 días) NO se consume el API. Solo cuando falta o envejeció
     * se consulta Migo, y ÚNICAMENTE los éxitos se guardan: los errores y los
     * "no encontrado" no dejan fila (el rate limiter frena los reintentos).
     * Si el API falla o no hay token, se sirve el dato local aunque esté viejo.
     *
     * @param  callable(array): (array|false)  $normalize
     */
    protected function cachedLookup(string $type, string $number, string $path, callable $normalize): ?array
    {
        $row = DocumentLookup::query()->where('type', $type)->where('number', $number)->first();

        if ($row && $row->fetched_at->gt(now()->subDays(self::HIT_TTL_DAYS))) {
            return $row->payload;
        }

        if (! $this->isConfigured()) {
            return $row?->payload;
        }

        $limiterKey = 'migo:'.(auth()->id() ?? 'sistema');
        if (RateLimiter::tooManyAttempts($limiterKey, 15)) {
            Log::notice('Límite de consultas a Migo alcanzado', ['key' => $limiterKey]);

            return $row?->payload;
        }
        RateLimiter::hit($limiterKey, 60);

        $data = $this->get($path);

        if ($data === null) {
            // Error transitorio: mejor un dato viejo que ninguno.
            return $row?->payload;
        }

        if ($data === false || ($result = $normalize($data)) === false) {
            return null;
        }

        DocumentLookup::query()->updateOrCreate(
            ['type' => $type, 'number' => $number],
            ['payload' => $result, 'fetched_at' => now()],
        );

        return $result;
    }

    /**
     * Separa nombres y apellidos de forma defensiva: usa los campos separados
     * si el API los trae; si no, "APELLIDOS, NOMBRES" por la coma, y como
     * último recurso asume que las dos últimas palabras son los apellidos.
     *
     * @return array{0: string, 1: string}
     */
    protected function splitName(array $data): array
    {
        if (! empty($data['nombres']) && (! empty($data['apellido_paterno']) || ! empty($data['apellidos']))) {
            $apellidos = ! empty($data['apellidos'])
                ? $data['apellidos']
                : trim(($data['apellido_paterno'] ?? '').' '.($data['apellido_materno'] ?? ''));

            return [trim($data['nombres']), trim($apellidos)];
        }

        $full = trim((string) $data['nombre']);

        if (str_contains($full, ',')) {
            [$apellidos, $nombres] = array_pad(explode(',', $full, 2), 2, '');

            return [trim($nombres), trim($apellidos)];
        }

        $parts = preg_split('/\s+/', $full) ?: [];

        if (count($parts) <= 2) {
            return [$parts[0] ?? $full, $parts[1] ?? ''];
        }

        $apellidos = implode(' ', array_slice($parts, -2));
        $nombres = implode(' ', array_slice($parts, 0, -2));

        return [$nombres, $apellidos];
    }

    /**
     * @return array|false|null array = encontrado; false = no existe
     *                          (definitivo, cacheable); null = error transitorio.
     */
    protected function get(string $path): array|false|null
    {
        try {
            $response = Http::baseUrl(config('services.migo.base_url'))
                ->withToken(config('services.migo.token'))
                ->acceptJson()
                ->timeout(8)
                ->get($path);

            if ($response->status() === 404 || ($response->successful() && $response->json('success') === false)) {
                return false;
            }

            if (! $response->successful()) {
                Log::warning('Consulta a Migo devolvió error', ['path' => $path, 'status' => $response->status()]);

                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::warning('Consulta a Migo falló', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        }
    }
}
