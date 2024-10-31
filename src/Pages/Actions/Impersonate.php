<?php

namespace STS\FilamentImpersonate\Pages\Actions;

use Filament\Pages\Actions\Action;
use STS\FilamentImpersonate\Concerns\Impersonates;

class Impersonate extends Action
{
    use Impersonates;

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label(__('filament-impersonate::action.label'))
            ->icon('impersonate-icon')
            ->action(fn ($record) => $this->impersonate($this->getImpersonateRecord() ?? $record))
            ->hidden(fn ($record) => ! $this->canBeImpersonated($this->getImpersonateRecord() ?? $record));
    }
}
