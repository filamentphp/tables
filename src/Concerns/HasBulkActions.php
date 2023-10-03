<?php

namespace Filament\Tables\Concerns;

use Closure;
use Filament\Forms\Form;
use Filament\Support\Exceptions\Cancel;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * @property Form $mountedTableBulkActionForm
 */
trait HasBulkActions
{
    /**
     * @var array<int | string>
     */
    public array $selectedTableRecords = [];

    public ?string $mountedTableBulkAction = null;

    /**
     * @var array<string, mixed> | null
     */
    public ?array $mountedTableBulkActionData = [];

    protected EloquentCollection $cachedSelectedTableRecords;

    protected function configureTableBulkAction(BulkAction $action): void
    {
    }

    /**
     * @param  array<string, mixed>  $arguments
     */
    public function callMountedTableBulkAction(array $arguments = []): mixed
    {
        $action = $this->getMountedTableBulkAction();

        if (! $action) {
            return null;
        }

        if ($action->isDisabled()) {
            return null;
        }

        $action->arguments($arguments);

        $form = $this->getMountedTableBulkActionForm();

        $result = null;

        try {
            if ($this->mountedTableBulkActionHasForm()) {
                $action->callBeforeFormValidated();

                $action->formData($form->getState());

                $action->callAfterFormValidated();
            }

            $action->callBefore();

            $result = $action->call([
                'form' => $form,
            ]);

            $result = $action->callAfter() ?? $result;
        } catch (Halt $exception) {
            return null;
        } catch (Cancel $exception) {
        }

        if (filled($this->redirectTo)) {
            return $result;
        }

        $this->mountedTableBulkAction = null;
        $this->selectedTableRecords = [];

        $action->resetArguments();
        $action->resetFormData();

        $this->closeTableBulkActionModal();

        return $result;
    }

    /**
     * @param  array<int | string>  $selectedRecords
     */
    public function mountTableBulkAction(string $name, array $selectedRecords): mixed
    {
        $this->mountedTableBulkAction = $name;
        $this->selectedTableRecords = $selectedRecords;

        $action = $this->getMountedTableBulkAction();

        if (! $action) {
            return null;
        }

        if ($action->isDisabled()) {
            return null;
        }

        $this->cacheMountedTableBulkActionForm();

        try {
            $hasForm = $this->mountedTableBulkActionHasForm();

            if ($hasForm) {
                $action->callBeforeFormFilled();
            }

            $action->mount([
                'form' => $this->getMountedTableBulkActionForm(),
            ]);

            if ($hasForm) {
                $action->callAfterFormFilled();
            }
        } catch (Halt $exception) {
            return null;
        } catch (Cancel $exception) {
            $this->resetMountedTableBulkActionProperties();

            return null;
        }

        if (! $this->mountedTableBulkActionShouldOpenModal()) {
            return $this->callMountedTableBulkAction();
        }

        $this->resetErrorBag();

        $this->openTableBulkActionModal();

        return null;
    }

    protected function cacheMountedTableBulkActionForm(): void
    {
        $this->cacheForm(
            'mountedTableBulkActionForm',
            fn () => $this->getMountedTableBulkActionForm(),
        );
    }

    protected function resetMountedTableBulkActionProperties(): void
    {
        $this->mountedTableBulkAction = null;
        $this->selectedTableRecords = [];
    }

    public function mountedTableBulkActionShouldOpenModal(): bool
    {
        $action = $this->getMountedTableBulkAction();

        if ($action->isModalHidden()) {
            return false;
        }

        return $action->getModalDescription() ||
            $action->getModalContent() ||
            $action->getModalContentFooter() ||
            $action->getInfolist() ||
            $this->mountedTableBulkActionHasForm();
    }

    public function mountedTableBulkActionHasForm(): bool
    {
        return (bool) count($this->getMountedTableBulkActionForm()?->getComponents() ?? []);
    }

    public function deselectAllTableRecords(): void
    {
        $this->emitSelf('deselectAllTableRecords');
    }

