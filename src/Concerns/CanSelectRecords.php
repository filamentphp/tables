<?php

namespace Filament\Tables\Concerns;

use Filament\Tables\Actions\BulkAction;
use Filament\Tables\Actions\RecordCheckboxPosition;
use Filament\Tables\Contracts\HasRelationshipTable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\LengthAwarePaginator;

trait CanSelectRecords
{
    public array $selectedTableRecords = [];

    protected bool $shouldSelectCurrentPageOnly = false;

    protected Collection $cachedSelectedTableRecords;

    public function deselectAllTableRecords(): void
    {
        $this->emitSelf('deselectAllTableRecords');
    }

    public function shouldDeselectAllRecordsWhenTableFiltered(): bool
    {
        return true;
    }

    public function getAllTableRecordKeys(): array
    {
        $query = $this->getFilteredTableQuery();

        $records = $this->shouldSelectCurrentPageOnly() ?
            $this->getTableRecords() :
            $query;

        return $records
            ->pluck($query->getModel()->getQualifiedKeyName())
            ->map(fn ($key): string => (string) $key)
            ->all();
    }

    public function getAllTableRecordsCount(): int
    {
        if ($this->shouldSelectCurrentPageOnly()) {
            return $this->records->count();
        }

        if ($this->records instanceof LengthAwarePaginator) {
            return $this->records->total();
        }

        $query = $this->getFilteredTableQuery();

        if ($this->isTableRecordSelectable() !== null) {
            return $query
                ->get()
                ->filter(fn (Model $record): bool => $this->isTableRecordSelectable()($record))
                ->count();
        }

        return $query->count();
    }

    public function getSelectedTableRecords(): Collection
    {
        if (isset($this->cachedSelectedTableRecords)) {
            return $this->cachedSelectedTableRecords;
        }

        if (! ($this instanceof HasRelationshipTable && $this->getRelationship() instanceof BelongsToMany && $this->allowsDuplicates())) {
            $query = $this->getTableQuery()->whereIn(app($this->getTableModel())->getQualifiedKeyName(), $this->selectedTableRecords);
            $this->applySortingToTableQuery($query);

            return $this->cachedSelectedTableRecords = $query->get();
        }

        /** @var BelongsToMany $relationship */
        $relationship = $this->getRelationship();

        $pivotClass = $relationship->getPivotClass();
        $pivotKeyName = app($pivotClass)->getKeyName();

        return $this->cachedSelectedTableRecords = $this->hydratePivotRelationForTableRecords($this->selectPivotDataInQuery(
            $relationship->wherePivotIn($pivotKeyName, $this->selectedTableRecords),
        )->get());
    }

    public function isTableSelectionEnabled(): bool
    {
        return (bool) count(array_filter(
            $this->getCachedTableBulkActions(),
            fn (BulkAction $action): bool => ! $action->isHidden(),
        ));
    }

    public function getTableRecordCheckboxPosition(): string
    {
        return RecordCheckboxPosition::BeforeCells;
    }

    public function shouldSelectCurrentPageOnly(): bool
    {
        return $this->shouldSelectCurrentPageOnly;
    }
}
