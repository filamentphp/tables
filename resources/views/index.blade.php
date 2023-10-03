@php
    use Filament\Tables\Actions\Position as ActionsPosition;
    use Filament\Tables\Actions\RecordCheckboxPosition;
    use Filament\Tables\Filters\Layout as FiltersLayout;

    $actions = $getActions();
    $actionsAlignment = $getActionsAlignment();
    $actionsPosition = $getActionsPosition();
    $actionsColumnLabel = $getActionsColumnLabel();
    $columns = $getVisibleColumns();
    $collapsibleColumnsLayout = $getCollapsibleColumnsLayout();
    $content = $getContent();
    $contentGrid = $getContentGrid();
    $contentFooter = $getContentFooter();
    $filterIndicators = [
        ...($hasSearch() ? ['resetTableSearch' => $getSearchIndicator()] : []),
        ...collect($getColumnSearchIndicators())
            ->mapWithKeys(fn (string $indicator, string $column): array => [
                "resetTableColumnSearch('{$column}')" => $indicator,
            ])
            ->all(),
        ...array_reduce(
            $getFilters(),
            fn (array $carry, \Filament\Tables\Filters\BaseFilter $filter): array => [
                ...$carry,
                ...collect($filter->getIndicators())
                    ->mapWithKeys(fn (string $label, int | string $field) => [
                        "removeTableFilter('{$filter->getName()}'" . (is_string($field) ? ' , \'' . $field . '\'' : null) . ')' => $label,
                    ])
                    ->all(),
            ],
            [],
        ),
    ];
    $hasColumnsLayout = $hasColumnsLayout();
    $hasSummary = $hasSummary();
    $header = $getHeader();
    $headerActions = array_filter(
        $getHeaderActions(),
        fn (\Filament\Tables\Actions\Action | \Filament\Tables\Actions\BulkAction | \Filament\Tables\Actions\ActionGroup $action): bool => $action->isVisible(),
    );
    $headerActionsPosition = $getHeaderActionsPosition();
    $heading = $getHeading();
    $group = $getGrouping();
    $bulkActions = array_filter(
        $getBulkActions(),
        fn (\Filament\Tables\Actions\BulkAction | \Filament\Tables\Actions\ActionGroup $action): bool => $action->isVisible(),
    );
    $groups = $getGroups();
    $description = $getDescription();
    $isGroupsOnly = $isGroupsOnly() && $group;
    $isReorderable = $isReorderable();
    $isReordering = $isReordering();
    $isColumnSearchVisible = $isSearchableByColumn();
    $isGlobalSearchVisible = $isSearchable();
    $isSelectionEnabled = $isSelectionEnabled();
    $recordCheckboxPosition = $getRecordCheckboxPosition();
    $isStriped = $isStriped();
    $isLoaded = $isLoaded();
    $hasFilters = $isFilterable();
    $filtersLayout = $getFiltersLayout();
    $hasFiltersDropdown = $hasFilters && ($filtersLayout === FiltersLayout::Dropdown);
    $hasFiltersAboveContent = $hasFilters && in_array($filtersLayout, [FiltersLayout::AboveContent, FiltersLayout::AboveContentCollapsible]);
    $hasFiltersAboveContentCollapsible = $hasFilters && ($filtersLayout === FiltersLayout::AboveContentCollapsible);
    $hasFiltersBelowContent = $hasFilters && ($filtersLayout === FiltersLayout::BelowContent);
    $isColumnToggleFormVisible = $hasToggleableColumns();
    $pluralModelLabel = $getPluralModelLabel();
    $records = $isLoaded ? $getRecords() : null;
    $allSelectableRecordsCount = $isLoaded ? $getAllSelectableRecordsCount() : null;
    $columnsCount = count($columns);

    if (count($actions) && (! $isReordering)) {
        $columnsCount++;
    }

    if ($isSelectionEnabled || $isReordering) {
        $columnsCount++;
    }

    if ($group) {
        $groupedSummarySelectedState = $this->getTableSummarySelectedState($this->getAllTableSummaryQuery(), modifyQueryUsing: fn (\Illuminate\Database\Query\Builder $query) => $group->groupQuery($query, model: $getQuery()->getModel()));
    }

    $getHiddenClasses = function (Filament\Tables\Columns\Column $column): ?string {
        if ($breakpoint = $column->getHiddenFrom()) {
            return match ($breakpoint) {
                'sm' => 'sm:hidden',
                'md' => 'md:hidden',
                'lg' => 'lg:hidden',
                'xl' => 'xl:hidden',
                '2xl' => '2xl:hidden',
            };
        }

        if ($breakpoint = $column->getVisibleFrom()) {
            return match ($breakpoint) {
                'sm' => 'hidden sm:table-cell',
                'md' => 'hidden md:table-cell',
                'lg' => 'hidden lg:table-cell',
                'xl' => 'hidden xl:table-cell',
                '2xl' => 'hidden 2xl:table-cell',
            };
        }

        return null;
    };
@endphp

