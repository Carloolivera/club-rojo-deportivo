<?php

namespace App\Imports;

use App\Models\FeeCategory;
use App\Models\Member;
use App\Models\Sport;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class MembersImport implements ToCollection, WithHeadingRow
{
    public int $imported = 0;
    public int $skipped  = 0;

    /** @var array<string> */
    public array $errors = [];

    private Collection $sports;
    private Collection $categories;

    public function __construct()
    {
        $this->sports     = Sport::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id]);
        $this->categories = FeeCategory::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [mb_strtolower($name) => $id]);
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2; // +2 porque la fila 1 es el encabezado

            $data = [
                'name'      => trim($row['nombre'] ?? ''),
                'dni'       => trim($row['dni'] ?? ''),
                'email'     => trim($row['email'] ?? '') ?: null,
                'phone'     => trim($row['telefono'] ?? '') ?: null,
                'birthdate' => trim($row['fecha_nacimiento'] ?? '') ?: null,
                'status'    => trim($row['estado'] ?? 'pending') ?: 'pending',
                'sport'     => mb_strtolower(trim($row['deporte'] ?? '')),
                'category'  => mb_strtolower(trim($row['categoria_cuota'] ?? '')),
            ];

            $validator = Validator::make($data, [
                'name'      => 'required|string|max:255',
                'dni'       => 'required|digits_between:7,8',
                'email'     => 'nullable|email|max:255',
                'birthdate' => 'nullable|date',
                'status'    => 'in:active,inactive,suspended,pending',
                'sport'     => 'required',
                'category'  => 'required',
            ], [
                'name.required'    => 'El nombre es obligatorio.',
                'dni.required'     => 'El DNI es obligatorio.',
                'dni.digits_between' => 'El DNI debe tener entre 7 y 8 dígitos.',
                'sport.required'   => 'El deporte es obligatorio.',
                'category.required' => 'La categoría de cuota es obligatoria.',
            ]);

            if ($validator->fails()) {
                $this->errors[] = "Fila {$line}: " . $validator->errors()->first();
                $this->skipped++;
                continue;
            }

            // Verificar duplicado por DNI
            $dniHash = hash('sha256', $data['dni']);
            if (Member::where('dni_hash', $dniHash)->exists()) {
                $this->errors[] = "Fila {$line}: DNI {$data['dni']} ya existe — omitido.";
                $this->skipped++;
                continue;
            }

            $sportId = $this->sports->get($data['sport']);
            if (! $sportId) {
                $this->errors[] = "Fila {$line}: deporte \"{$data['sport']}\" no encontrado.";
                $this->skipped++;
                continue;
            }

            $categoryId = $this->categories->get($data['category']);
            if (! $categoryId) {
                $this->errors[] = "Fila {$line}: categoría \"{$data['category']}\" no encontrada.";
                $this->skipped++;
                continue;
            }

            Member::create([
                'name'            => $data['name'],
                'dni'             => $data['dni'],
                'email'           => $data['email'],
                'phone'           => $data['phone'],
                'birthdate'       => $data['birthdate'],
                'is_minor'        => $data['birthdate'] ? now()->diffInYears($data['birthdate']) < 18 : false,
                'status'          => $data['status'],
                'sport_id'        => $sportId,
                'fee_category_id' => $categoryId,
            ]);

            $this->imported++;
        }
    }
}
