<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, string $locale): RedirectResponse
    {
        abort_unless(in_array($locale, config('club.locales'), true), 404);

        $request->session()->put('locale', $locale);

        // Only redirect back to our own site, never to a URL supplied by the visitor.
        $previous = url()->previous();
        $target = str_starts_with($previous, url('/')) ? $previous : route('landing');

        return redirect()->to($target);
    }
}
