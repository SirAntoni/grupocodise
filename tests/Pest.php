<?php

use App\Models\Client;
use App\Models\DispatchGuide;
use App\Models\Product;
use App\Models\Requirement;
use App\Models\Series;
use App\Models\User;
use App\Services\DispatchGuideService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Helpers de dominio para los tests
|--------------------------------------------------------------------------
*/

function testUser(): User
{
    return User::factory()->create();
}

function seedSeries(): void
{
    foreach ([
        ['document_type' => 'guia_remision', 'code' => 'T001'],
        ['document_type' => 'factura', 'code' => 'F001'],
        ['document_type' => 'nota_credito', 'code' => 'FC01'],
    ] as $data) {
        Series::query()->firstOrCreate([...$data, 'environment' => 'beta']);
    }
}

/**
 * Crea un requerimiento con ítems [product_id => cantidad] y devuelve la guía
 * en borrador generada a partir de él, con datos de transporte válidos.
 */
function draftGuide(Client $client, array $items, User $user): DispatchGuide
{
    $requirement = Requirement::query()->create([
        'code' => Requirement::nextCode(),
        'client_id' => $client->id,
        'crew_chief' => 'Jefe de Prueba',
        'district' => 'Lima',
        'delivery_address' => 'Obra de prueba 123',
        'required_date' => now()->addDay()->toDateString(),
        'created_by' => $user->id,
    ]);

    foreach ($items as $productId => $quantity) {
        $requirement->items()->create(['product_id' => $productId, 'quantity' => $quantity]);
    }

    $guide = app(DispatchGuideService::class)->createFromRequirement($requirement->fresh('items'), $user);

    $guide->update([
        'transport_mode' => 'privado',
        'vehicle_plate' => 'ABC-123',
        'driver_first_names' => 'Juan',
        'driver_last_names' => 'Pérez García',
        'driver_document_type' => '1',
        'driver_document' => '45678912',
        'driver_license' => 'Q45678912',
        'total_weight' => 100,
    ]);

    return $guide->fresh('items');
}
