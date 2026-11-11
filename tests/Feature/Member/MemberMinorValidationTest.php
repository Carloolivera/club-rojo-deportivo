<?php

namespace Tests\Feature\Member;

use App\Models\FeeCategory;
use App\Models\Sport;
use App\Rules\UniqueDni;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Tests\TestCase;

class MemberMinorValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeRules(string $birthdate, ?int $memberId = null): array
    {
        $isMinor = Carbon::parse($birthdate)->age < 18;

        return [
            'name' => ['required', 'string', 'max:255'],
            'dni' => ['required', 'string', 'digits_between:7,8', new UniqueDni($memberId)],
            'birthdate' => ['required', 'date', 'before:today'],
            'sport_id' => ['required', 'exists:sports,id'],
            'fee_category_id' => ['required', 'exists:fee_categories,id'],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended', 'pending'])],
            'consent' => ['required', 'accepted'],
            'guardians' => [Rule::requiredIf($isMinor), 'array', 'min:1'],
            'guardians.*.name' => ['required_with:guardians', 'string', 'max:255'],
            'guardians.*.relationship' => ['required_with:guardians', 'string', 'max:100'],
        ];
    }

    private function messages(): array
    {
        return [
            'consent.accepted' => 'Debe aceptar el consentimiento para registrar al socio.',
            'guardians.required' => 'El socio es menor de edad — debe registrar al menos un responsable.',
            'birthdate.before' => 'La fecha de nacimiento debe ser anterior a hoy.',
        ];
    }

    private function baseData(array $overrides = []): array
    {
        $sport = Sport::factory()->create();
        $category = FeeCategory::factory()->create();

        return array_merge([
            'name' => 'Juan Pérez',
            'dni' => '55123456',
            'birthdate' => now()->subYears(10)->format('Y-m-d'),
            'sport_id' => $sport->id,
            'fee_category_id' => $category->id,
            'status' => 'active',
            'consent' => true,
        ], $overrides);
    }

    /** CA06: Socio menor sin responsable → falla validación */
    public function test_minor_without_guardian_fails_validation(): void
    {
        $data = $this->baseData();
        $validator = Validator::make($data, $this->makeRules($data['birthdate']), $this->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('guardians', $validator->errors()->toArray());
    }

    public function test_minor_with_guardian_passes_validation(): void
    {
        $data = $this->baseData([
            'guardians' => [
                ['name' => 'María García', 'relationship' => 'madre', 'phone' => '1123456789'],
            ],
        ]);
        $validator = Validator::make($data, $this->makeRules($data['birthdate']), $this->messages());

        $this->assertFalse($validator->fails(), implode(', ', $validator->errors()->all()));
    }

    public function test_adult_without_guardian_passes_validation(): void
    {
        $data = $this->baseData(['birthdate' => now()->subYears(25)->format('Y-m-d')]);
        $validator = Validator::make($data, $this->makeRules($data['birthdate']), $this->messages());

        $this->assertFalse($validator->fails(), implode(', ', $validator->errors()->all()));
    }

    public function test_consent_not_accepted_fails_validation(): void
    {
        $data = $this->baseData([
            'birthdate' => now()->subYears(25)->format('Y-m-d'),
            'consent' => false,
        ]);
        $validator = Validator::make($data, $this->makeRules($data['birthdate']), $this->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('consent', $validator->errors()->toArray());
    }

    public function test_birthdate_in_future_fails_validation(): void
    {
        $data = $this->baseData([
            'birthdate' => now()->addDays(1)->format('Y-m-d'),
            'guardians' => [['name' => 'Tutor', 'relationship' => 'tutor']],
        ]);
        $validator = Validator::make($data, $this->makeRules($data['birthdate']), $this->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('birthdate', $validator->errors()->toArray());
    }
}
