<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

use function Illuminate\Log\log;

class IsAdmin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {   $user = Auth::user(); 
        log::info('Authenticated user :',['user'=> Auth::user()]);
        if ($user && $user->role === 2) { // 2 تعني Admin
            return $next($request);
        }

        return response()->json(['error' => 'Unauthorized'], 403);
       
    }
}
