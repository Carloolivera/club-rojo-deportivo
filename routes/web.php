<?php

use App\Http\Controllers\LandingController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\MemberComplianceController;
use App\Http\Controllers\MpWebhookController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PublicMemberController;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [LandingController::class, 'index'])->name('landing');
Route::get('/idioma/{locale}', LocaleController::class)->name('locale.switch');
Route::post('/contacto', [LandingController::class, 'contact'])
    ->middleware('throttle:5,1')
    ->name('landing.contact');

Route::post('/mp/webhook', MpWebhookController::class)->name('mp.webhook');

Route::get('/estado', [PublicMemberController::class, 'show'])->name('portal.estado');
Route::post('/estado/check', [PublicMemberController::class, 'check'])
    ->middleware('throttle:10,1')
    ->name('portal.check');

Route::get('/mis-cuotas/{member}', function (Request $request, Member $member) {
    abort_unless($request->hasValidSignature(), 403);

    $member->load('sport', 'feeCategory');

    $fees = \App\Models\Fee::where('member_id', $member->id)
        ->orderByDesc('period')
        ->get();

    return view('member.fees', compact('member', 'fees'));
})->name('member.fees');

// Inicia el pago de una cuota: crea la preference MP on-demand y redirige al checkout.
Route::get('/mis-cuotas/{fee}/pagar', [PaymentController::class, 'redirect'])
    ->name('member.fees.pay');

// Back URL que MercadoPago redirige con ?status=success|failure|pending.
// No exponemos datos del fee — la vista sólo muestra el estado del pago.
Route::get('/mis-cuotas/{fee}/pago', function () {
    return view('member.fees-payment');
})->name('member.fees.payment');

Route::middleware(['auth'])->group(function () {
    Route::delete('/socios/{member}/datos', [MemberComplianceController::class, 'erase'])->name('members.erase');
    Route::get('/socios/{member}/exportar', [MemberComplianceController::class, 'export'])->name('members.export');

    Route::get('/admin/members/export/selected/excel', function () {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('view-reports'), 403);
        $ids     = session('export_member_ids', []);
        $records = \App\Models\Member::with('sport')->whereIn('id', $ids)->orderBy('status')->get();
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MembersReportExport($records),
            'socios-seleccionados.xlsx'
        );
    })->name('members.export.selected.excel');

    Route::get('/admin/fees/export/selected/excel', function () {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('view-reports'), 403);
        $ids     = session('export_fee_ids', []);
        $records = \App\Models\Fee::with('member.sport')->whereIn('id', $ids)->orderBy('status')->get();
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\FeesExport(records: $records),
            'cuotas-seleccionadas.xlsx'
        );
    })->name('fees.export.selected.excel');

    Route::get('/admin/fees/export/excel', function (Request $request) {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('view-reports'), 403);
        $period = $request->get('period', now()->format('Y-m'));
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\FeesExport($period),
            "cuotas-{$period}.xlsx"
        );
    })->name('fees.export.excel');

    Route::get('/admin/reports/export/excel', function () {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('view-reports'), 403);
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\MembersReportExport(),
            'reporte-socios.xlsx'
        );
    })->name('reports.export.excel');

    Route::get('/admin/reports/export/pdf', function () {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('view-reports'), 403);
        $members = \App\Models\Member::with('sport')->orderBy('status')->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.members-pdf', compact('members'));
        return response()->streamDownload(
            fn () => print($pdf->output()),
            'reporte-socios.pdf',
            ['Content-Type' => 'application/pdf']
        );
    })->name('reports.export.pdf');

    Route::get('/admin/reports/overdue/excel', function () {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('view-reports'), 403);
        return \Maatwebsite\Excel\Facades\Excel::download(
            new \App\Exports\OverdueExport(),
            'morosos-' . now()->format('Y-m-d') . '.xlsx'
        );
    })->name('reports.export.overdue.excel');

    Route::get('/admin/reports/overdue/pdf', function () {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('view-reports'), 403);

        $overdueFees = \App\Models\Fee::with(['member.sport'])
            ->where('status', 'overdue')
            ->orderBy('period')
            ->get();

        $debtors = $overdueFees
            ->groupBy('member_id')
            ->map(function ($fees) {
                $member = $fees->first()->member;
                return [
                    'name'    => $member?->name ?? '—',
                    'sport'   => $member?->sport?->name ?? '—',
                    'count'   => $fees->count(),
                    'total'   => $fees->sum('amount'),
                    'periods' => $fees->pluck('period')->sort()->implode(', '),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $totalDebt = $debtors->sum('total');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.overdue-pdf', compact('debtors', 'totalDebt'));
        return response()->streamDownload(
            fn () => print($pdf->output()),
            'morosos-' . now()->format('Y-m-d') . '.pdf',
            ['Content-Type' => 'application/pdf']
        );
    })->name('reports.export.overdue');

    Route::get('/admin/fees/export/pdf', function (Request $request) {
        abort_unless(\Illuminate\Support\Facades\Gate::allows('view-reports'), 403);
        $period = $request->get('period', now()->format('Y-m'));
        $fees   = \App\Models\Fee::with('member.sport')
            ->forPeriod($period)
            ->orderBy('status')
            ->get();
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.fees-pdf', compact('fees', 'period'));
        return response()->streamDownload(
            fn () => print($pdf->output()),
            "cuotas-{$period}.pdf",
            ['Content-Type' => 'application/pdf']
        );
    })->name('fees.export.pdf');
});
