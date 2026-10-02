<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware 
{
    public function handle(
        Request $request, 
        Closure $next, 
        string ...$roles
        ): Response {
            $user = $request->user();

            abort_unless(
                $user 
                    && $user->account_status === 'active'
                    && in_array($user->role, $roles, true),
                Response::HTTP_FORBIDDEN
            );
            
            return $next($request);
            
        }
}
