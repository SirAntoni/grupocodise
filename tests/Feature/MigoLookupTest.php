<?php

use App\Livewire\Clients\Index as ClientsIndex;
use App\Livewire\Guides\Form as GuidesForm;
use App\Models\Client;
use App\Models\Product;
use App\Services\MigoService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.migo.token' => 'token-de-prueba']);
});

it('normaliza la respuesta de RUC y cachea la consulta', function () {
    Http::fake([
        'api.migo.pe/api/v2/ruc/*' => Http::response([
            'success' => true,
            'ruc' => '20600896190',
            'nombre_o_razon_social' => 'GRUPO CODISE S.A.C.',
            'estado_del_contribuyente' => 'ACTIVO',
            'condicion_de_domicilio' => 'HABIDO',
            'ubigeo' => '150101',
            'distrito' => 'LIMA',
            'provincia' => 'LIMA',
            'departamento' => 'LIMA',
            'direccion' => 'AV. EJEMPLO 123, LIMA',
        ]),
    ]);

    $service = app(MigoService::class);

    $info = $service->lookupRuc('20600896190');

    expect($info['razon_social'])->toBe('GRUPO CODISE S.A.C.')
        ->and($info['estado'])->toBe('ACTIVO')
        ->and($info['condicion'])->toBe('HABIDO')
        ->and($info['ubigeo'])->toBe('150101')
        ->and($info['direccion'])->toBe('AV. EJEMPLO 123, LIMA');

    // Segunda consulta: sale del caché, sin nueva llamada HTTP.
    $service->lookupRuc('20600896190');
    Http::assertSentCount(1);
});

it('parsea el nombre del DNI con y sin coma', function () {
    Http::fake([
        'api.migo.pe/api/v2/dni/11111111' => Http::response(['success' => true, 'dni' => '11111111', 'nombre' => 'QUISPE MAMANI, LUIS ALBERTO']),
        'api.migo.pe/api/v2/dni/22222222' => Http::response(['success' => true, 'dni' => '22222222', 'nombre' => 'ANA MARIA ROJAS DIAZ']),
    ]);

    $service = app(MigoService::class);

    $conComa = $service->lookupDni('11111111');
    expect($conComa['nombres'])->toBe('LUIS ALBERTO')
        ->and($conComa['apellidos'])->toBe('QUISPE MAMANI');

    $sinComa = $service->lookupDni('22222222');
    expect($sinComa['nombres'])->toBe('ANA MARIA')
        ->and($sinComa['apellidos'])->toBe('ROJAS DIAZ');
});

it('devuelve null sin token, con RUC no encontrado o con error del servicio', function () {
    config(['services.migo.token' => null]);
    $service = app(MigoService::class);

    expect($service->isConfigured())->toBeFalse()
        ->and($service->lookupRuc('20600896190'))->toBeNull();
    Http::assertNothingSent();

    config(['services.migo.token' => 'token-de-prueba']);
    Http::fake([
        'api.migo.pe/api/v2/ruc/20111111111' => Http::response(['success' => false, 'message' => 'RUC not found'], 404),
        'api.migo.pe/api/v2/dni/*' => fn () => throw new \Exception('timeout'),
    ]);

    expect($service->lookupRuc('20111111111'))->toBeNull()
        ->and($service->lookupDni('99999999'))->toBeNull();

    // Los errores y "no encontrado" NO se guardan en el padrón local.
    expect(\App\Models\DocumentLookup::query()->count())->toBe(0);
});

it('bloquea los lookups de la guía cuando el permiso fue revocado', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    seedSeries();
    $user = testUser();
    $user->assignRole('usuario');

    $client = Client::factory()->create();
    $product = Product::factory()->create(['stock' => 10]);
    $guide = draftGuide($client, [$product->id => 2], $user);

    $component = Livewire::actingAs($user)->test(GuidesForm::class, ['dispatchGuide' => $guide->id]);

    $user->removeRole('usuario');
    app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $component->call('lookupDriver')->assertForbidden();
    Http::assertNothingSent();
});

