@php
    $state = $getState();
@endphp

<div
    wire:key="{{ $this->id }}.table.record.{{ $recordKey }}.column.{{ $getName() }}.toggle-column.{{ $state ? 'true' : 'false' }}"
>
    <div
        x-data="{
            error: undefined,
            state: @js((bool) $state),
            isLoading: false,
        }"
        wire:ignore
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->class(['filament-tables-toggle-column'])
        }}
    >
        @php
            $offColor = $getOffColor() ?? 'gray';
            $onColor = $getOnColor() ?? 'primary';
        @endphp

        <button
            role="switch"
            aria-checked="false"
            x-bind:aria-checked="state.toString()"
            x-on:click="
                if (isLoading) {
                    return
                }

                state = ! state

                isLoading = true
                response = await $wire.updateTableColumnState(@js($getName()), @js($recordKey), state)
                error = response?.error ?? undefined

                if (error) {
                    state = ! state
                }

                isLoading = false
            "
            x-tooltip="error"
            x-bind:class="
                (state
                    ? '{{
                        match ($onColor) {
                            'gray' => 'bg-gray-200 dark:bg-gray-700',
                            default => 'bg-custom-600',
                        }
                    }}'
                    : '{{
                        match ($offColor) {
                            'gray' => 'bg-gray-200 dark:bg-gray-700',
                            default => 'bg-custom-600',
                        }
                    }}') +
                    (isLoading ? ' opacity-70 pointer-events-none' : '')
            "
            x-bind:style="
                state
                    ? '{{ \Filament\Support\get_color_css_variables($onColor, shades: [600]) }}'
                    : '{{ \Filament\Support\get_color_css_variables($offColor, shades: [600]) }}'
            "
            @disabled($isDisabled())
            type="button"
            class="relative ms-4 inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent outline-none transition-colors duration-200 ease-in-out disabled:pointer-events-none disabled:opacity-70"
        >
            <span
                class="pointer-events-none relative inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out"
                x-bind:class="{
                    'translate-x-5 rtl:-translate-x-5': state,
                    'translate-x-0': ! state,
                }"
            >
                @if ($hasOffIcon())
                    <x-filament::icon
                        :name="$getOffIcon()"
                        alias="tables::columns.toggle.off"
                        :color="
                            match ($onColor) {
                                'gray' => 'text-gray-400 dark:text-gray-700',
                                default => 'text-custom-600',
                            }
                        "
                        size="h-3 w-3"
                    />
                @endif
            </span>

            <span
                class="absolute inset-0 flex h-full w-full items-center justify-center transition-opacity"
                aria-hidden="true"
                x-bind:class="{
                    'opacity-100 ease-in duration-200': state,
                    'opacity-0 ease-out duration-100': ! state,
                }"
            >
                @if ($hasOnIcon())
                    <x-filament::icon
                        :name="$getOnIcon()"
                        alias="tables::columns.toggle.on"
                        :color="
                            match ($onColor) {
                                'gray' => 'text-gray-400 dark:text-gray-700',
                                default => 'text-custom-600',
                            }
                        "
                        size="h-3 w-3"
                        x-cloak="x-cloak"
                    />
                @endif
            </span>
        </button>
    </div>
</div>
