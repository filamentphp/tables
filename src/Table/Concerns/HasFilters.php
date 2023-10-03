<?php

namespace Filament\Tables\Table\Concerns;

use Closure;
use Filament\Forms\Form;
use Filament\Tables\Actions\Action;
use Filament\Tables\Filters\BaseFilter;
use Filament\Tables\Filters\Layout;

trait HasFilters
{
    /**
     * @var array<string, BaseFilter>
     */
    protected array $filters = [];

    /**
     * @var int | array<string, int | null> | Closure
     */
    protected int | array | Closure $filtersFormColumns = 1;

    protected string | Closure | null $filtersFormMaxHeight = null;

    protected string | Closure | null $filtersFormWidth = null;

    protected string | Closure | null $filtersLayout = null;

    protected ?Closure $modifyFiltersTriggerActionUsing = null;

    protected bool | Closure | null $persistsFiltersInSession = false;

    protected bool | Closure $shouldDeselectAllRecordsWhenFiltered = true;

    public function deselectAllRecordsWhenFiltered(bool | Closure $condition = true): static
    {
        $this->shouldDeselectAllRecordsWhenFiltered = $condition;

        return $this;
    }

    /**
     * @param  array<BaseFilter>  $filters
     */
    public function filters(array $filters, string | Closure $layout = null): static
    {
        foreach ($filters as $filter) {
            $filter->table($this);

            $this->filters[$filter->getName()] = $filter;
        }

        $this->filtersLayout($layout);

        return $this;
    }

    /**
     * @param  int | array<string, int | null> | Closure  $columns
     */
    public function filtersFormColumns(int | array | Closure $columns): static
    {
        $this->filtersFormColumns = $columns;

        return $this;
    }

    public function filtersFormMaxHeight(string | Closure | null $height): static
    {
        $this->filtersFormMaxHeight = $height;

        return $this;
    }

    public function filtersFormWidth(string | Closure | null $width): static
    {
        $this->filtersFormWidth = $width;

        return $this;
    }

    public function filtersLayout(string | Closure | null $filtersLayout): static
    {
        $this->filtersLayout = $filtersLayout;

        return $this;
    }

    public function filtersTriggerAction(?Closure $callback): static
    {
        $this->modifyFiltersTriggerActionUsing = $callback;

        return $this;
    }

    public function persistFiltersInSession(bool | Closure $condition = true): static
    {
        $this->persistsFiltersInSession = $condition;

        return $this;
    }

    /**
     * @return array<string, BaseFilter>
     */
    public function getFilters(): array
    {
        return array_filter(
            $this->filters,
            fn (BaseFilter $filter): bool => $filter->isVisible(),
        );
    }

    public function getFilter(string $name): ?BaseFilter
    {
        return $this->getFilters()[$name] ?? null;
    }

    public function getFiltersForm(): Form
    {
        return $this->getLivewire()->getTableFiltersForm();
    }

    public function getFiltersTriggerAction(): Action
    {
        $action = Action::make('openFilters')
            ->label(__('filament-tables::table.buttons.filter.label'))
            ->iconButton()
            ->icon('heroicon-m-funnel')
            ->color('gray')
            ->livewireClickHandlerEnabled(false)
            ->table($this);

        if ($this->modifyFiltersTriggerActionUsing) {
            $action = $this->evaluate($this->modifyFiltersTriggerActionUsing, [
                'action' => $action,
            ]) ?? $action;
        }

        if ($action->getView() === Action::BUTTON_VIEW) {
            $action->defaultSize('sm');
        }

        return $action;
    }

    /**
     * @return int | array<string, int | null>
     */
    public function getFiltersFormColumns(): int | array
    {
        return $this->evaluate($this->filtersFormColumns) ?? match ($this->getFiltersLayout()) {
            Layout::AboveContent, Layout::BelowContent => [
                'sm' => 2,
                'lg' => 3,
                'xl' => 4,
                '2xl' => 5,
            ],
            default => 1,
        };
    }

    public function getFiltersFormMaxHeight(): ?string
    {
        return $this->evaluate($this->filtersFormMaxHeight);
    }

    public function getFiltersFormWidth(): ?string
    {
        return $this->evaluate($this->filtersFormWidth) ?? match ($this->getFiltersFormColumns()) {
            2 => '2xl',
            3 => '4xl',
            4 => '6xl',
            default => null,
        };
    }

    public function getFiltersLayout(): string
    {
        return $this->evaluate($this->filtersLayout) ?? Layout::Dropdown;
    }

    public function isFilterable(): bool
    {
        return (bool) count($this->getFilters());
    }

    public function persistsFiltersInSession(): bool
    {
        return (bool) $this->evaluate($this->persistsFiltersInSession);
    }

    public function shouldDeselectAllRecordsWhenFiltered(): bool
    {
        return (bool) $this->evaluate($this->shouldDeselectAllRecordsWhenFiltered);
    }
}
