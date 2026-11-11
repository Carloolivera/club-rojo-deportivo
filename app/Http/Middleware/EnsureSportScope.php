<?php

namespace App\Http\Middleware;

use App\Models\Member;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSportScope
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->hasRole('manager')) {
            $member = $request->route('member');

            if ($member instanceof Member && $member->sport_id !== $user->sport_id) {
                activity()
                    ->causedBy($user)
                    ->on($member)
                    ->inLog('security')
                    ->log('idor_attempt');

                abort(403);
            }
        }

        return $next($request);
    }
}
