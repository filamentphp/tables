<?php

namespace Filament\Tables\Columns\Concerns;

use Closure;
use Filament\Support\Contracts\HasLabel as LabelInterface;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;

use function Filament\Support\format_money;
use function Filament\Support\format_number;

trait CanFormatState
{
    protected ?Closure $formatStateUsing = null;

    protected int | Closure | null $characterLimit = null;

    protected string | Closure | null $characterLimitEnd = null;

    protected int | Closure | null $wordLimit = null;

    protected string | Closure | null $wordLimitEnd = null;

    protected string | Closure | null $prefix = null;

    protected string | Closure | null $suffix = null;

    protected string | Closure | null $placeholder = null;

    protected string | Closure | null $timezone = null;

    protected bool | Closure $isHtml = false;

    protected bool | Closure $isMarkdown = false;

    public function markdown(bool | Closure $condition = true): static
    {
        $this->isMarkdown = $condition;

        return $this;
    }

    public function date(string $format = null, string $timezone = null): static
    {
        $format ??= Table::$defaultDateDisplayFormat;

        $this->formatStateUsing(static function (TextColumn $column, $state) use ($format, $timezone): ?string {
            if (blank($state)) {
                return null;
            }

            return Carbon::parse($state)
                ->setTimezone($timezone ?? $column->getTimezone())
                ->translatedFormat($format);
        });

        return $this;
    }

    public function dateTime(string $format = null, string $timezone = null): static
    {
        $format ??= Table::$defaultDateTimeDisplayFormat;

        $this->date($format, $timezone);

        return $this;
    }

    public function since(string $timezone = null): static
    {
        $this->formatStateUsing(static function (TextColumn $column, $state) use ($timezone): ?string {
            if (blank($state)) {
                return null;
            }

            return Carbon::parse($state)
                ->setTimezone($timezone ?? $column->getTimezone())
                ->diffForHumans();
        });

        return $this;
    }

    public function money(string | Closure $currency = null, int $divideBy = 0): static
    {
        $this->formatStateUsing(static function (TextColumn $column, $state) use ($currency, $divideBy): ?string {
            if (blank($state)) {
                return null;
            }

            $currency = $column->evaluate($currency) ?? Table::$defaultCurrency;

            return format_money($state, $currency, $divideBy);
        });

        return $this;
    }

    public function numeric(int | Closure $decimalPlaces = null, string | Closure | null $decimalSeparator = '.', string | Closure | null $thousandsSeparator = ','): static
    {
        $this->formatStateUsing(static function (TextColumn $column, $state) use ($decimalPlaces, $decimalSeparator, $thousandsSeparator): ?string {
            if (blank($state)) {
                return null;
            }

            if (! is_numeric($state)) {
                return $state;
            }

            if ($decimalPlaces === null) {
                return format_number($state);
            }

            return number_format(
                $state,
                $column->evaluate($decimalPlaces),
                $column->evaluate($decimalSeparator),
                $column->evaluate($thousandsSeparator),
            );
        });

        return $this;
    }

    public function time(string $format = null, string $timezone = null): static
    {
        $format ??= Table::$defaultTimeDisplayFormat;

        $this->date($format, $timezone);

        return $this;
    }

    public function timezone(string | Closure | null $timezone): static
    {
        $this->timezone = $timezone;

        return $this;
    }

    public function placeholder(string | Closure | null $placeholder): static
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    public function limit(int | Closure | null $length = 100, string | Closure | null $end = '...'): static
    {
        $this->characterLimit = $length;
        $this->characterLimitEnd = $end;

        return $this;
    }

    public function words(int $words = 100, string $end = '...'): static
    {
        $this->wordLimit = $words;
        $this->wordLimitEnd = $end;

        return $this;
    }

    public function prefix(string | Closure | null $prefix): static
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function suffix(string | Closure | null $suffix): static
    {
        $this->suffix = $suffix;

        return $this;
    }

    public function html(bool | Closure $condition = true): static
    {
        $this->isHtml = $condition;

        return $this;
    }

    public function formatStateUsing(?Closure $callback): static
    {
        $this->formatStateUsing = $callback;

        return $this;
    }

    public function formatState(mixed $state): mixed
    {
        if ($state instanceof LabelInterface) {
            $state = $state->getLabel();
        }

        $state = $this->evaluate($this->formatStateUsing ?? $state, [
            'state' => $state,
        ]);

        if ($characterLimit = $this->getCharacterLimit()) {
            $state = Str::limit($state, $characterLimit, $this->getCharacterLimitEnd());
        }

        if ($wordLimit = $this->getWordLimit()) {
            $state = Str::words($state, $wordLimit, $this->getWordLimitEnd());
        }

        if (filled($prefix = $this->getPrefix())) {
            $state = $prefix . $state;
        }

        if (filled($suffix = $this->getSuffix())) {
            $state = $state . $suffix;
        }

        if ($state instanceof HtmlString) {
            return $state;
        }

        if (blank($state)) {
            $state = $this->evaluate($this->placeholder);
        }

        if ($this->isHtml()) {
            return str($state)
                ->when($this->isMarkdown(), fn (Stringable $stringable) => $stringable->markdown())
                ->sanitizeHtml()
                ->toHtmlString();
        }

        return $state;
    }

    public function getCharacterLimit(): ?int
    {
        return $this->evaluate($this->characterLimit);
    }

    public function getCharacterLimitEnd(): ?string
    {
        return $this->evaluate($this->characterLimitEnd);
    }

    public function getWordLimit(): ?int
    {
        return $this->evaluate($this->wordLimit);
    }

    public function getWordLimitEnd(): ?string
    {
        return $this->evaluate($this->wordLimitEnd);
    }

    public function getTimezone(): string
    {
        return $this->evaluate($this->timezone) ?? config('app.timezone');
    }

    public function isHtml(): bool
    {
        return $this->evaluate($this->isHtml) || $this->isMarkdown();
    }

    public function getPrefix(): ?string
    {
        return $this->evaluate($this->prefix);
    }

    public function getSuffix(): ?string
    {
        return $this->evaluate($this->suffix);
    }

    public function isMarkdown(): bool
    {
        return (bool) $this->evaluate($this->isMarkdown);
    }
}
