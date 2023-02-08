@props(['display', 'fixed', 'position', 'background'])

@if(app('impersonate')->isImpersonating())

@php
$display = $display ?? Filament\Facades\Filament::getUserName(auth()->user());
$fixed = $fixed ?? config('filament-impersonate.banner.fixed');
$position = $position ?? config('filament-impersonate.banner.position');
$background = $background ?? config('filament-impersonate.banner.background');
@endphp

<style>
    :root {
        --impersonate-banner-height: 50px;
    }
    html {
        margin-{{ $position }}: var(--impersonate-banner-height);
    }

    body.filament-body > div.filament-app-layout > aside.filament-sidebar {
        padding-{{ $position }}: var(--impersonate-banner-height);
    }

    #impersonate-banner {
        position: {{ $fixed ? 'fixed' : 'absolute' }};
        height: var(--impersonate-banner-height);
        {{ $position }}: 0;
        width: 100%;
        display: flex;
        column-gap: 20px;
        justify-content: center;
        align-items: center;
        background-color: #1f2937;
        color: #f3f4f6;
        border-bottom: 1px solid #374151;
    }

    .dark #impersonate-banner {
        background-color: #f3f4f6;
        color: #1f2937;
    }

    #impersonate-banner a {
        display: block;
        padding: 4px 20px;
        background-color: #d1d5db;
        color: #000;
        border-radius: 5px;
    }

    #impersonate-banner a:hover {
        background-color: #f3f4f6;
    }
    .dark #impersonate-banner a:hover {
        background-color: #9ca3af;
    }

    @if($fixed && $position === 'top')
    .filament-main-topbar {
        top: var(--impersonate-banner-height);
    }
    @endif

    @media print{
        aside, body {
            margin-top: 0;
        }

        #impersonate-banner {
            display: none;
        }
    }
</style>

<div id="impersonate-banner">
    <div>
        {{ __('filament-impersonate::banner.impersonating') }} <strong>{{ $display }}</strong>
    </div>

    <a href="{{ route('filament-impersonate.leave') }}">{{ __('filament-impersonate::banner.leave') }}</a>
</div>
@endIf
