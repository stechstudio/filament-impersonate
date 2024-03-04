<?php

namespace STS\FilamentImpersonate\Tables\Actions;

use Filament\Tables\Actions\Action;
use STS\FilamentImpersonate\Concerns\Impersonates;

class Impersonate extends Action
{
    use Impersonates;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-impersonate::action.label'))
            ->iconButton()
            ->icon('impersonate-icon')
            ->before(fn (Component $livewire) => $livewire->dispatchFormEvent(false))
            ->action(fn ($record) => $this->impersonate($record))
            ->hidden(fn ($record) => !$this->canBeImpersonated($record));
    }
}
