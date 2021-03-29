<?php

namespace STS\FilamentImpersonate;

use Filament\Tables\RecordActions\Action;
use Filament\Tables\RecordActions\Concerns\CanCallAction;

class Impersonate extends Action
{
    use CanCallAction;

    protected $view = 'impersonate::icon';

    protected $guard;

    protected $redirect;

    public static function make($name = 'impersonate')
    {
        return (new static($name))
            ->when(fn ($record) => static::allowed(auth()->user(), $record));
    }

    public function guard($guard)
    {
        $this->configure(function () use ($guard) {
            $this->guard = $guard;
        });

        return $this;
    }

    public function getGuard()
    {
        return $this->guard;
    }

    public function redirect($redirect)
    {
        $this->configure(function () use ($redirect) {
            $this->redirect = $redirect;
        });

        return $this;
    }

    public function getRedirect()
    {
        return $this->redirect;
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
