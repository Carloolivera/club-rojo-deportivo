<?php

namespace Database\Seeders;

use App\Models\Fee;
use App\Models\FeeCategory;
use App\Models\Guardian;
use App\Models\Member;
use App\Models\MemberPeriod;
use App\Models\Sport;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoSeeder extends Seeder
{
    private array $nombres = [
        'Juan Pablo', 'María José', 'Carlos Alberto', 'Ana Laura', 'Diego Martín',
        'Lucía Fernanda', 'Martín Ezequiel', 'Sofía Belén', 'Facundo Nicolás', 'Valentina',
        'Rodrigo Sebastián', 'Camila', 'Ignacio', 'Florencia', 'Nicolás Agustín',
        'Julieta', 'Tomás', 'Agustina', 'Matías', 'Micaela',
        'Gonzalo', 'Romina', 'Sebastián', 'Natalia', 'Federico',
        'Paula', 'Leandro', 'Cecilia', 'Hernán', 'Vanesa',
        'Ariel', 'Claudia', 'Maximiliano', 'Silvina', 'Pablo',
        'Karina', 'Gustavo', 'Marcela', 'Roberto', 'Patricia',
        'Jorge', 'Liliana', 'Alejandro', 'Graciela', 'Daniel',
        'Susana', 'Ricardo', 'Marta', 'Eduardo', 'Nora',
        'Ezequiel', 'Brenda', 'Ramiro', 'Celeste', 'Emiliano',
        'Soledad', 'Darío', 'Verónica', 'Cristian', 'Lorena',
        'Marcos', 'Andrea', 'Jonathan', 'Sandra', 'Lucas',
        'Mónica', 'Brian', 'Carolina', 'Kevin', 'Mariana',
        'Joaquín', 'Victoria', 'Franco', 'Luciana', 'Mauro',
        'Valeria', 'Sergio', 'Ivana', 'Leonardo', 'Roxana',
        'Adrián', 'Sabrina', 'Germán', 'Paola', 'Claudio',
        'Bárbara', 'Oscar', 'Eliana', 'Raúl', 'Natasha',
        'Iván', 'Melina', 'Walter', 'Débora', 'Marcelo',
        'Virginia', 'Javier', 'Alejandra', 'Esteban', 'Laura',
    ];

    private array $apellidos = [
        'González', 'Rodríguez', 'Gómez', 'Fernández', 'López',
        'Martínez', 'Díaz', 'Pérez', 'García', 'Sánchez',
        'Romero', 'Sosa', 'Torres', 'Álvarez', 'Ruiz',
        'Ramírez', 'Flores', 'Acosta', 'Benítez', 'Medina',
        'Suárez', 'Herrera', 'Aguirre', 'Castro', 'Ramos',
        'Ríos', 'Molina', 'Ortega', 'Delgado', 'Cruz',
        'Morales', 'Vargas', 'Vega', 'Cabrera', 'Rojas',
        'Quiroga', 'Luna', 'Bravo', 'Silva', 'Campos',
        'Giménez', 'Leiva', 'Ibáñez', 'Salinas', 'Reyes',
        'Leguizamón', 'Gutiérrez', 'Mansilla', 'Olivera', 'Pereyra',
    ];

    private array $calles = [
        'Rivadavia', 'San Martín', 'Belgrano', 'Mitre', 'Sarmiento',
        'Avellaneda', 'Moreno', 'Lavalle', 'Corrientes', 'Florida',
        'Maipú', 'Tucumán', 'Córdoba', 'Santa Fe', 'Mendoza',
        'Colón', 'Entre Ríos', 'Catamarca', 'Chacabuco', 'Pueyrredón',
    ];

    public function run(): void
    {
        $sports = Sport::all();
        $categories = FeeCategory::all();

        if ($sports->isEmpty() || $categories->isEmpty()) {
            $this->command->error('Correr SportSeeder y FeeCategorySeeder primero.');
            return;
        }

        $total = 200;
        $this->command->info("Creando {$total} socios demo...");

        $dniBase = 20_000_000;
        $usedNames = [];

        for ($n = 0; $n < $total; $n++) {
            $nombre = $this->nombres[array_rand($this->nombres)];
            $apellido = $this->apellidos[array_rand($this->apellidos)];
            $fullName = "{$nombre} {$apellido}";

            $birthYear = rand(1960, 2018);
            $birthdate = Carbon::create($birthYear, rand(1, 12), rand(1, 28));
            $age = now()->diffInYears($birthdate);
            $isMinor = $age < 18;

            $status = $this->randomStatus();
            $sport = $sports->random();
            $category = $categories->random();

            $member = Member::create([
                'name'            => $fullName,
                'dni'             => (string) ($dniBase + $n),
                'email'           => $this->randomEmail($nombre, $apellido, $n),
                'phone'           => '2291' . rand(400000, 599999),
                'address'         => $this->calles[array_rand($this->calles)] . ' ' . rand(100, 3000),
                'birthdate'       => $birthdate,
                'is_minor'        => $isMinor,
                'sport_id'        => $sport->id,
                'fee_category_id' => $category->id,
                'status'          => $status,
                'consent_at'      => now()->subDays(rand(1, 730)),
                'consent_ip'      => rand(192, 200) . '.' . rand(168, 200) . '.' . rand(1, 254) . '.' . rand(1, 254),
            ]);

            if ($isMinor) {
                $guardian = Guardian::create([
                    'name'         => $this->nombres[array_rand($this->nombres)] . ' ' . $apellido,
                    'relationship' => ['madre', 'padre', 'tutor'][rand(0, 2)],
                    'phone'        => '2291' . rand(300000, 399999),
                    'email'        => null,
                ]);
                $member->guardians()->attach($guardian->id, ['is_primary' => true]);
            }

            MemberPeriod::create([
                'member_id'  => $member->id,
                'sport_id'   => $member->sport_id,
                'started_at' => now()->subMonths(rand(1, 36)),
                'ended_at'   => $status === 'active' ? null : now()->subDays(rand(1, 180)),
            ]);

            $this->generateFees($member);
        }

        $this->command->info('Demo completado: ' . Member::count() . ' socios, ' . Fee::count() . ' fees.');
    }

    private function generateFees(Member $member): void
    {
        for ($i = 11; $i >= 0; $i--) {
            $period = Carbon::now()->subMonths($i)->format('Y-m');
            $isPaid = rand(1, 100) <= 72;

            Fee::create([
                'member_id'      => $member->id,
                'period'         => $period,
                'amount'         => $member->feeCategory->amount,
                'status'         => $isPaid ? 'paid' : ($i > 0 ? 'overdue' : 'pending'),
                'paid_at'        => $isPaid ? Carbon::now()->subMonths($i)->addDays(rand(1, 20)) : null,
                'payment_method' => $isPaid ? ['mp', 'manual', 'transfer', 'cash'][rand(0, 3)] : null,
            ]);
        }
    }

    private function randomStatus(): string
    {
        $r = rand(1, 100);
        if ($r <= 75) return 'active';
        if ($r <= 88) return 'inactive';
        return 'suspended';
    }

    private function randomEmail(string $nombre, string $apellido, int $n): ?string
    {
        if (rand(1, 100) > 70) return null;

        $nombre = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $nombre));
        $apellido = strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $apellido));
        $nombre = preg_replace('/[^a-z]/', '', $nombre);
        $apellido = preg_replace('/[^a-z]/', '', $apellido);
        $domains = ['gmail.com', 'hotmail.com', 'yahoo.com.ar', 'outlook.com'];

        return "{$nombre}.{$apellido}{$n}@{$domains[rand(0, 3)]}";
    }
}
