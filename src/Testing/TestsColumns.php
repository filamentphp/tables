<?php

namespace Filament\Tables\Testing;

use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Testing\Assert;
use Livewire\Testing\TestableLivewire;

/**
 * @method HasTable instance()
 *
 * @mixin TestableLivewire
 */
class TestsColumns
{
    public function assertCanRenderTableColumn(): Closure
    {
        return function (string $name): static {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnVisible($name);

            $column = $this->instance()->getTable()->getColumn($name);

            $html = array_map(
                function ($record) use ($column) {
                    return $column->record($record)->toHtml();
                },
                $this->instance()->getTableRecords()->all(),
            );

            $this->assertSeeHtml($html);

            return $this;
        };
    }

    public function assertCanNotRenderTableColumn(): Closure
    {
        return function (string $name): static {
            /** @phpstan-ignore-next-line  */
            $this->assertTableColumnExists($name);

            $column = $this->instance()->getTable()->getColumn($name);

            $html = array_map(
                function ($record) use ($column) {
                    return $column->record($record)->toHtml();
                },
                $this->instance()->getTableRecords()->all(),
            );

            $this->assertDontSeeHtml($html);

            return $this;
        };
    }

    public function assertTableColumnExists(): Closure
    {
        return function (string $name): static {
            $column = $this->instance()->getTable()->getColumn($name);

            $livewireClass = $this->instance()::class;

            Assert::assertInstanceOf(
                Column::class,
                $column,
                message: "Failed asserting that a table column with name [{$name}] exists on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableColumnVisible(): Closure
    {
        return function (string $name): static {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            $column = $this->instance()->getTable()->getColumn($name);

            $livewireClass = $this->instance()::class;

            Assert::assertFalse(
                $column->isHidden(),
                message: "Failed asserting that a table column with name [{$name}] is visible on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableColumnHidden(): Closure
    {
        return function (string $name): static {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            $column = $this->instance()->getTable()->getColumn($name);

            $livewireClass = $this->instance()::class;

            Assert::assertTrue(
                $column->isHidden(),
                message: "Failed asserting that a table column with name [{$name}] is hidden on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableColumnStateSet(): Closure
    {
        return function (string $name, $value, $record): static {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            $column = $this->instance()->getTable()->getColumn($name);

            if (! ($record instanceof Model)) {
                $record = $this->instance()->getTableRecord($record);
            }

            $column->record($record);

            $livewireClass = $this->instance()::class;

            Assert::assertTrue(
                $column->getState() == $value,
                message: "Failed asserting that a table column with name [{$name}] has value of [{$value}] for record [{$record->getKey()}] on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableColumnStateNotSet(): Closure
    {
        return function (string $name, $value, $record): static {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            $column = $this->instance()->getTable()->getColumn($name);

            if (! ($record instanceof Model)) {
                $record = $this->instance()->getTableRecord($record);
            }

            $column->record($record);

            $livewireClass = $this->instance()::class;

            Assert::assertFalse(
                $column->getState() == $value,
                message: "Failed asserting that a table column with name [{$name}] does not have a value of [{$value}] for record [{$record->getKey()}] on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableColumnFormattedStateSet(): Closure
    {
        return function (string $name, $value, $record): static {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            /** @var \Filament\Tables\Columns\TextColumn $column */
            $column = $this->instance()->getTable()->getColumn($name);

            if (! ($record instanceof Model)) {
                $record = $this->instance()->getTableRecord($record);
            }

            $column->record($record);

            $livewireClass = $this->instance()::class;

            Assert::assertTrue(
                $column->formatState($column->getState()) == $value,
                message: "Failed asserting that a table column with name [{$name}] has a formatted state of [{$value}] for record [{$record->getKey()}] on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableColumnFormattedStateNotSet(): Closure
    {
        return function (string $name, $value, $record): static {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            /** @var \Filament\Tables\Columns\TextColumn $column */
            $column = $this->instance()->getTable()->getColumn($name);

            if (! ($record instanceof Model)) {
                $record = $this->instance()->getTableRecord($record);
            }

            $column->record($record);

            $livewireClass = $this->instance()::class;

            Assert::assertFalse(
                $column->formatState($column->getState()) == $value,
                message: "Failed asserting that a table column with name [{$name}] does not have a formatted state of [{$value}] for record [{$record->getKey()}] on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableColumnHasExtraAttributes(): Closure
    {
        return function (string $name, array $attributes, $record) {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            $column = $this->instance()->getTable()->getColumn($name);

            if (! ($record instanceof Model)) {
                $record = $this->instance()->getTableRecord($record);
            }

            $column->record($record);

            $attributesString = print_r($attributes, true);

            $livewireClass = $this->instance()::class;

            Assert::assertTrue(
                $column->getExtraAttributes() == $attributes,
                message: "Failed asserting that a table column with name [{$name}] has extra attributes [{$attributesString}] for record [{$record->getKey()}] on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableColumnDoesNotHaveExtraAttributes(): Closure
    {
        return function (string $name, array $attributes, $record) {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            $column = $this->instance()->getTable()->getColumn($name);

            if (! ($record instanceof Model)) {
                $record = $this->instance()->getTableRecord($record);
            }

            $column->record($record);

            $attributesString = print_r($attributes, true);

            $livewireClass = $this->instance()::class;

            Assert::assertFalse(
                $column->getExtraAttributes() == $attributes,
                message: "Failed asserting that a table column with name [{$name}] does not have extra attributes [{$attributesString}] for record [{$record->getKey()}] on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableColumnHasDescription(): Closure
    {
        return function (string $name, $description, $record, string $position = 'below') {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            /** @var \Filament\Tables\Columns\TextColumn $column */
            $column = $this->instance()->getTable()->getColumn($name);

            if (! ($record instanceof Model)) {
                $record = $this->instance()->getTableRecord($record);
            }

            $column->record($record);

            $actualDescription = $position === 'above' ? $column->getDescriptionAbove() : $column->getDescriptionBelow();

            $livewireClass = $this->instance()::class;

            Assert::assertTrue(
                $actualDescription == $description,
                message: "Failed asserting that a table column with name [{$name}] has description [{$description}] for record [{$record->getKey()}] on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableColumnDoesNotHaveDescription(): Closure
    {
        return function (string $name, $description, $record, string $position = 'below') {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            /** @var \Filament\Tables\Columns\TextColumn $column */
            $column = $this->instance()->getTable()->getColumn($name);

            if (! ($record instanceof Model)) {
                $record = $this->instance()->getTableRecord($record);
            }

            $column->record($record);

            $actualDescription = $position === 'above' ? $column->getDescriptionAbove() : $column->getDescriptionBelow();

            $livewireClass = $this->instance()::class;

            Assert::assertFalse(
                $actualDescription == $description,
                message: "Failed asserting that a table column with name [{$name}] does not have description [{$description}] for record [{$record->getKey()}] on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableSelectColumnHasOptions(): Closure
    {
        return function (string $name, array $options, $record) {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            /** @var \Filament\Tables\Columns\SelectColumn $column */
            $column = $this->instance()->getTable()->getColumn($name);

            if (! ($record instanceof Model)) {
                $record = $this->instance()->getTableRecord($record);
            }

            $column->record($record);

            $optionsString = print_r($options, true);

            $livewireClass = $this->instance()::class;

            Assert::assertTrue(
                $column->getOptions() == $options,
                message: "Failed asserting that a table column with name [{$name}] has options [{$optionsString}] for record [{$record->getKey()}] on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function assertTableSelectColumnDoesNotHaveOptions(): Closure
    {
        return function (string $name, array $options, $record) {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            /** @var \Filament\Tables\Columns\SelectColumn $column */
            $column = $this->instance()->getTable()->getColumn($name);

            if (! ($record instanceof Model)) {
                $record = $this->instance()->getTableRecord($record);
            }

            $column->record($record);

            $optionsString = print_r($options, true);

            $livewireClass = $this->instance()::class;

            Assert::assertFalse(
                $column->getOptions() == $options,
                message: "Failed asserting that a table column with name [{$name}] does not have options [{$optionsString}] for record [{$record->getKey()}] on the [{$livewireClass}] component.",
            );

            return $this;
        };
    }

    public function callTableColumnAction(): Closure
    {
        return function (string $name, $record = null): static {
            /** @phpstan-ignore-next-line */
            $this->assertTableColumnExists($name);

            if ($record instanceof Model) {
                $record = $this->instance()->getTableRecordKey($record);
            }

            $this->call('callTableColumnAction', $name, $record);

            return $this;
        };
    }

    public function sortTable(): Closure
    {
        return function (string $name = null, string $direction = null): static {
            $this->call('sortTable', $name, $direction);

            return $this;
        };
    }

    public function searchTable(): Closure
    {
        return function (string $search = null): static {
            $this->set('tableSearch', $search);

            return $this;
        };
    }

    public function searchTableColumns(): Closure
    {
        return function (array $searches): static {
            $this->set('tableColumnSearches', $searches);

            return $this;
        };
    }
}
