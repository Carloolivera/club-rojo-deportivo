<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;

class PublicMemberController extends Controller
{
    public function show(): \Illuminate\View\View
    {
        return view('portal.estado');
    }

    public function check(Request $request): JsonResponse
    {
        $request->validate(['dni' => 'required|string']);

        $hash = hash('sha256', $request->string('dni'));
        $member = Member::where('dni_hash', $hash)->first();

        if (! $member) {
            Log::channel('single')->info('portal.check', [
                'result' => 'not_found',
                'ip'     => $request->ip(),
            ]);

            return response()->json(['status' => 'al_dia']);
        }

        $hasOverdue = Fee::where('member_id', $member->id)
            ->where('status', 'overdue')
            ->exists();

        $status = $hasOverdue ? 'en_deuda' : 'al_dia';

        Log::channel('single')->info('portal.check', [
            'result' => $status,
            'ip'     => $request->ip(),
        ]);

        $payload = [
            'status'   => $status,
            'fees_url' => URL::temporarySignedRoute(
                'member.fees',
                now()->addDays(7),
                ['member' => $member->id]
            ),
        ];

        return response()->json($payload);
    }
}
