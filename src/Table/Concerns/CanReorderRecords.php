<?php

namespace Filament\Tables\Table\Concerns;

use Closure;
use Filament\Tables\Actions\Action;

trait CanReorderRecords
{
    protected bool | Closure $isReorderable = true;

    protected string | Closure | null $reorderColumn = null;

    protected ?Closure $modifyReorderRecordsTriggerActionUsing = null;

    public function reorderRecordsTriggerAction(?Closure $callback): static
    {
        $this->modifyReorderRecordsTriggerActionUsing = $callback;

        return $this;
    }

    public function reorderable(string | Closure $column = null, bool | Closure $condition = null): static
    {
        $this->reorderColumn = $column;

        if ($condition !== null) {
            $this->isReorderable = $condition;
        }

        return $this;
    }

    public function getReorderRecordsTriggerAction(bool $isReordering): Action
    {
        $action = Action::make('reorderRecords')
            ->label($isReordering ? __('filament-tables::table.buttons.disable_reordering.label') : __('filament-tables::table.buttons.enable_reordering.label'))
            ->iconButton()
            ->icon($isReordering ? 'heroicon-m-check' : 'heroicon-m-chevron-up-down')
            ->color('gray')
            ->action('toggleTableReordering')
            ->table($this);

        if ($this->modifyReorderRecordsTriggerActionUsing) {
            $action = $this->evaluate($this->modifyReorderRecordsTriggerActionUsing, [
                'action' => $action,
                'isReordering' => $isReordering,
            ]) ?? $action;
        }

        if ($action->getView() === Action::BUTTON_VIEW) {
            $action->defaultSize('sm');
        }

        return $action;
    }

    public function getReorderColumn(): ?string
    {
        return $this->evaluate($this->reorderColumn);
    }

    public function isReorderable(): bool
    {
        return filled($this->getReorderColumn()) && $this->evaluate($this->isReorderable);
    }

    public function isReordering(): bool
    {
        return $this->getLivewire()->isTableReordering();
    }
}
