<?php

namespace Tests\Feature\Member;

use App\Imports\MembersImport;
use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class MembersImportTest extends TestCase
{
    use RefreshDatabase;

    private Sport $sport;
    private FeeCategory $category;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->sport    = Sport::factory()->create(['name' => 'Fútbol']);
        $this->category = FeeCategory::factory()->create(['name' => 'Adulto']);
    }

    private function makeRows(array $overrides = []): Collection
    {
        return collect([array_merge([
            'nombre'           => 'Juan Pérez',
            'dni'              => '12345678',
            'email'            => 'juan@test.com',
            'telefono'         => '1134567890',
            'fecha_nacimiento' => '1990-01-01',
            'estado'           => 'active',
            'deporte'          => 'fútbol',
            'categoria_cuota'  => 'adulto',
        ], $overrides)]);
    }

    public function test_importa_socio_valido(): void
    {
        $import = new MembersImport();
        $import->collection($this->makeRows());

        $this->assertSame(1, $import->imported);
        $this->assertSame(0, $import->skipped);
        $this->assertEmpty($import->errors);
        $this->assertDatabaseHas('members', ['dni_hash' => hash('sha256', '12345678')]);
    }

    public function test_omite_socio_con_dni_duplicado(): void
    {
        Member::factory()->create(['dni' => '12345678']);

        $import = new MembersImport();
        $import->collection($this->makeRows());

        $this->assertSame(0, $import->imported);
        $this->assertSame(1, $import->skipped);
        $this->assertStringContainsString('DNI 12345678 ya existe', $import->errors[0]);
    }

    public function test_omite_fila_con_deporte_desconocido(): void
    {
        $import = new MembersImport();
        $import->collection($this->makeRows(['deporte' => 'polo']));

        $this->assertSame(0, $import->imported);
        $this->assertSame(1, $import->skipped);
        $this->assertStringContainsString('deporte "polo" no encontrado', $import->errors[0]);
    }

    public function test_omite_fila_con_categoria_desconocida(): void
    {
        $import = new MembersImport();
        $import->collection($this->makeRows(['categoria_cuota' => 'vip']));

        $this->assertSame(0, $import->imported);
        $this->assertSame(1, $import->skipped);
        $this->assertStringContainsString('categoría "vip" no encontrada', $import->errors[0]);
    }

    public function test_falla_validacion_nombre_vacio(): void
    {
        $import = new MembersImport();
        $import->collection($this->makeRows(['nombre' => '']));

        $this->assertSame(0, $import->imported);
        $this->assertSame(1, $import->skipped);
        $this->assertStringContainsString('Fila 2:', $import->errors[0]);
    }

    public function test_falla_validacion_dni_muy_corto(): void
    {
        $import = new MembersImport();
        $import->collection($this->makeRows(['dni' => '123']));

        $this->assertSame(0, $import->imported);
        $this->assertSame(1, $import->skipped);
    }

    public function test_importa_multiples_filas_con_mezcla_validos_invalidos(): void
    {
        $rows = collect([
            ['nombre' => 'Válido Uno', 'dni' => '11111111', 'email' => 'uno@test.com', 'telefono' => null, 'fecha_nacimiento' => null, 'estado' => 'active', 'deporte' => 'fútbol', 'categoria_cuota' => 'adulto'],
            ['nombre' => 'Válido Dos', 'dni' => '22222222', 'email' => 'dos@test.com', 'telefono' => null, 'fecha_nacimiento' => null, 'estado' => 'active', 'deporte' => 'fútbol', 'categoria_cuota' => 'adulto'],
            ['nombre' => '',           'dni' => '33333333', 'email' => null,           'telefono' => null, 'fecha_nacimiento' => null, 'estado' => 'active', 'deporte' => 'fútbol', 'categoria_cuota' => 'adulto'],
        ]);

        $import = new MembersImport();
        $import->collection($rows);

        $this->assertSame(2, $import->imported);
        $this->assertSame(1, $import->skipped);
        $this->assertCount(1, $import->errors);
    }

    public function test_detecta_menor_de_edad_correctamente(): void
    {
        $import = new MembersImport();
        $import->collection($this->makeRows([
            'dni'              => '87654321',
            'fecha_nacimiento' => now()->subYears(10)->format('Y-m-d'),
        ]));

        $this->assertSame(1, $import->imported);
        $member = Member::where('dni_hash', hash('sha256', '87654321'))->first();
        $this->assertTrue($member->is_minor);
    }

    public function test_importa_sin_email_ni_telefono(): void
    {
        $import = new MembersImport();
        $import->collection($this->makeRows([
            'dni'      => '99999999',
            'email'    => '',
            'telefono' => '',
        ]));

        $this->assertSame(1, $import->imported);
        // email y phone nulos se guardan como null cifrado → verificar vía modelo
        $member = Member::where('dni_hash', hash('sha256', '99999999'))->first();
        $this->assertNotNull($member);
        $this->assertNull($member->getRawOriginal('email'));
        $this->assertNull($member->getRawOriginal('phone'));
    }

    public function test_estado_por_defecto_es_pending(): void
    {
        $import = new MembersImport();
        $import->collection($this->makeRows(['estado' => '']));

        $this->assertSame(1, $import->imported);
        $member = Member::where('dni_hash', hash('sha256', '12345678'))->first();
        $this->assertSame('pending', $member->status);
    }

    public function test_estado_invalido_es_rechazado_por_validacion(): void
    {
        $import = new MembersImport();
        $import->collection($this->makeRows(['estado' => 'desconocido']));

        $this->assertSame(0, $import->imported);
        $this->assertSame(1, $import->skipped);
    }

    public function test_numero_de_fila_correcto_en_mensaje_de_error(): void
    {
        $rows = collect([
            ['nombre' => 'Válido',   'dni' => '11111111', 'email' => null, 'telefono' => null, 'fecha_nacimiento' => null, 'estado' => 'active', 'deporte' => 'fútbol', 'categoria_cuota' => 'adulto'],
            ['nombre' => '',         'dni' => '22222222', 'email' => null, 'telefono' => null, 'fecha_nacimiento' => null, 'estado' => 'active', 'deporte' => 'fútbol', 'categoria_cuota' => 'adulto'],
        ]);

        $import = new MembersImport();
        $import->collection($rows);

        $this->assertStringContainsString('Fila 3:', $import->errors[0]);
    }
}
