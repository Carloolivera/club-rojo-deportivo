<?php

namespace App\Http\Controllers;

use App\Models\Fee;
use App\Models\Member;
use Illuminate\Http\JsonResponse;

class MemberComplianceController extends Controller
{
    public function erase(\Illuminate\Http\Request $request, Member $member): JsonResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $member->name    = 'ANONIMIZADO';
        $member->email   = null;
        $member->phone   = null;
        $member->address = null;
        $member->dni     = 'ANON-' . $member->id;
        $member->save();

        return response()->json(['ok' => true]);
    }

    public function export(\Illuminate\Http\Request $request, Member $member): JsonResponse
    {
        abort_unless($request->user()?->hasRole('admin'), 403);

        $fees = Fee::where('member_id', $member->id)
            ->orderByDesc('period')
            ->get(['period', 'amount', 'status', 'paid_at', 'payment_method']);

        return response()->json([
            'member' => [
                'name'      => $member->name,
                'dni'       => $member->dni,
                'email'     => $member->email,
                'phone'     => $member->phone,
                'address'   => $member->address,
                'birthdate' => $member->birthdate?->format('Y-m-d'),
                'status'    => $member->status,
                'sport'     => $member->sport?->name,
                'exported_at' => now()->toIso8601String(),
            ],
            'fees' => $fees,
        ]);
    }
}
