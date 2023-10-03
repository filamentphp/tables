<?php

namespace Filament\Tables\Actions;

use Closure;
use Filament\Actions\Concerns\CanCustomizeProcess;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;

class AssociateAction extends Action
{
    use CanCustomizeProcess;

    protected ?Closure $modifyRecordSelectUsing = null;

    protected ?Closure $modifyRecordSelectOptionsQueryUsing = null;

    protected bool | Closure $canAssociateAnother = true;

    protected bool | Closure $isRecordSelectPreloaded = false;

    /**
     * @var array<string> | Closure | null
     */
    protected array | Closure | null $recordSelectSearchColumns = null;

    public static function getDefaultName(): ?string
    {
        return 'associate';
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('filament-actions::associate.single.label'));

        $this->modalHeading(fn (): string => __('filament-actions::associate.single.modal.heading', ['label' => $this->getModelLabel()]));

        $this->modalSubmitActionLabel(__('filament-actions::associate.single.modal.actions.associate.label'));

        $this->modalWidth('lg');

        $this->extraModalFooterActions(function (): array {
            return $this->canAssociateAnother ? [
                $this->makeModalSubmitAction('associateAnother', arguments: ['another' => true])
                    ->label(__('filament-actions::associate.single.modal.actions.associate_another.label')),
            ] : [];
        });

        $this->successNotificationTitle(__('filament-actions::associate.single.messages.associated'));

        $this->color('gray');

        $this->form(fn (): array => [$this->getRecordSelect()]);

        $this->action(function (array $arguments, array $data, Form $form, Table $table): void {
            /** @var HasMany | MorphMany $relationship */
            $relationship = Relation::noConstraints(fn () => $table->getRelationship());

            $record = $relationship->getQuery()->find($data['recordId']);

            /** @var BelongsTo $inverseRelationship */
            $inverseRelationship = $table->getInverseRelationshipFor($record);

            $this->process(function () use ($inverseRelationship, $record, $relationship) {
                $inverseRelationship->associate($relationship->getParent());
                $record->save();
            });

            if ($record instanceof Model) {
                $this->record($record);
            }

            if ($arguments['another'] ?? false) {
                $this->callAfter();
                $this->sendSuccessNotification();

                $this->record(null);

                $form->fill();

                $this->halt();

                return;
            }

            $this->success();
        });
    }

    public function recordSelect(?Closure $callback): static
    {
        $this->modifyRecordSelectUsing = $callback;

        return $this;
    }

    public function recordSelectOptionsQuery(?Closure $callback): static
    {
        $this->modifyRecordSelectOptionsQueryUsing = $callback;

        return $this;
    }

    public function associateAnother(bool | Closure $condition = true): static
    {
        $this->canAssociateAnother = $condition;

        return $this;
    }

    /**
     * @deprecated Use `associateAnother()` instead.
     */
    public function disableAssociateAnother(bool | Closure $condition = true): static
    {
        $this->associateAnother(fn (AssociateAction $action): bool => ! $action->evaluate($condition));

        return $this;
    }

    public function preloadRecordSelect(bool | Closure $condition = true): static
    {
        $this->isRecordSelectPreloaded = $condition;

        return $this;
    }

    public function canAssociateAnother(): bool
    {
        return (bool) $this->evaluate($this->canAssociateAnother);
    }

    public function isRecordSelectPreloaded(): bool
    {
        return (bool) $this->evaluate($this->isRecordSelectPreloaded);
    }

    /**
     * @param  array<string> | Closure | null  $columns
     */
    public function recordSelectSearchColumns(array | Closure | null $columns): static
    {
        $this->recordSelectSearchColumns = $columns;

        return $this;
    }

    /**
     * @return array<string> | null
     */
    public function getRecordSelectSearchColumns(): ?array
    {
        return $this->evaluate($this->recordSelectSearchColumns);
    }

    public function getRecordSelect(): Select
    {
        $table = $this->getTable();

        $getOptions = function (string $search = null, ?array $searchColumns = []) use ($table): array {
            /** @var HasMany | MorphMany $relationship */
            $relationship = Relation::noConstraints(fn () => $table->getRelationship());

            $relationshipQuery = $relationship->getQuery();

            $titleAttribute = $relationshipQuery->qualifyColumn($this->getRecordTitleAttribute());

            $relationshipQuery->orderBy($titleAttribute);

            if ($this->modifyRecordSelectOptionsQueryUsing) {
                $relationshipQuery = $this->evaluate($this->modifyRecordSelectOptionsQueryUsing, [
                    'query' => $relationshipQuery,
                ]) ?? $relationshipQuery;
            }

            if (filled($search)) {
                $search = strtolower($search);

                /** @var Connection $databaseConnection */
                $databaseConnection = $relationshipQuery->getConnection();

                $searchOperator = match ($databaseConnection->getDriverName()) {
                    'pgsql' => 'ilike',
                    default => 'like',
                };

                $searchColumns ??= [$titleAttribute];
                $isFirst = true;

                $relationshipQuery->where(function (Builder $query) use ($isFirst, $searchColumns, $searchOperator, $search): Builder {
                    foreach ($searchColumns as $searchColumn) {
                        $whereClause = $isFirst ? 'where' : 'orWhere';

                        $query->{"{$whereClause}Raw"}(
                            "lower({$searchColumn}) {$searchOperator} ?",
                            "%{$search}%",
                        );

                        $isFirst = false;
                    }

                    return $query;
                });
            }

            return $relationshipQuery
                ->whereDoesntHave($table->getInverseRelationship(), function (Builder $query) use ($relationship): Builder {
                    if ($relationship instanceof MorphMany) {
                        return $query
                            ->where(
                                $relationship->getMorphType(),
                                $relationship->getMorphClass(),
                            )
                            ->where(
                                $relationship->getQualifiedForeignKeyName(),
                                $relationship->getParent()->getKey()
                            );
                    }

                    return $query->where(
                        $relationship->getParent()->getQualifiedKeyName(),
                        $relationship->getParent()->getKey(),
                    );
                })
                ->get()
                ->mapWithKeys(fn (Model $record): array => [$record->getKey() => $this->getRecordTitle($record)])
                ->toArray();
        };

        $select = Select::make('recordId')
            ->label(__('filament-actions::associate.single.modal.fields.record_id.label'))
            ->required()
            ->searchable($this->getRecordSelectSearchColumns() ?? true)
            ->getSearchResultsUsing(static fn (Select $component, string $search): array => $getOptions(search: $search, searchColumns: $component->getSearchColumns()))
            ->getOptionLabelUsing(fn ($value): string => $this->getRecordTitle(Relation::noConstraints(fn () => $table->getRelationship())->getQuery()->find($value)))
            ->options(fn (): array => $this->isRecordSelectPreloaded() ? $getOptions() : [])
            ->hiddenLabel();

        if ($this->modifyRecordSelectUsing) {
            $select = $this->evaluate($this->modifyRecordSelectUsing, [
                'select' => $select,
            ]);
        }

        return $select;
    }
}
