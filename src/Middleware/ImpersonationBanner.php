<?php

namespace STS\FilamentImpersonate\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Lab404\Impersonate\Services\ImpersonateManager;
use function config;

class ImpersonationBanner
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        // Only touch illuminate responses (avoid binary, etc)
        if (!$response instanceof Response || !app(ImpersonateManager::class)->isImpersonating()) {
            return $response;
        }

        return $response->setContent(
            str_replace(
                "</body>",
                $this->bannerHtml($request) . "</body>",
                $response->getContent()
            )
        );
    }

    protected function bannerHtml($request)
    {
        return view('impersonate::auto-inject-banner', [
            'isFilament' => Str::startsWith($request->path(), config('filament.path')),
        ])->render();
    }
}
