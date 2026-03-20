<?php

namespace App\Http\Middleware;

use App\Models\Document;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDocumentPermission
{
    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $document = $request->route('document');

        if ($document instanceof Document && !$request->user()->can($ability, $document)) {
            abort(403, 'Forbidden');
        }

        return $next($request);
    }
}