it('autocompleta el cliente desde el RUC en el componente', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = testUser();
    $user->assignRole('admin');

    Http::fake([
        'api.migo.pe/api/v2/ruc/*' => Http::response([
            'success' => true,
            'nombre_o_razon_social' => 'CONSTRUCTORA NUEVA S.A.C.',
            'estado_del_contribuyente' => 'ACTIVO',
            'condicion_de_domicilio' => 'HABIDO',
            'ubigeo' => '150140',
            'distrito' => 'SANTIAGO DE SURCO',
            'direccion' => 'AV. PRIMAVERA 500',
        ]),
    ]);

    Livewire::actingAs($user)
        ->test(ClientsIndex::class)
        ->call('openCreate')
        ->set('ruc', '20512345670')
        ->call('lookupRuc')
        ->assertHasNoErrors()
        ->assertSet('business_name', 'CONSTRUCTORA NUEVA S.A.C.')
        ->assertSet('address', 'AV. PRIMAVERA 500')
        ->assertSet('district', 'SANTIAGO DE SURCO')
        ->assertSet('ubigeo', '150140');
});

it('autocompleta conductor y transportista en el formulario de guía', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    seedSeries();
    $user = testUser();
    $user->assignRole('usuario');

    $client = Client::factory()->create();
    Product::factory()->create(['stock' => 10]);
    $guide = draftGuide($client, [Product::query()->first()->id => 2], $user);

    Http::fake([
        'api.migo.pe/api/v2/dni/*' => Http::response(['success' => true, 'nombre' => 'CHAVEZ TORRES, PEDRO PABLO']),
        'api.migo.pe/api/v2/ruc/*' => Http::response(['success' => true, 'nombre_o_razon_social' => 'TRANSPORTES RAPIDOS S.A.']),
    ]);

    Livewire::actingAs($user)
        ->test(GuidesForm::class, ['dispatchGuide' => $guide->id])
        ->set('driver_document_type', '1')
        ->set('driver_document', '45678912')
        ->call('lookupDriver')
        ->assertHasNoErrors()
        ->assertSet('driver_first_names', 'PEDRO PABLO')
        ->assertSet('driver_last_names', 'CHAVEZ TORRES')
        ->set('transport_mode', 'publico')
        ->set('carrier_ruc', '20512345670')
        ->call('lookupCarrier')
        ->assertHasNoErrors()
        ->assertSet('carrier_name', 'TRANSPORTES RAPIDOS S.A.');
});

it('refresca un acierto del padrón local cuando envejece', function () {
    Http::fake([
        'api.migo.pe/api/v2/ruc/*' => Http::response(['success' => true, 'nombre_o_razon_social' => 'EMPRESA X S.A.C.']),
    ]);

    $service = app(MigoService::class);
    $service->lookupRuc('20600896190');
    $service->lookupRuc('20600896190');
    Http::assertSentCount(1); // dentro de los 30 días: padrón local

    $this->travel(31)->days();
    $service->lookupRuc('20600896190');
    Http::assertSentCount(2); // envejecido: se refresca contra el API
});

it('sin token sirve el dato del padrón local aunque esté viejo', function () {
    Http::fake();

    \App\Models\DocumentLookup::query()->create([
        'type' => 'ruc',
        'number' => '20600896190',
        'payload' => ['razon_social' => 'GRUPO CODISE S.A.C.'],
        'fetched_at' => now()->subDays(90),
    ]);

    config(['services.migo.token' => null]);

    $info = app(MigoService::class)->lookupRuc('20600896190');

    expect($info['razon_social'])->toBe('GRUPO CODISE S.A.C.');
    Http::assertNothingSent();
});

it('no consume el API si el RUC ya es un cliente registrado', function () {
    Http::fake();
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = testUser();
    $user->assignRole('admin');

    Client::factory()->create(['ruc' => '20512345670', 'business_name' => 'CLIENTE EXISTENTE S.A.C.']);

    Livewire::actingAs($user)
        ->test(ClientsIndex::class)
        ->call('openCreate')
        ->set('ruc', '20512345670')
        ->call('lookupRuc')
        ->assertHasErrors('ruc');

    Http::assertNothingSent();
});

it('exige configurar el token antes de buscar', function () {
    config(['services.migo.token' => null]);
    $this->seed(RolesAndPermissionsSeeder::class);
    $user = testUser();
    $user->assignRole('admin');

    Livewire::actingAs($user)
        ->test(ClientsIndex::class)
        ->call('openCreate')
        ->set('ruc', '20600896190')
        ->call('lookupRuc')
        ->assertHasErrors('ruc');
});
