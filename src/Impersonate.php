<?php

namespace STS\FilamentImpersonate;

use Filament\Tables\Actions\Action;

class Impersonate extends Action
{
    protected $view = 'impersonate::icon';

    public static function make($name = 'impersonate')
    {
        return (new static($name))
            ->when(fn ($record) => static::allowed(auth()->user(), $record));
    }

    public static function allowed($current, $target)
    {
        $userCanImpersonate = method_exists($current, 'canImpersonate')
            ? $current->canImpersonate()
            : $current->isFilamentAdmin();

        $targetCanBeImpersonated = method_exists($target, 'canBeImpersonated')
            ? $target->canBeImpersonated()
            : true;

        return $userCanImpersonate && $targetCanBeImpersonated;
    }
}