    public function getAllSelectableTableRecordKeys(): array
    {
        $query = $this->getFilteredTableQuery();

        if (! $this->getTable()->checksIfRecordIsSelectable()) {
            $records = $this->getTable()->selectsCurrentPageOnly() ?
                $this->getTableRecords() :
                $query;

            return $records
                ->pluck($query->getModel()->getQualifiedKeyName())
                ->map(fn ($key): string => (string) $key)
                ->all();
        }

        $records = $this->getTable()->selectsCurrentPageOnly() ?
            $this->getTableRecords() :
            $query->get();

        return $records->reduce(
            function (array $carry, Model $record): array {
                if (! $this->getTable()->isRecordSelectable($record)) {
                    return $carry;
                }

                $carry[] = (string) $record->getKey();

                return $carry;
            },
            initial: [],
        );
    }

    public function getAllSelectableTableRecordsCount(): int
    {
        if ($this->getTable()->checksIfRecordIsSelectable()) {
            /** @var Collection $records */
            $records = $this->getTable()->selectsCurrentPageOnly() ?
                $this->getTableRecords() :
                $this->getFilteredTableQuery()->get();

            return $records
                ->filter(fn (Model $record): bool => $this->getTable()->isRecordSelectable($record))
                ->count();
        }

        if ($this->getTable()->selectsCurrentPageOnly()) {
            return $this->records->count();
        }

        if ($this->records instanceof LengthAwarePaginator) {
            return $this->records->total();
        }

        return $this->getFilteredTableQuery()->count();
    }

    public function getSelectedTableRecords(): EloquentCollection
    {
        if (isset($this->cachedSelectedTableRecords)) {
            return $this->cachedSelectedTableRecords;
        }

        $table = $this->getTable();

        if (! ($table->getRelationship() instanceof BelongsToMany && $table->allowsDuplicates())) {
            $query = $table->getQuery()->whereKey($this->selectedTableRecords);
            $this->applySortingToTableQuery($query);

            foreach ($this->getTable()->getColumns() as $column) {
                $column->applyEagerLoading($query);
                $column->applyRelationshipAggregates($query);
            }

            return $this->cachedSelectedTableRecords = $query->get();
        }

        /** @var BelongsToMany $relationship */
        $relationship = $table->getRelationship();

        $pivotClass = $relationship->getPivotClass();
        $pivotKeyName = app($pivotClass)->getKeyName();

        $relationship->wherePivotIn($pivotKeyName, $this->selectedTableRecords);

        foreach ($this->getTable()->getColumns() as $column) {
            $column->applyEagerLoading($relationship);
            $column->applyRelationshipAggregates($relationship);
        }

        return $this->cachedSelectedTableRecords = $this->hydratePivotRelationForTableRecords(
            $table->selectPivotDataInQuery($relationship)->get(),
        );
    }

    protected function closeTableBulkActionModal(): void
    {
        $this->dispatchBrowserEvent('close-modal', [
            'id' => "{$this->id}-table-bulk-action",
        ]);
    }

    protected function openTableBulkActionModal(): void
    {
        $this->dispatchBrowserEvent('open-modal', [
            'id' => "{$this->id}-table-bulk-action",
        ]);
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function shouldSelectCurrentPageOnly(): bool
    {
        return false;
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function shouldDeselectAllRecordsWhenTableFiltered(): bool
    {
        return true;
    }

    public function getMountedTableBulkAction(): ?BulkAction
    {
        if (! $this->mountedTableBulkAction) {
            return null;
        }

        return $this->getTable()->getBulkAction($this->mountedTableBulkAction);
    }

    public function getMountedTableBulkActionForm(): ?Form
    {
        $action = $this->getMountedTableBulkAction();

        if (! $action) {
            return null;
        }

        if ((! $this->isCachingForms) && $this->hasCachedForm('mountedTableBulkActionForm')) {
            return $this->getForm('mountedTableBulkActionForm');
        }

        return $action->getForm(
            $this->makeForm()
                ->model($this->getTable()->getModel())
                ->statePath('mountedTableBulkActionData')
                ->operation($this->mountedTableBulkAction),
        );
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     *
     * @return array<BulkAction>
     */
    protected function getTableBulkActions(): array
    {
        return [];
    }

    /**
     * @deprecated Override the `table()` method to configure the table.
     */
    public function isTableRecordSelectable(): ?Closure
    {
        return null;
    }
}
