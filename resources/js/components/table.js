export default function table() {
    return {
        checkboxClickController: null,

        collapsedGroups: [],

        isLoading: false,

        selectedRecords: new Set(),

        deselectedRecords: new Set(),

        isTrackingDeselectedRecords: false,

        shouldCheckUniqueSelection: true,

        lastCheckedRecord: null,

        livewireId: null,

        init: function () {
            this.livewireId =
                this.$root.closest('[wire\\:id]').attributes['wire:id'].value

            this.$wire.$on('deselectAllTableRecords', () =>
                this.deselectAllRecords(),
            )

            this.$watch('selectedRecords', () => {
                if (!this.shouldCheckUniqueSelection) {
                    this.shouldCheckUniqueSelection = true

                    return
                }

                this.shouldCheckUniqueSelection = false
            })

            this.$nextTick(() => this.watchForCheckboxClicks())

            Livewire.hook('element.init', ({ component }) => {
                if (component.id === this.livewireId) {
                    this.watchForCheckboxClicks()
                }
            })
        },

        mountAction: function (...args) {
            this.$wire.set(
                'isTrackingDeselectedTableRecords',
                this.isTrackingDeselectedRecords,
                false,
            )
            this.$wire.set(
                'selectedTableRecords',
                [...this.selectedRecords],
                false,
            )
            this.$wire.set(
                'deselectedTableRecords',
                [...this.deselectedRecords],
                false,
            )

            this.$wire.mountAction(...args)
        },

        toggleSelectRecordsOnPage: function () {
            const keys = this.getRecordsOnPage()

            if (this.areRecordsSelected(keys)) {
                this.deselectRecords(keys)

                return
            }

            this.selectRecords(keys)
        },

        toggleSelectRecordsInGroup: async function (group) {
            this.isLoading = true

            if (this.areRecordsSelected(this.getRecordsInGroupOnPage(group))) {
                this.deselectRecords(
                    await this.$wire.getGroupedSelectableTableRecordKeys(group),
                )

                return
            }

            this.selectRecords(
                await this.$wire.getGroupedSelectableTableRecordKeys(group),
            )

            this.isLoading = false
        },

        getRecordsInGroupOnPage: function (group) {
            const keys = []

            for (let checkbox of this.$root?.getElementsByClassName(
                'fi-ta-record-checkbox',
            ) ?? []) {
                if (checkbox.dataset.group !== group) {
                    continue
                }

                keys.push(checkbox.value)
            }

            return keys
        },

        getSelectedRecordsCount: function () {
            if (this.isTrackingDeselectedRecords) {
                return (
                    (this.$refs.allSelectableRecordsCount?.value ??
                        this.deselectedRecords.size) -
                    this.deselectedRecords.size
                )
            }

            return this.selectedRecords.size
        },

        getRecordsOnPage: function () {
            const keys = []

            for (let checkbox of this.$root?.getElementsByClassName(
                'fi-ta-record-checkbox',
            ) ?? []) {
                keys.push(checkbox.value)
            }

            return keys
        },

        selectRecords: function (keys) {
            for (let key of keys) {
                if (this.isRecordSelected(key)) {
                    continue
                }

                if (this.isTrackingDeselectedRecords) {
                    this.deselectedRecords.delete(key)

                    continue
                }

                this.selectedRecords.add(key)
            }
        },

        deselectRecords: function (keys) {
            for (let key of keys) {
                if (this.isTrackingDeselectedRecords) {
                    this.deselectedRecords.add(key)

                    continue
                }

                this.selectedRecords.delete(key)
            }
        },

        toggleSelectedRecord: function (key) {
            if (this.isRecordSelected(key)) {
                this.deselectRecords([key])

                return
            }

            this.selectRecords([key])
        },

        selectAllRecords: async function () {
            this.isTrackingDeselectedRecords = true
            this.selectedRecords = new Set()
            this.deselectedRecords = new Set()
        },

        deselectAllRecords: function () {
            this.isTrackingDeselectedRecords = false
            this.selectedRecords = new Set()
            this.deselectedRecords = new Set()
        },

        isRecordSelected: function (key) {
            if (this.isTrackingDeselectedRecords) {
                return !this.deselectedRecords.has(key)
            }

            return this.selectedRecords.has(key)
        },

        areRecordsSelected: function (keys) {
            return keys.every((key) => this.isRecordSelected(key))
        },

        toggleCollapseGroup: function (group) {
            if (this.isGroupCollapsed(group)) {
                this.collapsedGroups.splice(
                    this.collapsedGroups.indexOf(group),
                    1,
                )

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

        watchForCheckboxClicks: function () {
            if (this.checkboxClickController) {
                this.checkboxClickController.abort()
            }

            this.checkboxClickController = new AbortController()

            const { signal } = this.checkboxClickController

            this.$root?.addEventListener(
                'click',
                (event) =>
                    event.target?.matches('.fi-ta-record-checkbox') &&
                    this.handleCheckboxClick(event, event.target),
                { signal },
            )
        },

        handleCheckboxClick: function (event, checkbox) {
            if (!this.lastChecked) {
                this.lastChecked = checkbox

                return
            }

            if (event.shiftKey) {
                let checkboxes = Array.from(
                    this.$root?.getElementsByClassName(
                        'fi-ta-record-checkbox',
                    ) ?? [],
                )

                if (!checkboxes.includes(this.lastChecked)) {
                    this.lastChecked = checkbox

                    return
                }

                let start = checkboxes.indexOf(this.lastChecked)
                let end = checkboxes.indexOf(checkbox)

                let range = [start, end].sort((a, b) => a - b)
                let values = []

                for (let i = range[0]; i <= range[1]; i++) {
                    checkboxes[i].checked = checkbox.checked

                    values.push(checkboxes[i].value)
                }

                if (checkbox.checked) {
                    this.selectRecords(values)
                } else {
                    this.deselectRecords(values)
                }
            }

            this.lastChecked = checkbox
        },
    }
}
