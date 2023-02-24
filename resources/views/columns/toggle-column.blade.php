@php
    $state = $getState();
@endphp

<div
    x-data="{
        error: undefined,
        state: @js((bool) $state),
        isLoading: false,
    }"
    x-init="
        $watch('state', () => $refs.button.dispatchEvent(new Event('change')))

        Livewire.hook('message.processed', (component) => {
            if (component.component.id !== @js($this->id)) {
                return
            }

            let newState = $refs.newState.value === '1' ? true : false

            if (state === newState) {
                return
            }

            state = newState
        })
    "
    {{ $attributes->merge($getExtraAttributes())->class([
        'filament-tables-toggle-column',
    ]) }}
>
    <input
        type="hidden"
        value="{{ $state ? 1 : 0 }}"
        x-ref="newState"
    />

    <button
        role="switch"
        aria-checked="false"
        x-bind:aria-checked="state.toString()"
        x-on:click="! isLoading && (state = ! state)"
        x-ref="button"
        x-on:change="
            isLoading = true
            response = await $wire.updateTableColumnState(@js($getName()), @js($recordKey), state)
            error = response?.error ?? undefined
            isLoading = false
        "
        x-tooltip="error"
        x-bind:class="{
            'opacity-70 pointer-events-none': isLoading,
            '{{ match ($getOnColor()) {
                'danger' => 'bg-danger-500',
                'secondary' => 'bg-gray-500',
                'success' => 'bg-success-500',
                'warning' => 'bg-warning-500',
                default => 'bg-primary-600',
            } }}': state,
            '{{ match ($getOffColor()) {
                'danger' => 'bg-danger-500',
                'primary' => 'bg-primary-500',
                'success' => 'bg-success-500',
                'warning' => 'bg-warning-500',
                default => 'bg-gray-200',
            } }} @if (config('forms.dark_mode')) dark:bg-white/10 @endif': ! state,
        }"
        {!! $isDisabled() ? 'disabled' : null !!}
        wire:ignore.self
        type="button"
        class="relative inline-flex shrink-0 ml-4 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 outline-none focus:ring-1 focus:ring-offset-1 focus:ring-primary-500 disabled:opacity-70 disabled:cursor-not-allowed disabled:pointer-events-none"
    >
        <span
            class="pointer-events-none relative inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 ease-in-out transition duration-200"
            x-bind:class="{
                'translate-x-5 rtl:-translate-x-5': state,
                'translate-x-0': ! state,
            }"
        >
            <span
                class="absolute inset-0 h-full w-full flex items-center justify-center transition-opacity"
                aria-hidden="true"
                x-bind:class="{
                    'opacity-0 ease-out duration-100': state,
                    'opacity-100 ease-in duration-200': ! state,
                }"
            >
                @if ($hasOffIcon())
                    <x-dynamic-component
                        :component="$getOffIcon()"
                        :class="\Illuminate\Support\Arr::toCssClasses([
                            'h-3 w-3',
                            match ($getOffColor()) {
                                'danger' => 'text-danger-500',
                                'primary' => 'text-primary-500',
                                'success' => 'text-success-500',
                                'warning' => 'text-warning-500',
                                default => 'text-gray-400',
                            },
                        ])"
                    />
                @endif
            </span>

            <span
                class="absolute inset-0 h-full w-full flex items-center justify-center transition-opacity"
                aria-hidden="true"
                x-bind:class="{
                    'opacity-100 ease-in duration-200': state,
                    'opacity-0 ease-out duration-100': ! state,
                }"
            >
                @if ($hasOnIcon())
                    <x-dynamic-component
                        :component="$getOnIcon()"
                        x-cloak
                        :class="\Illuminate\Support\Arr::toCssClasses([
                            'h-3 w-3',
                            match ($getOnColor()) {
                                'danger' => 'text-danger-500',
                                'secondary' => 'text-gray-400',
                                'success' => 'text-success-500',
                                'warning' => 'text-warning-500',
                                default => 'text-primary-500',
                            },
                        ])"
                    />
                @endif
            </span>
        </span>
    </button>
</div>
