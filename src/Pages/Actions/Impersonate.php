<?php

namespace STS\FilamentImpersonate\Pages\Actions;

use Filament\Facades\Filament;
use Filament\Pages\Actions\Action;
use STS\FilamentImpersonate\Contracts\ImpersonateAction;

class Impersonate extends Action
{
    use ImpersonateAction;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-impersonate::action.label'))
            ->icon('impersonate::icon')
            ->action(fn ($record) => $this->impersonate($record))
            ->hidden(static fn ($record) => !static::allowed(Filament::auth()->user(), $record));
    }
}
