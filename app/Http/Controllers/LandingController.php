<?php

namespace App\Http\Controllers;

use App\Mail\ContactInquiry;
use App\Models\Member;
use App\Models\Sport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LandingController extends Controller
{
    public function index()
    {
        $sports = Sport::where('is_active', true)->orderBy('name')->get();
        $activeMembers = Member::active()->count();

        return view('landing', compact('sports', 'activeMembers'));
    }

    public function contact(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'  => 'required|string|max:100',
            'phone' => 'required|string|max:30',
            'sport' => 'nullable|string|max:50',
        ]);

        Mail::to(config('club.contact_email', 'admin@club.com'))
            ->send(new ContactInquiry($data));

        return response()->json(['ok' => true]);
    }
}
