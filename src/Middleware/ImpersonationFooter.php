<?php

namespace STS\FilamentImpersonate\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use function config;

class ImpersonationFooter
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // We only care if this is a Filament request
        if (!Str::startsWith($request->path(), config('filament.path'))) {
            return $response;
        }

        // Only touch illuminate responses (avoid binary, etc)
        if (!$response instanceof Response) {
            return $response;
        }

        return $response->setContent(
            str_replace("<footer", view('impersonate::footer')->render() . "<footer", $response->getContent())
        );
    }
}
