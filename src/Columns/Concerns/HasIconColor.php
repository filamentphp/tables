<?php

namespace Filament\Tables\Columns\Concerns;

use Closure;

trait HasIconColor
{
    protected string | Closure | null $iconColor = null;

    public function iconColor(string | Closure | null $color): static
    {
        $this->iconColor = $color;

        return $this;
    }

    /**
     * @return string | array<int | string, string | int> | null
     */
    public function getIconColor(mixed $state): string | array | null
    {
        return $this->evaluate($this->iconColor, [
            'state' => $state,
        ]);
    }
}
