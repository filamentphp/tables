<?php

namespace Filament\Tables\Concerns;

use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\LengthAwarePaginator;
use function Livewire\invade;

trait HasRecords
{
    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    protected bool $allowsDuplicates = false;

    protected Collection | Paginator | null $records = null;

    public function getFilteredTableQuery(): Builder
    {
        $query = $this->getTable()->getQuery();

        $this->applyFiltersToTableQuery($query);

        $this->applySearchToTableQuery($query);

        foreach ($this->getTable()->getColumns() as $column) {
            if ($column->isHidden()) {
                continue;
            }

            $column->applyRelationshipAggregates($query);

            if ($this->getTable()->isGroupsOnly()) {
                continue;
            }

            $column->applyEagerLoading($query);
        }

        return $query;
    }

    public function getFilteredSortedTableQuery(): Builder
    {
        $query = $this->getFilteredTableQuery();

        $this->applyGroupingToTableQuery($query);

        $this->applySortingToTableQuery($query);

        return $query;
    }

    protected function hydratePivotRelationForTableRecords(Collection | Paginator $records): Collection | Paginator
    {
        $table = $this->getTable();
        $relationship = $table->getRelationship();

        if ($table->getRelationship() instanceof BelongsToMany && ! $table->allowsDuplicates()) {
            invade($relationship)->hydratePivotRelation($records->all());
        }

        return $records;
    }

    public function getTableRecords(): Collection | Paginator
    {
        if ($this->records) {
            return $this->records;
        }

        $query = $this->getFilteredSortedTableQuery();

        if (
            (! $this->getTable()->isPaginated()) ||
            ($this->isTableReordering() && (! $this->getTable()->isPaginatedWhileReordering()))
        ) {
            return $this->records = $this->hydratePivotRelationForTableRecords($query->get());
        }

        return $this->records = $this->hydratePivotRelationForTableRecords($this->paginateTableQuery($query));
    }

    protected function resolveTableRecord(?string $key): ?Model
    {
        if ($key === null) {
            return null;
        }

        if (! ($this->getTable()->getRelationship() instanceof BelongsToMany)) {
            return $this->getTable()->getQuery()->find($key);
        }

        /** @var BelongsToMany $relationship */
        $relationship = $this->getTable()->getRelationship();

        $pivotClass = $relationship->getPivotClass();
        $pivotKeyName = app($pivotClass)->getKeyName();

        $table = $this->getTable();

        $query = $table->allowsDuplicates() ?
            $relationship->wherePivot($pivotKeyName, $key) :
            $relationship->where($relationship->getQualifiedRelatedKeyName(), $key);

        $record = $table->selectPivotDataInQuery($query)->first();

        return $record?->setRawAttributes($record->getRawOriginal());
    }

    public function getTableRecord(?string $key): ?Model
    {
        return $this->resolveTableRecord($key);
    }

    public function getTableRecordKey(Model $record): string
    {
        $table = $this->getTable();

        if (! ($table->getRelationship() instanceof BelongsToMany && $table->allowsDuplicates())) {
            return $record->getKey();
        }

        /** @var BelongsToMany $relationship */
        $relationship = $table->getRelationship();

        $pivotClass = $relationship->getPivotClass();
        $pivotKeyName = app($pivotClass)->getKeyName();

        return $record->getAttributeValue($pivotKeyName);
    }

    public function getAllTableRecordsCount(): int
    {
        if ($this->records instanceof LengthAwarePaginator) {
            return $this->records->total();
        }

        return $this->getFilteredTableQuery()->count();
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function allowsDuplicates(): bool
    {
        return $this->allowsDuplicates;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function getTableRecordTitle(Model $record): ?string
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function getTableModelLabel(): ?string
    {
        return null;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function getTablePluralModelLabel(): ?string
    {
        return null;
    }
}
