@props(['style', 'display', 'fixed', 'position'])

@if(app('impersonate')->isImpersonating())

@php
$style = $style ?? config('filament-impersonate.banner.style');
$display = $display ?? Filament\Facades\Filament::getUserName(auth()->user());
$fixed = $fixed ?? config('filament-impersonate.banner.fixed');
$position = $position ?? config('filament-impersonate.banner.position');
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

        @if($style == 'dark')
        background-color: #1f2937;
        color: #f3f4f6;
        border-bottom: 1px solid #374151;
        @else
        background-color: #f3f4f6;
        color: #1f2937;
        @endif
    }

    #impersonate-banner a {
        display: block;
        padding: 4px 20px;
        background-color: #d1d5db;
        color: #000;
        border-radius: 5px;
    }

    #impersonate-banner a:hover {
        @if($style == 'dark')
        background-color: #f3f4f6;
        @else
        background-color: #9ca3af;
        @endif
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
        {{ __('Impersonating user') }} <strong>{{ $display }}</strong>
    </div>

    <a href="{{ route('filament-impersonate.leave') }}">{{ __('Leave') }}</a>
</div>
@endIf
