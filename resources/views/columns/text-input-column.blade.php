@php
    $state = $getState();
    $type = $getType();
@endphp

<div
    x-data="{
        error: undefined,
        state: @js($state),
        isLoading: false,
        isEditing: false,
    }"
    x-init="
        Livewire.hook('message.processed', (component) => {
            if (component.component.id !== @js($this->id)) {
                return
            }

            if (isEditing) {
                return
            }

            if (! $refs.newState) {
                return
            }

            let newState = $refs.newState.value

            if (state === newState) {
                return
            }

            state = newState
        })
    "
    {{
        $attributes
            ->merge($getExtraAttributes(), escape: false)
            ->class(['filament-tables-text-input-column'])
    }}
>
    <input
        type="hidden"
        value="{{ str($state)->replace('"', '\\"') }}"
        x-ref="newState"
    />

    <input
        x-model="state"
        x-on:focus="isEditing = true"
        x-on:blur="isEditing = false"
        x-on:change{{ $type === 'number' ? '.debounce.1s' : null }}="
            isLoading = true
            response = await $wire.updateTableColumnState(
                @js($getName()),
                @js($recordKey),
                $event.target.value,
            )
            error = response?.error ?? undefined
            if (! error) state = response
            isLoading = false
        "
        x-bind:readonly="isLoading"
        wire:loading.attr="readonly"
        x-tooltip="error"
        x-bind:class="{
            'border-gray-300 dark:border-gray-600': ! error,
            'border-danger-600 ring-1 ring-inset ring-danger-600 dark:border-danger-400 dark:ring-danger-400':
                error,
        }"
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->merge($getExtraInputAttributes(), escape: false)
                ->merge([
                    'disabled' => $isDisabled(),
                    'inputmode' => $getInputMode(),
                    'placeholder' => $getPlaceholder(),
                    'step' => $getStep(),
                    'type' => $type,
                ])
                ->class([
                    'ms-0.5 inline-block rounded-lg text-gray-900 shadow-sm outline-none transition duration-75 focus:border-primary-500 focus:ring-1 focus:ring-inset focus:ring-primary-500 disabled:opacity-70 dark:bg-gray-700 dark:text-white dark:focus:border-primary-500 sm:text-sm',
                    match ($getAlignment()) {
                        'center' => 'text-center',
                        'end' => 'text-end',
                        'left' => 'text-left',
                        'right' => 'text-right',
                        'start', null => 'text-start',
                    },
                ])
        }}
    />
</div>
