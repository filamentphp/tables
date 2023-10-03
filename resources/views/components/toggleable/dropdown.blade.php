@props([
    'form',
    'maxHeight' => null,
    'triggerAction',
    'width' => null,
])

<x-filament::dropdown
    {{ $attributes->class(['filament-tables-column-toggling']) }}
    :max-height="$maxHeight"
    placement="bottom-end"
    shift
    :width="$width"
    wire:key="{{ $this->id }}.table.toggle"
>
    <x-slot name="trigger">
        {{ $triggerAction }}
    </x-slot>

    <div class="p-4">
        {{ $form }}
    </div>
</x-filament::dropdown>
