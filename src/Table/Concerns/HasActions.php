<?php

namespace Filament\Tables\Table\Concerns;

use Closure;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\Position;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use InvalidArgumentException;

trait HasActions
{
    /**
     * @var array<Action | ActionGroup>
     */
    protected array $actions = [];

    /**
     * @var array<string, Action>
     */
    protected array $flatActions = [];

    protected string | Closure | null $actionsColumnLabel = null;

    protected string | Closure | null $actionsAlignment = null;

    protected string | Closure | null $actionsPosition = null;

    /**
     * @param  array<Action | ActionGroup> | ActionGroup  $actions
     */
    public function actions(array | ActionGroup $actions, string | Closure $position = null): static
    {
        foreach (Arr::wrap($actions) as $action) {
            $action->table($this);

            if ($action instanceof ActionGroup) {
                /** @var array<string, Action> $flatActions */
                $flatActions = $action->getFlatActions();

                if (! $action->getDropdownPlacement()) {
                    $action->dropdownPlacement('bottom-end');
                }

                $this->mergeCachedFlatActions($flatActions);
            } elseif ($action instanceof Action) {
                $action->defaultSize('sm');
                $action->defaultView($action::LINK_VIEW);

                $this->cacheAction($action);
            } else {
                throw new InvalidArgumentException('Table actions must be an instance of ' . Action::class . ' or ' . ActionGroup::class . '.');
            }

            $this->actions[] = $action;
        }

        $this->actionsPosition($position);

        return $this;
    }

    public function actionsColumnLabel(string | Closure | null $label): static
    {
        $this->actionsColumnLabel = $label;

        return $this;
    }

    public function actionsAlignment(string | Closure $alignment = null): static
    {
        $this->actionsAlignment = $alignment;

        return $this;
    }

    public function actionsPosition(string | Closure $position = null): static
    {
        $this->actionsPosition = $position;

        return $this;
    }

    /**
     * @return array<Action | ActionGroup>
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * @param  string | array<string>  $name
     */
    public function getAction(string | array $name): ?Action
    {
        if (is_string($name) && str($name)->contains('.')) {
            $name = explode('.', $name);
        }

        if (is_array($name)) {
            $firstName = array_shift($name);
            $modalActionNames = $name;

            $name = $firstName;
        }

        $mountedRecord = $this->getLivewire()->getMountedTableActionRecord();

        $action = $this->getFlatActions()[$name] ?? null;

        if (! $action) {
            return null;
        }

        return $this->getMountableModalActionFromAction(
            $action->record($mountedRecord),
            modalActionNames: $modalActionNames ?? [],
            parentActionName: $name,
            mountedRecord: $mountedRecord,
        );
    }

    /**
     * @return array<string, Action>
     */
    public function getFlatActions(): array
    {
        return $this->flatActions;
    }

    protected function cacheAction(Action $action): void
    {
        $this->flatActions[$action->getName()] = $action;
    }

    /**
     * @param  array<string, Action>  $actions
     */
    protected function mergeCachedFlatActions(array $actions): void
    {
        $this->flatActions = [
            ...$this->flatActions,
            ...$actions,
        ];
    }

    /**
     * @param  array<string>  $modalActionNames
     */
    protected function getMountableModalActionFromAction(Action $action, array $modalActionNames, string $parentActionName, Model $mountedRecord = null): ?Action
    {
        foreach ($modalActionNames as $modalActionName) {
            $action = $action->getMountableModalAction($modalActionName);

            if (! $action) {
                return null;
            }

            if ($action instanceof Action) {
                $action->record($mountedRecord);
            }

            $parentActionName = $modalActionName;
        }

        if (! $action instanceof Action) {
            return null;
        }

        return $action;
    }

    public function getActionsPosition(): string
    {
        $position = $this->evaluate($this->actionsPosition);

        if (filled($position)) {
            return $position;
        }

        if (! ($this->getContentGrid() || $this->hasColumnsLayout())) {
            return Position::AfterColumns;
        }

        $actions = $this->getActions();

        $firstAction = Arr::first($actions);

        if ($firstAction instanceof ActionGroup) {
            $firstAction->size('sm md:md');

            return Position::BottomCorner;
        }

        return Position::AfterContent;
    }

    public function getActionsAlignment(): ?string
    {
        return $this->evaluate($this->actionsAlignment);
    }

    public function getActionsColumnLabel(): ?string
    {
        return $this->evaluate($this->actionsColumnLabel);
    }
}