<div
    x-data="{
        collapsedGroups: [],

        hasHeader: true,

        isLoading: false,

        selectedRecords: [],

        shouldCheckUniqueSelection: true,

        init: function () {
            $wire.on('deselectAllTableRecords', () => this.deselectAllRecords())

            $watch('selectedRecords', () => {
                if (! this.shouldCheckUniqueSelection) {
                    this.shouldCheckUniqueSelection = true

                    return
                }

                this.selectedRecords = [...new Set(this.selectedRecords)]

                this.shouldCheckUniqueSelection = false
            })
        },

        mountBulkAction: function (name) {
            $wire.mountTableBulkAction(name, this.selectedRecords)
        },

        toggleSelectRecordsOnPage: function () {
            let keys = this.getRecordsOnPage()

            if (this.areRecordsSelected(keys)) {
                this.deselectRecords(keys)

                return
            }

            this.selectRecords(keys)
        },

        getRecordsOnPage: function () {
            let keys = []

            for (checkbox of $el.getElementsByClassName(
                'filament-tables-record-checkbox',
            )) {
                keys.push(checkbox.value)
            }

            return keys
        },

        selectRecords: function (keys) {
            for (key of keys) {
                if (this.isRecordSelected(key)) {
                    continue
                }

                this.selectedRecords.push(key)
            }
        },

        deselectRecords: function (keys) {
            for (key of keys) {
                let index = this.selectedRecords.indexOf(key)

                if (index === -1) {
                    continue
                }

                this.selectedRecords.splice(index, 1)
            }
        },

        selectAllRecords: async function () {
            this.isLoading = true

            this.selectedRecords = await $wire.getAllSelectableTableRecordKeys()

            this.isLoading = false
        },

        deselectAllRecords: function () {
            this.selectedRecords = []
        },

        isRecordSelected: function (key) {
            return this.selectedRecords.includes(key)
        },

        areRecordsSelected: function (keys) {
            return keys.every((key) => this.isRecordSelected(key))
        },

        toggleCollapseGroup: function (group) {
            if (this.isGroupCollapsed(group)) {
                this.collapsedGroups.splice(this.collapsedGroups.indexOf(group), 1)

                return
            }

            this.collapsedGroups.push(group)
        },

        isGroupCollapsed: function (group) {
            return this.collapsedGroups.includes(group)
        },

        resetCollapsedGroups: function () {
            this.collapsedGroups = []
        },
    }"
    class="filament-tables-component"
    @if (! $isLoaded)
        wire:init="loadTable"
    @endif
