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
        --impersonate-dark-color: #1f2937;
        --impersonate-light-color: #f3f4f6;
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

        @if($style === 'dark')
        background-color: var(--impersonate-dark-color);
        color: var(--impersonate-light-color);
        border-bottom: 1px solid #374151;
        @else
        background-color: var(--impersonate-light-color);
        color: var(--impersonate-dark-color);
        @endif
    }

    @if($style === 'auto')
    .dark #impersonate-banner {
        background-color: var(--impersonate-light-color);
        color: var(--impersonate-dark-color);
        border-bottom: 1px solid #374151;
    }
    @endif

    #impersonate-banner a {
        display: block;
        padding: 4px 20px;
        border-radius: 5px;
        @if($style === 'dark')
        background-color: var(--impersonate-light-color);
        color: var(--impersonate-dark-color);
        @else
        background-color: var(--impersonate-dark-color);
        color: var(--impersonate-light-color);
        @endif
    }

    @if($style === 'auto')
    .dark #impersonate-banner a {
        background-color: var(--impersonate-dark-color);
        color: var(--impersonate-light-color);
    }
    @endif

    #impersonate-banner a:hover {
        @if($style === 'dark')
        background-color: var(--impersonate-light-color);
        @else
        background-color: var(--impersonate-dark-color);
        @endif
    }

    @if($style === 'auto')
    .dark #impersonate-banner a:hover {
        background-color: var(--impersonate-dark-color);
    }
    @endif
    
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
