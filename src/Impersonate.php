<?php

namespace STS\FilamentImpersonate;

use Filament\Tables\Actions\Action;

class Impersonate extends Action
{
    protected string $view = 'impersonate::icon';

    public static function make(string $name): static
    {
        return (new static($name))
            ->hidden(fn ($record) => !static::allowed(auth()->user(), $record));
    }

    public static function allowed($current, $target)
    {
        if($current->is($target)) {
            return false;
        }

        $userCanImpersonate = method_exists($current, 'canImpersonate')
            ? $current->canImpersonate()
            : true;

        $targetCanBeImpersonated = method_exists($target, 'canBeImpersonated')
            ? $target->canBeImpersonated()
            : true;

        return $userCanImpersonate && $targetCanBeImpersonated;
    }
}