>
    <x-filament-tables::container>
        <div
            class="filament-tables-header-container"
            x-show="hasHeader = @js($renderHeader = ($header || $heading || $description || ($headerActions && (! $isReordering)) || $isReorderable || count($groups) || $isGlobalSearchVisible || $hasFilters || $isColumnToggleFormVisible)) || (selectedRecords.length && @js(count($bulkActions)))"
            @if (! $renderHeader) x-cloak @endif
        >
            @if ($header)
                {{ $header }}
            @elseif ($heading || $description || $headerActions)
                <div
                    @class([
                        'filament-tables-header-hr-wrapper px-2 pt-2',
                        'hidden' => ! ($heading || $description) && $isReordering,
                    ])
                >
                    <x-filament-tables::header
                        :actions="$isReordering ? [] : $headerActions"
                        :actions-position="$headerActionsPosition"
                        class="mb-2"
                        :heading="$heading"
                        :description="$description"
                    />

                    <x-filament::hr
                        :x-show="\Illuminate\Support\Js::from($isReorderable || count($groups) || $isGlobalSearchVisible || $hasFilters || $isColumnToggleFormVisible) . ' || (selectedRecords.length && ' . \Illuminate\Support\Js::from(count($bulkActions)) . ')'"
                    />
                </div>
            @endif

            @if ($hasFiltersAboveContent)
                <div
                    class="px-2"
                    x-data="{ areFiltersOpen: @js(! $hasFiltersAboveContentCollapsible) }"
                >
                    <div class="py-2">
                        @if ($hasFiltersAboveContentCollapsible)
                            <div
                                x-on:click="areFiltersOpen = ! areFiltersOpen"
                                class="flex w-full justify-end"
                            >
                                {{ $getFiltersTriggerAction()->indicator(count(\Illuminate\Support\Arr::flatten($filterIndicators))) }}
                            </div>
                        @endif

                        <div class="p-4" x-show="areFiltersOpen">
                            <x-filament-tables::filters
                                :form="$getFiltersForm()"
                            />
                        </div>
                    </div>

                    <x-filament::hr
                        :x-show="\Illuminate\Support\Js::from($isReorderable || count($groups) || $isGlobalSearchVisible || $isColumnToggleFormVisible) . ' || (selectedRecords.length && ' . \Illuminate\Support\Js::from(count($bulkActions)) . ')'"
                    />
                </div>
            @endif

            <div
                x-show="@js($shouldRenderHeaderDiv = ($isReorderable || count($groups) || $isGlobalSearchVisible || $hasFiltersDropdown || $isColumnToggleFormVisible)) || (selectedRecords.length && @js(count($bulkActions)))"
                @if (! $shouldRenderHeaderDiv) x-cloak @endif
                class="filament-tables-header-toolbar flex h-14 items-center justify-between px-3 py-2"
                x-bind:class="{
                    'gap-3': @js($isReorderable) || @js(count($groups)) || (selectedRecords.length && @js(count($bulkActions))),
                }"
            >
                <div class="flex shrink-0 items-center sm:gap-3">
                    @if ($isReorderable)
                        {{ $getReorderRecordsTriggerAction($isReordering) }}
                    @endif

                    @if (count($groups))
                        <x-filament-tables::groups
                            :dropdown-on-desktop="$areGroupsInDropdownOnDesktop()"
                            :groups="$groups"
                            :trigger-action="$getGroupRecordsTriggerAction()"
                        />
                    @endif

                    @if ((! $isReordering) && count($bulkActions))
                        <x-filament-tables::actions
                            :actions="$bulkActions"
                            x-show="selectedRecords.length"
                            x-cloak="x-cloak"
                        />
                    @endif
                </div>

                @if ($isGlobalSearchVisible || $hasFiltersDropdown || $isColumnToggleFormVisible)
                    <div
                        class="flex flex-1 items-center justify-end gap-3 md:max-w-md"
                    >
                        @if ($isGlobalSearchVisible)
                            <div
                                class="filament-tables-search-container flex flex-1 items-center justify-end"
                            >
                                <x-filament-tables::search-input />
                            </div>
                        @endif

                        @if ($hasFiltersDropdown || $isColumnToggleFormVisible)
                            <div class="flex items-center gap-x-1">
                                @if ($hasFiltersDropdown)
                                    <x-filament-tables::filters.dropdown
                                        :form="$getFiltersForm()"
                                        :indicators-count="count(\Illuminate\Support\Arr::flatten($filterIndicators))"
                                        :max-height="$getFiltersFormMaxHeight()"
                                        :trigger-action="$getFiltersTriggerAction()"
                                        :width="$getFiltersFormWidth()"
                                        class="shrink-0"
                                    />
                                @endif

                                @if ($isColumnToggleFormVisible)
                                    <x-filament-tables::toggleable.dropdown
                                        :form="$getColumnToggleForm()"
                                        :max-height="$getColumnToggleFormMaxHeight()"
                                        :trigger-action="$getToggleColumnsTriggerAction()"
                                        :width="$getColumnToggleFormWidth()"
                                        class="shrink-0"
                                    />
                                @endif
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </div>

        @if ($isReordering)
            <x-filament-tables::reorder.indicator
                :colspan="$columnsCount"
                class="border-t dark:border-gray-700"
            />
        @elseif ($isSelectionEnabled && $isLoaded)
            <x-filament-tables::selection-indicator
                :all-selectable-records-count="$allSelectableRecordsCount"
                :colspan="$columnsCount"
                x-show="selectedRecords.length"
                class="border-t dark:border-gray-700"
            >
                <x-slot name="selectedRecordsCount">
                    <span x-text="selectedRecords.length"></span>
                </x-slot>
            </x-filament-tables::selection-indicator>
        @endif

        <div>
            <x-filament-tables::filters.indicators
                :indicators="$filterIndicators"
                class="border-t dark:border-gray-700"
            />
        </div>

        <div
            @if ($pollingInterval = $getPollingInterval())
                wire:poll.{{ $pollingInterval }}
            @endif
            @class([
                'filament-tables-table-container overflow-x-auto dark:border-gray-700',
                'overflow-x-auto' => $content || $hasColumnsLayout,
                'rounded-t-xl' => ! $renderHeader,
                'border-t' => $renderHeader,
            ])
            x-bind:class="{
                'rounded-t-xl': ! hasHeader,
                'border-t': hasHeader,
            }"
        >
            @if (($content || $hasColumnsLayout) && ($records !== null) && count($records))
                @if (! $isReordering)
                    @php
                        $sortableColumns = array_filter(
                            $columns,
                            fn (\Filament\Tables\Columns\Column $column): bool => $column->isSortable(),
                        );
                    @endphp

                    <div
                        @class([
                            'flex items-center gap-4 border-b bg-gray-500/5 px-4 dark:border-gray-700',
                            'hidden' => (! $isSelectionEnabled) && (! count($sortableColumns)),
                        ])
                    >
                        @if ($isSelectionEnabled)
                            <x-filament-tables::checkbox
                                :label="__('filament-tables::table.fields.bulk_select_page.label')"
                                x-on:click="toggleSelectRecordsOnPage"
                                x-bind:checked="
                                    let recordsOnPage = getRecordsOnPage()

                                    if (recordsOnPage.length && areRecordsSelected(recordsOnPage)) {
                                        $el.checked = true

                                        return 'checked'
                                    }

                                    $el.checked = false

                                    return null
                                "
                                @class(['hidden' => $isReordering])
                            />
                        @endif

                        @if (count($sortableColumns))
                            <div
                                x-data="{
                                    column: $wire.entangle('tableSortColumn'),
                                    direction: $wire.entangle('tableSortDirection'),
                                }"
                                x-init="
                                    $watch('column', function (newColumn, oldColumn) {
                                        if (! newColumn) {
                                            direction = null

                                            return
                                        }

                                        if (oldColumn) {
                                            return
                                        }

                                        direction = 'asc'
                                    })
                                "
                                class="flex flex-wrap items-center gap-1 py-1 text-xs sm:text-sm"
                            >
                                <label>
                                    <span class="me-1 font-medium">
                                        {{ __('filament-tables::table.sorting.fields.column.label') }}
                                    </span>

                                    <select
                                        x-model="column"
                                        style="
                                            background-position: right 0.2rem
                                                center;
                                        "
                                        class="rounded-lg border-0 border-gray-300 bg-gray-500/5 py-1 pe-6 ps-2 text-xs font-medium focus:border-primary-500 focus:ring-0 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 sm:text-sm"
                                    >
                                        <option value="">-</option>

                                        @foreach ($sortableColumns as $column)
                                            <option
                                                value="{{ $column->getName() }}"
                                            >
                                                {{ $column->getLabel() }}
                                            </option>
                                        @endforeach
                                    </select>
                                </label>

                                <label x-show="column" x-cloak>
                                    <span class="sr-only">
                                        {{ __('filament-tables::table.sorting.fields.direction.label') }}
                                    </span>

                                    <select
                                        x-model="direction"
                                        style="
                                            background-position: right 0.2rem
                                                center;
                                        "
                                        class="rounded-lg border-0 border-gray-300 bg-gray-500/5 py-1 pe-6 ps-2 text-xs font-medium focus:border-primary-500 focus:ring-0 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 sm:text-sm"
                                    >
                                        <option value="asc">
                                            {{ __('filament-tables::table.sorting.fields.direction.options.asc') }}
                                        </option>

                                        <option value="desc">
                                            {{ __('filament-tables::table.sorting.fields.direction.options.desc') }}
                                        </option>
                                    </select>
                                </label>
                            </div>
                        @endif
                    </div>
                @endif

                @if ($content)
                    {{ $content->with(['records' => $records]) }}
                @else
                    <x-filament::grid
                        x-sortable
                        x-on:end.stop="$wire.reorderTable($event.target.sortable.toArray())"
                        :default="$contentGrid['default'] ?? 1"
                        :sm="$contentGrid['sm'] ?? null"
                        :md="$contentGrid['md'] ?? null"
                        :lg="$contentGrid['lg'] ?? null"
                        :xl="$contentGrid['xl'] ?? null"
                        :two-xl="$contentGrid['2xl'] ?? null"
                        @class([
                            'dark:divide-gray-700',
                            'divide-y' => ! $contentGrid,
                            'p-2 gap-2' => $contentGrid,
                        ])
                    >
                        @php
                            $previousRecord = null;
                            $previousRecordGroupKey = null;
                            $previousRecordGroupTitle = null;
                        @endphp

                        @foreach ($records as $record)
                            @php
                                $recordAction = $getRecordAction($record);
                                $recordKey = $getRecordKey($record);
                                $recordUrl = $getRecordUrl($record);
                                $recordGroupKey = $group?->getKey($record);
                                $recordGroupTitle = $group?->getTitle($record);

                                $collapsibleColumnsLayout?->record($record);
                                $hasCollapsibleColumnsLayout = (bool) $collapsibleColumnsLayout?->isVisible();
                            @endphp

                            @if ($recordGroupTitle !== $previousRecordGroupTitle)
                                @if ($hasSummary && (! $isReordering) && filled($previousRecordGroupTitle))
                                    <x-filament-tables::table
                                        class="col-span-full"
                                    >
                                        <x-filament-tables::summary.row
                                            :columns="$columns"
                                            :heading="__('filament-tables::table.summary.subheadings.group', ['group' => $previousRecordGroupTitle, 'label' => $pluralModelLabel])"
                                            :query="$group->scopeQuery($this->getAllTableSummaryQuery(), $previousRecord)"
                                            :selected-state="$groupedSummarySelectedState[$previousRecordGroupKey] ?? []"
                                            extra-heading-column
                                            :placeholder-columns="false"
                                        />
                                    </x-filament-tables::table>
                                @endif

                                <div
                                    @class([
                                        'col-span-full bg-gray-500/5',
                                        'rounded-xl shadow-sm' => $contentGrid,
                                    ])
                                >
                                    @php
                                        $tag = $group->isCollapsible() ? 'button' : 'div';
                                    @endphp

                                    <{{ $tag }}
                                        @if ($group->isCollapsible())
                                            type="button"
                                            x-on:click="toggleCollapseGroup(@js($recordGroupTitle))"
                                        @endif
                                        class="flex w-full justify-start gap-x-2 whitespace-nowrap px-4 py-2"
                                    >
                                        @if ($group->isCollapsible())
                                            <x-filament::icon
                                                name="heroicon-m-chevron-up"
                                                alias="tables::grouping.collapse"
                                                size="h-5 w-5"
                                                color="text-gray-600 dark:text-gray-300"
                                                class="transition"
                                                x-bind:class="isGroupCollapsed({{ \Illuminate\Support\Js::from($recordGroupTitle) }}) && 'rotate-180'"
                                            />
                                        @endif

                                        <div
                                            class="flex flex-col items-start gap-y-1"
                                        >
                                            <span
                                                class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                            >
                                                @if ($group->isTitlePrefixedWithLabel())
                                                        {{ $group->getLabel() }}:
                                                @endif

                                                {{ $recordGroupTitle }}
                                            </span>

                                            @if (filled($recordGroupDescription = $group->getDescription($record, $recordGroupTitle)))
                                                <span
                                                    class="text-sm text-gray-500 dark:text-gray-400"
                                                >
                                                    {{ $recordGroupDescription }}
                                                </span>
                                            @endif
                                        </div>
                                    </{{ $tag }}>
                                </div>
                            @endif

                            <div
                                @if ($hasCollapsibleColumnsLayout)
                                    x-data="{ isCollapsed: @js($collapsibleColumnsLayout->isCollapsed()) }"
                                    x-init="$dispatch('collapsible-table-row-initialized')"
                                    x-on:expand-all-table-rows.window="isCollapsed = false"
                                    x-on:collapse-all-table-rows.window="isCollapsed = true"
                                @endif
                                wire:key="{{ $this->id }}.table.records.{{ $recordKey }}"
                                @if ($isReordering)
                                    x-sortable-item="{{ $recordKey }}"
                                    x-sortable-handle
                                @endif
                                x-bind:class="{
                                    'hidden':
                                        {{ $group?->isCollapsible() ? 'true' : 'false' }} &&
                                        isGroupCollapsed('{{ $recordGroupTitle }}'),
                                }"
                            >
                                <div
                                    x-bind:class="{
                                        'bg-gray-50 dark:bg-gray-500/10': isRecordSelected('{{ $recordKey }}'),
                                    }"
                                    @class([
                                        'relative h-full px-4 transition',
                                        'hover:bg-gray-50 dark:hover:bg-gray-500/10' => $recordUrl || $recordAction,
                                        'dark:border-gray-600' => ! $contentGrid,
                                        'group' => $isReordering,
                                        'rounded-xl border border-gray-200 shadow-sm dark:border-gray-700 dark:bg-gray-700/40' => $contentGrid,
                                        ...$getRecordClasses($record),
                                    ])
                                >
                                    <div
                                        @class([
                                            'items-center gap-4 md:me-0 md:flex' => (! $contentGrid),
                                            'me-6' => $isSelectionEnabled || $hasCollapsibleColumnsLayout || $isReordering,
                                        ])
                                    >
                                        <x-filament-tables::reorder.handle
                                            @class([
                                                'absolute top-3 end-3',
                                                'md:relative md:top-0 end-0' => ! $contentGrid,
                                                'hidden' => ! $isReordering,
                                            ])
                                        />

                                        @if ($isSelectionEnabled && $isRecordSelectable($record))
                                            <x-filament-tables::checkbox
                                                :label="__('filament-tables::table.fields.bulk_select_record.label', ['key' => $recordKey])"
                                                x-model="selectedRecords"
                                                :value="$recordKey"
                                                @class([
                                                    'filament-tables-record-checkbox absolute top-3 end-3',
                                                    'md:relative md:top-0 md:end-0' => ! $contentGrid,
                                                    'hidden' => $isReordering,
                                                ])
                                            />
                                        @endif

                                        @if ($hasCollapsibleColumnsLayout)
                                            <div
                                                @class([
                                                    'absolute end-1',
                                                    'top-10' => $isSelectionEnabled,
                                                    'top-1' => ! $isSelectionEnabled,
                                                    'md:relative md:end-0 md:top-0' => ! $contentGrid,
                                                    'hidden' => $isReordering,
                                                ])
                                            >
                                                <x-filament::icon-button
                                                    icon="heroicon-m-chevron-down"
                                                    icon-alias="tables::collapsible-column.trigger"
                                                    color="gray"
                                                    size="sm"
                                                    x-on:click="isCollapsed = ! isCollapsed"
                                                    x-bind:class="isCollapsed || '-rotate-180'"
                                                    class="transition"
                                                />
                                            </div>
                                        @endif

                                        @if ($recordUrl)
                                            <a
                                                href="{{ $recordUrl }}"
                                                class="filament-tables-record-url-link block flex-1 py-3"
                                            >
                                                <x-filament-tables::columns.layout
                                                    :components="$getColumnsLayout()"
                                                    :record="$record"
                                                    :record-key="$recordKey"
                                                    :row-loop="$loop"
                                                />
                                            </a>
                                        @elseif ($recordAction)
                                            @php
                                                if ($getAction($recordAction)) {
                                                    $recordWireClickAction = "mountTableAction('{$recordAction}', '{$recordKey}')";
                                                } else {
                                                    $recordWireClickAction = "{$recordAction}('{$recordKey}')";
                                                }
                                            @endphp

                                            <button
                                                wire:click="{{ $recordWireClickAction }}"
                                                wire:target="{{ $recordWireClickAction }}"
                                                wire:loading.attr="disabled"
                                                type="button"
                                                class="filament-tables-record-action-button block flex-1 py-3 disabled:pointer-events-none disabled:opacity-70"
                                            >
                                                <x-filament-tables::columns.layout
                                                    :components="$getColumnsLayout()"
                                                    :record="$record"
                                                    :record-key="$recordKey"
                                                    :row-loop="$loop"
                                                />
                                            </button>
                                        @else
                                            <div class="flex-1 py-3">
                                                <x-filament-tables::columns.layout
                                                    :components="$getColumnsLayout()"
                                                    :record="$record"
                                                    :record-key="$recordKey"
                                                    :row-loop="$loop"
                                                />
                                            </div>
                                        @endif

                                        @if (count($actions))
                                            <x-filament-tables::actions
                                                :actions="$actions"
                                                :alignment="$actionsPosition === ActionsPosition::AfterContent ? 'start' : 'start md:end'"
                                                :record="$record"
                                                wrap="-md"
                                                @class([
                                                    'absolute bottom-1 end-1' => $actionsPosition === ActionsPosition::BottomCorner,
                                                    'md:relative md:bottom-0 md:end-0' => $actionsPosition === ActionsPosition::BottomCorner && (! $contentGrid),
                                                    'mb-3' => $actionsPosition === ActionsPosition::AfterContent,
                                                    'md:mb-0' => $actionsPosition === ActionsPosition::AfterContent && (! $contentGrid),
                                                    'hidden' => $isReordering,
                                                ])
                                            />
                                        @endif
                                    </div>

                                    @if ($hasCollapsibleColumnsLayout)
                                        <div
                                            x-show="! isCollapsed"
                                            x-collapse
                                            @class([
                                                '-mx-2 pb-2',
                                                'md:ps-20' => (! $contentGrid) && $isSelectionEnabled,
                                                'md:ps-12' => (! $contentGrid) && (! $isSelectionEnabled),
                                                'hidden' => $isReordering,
                                            ])
                                        >
                                            {{ $collapsibleColumnsLayout->viewData(['recordKey' => $recordKey]) }}
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @php
                                $previousRecordGroupKey = $recordGroupKey;
                                $previousRecordGroupTitle = $recordGroupTitle;
                                $previousRecord = $record;
                            @endphp
                        @endforeach

                        @if ($hasSummary && (! $isReordering) && filled($previousRecordGroupTitle) && ((! $records instanceof \Illuminate\Contracts\Pagination\Paginator) || (! $records->hasMorePages())))
                            <x-filament-tables::table class="col-span-full">
                                <x-filament-tables::summary.row
                                    :columns="$columns"
                                    :heading="__('filament-tables::table.summary.subheadings.group', ['group' => $previousRecordGroupTitle, 'label' => $pluralModelLabel])"
                                    :query="$group->scopeQuery($this->getAllTableSummaryQuery(), $previousRecord)"
                                    :selected-state="$groupedSummarySelectedState[$previousRecordGroupKey] ?? []"
                                    extra-heading-column
                                    :placeholder-columns="false"
                                />
                            </x-filament-tables::table>
                        @endif
                    </x-filament::grid>
                @endif

                @if (($content || $hasColumnsLayout) && $contentFooter)
                    {{ $contentFooter->with(['columns' => $columns, 'records' => $records]) }}
                @endif

                @if ($hasSummary && (! $isReordering))
                    <x-filament-tables::table
                        class="border-t dark:border-gray-700"
                    >
                        <x-filament-tables::summary
                            :columns="$columns"
                            :plural-model-label="$pluralModelLabel"
                            :records="$records"
                            extra-heading-column
                            :placeholder-columns="false"
                        />
                    </x-filament-tables::table>
                @endif
            @elseif (($records !== null) && count($records))
                <x-filament-tables::table :reorderable="$isReorderable">
                    <x-slot name="header">
                        @if ($isReordering)
                            <th></th>
                        @else
                            @if (count($actions) && $actionsPosition === ActionsPosition::BeforeCells)
                                @if ($actionsColumnLabel)
                                    <x-filament-tables::header-cell>
                                        {{ $actionsColumnLabel }}
                                    </x-filament-tables::header-cell>
                                @else
                                    <th class="w-5"></th>
                                @endif
                            @endif

                            @if ($isSelectionEnabled && $recordCheckboxPosition === RecordCheckboxPosition::BeforeCells)
                                <x-filament-tables::checkbox.cell>
                                    <x-filament-tables::checkbox
                                        :label="__('filament-tables::table.fields.bulk_select_page.label')"
                                        x-on:click="toggleSelectRecordsOnPage"
                                        x-bind:checked="
                                            let recordsOnPage = getRecordsOnPage()

                                            if (recordsOnPage.length && areRecordsSelected(recordsOnPage)) {
                                                $el.checked = true

                                                return 'checked'
                                            }

                                            $el.checked = false

                                            return null
                                        "
                                    />
                                </x-filament-tables::checkbox.cell>
                            @endif

                            @if (count($actions) && $actionsPosition === ActionsPosition::BeforeColumns)
                                @if ($actionsColumnLabel)
                                    <x-filament-tables::header-cell>
                                        {{ $actionsColumnLabel }}
                                    </x-filament-tables::header-cell>
                                @else
                                    <th class="w-5"></th>
                                @endif
                            @endif
                        @endif

                        @if ($isGroupsOnly)
                            <th
                                class="filament-tables-header-cell whitespace-nowrap px-4 py-2 text-sm font-medium text-gray-600 dark:text-gray-300"
                            >
                                {{ $group->getLabel() }}
                            </th>
                        @endif

                        @foreach ($columns as $column)
                            <x-filament-tables::header-cell
                                :actively-sorted="$getSortColumn() === $column->getName()"
                                :name="$column->getName()"
                                :alignment="$column->getAlignment()"
                                :sortable="$column->isSortable() && (! $isReordering)"
                                :sort-direction="$getSortDirection()"
                                class="filament-table-header-cell-{{ str($column->getName())->camel()->kebab() }} {{ $getHiddenClasses($column) }}"
                                :attributes="\Filament\Support\prepare_inherited_attributes($column->getExtraHeaderAttributeBag())"
                                :wrap="$column->isHeaderWrapped()"
                            >
                                {{ $column->getLabel() }}
                            </x-filament-tables::header-cell>
                        @endforeach

                        @if (! $isReordering)
                            @if (count($actions) && $actionsPosition === ActionsPosition::AfterColumns)
                                @if ($actionsColumnLabel)
                                    <x-filament-tables::header-cell
                                        alignment="right"
                                    >
                                        {{ $actionsColumnLabel }}
                                    </x-filament-tables::header-cell>
                                @else
                                    <th class="w-5"></th>
                                @endif
                            @endif

                            @if ($isSelectionEnabled && $recordCheckboxPosition === RecordCheckboxPosition::AfterCells)
                                <x-filament-tables::checkbox.cell>
                                    <x-filament-tables::checkbox
                                        :label="__('filament-tables::table.fields.bulk_select_page.label')"
                                        x-on:click="toggleSelectRecordsOnPage"
                                        x-bind:checked="
                                            let recordsOnPage = getRecordsOnPage()

                                            if (recordsOnPage.length && areRecordsSelected(recordsOnPage)) {
                                                $el.checked = true

                                                return 'checked'
                                            }

                                            $el.checked = false

                                            return null
                                        "
                                    />
                                </x-filament-tables::checkbox.cell>
                            @endif

                            @if (count($actions) && $actionsPosition === ActionsPosition::AfterCells)
                                @if ($actionsColumnLabel)
                                    <x-filament-tables::header-cell
                                        alignment="right"
                                    >
                                        {{ $actionsColumnLabel }}
                                    </x-filament-tables::header-cell>
                                @else
                                    <th class="w-5"></th>
                                @endif
                            @endif
                        @endif
                    </x-slot>

                    @if ($isColumnSearchVisible)
                        <x-filament-tables::row>
                            @if ($isReordering)
                                <td></td>
                            @else
                                @if (count($actions) && in_array($actionsPosition, [ActionsPosition::BeforeCells, ActionsPosition::BeforeColumns]))
                                    <td></td>
                                @endif

                                @if ($isSelectionEnabled && $recordCheckboxPosition === RecordCheckboxPosition::BeforeCells)
                                    <td></td>
                                @endif
                            @endif

                            @foreach ($columns as $column)
                                <x-filament-tables::cell
                                    class="filament-table-individual-search-cell-{{ str($column->getName())->camel()->kebab() }} px-4 py-1"
                                >
                                    @if ($column->isIndividuallySearchable())
                                        <x-filament-tables::search-input
                                            wire-model="tableColumnSearches.{{ $column->getName() }}"
                                        />
                                    @endif
                                </x-filament-tables::cell>
                            @endforeach

                            @if (! $isReordering)
                                @if (count($actions) && in_array($actionsPosition, [ActionsPosition::AfterColumns, ActionsPosition::AfterCells]))
                                    <td></td>
                                @endif

                                @if ($isSelectionEnabled && $recordCheckboxPosition === RecordCheckboxPosition::AfterCells)
                                    <td></td>
                                @endif
                            @endif
                        </x-filament-tables::row>
                    @endif

                    @if (($records !== null) && count($records))
                        @php
                            $previousRecord = null;
                            $previousRecordGroupKey = null;
                            $previousRecordGroupTitle = null;
                        @endphp

                        @foreach ($records as $record)
                            @php
                                $recordAction = $getRecordAction($record);
                                $recordKey = $getRecordKey($record);
                                $recordUrl = $getRecordUrl($record);
                                $recordGroupKey = $group?->getKey($record);
                                $recordGroupTitle = $group?->getTitle($record);
                            @endphp

                            @if ($recordGroupTitle !== $previousRecordGroupTitle)
                                @if ($hasSummary && (! $isReordering) && filled($previousRecordGroupTitle))
                                    <x-filament-tables::summary.row
                                        :actions="count($actions)"
                                        :actions-position="$actionsPosition"
                                        :columns="$columns"
                                        :heading="$isGroupsOnly ? $previousRecordGroupTitle : __('filament-tables::table.summary.subheadings.group', ['group' => $previousRecordGroupTitle, 'label' => $pluralModelLabel])"
                                        :groups-only="$isGroupsOnly"
                                        :selection-enabled="$isSelectionEnabled"
                                        :query="$group->scopeQuery($this->getAllTableSummaryQuery(), $previousRecord)"
                                        :selected-state="$groupedSummarySelectedState[$previousRecordGroupKey] ?? []"
                                        :record-checkbox-position="$recordCheckboxPosition"
                                    />
                                @endif

                                @if (! $isGroupsOnly)
                                    <x-filament-tables::row
                                        class="filament-tables-group-header-row bg-gray-500/5"
                                    >
                                        <td colspan="{{ $columnsCount }}">
                                            @php
                                                $tag = $group->isCollapsible() ? 'button' : 'div';
                                            @endphp

                                            <{{ $tag }}
                                                @if ($group->isCollapsible())
                                                    type="button"
                                                    x-on:click="toggleCollapseGroup(@js($recordGroupTitle))"
                                                @endif
                                                class="flex w-full justify-start gap-x-2 whitespace-nowrap px-4 py-2"
                                            >
                                                @if ($group->isCollapsible())
                                                    <x-filament::icon
                                                        name="heroicon-m-chevron-up"
                                                        alias="tables::grouping.collapse"
                                                        size="h-5 w-5"
                                                        color="text-gray-600 dark:text-gray-300"
                                                        class="transition"
                                                        x-bind:class="isGroupCollapsed({{ \Illuminate\Support\Js::from($recordGroupTitle) }}) && 'rotate-180'"
                                                    />
                                                @endif

                                                <div
                                                    class="flex flex-col items-start gap-y-1"
                                                >
                                                    <span
                                                        class="text-sm font-medium text-gray-600 dark:text-gray-300"
                                                    >
                                                        @if ($group->isTitlePrefixedWithLabel())
                                                                {{ $group->getLabel() }}:
                                                        @endif

                                                        {{ $recordGroupTitle }}
                                                    </span>

                                                    @if (filled($recordGroupDescription = $group->getDescription($record, $recordGroupTitle)))
                                                        <span
                                                            class="text-sm text-gray-500 dark:text-gray-400"
                                                        >
                                                            {{ $recordGroupDescription }}
                                                        </span>
                                                    @endif
                                                </div>
                                            </{{ $tag }}>
                                        </td>
                                    </x-filament-tables::row>
                                @endif
                            @endif

                            @if (! $isGroupsOnly)
                                <x-filament-tables::row
                                    :record-action="$recordAction"
                                    :record-url="$recordUrl"
                                    :wire:key="$this->id . '.table.records.' . $recordKey"
                                    :x-sortable-item="$isReordering ? $recordKey : null"
                                    :x-sortable-handle="$isReordering"
                                    :striped="$isStriped"
                                    x-bind:class="{
                                        'hidden': {{ $group?->isCollapsible() ? 'true' : 'false' }} && isGroupCollapsed('{{ $recordGroupTitle }}'),
                                        'bg-gray-50 dark:bg-gray-500/10': isRecordSelected('{{ $recordKey }}'),
                                    }"
                                    @class([
                                        'group cursor-move' => $isReordering,
                                        ...$getRecordClasses($record),
                                    ])
                                >
                                    <x-filament-tables::reorder.cell
                                        @class([
                                            'hidden' => ! $isReordering,
                                        ])
                                    >
                                        @if ($isReordering)
                                            <x-filament-tables::reorder.handle />
                                        @endif
                                    </x-filament-tables::reorder.cell>

                                    @if (count($actions) && $actionsPosition === ActionsPosition::BeforeCells)
                                        <x-filament-tables::actions.cell
                                            @class([
                                                'hidden' => $isReordering,
                                            ])
                                        >
                                            <x-filament-tables::actions
                                                :actions="$actions"
                                                :alignment="$actionsAlignment ?? 'start'"
                                                :record="$record"
                                            />
                                        </x-filament-tables::actions.cell>
                                    @endif

                                    @if ($isSelectionEnabled && $recordCheckboxPosition === RecordCheckboxPosition::BeforeCells)
                                        <x-filament-tables::checkbox.cell
                                            @class([
                                                'hidden' => $isReordering,
                                            ])
                                        >
                                            @if ($isRecordSelectable($record))
                                                <x-filament-tables::checkbox
                                                    :label="__('filament-tables::table.fields.bulk_select_record.label', ['key' => $recordKey])"
                                                    x-model="selectedRecords"
                                                    :value="$recordKey"
                                                    class="filament-tables-record-checkbox"
                                                />
                                            @endif
                                        </x-filament-tables::checkbox.cell>
                                    @endif

                                    @if (count($actions) && $actionsPosition === ActionsPosition::BeforeColumns)
                                        <x-filament-tables::actions.cell
                                            @class([
                                                'hidden' => $isReordering,
                                            ])
                                        >
                                            <x-filament-tables::actions
                                                :actions="$actions"
                                                :alignment="$actionsAlignment ?? 'start'"
                                                :record="$record"
                                            />
                                        </x-filament-tables::actions.cell>
                                    @endif

                                    @foreach ($columns as $column)
                                        @php
                                            $column->record($record);
                                            $column->rowLoop($loop->parent);
                                        @endphp

                                        <x-filament-tables::cell
                                            wire:key="{{ $this->id }}.table.record.{{ $recordKey }}.column.{{ $column->getName() }}"
                                            wire:loading.remove.delay=""
                                            wire:target="{{ implode(',', \Filament\Tables\Table::LOADING_TARGETS) }}"
                                            class="filament-table-cell-{{ str($column->getName())->camel()->kebab() }} {{ $getHiddenClasses($column) }}"
                                            :attributes="\Filament\Support\prepare_inherited_attributes($column->getExtraCellAttributeBag())"
                                        >
                                            <x-filament-tables::columns.column
                                                :column="$column"
                                                :record="$record"
                                                :record-action="$recordAction"
                                                :record-key="$recordKey"
                                                :record-url="$recordUrl"
                                                :is-click-disabled="$column->isClickDisabled() || $isReordering"
                                            />
                                        </x-filament-tables::cell>
                                    @endforeach

                                    @if (count($actions) && $actionsPosition === ActionsPosition::AfterColumns)
                                        <x-filament-tables::actions.cell
                                            @class([
                                                'hidden' => $isReordering,
                                            ])
                                        >
                                            <x-filament-tables::actions
                                                :actions="$actions"
                                                :alignment="$actionsAlignment ?? 'end'"
                                                :record="$record"
                                            />
                                        </x-filament-tables::actions.cell>
                                    @endif

                                    @if ($isSelectionEnabled && $recordCheckboxPosition === RecordCheckboxPosition::AfterCells)
                                        <x-filament-tables::checkbox.cell
                                            @class([
                                                'hidden' => $isReordering,
                                            ])
                                        >
                                            @if ($isRecordSelectable($record))
                                                <x-filament-tables::checkbox
                                                    :label="__('filament-tables::table.fields.bulk_select_record.label', ['key' => $recordKey])"
                                                    x-model="selectedRecords"
                                                    :value="$recordKey"
                                                    class="filament-tables-record-checkbox"
                                                />
                                            @endif
                                        </x-filament-tables::checkbox.cell>
                                    @endif

                                    @if (count($actions) && $actionsPosition === ActionsPosition::AfterCells)
                                        <x-filament-tables::actions.cell
                                            @class([
                                                'hidden' => $isReordering,
                                            ])
                                        >
                                            <x-filament-tables::actions
                                                :actions="$actions"
                                                :alignment="$actionsAlignment ?? 'end'"
                                                :record="$record"
                                            />
                                        </x-filament-tables::actions.cell>
                                    @endif

                                    <x-filament-tables::loading-cell
                                        :colspan="$columnsCount"
                                        wire:loading.class.remove.delay="hidden"
                                        class="hidden"
                                        :wire:key="$this->id . '.table.records.' . $recordKey . '.loading-cell'"
                                        wire:target="{{ implode(',', \Filament\Tables\Table::LOADING_TARGETS) }}"
                                    />
                                </x-filament-tables::row>
                            @endif

                            @php
                                $previousRecordGroupKey = $recordGroupKey;
                                $previousRecordGroupTitle = $recordGroupTitle;
                                $previousRecord = $record;
                            @endphp
                        @endforeach

                        @if ($hasSummary && (! $isReordering) && filled($previousRecordGroupTitle) && ((! $records instanceof \Illuminate\Contracts\Pagination\Paginator) || (! $records->hasMorePages())))
                            <x-filament-tables::summary.row
                                :actions="count($actions)"
                                :actions-position="$actionsPosition"
                                :columns="$columns"
                                :heading="$isGroupsOnly ? $previousRecordGroupTitle : __('filament-tables::table.summary.subheadings.group', ['group' => $previousRecordGroupTitle, 'label' => $pluralModelLabel])"
                                :groups-only="$isGroupsOnly"
                                :selection-enabled="$isSelectionEnabled"
                                :query="$group->scopeQuery($this->getAllTableSummaryQuery(), $previousRecord)"
                                :selected-state="$groupedSummarySelectedState[$previousRecordGroupKey] ?? []"
                                :record-checkbox-position="$recordCheckboxPosition"
                            />
                        @endif

                        @if ($contentFooter)
                            <x-slot name="footer">
                                {{ $contentFooter->with(['columns' => $columns, 'records' => $records]) }}
                            </x-slot>
                        @endif

                        @if ($hasSummary && (! $isReordering))
                            <x-filament-tables::summary
                                :actions="count($actions)"
                                :actions-position="$actionsPosition"
                                :columns="$columns"
                                :groups-only="$isGroupsOnly"
                                :selection-enabled="$isSelectionEnabled"
                                :plural-model-label="$pluralModelLabel"
                                :records="$records"
                                :record-checkbox-position="$recordCheckboxPosition"
                            />
                        @endif
                    @endif
                </x-filament-tables::table>
            @elseif ($records === null)
                <div
                    class="filament-tables-defer-loading-indicator flex items-center justify-center p-6"
                >
                    <div
                        class="flex h-16 w-16 items-center justify-center rounded-full bg-primary-50 text-primary-500 dark:bg-gray-700"
                    >
                        <x-filament::loading-indicator class="h-6 w-6" />
                    </div>
                </div>
            @else
                @if ($emptyState = $getEmptyState())
                    {{ $emptyState }}
                @else
                    <tr>
                        <td colspan="{{ $columnsCount }}">
                            <div
                                class="flex w-full items-center justify-center p-4"
                            >
                                <x-filament-tables::empty-state
                                    :icon="$getEmptyStateIcon()"
                                    :actions="$getEmptyStateActions()"
                                >
                                    <x-slot name="heading">
                                        {{ $getEmptyStateHeading() }}
                                    </x-slot>

                                    <x-slot name="description">
                                        {{ $getEmptyStateDescription() }}
                                    </x-slot>
                                </x-filament-tables::empty-state>
                            </div>
                        </td>
                    </tr>
                @endif
            @endif
        </div>

        @if ($records instanceof \Illuminate\Contracts\Pagination\Paginator &&
             ((! $records instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator) || $records->total()))
            <div
                class="filament-tables-pagination-container border-t p-2 dark:border-gray-700"
            >
                <x-filament-tables::pagination
                    :paginator="$records"
                    :page-options="$getPaginationPageOptions()"
                />
            </div>
        @endif

        @if ($hasFiltersBelowContent)
            <div class="px-2 pb-2">
                <x-filament::hr />

                <div class="mt-2 p-4">
                    <x-filament-tables::filters :form="$getFiltersForm()" />
                </div>
            </div>
        @endif
    </x-filament-tables::container>

    <x-filament-actions::modals />
</div>
