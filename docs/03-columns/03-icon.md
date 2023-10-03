---
title: Icon column
---
import AutoScreenshot from "@components/AutoScreenshot.astro"

## Overview

Icon columns render an [icon](https://blade-ui-kit.com/blade-icons?set=1#search) representing their contents:

```php
use Filament\Tables\Columns\IconColumn;

IconColumn::make('status')
    ->icon(fn (string $state): string => match ($state) {
        'draft' => 'heroicon-o-pencil',
        'reviewing' => 'heroicon-o-clock',
        'published' => 'heroicon-o-check-circle',
    })
```

In the function, `$state` is the value of the column, and `$record` can be used to access the underlying Eloquent record.

<AutoScreenshot name="tables/columns/icon/simple" alt="Icon column" version="3.x" />

## Customizing the color

Icon columns may also have a set of icon colors, using the same syntax. They may be either `danger`, `gray`, `info`, `primary`, `success` or `warning`:

```php
use Filament\Tables\Columns\IconColumn;

IconColumn::make('status')
    ->color(fn (string $state): string => match ($state) {
        'draft' => 'info',
        'reviewing' => 'warning',
        'published' => 'success',
        default => 'gray',
    })
```

In the function, `$state` is the value of the column, and `$record` can be used to access the underlying Eloquent record.

<AutoScreenshot name="tables/columns/icon/color" alt="Icon column with color" version="3.x" />

## Customizing the size

The default icon size is `lg`, but you may customize the size to be either `xs`, `sm`, `md`, `lg` or `xl`:

```php
use Filament\Tables\Columns\IconColumn;

IconColumn::make('status')
    ->size('md')
```

<AutoScreenshot name="tables/columns/icon/medium" alt="Medium-sized icon column" version="3.x" />

## Handling booleans

Icon columns can display a check or cross icon based on the contents of the database column, either true or false, using the `boolean()` method:

```php
use Filament\Tables\Columns\IconColumn;

IconColumn::make('is_featured')
    ->boolean()
```

<AutoScreenshot name="tables/columns/icon/boolean" alt="Icon column to display a boolean" version="3.x" />

### Customizing the boolean icons

You may customize the icon representing each state. Icons are the name of a Blade component present. By default, [Heroicons v1](https://v1.heroicons.com) are installed:

```php
use Filament\Tables\Columns\IconColumn;

IconColumn::make('is_featured')
    ->boolean()
    ->trueIcon('heroicon-o-check-badge')
    ->falseIcon('heroicon-o-x-mark')
```

<AutoScreenshot name="tables/columns/icon/boolean-icon" alt="Icon column to display a boolean with custom icons" version="3.x" />

### Customizing the boolean colors

You may customize the icon color representing each state. These may be either `danger`, `gray`, `info`, `primary`, `success` or `warning`:

```php
use Filament\Tables\Columns\IconColumn;

IconColumn::make('is_featured')
    ->boolean()
    ->trueColor('info')
    ->falseColor('warning')
```

<AutoScreenshot name="tables/columns/icon/boolean-color" alt="Icon column to display a boolean with custom colors" version="3.x" />
