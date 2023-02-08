<?php

namespace STS\FilamentImpersonate\Tables\Actions;

use Filament\Facades\Filament;
use Filament\Tables\Actions\Action;

class Impersonate extends Action
{
    use \STS\FilamentImpersonate\Contracts\ImpersonateAction;

    public static function getDefaultName(): ?string
    {
        return 'impersonate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->iconButton()
            ->icon('impersonate::icon')
            ->action(fn ($record) => $this->impersonate($record))
            ->hidden(fn ($record) => !static::allowed(Filament::auth()->user(), $record));
    }
}
