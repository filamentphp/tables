<?php

namespace Filament\Tables\Table\Concerns;

use Closure;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\Position;
use Illuminate\Support\Arr;
use InvalidArgumentException;

trait HasHeaderActions
{
    /**
     * @var array<string, Action | BulkAction | ActionGroup>
     */
    protected array $headerActions = [];

    protected string | Closure | null $headerActionsPosition = null;

    public function headerActionsPosition(string | Closure $position = null): static
    {
        $this->headerActionsPosition = $position;

        return $this;
    }

    /**
     * @param  array<Action | BulkAction | ActionGroup> | ActionGroup  $actions
     */
    public function headerActions(array | ActionGroup $actions, string | Closure $position = null): static
    {
        foreach (Arr::wrap($actions) as $action) {
            $action->table($this);

            if ($action instanceof ActionGroup) {
                foreach ($action->getFlatActions() as $flatAction) {
                    if ($flatAction instanceof BulkAction) {
                        $this->cacheBulkAction($flatAction);
                    } elseif ($flatAction instanceof Action) {
                        $this->cacheAction($flatAction);
                    }
                }
            } elseif ($action instanceof Action) {
                $action->defaultSize('sm');

                $this->cacheAction($action);
            } elseif ($action instanceof BulkAction) {
                $action->defaultSize('sm');

                $this->cacheBulkAction($action);
            } else {
                throw new InvalidArgumentException('Table header actions must be an instance of ' . Action::class . ', ' . BulkAction::class . ' or ' . ActionGroup::class . '.');
            }

            $this->headerActions[] = $action;
        }

        $this->headerActionsPosition($position);

        return $this;
    }

    public function getHeaderActionsPosition(): string
    {
        $position = $this->evaluate($this->headerActionsPosition);

        if (filled($position)) {
            return $position;
        }

        return Position::End;
    }

    /**
     * @return array<string, Action | BulkAction | ActionGroup>
     */
    public function getHeaderActions(): array
    {
        return $this->headerActions;
    }
}
